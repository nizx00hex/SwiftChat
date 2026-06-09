<?php
/**
 * SwiftChat Audit Logger
 * Logs security-relevant events to file and database
 */

class AuditLogger {
    private $db;
    private $logFile;
    
    public function __construct($db) {
        $this->db = $db;
        $this->logFile = __DIR__ . '/../logs/audit.log';
    }
    
    /**
     * Log an event
     */
    public function log($action, $description, $severity = 'INFO', $userId = null, $metadata = []) {
        $entry = [
            'timestamp' => date('c'),
            'action' => $action,
            'description' => $description,
            'severity' => $severity,
            'user_id' => $userId ?? $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'metadata' => $metadata
        ];
        
        // Write to file
        error_log(json_encode($entry) . "\n", 3, $this->logFile);
        
        // Store in database for critical events
        if (in_array($severity, ['CRITICAL', 'EMERGENCY'])) {
            $stmt = $this->db->prepare(
                "INSERT INTO audit_logs (user_id, action, description, details, ip_address, severity) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $json = json_encode($metadata);
            $stmt->bind_param("isssss", $entry['user_id'], $action, $description, $json, $entry['ip'], $severity);
            $stmt->execute();
        }
    }
}
?>