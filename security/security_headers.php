<?php
/**
 * SwiftChat Security Headers
 * Applies all recommended security headers
 */

class SecurityHeaders {
    public function apply() {
        $this->HSTS();
        $this->XFrameOptions();
        $this->XContentTypeOptions();
        $this->XSSProtection();
        $this->ReferrerPolicy();
        $this->PermissionsPolicy();
        $this->removeServerSignature();
    }
    
    private function HSTS() {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    private function XFrameOptions() {
        header('X-Frame-Options: DENY');
    }
    
    private function XContentTypeOptions() {
        header('X-Content-Type-Options: nosniff');
    }
    
    private function XSSProtection() {
        header('X-XSS-Protection: 1; mode=block');
    }
    
    private function ReferrerPolicy() {
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    private function PermissionsPolicy() {
        header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    }
    
    private function removeServerSignature() {
        header_remove('X-Powered-By');
    }
}
?>