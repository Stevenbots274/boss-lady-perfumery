<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_helpers.php';
require_once __DIR__ . '/../imagekit.php';

function admin_product_media_error($status, $message)
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

$origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
if ($origin !== '' && rtrim($origin, '/') !== rtrim($config['site_url'], '/')) admin_product_media_error(403, 'Request origin is not allowed.');
$token = is_string($_COOKIE['__Host-bl_admin_token'] ?? null) ? $_COOKIE['__Host-bl_admin_token'] : '';
$user = strlen($token) <= 4096 ? supabase_user($token, $config) : null;
$authorized = $user && is_string($user['email'] ?? null) && $config['admin_email'] && hash_equals($config['admin_email'], strtolower($user['email']));
if (!$authorized) admin_product_media_error(401, 'Sign in to continue.');
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || ($_GET['action'] ?? '') !== 'upload-auth') admin_product_media_error(405, 'Method not allowed.');
if (!imagekit_configured($config)) admin_product_media_error(503, 'Product video uploads are not configured yet.');
$auth = imagekit_upload_auth($config, 'video', '', '/boss-lady/products');
if (!$auth) admin_product_media_error(503, 'Product video uploads are not available right now.');
echo json_encode(['ok' => true] + $auth);
