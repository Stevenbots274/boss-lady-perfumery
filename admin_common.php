<?php
$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/imagekit.php';

function admin_h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function admin_path()
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH);
    return is_string($path) && preg_match('#^/admin(?:/|$)#', $path) ? rtrim($path, '/') : '/admin';
}
function admin_redirect($notice, $anchor = '')
{
    header('Location: ' . admin_path() . '?notice=' . rawurlencode($notice) . $anchor);
    exit;
}
function set_auth_cookie($name, $value, $expires)
{
    setcookie($name, $value, ['expires' => $expires, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
}
function csrf_ok($csrfToken)
{
    return isset($_POST['csrf']) && is_string($_POST['csrf']) && hash_equals($csrfToken, $_POST['csrf']);
}
function supabase_update_password($accessToken, $password, $config)
{
    if (!function_exists('curl_init') || !$config['supabase_url'] || !$config['supabase_anon_key']) return false;
    $curl = curl_init($config['supabase_url'] . '/auth/v1/user');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10, CURLOPT_POSTFIELDS => json_encode(['password' => $password]), CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'apikey: ' . $config['supabase_anon_key'], 'Authorization: Bearer ' . $accessToken]]);
    $body = curl_exec($curl);
    return $body !== false && (int) curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200;
}
function supabase_upload_image($accessToken, $tmpPath, $path, $mime, $config)
{
    if (!function_exists('curl_init') || !$config['supabase_url'] || !$config['supabase_anon_key']) return null;
    $contents = file_get_contents($tmpPath);
    if ($contents === false || strlen($contents) > 5 * 1024 * 1024) return null;
    $curl = curl_init($config['supabase_url'] . '/storage/v1/object/product-images/' . rawurlencode($path));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_POSTFIELDS => $contents, CURLOPT_HTTPHEADER => ['Content-Type: ' . $mime, 'apikey: ' . $config['supabase_anon_key'], 'Authorization: Bearer ' . $accessToken]]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    return $body !== false && in_array($status, [200, 201], true) ? rtrim($config['supabase_url'], '/') . '/storage/v1/object/public/product-images/' . rawurlencode($path) : null;
}

$csrfCookie = $_COOKIE['__Host-bl_admin_csrf'] ?? null;
$csrfValid = is_string($csrfCookie) && preg_match('/^[a-f0-9]{64}$/', $csrfCookie);
$csrfToken = $csrfValid ? $csrfCookie : bin2hex(random_bytes(32));
if (!$csrfValid) set_auth_cookie('__Host-bl_admin_csrf', $csrfToken, time() + 86400);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_ok($csrfToken)) { http_response_code(403); exit('Invalid request.'); }

require __DIR__ . '/db.php';
require __DIR__ . '/inventory.php';
if (!($pdo instanceof PDO)) {
    http_response_code(503);
    exit('The admin workspace is taking a short pause. Please try again in a moment.');
}
$productVideoReady = false;
try { $pdo->query('SELECT video_url,video_thumbnail_url,video_imagekit_file_id,video_file_size,video_duration_seconds,video_mime_type FROM products LIMIT 0'); $productVideoReady = true; } catch (Throwable $e) {}

