<?php
/**
 * SwiftChat SQL Injection Protection
 * Prevents SQL Injection attacks with multiple layers
 */

class SQLProtection {
    private $db;
    private $dangerous_keywords = [];
    private $log_file;
    
    public function __construct($db) {
        $this->db = $db;
        $this->log_file = __DIR__ . '/../logs/sql_injection.log';
        
        $this->dangerous_keywords = [
            'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'RENAME',
            'LOAD_FILE', 'INTO OUTFILE', 'INTO DUMPFILE',
            'UNION', 'SLEEP', 'BENCHMARK', 'WAITFOR',
            'EXEC', 'EXECUTE', 'xp_', 'sp_',
            '--', '/*', '*/', '#', '@@'
        ];
    }
    
    /**
     * Execute prepared statement safely
     */
    public function preparedQuery($query, $types, ...$params) {
        // Validate query
        if (!$this->validateQuery($query)) {
            throw new Exception('SQL query validation failed');
        }
        
        // Prepare statement
        $stmt = $this->db->prepare($query);
        
        if (!$stmt) {
            $this->logAttempt($query, 'Prepare failed: ' . $this->db->error);
            throw new Exception('Query preparation failed');
        }
        
        // Bind parameters
        if ($params) {
            if (!$stmt->bind_param($types, ...$params)) {
                $this->logAttempt($query, 'Bind failed');
                throw new Exception('Parameter binding failed');
            }
        }
        
        // Execute
        if (!$stmt->execute()) {
            $this->logAttempt($query, 'Execute failed: ' . $stmt->error);
            throw new Exception('Query execution failed');
        }
        
        return $stmt;
    }
    
    /**
     * Validate SQL query structure
     */
    public function validateQuery($query) {
        $query_upper = strtoupper($query);
        
        // Check for dangerous keywords
        foreach ($this->dangerous_keywords as $keyword) {
            if (strpos($query_upper, $keyword) !== false) {
                // Allow some keywords in specific contexts
                if (!$this->isAllowedContext($query, $keyword)) {
                    $this->logAttempt($query, 'Dangerous keyword: ' . $keyword);
                    return false;
                }
            }
        }
        
        // Check query type
        $query_type = strtoupper(trim(substr($query, 0, 10)));
        $allowed_types = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'SET', 'REPLACE', 'WITH'];
        
        $is_allowed = false;
        foreach ($allowed_types as $type) {
            if (strpos($query_type, $type) === 0) {
                $is_allowed = true;
                break;
            }
        }
        
        if (!$is_allowed) {
            $this->logAttempt($query, 'Invalid query type');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if keyword usage is in allowed context
     */
    private function isAllowedContext($query, $keyword) {
        // Allow CREATE in INSERT ... SELECT context
        if ($keyword === 'CREATE' && preg_match('/INSERT\s+INTO.*SELECT/i', $query)) {
            return true;
        }
        
        // Allow UNION in SELECT context
        if ($keyword === 'UNION' && preg_match('/^SELECT/i', trim($query))) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Sanitize input based on type
     */
    public function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'int':
            case 'integer':
                return $this->sanitizeInt($input);
                
            case 'email':
                return $this->sanitizeEmail($input);
                
            case 'username':
                return $this->sanitizeUsername($input);
                
            case 'text':
                return $this->sanitizeText($input);
                
            case 'alphanumeric':
                return $this->sanitizeAlphanumeric($input);
                
            default:
                return $this->sanitizeString($input);
        }
    }
    
    /**
     * Sanitize integer
     */
    private function sanitizeInt($input) {
        return filter_var($input, FILTER_VALIDATE_INT);
    }
    
