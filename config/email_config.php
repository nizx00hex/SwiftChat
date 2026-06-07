<?php

return [
    //SMTP configuration
    'host'       => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port'       => getenv('MAIL_PORT') ?: 587,
    'username'   => getenv('MAIL_USERNAME') ?: '',
    'password'   => getenv('MAIL_PASSWORD') ?: '',
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',

    //sender information
    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@swiftchat.com',
    'from_name'    => getenv('MAIL_FROM_NAME') ?: 'SwiftChat',

    //email settings
    'charset'   => 'UTF-8',
    'word_wrap' => 70,
    'priority'  => 3, //1:high 2:normal 5:low

    //rate limiting (emails per hour)
    'max_emails_per_hour'           => 50,
    'max_emails_per_user_per_hour'  => 5,

    //templates
    'templates_path' => __DIR__ . '/../public/templates/emails/',

    //retry settings
    'max_retries' => 3,
    'retry_delay' => 5, //seconds

    //tesr mode (don't actually send emails)
    'test_mode'  => getenv('APP_ENV') === 'development',
    'test_email' => 'test@swiftchat.com',
];
