<?php
/**
 * SwiftChat SSRF Protection
 * Prevents Server-Side Request Forgery attacks
 */

class SSRFProtection {
    private $allowed_hosts = [];
    private $blocked_ports = [];
    private $blocked_schemes = [];
    private $max_redirects = 3;
    private $timeout = 10;
    
    public function __construct() {
        // Whitelist allowed domains
        $this->allowed_hosts = [
            'api.swiftchat.com',
            'localhost',
            '127.0.0.1'
        ];
        
        // Block dangerous ports
        $this->blocked_ports = [
            22,     // SSH
            25,     // SMTP
            53,     // DNS
            110,    // POP3
            143,    // IMAP
            465,    // SMTPS
            587,    // SMTP
            993,    // IMAPS
            995,    // POP3S
            3306,   // MySQL
            5432,   // PostgreSQL
            6379,   // Redis
            11211,  // Memcached
            27017,  // MongoDB
            27018,  // MongoDB
            27019   // MongoDB
        ];
        
        // Block dangerous schemes
        $this->blocked_schemes = [
            'file',
            'ftp',
            'gopher',
            'dict',
            'ldap',
            'ssh',
            'telnet'
        ];
    }
    
    /**
     * Validate URL before making request
     */
    public function validateURL($url) {
        // Parse URL
        $parsed = parse_url($url);
        
        if (!$parsed || !isset($parsed['host'])) {
            throw new Exception('Invalid URL format');
        }
        
        // Check scheme
        $scheme = strtolower($parsed['scheme'] ?? 'http');
        if (in_array($scheme, $this->blocked_schemes)) {
            $this->logAttempt($url, 'Blocked scheme: ' . $scheme);
            return false;
        }
        
        if (!in_array($scheme, ['http', 'https'])) {
            $this->logAttempt($url, 'Invalid scheme: ' . $scheme);
            return false;
        }
        
        $host = $parsed['host'];
        
        // Block raw IP addresses
        if ($this->isIPAddress($host)) {
            if (!$this->isAllowedIP($host)) {
                $this->logAttempt($url, 'Blocked IP: ' . $host);
                return false;
            }
        } else {
            // Resolve domain to IP
            $ip = gethostbyname($host);
            
            // Check if resolution was successful
            if ($ip === $host) {
                $this->logAttempt($url, 'DNS resolution failed');
                return false;
            }
            
            // Check if resolved IP is private/internal
            if ($this->isInternalIP($ip)) {
                $this->logAttempt($url, 'Resolved to internal IP: ' . $ip);
                return false;
            }
        }
        
        // Check port
        $port = isset($parsed['port']) ? (int)$parsed['port'] : ($scheme === 'https' ? 443 : 80);
        if (in_array($port, $this->blocked_ports)) {
            $this->logAttempt($url, 'Blocked port: ' . $port);
            return false;
        }
        
        // Check host whitelist
        if (!empty($this->allowed_hosts) && !$this->isAllowedHost($host)) {
            $this->logAttempt($url, 'Host not in whitelist');
            return false;
        }
        
        return true;
    }
    
    /**
     * Safe HTTP request with SSRF protection
     */
    public function safeRequest($url, $options = []) {
        // Validate URL first
        if (!$this->validateURL($url)) {
            throw new Exception('URL failed SSRF validation');
        }
        
        $ch = curl_init();
        
        $default_options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_MAXREDIRS => $this->max_redirects,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'SwiftChat/1.0',
            CURLOPT_DNS_USE_GLOBAL_CACHE => false,
            CURLOPT_DNS_CACHE_TIMEOUT => 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_WHATEVER
        ];
        
        // Merge custom options
        foreach ($options as $key => $value) {
            $default_options[$key] = $value;
        }
        
        curl_setopt_array($ch, $default_options);
        
        // Execute request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Request failed: ' . $error);
        }
        
        return [
            'body' => $response,
            'status_code' => $http_code
        ];
    }
    
    /**
     * Check if string is IP address
     */
    private function isIPAddress($host) {
        return filter_var($host, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Check if IP is internal/private
     */
    private function isInternalIP($ip) {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
    
    /**
     * Check if IP is allowed
     */
    private function isAllowedIP($ip) {
        return in_array($ip, $this->allowed_hosts);
    }
    
    /**
     * Check if host is allowed
     */
    private function isAllowedHost($host) {
        foreach ($this->allowed_hosts as $allowed) {
            // Exact match
            if ($host === $allowed) {
                return true;
            }
            
            // Wildcard match
            if (strpos($allowed, '*') !== false) {
                $pattern = str_replace('\*', '.*', preg_quote($allowed, '/'));
                if (preg_match('/^' . $pattern . '$/', $host)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Add allowed host
     */
    public function addAllowedHost($host) {
        $this->allowed_hosts[] = $host;
    }
    
    /**
     * Log SSRF attempt
     */
    private function logAttempt($url, $reason) {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $url,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        $logFile = __DIR__ . '/../logs/ssrf.log';
        error_log(json_encode($log) . "\n", 3, $logFile);
    }
}
?>