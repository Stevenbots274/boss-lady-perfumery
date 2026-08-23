<?php
ini_set('session.use_strict_mode','1');
session_set_cookie_params([
 'httponly'=>true,
 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
 'samesite'=>'Lax'
]);
session_start();
$config=require __DIR__.'/config.php'; require __DIR__.'/db.php';

if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
function csrf_ok(){ return isset($_POST['csrf'],$_SESSION['csrf']) && hash_equals($_SESSION['csrf'],$_POST['csrf']); }

if(isset($_POST['logout'])){session_destroy();header('Location: admin.php');exit;}
if(!isset($_SESSION['admin'])){
 if(isset($_POST['login']) && hash_equals($config['admin_user'],$_POST['user']??'') && password_verify($_POST['pass']??'', $config['admin_password_hash'])){session_regenerate_id(true);$_SESSION['admin']=true;}
 else { ?>
<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><title>Boss Lady Admin</title><style>body{font-family:Arial;background:#09070a;color:#fff;max-width:420px;margin:10vh auto;padding:20px}input{width:100%;padding:13px;margin:7px 0 12px;background:#151116;color:#fff;border:1px solid #ffffff22;border-radius:8px}button{padding:13px 18px;background:#e1b866;border:0;border-radius:8px;font-weight:bold}</style><h1>Boss Lady Admin</h1><form method=post><input name=user placeholder=Username><input name=pass type=password placeholder=Password><button name=login>Login</button></form><?php exit;}}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_SESSION['admin']) && !isset($_POST['login']) && !csrf_ok()){http_response_code(403);exit('Invalid request.');}
if(isset($_POST['save'])){
 $name=trim($_POST['name']);$desc=trim($_POST['description']);$price=(int)round(((float)$_POST['price'])*100);$img=trim($_POST['image_url']);$stock=(int)$_POST['stock'];
 $s=$pdo->prepare("INSERT INTO products(name,description,price_kobo,image_url,stock) VALUES(?,?,?,?,?)");$s->execute([$name,$desc,$price,$img,$stock]);
}
if(isset($_GET['del'])){$s=$pdo->prepare("UPDATE products SET active=0 WHERE id=?");$s->execute([(int)$_GET['del']);header('Location: admin.php');exit;}
if(isset($_POST['status'])){$s=$pdo->prepare("UPDATE orders SET order_status=? WHERE id=?");$s->execute([$_POST['status'],(int)$_POST['id']]);}
$products=$pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();$orders=$pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 100")->fetchAll();
?><!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><title>Boss Lady Admin</title>
<style>body{margin:0;background:#09070a;color:#eee;font-family:Arial}.wrap{max-width:1050px;margin:auto;padding:24px}.box{background:#151116;border:1px solid #e1b86633;border-radius:14px;padding:20px;margin:18px 0}input,textarea,select{padding:11px;background:#0d0a0e;color:#fff;border:1px solid #ffffff22;border-radius:7px;margin:5px;width:calc(100% - 22px)}button{padding:11px 16px;border:0;border-radius:7px;background:#e1b866;font-weight:bold}table{width:100%;border-collapse:collapse}td,th{padding:9px;border-bottom:1px solid #ffffff15;text-align:left;font-size:13px}</style>
<div class=wrap><h1>Boss Lady Perfumery — Admin</h1><form method=post><input type=hidden name=csrf value="<?=htmlspecialchars($_SESSION['csrf'])?>"><button name=logout>Log out</button></form>
<div class=box><h2>Add Product</h2><form method=post><input type=hidden name=csrf value="<?=htmlspecialchars($_SESSION['csrf'])?>"><input name=name placeholder="Product name" required><textarea name=description placeholder="Description"></textarea><input name=price type=number step=.01 placeholder="Price in NGN" required><input name=image_url placeholder="Product image URL"><input name=stock type=number value=0 placeholder="Stock"><button name=save>Publish Product</button></form></div>
<div class=box><h2>Products</h2><table><tr><th>Name</th><th>Price</th><th>Stock</th><th></th></tr><?php foreach($products as $p):?><tr><td><?=htmlspecialchars($p['name'])?></td><td>₦<?=number_format($p['price_kobo']/100,2)?></td><td><?=$p['stock']?></td><td><a style="color:#e1b866" href="?del=<?=$p['id']?>">Hide</a></td></tr><?php endforeach;?></table></div>
<div class=box><h2>Orders</h2><table><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th></tr><?php foreach($orders as $o):?><tr><td><?=$o['order_code']?></td><td><?=htmlspecialchars($o['customer_name'])?></td><td>₦<?=number_format($o['total_kobo']/100,2)?></td><td><?=$o['payment_status']?></td><td><form method=post><input type=hidden name=csrf value="<?=htmlspecialchars($_SESSION['csrf'])?>"><input type=hidden name=id value="<?=$o['id']?>"><select name=status onchange="this.form.submit()"><?php foreach(['new','processing','ready','shipped','delivered','cancelled'] as $st):?><option <?=$o['order_status']==$st?'selected':''?>><?=$st?></option><?php endforeach;?></select></form></td></tr><?php endforeach;?></table></div>
</div>