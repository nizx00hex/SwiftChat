<?php
/**
 * SwiftChat Security Configuration Loader
 * Returns merged security settings
 */

return [
    'csrf' => [
        'token_length' => 32,
        'expiry' => 3600
    ],
    'session' => [
        'lifetime' => 1800,
        'regenerate' => 300
    ],
    'rate_limit' => [
        'default' => ['requests' => 60, 'window' => 60],
        'login' => ['attempts' => 5, 'window' => 900]
    ],
    'encryption' => [
        'cipher' => 'aes-256-gcm',
        'hash_algo' => PASSWORD_ARGON2ID
    ],
    'password' => [
        'min_length' => 8,
        'require_upper' => true,
        'require_lower' => true,
        'require_number' => true,
        'require_special' => true
    ],
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff'
    ]
];
?>