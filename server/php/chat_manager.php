<?php
/**
 * SwiftChat Chat Manager
 * Handles messaging between connected users only
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/request_manager.php';

class ChatManager {
    private $db;
    private $requestManager;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->requestManager = new RequestManager();
    }
    
    /**
     * Send message (only if connection exists)
     */
    public function sendMessage($fromUserId, $toUsername, $message) {
        // Find target user
        $stmt = $this->db->prepare("SELECT id, username FROM users WHERE username = ?");
        $stmt->bind_param("s", $toUsername);
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
        
        // Check if can message
        $canMessage = $this->requestManager->canMessage($fromUserId, $toUserId);
        
        if (!$canMessage['can_message']) {
            switch ($canMessage['reason']) {
                case 'blocked':
                    return ['success' => false, 'message' => 'Cannot send message. User blocked.'];
                case 'no_connection':
                    return [
                        'success' => false, 
                        'message' => 'You are not connected. Send a chat request first.',
                        'require_request' => true
                    ];
                default:
                    return ['success' => false, 'message' => 'Cannot send message.'];
            }
        }
        
        // Validate message
        $message = trim($message);
        if (empty($message)) {
            return ['success' => false, 'message' => 'Message cannot be empty.'];
        }
        if (strlen($message) > 5000) {
            return ['success' => false, 'message' => 'Message too long (max 5000 characters).'];
        }
        
        // Sanitize
        $message = strip_tags($message);
        
        // Store message
        $stmt = $this->db->prepare(
            "INSERT INTO messages (from_user, to_user, message, created_at) VALUES (?, ?, ?, NOW())"
        );
        $stmt->bind_param("iis", $fromUserId, $toUserId, $message);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Message sent!',
                'message_id' => $stmt->insert_id,
                'to_user_id' => $toUserId
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to send message.'];
    }
    
    /**
     * Get conversation history
     */
    public function getConversation($userId, $contactId, $limit = 50, $offset = 0) {
        // Verify connection exists
        $canMessage = $this->requestManager->canMessage($userId, $contactId);
        if (!$canMessage['can_message']) {
            return ['success' => false, 'message' => 'No connection with this user.'];
        }
        
        $stmt = $this->db->prepare(
            "SELECT * FROM messages 
             WHERE (from_user = ? AND to_user = ?) OR (from_user = ? AND to_user = ?)
             ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->bind_param("iiiiii", $userId, $contactId, $contactId, $userId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        return [
            'success' => true,
            'messages' => array_reverse($messages)
        ];
    }
}
?>