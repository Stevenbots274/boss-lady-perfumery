<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../db.php';

function track_error($status, $message)
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    track_error(405, 'Method not allowed.');
}

$code = trim(is_string($_GET['code'] ?? null) ? $_GET['code'] : '');
$phone = trim(is_string($_GET['phone'] ?? null) ? $_GET['phone'] : '');
if (!preg_match('/^BL-[0-9]{8}-[A-Fa-f0-9]{6,16}$/', $code) || !preg_match('/^[0-9+().\s-]{7,40}$/', $phone)) {
    track_error(422, 'Enter the order ID and phone number used at checkout.');
}

try {
    $s = $pdo->prepare('SELECT order_code,total_kobo,payment_status,order_status,updated_at FROM orders WHERE order_code=? AND phone=? LIMIT 1');
    $s->execute([$code, $phone]);
    $order = $s->fetch();
    echo $order ? json_encode(['ok' => true, 'order' => $order]) : json_encode(['ok' => false]);
} catch (Throwable $e) {
    error_log('Boss Lady order tracking failed.');
    track_error(500, 'Could not track the order right now.');
}
