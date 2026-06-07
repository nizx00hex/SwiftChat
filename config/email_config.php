<?php
/**
 * SwiftChat Email Configuration
 */

return [
    // SMTP Settings
    'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port' => getenv('MAIL_PORT') ?: 587,
    'username' => getenv('MAIL_USERNAME') ?: '',
    'password' => getenv('MAIL_PASSWORD') ?: '',
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    
    // Sender Information
    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@swiftchat.com',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'SwiftChat',
    
    // Email Types
    'templates' => [
        'verification' => [
            'subject' => 'Verify Your Email - SwiftChat',
            'template' => 'verification.php'
        ],
        'login_otp' => [
            'subject' => 'Login OTP - SwiftChat',
            'template' => 'login_otp.php'
        ],
        'password_reset' => [
            'subject' => 'Password Reset - SwiftChat',
            'template' => 'password_reset.php'
        ],
        'welcome' => [
            'subject' => 'Welcome to SwiftChat!',
            'template' => 'welcome.php'
        ],
        'chat_request' => [
            'subject' => 'New Chat Request - SwiftChat',
            'template' => 'chat_request.php'
        ]
    ],
    
    // Rate Limiting
    'max_emails_per_hour' => 20,
    'otp_expiry_minutes' => 10,
    'login_otp_expiry_minutes' => 5,
    
    // Security
    'dkim_enabled' => false,
    'dkim_private_key' => '',
    'dkim_selector' => 'default',
    'dkim_domain' => '',
    
    // Testing
    'test_mode' => false,
    'test_email' => 'test@swiftchat.com'
];
?>