    /**
     * Sanitize email
     */
    private function sanitizeEmail($input) {
        $email = filter_var($input, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }
    
    /**
     * Sanitize username
     */
    private function sanitizeUsername($input) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $input);
    }
    
    /**
     * Sanitize text
     */
    private function sanitizeText($input) {
        $text = strip_tags($input);
        $text = trim($text);
        return substr($text, 0, 5000);
    }
    
    /**
     * Sanitize alphanumeric
     */
    private function sanitizeAlphanumeric($input) {
        return preg_replace('/[^a-zA-Z0-9]/', '', $input);
    }
    
    /**
     * Sanitize string
     */
    private function sanitizeString($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeString'], $input);
        }
        return $this->db->real_escape_string(strip_tags(trim($input)));
    }
    
    /**
     * Build safe dynamic query
     */
    public function buildSelectQuery($table, $columns = '*', $conditions = [], $order = '', $limit = '') {
        // Validate table name
        if (!$this->validateIdentifier($table)) {
            throw new Exception('Invalid table name');
        }
        
        $query = "SELECT {$columns} FROM `{$table}`";
        $params = [];
        $types = '';
        
        // Build WHERE clause
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                if (!$this->validateIdentifier($field)) {
                    throw new Exception('Invalid column name: ' . $field);
                }
                
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $where[] = "`{$field}` IN ({$placeholders})";
                    foreach ($value as $v) {
                        $params[] = $v;
                        $types .= $this->getParamType($v);
                    }
                } else {
                    $where[] = "`{$field}` = ?";
                    $params[] = $value;
                    $types .= $this->getParamType($value);
                }
            }
            $query .= " WHERE " . implode(' AND ', $where);
        }
        
        // Add ORDER BY
        if ($order) {
            $query .= " ORDER BY {$order}";
        }
        
        // Add LIMIT
        if ($limit) {
            $query .= " LIMIT {$limit}";
        }
        
        return [
            'query' => $query,
            'types' => $types,
            'params' => $params
        ];
    }
    
    /**
     * Build safe insert query
     */
    public function buildInsertQuery($table, $data) {
        if (!$this->validateIdentifier($table)) {
            throw new Exception('Invalid table name');
        }
        
        $fields = [];
        $placeholders = [];
        $params = [];
        $types = '';
        
        foreach ($data as $field => $value) {
            if (!$this->validateIdentifier($field)) {
                throw new Exception('Invalid column name: ' . $field);
            }
            
            $fields[] = "`{$field}`";
            $placeholders[] = '?';
            $params[] = $value;
            $types .= $this->getParamType($value);
        }
        
        $field_list = implode(', ', $fields);
        $placeholder_list = implode(', ', $placeholders);
        
        $query = "INSERT INTO `{$table}` ({$field_list}) VALUES ({$placeholder_list})";
        
        return [
            'query' => $query,
            'types' => $types,
            'params' => $params
        ];
    }
    
    /**
     * Validate SQL identifier (table/column name)
     */
    private function validateIdentifier($identifier) {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier);
    }
    
    /**
     * Get parameter type for binding
     */
    private function getParamType($value) {
        if (is_int($value)) return 'i';
        if (is_float($value)) return 'd';
        if (is_string($value)) return 's';
        return 's'; // Default to string
    }
    
    /**
     * Log SQL injection attempt
     */
    private function logAttempt($query, $reason) {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'query' => substr($query, 0, 500),
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'url' => $_SERVER['REQUEST_URI'] ?? ''
        ];
        
        error_log(json_encode($log) . "\n", 3, $this->log_file);
        
        // Alert for critical patterns
        $critical_patterns = ['DROP', 'TRUNCATE', 'INTO OUTFILE', 'INTO DUMPFILE'];
        foreach ($critical_patterns as $pattern) {
            if (stripos($query, $pattern) !== false) {
                $this->sendAlert($log);
                break;
            }
        }
    }
    
    /**
     * Send security alert
     */
    private function sendAlert($log) {
        $to = getenv('ADMIN_NOTIFICATION_EMAIL') ?: 'admin@swiftchat.com';
        $subject = '[CRITICAL] SQL Injection Attempt Detected - SwiftChat';
        $message = "A potential SQL injection attack was detected:\n\n" . json_encode($log, JSON_PRETTY_PRINT);
        
        @mail($to, $subject, $message);
    }
}
?>