<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/site_layout.php';
require __DIR__ . '/db.php';
require_once __DIR__ . '/testimonial_helpers.php';
$catalogOffline = !($pdo instanceof PDO);
$products = [];
$productTestimonialStats = [];
try {
    if (!$catalogOffline) $products = $pdo->query('SELECT * FROM products WHERE active=TRUE ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) {
    error_log('Boss Lady shop load failed.');
    $catalogOffline = true;
}
if ($pdo instanceof PDO) {
    try { foreach (testimonial_fetch_product_stats($pdo) as $stat) $productTestimonialStats[(int) $stat['product_id']] = $stat; } catch (Throwable $e) { error_log('Boss Lady product testimonial stats load failed.'); }
}
header('Cache-Control: public, s-maxage=60, stale-while-revalidate=600');
function shop_naira($kobo) { return '₦' . number_format($kobo / 100, 2); }
site_start($config, 'Shop the collection | Boss Lady Perfumery', 'Explore the full Boss Lady Perfumery fragrance collection.', 'shop');
?>
<section class="page-hero"><div class="site-wrap page-hero-inner reveal"><div class="eyebrow">Shop fragrances</div><h1>Find the scent<br><em>that stays.</em></h1><p>Browse our fragrances at your own pace. Each one is chosen to make an entrance, stay close, and leave a lasting impression.</p></div></section>
<section class="page-section"><div class="site-wrap"><div class="shop-toolbar reveal"><span><?=count($products)?> fragrance<?=count($products) === 1 ? '' : 's'?> available</span><span>Need help choosing? Message us on WhatsApp.</span></div><div class="store-grid">
 <?php if ($catalogOffline): ?>
 <article class="shop-empty reveal"><div class="shop-empty-art">BL</div><div><div class="store-kicker">A personal collection</div><h3>Our fragrance edit is being refreshed.</h3><p>Message us on WhatsApp for today's available fragrances and a personal recommendation from Boss Lady.</p><a class="site-button site-button-dark" href="<?=site_support_url($config)?>">Message us on WhatsApp ↗</a></div></article>
 <?php elseif ($products): foreach ($products as $p): $hasImage = trim((string) ($p['image_url'] ?? '')) !== ''; ?>
<article class="store-card reveal"><div class="store-visual <?=$hasImage ? '' : 'store-art'?>"><?php if ($hasImage): ?><a href="<?=htmlspecialchars($p['image_url'], ENT_QUOTES, 'UTF-8')?>" target="_blank" rel="noreferrer" title="Open full-size image"><img src="<?=htmlspecialchars($p['image_url'], ENT_QUOTES, 'UTF-8')?>" alt="<?=htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8')?>" loading="lazy" decoding="async" style="cursor:zoom-in"></a><?php else: ?><div>BL<small>PERFUMERY</small></div><?php endif; ?></div><div class="store-body"><?php if ($hasImage): ?><div style="margin-bottom:9px;color:#9c6b39;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase">Click image to view full size ↗</div><?php endif; ?><div class="store-kicker">Featured fragrance</div><h3><?=htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8')?></h3><?php if (isset($productTestimonialStats[(int) $p['id']])): $stat = $productTestimonialStats[(int) $p['id']]; ?><a href="/testimonials?product=<?=intval($p['id'])?>" style="display:inline-block;margin:0 0 10px;color:#9c6b39;font-size:11px;text-decoration:underline;text-underline-offset:3px"><?=testimonial_stars((int) round((float) $stat['rating']))?> <?=number_format((float) $stat['rating'], 1)?> · <?=intval($stat['review_count'])?> customer stor<?=intval($stat['review_count']) === 1 ? 'y' : 'ies'?> ↗</a><?php endif; ?><p><?=htmlspecialchars($p['description'] ?? 'A confident signature for every room.', ENT_QUOTES, 'UTF-8')?></p><div class="store-footer"><span class="store-price"><?=shop_naira($p['price_kobo'])?></span><button class="store-add" onclick='addToCart(<?=htmlspecialchars(json_encode(["id" => (int)$p["id"], "name" => $p["name"], "price" => (int)$p["price_kobo"]], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8")?>)'>Add to bag +</button></div></div></article>
<?php endforeach; else: ?>
<article class="shop-empty reveal"><div class="shop-empty-art">BL</div><div><div class="store-kicker">Coming soon</div><h3>Our fragrance collection is on its way.</h3><p>Message us for available fragrances and a personal recommendation while we prepare the online collection.</p><a class="site-button site-button-dark" href="<?=site_support_url($config)?>">Message us on WhatsApp ↗</a></div></article>
<?php endif; ?>
</div><div class="bag-bar reveal"><div><strong>Your bag</strong><br><span id="shopBagSummary">Your bag is waiting for a signature scent.</span></div><a class="site-button site-button-dark" href="/?#checkout">Continue to checkout ↗</a></div></div></section>
<script>
let shopCart=[];try{const value=JSON.parse(localStorage.getItem('bl_cart')||'[]');if(Array.isArray(value))shopCart=value}catch(_){shopCart=[]}function addToCart(product){const item=shopCart.find(i=>i.id===product.id);if(item)item.qty++;else shopCart.push({...product,qty:1});localStorage.setItem('bl_cart',JSON.stringify(shopCart));renderShopBag();document.querySelector('.bag-bar').scrollIntoView({behavior:'smooth',block:'center'})}function renderShopBag(){const count=shopCart.reduce((sum,item)=>sum+(Number(item.qty)||0),0),summary=document.querySelector('#shopBagSummary');if(summary)summary.textContent=count?`${count} item${count===1?'':'s'} saved on this device.`:'Your bag is waiting for a signature scent.'}renderShopBag();
</script>
<?php site_end($config); ?>
