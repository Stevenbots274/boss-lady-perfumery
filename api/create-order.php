<?php
header('Content-Type: application/json; charset=utf-8');
$config = require __DIR__ . '/../config.php';
require __DIR__ . '/../db.php';

function fail_request($status, $message)
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function string_field($input, $key, $maxLength)
{
    if (!isset($input[$key]) || !is_string($input[$key])) {
        return null;
    }
    $value = trim($input[$key]);
    return $value !== '' && strlen($value) <= $maxLength ? $value : null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    fail_request(405, 'Method not allowed.');
}

$contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''), 2)[0]));
if ($contentType !== 'application/json') {
    fail_request(415, 'The request must be JSON.');
}

$siteParts = parse_url($config['site_url']);
if (strpos($config['site_url'], 'YOUR-DOMAIN.com') !== false
    || !is_array($siteParts)
    || strtolower($siteParts['scheme'] ?? '') !== 'https'
    || empty($siteParts['host'])
    || isset($siteParts['query'])
    || isset($siteParts['fragment'])
    || isset($siteParts['user'])
    || isset($siteParts['pass'])) {
    fail_request(500, 'The store is not configured yet.');
}

$expectedOrigin = $siteParts['scheme'] . '://' . $siteParts['host']
    . (isset($siteParts['port']) ? ':' . $siteParts['port'] : '');
$origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
if ($origin !== '' && rtrim($origin, '/') !== rtrim($expectedOrigin, '/')) {
    fail_request(403, 'Request origin is not allowed.');
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 65536) {
    fail_request(413, 'Request is too large.');
}

$raw = file_get_contents('php://input', false, null, 0, 65537);
if ($raw !== false && strlen($raw) > 65536) {
    fail_request(413, 'Request is too large.');
}
$input = json_decode($raw ?: '', true);
if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    fail_request(400, 'Invalid request.');
}

