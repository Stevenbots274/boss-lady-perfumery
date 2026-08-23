<?php
require __DIR__ . '/admin_common.php';
require __DIR__ . '/admin_layout.php';
admin_start('Settings', 'settings', $config);
?>
<section class="panel" id="settings"><div class="section-title"><div><div class="eyebrow">Workspace</div><h2>Settings.</h2></div><p>Keep access secure and get support when you need it.</p></div><div class="settings-grid"><div class="setting-copy"><h3>Change admin password</h3><p>Update the Supabase Auth password without leaving the workspace. Use at least 8 characters.</p></div><form class="password-form" method="post"><input type="hidden" name="csrf" value="<?=admin_h($csrfToken)?>"><div class="field"><label>New password</label><input name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required><?php if ($passwordNotice): ?><small><?=admin_h($passwordNotice)?></small><?php endif; ?></div><button class="primary" name="change_password" value="1">Update password</button></form></div></section>
<section class="panel"><div class="section-title"><div><div class="eyebrow">Customer care</div><h2>Need a hand?</h2></div><p>Use a ready-to-send message when you need help with the store.</p></div><a class="primary" style="display:inline-block;text-decoration:none" href="https://wa.me/<?=$config['whatsapp']?>?text=<?=rawurlencode('Hello Boss Lady Perfumery, I need help with my store.')?>">Open support on WhatsApp ↗</a></section>
<?php admin_end(); ?>
