<?php
/**
 * SwiftChat CSRF Protection
 * Prevents Cross-Site Request Forgery attacks
 */

class CSRFProtection {
    private $token_name = 'csrf_token';
    private $cookie_name = 'csrf_cookie';
    private $header_name = 'X-CSRF-Token';
    private $token_expiry = 3600;
    private $max_tokens = 5;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Generate new CSRF token
     */
    public function generateToken() {
        $token = bin2hex(random_bytes(32));
        $token_data = [
            'token' => $token,
            'expires' => time() + $this->token_expiry,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        // Store in session (support multiple tokens for multiple tabs)
        if (!isset($_SESSION[$this->token_name])) {
            $_SESSION[$this->token_name] = [];
        }
        
        $_SESSION[$this->token_name][] = $token_data;
        
        // Keep only last N tokens
        if (count($_SESSION[$this->token_name]) > $this->max_tokens) {
            array_shift($_SESSION[$this->token_name]);
        }
        
        // Set cookie for double submit pattern
        $this->setCookie($token);
        
        return $token;
    }
    
    /**
     * Get HTML hidden field
     */
    public function getTokenField() {
        $token = $this->generateToken();
        return "<input type='hidden' name='{$this->token_name}' value='{$token}'>";
    }
    
    /**
     * Get token for meta tag
     */
    public function getMetaTag() {
        $token = $this->generateToken();
        return "<meta name='csrf-token' content='{$token}'>";
    }
    
    /**
     * Get token for JavaScript
     */
    public function getTokenForJS() {
        return $this->generateToken();
    }
    
    /**
     * Validate CSRF token
     */
    public function validateToken($token = null) {
        // Get token from various sources
        if ($token === null) {
            $token = $this->getTokenFromRequest();
        }
        
        if (!$token || !isset($_SESSION[$this->token_name])) {
            $this->logFailure('No token provided');
            return false;
        }
        
        // Check against all stored tokens
        foreach ($_SESSION[$this->token_name] as $key => $stored) {
            if (hash_equals($stored['token'], $token)) {
                // Check expiry
                if ($stored['expires'] < time()) {
                    unset($_SESSION[$this->token_name][$key]);
                    $this->logFailure('Token expired');
                    return false;
                }
                
                // Check IP binding (optional - may cause issues with proxies)
                // if ($stored['ip'] !== $this->getClientIP()) {
                //     $this->logFailure('IP mismatch');
                //     return false;
                // }
                
                // Token valid - remove it (one-time use)
                unset($_SESSION[$this->token_name][$key]);
                $_SESSION[$this->token_name] = array_values($_SESSION[$this->token_name]);
                
                return true;
            }
        }
        
        $this->logFailure('Token not found');
        return false;
    }
    
    /**
     * Validate double submit cookie
     */
    public function validateDoubleSubmit() {
        $cookie_token = $_COOKIE[$this->cookie_name] ?? '';
        $header_token = $_SERVER['HTTP_' . str_replace('-', '_', $this->header_name)] ?? '';
        
        if (!$cookie_token || !$header_token) {
            return false;
        }
        
        return hash_equals($cookie_token, $header_token);
    }
    
    /**
     * Require CSRF validation for state-changing requests
     */
    public function requireCSRF() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            if (!$this->validateToken() && !$this->validateDoubleSubmit()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'CSRF token validation failed',
                    'code' => 'CSRF_ERROR',
                    'message' => 'Security token is invalid or expired. Please refresh the page.'
                ]);
                exit;
            }
        }
    }
    
    /**
     * Get token from request
     */
    private function getTokenFromRequest() {
        // Check POST body
        if (isset($_POST[$this->token_name])) {
            return $_POST[$this->token_name];
        }
        
        // Check JSON body
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input[$this->token_name])) {
            return $input[$this->token_name];
        }
        
        // Check headers
        $header = $_SERVER['HTTP_' . str_replace('-', '_', $this->header_name)] ?? '';
        if ($header) {
            return $header;
        }
        
        // Check alternative header
        $alt_header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if ($alt_header) {
            return $alt_header;
        }
        
        return null;
    }
    
    /**
     * Set CSRF cookie
     */
    private function setCookie($token) {
        // if (headers_sent()) {
        //     die("Headers already sent. Fix output before cookie/session.");
        // }
        setcookie(
            $this->cookie_name,
            $token,
            [
                'expires' => time() + $this->token_expiry,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => false, // JavaScript needs to read this
                'samesite' => 'Strict'
            ]
        );
    }
    
    /**
     * Get client IP
     */
    private function getClientIP() {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        
        return '127.0.0.1';
    }
    
    /**
     * Log CSRF failure
     */
    private function logFailure($reason) {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $this->getClientIP(),
            'reason' => $reason,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $logFile = __DIR__ . '/../logs/csrf.log';
        error_log(json_encode($log) . "\n", 3, $logFile);
    }
    
    /**
     * Clean expired tokens
     */
    public function cleanExpiredTokens() {
        if (isset($_SESSION[$this->token_name])) {
            $_SESSION[$this->token_name] = array_filter(
                $_SESSION[$this->token_name],
                function($token) {
                    return $token['expires'] > time();
                }
            );
            $_SESSION[$this->token_name] = array_values($_SESSION[$this->token_name]);
        }
    }
}
?>