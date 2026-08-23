<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/db.php';

function set_auth_cookie($name, $value, $expires)
{
    setcookie($name, $value, [
        'expires' => $expires,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function csrf_ok($csrfToken)
{
    return isset($_POST['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($csrfToken, $_POST['csrf']);
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function supabase_user($accessToken, $config)
{
    if (!function_exists('curl_init') || !$config['supabase_url'] || !$config['supabase_anon_key']) {
        return null;
    }
    $curl = curl_init($config['supabase_url'] . '/auth/v1/user');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'apikey: ' . $config['supabase_anon_key'],
            'Authorization: Bearer ' . $accessToken,
        ],
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($body === false || $status !== 200) {
        return null;
    }
    $user = json_decode($body, true);
    return is_array($user) ? $user : null;
}

$csrfCookie = $_COOKIE['__Host-bl_admin_csrf'] ?? null;
$csrfValid = is_string($csrfCookie) && preg_match('/^[a-f0-9]{64}$/', $csrfCookie);
$csrfToken = $csrfValid ? $csrfCookie : bin2hex(random_bytes(32));
if (!$csrfValid) {
    set_auth_cookie('__Host-bl_admin_csrf', $csrfToken, time() + 86400);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_ok($csrfToken)) {
    http_response_code(403);
    exit('Invalid request.');
}

$adminToken = is_string($_COOKIE['__Host-bl_admin_token'] ?? null) ? $_COOKIE['__Host-bl_admin_token'] : '';
$adminUser = strlen($adminToken) <= 4096 ? supabase_user($adminToken, $config) : null;
$adminAuthorized = $adminUser
    && isset($adminUser['email'])
    && is_string($adminUser['email'])
    && $config['admin_email']
    && hash_equals($config['admin_email'], strtolower($adminUser['email']));

if (!$adminAuthorized) {
    $loginError = '';
    if (isset($_POST['login'])) {
        $accessToken = is_string($_POST['supabase_token'] ?? null) ? trim($_POST['supabase_token']) : '';
        $user = strlen($accessToken) <= 4096 ? supabase_user($accessToken, $config) : null;
        $valid = $user
            && isset($user['email'])
            && is_string($user['email'])
            && $config['admin_email']
            && hash_equals($config['admin_email'], strtolower($user['email']));
        if ($valid) {
            set_auth_cookie('__Host-bl_admin_token', $accessToken, time() + 3600);
            header('Location: admin.php');
            exit;
        }
        http_response_code(401);
        $loginError = 'Supabase sign-in failed or this account is not authorized.';
    }
    ?>
<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/jpeg" href="/assets/boss-lady-favicon.jpg"><title>Boss Lady Admin</title><style>body{font-family:Arial;background:#09070a;color:#fff;max-width:420px;margin:10vh auto;padding:20px}input{width:100%;padding:13px;margin:7px 0 12px;background:#151116;color:#fff;border:1px solid #ffffff22;border-radius:8px}button{padding:13px 18px;background:#e1b866;border:0;border-radius:8px;font-weight:bold}.error{color:#ff9eb3;font-size:13px}</style><h1>Boss Lady Admin</h1><?php if ($loginError): ?><p class="error"><?=h($loginError)?></p><?php endif; ?><form id="supabaseLogin" method="post" data-supabase-url="<?=h($config['supabase_url'])?>" data-supabase-anon-key="<?=h($config['supabase_anon_key'])?>"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><input name="email" type="email" placeholder="Admin email" autocomplete="username" required><input name="password" type="password" placeholder="Password" autocomplete="current-password" required><button name="login" value="1">Sign in</button></form><script>document.getElementById('supabaseLogin').addEventListener('submit',async function(event){event.preventDefault();const form=event.currentTarget;const button=form.querySelector('button');button.disabled=true;try{const auth=await fetch(form.dataset.supabaseUrl+'/auth/v1/token?grant_type=password',{method:'POST',headers:{'Content-Type':'application/json','apikey':form.dataset.supabaseAnonKey},body:JSON.stringify({email:form.email.value,password:form.password.value})});const data=await auth.json();if(!auth.ok||!data.access_token)throw new Error('sign-in failed');const response=await fetch('admin.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'text/html'},body:new URLSearchParams({csrf:form.csrf.value,login:'1',supabase_token:data.access_token})});if(!response.ok)throw new Error('authorization failed');window.location.reload()}catch(_){button.disabled=false;alert('Sign-in failed. Check your details or ask the administrator to authorize this account.')}});</script><?php exit;
}

$notices = [
    'product-published' => 'Product published.',
    'product-hidden' => 'Product hidden.',
    'order-updated' => 'Order status updated.',
];
$notice = $notices[$_GET['notice'] ?? ''] ?? '';
$allowedStatuses = ['new', 'processing', 'ready', 'shipped', 'delivered', 'cancelled'];
if (isset($_POST['logout'])) {
    set_auth_cookie('__Host-bl_admin_token', '', time() - 3600);
    set_auth_cookie('__Host-bl_admin_csrf', '', time() - 3600);
    header('Location: admin.php');
    exit;
}

if (isset($_POST['save'])) {
    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
    $priceInput = is_string($_POST['price'] ?? null) ? trim($_POST['price']) : '';
    $image = is_string($_POST['image_url'] ?? null) ? trim($_POST['image_url']) : '';
    $stockInput = is_string($_POST['stock'] ?? null) ? trim($_POST['stock']) : '';
    $price = is_numeric($priceInput) && preg_match('/^\d+(?:\.\d{1,2})?$/', $priceInput)
        ? (int) round((float) $priceInput * 100)
        : 0;
    $imageParts = $image !== '' ? parse_url($image) : [];
    $validImage = $image === '' || (filter_var($image, FILTER_VALIDATE_URL)
        && ($imageParts['scheme'] ?? '') === 'https' && strlen($image) <= 500);
    $validStock = $stockInput === '' || (preg_match('/^\d+$/', $stockInput) && (int) $stockInput <= 2147483647);
    if ($name === '' || strlen($name) > 160 || strlen($description) > 5000 || $price < 1
        || $price > 2147483647 || !$validImage || !$validStock) {
        $notice = 'Enter a valid product name, price, image URL, and stock value.';
    } else {
        try {
            $stock = $stockInput === '' ? null : (int) $stockInput;
            $s = $pdo->prepare('INSERT INTO products(name,description,price_kobo,image_url,stock) VALUES(?,?,?,?,?)');
            $s->execute([$name, $description ?: null, $price, $image ?: null, $stock]);
            header('Location: admin.php?notice=product-published');
            exit;
        } catch (Throwable $e) {
            error_log('Boss Lady product creation failed.');
            $notice = 'The product could not be saved right now.';
        }
    }
}

if (isset($_POST['del'])) {
    $id = is_scalar($_POST['del'])
        ? filter_var($_POST['del'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;
    if ($id === false) {
        $notice = 'Invalid product.';
    } else {
        try {
            $s = $pdo->prepare('UPDATE products SET active=0 WHERE id=?');
            $s->execute([$id]);
            header('Location: admin.php?notice=product-hidden');
            exit;
        } catch (Throwable $e) {
            error_log('Boss Lady product update failed.');
            $notice = 'The product could not be updated right now.';
        }
    }
}

if (isset($_POST['status'])) {
    $status = is_string($_POST['status']) ? $_POST['status'] : '';
    $id = is_scalar($_POST['id'] ?? null)
        ? filter_var($_POST['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;
    if (!in_array($status, $allowedStatuses, true) || $id === false) {
        $notice = 'Invalid order status.';
    } else {
        try {
            $pdo->beginTransaction();
            $currentStatement = $pdo->prepare('SELECT order_status,stock_released_at FROM orders WHERE id=? FOR UPDATE');
            $currentStatement->execute([$id]);
            $current = $currentStatement->fetch();
            if (!$current) {
                throw new InvalidArgumentException('Order not found.');
            }
            if ($current['order_status'] === 'cancelled' && $status !== 'cancelled') {
                throw new InvalidArgumentException('Cancelled orders cannot be reopened.');
            }

            $releaseStock = $status === 'cancelled' && $current['stock_released_at'] === null;
            if ($releaseStock) {
                $itemsStatement = $pdo->prepare('SELECT product_id,SUM(quantity) AS quantity FROM order_items WHERE order_id=? GROUP BY product_id');
                $itemsStatement->execute([$id]);
                $productStatement = $pdo->prepare('SELECT stock FROM products WHERE id=? FOR UPDATE');
                $updateProduct = $pdo->prepare('UPDATE products SET stock=? WHERE id=?');
                while ($item = $itemsStatement->fetch()) {
                    $productStatement->execute([(int) $item['product_id']]);
                    $product = $productStatement->fetch();
                    if (!$product) {
                        throw new RuntimeException('Product for this order no longer exists.');
                    }
                    if ($product['stock'] !== null) {
                        $newStock = (int) $product['stock'] + (int) $item['quantity'];
                        if ($newStock > 2147483647) {
                            throw new RuntimeException('Stock value is too large.');
                        }
                        $updateProduct->execute([$newStock, (int) $item['product_id']]);
                    }
                }
                $s = $pdo->prepare('UPDATE orders SET order_status=?,stock_released_at=NOW() WHERE id=?');
            } else {
                $s = $pdo->prepare('UPDATE orders SET order_status=? WHERE id=?');
            }
            $s->execute([$status, $id]);
            $pdo->commit();
            header('Location: admin.php?notice=order-updated');
            exit;
        } catch (InvalidArgumentException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $notice = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Boss Lady order update failed.');
            $notice = 'The order could not be updated right now.';
        }
    }
}

try {
    $products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
    $orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 100')->fetchAll();
} catch (Throwable $e) {
    error_log('Boss Lady admin data load failed.');
    http_response_code(500);
    exit('Service temporarily unavailable.');
}
?><!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/jpeg" href="/assets/boss-lady-favicon.jpg"><title>Boss Lady Admin</title>
<style>body{margin:0;background:#09070a;color:#eee;font-family:Arial}.wrap{max-width:1050px;margin:auto;padding:24px}.box{background:#151116;border:1px solid #e1b86633;border-radius:14px;padding:20px;margin:18px 0}input,textarea,select{padding:11px;background:#0d0a0e;color:#fff;border:1px solid #ffffff22;border-radius:7px;margin:5px;width:calc(100% - 22px)}button{padding:11px 16px;border:0;border-radius:7px;background:#e1b866;font-weight:bold}table{width:100%;border-collapse:collapse}td,th{padding:9px;border-bottom:1px solid #ffffff15;text-align:left;font-size:13px}.notice{color:#f0d18b;font-size:13px}.danger{background:transparent;color:#e1b866;padding:0}</style>
<div class="wrap"><h1>Boss Lady Perfumery — Admin</h1><?php if ($notice): ?><p class="notice"><?=h($notice)?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><button name="logout" value="1">Log out</button></form>
<div class="box"><h2>Add Product</h2><form method="post"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><input name="name" placeholder="Product name" maxlength="160" required><textarea name="description" maxlength="5000" placeholder="Description"></textarea><input name="price" type="number" min="0.01" step=".01" placeholder="Price in NGN" required><input name="image_url" type="url" maxlength="500" placeholder="HTTPS product image URL"><input name="stock" type="number" min="0" placeholder="Stock (blank = unlimited)"><button name="save" value="1">Publish Product</button></form></div>
<div class="box"><h2>Products</h2><table><tr><th>Name</th><th>Price</th><th>Stock</th><th></th></tr><?php foreach ($products as $p): ?><tr><td><?=h($p['name'])?></td><td>₦<?=number_format($p['price_kobo'] / 100, 2)?></td><td><?=$p['stock'] === null ? 'Unlimited' : (int) $p['stock']?></td><td><form method="post"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><button class="danger" name="del" value="<?= (int) $p['id'] ?>">Hide</button></form></td></tr><?php endforeach; ?></table></div>
<div class="box"><h2>Orders</h2><table><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Private page</th><th>Status</th></tr><?php foreach ($orders as $o): ?><tr><td><?=h($o['order_code'])?></td><td><?=h($o['customer_name'])?></td><td>₦<?=number_format($o['total_kobo'] / 100, 2)?></td><td><?=h($o['payment_status'])?></td><td><a style="color:#e1b866" href="<?=h(rtrim($config['site_url'], '/') . '/order/' . $o['order_token'])?>" target="_blank" rel="noreferrer">View</a></td><td><form method="post"><input type="hidden" name="csrf" value="<?=h($csrfToken)?>"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><select name="status" onchange="this.form.submit()"><?php foreach ($allowedStatuses as $status): ?><option value="<?=h($status)?>"<?=$o['order_status'] === $status ? ' selected' : ''?>><?=h($status)?></option><?php endforeach; ?></select></form></td></tr><?php endforeach; ?></table></div>
</div>
