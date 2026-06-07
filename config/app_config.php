<?php
/**
 * SwiftChat Application Configuration
 */

return [
    // Application Info
    'name' => 'SwiftChat',
    'version' => '1.0.0',
    'environment' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') ?: false,
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'UTC',
    
    // Paths
    'root_path' => dirname(__DIR__),
    'public_path' => dirname(__DIR__) . '/public',
    'upload_path' => dirname(__DIR__) . '/public/uploads',
    'log_path' => dirname(__DIR__) . '/logs',
    
    // WebSocket
    'websocket' => [
        'host' => getenv('WS_HOST') ?: 'localhost',
        'port' => getenv('WS_PORT') ?: 8080,
        'health_port' => getenv('WS_HEALTH_PORT') ?: 8081,
        'protocol' => 'chat-protocol',
        'ping_interval' => 30
    ],
    
    // Chat Settings
    'chat' => [
        'max_message_length' => 5000,
        'messages_per_page' => 50,
        'max_contacts' => 500,
        'online_timeout' => 300, // 5 minutes
        'typing_timeout' => 5
    ],
    
    // User Settings
    'user' => [
        'min_username_length' => 3,
        'max_username_length' => 30,
        'username_pattern' => '/^[a-zA-Z0-9_]+$/',
        'default_avatar' => '/assets/images/default-avatar.png'
    ],
    
    // Features
    'features' => [
        'registration' => true,
        'email_verification' => true,
        'two_factor_auth' => true,
        'file_sharing' => false,
        'group_chat' => false,
        'voice_messages' => false,
        'read_receipts' => true,
        'typing_indicator' => true
    ],
    
    // API
    'api' => [
        'version' => 'v1',
        'rate_limit' => true,
        'cors_enabled' => true,
        'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ]
];
?>