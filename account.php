<?php
$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/customer_auth.php';
require __DIR__ . '/db.php';
$customer = customer_auth_session($config);
$orders = [];
$ordersError = false;
if ($customer) {
    if ($pdo instanceof PDO) {
        try {
            $statement = $pdo->prepare("SELECT o.id,o.order_code,o.order_token,o.total_kobo,o.payment_status,o.order_status,o.created_at,COALESCE(json_agg(json_build_object('name',oi.product_name,'quantity',oi.quantity,'unit_price_kobo',oi.unit_price_kobo) ORDER BY oi.id) FILTER (WHERE oi.id IS NOT NULL),'[]'::json) AS items FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id WHERE lower(o.email)=lower(?) GROUP BY o.id ORDER BY o.created_at DESC LIMIT 100");
            $statement->execute([$customer['email']]);
            $orders = $statement->fetchAll();
            foreach ($orders as &$order) {
                $decoded = json_decode($order['items'] ?? '[]', true);
                $order['items'] = is_array($decoded) ? $decoded : [];
            }
            unset($order);
        } catch (Throwable $e) {
            error_log('Boss Lady customer order history failed.');
            $ordersError = true;
        }
    } else $ordersError = true;
}

function account_h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function account_naira($kobo) { return '₦' . number_format(((int) $kobo) / 100, 2); }
function account_order_status($value) { return ['new' => 'New', 'processing' => 'Processing', 'ready' => 'Ready for delivery', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'][$value] ?? 'Updated'; }
function account_payment_status($value) { return ['awaiting_whatsapp' => 'Awaiting WhatsApp confirmation', 'pending' => 'Payment pending', 'paid' => 'Paid', 'failed' => 'Payment failed', 'refunded' => 'Refunded'][$value] ?? 'Payment update'; }
require __DIR__ . '/site_layout.php';
site_start($config, 'My account | Boss Lady Perfumery', 'Optional Boss Lady Perfumery account access and order history.', 'account', false);
?>
<style>
.account-grid{display:grid;grid-template-columns:.8fr 1.2fr;gap:55px;align-items:start}.account-intro h2{margin:18px 0;font:400 clamp(38px,5vw,60px)/.98 var(--serif);letter-spacing:-.04em}.account-intro p,.account-summary p{max-width:430px;color:var(--muted);font-size:14px;line-height:1.8}.account-note{margin-top:28px;padding:18px 20px;border-left:2px solid var(--gold);background:var(--rose-light);color:var(--muted);font-size:12px;line-height:1.7}.account-card,.account-summary{padding:30px;background:var(--ink);color:var(--cream)}.account-card h2,.account-summary h2{margin:0 0 8px;font:400 34px var(--serif)}.account-card>p,.account-summary p{color:#b9acad;font-size:12px}.auth-tabs{display:flex;gap:8px;margin:24px 0 20px;border-bottom:1px solid #ffffff22}.auth-tabs button{padding:0 0 12px;border:0;background:none;color:#b9acad;cursor:pointer;font-size:12px}.auth-tabs button.active{color:var(--rose);border-bottom:2px solid var(--rose)}.account-form{display:grid;gap:10px}.account-form[hidden]{display:none}.account-form label{color:#c8b9bb;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}.account-form input{padding:13px;border:1px solid #5f5053;background:#302629;color:var(--cream)}.account-form button,.account-link{margin-top:8px;padding:13px 18px;border:0;border-radius:999px;background:var(--rose);color:#27171b;font-weight:700;cursor:pointer}.account-message{min-height:20px;margin:16px 0 0;color:var(--rose);font-size:12px;line-height:1.6}.orders-panel{grid-column:1/-1}.orders-head{display:flex;justify-content:space-between;gap:20px;align-items:end;margin-bottom:20px}.orders-head h2{margin:0;font:400 clamp(34px,5vw,52px) var(--serif)}.orders-head p{margin:0;color:var(--muted);font-size:12px}.order-list{display:grid;gap:12px}.account-order{padding:22px;background:#fff;border:1px solid var(--line)}.order-head{display:flex;justify-content:space-between;gap:16px;align-items:start}.order-head small,.order-meta{color:var(--muted);font-size:11px}.order-head h3{margin:6px 0 0;font:400 22px var(--serif)}.order-meta{display:flex;flex-wrap:wrap;gap:8px 18px;margin:18px 0 12px}.order-items{margin:0;color:var(--muted);font-size:12px;line-height:1.8}.order-link{flex:none;padding:10px 13px;border:1px solid var(--line);border-radius:999px;color:var(--ink);font-size:11px;font-weight:700}.empty-orders{padding:25px;background:#fff;border:1px solid var(--line);color:var(--muted);font-size:13px;line-height:1.7}@media(max-width:850px){.account-grid{grid-template-columns:1fr;gap:30px}.orders-panel{grid-column:auto}.orders-head{display:block}.orders-head p{margin-top:10px}.order-head{display:block}.order-link{display:inline-block;margin-top:14px}}
</style>
<style>
.account-summary .account-link{display:inline-flex;align-items:center;justify-content:center;margin-top:22px}
@media(max-width:850px){.account-grid{grid-template-columns:1fr;gap:30px}.orders-panel{grid-column:auto}.orders-head{display:block}.orders-head p{margin-top:10px}}
 @media(max-width:520px){.account-card,.account-summary{padding:22px}.order-head{display:block}.order-actions{margin-top:13px}.order-link{display:inline-block;margin:0 12px 0 0}.order-meta{display:grid;gap:7px;margin-top:18px}}
</style>
<section class="page-hero"><div class="site-wrap page-hero-inner reveal"><div class="eyebrow">Optional account</div><h1>Keep your orders<br><em>close at hand.</em></h1><p>Save your order history in one place. You can always shop and check out as a guest.</p></div></section>
<section class="page-section"><div class="site-wrap account-grid">
<?php if ($customer): ?>
  <div class="account-summary reveal"><div class="eyebrow">Signed in</div><h2>Welcome back.</h2><p><?=account_h($customer['email'])?></p><div class="account-note">Your previous guest orders using this same email address appear below automatically.</div><button class="account-link" type="button" data-account-logout>Sign out</button></div>
  <section class="orders-panel reveal" id="orders"><div class="orders-head"><h2>My orders.</h2><p>Order history for <?=account_h($customer['email'])?></p></div>
  <?php if ($ordersError): ?><div class="empty-orders">Your order history is taking a short pause. Please try again shortly. Your guest checkout and public order links are not affected.</div>
  <?php elseif (!$orders): ?><div class="empty-orders">No orders are connected to this email yet. You can shop whenever you are ready, with or without an account.</div>
  <?php else: ?><div class="order-list"><?php foreach ($orders as $order): ?><article class="account-order"><div class="order-head"><div><small>Order ID</small><h3><?=account_h($order['order_code'])?></h3></div><div class="order-actions"><a class="order-link" href="/order/<?=account_h($order['order_token'])?>">View order ↗</a><?php if ($order['order_status'] === 'delivered'): ?><a class="order-link order-testimonial-link" href="/testimonial?order=<?=intval($order['id'])?>">Leave a testimonial ↗</a><?php endif; ?></div></div><div class="order-meta"><span>Date: <?=account_h(date('j M Y', strtotime($order['created_at'])))?></span><span>Payment: <?=account_h(account_payment_status($order['payment_status']))?></span><span>Order: <?=account_h(account_order_status($order['order_status']))?></span><strong><?=account_h(account_naira($order['total_kobo']))?></strong></div><p class="order-items"><?php $itemCount = count($order['items']); foreach ($order['items'] as $itemIndex => $item): ?><?=account_h($item['name'])?> × <?=intval($item['quantity'])?><?php if ($itemIndex + 1 < $itemCount): ?> · <?php endif; ?><?php endforeach; ?></p></article><?php endforeach; ?></div><?php endif; ?></section>
<?php else: ?>
  <div class="account-intro reveal"><div class="eyebrow">Optional convenience</div><h2>Buy first.<br><em>Sign in when ready.</em></h2><p>An account is never required. Create one only if you would like your past and future orders gathered in one place.</p><div class="account-note">Guest checkout stays exactly the same: browse, add to bag, enter your details, receive an Order ID, and continue on WhatsApp.</div></div>
  <div class="account-card reveal" id="customerAuth" data-supabase-url="<?=account_h($config['supabase_url'])?>" data-supabase-anon-key="<?=account_h($config['supabase_anon_key'])?>"><h2>Welcome in.</h2><p>Use only an email and password. No phone verification or profile setup.</p><div class="auth-tabs"><button class="active" type="button" data-auth-tab="signin">Sign In</button><button type="button" data-auth-tab="signup">Create Account</button></div><form class="account-form" id="signinForm"><label for="signinEmail">Email</label><input id="signinEmail" name="email" type="email" autocomplete="email" required><label for="signinPassword">Password</label><input id="signinPassword" name="password" type="password" autocomplete="current-password" required><button type="submit">Sign In</button></form><form class="account-form" id="signupForm" hidden><label for="signupEmail">Email</label><input id="signupEmail" name="email" type="email" autocomplete="email" required><label for="signupPassword">Password</label><input id="signupPassword" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required><button type="submit">Create Account</button></form><p class="account-message" id="accountMessage" role="status" aria-live="polite"></p></div>
<?php endif; ?>
</div></section>
<script src="/assets/account.js" defer></script>
<?php site_end($config); ?>
