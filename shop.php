<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/site_layout.php';
require __DIR__ . '/db.php';
try {
    $products = $pdo->query('SELECT * FROM products WHERE active=TRUE ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) {
    error_log('Boss Lady shop load failed.');
    http_response_code(500);
    exit('Shop temporarily unavailable.');
}
function shop_naira($kobo) { return '₦' . number_format($kobo / 100, 2); }
site_start($config, 'Shop the collection | Boss Lady Perfumery', 'Explore the full Boss Lady Perfumery fragrance collection.', 'shop');
?>
<section class="page-hero"><div class="site-wrap page-hero-inner reveal"><div class="eyebrow">The full collection</div><h1>Find the scent<br><em>that stays.</em></h1><p>Take your time with the Boss Lady edit. Every fragrance is selected to make an entrance, hold a room, and leave a little mystery behind.</p></div></section>
<section class="page-section"><div class="site-wrap"><div class="shop-toolbar reveal"><span><?=count($products)?> fragrance<?=count($products) === 1 ? '' : 's'?> in the current edit</span><span>Complimentary guidance via WhatsApp</span></div><div class="store-grid">
<?php if ($products): foreach ($products as $p): $hasImage = trim((string) ($p['image_url'] ?? '')) !== ''; ?>
<article class="store-card reveal"><div class="store-visual <?=$hasImage ? '' : 'store-art'?>"><?php if ($hasImage): ?><img src="<?=htmlspecialchars($p['image_url'], ENT_QUOTES, 'UTF-8')?>" alt="<?=htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8')?>" loading="lazy"><?php else: ?><div>BL<small>PERFUMERY</small></div><?php endif; ?></div><div class="store-body"><div class="store-kicker">Boss Lady edit</div><h3><?=htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8')?></h3><p><?=htmlspecialchars($p['description'] ?? 'A confident signature for every room.', ENT_QUOTES, 'UTF-8')?></p><div class="store-footer"><span class="store-price"><?=shop_naira($p['price_kobo'])?></span><button class="store-add" onclick='addToCart(<?=htmlspecialchars(json_encode(["id" => (int)$p["id"], "name" => $p["name"], "price" => (int)$p["price_kobo"]], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8")?>)'>Add to bag +</button></div></div></article>
<?php endforeach; else: ?>
<article class="shop-empty reveal"><div class="shop-empty-art">BL</div><div><div class="store-kicker">The first drop is coming</div><h3>A considered collection, coming soon.</h3><p>We are preparing the first Boss Lady edit. Message us for available fragrances and personal recommendations.</p><a class="site-button site-button-dark" href="<?=site_support_url($config)?>">Speak with a scent concierge ↗</a></div></article>
<?php endif; ?>
</div><div class="bag-bar reveal"><div><strong>Your bag</strong><br><span id="shopBagSummary">Your bag is waiting for a signature scent.</span></div><a class="site-button site-button-dark" href="/?#checkout">Continue to checkout ↗</a></div></div></section>
<script>
let shopCart=[];try{const value=JSON.parse(localStorage.getItem('bl_cart')||'[]');if(Array.isArray(value))shopCart=value}catch(_){shopCart=[]}function addToCart(product){const item=shopCart.find(i=>i.id===product.id);if(item)item.qty++;else shopCart.push({...product,qty:1});localStorage.setItem('bl_cart',JSON.stringify(shopCart));renderShopBag();document.querySelector('.bag-bar').scrollIntoView({behavior:'smooth',block:'center'})}function renderShopBag(){const count=shopCart.reduce((sum,item)=>sum+(Number(item.qty)||0),0),summary=document.querySelector('#shopBagSummary');if(summary)summary.textContent=count?`${count} item${count===1?'':'s'} saved on this device.`:'Your bag is waiting for a signature scent.'}renderShopBag();
</script>
<?php site_end($config); ?>
