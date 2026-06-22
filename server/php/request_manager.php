<?php
/**
 * SwiftChat Request Manager
 * Handles chat requests: send, accept, reject, block
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../security/sql_protection.php';
require_once __DIR__ . '/../../security/input_validator.php';
require_once __DIR__ . '/../../security/rate_limiter.php';

class RequestManager {
    private $db;
    private $sqlProtection;
    private $rateLimiter;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->sqlProtection = new SQLProtection($db);
        $this->rateLimiter = new RateLimiter($db);
    }
    
    /**
     * Send a chat request to another user
     */
    public function sendRequest($fromUserId, $toUsername, $message = '') {
        // Rate limit: max 10 requests per hour
        if (!$this->rateLimiter->check('send_request_' . $fromUserId, 10, 3600)) {
            return [
                'success' => false,
                'message' => 'Too many requests. Please try again later.'
            ];
        }
        
        // Find target user
        $stmt = $this->db->prepare("SELECT id, username FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $toUsername, $fromUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'User not found.'
            ];
        }
        
        $targetUser = $result->fetch_assoc();
        $toUserId = $targetUser['id'];
        
        // Check if blocked
        if ($this->isBlocked($toUserId, $fromUserId)) {
            return [
                'success' => false,
                'message' => 'You cannot send a request to this user.'
            ];
        }
        
        if ($this->isBlocked($fromUserId, $toUserId)) {
            return [
                'success' => false,
                'message' => 'You have blocked this user. Unblock to send request.'
            ];
        }
        
        // Check existing request
        $existing = $this->getExistingRequest($fromUserId, $toUserId);
        if ($existing) {
            switch ($existing['status']) {
                case 'pending':
                    return [
                        'success' => false,
                        'message' => 'Request already pending.'
                    ];
                case 'accepted':
                    return [
                        'success' => false,
                        'message' => 'You are already connected.'
                    ];
                case 'rejected':
                    // Allow re-request after 24 hours
                    $rejectedTime = strtotime($existing['updated_at']);
                    if (time() - $rejectedTime < 86400) {
                        $hoursLeft = ceil((86400 - (time() - $rejectedTime)) / 3600);
                        return [
                            'success' => false,
                            'message' => "Request was rejected. Try again in {$hoursLeft} hours."
                        ];
                    }
                    break;
                case 'blocked':
                    return [
                        'success' => false,
                        'message' => 'Cannot send request.'
                    ];
            }
        }
        
        // Sanitize message
        $message = strip_tags(trim($message));
        $message = substr($message, 0, 500);
        
        // Insert or update request
        $stmt = $this->db->prepare(
            "INSERT INTO chat_requests (from_user, to_user, status, request_message) 
             VALUES (?, ?, 'pending', ?)
             ON DUPLICATE KEY UPDATE status = 'pending', request_message = ?, updated_at = NOW()"
        );
        $stmt->bind_param("iiss", $fromUserId, $toUserId, $message, $message);
        
        if ($stmt->execute()) {
            // Create notification for target user
            $this->createNotification(
                $toUserId,
                'chat_request',
                'New Chat Request',
                "{$targetUser['username']} wants to chat with you!",
                $fromUserId
            );
            
            return [
                'success' => true,
                'message' => 'Chat request sent successfully!',
                'request_id' => $stmt->insert_id
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to send request.'
        ];
    }
    
    /**
     * Accept a chat request
     */
    public function acceptRequest($userId, $requestId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM chat_requests WHERE id = ? AND to_user = ? AND status = 'pending'"
        );
        $stmt->bind_param("ii", $requestId, $userId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        
        if (!$request) {
            return [
                'success' => false,
                'message' => 'Request not found or already processed.'
            ];
        }
        
        // Update request status
        $stmt = $this->db->prepare(
            "UPDATE chat_requests SET status = 'accepted', responded_at = NOW() WHERE id = ?"
        );
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        
        // Add to contacts (both ways)
        $this->addContact($userId, $request['from_user']);
        $this->addContact($request['from_user'], $userId);
        
        // Create notification
        $this->createNotification(
            $request['from_user'],
            'request_accepted',
            'Request Accepted!',
            'Your chat request has been accepted!',
            $userId
        );
        
        return [
            'success' => true,
            'message' => 'Request accepted! You can now chat.',
            'contact_id' => $request['from_user']
        ];
    }
    
    /**
     * Reject a chat request
     */
    public function rejectRequest($userId, $requestId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM chat_requests WHERE id = ? AND to_user = ? AND status = 'pending'"
        );
        $stmt->bind_param("ii", $requestId, $userId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        
        if (!$request) {
            return [
                'success' => false,
                'message' => 'Request not found.'
            ];
        }
        
        // Update status
        $stmt = $this->db->prepare(
            "UPDATE chat_requests SET status = 'rejected', responded_at = NOW() WHERE id = ?"
        );
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        
        // Notify sender
        $this->createNotification(
            $request['from_user'],
            'request_rejected',
            'Request Declined',
            'Your chat request was declined.',
            $userId
        );
        
        return [
            'success' => true,
            'message' => 'Request rejected.'
        ];
    }
    
    /**
     * Block a user
     */
    public function blockUser($userId, $targetUserId, $reason = '') {
        // Check if already blocked
        if ($this->isBlocked($userId, $targetUserId)) {
            return [
                'success' => false,
                'message' => 'User already blocked.'
            ];
        }
        
        // Insert block
        $stmt = $this->db->prepare(
            "INSERT INTO blocked_users (blocker_id, blocked_id, reason) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iis", $userId, $targetUserId, $reason);
        
        if ($stmt->execute()) {
            // Remove from contacts
            $this->removeContact($userId, $targetUserId);
            $this->removeContact($targetUserId, $userId);
            
            // Cancel any pending requests
            $this->db->query(
                "UPDATE chat_requests SET status = 'blocked' 
                 WHERE (from_user = {$userId} AND to_user = {$targetUserId})
                    OR (from_user = {$targetUserId} AND to_user = {$userId})"
            );
            
            return [
                'success' => true,
                'message' => 'User blocked successfully.'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to block user.'
        ];
    }
    
    /**
     * Unblock a user
     */
    public function unblockUser($userId, $targetUserId) {
        $stmt = $this->db->prepare(
            "DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?"
        );
        $stmt->bind_param("ii", $userId, $targetUserId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return [
                'success' => true,
                'message' => 'User unblocked.'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'User not found in block list.'
        ];
    }
    
    /**
     * Get pending requests for a user
     */
    public function getPendingRequests($userId) {
        $stmt = $this->db->prepare(
            "SELECT cr.id, cr.from_user, cr.request_message, cr.created_at,
                    u.username as from_username
             FROM chat_requests cr
             JOIN users u ON cr.from_user = u.id
             WHERE cr.to_user = ? AND cr.status = 'pending'
             ORDER BY cr.created_at DESC"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        
        return [
            'success' => true,
            'requests' => $requests
        ];
    }
    
    /**
     * Get sent requests status
     */
    public function getSentRequests($userId) {
        $stmt = $this->db->prepare(
            "SELECT cr.id, cr.to_user, cr.status, cr.request_message, cr.created_at,
                    u.username as to_username
             FROM chat_requests cr
             JOIN users u ON cr.to_user = u.id
             WHERE cr.from_user = ?
             ORDER BY cr.created_at DESC
             LIMIT 20"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        
        return [
            'success' => true,
            'requests' => $requests
        ];
    }
    
    /**
     * Check if user can message another user
     */
    public function canMessage($fromUserId, $toUserId) {
        // Check if blocked
        if ($this->isBlocked($fromUserId, $toUserId) || $this->isBlocked($toUserId, $fromUserId)) {
            return [
                'can_message' => false,
                'reason' => 'blocked'
            ];
        }
        
        // Check if request accepted
        $stmt = $this->db->prepare(
            "SELECT id FROM chat_requests 
             WHERE ((from_user = ? AND to_user = ?) OR (from_user = ? AND to_user = ?))
             AND status = 'accepted'"
        );
        $stmt->bind_param("iiii", $fromUserId, $toUserId, $toUserId, $fromUserId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return [
                'can_message' => true,
                'reason' => 'accepted'
            ];
        }
        
        // Check if in contacts
        $stmt = $this->db->prepare(
            "SELECT id FROM contacts WHERE user_id = ? AND contact_id = ?"
        );
        $stmt->bind_param("ii", $fromUserId, $toUserId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return [
                'can_message' => true,
                'reason' => 'contact'
            ];
        }
        
        return [
            'can_message' => false,
            'reason' => 'no_connection'
        ];
    }
    
    /**
     * Get blocked users list
     */
    public function getBlockedUsers($userId) {
        $stmt = $this->db->prepare(
            "SELECT bu.id, bu.blocked_id, bu.reason, bu.created_at,
                    u.username as blocked_username
             FROM blocked_users bu
             JOIN users u ON bu.blocked_id = u.id
             WHERE bu.blocker_id = ?
             ORDER BY bu.created_at DESC"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $blocked = [];
        while ($row = $result->fetch_assoc()) {
            $blocked[] = $row;
        }
        
        return [
            'success' => true,
            'blocked_users' => $blocked
        ];
    }
    
    // ========== HELPER METHODS ==========
    
    private function isBlocked($blockerId, $blockedId) {
        $stmt = $this->db->prepare(
            "SELECT id FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?"
        );
        $stmt->bind_param("ii", $blockerId, $blockedId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    private function getExistingRequest($fromUserId, $toUserId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM chat_requests 
             WHERE from_user = ? AND to_user = ?
             ORDER BY updated_at DESC LIMIT 1"
        );
        $stmt->bind_param("ii", $fromUserId, $toUserId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function addContact($userId, $contactId) {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO contacts (user_id, contact_id) VALUES (?, ?)"
        );
        $stmt->bind_param("ii", $userId, $contactId);
        $stmt->execute();
    }
    
    private function removeContact($userId, $contactId) {
        $stmt = $this->db->prepare(
            "DELETE FROM contacts WHERE user_id = ? AND contact_id = ?"
        );
        $stmt->bind_param("ii", $userId, $contactId);
        $stmt->execute();
    }
    
    private function createNotification($userId, $type, $title, $message, $referenceId) {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, reference_id)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isssi", $userId, $type, $title, $message, $referenceId);
        $stmt->execute();
    }
}
?>