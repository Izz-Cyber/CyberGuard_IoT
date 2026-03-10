<?php
// Application configuration (database + other settings).
// عدّل القيم حسب إعدادات XAMPP/MySQL أو استخدم متغيرات البيئة للسرّية.
return [
    // Database
    'db_host' => '127.0.0.1',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'cybergurad',

    // Other settings
    // Set environment variable CYBERGURAD_GOOGLE_API_KEY or keep empty for local dev.
    'google_api_key' => getenv('CYBERGURAD_GOOGLE_API_KEY') ?: '',
];
