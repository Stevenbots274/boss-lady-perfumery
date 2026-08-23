<?php
$config = require __DIR__ . '/config.php';

function set_auth_cookie($name, $value, $expires)
{
    setcookie($name, $value, ['expires' => $expires, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
}
function csrf_ok($csrfToken)
{
    return isset($_POST['csrf']) && is_string($_POST['csrf']) && hash_equals($csrfToken, $_POST['csrf']);
}
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function supabase_user($accessToken, $config)
{
    if (!function_exists('curl_init') || !$config['supabase_url'] || !$config['supabase_anon_key']) return null;
    $curl = curl_init($config['supabase_url'] . '/auth/v1/user');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10, CURLOPT_HTTPHEADER => ['Accept: application/json', 'apikey: ' . $config['supabase_anon_key'], 'Authorization: Bearer ' . $accessToken]]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($body === false || $status !== 200) return null;
    $user = json_decode($body, true);
    return is_array($user) ? $user : null;
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
            header('Location: admin.php');
            exit;
        }
        http_response_code(401);
        $loginError = 'Sign-in failed or this account is not authorized.';
    }
    ?>
<!doctype html><html lang="en"><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/jpeg" href="/assets/boss-lady-favicon.jpg"><title>Boss Lady Admin</title><style>:root{--ink:#191315;--rose:#dda8b1;--gold:#c59a53;--paper:#fffaf6}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--ink);color:var(--paper);font:14px Arial}.login{width:min(400px,calc(100% - 32px));padding:34px;background:#251c1f;border:1px solid #c59a5344}.mark{color:var(--gold);font:italic 38px Georgia}.eyebrow{margin:18px 0 8px;color:var(--rose);font-size:10px;letter-spacing:.2em;text-transform:uppercase}h1{margin:0 0 26px;font:400 34px Georgia}label{display:block;margin:13px 0 6px;color:#cbbdc0;font-size:11px;text-transform:uppercase;letter-spacing:.1em}input{width:100%;padding:13px;border:1px solid #ffffff22;background:#171012;color:#fff}button{margin-top:17px;padding:13px 18px;border:0;background:var(--rose);color:#25171b;font-weight:bold;cursor:pointer}.error{margin:0 0 15px;color:#ffabb8;font-size:12px}</style></head><body><main class="login"><div class="mark">BL</div><div class="eyebrow">Private workspace</div><h1>Boss Lady Admin</h1><?php if ($loginError): ?><p class="error"><?=h($loginError)?></p><?php endif; ?><form id="supabaseLogin" method="post" data-supabase-url="<?=h($config['supabase_url'])?>" data-supabase-anon-key="<?=h($config['supabase_anon_key'])?>"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><label>Email</label><input name="email" type="email" autocomplete="username" required><label>Password</label><input name="password" type="password" autocomplete="current-password" required><button name="login" value="1">Enter workspace</button></form></main><script>document.getElementById('supabaseLogin').addEventListener('submit',async function(event){event.preventDefault();const form=event.currentTarget,button=form.querySelector('button');button.disabled=true;try{const auth=await fetch(form.dataset.supabaseUrl+'/auth/v1/token?grant_type=password',{method:'POST',headers:{'Content-Type':'application/json','apikey':form.dataset.supabaseAnonKey},body:JSON.stringify({email:form.email.value,password:form.password.value})});const data=await auth.json();if(!auth.ok||!data.access_token)throw new Error();const response=await fetch('admin.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({csrf:form.csrf.value,login:'1',supabase_token:data.access_token})});if(!response.ok)throw new Error();window.location.reload()}catch(_){window.location.reload()} });</script></body></html>
<?php
    exit;
}
if (isset($_POST['logout'])) {
    set_auth_cookie('__Host-bl_admin_token', '', time() - 3600);
    set_auth_cookie('__Host-bl_admin_csrf', '', time() - 3600);
    header('Location: admin.php');
    exit;
}
require __DIR__ . '/db.php';

