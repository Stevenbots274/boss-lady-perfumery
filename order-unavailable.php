<?php
$config = require __DIR__ . '/config.php';
http_response_code(503);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
$supportUrl = 'https://wa.me/' . $config['whatsapp'] . '?text=' . rawurlencode('Hello Boss Lady Perfumery, I need help with my order link.');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/jpeg" href="/assets/boss-lady-favicon.jpg">
<title>Order link | Boss Lady Perfumery</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#171214;color:#f8f3ed;font-family:Arial,Helvetica,sans-serif}.card{width:min(560px,calc(100% - 36px));padding:42px 30px;background:#241b1e;border:1px solid #c59a5344;text-align:center}.logo{width:min(380px,100%);height:auto;margin-bottom:30px}.eyebrow{color:#dda8b1;font-size:11px;letter-spacing:.2em;text-transform:uppercase}h1{margin:15px 0;font:400 42px/1 Georgia,serif}p{color:#c8b8b8;font-size:14px;line-height:1.8}.button{display:inline-block;margin-top:14px;padding:13px 20px;border-radius:999px;background:#dda8b1;color:#27171b;font-size:12px;font-weight:700;text-decoration:none}
</style>
</head>
<body><main class="card"><img class="logo" src="/assets/boss-lady-logo.svg" width="640" height="180" alt="Boss Lady Perfumery"><div class="eyebrow">Order care</div><h1>Your order is still safe.</h1><p>We are refreshing the order desk. Please try this private link again shortly, or message our concierge and we will help you directly.</p><a class="button" href="<?=$supportUrl?>">Message on WhatsApp</a></main></body>
</html>
