<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../customer_auth.php';
require_once __DIR__ . '/../imagekit.php';

function testimonial_api_error($status, $message)
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function testimonial_api_origin_allowed($config)
{
    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin === '') return true;
    return rtrim($origin, '/') === rtrim($config['site_url'], '/');
}

function testimonial_api_order($pdo, $orderId, $customer)
{
    $statement = $pdo->prepare("SELECT o.id,o.order_code,o.order_status,o.email,t.id AS testimonial_id,t.status AS testimonial_status FROM orders o LEFT JOIN testimonials t ON t.order_id=o.id WHERE o.id=? AND lower(o.email)=lower(?) LIMIT 1");
    $statement->execute([$orderId, $customer['email']]);
    return $statement->fetch() ?: null;
}

function testimonial_api_scope($customer, $orderId, $config)
{
    return 'u-' . substr(hash_hmac('sha256', $customer['id'] . '|' . $orderId, $config['imagekit_private_key']), 0, 24);
}

if (!testimonial_api_origin_allowed($config)) testimonial_api_error(403, 'Request origin is not allowed.');
$customer = customer_auth_session($config);
if (!$customer || !is_string($customer['id'] ?? null) || !is_string($customer['email'] ?? null)) testimonial_api_error(401, 'Sign in to continue.');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET' && ($_GET['action'] ?? '') === 'upload-auth') {
    $orderId = filter_var($_GET['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $mediaType = is_string($_GET['media_type'] ?? null) ? $_GET['media_type'] : '';
    if ($orderId === false || !in_array($mediaType, ['image', 'video'], true)) testimonial_api_error(422, 'Choose one valid media type.');
    require __DIR__ . '/../db.php';
    if (!($pdo instanceof PDO)) testimonial_api_error(503, 'Testimonials are taking a short pause. Please try again shortly.');
    try {
        $order = testimonial_api_order($pdo, (int) $orderId, $customer);
    } catch (Throwable $e) {
        error_log('Boss Lady testimonial eligibility check failed.');
        testimonial_api_error(503, 'Testimonials are taking a short pause. Please try again shortly.');
    }
    if (!$order || $order['order_status'] !== 'delivered') testimonial_api_error(403, 'That order is not eligible for a testimonial.');
    if ($order['testimonial_id']) testimonial_api_error(409, 'A testimonial has already been submitted for this order.');
    if (!imagekit_configured($config)) testimonial_api_error(503, 'Testimonial media is not configured yet.');
    echo json_encode(['ok' => true] + imagekit_upload_auth($config, $mediaType, testimonial_api_scope($customer, $orderId, $config)));
    exit;
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    testimonial_api_error(405, 'Method not allowed.');
}
$contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''), 2)[0]));
if ($contentType !== 'application/json') testimonial_api_error(415, 'The request must be JSON.');
$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input) || ($input['action'] ?? '') !== 'submit') testimonial_api_error(400, 'Invalid testimonial request.');
$orderId = filter_var($input['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
$message = is_string($input['message'] ?? null) ? trim($input['message']) : '';
if ($orderId === false || $rating === false || $message === '' || strlen($message) > 3000) testimonial_api_error(422, 'Add a rating and a testimonial under 3000 characters.');

require __DIR__ . '/../db.php';
if (!($pdo instanceof PDO)) testimonial_api_error(503, 'Testimonials are taking a short pause. Please try again shortly.');
$uploadedFileIds = [];
try {
    $order = testimonial_api_order($pdo, (int) $orderId, $customer);
    if (!$order || $order['order_status'] !== 'delivered') testimonial_api_error(403, 'That order is not eligible for a testimonial.');
    if ($order['testimonial_id']) testimonial_api_error(409, 'A testimonial has already been submitted for this order.');

    $mediaInput = $input['media'] ?? null;
    if (is_array($mediaInput) && isset($mediaInput['file_id'])) $mediaInput = [$mediaInput];
    if (!is_array($mediaInput) || !$mediaInput || count($mediaInput) > 2) testimonial_api_error(422, 'Add one image, one video, or both before sending your testimonial.');
    $media = [];
    $mediaTypes = [];
    $scope = testimonial_api_scope($customer, $orderId, $config);
    foreach ($mediaInput as $asset) {
        if (!is_array($asset)) testimonial_api_error(422, 'The media selection is invalid.');
        $fileId = is_string($asset['file_id'] ?? null) ? trim($asset['file_id']) : '';
        $mediaType = is_string($asset['media_type'] ?? null) ? $asset['media_type'] : '';
        if (!$fileId || !in_array($mediaType, ['image', 'video'], true) || in_array($mediaType, $mediaTypes, true) || !imagekit_configured($config)) testimonial_api_error(422, 'Choose no more than one image and one video.');
        $uploadedFileIds[] = $fileId;
        $assetDetails = imagekit_asset_details($fileId, $mediaType, $config, '/boss-lady/testimonials/' . ($mediaType === 'video' ? 'videos' : 'images') . '/' . $scope);
        if (!$assetDetails) testimonial_api_error(422, 'The media type, size, duration, or source could not be verified.');
        $mediaTypes[] = $mediaType;
        $media[] = $assetDetails;
    }

    $pdo->beginTransaction();
    $insert = $pdo->prepare("INSERT INTO testimonials(user_id,order_id,rating,message,status) VALUES(?,?,?,?, 'pending') RETURNING id");
    $insert->execute([$customer['id'], (int) $orderId, (int) $rating, $message]);
    $testimonialId = (int) $insert->fetchColumn();
    foreach ($media as $asset) {
        $mediaInsert = $pdo->prepare('INSERT INTO testimonial_media(testimonial_id,provider,media_type,media_url,thumbnail_url,imagekit_file_id,file_size,duration_seconds,mime_type) VALUES(?,\'imagekit\',?,?,?,?,?,?,?)');
        $mediaInsert->execute([$testimonialId, $asset['media_type'], $asset['url'], $asset['thumbnail_url'], $asset['file_id'], $asset['file_size'], $asset['duration_seconds'], $asset['mime_type']]);
    }
    $productLinks = $pdo->prepare('INSERT INTO testimonial_products(testimonial_id,product_id) SELECT DISTINCT ?,product_id FROM order_items WHERE order_id=? ON CONFLICT DO NOTHING');
    $productLinks->execute([$testimonialId, (int) $orderId]);
    $pdo->commit();
    echo json_encode(['ok' => true, 'status' => 'pending']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach ($uploadedFileIds as $fileId) imagekit_delete_file($fileId, $config);
    if ((string) $e->getCode() === '23505') testimonial_api_error(409, 'A testimonial has already been submitted for this order.');
    if ((string) $e->getCode() === '42P01') testimonial_api_error(503, 'Testimonials are taking a short pause. Please try again shortly.');
    error_log('Boss Lady testimonial submission failed.');
    testimonial_api_error(500, 'The testimonial could not be submitted right now.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach ($uploadedFileIds as $fileId) imagekit_delete_file($fileId, $config);
    error_log('Boss Lady testimonial submission failed unexpectedly.');
    testimonial_api_error(500, 'The testimonial could not be submitted right now.');
}
