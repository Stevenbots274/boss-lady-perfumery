<?php
// Boss Lady Perfumery — configuration
// IMPORTANT: keep your Paystack SECRET key on the server only.
return [
    'db' => [
        'dsn' => 'mysql:host=localhost;dbname=boss_lady;charset=utf8mb4',
        'user' => 'YOUR_DB_USER',
        'pass' => 'YOUR_DB_PASSWORD',
    ],
    'site_url' => 'https://YOUR-DOMAIN.com',
    'whatsapp' => '2349067956221',
    'admin_user' => 'admin',
    // Change this before going live.
    // Generate a password hash with PHP password_hash(). Never put the plain password here.
    'admin_password_hash' => '$2y$10$REPLACE_WITH_PASSWORD_HASH',
];
