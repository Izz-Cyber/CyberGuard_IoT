<?php
// Copy this file to mail_config.php and fill your SMTP settings.
return [
    'use_smtp' => true, // set to false to use PHP mail()
    'smtp' => [
        'host' => 'smtp.example.com',
        'username' => 'user@example.com',
        'password' => 'yourpassword',
        'port' => 587,
        'encryption' => 'tls' // 'tls' or 'ssl' or ''
    ],
    'from' => [
        'email' => 'no-reply@example.com',
        'name' => 'CyberGuard IoT'
    ]
];
