<?php
/**
 * SwiftChat Notification Service
 * Handles real-time notifications for users
 */

require_once __DIR__ . '/../../config/database.php';

class NotificationService {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications($userId, $limit = 20) {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications 
             WHERE user_id = ? AND is_read = FALSE 
             ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        return [
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => count($notifications)
        ];
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $notificationId, $userId);
        $stmt->execute();
        
        return ['success' => true];
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = TRUE WHERE user_id = ?"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        return ['success' => true];
    }
    
    /**
     * Get unread count only
     */
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'success' => true,
            'count' => (int)$result['count']
        ];
    }
}
?>