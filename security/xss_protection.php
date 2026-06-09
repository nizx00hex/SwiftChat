<?php
/**
 * SwiftChat XSS Protection
 * Prevents Cross-Site Scripting attacks
 */

class XSSProtection {
    private $csp_nonce;
    
    public function __construct() {
        $this->csp_nonce = bin2hex(random_bytes(16));
    }
    
    /**
     * Encode string for safe HTML output
     */
    public function encodeHTML($string) {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Encode for JavaScript context
     */
    public function encodeJS($string) {
        return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    
    /**
     * Encode for HTML attribute
     */
    public function encodeAttribute($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Encode for URL parameter
     */
    public function encodeURL($string) {
        return urlencode($string);
    }
    
    /**
     * Sanitize user-generated HTML (allow only safe tags)
     */
    public function sanitizeHTML($html) {
        $allowed_tags = '<p><br><strong><em><u><ul><ol><li><a><blockquote><code><pre>';
        $clean = strip_tags($html, $allowed_tags);
        
        // Remove event handlers and javascript: URLs
        $clean = preg_replace('/\bon\w+\s*=\s*"[^"]*"/i', '', $clean);
        $clean = preg_replace('/\bon\w+\s*=\s*\'[^\']*\'/i', '', $clean);
        $clean = preg_replace('/href\s*=\s*"javascript:[^"]*"/i', 'href="#"', $clean);
        
        return $clean;
    }
    
    /**
     * Set Content Security Policy header
     */
    public function setCSPHeader() {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$this->csp_nonce}' 'strict-dynamic'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self' ws://localhost:8080",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        header("Content-Security-Policy: " . implode('; ', $csp));
    }
    
    /**
     * Get nonce for inline scripts
     */
    public function getNonce() {
        return $this->csp_nonce;
    }
    
    /**
     * Filter input array recursively
     */
    public function filterInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'filterInput'], $data);
        }
        $data = str_replace(chr(0), '', $data);
        return $data;
    }
}
?>