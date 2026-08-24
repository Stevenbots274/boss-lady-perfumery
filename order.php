<?php
require __DIR__ . '/db.php';
$token = is_string($_GET['token'] ?? null) ? strtolower(trim($_GET['token'])) : '';
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(404);
    exit('Order not found.');
}

try {
    $s = $pdo->prepare('SELECT * FROM orders WHERE order_token=? LIMIT 1');
    $s->execute([$token]);
    $order = $s->fetch();
    if (!$order) {
        http_response_code(404);
        exit('Order not found.');
    }

    $i = $pdo->prepare('SELECT order_items.*, products.image_url FROM order_items LEFT JOIN products ON products.id=order_items.product_id WHERE order_items.order_id=? ORDER BY order_items.id');
    $i->execute([$order['id']]);
    $items = $i->fetchAll();
} catch (Throwable $e) {
    error_log('Boss Lady order page load failed.');
    http_response_code(500);
    exit('Order page temporarily unavailable.');
}

function naira($kobo){ return '₦'.number_format($kobo/100, 2); }
function order_status_label($value)
{
    return ['new' => 'New', 'processing' => 'Processing', 'ready' => 'Ready for delivery', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'][$value] ?? 'Updated';
}
function payment_status_label($value)
{
    return ['awaiting_whatsapp' => 'Awaiting WhatsApp confirmation', 'pending' => 'Payment pending', 'paid' => 'Paid', 'failed' => 'Payment failed', 'refunded' => 'Refunded'][$value] ?? 'Payment update';
}
$config = require __DIR__.'/config.php';
$waMsg = "Hello Boss Lady Perfumery, I am viewing order ".$order['order_code'].". Please help me with this order.";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/jpeg" href="/assets/boss-lady-favicon.jpg">
<title><?=htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8')?> | Boss Lady Perfumery</title>
<style>
body{margin:0;background:#09070a;color:#f8f2eb;font-family:Arial,sans-serif}
.wrap{max-width:720px;width:92%;margin:35px auto}.brand{text-align:center;color:#f0d18b;font:700 25px Georgia;letter-spacing:2px}.sub{text-align:center;color:#d9879b;font-size:11px;letter-spacing:4px;margin-top:4px}
.box{background:#151116;border:1px solid #e1b86644;border-radius:20px;padding:25px;margin-top:22px}
h1,h2{font-family:Georgia}.id{font-size:24px;color:#f0d18b}.row{display:flex;justify-content:space-between;gap:15px;padding:12px 0;border-bottom:1px solid #ffffff12;font-size:14px}.muted{color:#aaa1a5}.total{font-size:20px;font-weight:bold;color:#f0d18b}
.badge{display:inline-block;padding:8px 12px;border-radius:999px;background:#251d11;color:#f0d18b;font-size:12px}.item-row{display:flex;align-items:center;gap:13px;padding:10px 0;border-bottom:1px solid #ffffff12}.item-image,.item-placeholder{width:56px;height:56px;flex:none;border-radius:10px;object-fit:cover}.item-placeholder{display:grid;place-items:center;background:#251d11;color:#f0d18b;font:italic 22px Georgia}.item-copy{display:flex;justify-content:space-between;align-items:center;gap:15px;width:100%;font-size:14px}.item-copy strong{font-weight:400}.item-copy span{color:#f0d18b;white-space:nowrap}
.btn{display:inline-block;padding:13px 18px;border-radius:999px;background:#25d366;color:white;text-decoration:none;font-weight:bold;margin-top:15px}
 .notice{font-size:12px;line-height:1.6;color:#999}.image-link{display:block;flex:none}.image-link:focus-visible{outline:2px solid #f0d18b;outline-offset:3px}
</style></head>
<body>
<div class="wrap">
<div class="brand">BOSS LADY</div><div class="sub">PERFUMERY</div>
<div class="box">
  <div class="muted">ORDER ID</div><div class="id"><?=htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8')?></div>
  <p><span class="badge">Payment: <?=htmlspecialchars(payment_status_label($order['payment_status']), ENT_QUOTES, 'UTF-8')?></span> <span class="badge">Order: <?=htmlspecialchars(order_status_label($order['order_status']), ENT_QUOTES, 'UTF-8')?></span></p>
</div>
<div class="box">
<h2>Customer</h2>
<div class="row"><span class="muted">Name</span><span><?=htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8')?></span></div>
<div class="row"><span class="muted">Phone</span><span><?=htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8')?></span></div>
<div class="row"><span class="muted">Delivery</span><span><?=nl2br(htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8'))?></span></div>
</div>
<div class="box">
<h2>Items</h2>
<?php foreach($items as $item): ?>
 <div class="item-row"><?php if (!empty($item['image_url'])): ?><a class="image-link" href="<?=htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8')?>" target="_blank" rel="noreferrer" title="Open full-size product image" aria-label="Open full-size image of <?=htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8')?>"><img class="item-image" src="<?=htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8')?>" alt="<?=htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8')?>"></a><?php else: ?><div class="item-placeholder">BL</div><?php endif; ?><div class="item-copy"><strong><?=htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8')?> × <?=intval($item['quantity'])?></strong><span><?=naira($item['unit_price_kobo']*$item['quantity'])?><?php if (!empty($item['image_url'])): ?> <a href="<?=htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8')?>" target="_blank" rel="noreferrer" style="color:#f0d18b;text-decoration:underline;text-underline-offset:3px">Open image ↗</a><?php endif; ?></span></div></div>
<?php endforeach; ?>
<div class="row total"><span>Total</span><span><?=naira($order['total_kobo'])?></span></div>
<a class="btn" href="https://wa.me/<?=$config['whatsapp']?>?text=<?=rawurlencode($waMsg)?>">Chat on WhatsApp</a>
</div>
<div class="box">
<h2>Order Information</h2>
<div class="row"><span class="muted">Created</span><span><?=htmlspecialchars($order['created_at'], ENT_QUOTES, 'UTF-8')?></span></div>
<div class="row"><span class="muted">Last updated</span><span><?=htmlspecialchars($order['updated_at'], ENT_QUOTES, 'UTF-8')?></span></div>
<p class="notice">This private order link contains delivery information. Do not share it publicly.</p>
</div>
</div>
</body></html>
