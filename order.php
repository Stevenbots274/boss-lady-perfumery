<?php
require __DIR__.'/db.php';
$code = trim($_GET['code'] ?? '');
if (!$code) { http_response_code(404); exit('Order not found.'); }

$s = $pdo->prepare("SELECT * FROM orders WHERE order_code=? LIMIT 1");
$s->execute([$code]);
$order = $s->fetch();
if (!$order) { http_response_code(404); exit('Order not found.'); }

$i = $pdo->prepare("SELECT * FROM order_items WHERE order_id=? ORDER BY id");
$i->execute([$order['id']]);
$items = $i->fetchAll();

function naira($kobo){ return '₦'.number_format($kobo/100, 2); }
$config = require __DIR__.'/config.php';
$waMsg = "Hello Boss Lady Perfumery, I am viewing order ".$order['order_code'].". Please help me with this order.";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($order['order_code'])?> | Boss Lady Perfumery</title>
<style>
body{margin:0;background:#09070a;color:#f8f2eb;font-family:Arial,sans-serif}
.wrap{max-width:720px;width:92%;margin:35px auto}.brand{text-align:center;color:#f0d18b;font:700 25px Georgia;letter-spacing:2px}.sub{text-align:center;color:#d9879b;font-size:11px;letter-spacing:4px;margin-top:4px}
.box{background:#151116;border:1px solid #e1b86644;border-radius:20px;padding:25px;margin-top:22px}
h1,h2{font-family:Georgia}.id{font-size:24px;color:#f0d18b}.row{display:flex;justify-content:space-between;gap:15px;padding:12px 0;border-bottom:1px solid #ffffff12;font-size:14px}.muted{color:#aaa1a5}.total{font-size:20px;font-weight:bold;color:#f0d18b}
.badge{display:inline-block;padding:8px 12px;border-radius:999px;background:#251d11;color:#f0d18b;font-size:12px}
.btn{display:inline-block;padding:13px 18px;border-radius:999px;background:#25d366;color:white;text-decoration:none;font-weight:bold;margin-top:15px}
.notice{font-size:12px;line-height:1.6;color:#999}
</style></head>
<body>
<div class="wrap">
<div class="brand">BOSS LADY</div><div class="sub">PERFUMERY</div>
<div class="box">
  <div class="muted">ORDER ID</div><div class="id"><?=htmlspecialchars($order['order_code'])?></div>
  <p><span class="badge">Payment: <?=htmlspecialchars($order['payment_status'])?></span> <span class="badge">Order: <?=htmlspecialchars($order['order_status'])?></span></p>
</div>
<div class="box">
<h2>Customer</h2>
<div class="row"><span class="muted">Name</span><span><?=htmlspecialchars($order['customer_name'])?></span></div>
<div class="row"><span class="muted">Phone</span><span><?=htmlspecialchars($order['phone'])?></span></div>
<div class="row"><span class="muted">Delivery</span><span><?=nl2br(htmlspecialchars($order['address']))?></span></div>
</div>
<div class="box">
<h2>Items</h2>
<?php foreach($items as $item): ?>
<div class="row"><span><?=htmlspecialchars($item['product_name'])?> × <?=intval($item['quantity'])?></span><span><?=naira($item['unit_price_kobo']*$item['quantity'])?></span></div>
<?php endforeach; ?>
<div class="row total"><span>Total</span><span><?=naira($order['total_kobo'])?></span></div>
<a class="btn" href="https://wa.me/<?=$config['whatsapp']?>?text=<?=rawurlencode($waMsg)?>">Chat on WhatsApp</a>
</div>
<div class="box">
<h2>Order Information</h2>
<div class="row"><span class="muted">Created</span><span><?=htmlspecialchars($order['created_at'])?></span></div>
<div class="row"><span class="muted">Last updated</span><span><?=htmlspecialchars($order['updated_at'])?></span></div>
<p class="notice">This page is intentionally public. Anyone who has the complete order link can view this order. Do not share the link publicly if the order contains information you want kept private.</p>
</div>
</div>
</body></html>
