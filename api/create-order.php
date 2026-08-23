<?php
header('Content-Type: application/json');
$config=require __DIR__.'/../config.php'; require __DIR__.'/../db.php';
$input=json_decode(file_get_contents('php://input'),true);
if(!$input || empty($input['name'])||empty($input['email'])||empty($input['phone'])||empty($input['address'])||empty($input['items'])){
 http_response_code(422);echo json_encode(['ok'=>false,'error'=>'Complete all checkout fields.']);exit;
}
$total=0;$clean=[];$stmt=$pdo->prepare("SELECT * FROM products WHERE id=? AND active=1 LIMIT 1");
foreach($input['items'] as $item){
 $stmt->execute([(int)$item['id']]);$p=$stmt->fetch();$qty=max(1,min(20,(int)$item['qty']));
 if(!$p){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'One of the products is unavailable.']);exit;}
 if((int)$p['stock']>0 && $qty>(int)$p['stock']){http_response_code(422);echo json_encode(['ok'=>false,'error'=>$p['name'].' has limited stock.']);exit;}
 $total += $p['price_kobo']*$qty;
 $clean[]=['id'=>(int)$p['id'],'name'=>$p['name'],'price_kobo'=>(int)$p['price_kobo'],'qty'=>$qty];
}
$code='BL-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
$pdo->beginTransaction();
$ins=$pdo->prepare("INSERT INTO orders(order_code,customer_name,email,phone,address,total_kobo,payment_status,order_status) VALUES(?,?,?,?,?,?,?,?)");
$ins->execute([$code,$input['name'],$input['email'],$input['phone'],$input['address'],$total,'awaiting_whatsapp','new']);
$orderId=(int)$pdo->lastInsertId();
$ii=$pdo->prepare("INSERT INTO order_items(order_id,product_id,product_name,unit_price_kobo,quantity) VALUES(?,?,?,?,?)");
foreach($clean as $i)$ii->execute([$orderId,$i['id'],$i['name'],$i['price_kobo'],$i['qty']]);
$pdo->commit();

$orderUrl=rtrim($config['site_url'],'/').'/order/'.$code;
$itemLines=[];
foreach($clean as $i){ $itemLines[]='• '.$i['name'].' × '.$i['qty'].' — ₦'.number_format(($i['price_kobo']*$i['qty'])/100,2); }
$msg="Hello Boss Lady Perfumery 👋\n\n".
"I just placed an order through your website.\n\n".
"Order ID: ".$code."\n".
"Customer: ".$input['name']."\n".
"Phone: ".$input['phone']."\n".
"Total: ₦".number_format($total/100,2)."\n\n".
"Items:\n".implode("\n",$itemLines)."\n".
"Delivery: ".$input['address']."\n".
"🔗 View complete order:\n".$orderUrl."\n".
"Please confirm availability and send payment details. Thank you ❤️";
echo json_encode(['ok'=>true,'order_code'=>$code,'order_url'=>$orderUrl,'whatsapp_url'=>'https://wa.me/'.$config['whatsapp'].'?text='.rawurlencode($msg)]);
