<?php
/**
 * SwiftChat Input Validator
 * Comprehensive input validation with custom rules
 */

class InputValidator {
    private $errors = [];
    
    /**
     * Validate data against rules
     */
    public function validate(array $data, array $rules): array {
        $sanitized = [];
        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $rulesArray = explode('|', $ruleSet);
            foreach ($rulesArray as $rule) {
                $params = explode(':', $rule);
                $ruleName = $params[0];
                $ruleParams = array_slice($params, 1);
                $method = 'rule' . ucfirst($ruleName);
                if (method_exists($this, $method)) {
                    $value = $this->$method($field, $value, $ruleParams, $data);
                }
            }
            if (!isset($this->errors[$field])) {
                $sanitized[$field] = $value;
            }
        }
        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }
        return $sanitized;
    }
    
    private function ruleRequired($field, $value) {
        if (empty($value) && $value !== '0') {
            $this->errors[$field][] = "$field is required";
        }
        return $value;
    }
    
    private function ruleEmail($field, $value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Invalid email format";
        }
        return $value;
    }
    
    private function ruleMin($field, $value, $params) {
        if (strlen($value) < $params[0]) {
            $this->errors[$field][] = "$field must be at least {$params[0]} characters";
        }
        return $value;
    }
    
    private function ruleMax($field, $value, $params) {
        if (strlen($value) > $params[0]) {
            $this->errors[$field][] = "$field must not exceed {$params[0]} characters";
        }
        return $value;
    }
    
    private function ruleAlphanumeric($field, $value) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            $this->errors[$field][] = "$field may only contain letters, numbers, and underscores";
        }
        return $value;
    }
    
    private function ruleNumeric($field, $value) {
        if (!is_numeric($value)) {
            $this->errors[$field][] = "$field must be a number";
        }
        return $value;
    }
}

class ValidationException extends Exception {
    private $errors;
    public function __construct(array $errors) {
        $this->errors = $errors;
        parent::__construct('Validation failed');
    }
    public function getErrors(): array {
        return $this->errors;
    }
}
?>