<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
$products = $pdo->query("SELECT * FROM products WHERE active=1 ORDER BY id DESC")->fetchAll();
function naira($kobo){ return '₦'.number_format($kobo/100, 2); }
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Boss Lady Perfumery</title>
<style>
:root{--bg:#080609;--card:#141016;--gold:#e1b866;--pink:#d9879b;--text:#f8f2eb;--muted:#b9b0b4}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at top,#25171d,#080609 48%);color:var(--text);font-family:Georgia,serif}
a{text-decoration:none;color:inherit}.wrap{max-width:1100px;width:92%;margin:auto}
nav{position:sticky;top:0;background:#080609e8;backdrop-filter:blur(12px);border-bottom:1px solid #e1b86633;z-index:5}
.nav{height:68px;display:flex;align-items:center;justify-content:space-between}.logo{font-weight:700;letter-spacing:2px}.logo small{display:block;color:var(--pink);font:10px Arial;letter-spacing:4px}
.nav a{font:13px Arial;color:#ddd;margin-left:18px}
.hero{padding:55px 0 35px}.hero h1{font-size:clamp(46px,7vw,78px);line-height:.95;margin:12px 0}.hero p,.muted{font:15px/1.7 Arial;color:var(--muted)}
.btn{display:inline-block;padding:13px 18px;border-radius:999px;background:linear-gradient(135deg,#d9ab57,#f0cf87);color:#140e08;font:700 13px Arial;border:0;cursor:pointer}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}@media(max-width:760px){.grid{grid-template-columns:1fr 1fr}}@media(max-width:520px){.grid{grid-template-columns:1fr}}
.card{background:linear-gradient(145deg,#181219,#0e0b0f);border:1px solid #e1b86633;border-radius:18px;overflow:hidden}.pic{height:220px;background:#21151b;display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:55px}.body{padding:18px}.body h3{margin:0 0 8px;font-size:21px}.body p{min-height:45px;font:13px/1.6 Arial;color:var(--muted)}.price{color:#f0d18b;font:bold 19px Arial;margin:12px 0}
section{padding:45px 0}.title{text-align:center;font-size:36px}.checkout{background:#121015;border:1px solid #e1b86633;border-radius:22px;padding:28px}
input,textarea{width:100%;padding:13px;margin:7px 0 12px;border-radius:10px;border:1px solid #ffffff22;background:#0b090c;color:#fff}label{font:12px Arial;color:#ccc}
.cart{position:fixed;right:18px;bottom:18px;z-index:10}.cart button{border:0;border-radius:999px;padding:15px 20px;background:#25d366;color:#fff;font:bold 13px Arial}
.item{display:flex;justify-content:space-between;border-bottom:1px solid #ffffff15;padding:10px 0;font:13px Arial}.item button{background:none;color:#ff9eb3;border:0}
.notice{font:12px/1.6 Arial;color:#a9a0a5}.status{padding:12px;border-radius:10px;background:#0c0a0d;margin-top:10px;font:13px Arial}
</style></head><body>
<nav><div class="wrap nav"><a class="logo" href="#">BOSS LADY<small>PERFUMERY</small></a><div><a href="#shop">Shop</a><a href="#track">Track</a><a href="https://wa.me/<?=$config['whatsapp']?>">WhatsApp</a></div></div></nav>

<header class="wrap hero"><div style="max-width:650px"><div style="color:#d9879b;font:700 12px Arial;letter-spacing:4px">SCENT OF CONFIDENCE</div><h1>Smell like a Boss.</h1><p>Shop premium fragrances from Boss Lady Perfumery. Add your favourites to cart, pay securely with Paystack, receive an order ID, or order directly through WhatsApp.</p><a class="btn" href="#shop">Shop Fragrances</a></div></header>

<section id="shop"><div class="wrap"><h2 class="title">Shop Fragrances</h2><div class="grid">
<?php foreach($products as $p): ?>
<article class="card"><div class="pic"><?php if($p['image_url']): ?><img src="<?=htmlspecialchars($p['image_url'])?>" style="width:100%;height:100%;object-fit:cover"><?php else: ?>✦<?php endif; ?></div><div class="body"><h3><?=htmlspecialchars($p['name'])?></h3><p><?=htmlspecialchars($p['description'] ?? '')?></p><div class="price"><?=naira($p['price_kobo'])?></div><button class="btn" onclick='addToCart(<?=json_encode(["id"=>(int)$p["id"],"name"=>$p["name"],"price"=>(int)$p["price_kobo"]])?>)'>Add to Cart</button> <a class="btn" style="background:transparent;color:#f0d18b;border:1px solid #e1b86655" href="https://wa.me/<?=$config['whatsapp']?>?text=<?=rawurlencode("Hello Boss Lady Perfumery, I want to order ".$p["name"].". Please confirm availability.")?>">WhatsApp</a></div></article>
<?php endforeach; ?>
</div><?php if(!$products): ?><p class="notice">No products have been added yet. Use the admin panel to publish products.</p><?php endif; ?></div></section>

<section id="checkout"><div class="wrap checkout"><h2>Checkout</h2><div id="cart"></div><form id="checkoutForm"><label>Full name</label><input id="name" required><label>Email</label><input id="email" type="email" required><label>Phone / WhatsApp</label><input id="phone" required><label>Delivery address</label><textarea id="address" required></textarea><button class="btn" type="submit">Place Order & Continue to WhatsApp</button></form><p class="notice">Payment and delivery details are confirmed with Boss Lady on WhatsApp.</p><div id="result"></div></div></section>

<section id="track"><div class="wrap checkout"><h2>Track Your Order</h2><p class="muted">Enter the order ID you received after checkout.</p><input id="trackCode" placeholder="e.g. BL-20260823-AB12"><button class="btn" onclick="trackOrder()">Track Order</button><div id="trackResult"></div><p class="notice">Need help? <a style="color:#f0d18b" href="https://wa.me/<?=$config['whatsapp']?>">Track with WhatsApp →</a></p></div></section>

<footer class="wrap" style="padding:35px 0;color:#aaa;font:13px Arial">BOSS LADY PERFUMERY · WhatsApp 0906 795 6221 · Call 0703 234 8639</footer>
<div class="cart"><button onclick="document.querySelector('#checkout').scrollIntoView()">🛒 Cart (<span id="count">0</span>)</button></div>
<script src="https://js.paystack.co/v2/inline.js"></script>
<script>
let cart=JSON.parse(localStorage.getItem('bl_cart')||'[]');
function money(k){return '₦'+(k/100).toLocaleString(undefined,{minimumFractionDigits:2})}
function save(){localStorage.setItem('bl_cart',JSON.stringify(cart));render()}
function addToCart(p){let x=cart.find(i=>i.id===p.id);if(x)x.qty++;else cart.push({...p,qty:1});save();alert('Added to cart')}
function removeItem(id){cart=cart.filter(i=>i.id!==id);save()}
function render(){let el=document.querySelector('#cart');document.querySelector('#count').textContent=cart.reduce((s,i)=>s+i.qty,0);if(!cart.length){el.innerHTML='<p class="muted">Your cart is empty.</p>';return}let total=cart.reduce((s,i)=>s+i.price*i.qty,0);el.innerHTML=cart.map(i=>`<div class="item"><span>${i.name} × ${i.qty}</span><span>${money(i.price*i.qty)} <button onclick="removeItem(${i.id})">Remove</button></span></div>`).join('')+`<h3>Total: ${money(total)}</h3>`}
render();
document.querySelector('#checkoutForm').addEventListener('submit',async e=>{
 e.preventDefault(); if(!cart.length)return alert('Add a product first.');
 const data={name:name.value,email:email.value,phone:phone.value,address:address.value,items:cart};
 const r=await fetch('api/create-order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
 const j=await r.json(); if(!j.ok)return alert(j.error||'Could not create order.');
 localStorage.removeItem('bl_cart'); cart=[]; render();
 document.querySelector('#result').innerHTML=`<div class="status"><b>Order created: ${j.order_code}</b><br>Your order has been recorded. Continue to WhatsApp so Boss Lady can confirm your order, payment and delivery.<br><br><a class="btn" href="${j.whatsapp_url}">Continue on WhatsApp</a> <a class="btn" style="background:transparent;color:#f0d18b;border:1px solid #e1b86655" href="#track">Track Order</a></div>`;
});
</script></body></html>
