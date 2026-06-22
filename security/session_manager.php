<?php
/**
 * SwiftChat Secure Session Manager
 * Database-backed sessions with security features
 */

class SecureSession {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->configure();
        session_start();
        $this->validateSession();
    }
    
    private function configure() {
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        session_set_save_handler(
            [$this, 'open'], [$this, 'close'], [$this, 'read'],
            [$this, 'write'], [$this, 'destroy'], [$this, 'gc']
        );
    }
    
    private function validateSession() {
        if (isset($_SESSION['user_id'])) {
            if (($_SESSION['ip'] ?? '') !== $_SERVER['REMOTE_ADDR']) {
                $this->destroy(session_id());
                session_start();
            }
        }
    }
    
    public function open($path, $name) { return true; }
    public function close() { return true; }
    
    public function read($id) {
        $stmt = $this->db->prepare("SELECT data FROM sessions WHERE session_id = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['data'] ?? '';
    }
    
    public function write($id, $data) {
        $expires = date('Y-m-d H:i:s', time() + 1800);
        $stmt = $this->db->prepare("REPLACE INTO sessions (session_id, data, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $id, $data, $expires);
        return $stmt->execute();
    }
    
    public function destroy($id) {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE session_id = ?");
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }
    
    public function gc($maxlifetime) {
        $this->db->query("DELETE FROM sessions WHERE expires_at < NOW()");
        return true;
    }
}
?>