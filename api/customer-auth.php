<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
require __DIR__ . '/../customer_auth.php';
$config = require __DIR__ . '/../config.php';

function customer_auth_error($status, $message)
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'status') {
    $user = customer_auth_session($config);
    echo json_encode(['ok' => true, 'authenticated' => (bool) $user, 'email' => $user['email'] ?? null]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    customer_auth_error(405, 'Method not allowed.');
}
$contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''), 2)[0]));
if ($contentType !== 'application/json') customer_auth_error(415, 'The request must be JSON.');
$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) customer_auth_error(400, 'Invalid request.');

if (($input['action'] ?? '') === 'session') {
    $accessToken = is_string($input['access_token'] ?? null) ? trim($input['access_token']) : '';
    $refreshToken = is_string($input['refresh_token'] ?? null) ? trim($input['refresh_token']) : '';
    $user = customer_auth_store($accessToken, $refreshToken, $config);
    if (!$user) customer_auth_error(401, 'That sign-in session could not be verified. Please try again.');
    echo json_encode(['ok' => true, 'email' => $user['email']]);
    exit;
}

if (($input['action'] ?? '') === 'logout') {
    customer_auth_clear();
    echo json_encode(['ok' => true]);
    exit;
}
customer_auth_error(400, 'Unknown authentication action.');
