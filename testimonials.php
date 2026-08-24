<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require_once __DIR__ . '/testimonial_helpers.php';
$productId = filter_var($_GET['product'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$productName = '';
$testimonials = [];
$testimonialError = false;
if ($pdo instanceof PDO) {
    try {
        if ($productId !== false) {
            $productStatement = $pdo->prepare('SELECT name FROM products WHERE id=? LIMIT 1');
            $productStatement->execute([(int) $productId]);
            $productName = (string) ($productStatement->fetchColumn() ?: '');
        }
        $testimonials = testimonial_fetch_approved($pdo, 18, $productId !== false ? (int) $productId : null);
    } catch (Throwable $e) {
        error_log('Boss Lady public testimonials load failed.');
        $testimonialError = true;
    }
}
function testimonials_page_h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
require __DIR__ . '/site_layout.php';
site_start($config, ($productName ? $productName . ' testimonials' : 'Customer testimonials') . ' | Boss Lady Perfumery', 'Verified customer fragrance stories from Boss Lady Perfumery.', 'testimonials');
?>
<style>
.testimonials-intro{display:flex;justify-content:space-between;gap:30px;align-items:end}.testimonials-intro h2{margin:18px 0 0;font:400 clamp(38px,5vw,60px)/.98 var(--serif);letter-spacing:-.04em}.testimonials-intro p{max-width:390px;color:var(--muted);font-size:14px;line-height:1.8}.testimonial-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:42px}.testimonial-card{overflow:hidden;background:#fff;border:1px solid var(--line)}.testimonial-media{height:255px;background:#eadcd6;position:relative}.testimonial-image{display:block;width:100%;height:100%;padding:0;border:0;background:none;cursor:zoom-in}.testimonial-image img,.testimonial-video video{display:block;width:100%;height:100%;object-fit:cover}.testimonial-video{height:100%;position:relative}.testimonial-video video{background:#24191c}.testimonial-play{position:absolute;right:15px;bottom:15px;display:grid;place-items:center;width:35px;height:35px;border-radius:50%;background:var(--rose);color:var(--ink);font-size:12px}.testimonial-no-media{display:grid;place-items:center;height:100%;color:#b99586;font:italic 50px var(--serif)}.testimonial-card-body{padding:20px}.testimonial-rating{display:flex;align-items:center;gap:9px;color:var(--muted);font-size:11px}.testimonial-stars{color:var(--gold);font-size:17px;letter-spacing:2px}.testimonial-quote{min-height:75px;margin:15px 0 20px;font:400 19px/1.3 var(--serif);color:var(--ink)}.testimonial-byline{display:flex;align-items:center;flex-wrap:wrap;gap:9px;font-size:12px}.testimonial-verified{color:#9c6b39;font-size:10px;letter-spacing:.03em}.testimonial-empty{padding:28px;background:var(--rose-light);color:var(--muted);line-height:1.7}.testimonial-lightbox{position:fixed;inset:0;z-index:50;display:grid;place-items:center;padding:25px;background:#171214dd}.testimonial-lightbox[hidden]{display:none}.testimonial-lightbox img{max-width:min(100%,980px);max-height:90vh;object-fit:contain}.testimonial-lightbox button{position:absolute;top:18px;right:20px;border:0;background:none;color:#fff;font-size:28px;cursor:pointer}
@media(max-width:850px){.testimonials-intro{display:block}.testimonial-grid{grid-template-columns:1fr 1fr}}@media(max-width:520px){.testimonial-grid{grid-template-columns:1fr}.testimonial-media{height:290px}}
</style>
<section class="page-hero"><div class="site-wrap page-hero-inner reveal"><div class="eyebrow"><?=testimonials_page_h($productName ? 'Stories for ' . $productName : 'The Boss Lady circle')?></div><h1>Real women.<br><em>Real presence.</em></h1><p>Every approved story is linked to a delivered order, so you can shop with a little more confidence.</p></div></section>
<section class="page-section"><div class="site-wrap"><div class="testimonials-intro reveal"><div><div class="eyebrow">Customer love</div><h2><?=testimonials_page_h($productName ? 'What they say about it.' : 'The feeling after the first spray.')?></h2></div><p><?=testimonials_page_h($productName ? 'Read verified stories from customers who chose this fragrance.' : 'From first meetings to ordinary Tuesdays, these are the scent stories that stayed with our customers.')?></p></div>
<?php if ($testimonialError): ?><div class="testimonial-empty" style="margin-top:30px">Customer stories are taking a short pause. Please check back shortly.</div>
<?php elseif (!$testimonials): ?><div class="testimonial-empty" style="margin-top:30px">No approved stories are available here yet. Be the first to share yours after your order is delivered.</div>
<?php else: ?><div class="testimonial-grid"><?php foreach ($testimonials as $testimonial): ?><?=testimonial_card_markup($testimonial)?><?php endforeach; ?></div><?php endif; ?></div></section>
<div class="testimonial-lightbox" id="testimonialLightbox" hidden><button type="button" aria-label="Close image">×</button><img alt=""></div>
<script>
const lightbox=document.querySelector('#testimonialLightbox');document.querySelectorAll('[data-lightbox-src]').forEach(button=>button.addEventListener('click',()=>{lightbox.querySelector('img').src=button.dataset.lightboxSrc;lightbox.querySelector('img').alt=button.dataset.lightboxAlt||'';lightbox.hidden=false}));lightbox.addEventListener('click',event=>{if(event.target===lightbox||event.target.tagName==='BUTTON')lightbox.hidden=true});document.addEventListener('keydown',event=>{if(event.key==='Escape')lightbox.hidden=true});
</script>
<?php site_end($config); ?>
