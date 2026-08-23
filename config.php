<?php
// Boss Lady Perfumery configuration. Set these values as server environment variables.
return [
    'db' => [
        'dsn' => getenv('BL_DB_DSN') ?: 'mysql:host=localhost;dbname=boss_lady;charset=utf8mb4',
        'user' => getenv('BL_DB_USER') ?: '',
        'pass' => getenv('BL_DB_PASSWORD') ?: '',
    ],
    'site_url' => rtrim(getenv('BL_SITE_URL') ?: 'https://YOUR-DOMAIN.com', '/'),
    'whatsapp' => preg_replace('/\D+/', '', getenv('BL_WHATSAPP') ?: '2349067956221'),
    'admin_user' => getenv('BL_ADMIN_USER') ?: 'admin',
    'admin_password_hash' => getenv('BL_ADMIN_PASSWORD_HASH') ?: '',
];
