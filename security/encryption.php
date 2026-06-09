<?php
/**
 * SwiftChat Encryption Service
 * AES-256-GCM encryption and Argon2id password hashing
 */

class Encryption {
    private $cipher = 'aes-256-gcm';
    private $key;
    
    public function __construct() {
        $this->key = getenv('ENCRYPTION_KEY') ?: 'default-key-change-in-production';
    }
    
    /**
     * Encrypt data
     */
    public function encrypt($data): string {
        $iv = random_bytes(16);
        $tag = '';
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    public function decrypt($data): string {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        return openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
    }
    
    /**
     * Hash password with Argon2id
     */
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verify password against hash
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure random token
     */
    public function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
}
?>