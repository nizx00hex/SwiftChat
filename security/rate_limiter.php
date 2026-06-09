<?php
/**
 * SwiftChat Rate Limiter
 * Prevents abuse by limiting request frequency
 */

class RateLimiter {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Check if request is within rate limit
     */
    public function check($key, $maxRequests = 60, $windowSeconds = 60): bool {
        $ip = $_SERVER['REMOTE_ADDR'];
        $endpoint = $key;
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM rate_limits 
             WHERE ip_address = ? AND endpoint = ? 
             AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
        $stmt->bind_param("ssi", $ip, $endpoint, $windowSeconds);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] >= $maxRequests) {
            return false;
        }
        
        $stmt = $this->db->prepare(
            "INSERT INTO rate_limits (ip_address, endpoint) VALUES (?, ?)"
        );
        $stmt->bind_param("ss", $ip, $endpoint);
        $stmt->execute();
        return true;
    }
    
    /**
     * Clean old rate limit entries
     */
    public function cleanup() {
        $this->db->query("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    }
}
?>