$notices = ['product-saved' => 'Product saved.', 'product-hidden' => 'Product hidden.', 'order-updated' => 'Order updated.'];
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
    $stockInput = is_string($_POST['stock'] ?? null) ? trim($_POST['stock']) : '';
    $price = is_numeric($priceInput) && preg_match('/^\d+(?:\.\d{1,2})?$/', $priceInput) ? (int) round((float) $priceInput * 100) : 0;
    $imageParts = $image !== '' ? parse_url($image) : [];
    $validImage = $image === '' || (filter_var($image, FILTER_VALIDATE_URL) && ($imageParts['scheme'] ?? '') === 'https' && strlen($image) <= 500);
    $validStock = $stockInput === '' || (preg_match('/^\d+$/', $stockInput) && (int) $stockInput <= 2147483647);
    $uploadedFile = $_FILES['image'] ?? null;
    $uploadError = '';
    $uploadedUrl = null;
    if (is_array($uploadedFile) && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $mimeMap = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp'];
        $mime = function_exists('finfo_open') && is_uploaded_file($uploadedFile['tmp_name'] ?? '') ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $uploadedFile['tmp_name']) : '';
        if (($uploadedFile['error'] ?? 1) !== UPLOAD_ERR_OK || ($uploadedFile['size'] ?? 0) > 5 * 1024 * 1024 || !isset($mimeMap[$mime])) $uploadError = 'Upload a JPEG, PNG, or WebP image under 5MB.';
        else $uploadedUrl = supabase_upload_image($adminToken, $uploadedFile['tmp_name'], bin2hex(random_bytes(16)) . $mimeMap[$mime], $mime, $config);
        if (!$uploadedUrl && !$uploadError) $uploadError = 'The image could not be uploaded right now.';
    }
    if ($uploadedUrl) $image = $uploadedUrl;
    $validImage = $uploadedUrl || $validImage;
    if ($name === '' || strlen($name) > 160 || strlen($description) > 5000 || $price < 1 || $price > 2147483647 || !$validImage || !$validStock || $uploadError) {
        $notice = $uploadError ?: 'Enter a valid product name, price, image URL, and stock value.';
    } else {
        try {
            $stock = $stockInput === '' ? null : (int) $stockInput;
            if ($id) {
                $s = $pdo->prepare('UPDATE products SET name=?,description=?,price_kobo=?,image_url=?,stock=? WHERE id=?');
                $s->execute([$name, $description ?: null, $price, $image ?: null, $stock, $id]);
            } else {
                $s = $pdo->prepare('INSERT INTO products(name,description,price_kobo,image_url,stock) VALUES(?,?,?,?,?)');
                $s->execute([$name, $description ?: null, $price, $image ?: null, $stock]);
            }
            header('Location: admin.php?notice=product-saved#products');
            exit;
        } catch (Throwable $e) {
            error_log('Boss Lady product save failed.');
            $notice = 'The product could not be saved right now.';
        }
    }
}

