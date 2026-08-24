<?php
$config = require __DIR__ . '/config.php';
$supabaseParts = parse_url($config['supabase_url'] ?? '');
$connectSources = "'self'";
if (is_array($supabaseParts)
    && strtolower($supabaseParts['scheme'] ?? '') === 'https'
    && preg_match('/^[A-Za-z0-9.-]+$/', $supabaseParts['host'] ?? '')) {
    $connectSources .= ' ' . $supabaseParts['scheme'] . '://' . $supabaseParts['host'];
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' https: data:; connect-src $connectSources; frame-ancestors 'none'; base-uri 'self'; form-action 'self' https://wa.me");
header('Cache-Control: no-store');

try {
    if (empty($config['db']['dsn']) || empty($config['db']['user']) || empty($config['db']['pass'])) {
        throw new RuntimeException('Database configuration is incomplete.');
    }
    $pdo = new PDO($config['db']['dsn'], $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    error_log('Boss Lady database connection failed.');
    http_response_code(500);
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['ok' => false, 'error' => 'Service temporarily unavailable.']));
    }
    exit('Service temporarily unavailable.');
}
