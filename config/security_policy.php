<?php
/**
 * SwiftChat Security Policy Configuration
 */

return [
    // Password Policy
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_special' => true,
        'max_age_days' => 90,
        'history_count' => 5
    ],
    
    // Admin Password Policy (Stricter)
    'admin_password' => [
        'min_length' => 10,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_special' => true,
        'max_age_days' => 30,
        'history_count' => 10
    ],
    
    // Session Policy
    'session' => [
        'lifetime' => 1800,
        'regenerate_time' => 300,
        'max_concurrent' => 3,
        'ip_binding' => true,
        'user_agent_binding' => true,
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ],
    
    // Rate Limiting
    'rate_limit' => [
        'api' => ['requests' => 60, 'window' => 60],
        'login' => ['attempts' => 5, 'window' => 900],
        'messages' => ['per_minute' => 30, 'per_hour' => 500],
        'otp' => ['attempts' => 3, 'window' => 600],
        'search' => ['requests' => 30, 'window' => 60]
    ],
    
    // Encryption
    'encryption' => [
        'algorithm' => 'aes-256-gcm',
        'hash_algorithm' => PASSWORD_ARGON2ID,
        'hash_options' => [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ],
        'key_rotation_days' => 30
    ],
    
    // CSRF
    'csrf' => [
        'token_length' => 32,
        'token_expiry' => 3600,
        'cookie_name' => 'csrf_token',
        'header_name' => 'X-CSRF-Token'
    ],
    
    // Security Headers
    'headers' => [
        'hsts' => 'max-age=31536000; includeSubDomains; preload',
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'camera=(), microphone=(), geolocation=()'
    ],
    
    // File Upload
    'file_upload' => [
        'max_size' => 10485760, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'scan_files' => true,
        'storage_path' => '/uploads/'
    ],
    
    // Logging
    'logging' => [
        'level' => 'INFO',
        'retention_days' => 90,
        'log_path' => __DIR__ . '/../logs/',
        'alerts' => [
            'email' => 'security@swiftchat.com',
            'min_severity' => 'WARNING'
        ]
    ],
    
    // Admin
    'admin' => [
        'registration_codes' => ['SWIFTCHAT_ADMIN_2024', 'ADMIN_ACCESS_KEY'],
        'max_login_attempts' => 5,
        'lockout_minutes' => 30,
        'session_timeout' => 1800
    ]
];
?>