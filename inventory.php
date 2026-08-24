<?php
function release_order_stock($pdo, $orderId)
{
    $items = $pdo->prepare('SELECT product_id,SUM(quantity) AS quantity FROM order_items WHERE order_id=? GROUP BY product_id ORDER BY product_id');
    $items->execute([$orderId]);
    $product = $pdo->prepare('SELECT stock FROM products WHERE id=? FOR UPDATE');
    $update = $pdo->prepare('UPDATE products SET stock=? WHERE id=?');
    while ($item = $items->fetch()) {
        $product->execute([(int) $item['product_id']]);
        $row = $product->fetch();
        if (!$row) throw new RuntimeException('Product no longer exists.');
        if ($row['stock'] !== null) {
            $newStock = (int) $row['stock'] + (int) $item['quantity'];
            if ($newStock > 2147483647) throw new RuntimeException('Product stock is out of range.');
            $update->execute([$newStock, (int) $item['product_id']]);
        }
    }
}

function release_expired_reservations($pdo)
{
    $startedTransaction = false;
    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }
        $expired = $pdo->query("SELECT id FROM orders WHERE order_status='new' AND stock_released_at IS NULL AND created_at <= CURRENT_TIMESTAMP - INTERVAL '24 hours' ORDER BY id FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
        $markReleased = $pdo->prepare("UPDATE orders SET order_status='cancelled',stock_released_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=? AND order_status='new' AND stock_released_at IS NULL");
        foreach ($expired as $orderId) {
            release_order_stock($pdo, (int) $orderId);
            $markReleased->execute([(int) $orderId]);
        }
        if ($startedTransaction) $pdo->commit();
        return count($expired);
    } catch (Throwable $e) {
        if ($startedTransaction && $pdo->inTransaction()) $pdo->rollBack();
        if (!$startedTransaction) throw $e;
        error_log('Boss Lady expired stock release failed.');
        return null;
    }
}
