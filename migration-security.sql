-- Run this once on an existing database before deploying the updated PHP files.
-- Existing order links using the old order code must be replaced with new token links.
ALTER TABLE orders ADD COLUMN order_token CHAR(64) NULL AFTER order_code;
UPDATE orders SET order_token = LOWER(HEX(RANDOM_BYTES(32))) WHERE order_token IS NULL;
ALTER TABLE orders MODIFY order_token CHAR(64) NOT NULL;
ALTER TABLE orders ADD UNIQUE KEY uq_orders_order_token (order_token);
ALTER TABLE orders ADD COLUMN stock_released_at TIMESTAMP NULL DEFAULT NULL AFTER order_status;
UPDATE orders SET stock_released_at = CURRENT_TIMESTAMP WHERE order_status = 'cancelled' AND stock_released_at IS NULL;
ALTER TABLE products MODIFY stock INT NULL DEFAULT NULL;
ALTER TABLE order_items ADD CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT;