$name = string_field($input, 'name', 160);
$email = string_field($input, 'email', 190);
$phone = string_field($input, 'phone', 40);
$address = string_field($input, 'address', 500);
if (!$name || !$email || !$phone || !$address) {
    fail_request(422, 'Complete all checkout fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail_request(422, 'Enter a valid email address.');
}
if (!preg_match('/^[0-9+().\s-]{7,40}$/', $phone)) {
    fail_request(422, 'Enter a valid phone number.');
}

if (!isset($input['items']) || !is_array($input['items']) || !$input['items'] || count($input['items']) > 50) {
    fail_request(422, 'Add at least one valid product.');
}

function order_rate_allowed($pdo, $key)
{
    try {
        $rateKey = hash('sha256', $key);
        $pdo->beginTransaction();
        $insert = $pdo->prepare('INSERT IGNORE INTO rate_limits(rate_key,window_started,request_count) VALUES(?,NOW(),0)');
        $insert->execute([$rateKey]);
        $select = $pdo->prepare('SELECT window_started,request_count FROM rate_limits WHERE rate_key=? FOR UPDATE');
        $select->execute([$rateKey]);
        $row = $select->fetch();
        if (!$row) {
            throw new RuntimeException('Rate limit record could not be created.');
        }
        $windowStarted = strtotime($row['window_started']);
        if ($windowStarted === false || time() - $windowStarted >= 600) {
            $update = $pdo->prepare('UPDATE rate_limits SET window_started=NOW(),request_count=1 WHERE rate_key=?');
            $update->execute([$rateKey]);
            $allowed = true;
        } elseif ((int) $row['request_count'] >= 10) {
            $allowed = false;
        } else {
            $update = $pdo->prepare('UPDATE rate_limits SET request_count=request_count+1 WHERE rate_key=?');
            $update->execute([$rateKey]);
            $allowed = true;
        }
        $pdo->commit();
        return $allowed;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Boss Lady order rate limit failed.');
        return null;
    }
}

$rateAllowed = order_rate_allowed($pdo, (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
if ($rateAllowed === null) {
    fail_request(503, 'Ordering is temporarily unavailable.');
}
if (!$rateAllowed) {
    header('Retry-After: 600');
    fail_request(429, 'Too many orders from this address. Try again later.');
}

$quantities = [];
foreach ($input['items'] as $item) {
    if (!is_array($item) || !isset($item['id'], $item['qty']) || !is_scalar($item['id']) || !is_scalar($item['qty'])) {
        fail_request(422, 'One of the products is invalid.');
    }
    $id = filter_var($item['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $qty = filter_var($item['qty'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);
    if ($id === false || $qty === false) {
        fail_request(422, 'Product quantities must be between 1 and 20.');
    }
    $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
    if ($quantities[$id] > 20) {
        fail_request(422, 'A product quantity cannot exceed 20.');
    }
}

$total = 0;
$clean = [];
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id,name,price_kobo,stock FROM products WHERE id=? AND active=1 LIMIT 1 FOR UPDATE');
    $updateStock = $pdo->prepare('UPDATE products SET stock=? WHERE id=?');

    foreach ($quantities as $id => $qty) {
        $stmt->execute([(int) $id]);
        $product = $stmt->fetch();
        if (!$product || (int) $product['price_kobo'] <= 0) {
            throw new InvalidArgumentException('One of the products is unavailable.');
        }

        $stock = $product['stock'] === null ? null : (int) $product['stock'];
        if ($stock !== null && $qty > $stock) {
            throw new InvalidArgumentException($product['name'] . ' has limited stock.');
        }
        if ($stock !== null) {
            $updateStock->execute([$stock - $qty, (int) $product['id']]);
        }

        $lineTotal = (int) $product['price_kobo'] * $qty;
        if ($lineTotal > 2147483647 - $total) {
            throw new InvalidArgumentException('Order total is too large.');
        }
        $total += $lineTotal;
        $clean[] = [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'price_kobo' => (int) $product['price_kobo'],
            'qty' => $qty,
        ];
    }

    $code = 'BL-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(8)));
    $token = bin2hex(random_bytes(32));
    $ins = $pdo->prepare('INSERT INTO orders(order_code,order_token,customer_name,email,phone,address,total_kobo,payment_status,order_status) VALUES(?,?,?,?,?,?,?,?,?)');
    $ins->execute([$code, $token, $name, $email, $phone, $address, $total, 'awaiting_whatsapp', 'new']);
    $orderId = (int) $pdo->lastInsertId();

    $ii = $pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,unit_price_kobo,quantity) VALUES(?,?,?,?,?)');
    foreach ($clean as $item) {
        $ii->execute([$orderId, $item['id'], $item['name'], $item['price_kobo'], $item['qty']]);
    }
    $pdo->commit();
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail_request(422, $e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Boss Lady order creation failed.');
    fail_request(500, 'Could not create the order right now.');
}

$orderUrl = rtrim($config['site_url'], '/') . '/order/' . $token;
$itemLines = [];
foreach ($clean as $item) {
    $itemLines[] = '• ' . $item['name'] . ' × ' . $item['qty'] . ' — ₦' . number_format(($item['price_kobo'] * $item['qty']) / 100, 2);
}
$message = "Hello Boss Lady Perfumery 👋\n\n" .
    "I just placed an order through your website.\n\n" .
    "Order ID: " . $code . "\n" .
    "Customer: " . $name . "\n" .
    "Phone: " . $phone . "\n" .
    "Total: ₦" . number_format($total / 100, 2) . "\n\n" .
    "Items:\n" . implode("\n", $itemLines) . "\n" .
    "Delivery: " . $address . "\n" .
    "🔗 View complete order:\n" . $orderUrl . "\n" .
    "Please confirm availability and send payment details. Thank you ❤️";

echo json_encode([
    'ok' => true,
    'order_code' => $code,
    'order_url' => $orderUrl,
    'whatsapp_url' => 'https://wa.me/' . $config['whatsapp'] . '?text=' . rawurlencode($message),
], JSON_UNESCAPED_UNICODE);
