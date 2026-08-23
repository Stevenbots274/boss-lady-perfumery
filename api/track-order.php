<?php
header('Content-Type: application/json'); require __DIR__.'/../db.php';
$code=trim($_GET['code']??'');
$s=$pdo->prepare("SELECT order_code,total_kobo,payment_status,order_status,updated_at FROM orders WHERE order_code=? LIMIT 1");
$s->execute([$code]);$o=$s->fetch();
echo $o?json_encode(['ok'=>true,'order'=>$o]):json_encode(['ok'=>false]);