if (isset($_POST['del'])) {
    $id = filter_var($_POST['del'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id !== false) {
        try { $s = $pdo->prepare('UPDATE products SET active=FALSE WHERE id=?'); $s->execute([$id]); header('Location: admin.php?notice=product-hidden#products'); exit; }
        catch (Throwable $e) { $notice = 'The product could not be updated right now.'; }
    } else $notice = 'Invalid product.';
}

if (isset($_POST['update_order'])) {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $orderStatus = is_string($_POST['order_status'] ?? null) ? $_POST['order_status'] : '';
    $paymentStatus = is_string($_POST['payment_status'] ?? null) ? $_POST['payment_status'] : '';
    if ($id === false || !in_array($orderStatus, $allowedStatuses, true) || !in_array($paymentStatus, $allowedPaymentStatuses, true)) $notice = 'Invalid order update.';
    else try {
        $pdo->beginTransaction();
        $currentStatement = $pdo->prepare('SELECT order_status,stock_released_at FROM orders WHERE id=? FOR UPDATE');
        $currentStatement->execute([$id]);
        $current = $currentStatement->fetch();
        if (!$current || ($current['order_status'] === 'cancelled' && $orderStatus !== 'cancelled')) throw new InvalidArgumentException('Cancelled orders cannot be reopened.');
        $releaseStock = $orderStatus === 'cancelled' && $current['stock_released_at'] === null;
        if ($releaseStock) {
            $items = $pdo->prepare('SELECT product_id,SUM(quantity) AS quantity FROM order_items WHERE order_id=? GROUP BY product_id'); $items->execute([$id]);
            $product = $pdo->prepare('SELECT stock FROM products WHERE id=? FOR UPDATE'); $update = $pdo->prepare('UPDATE products SET stock=? WHERE id=?');
            while ($item = $items->fetch()) { $product->execute([(int) $item['product_id']]); $row = $product->fetch(); if (!$row) throw new RuntimeException('Product no longer exists.'); if ($row['stock'] !== null) $update->execute([(int) $row['stock'] + (int) $item['quantity'], (int) $item['product_id']]); }
            $s = $pdo->prepare('UPDATE orders SET order_status=?,payment_status=?,stock_released_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        } else $s = $pdo->prepare('UPDATE orders SET order_status=?,payment_status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $s->execute([$orderStatus, $paymentStatus, $id]); $pdo->commit(); header('Location: admin.php?notice=order-updated#orders'); exit;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $notice = $e instanceof InvalidArgumentException ? $e->getMessage() : 'The order could not be updated right now.'; }
}

try { $products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll(); $orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 100')->fetchAll(); }
catch (Throwable $e) { error_log('Boss Lady admin data load failed.'); http_response_code(500); exit('Service temporarily unavailable.'); }
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$editingProduct = null; foreach ($products as $product) if ((int) $product['id'] === $editId) $editingProduct = $product;
$activeProducts = count(array_filter($products, fn($p) => (bool) $p['active']));
$openOrders = count(array_filter($orders, fn($o) => !in_array($o['order_status'], ['delivered', 'cancelled'], true)));
$awaitingPayment = count(array_filter($orders, fn($o) => $o['payment_status'] === 'awaiting_whatsapp'));
$paidTotal = array_reduce($orders, fn($sum, $o) => $sum + ($o['payment_status'] === 'paid' ? (int) $o['total_kobo'] : 0), 0);
?>
<!doctype html><html lang="en"><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/jpeg" href="/assets/boss-lady-favicon.jpg"><title>Boss Lady Workspace</title><style>
:root{--ink:#171214;--ink2:#21191c;--cream:#fbf7f2;--paper:#fffdf9;--rose:#dda8b1;--gold:#c59a53;--line:#eaded6;--muted:#877878;--sidebar:250px}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:#f5eee9;color:var(--ink);font:13px Arial,sans-serif}.admin-shell{display:grid;grid-template-columns:var(--sidebar) 1fr;min-height:100vh;transition:grid-template-columns .2s}.admin-shell.collapsed{--sidebar:78px}.sidebar{position:sticky;top:0;height:100vh;display:flex;flex-direction:column;background:var(--ink);color:var(--cream);padding:26px 14px;overflow:hidden}.brand{display:flex;align-items:center;gap:11px;padding:0 10px 34px;white-space:nowrap}.brand-mark{display:grid;place-items:center;flex:none;width:38px;height:38px;border:1px solid var(--gold);border-radius:50%;color:var(--gold);font:italic 18px Georgia}.brand-copy strong{display:block;font-size:12px;letter-spacing:.2em}.brand-copy small{display:block;margin-top:4px;color:#aa9b9d;font-size:9px;letter-spacing:.25em}.collapse{position:absolute;right:13px;top:29px;border:0;background:#ffffff12;color:#d7c9c8;border-radius:6px;padding:8px;cursor:pointer}.side-nav{display:grid;gap:6px}.side-nav a,.side-foot a{display:flex;align-items:center;gap:14px;padding:13px 11px;border-radius:7px;color:#b9abad;font-size:12px;white-space:nowrap}.side-nav a:hover,.side-nav a.active{background:#ffffff10;color:#fff}.side-nav span{width:18px;text-align:center;color:var(--gold);font-size:16px}.side-foot{margin-top:auto;border-top:1px solid #ffffff16;padding-top:17px}.admin-shell.collapsed .brand-copy,.admin-shell.collapsed .side-nav b,.admin-shell.collapsed .side-foot b{display:none}.admin-shell.collapsed .brand{padding-left:6px}.admin-shell.collapsed .side-nav a,.admin-shell.collapsed .side-foot a{justify-content:center}.main{min-width:0}.topbar{height:82px;display:flex;align-items:center;justify-content:space-between;padding:0 38px;background:rgba(255,253,249,.86);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:10;backdrop-filter:blur(14px)}.mobile-menu{display:none;border:0;background:none;font-size:22px}.top-kicker{display:block;color:var(--gold);font-size:10px;letter-spacing:.18em;text-transform:uppercase}.topbar h1{margin:5px 0 0;font:400 25px Georgia}.top-actions{display:flex;align-items:center;gap:15px}.top-actions a,.logout{color:var(--muted);font-size:12px}.logout{border:0;background:none;cursor:pointer}.content{width:min(1240px,calc(100% - 76px));margin:0 auto;padding:42px 0 70px}.notice{padding:13px 16px;background:#f1dfc4;border-left:3px solid var(--gold);margin-bottom:24px;color:#5c462c}.overview-head{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:24px}.eyebrow{margin-bottom:9px;color:var(--gold);font-size:10px;font-weight:bold;letter-spacing:.2em;text-transform:uppercase}.overview-head h2,.section-title h2{margin:0;font:400 40px Georgia;letter-spacing:-.04em}.overview-head p{max-width:330px;margin:0;color:var(--muted);line-height:1.6}.metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:48px}.metric{padding:21px;background:var(--paper);border:1px solid var(--line)}.metric small{display:block;color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:.13em}.metric strong{display:block;margin-top:12px;font:400 30px Georgia}.metric span{display:block;margin-top:6px;color:var(--gold);font-size:11px}.panel{margin-top:40px;padding:27px;background:var(--paper);border:1px solid var(--line)}.section-title{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:23px}.section-title h2{font-size:31px}.section-title p{margin:0;color:var(--muted);font-size:12px}.product-form{display:grid;grid-template-columns:1fr 1fr;gap:15px}.field.full{grid-column:1/-1}.field label{display:block;margin-bottom:7px;color:var(--muted);font-size:10px;font-weight:bold;letter-spacing:.1em;text-transform:uppercase}.field input,.field textarea,.field select{width:100%;padding:12px;border:1px solid var(--line);background:#fff;color:var(--ink);outline:none}.field textarea{min-height:92px;resize:vertical}.field input:focus,.field textarea:focus,.field select:focus{border-color:var(--gold)}.field small{display:block;margin-top:6px;color:var(--muted);font-size:10px}.form-actions{display:flex;align-items:center;gap:12px;margin-top:4px}.primary{padding:12px 17px;border:0;background:var(--ink);color:var(--cream);font-weight:bold;cursor:pointer}.cancel{color:var(--muted);font-size:12px}.product-list{display:grid;gap:10px}.product-row{display:grid;grid-template-columns:54px 1fr auto;gap:15px;align-items:center;padding:12px;border-top:1px solid var(--line)}.thumb{width:54px;height:54px;object-fit:cover;background:#2b2022}.thumb-placeholder{display:grid;place-items:center;width:54px;height:54px;background:#2b2022;color:var(--gold);font:italic 22px Georgia}.product-row h3{margin:0 0 5px;font:400 18px Georgia}.product-row p{margin:0;color:var(--muted);font-size:11px}.product-row-meta{text-align:right}.product-row-meta strong{display:block;font-size:13px}.product-row-meta span{display:block;margin-top:5px;color:var(--muted);font-size:10px}.row-actions{display:flex;gap:10px;margin-top:9px}.row-actions a,.row-actions button{border:0;background:none;padding:0;color:#9d6973;cursor:pointer;font-size:11px;text-decoration:underline}.order-list{display:grid;gap:13px}.order-card{padding:18px;border:1px solid var(--line);background:#fff}.order-top{display:flex;justify-content:space-between;gap:15px;align-items:start}.order-top h3{margin:0;font:400 19px Georgia}.order-top p{margin:6px 0 0;color:var(--muted);font-size:11px}.order-total{font-weight:bold}.order-controls{display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end;margin-top:17px}.order-controls label{display:block;margin-bottom:6px;color:var(--muted);font-size:9px;text-transform:uppercase;letter-spacing:.1em}.order-controls select{width:100%;padding:10px;border:1px solid var(--line);background:#fff}.order-controls button{padding:10px 14px;border:0;background:var(--rose);color:var(--ink);font-weight:bold;cursor:pointer}.order-links{margin-top:12px;color:#9d6973;font-size:11px}.settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:25px}.setting-copy h3{margin:0 0 9px;font:400 24px Georgia}.setting-copy p{max-width:420px;color:var(--muted);line-height:1.65}.password-form{display:flex;gap:9px;align-items:end}.password-form .field{flex:1}.footer-note{margin-top:35px;color:var(--muted);font-size:11px}
@media(max-width:900px){.admin-shell{display:block}.sidebar{position:fixed;left:-270px;width:250px;z-index:30;transition:left .2s;box-shadow:15px 0 40px #0004}.admin-shell.mobile-open .sidebar{left:0}.admin-shell.collapsed{--sidebar:250px}.admin-shell.collapsed .brand-copy,.admin-shell.collapsed .side-nav b,.admin-shell.collapsed .side-foot b{display:block}.admin-shell.collapsed .brand{padding-left:10px}.admin-shell.collapsed .side-nav a,.admin-shell.collapsed .side-foot a{justify-content:flex-start}.mobile-menu{display:block}.topbar{padding:0 18px}.top-actions a{display:none}.content{width:min(100% - 32px,650px);padding:28px 0 55px}.metrics{grid-template-columns:1fr 1fr;margin-bottom:34px}.overview-head{display:block}.overview-head p{margin-top:12px}.settings-grid{grid-template-columns:1fr}.password-form{display:block}.password-form button{margin-top:10px}.product-form{grid-template-columns:1fr}.field.full{grid-column:auto}.order-controls{grid-template-columns:1fr 1fr}.order-controls button{grid-column:1/-1}.panel{padding:20px}}
@media(max-width:520px){.topbar h1{font-size:21px}.overview-head h2{font-size:34px}.metrics{gap:8px}.metric{padding:15px}.metric strong{font-size:25px}.metric span{font-size:9px}.product-row{grid-template-columns:45px 1fr}.thumb,.thumb-placeholder{width:45px;height:45px}.product-row-meta{grid-column:2;text-align:left}.order-top{display:block}.order-total{display:block;margin-top:12px}}
</style></head><body>
<div class="admin-shell" id="adminShell"><aside class="sidebar"><div class="brand"><span class="brand-mark">BL</span><span class="brand-copy"><strong>BOSS LADY</strong><small>WORKSPACE</small></span></div><button class="collapse" id="collapse" title="Collapse sidebar">‹</button><nav class="side-nav"><a class="active" href="#overview"><span>⌂</span><b>Overview</b></a><a href="#products"><span>✦</span><b>Products</b></a><a href="#orders"><span>◌</span><b>Orders</b></a><a href="#settings"><span>⚙</span><b>Settings</b></a></nav><div class="side-foot"><a href="/" target="_blank" rel="noreferrer"><span>↗</span><b>View storefront</b></a></div></aside><div class="main"><header class="topbar"><div style="display:flex;align-items:center;gap:13px"><button class="mobile-menu" id="mobileMenu" aria-label="Open menu">☰</button><div><span class="top-kicker">Private workspace</span><h1>Good morning, Boss Lady.</h1></div></div><div class="top-actions"><a href="https://wa.me/<?=$config['whatsapp']?>?text=<?=rawurlencode('Hello Boss Lady Perfumery, I need help with my store.')?>">Support ↗</a><form method="post"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><button class="logout" name="logout" value="1">Log out</button></form></div></header><main class="content">
<?php if ($notice): ?><div class="notice"><?=h($notice)?></div><?php endif; ?>
<section id="overview"><div class="overview-head"><div><div class="eyebrow">Store pulse</div><h2>Everything in one place.</h2></div><p>Manage the collection, keep an eye on orders, and stay close to every customer conversation.</p></div><div class="metrics"><div class="metric"><small>Active products</small><strong><?=$activeProducts?></strong><span>In the collection</span></div><div class="metric"><small>Open orders</small><strong><?=$openOrders?></strong><span>Need attention</span></div><div class="metric"><small>Awaiting payment</small><strong><?=$awaitingPayment?></strong><span>Confirm on WhatsApp</span></div><div class="metric"><small>Paid revenue</small><strong>₦<?=number_format($paidTotal / 100, 0)?></strong><span>Recorded orders</span></div></div></section>
<section class="panel" id="products"><div class="section-title"><div><div class="eyebrow">Catalogue</div><h2><?= $editingProduct ? 'Edit product.' : 'Add a product.' ?></h2></div><p>Upload product photography directly to Supabase Storage.</p></div><form class="product-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><input type="hidden" name="product_id" value="<?=h($editingProduct['id'] ?? '')?>"><div class="field"><label>Product name</label><input name="name" maxlength="160" value="<?=h($editingProduct['name'] ?? '')?>" required></div><div class="field"><label>Price in NGN</label><input name="price" type="number" min="0.01" step=".01" value="<?= $editingProduct ? h(number_format($editingProduct['price_kobo'] / 100, 2, '.', '')) : '' ?>" required></div><div class="field full"><label>Description</label><textarea name="description" maxlength="5000" placeholder="What does this scent feel like?"><?=h($editingProduct['description'] ?? '')?></textarea></div><div class="field"><label>Product image upload</label><input name="image" type="file" accept="image/jpeg,image/png,image/webp"><small>JPEG, PNG or WebP. Maximum 5MB.</small><?php if (!empty($editingProduct['image_url'])): ?><small>Current image will remain unless replaced.</small><?php endif; ?></div><div class="field"><label>Image URL fallback</label><input name="image_url" type="url" maxlength="500" placeholder="https://..." value="<?=h($editingProduct['image_url'] ?? '')?>"><small>An uploaded file takes priority.</small></div><div class="field"><label>Stock</label><input name="stock" type="number" min="0" value="<?=($editingProduct && $editingProduct['stock'] !== null) ? h($editingProduct['stock']) : ''?>" placeholder="Blank = unlimited"></div><div class="field full form-actions"><button class="primary" name="save" value="1"><?= $editingProduct ? 'Save product changes' : 'Publish product' ?></button><?php if ($editingProduct): ?><a class="cancel" href="admin.php#products">Cancel edit</a><?php endif; ?></div></form><div style="height:28px"></div><div class="eyebrow">Current collection</div><div class="product-list"><?php if (!$products): ?><p style="color:var(--muted)">No products yet. Your first product can start the collection.</p><?php endif; ?><?php foreach ($products as $p): ?><div class="product-row"><?php if ($p['image_url']): ?><img class="thumb" src="<?=h($p['image_url'])?>" alt=""><?php else: ?><span class="thumb-placeholder">BL</span><?php endif; ?><div><h3><?=h($p['name'])?></h3><p><?=h($p['active'] ? 'Published' : 'Hidden')?> · <?= $p['stock'] === null ? 'Unlimited stock' : (int) $p['stock'] . ' in stock' ?></p><div class="row-actions"><a href="admin.php?edit=<?= (int) $p['id'] ?>#products">Edit</a><form method="post"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><button name="del" value="<?= (int) $p['id'] ?>">Hide</button></form></div></div><div class="product-row-meta"><strong>₦<?=number_format($p['price_kobo'] / 100, 2)?></strong><span><?=h($p['active'] ? 'Live' : 'Hidden')?></span></div></div><?php endforeach; ?></div></section>
<section class="panel" id="orders"><div class="section-title"><div><div class="eyebrow">Order desk</div><h2>Stay ahead of every order.</h2></div><p>Order status and payment status are managed separately.</p></div><div class="order-list"><?php if (!$orders): ?><p style="color:var(--muted)">Orders will appear here after the first checkout.</p><?php endif; ?><?php foreach ($orders as $o): ?><article class="order-card"><div class="order-top"><div><h3><?=h($o['order_code'])?></h3><p><?=h($o['customer_name'])?> · <?=h($o['phone'])?> · <?=h($o['email'])?></p></div><span class="order-total">₦<?=number_format($o['total_kobo'] / 100, 2)?></span></div><form class="order-controls" method="post"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><div><label>Order status</label><select name="order_status"><?php foreach ($allowedStatuses as $status): ?><option value="<?=h($status)?>"<?=$o['order_status'] === $status ? ' selected' : ''?>><?=h(ucwords(str_replace('_', ' ', $status)))?></option><?php endforeach; ?></select></div><div><label>Payment status</label><select name="payment_status"><?php foreach ($allowedPaymentStatuses as $status): ?><option value="<?=h($status)?>"<?=$o['payment_status'] === $status ? ' selected' : ''?>><?=h(ucwords(str_replace('_', ' ', $status)))?></option><?php endforeach; ?></select></div><button name="update_order" value="1">Save update</button></form><div class="order-links"><a href="<?=h(rtrim($config['site_url'], '/') . '/order/' . $o['order_token'])?>" target="_blank" rel="noreferrer">Open private order page ↗</a></div></article><?php endforeach; ?></div></section>
<section class="panel" id="settings"><div class="section-title"><div><div class="eyebrow">Workspace</div><h2>Settings.</h2></div><p>Keep access secure and get support when you need it.</p></div><div class="settings-grid"><div class="setting-copy"><h3>Change admin password</h3><p>Update the Supabase Auth password without leaving the workspace. Use at least 8 characters.</p></div><form class="password-form" method="post"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><div class="field"><label>New password</label><input name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required><?php if ($passwordNotice): ?><small><?=h($passwordNotice)?></small><?php endif; ?></div><button class="primary" name="change_password" value="1">Update password</button></form></div><p class="footer-note">Need help with an order or customer message? Use the Support link in the top bar for a ready-to-send WhatsApp message.</p></section>
</main></div></div><script>
const shell=document.getElementById('adminShell');const storedSidebar=localStorage.getItem('bl_admin_sidebar');if(storedSidebar==='collapsed')shell.classList.add('collapsed');document.getElementById('collapse').addEventListener('click',()=>{shell.classList.toggle('collapsed');localStorage.setItem('bl_admin_sidebar',shell.classList.contains('collapsed')?'collapsed':'open')});document.getElementById('mobileMenu').addEventListener('click',()=>shell.classList.toggle('mobile-open'));document.querySelectorAll('.side-nav a,.side-foot a').forEach(link=>link.addEventListener('click',()=>shell.classList.remove('mobile-open')));
</script></body></html>
