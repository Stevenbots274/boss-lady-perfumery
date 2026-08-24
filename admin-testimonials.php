<?php
require __DIR__ . '/admin_common.php';
require_once __DIR__ . '/imagekit.php';
require __DIR__ . '/admin_layout.php';
$filter = in_array($_GET['status'] ?? 'pending', ['pending', 'approved', 'rejected', 'all'], true) ? $_GET['status'] : 'pending';
$filterSql = $filter === 'all' ? '' : ' WHERE t.status=?';
if (isset($_POST['testimonial_action'])) {
    $id = filter_var($_POST['testimonial_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
    $action = is_string($_POST['testimonial_action']) ? $_POST['testimonial_action'] : '';
    if (!$id || !in_array($action, ['approve', 'reject', 'delete'], true)) admin_redirect('testimonial-error');
    try {
        if ($action === 'delete') {
            $mediaStatement = $pdo->prepare('SELECT imagekit_file_id FROM testimonial_media WHERE testimonial_id=?');
            $mediaStatement->execute([$id]);
            $fileId = $mediaStatement->fetchColumn() ?: null;
            $delete = $pdo->prepare('DELETE FROM testimonials WHERE id=? RETURNING id');
            $delete->execute([$id]);
            if ($delete->fetchColumn() && $fileId) imagekit_delete_file($fileId, $config);
            admin_redirect('testimonial-deleted');
        }
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $statement = $pdo->prepare('UPDATE testimonials SET status=?,approved_at=? WHERE id=?');
        $statement->execute([$status, $status === 'approved' ? date('c') : null, $id]);
        admin_redirect($action === 'approve' ? 'testimonial-approved' : 'testimonial-rejected');
    } catch (Throwable $e) {
        error_log('Boss Lady testimonial moderation failed.');
        admin_redirect('testimonial-error');
    }
}
$testimonials = [];
$loadError = false;
try {
    $query = "SELECT t.id,t.rating,t.message,t.status,t.created_at,t.approved_at,o.order_code,o.customer_name,o.email,o.order_status,tm.media_type,tm.media_url,tm.thumbnail_url,tm.imagekit_file_id,tm.file_size,tm.duration_seconds,tm.mime_type,COALESCE(string_agg(DISTINCT oi.product_name, ', ' ORDER BY oi.product_name),'') AS products FROM testimonials t INNER JOIN orders o ON o.id=t.order_id LEFT JOIN testimonial_media tm ON tm.testimonial_id=t.id LEFT JOIN order_items oi ON oi.order_id=o.id {$filterSql} GROUP BY t.id,o.id,tm.id ORDER BY CASE t.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END,t.created_at DESC";
    $statement = $pdo->prepare($query);
    if ($filterSql) $statement->execute([$filter]); else $statement->execute();
    $testimonials = $statement->fetchAll();
} catch (Throwable $e) {
    error_log('Boss Lady admin testimonials load failed.');
    $loadError = true;
}
admin_start('Testimonials', 'testimonials', $config);
?>
<style>
.testimonial-admin-intro{display:flex;justify-content:space-between;gap:30px;align-items:end;margin-bottom:25px}.testimonial-admin-intro h2{margin:14px 0 0;font:400 clamp(34px,4vw,52px) var(--serif);letter-spacing:-.04em}.testimonial-admin-intro p{max-width:390px;color:var(--muted);line-height:1.7}.testimonial-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}.testimonial-tabs a{padding:9px 13px;border:1px solid var(--line);border-radius:999px;color:var(--muted);font-size:11px}.testimonial-tabs a.active{background:var(--ink);border-color:var(--ink);color:var(--cream)}.testimonial-admin-list{display:grid;gap:14px}.testimonial-admin-card{display:grid;grid-template-columns:180px 1fr;gap:22px;padding:20px;background:#fff;border:1px solid var(--line)}.testimonial-admin-media{min-height:150px;background:#eadcd6}.testimonial-admin-media img,.testimonial-admin-media video{display:block;width:100%;height:100%;min-height:150px;object-fit:cover}.testimonial-admin-copy h3{margin:0 0 6px;font:400 25px var(--serif)}.testimonial-admin-copy small,.testimonial-admin-meta{color:var(--muted);font-size:11px;line-height:1.6}.testimonial-admin-message{margin:15px 0;font:18px/1.4 var(--serif)}.testimonial-admin-actions{display:flex;flex-wrap:wrap;gap:8px}.testimonial-admin-actions form{margin:0}.testimonial-admin-actions button{padding:9px 12px;border:1px solid var(--line);background:var(--paper);color:var(--ink);font-size:11px;cursor:pointer}.testimonial-admin-actions .approve{background:var(--ink);border-color:var(--ink);color:var(--cream)}.testimonial-admin-actions .delete{color:#9a4d4d}.admin-testimonial-empty{padding:25px;background:#fff;border:1px solid var(--line);color:var(--muted);line-height:1.7}@media(max-width:700px){.testimonial-admin-intro{display:block}.testimonial-admin-card{grid-template-columns:1fr}.testimonial-admin-media{max-height:280px}}
</style>
<section class="testimonial-admin-intro"><div><div class="eyebrow">Moderation desk</div><h2>Customer stories.</h2></div><p>Review every submission before it appears on the storefront. Verified purchase status comes from a delivered order.</p></section>
<div class="testimonial-tabs"><?php foreach (['pending' => 'Pending', 'approved' => 'Published', 'rejected' => 'Rejected', 'all' => 'All stories'] as $key => $label): ?><a class="<?=$filter === $key ? 'active' : ''?>" href="/admin/testimonials?status=<?=$key?>" aria-current="<?=$filter === $key ? 'page' : 'false'?>"><?=$label?></a><?php endforeach; ?></div>
<?php if ($loadError): ?><div class="admin-testimonial-empty">Testimonials are not available yet. Apply <code>migration-testimonials.sql</code>, then refresh this page.</div>
<?php elseif (!$testimonials): ?><div class="admin-testimonial-empty">There are no stories in this view.</div>
<?php else: ?><div class="testimonial-admin-list"><?php foreach ($testimonials as $testimonial): ?><article class="testimonial-admin-card"><div class="testimonial-admin-media"><?php if ($testimonial['media_type'] === 'image' && $testimonial['media_url']): ?><img src="<?=admin_h($testimonial['media_url'])?>" alt="Customer testimonial media" loading="lazy"><?php elseif ($testimonial['media_type'] === 'video' && $testimonial['media_url']): ?><video controls preload="metadata" poster="<?=admin_h($testimonial['thumbnail_url'] ?? '')?>"><source src="<?=admin_h($testimonial['media_url'])?>" type="<?=admin_h($testimonial['mime_type'] ?? 'video/mp4')?>"></video><?php else: ?><span style="display:grid;place-items:center;height:100%;min-height:150px;color:#b99586;font:italic 45px Georgia">BL</span><?php endif; ?></div><div class="testimonial-admin-copy"><h3><?=admin_h($testimonial['customer_name'])?></h3><div class="testimonial-admin-meta"><?=admin_h($testimonial['email'])?> · Order <?=admin_h($testimonial['order_code'])?> · <?=admin_h($testimonial['order_status'])?><br><?=admin_h($testimonial['products'] ?: 'Order products unavailable')?> · Submitted <?=admin_h(date('j M Y, g:ia', strtotime($testimonial['created_at'])))?></div><div style="margin-top:12px;color:var(--gold);font-size:18px;letter-spacing:2px"><?=str_repeat('★', (int) $testimonial['rating'])?><span style="color:#cfc2bb"><?=str_repeat('★', 5 - (int) $testimonial['rating'])?></span></div><p class="testimonial-admin-message">&ldquo;<?=admin_h($testimonial['message'])?>&rdquo;</p><div class="testimonial-admin-actions"><?php if ($testimonial['status'] !== 'approved'): ?><form method="post"><input type="hidden" name="csrf" value="<?=admin_h($csrfToken)?>"><input type="hidden" name="testimonial_id" value="<?=intval($testimonial['id'])?>"><button class="approve" name="testimonial_action" value="approve">Approve &amp; publish</button></form><?php endif; ?><?php if ($testimonial['status'] !== 'rejected'): ?><form method="post"><input type="hidden" name="csrf" value="<?=admin_h($csrfToken)?>"><input type="hidden" name="testimonial_id" value="<?=intval($testimonial['id'])?>"><button name="testimonial_action" value="reject">Reject</button></form><?php endif; ?><form method="post" onsubmit="return confirm('Delete this testimonial and its media permanently?');"><input type="hidden" name="csrf" value="<?=admin_h($csrfToken)?>"><input type="hidden" name="testimonial_id" value="<?=intval($testimonial['id'])?>"><button class="delete" name="testimonial_action" value="delete">Delete</button></form></div></div></article><?php endforeach; ?></div><?php endif; ?>
<?php admin_end(); ?>
