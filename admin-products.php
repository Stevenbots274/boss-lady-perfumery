<?php
require __DIR__ . '/admin_common.php';
require __DIR__ . '/admin_layout.php';
admin_start('Products', 'products', $config);
$views = ['all' => 'All products', 'live' => 'Live', 'hidden' => 'Hidden', 'archived' => 'Archived'];
$editingImage = trim((string) ($editingProduct['image_url'] ?? ''));
?>
<section class="panel" id="products">
  <div class="section-title"><div><div class="eyebrow">Catalogue</div><h2><?= $editingProduct ? 'Edit product.' : 'Add a product.' ?></h2></div><p>Manage what customers can see without losing your product history.</p></div>
  <?php if ($editingProduct && product_state($editingProduct) === 'archived'): ?><div class="notice">This product is permanently archived. You can update its record, but it cannot be moved back to Live.</div><?php endif; ?>
  <form class="product-form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?=admin_h($csrfToken)?>">
    <input type="hidden" name="product_id" value="<?=admin_h($editingProduct['id'] ?? '')?>">
    <div class="field"><label for="product-name">Product name</label><input id="product-name" name="name" maxlength="160" value="<?=admin_h($editingProduct['name'] ?? '')?>" required></div>
    <div class="field"><label for="product-price">Price in NGN</label><input id="product-price" name="price" type="number" min="0.01" step=".01" value="<?= $editingProduct ? admin_h(number_format($editingProduct['price_kobo'] / 100, 2, '.', '')) : '' ?>" required></div>
    <div class="field full"><label for="product-description">Description</label><textarea id="product-description" name="description" maxlength="5000" placeholder="What does this fragrance feel like?"><?=admin_h($editingProduct['description'] ?? '')?></textarea></div>
    <div class="field"><label for="product-image">Product image upload</label><input id="product-image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><small>JPEG, PNG, or WebP. Maximum 5 MB.</small><?php if ($editingImage): ?><small>Leave the URL unchanged to keep the current image.</small><?php endif; ?><?php if ($editingProduct && $editingImage): ?><label class="check-label" for="remove-image"><input id="remove-image" name="remove_image" type="checkbox" value="1"> Remove the current image</label><?php endif; ?></div>
    <div class="field"><label for="product-image-url">Image URL fallback</label><input id="product-image-url" name="image_url" type="url" maxlength="500" placeholder="https://..." value="<?=admin_h($editingProduct['image_url'] ?? '')?>"><small>An uploaded file takes priority. Leave blank for no image on a new product.</small></div>
    <div class="field"><label for="product-stock">Stock</label><input id="product-stock" name="stock" type="number" min="0" value="<?=($editingProduct && $editingProduct['stock'] !== null) ? admin_h($editingProduct['stock']) : ''?>"><small>Leave blank for unlimited stock.</small></div>
    <div class="field full"><button name="save" value="1" type="submit">Save product</button></div>
  </form>
</section>
<section class="panel">
  <div class="section-title"><div><div class="eyebrow">Product visibility</div><h2>Choose what is live.</h2></div><p>Hidden products can return to Live. Archived products are permanent and can never be published again.</p></div>
  <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:26px">
    <?php foreach ($views as $key => $label): $tabStyle = $productView === $key ? 'background:var(--ink);color:var(--cream)' : 'background:#fff;color:var(--ink)'; ?><a href="/admin/products?view=<?=$key?>" style="display:inline-flex;gap:8px;align-items:center;padding:10px 13px;border:1px solid var(--line);border-radius:999px;text-decoration:none;font-size:11px;<?=$tabStyle?>" aria-current="<?=$productView === $key ? 'page' : 'false'?>"><?=$label?> <span><?=count(array_filter($products, fn($product) => $key === 'all' || product_state($product) === $key))?></span></a><?php endforeach; ?>
  </div>
  <div class="product-list">
    <?php if (!$visibleProducts): ?><p style="color:var(--muted)">There are no products in this view.</p><?php endif; ?>
    <?php foreach ($visibleProducts as $p): $state = product_state($p); ?>
      <div class="product-row">
        <?php if ($p['image_url']): ?><a href="<?=admin_h($p['image_url'])?>" target="_blank" rel="noreferrer" title="Open full-size image"><img class="thumb" src="<?=admin_h($p['image_url'])?>" alt="<?=admin_h($p['name'])?>" loading="lazy" decoding="async"></a><?php else: ?><span class="thumb-placeholder" aria-label="No product image">BL</span><?php endif; ?>
        <div><h3><?=admin_h($p['name'])?></h3><p><strong style="color:var(--<?=$state === 'live' ? 'gold' : ($state === 'hidden' ? 'rose' : 'muted')?>)"><?=admin_h(ucfirst($state))?></strong> · <?= $p['stock'] === null ? 'Unlimited stock' : (int) $p['stock'] . ' in stock' ?></p><div class="row-actions"><a href="/admin/products?view=<?=$productView?>&amp;edit=<?= (int) $p['id'] ?>">Edit</a><?php if ($state === 'live'): ?><form method="post"><input type="hidden" name="csrf" value="<?=admin_h($csrfToken)?>"><button name="del" value="<?= (int) $p['id'] ?>">Move to Hidden</button></form><form method="post" onsubmit="return confirm('Archive this product permanently? It cannot return to Live.');"><input type="hidden" name="csrf" value="<?=admin_h($csrfToken)?>"><button name="archive" value="<?= (int) $p['id'] ?>">Archive permanently</button></form><?php elseif ($state === 'hidden'): ?><form method="post"><input type="hidden" name="csrf" value="<?=admin_h($csrfToken)?>"><button name="restore" value="<?= (int) $p['id'] ?>">Move to Live</button></form><form method="post" onsubmit="return confirm('Archive this product permanently? It cannot return to Live.');"><input type="hidden" name="csrf" value="<?=admin_h($csrfToken)?>"><button name="archive" value="<?= (int) $p['id'] ?>">Archive permanently</button></form><?php endif; ?></div></div>
        <div class="product-row-meta"><strong>₦<?=number_format($p['price_kobo'] / 100, 2)?></strong><span><?=admin_h($p['archived_at'] ? 'History only' : ($p['stock'] === null ? 'Available' : 'Stocked'))?></span></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php admin_end(); ?>
