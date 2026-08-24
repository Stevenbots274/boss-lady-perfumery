<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../db.php';

function track_error($status, $message)
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    track_error(405, 'Method not allowed.');
}

$contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''), 2)[0]));
if ($contentType !== 'application/json') track_error(415, 'The request must be JSON.');
$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) track_error(400, 'Invalid request.');
$code = trim(is_string($input['code'] ?? null) ? $input['code'] : '');
$phone = trim(is_string($input['phone'] ?? null) ? $input['phone'] : '');
if (!preg_match('/^BL-[0-9]{8}-[A-Fa-f0-9]{6,16}$/', $code) || !preg_match('/^[0-9+().\s-]{7,40}$/', $phone)) {
    track_error(422, 'Enter the order ID and phone number used at checkout.');
}
if (!($pdo instanceof PDO)) {
    header('Retry-After: 30');
    track_error(503, 'Order tracking is taking a short pause. Please try again shortly or message us on WhatsApp.');
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
