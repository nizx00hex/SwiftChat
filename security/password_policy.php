<?php
/**
 * SwiftChat Password Policy Enforcer
 */

class PasswordPolicy {
    private $minLength = 8;
    private $requireUpper = true;
    private $requireLower = true;
    private $requireNumber = true;
    private $requireSpecial = true;
    
    /**
     * Validate password strength
     */
    public function validate($password): array {
        $errors = [];
        if (strlen($password) < $this->minLength) {
            $errors[] = "Minimum {$this->minLength} characters";
        }
        if ($this->requireUpper && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "At least one uppercase letter";
        }
        if ($this->requireLower && !preg_match('/[a-z]/', $password)) {
            $errors[] = "At least one lowercase letter";
        }
        if ($this->requireNumber && !preg_match('/[0-9]/', $password)) {
            $errors[] = "At least one number";
        }
        if ($this->requireSpecial && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "At least one special character";
        }
        return $errors;
    }
    
    /**
     * Check if password is strong
     */
    public function isStrong($password): bool {
        return empty($this->validate($password));
    }
}
?>