$adminToken = is_string($_COOKIE['__Host-bl_admin_token'] ?? null) ? $_COOKIE['__Host-bl_admin_token'] : '';
$adminUser = strlen($adminToken) <= 4096 ? supabase_user($adminToken, $config) : null;
$adminAuthorized = $adminUser && is_string($adminUser['email'] ?? null) && $config['admin_email'] && hash_equals($config['admin_email'], strtolower($adminUser['email']));
if (!$adminAuthorized) {
    $loginError = '';
    if (isset($_POST['login'])) {
        $accessToken = is_string($_POST['supabase_token'] ?? null) ? trim($_POST['supabase_token']) : '';
        $user = strlen($accessToken) <= 4096 ? supabase_user($accessToken, $config) : null;
        if ($user && is_string($user['email'] ?? null) && $config['admin_email'] && hash_equals($config['admin_email'], strtolower($user['email']))) {
            set_auth_cookie('__Host-bl_admin_token', $accessToken, time() + 3600);
            header('Location: ' . admin_path());
            exit;
        }
        http_response_code(401);
        $loginError = 'Sign-in failed or this account is not authorized.';
    }
    ?>
<script src="/assets/login.js" defer></script>
<!doctype html><html lang="en"><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/jpeg" href="/assets/boss-lady-favicon.jpg"><title>Boss Lady Admin</title><style>:root{--ink:#191315;--rose:#dda8b1;--gold:#c59a53;--paper:#fffaf6}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--ink);color:var(--paper);font:14px Arial}.login{width:min(400px,calc(100% - 32px));padding:34px;background:#251c1f;border:1px solid #c59a5344}.mark{color:var(--gold);font:italic 38px Georgia}.eyebrow{margin:18px 0 8px;color:var(--rose);font-size:10px;letter-spacing:.2em;text-transform:uppercase}h1{margin:0 0 26px;font:400 34px Georgia}label{display:block;margin:13px 0 6px;color:#cbbdc0;font-size:11px;text-transform:uppercase;letter-spacing:.1em}input{width:100%;padding:13px;border:1px solid #ffffff22;background:#171012;color:#fff}button{margin-top:17px;padding:13px 18px;border:0;background:var(--rose);color:#25171b;font-weight:bold;cursor:pointer}.error{margin:0 0 15px;color:#ffabb8;font-size:12px}</style></head><body><main class="login"><div class="mark">BL</div><div class="eyebrow">Private workspace</div><h1>Boss Lady Admin</h1><?php if ($loginError): ?><p class="error"><?=admin_h($loginError)?></p><?php endif; ?><form id="supabaseLogin" method="post" data-supabase-url="<?=admin_h($config['supabase_url'])?>" data-supabase-anon-key="<?=admin_h($config['supabase_anon_key'])?>"><input type="hidden" name="csrf" value="<?=admin_h($csrfToken)?>"><label>Email</label><input name="email" type="email" autocomplete="username" required><label>Password</label><input name="password" type="password" autocomplete="current-password" required><button name="login" value="1">Enter workspace</button></form></main><script>document.getElementById('supabaseLogin').addEventListener('submit',async function(event){event.preventDefault();const form=event.currentTarget,button=form.querySelector('button');button.disabled=true;try{const auth=await fetch(form.dataset.supabaseUrl+'/auth/v1/token?grant_type=password',{method:'POST',headers:{'Content-Type':'application/json','apikey':form.dataset.supabaseAnonKey},body:JSON.stringify({email:form.email.value,password:form.password.value})});const data=await auth.json();if(!auth.ok||!data.access_token)throw new Error();const response=await fetch(location.pathname,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({csrf:form.csrf.value,login:'1',supabase_token:data.access_token})});if(!response.ok)throw new Error();location.reload()}catch(_){location.reload()}});</script></body></html>
<?php
    exit;
}
if (isset($_POST['logout'])) {
    set_auth_cookie('__Host-bl_admin_token', '', time() - 3600);
    set_auth_cookie('__Host-bl_admin_csrf', '', time() - 3600);
    header('Location: ' . admin_path());
    exit;
}
release_expired_reservations($pdo);
function product_state($product)
{
    if (!empty($product['archived_at'])) return 'archived';
    return !empty($product['active']) ? 'live' : 'hidden';
}
function order_status_transition_allowed($from, $to)
{
    $transitions = [
        'new' => ['new', 'processing', 'cancelled'],
        'processing' => ['processing', 'ready', 'cancelled'],
        'ready' => ['ready', 'shipped'],
        'shipped' => ['shipped', 'delivered'],
        'delivered' => ['delivered'],
        'cancelled' => ['cancelled'],
    ];
    return in_array($to, $transitions[$from] ?? [], true);
}
function admin_order_status_label($value)
{
    return ['new' => 'New', 'processing' => 'Processing', 'ready' => 'Ready for delivery', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'][$value] ?? 'Updated';
}

$notices = ['product-saved' => 'Product saved.', 'product-hidden' => 'Product moved to Hidden.', 'product-restored' => 'Product moved to Live.', 'product-archived' => 'Product permanently archived.', 'order-updated' => 'Order updated.', 'testimonial-approved' => 'Testimonial approved and published.', 'testimonial-rejected' => 'Testimonial moved to rejected.', 'testimonial-deleted' => 'Testimonial and its media were deleted.', 'testimonial-error' => 'The testimonial action could not be completed.'];
$notice = $notices[$_GET['notice'] ?? ''] ?? '';
$passwordNotice = '';
$allowedStatuses = ['new', 'processing', 'ready', 'shipped', 'delivered', 'cancelled'];
$allowedPaymentStatuses = ['awaiting_whatsapp', 'pending', 'paid', 'failed', 'refunded'];

if (isset($_POST['change_password'])) {
    $newPassword = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
    if (strlen($newPassword) < 8 || strlen($newPassword) > 72) $passwordNotice = 'Password must be between 8 and 72 characters.';
    elseif (supabase_update_password($adminToken, $newPassword, $config)) $passwordNotice = 'Password updated.';
    else $passwordNotice = 'The password could not be updated right now.';
}

if (isset($_POST['save'])) {
    $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
    $priceInput = is_string($_POST['price'] ?? null) ? trim($_POST['price']) : '';
    $image = is_string($_POST['image_url'] ?? null) ? trim($_POST['image_url']) : '';
    $removeImage = !empty($_POST['remove_image']);
    $stockInput = is_string($_POST['stock'] ?? null) ? trim($_POST['stock']) : '';
    $existingImage = null;
    $existingVideo = ['url' => null, 'thumbnail_url' => null, 'file_id' => null, 'file_size' => null, 'duration_seconds' => null, 'mime_type' => null];
    if ($id) {
        $existingStatement = $pdo->prepare('SELECT * FROM products WHERE id=?');
        $existingStatement->execute([$id]);
        $existingProduct = $existingStatement->fetch() ?: [];
        $existingImage = $existingProduct['image_url'] ?? null;
        if ($productVideoReady) {
            $existingVideo = ['url' => $existingProduct['video_url'] ?? null, 'thumbnail_url' => $existingProduct['video_thumbnail_url'] ?? null, 'file_id' => $existingProduct['video_imagekit_file_id'] ?? null, 'file_size' => $existingProduct['video_file_size'] ?? null, 'duration_seconds' => $existingProduct['video_duration_seconds'] ?? null, 'mime_type' => $existingProduct['video_mime_type'] ?? null];
        }
    }
    $price = is_numeric($priceInput) && preg_match('/^\d+(?:\.\d{1,2})?$/', $priceInput) ? (int) round((float) $priceInput * 100) : 0;
    $imageParts = $image !== '' ? parse_url($image) : [];
    $validImage = $image === '' || (filter_var($image, FILTER_VALIDATE_URL) && ($imageParts['scheme'] ?? '') === 'https' && strlen($image) <= 500);
    $validStock = $stockInput === '' || (preg_match('/^\d+$/', $stockInput) && (int) $stockInput <= 2147483647);
    $uploadedFile = $_FILES['image'] ?? null;
    $uploadError = '';
    $uploadedUrl = null;
    $newVideo = null;
    $videoFileId = is_string($_POST['video_file_id'] ?? null) ? trim($_POST['video_file_id']) : '';
    $removeVideo = !empty($_POST['remove_video']);
    if ($videoFileId !== '') {
        if (!$productVideoReady || !imagekit_configured($config)) $uploadError = 'Product video upload is not available until the product-video migration and ImageKit are configured.';
        else $newVideo = imagekit_asset_details($videoFileId, 'video', $config, '/boss-lady/products/videos');
        if (!$newVideo && !$uploadError) $uploadError = 'Upload an MP4 or MOV video under 50MB and 60 seconds.';
    }
    if (is_array($uploadedFile) && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $mimeMap = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp'];
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo && is_uploaded_file($uploadedFile['tmp_name'] ?? '') ? finfo_file($finfo, $uploadedFile['tmp_name']) : '';
        if ($finfo) finfo_close($finfo);
        if (($uploadedFile['error'] ?? 1) !== UPLOAD_ERR_OK || ($uploadedFile['size'] ?? 0) > 5 * 1024 * 1024 || !isset($mimeMap[$mime])) $uploadError = 'Upload a JPEG, PNG, or WebP image under 5MB.';
        else $uploadedUrl = supabase_upload_image($adminToken, $uploadedFile['tmp_name'], bin2hex(random_bytes(16)) . $mimeMap[$mime], $mime, $config);
        if (!$uploadedUrl && !$uploadError) $uploadError = 'The image could not be uploaded right now.';
    }
    if ($uploadedUrl) $image = $uploadedUrl;
    elseif ($removeImage) $image = '';
    elseif ($image === '' && $existingImage) $image = $existingImage;
    $validImage = $uploadedUrl || $validImage;
    if ($name === '' || strlen($name) > 160 || strlen($description) > 5000 || $price < 1 || $price > 2147483647 || !$validImage || !$validStock || $uploadError) {
        $notice = $uploadError ?: 'Enter a valid product name, price, and stock value.';
    } else {
        try {
            $stock = $stockInput === '' ? null : (int) $stockInput;
            $videoValues = $newVideo ? [$newVideo['url'], $newVideo['thumbnail_url'], $newVideo['file_id'], $newVideo['file_size'], $newVideo['duration_seconds'], $newVideo['mime_type']] : ($removeVideo ? [null, null, null, null, null, null] : [$existingVideo['url'], $existingVideo['thumbnail_url'], $existingVideo['file_id'], $existingVideo['file_size'], $existingVideo['duration_seconds'], $existingVideo['mime_type']]);
            if ($id && $productVideoReady) { $s = $pdo->prepare('UPDATE products SET name=?,description=?,price_kobo=?,image_url=?,video_url=?,video_thumbnail_url=?,video_imagekit_file_id=?,video_file_size=?,video_duration_seconds=?,video_mime_type=?,stock=? WHERE id=?'); $s->execute(array_merge([$name, $description ?: null, $price, $image ?: null], $videoValues, [$stock, $id])); }
            elseif ($id) { $s = $pdo->prepare('UPDATE products SET name=?,description=?,price_kobo=?,image_url=?,stock=? WHERE id=?'); $s->execute([$name, $description ?: null, $price, $image ?: null, $stock, $id]); }
            elseif ($productVideoReady) { $s = $pdo->prepare('INSERT INTO products(name,description,price_kobo,image_url,video_url,video_thumbnail_url,video_imagekit_file_id,video_file_size,video_duration_seconds,video_mime_type,stock) VALUES(?,?,?,?,?,?,?,?,?,?,?)'); $s->execute(array_merge([$name, $description ?: null, $price, $image ?: null], $videoValues, [$stock])); }
            else { $s = $pdo->prepare('INSERT INTO products(name,description,price_kobo,image_url,stock) VALUES(?,?,?,?,?)'); $s->execute([$name, $description ?: null, $price, $image ?: null, $stock]); }
            if ($productVideoReady && $existingVideo['file_id'] && $existingVideo['file_id'] !== ($videoValues[2] ?? null)) imagekit_delete_file($existingVideo['file_id'], $config);
            admin_redirect('product-saved', '#products');
        } catch (Throwable $e) { if ($newVideo && $newVideo['file_id']) imagekit_delete_file($newVideo['file_id'], $config); error_log('Boss Lady product save failed.'); $notice = 'The product could not be saved right now.'; }
    }
}

if (isset($_POST['del'])) {
    $id = filter_var($_POST['del'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id !== false) try { $s = $pdo->prepare('UPDATE products SET active=FALSE WHERE id=? AND archived_at IS NULL'); $s->execute([$id]); admin_redirect('product-hidden', '#products'); } catch (Throwable $e) { $notice = 'The product could not be updated right now.'; }
    else $notice = 'Invalid product.';
}

if (isset($_POST['restore'])) {
    $id = filter_var($_POST['restore'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id !== false) try { $s = $pdo->prepare('UPDATE products SET active=TRUE WHERE id=? AND archived_at IS NULL'); $s->execute([$id]); admin_redirect('product-restored', '#products'); } catch (Throwable $e) { $notice = 'The product could not be moved to Live right now.'; }
    else $notice = 'Invalid product.';
}

if (isset($_POST['archive'])) {
    $id = filter_var($_POST['archive'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id !== false) try { $s = $pdo->prepare('UPDATE products SET active=FALSE,archived_at=CURRENT_TIMESTAMP WHERE id=? AND archived_at IS NULL'); $s->execute([$id]); admin_redirect('product-archived', '#products'); } catch (Throwable $e) { $notice = 'The product could not be archived right now.'; }
    else $notice = 'Invalid product.';
}

if (isset($_POST['update_order'])) {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $orderStatus = is_string($_POST['order_status'] ?? null) ? $_POST['order_status'] : '';
    $paymentStatus = is_string($_POST['payment_status'] ?? null) ? $_POST['payment_status'] : '';
    if ($id === false || !in_array($orderStatus, $allowedStatuses, true) || !in_array($paymentStatus, $allowedPaymentStatuses, true)) $notice = 'Invalid order update.';
    else try {
        $pdo->beginTransaction();
        $currentStatement = $pdo->prepare('SELECT order_status,stock_released_at FROM orders WHERE id=? FOR UPDATE'); $currentStatement->execute([$id]); $current = $currentStatement->fetch();
        if (!$current || !order_status_transition_allowed($current['order_status'], $orderStatus)) throw new InvalidArgumentException('That order cannot move from ' . admin_order_status_label($current['order_status'] ?? '') . ' to ' . admin_order_status_label($orderStatus) . '.');
        $releaseStock = $orderStatus === 'cancelled' && $current['stock_released_at'] === null;
        if ($releaseStock) {
            release_order_stock($pdo, $id);
            $s = $pdo->prepare('UPDATE orders SET order_status=?,payment_status=?,stock_released_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        } elseif ($orderStatus !== 'new') {
            $s = $pdo->prepare('UPDATE orders SET order_status=?,payment_status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        } else $s = $pdo->prepare('UPDATE orders SET order_status=?,payment_status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $s->execute([$orderStatus, $paymentStatus, $id]); $pdo->commit(); admin_redirect('order-updated', '#orders');
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $notice = $e instanceof InvalidArgumentException ? $e->getMessage() : 'The order could not be updated right now.'; }
}

try { $products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll(); $orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll(); }
catch (Throwable $e) { error_log('Boss Lady admin data load failed.'); http_response_code(503); exit('The admin workspace is taking a short pause. Please try again in a moment.'); }
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$editingProduct = null; foreach ($products as $product) if ((int) $product['id'] === $editId) $editingProduct = $product;
$activeProducts = count(array_filter($products, fn($p) => product_state($p) === 'live'));
$hiddenProducts = count(array_filter($products, fn($p) => product_state($p) === 'hidden'));
$archivedProducts = count(array_filter($products, fn($p) => product_state($p) === 'archived'));
$stockAlerts = count(array_filter($products, fn($p) => product_state($p) !== 'archived' && $p['stock'] !== null && (int) $p['stock'] <= 3));
$openOrders = count(array_filter($orders, fn($o) => !in_array($o['order_status'], ['delivered', 'cancelled'], true)));
$awaitingPayment = count(array_filter($orders, fn($o) => $o['payment_status'] === 'awaiting_whatsapp'));
$paidTotal = array_reduce($orders, fn($sum, $o) => $sum + ($o['payment_status'] === 'paid' ? (int) $o['total_kobo'] : 0), 0);
$productView = in_array($_GET['view'] ?? 'all', ['all', 'live', 'hidden', 'archived'], true) ? $_GET['view'] : 'all';
$visibleProducts = array_values(array_filter($products, fn($product) => $productView === 'all' || product_state($product) === $productView));
