<?php
/**
 * SwiftChat File Scanner
 * Scans uploaded files for malware (basic signature check)
 */

class FileScanner {
    private $maxSize = 10485760; // 10 MB
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    
    /**
     * Validate uploaded file
     */
    public function validateFile($file): bool {
        if ($file['error'] !== UPLOAD_ERR_OK) return false;
        if ($file['size'] > $this->maxSize) return false;
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        return in_array($mime, $this->allowedTypes);
    }
    
    /**
     * Scan file content for suspicious patterns
     */
    public function scanContent($tmpPath): bool {
        $content = file_get_contents($tmpPath);
        // Simple check for PHP/JavaScript tags
        if (preg_match('/<\?(php|=)/i', $content) || preg_match('/<script/i', $content)) {
            return false;
        }
        return true;
    }
}
?>