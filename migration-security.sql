-- Run this once on an existing Supabase PostgreSQL database before deploying the updated PHP files.
-- Existing order links using the old order code must be replaced with new token links.
CREATE EXTENSION IF NOT EXISTS pgcrypto;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS order_token CHAR(64);
UPDATE orders SET order_token = encode(gen_random_bytes(32), 'hex') WHERE order_token IS NULL;
ALTER TABLE orders ALTER COLUMN order_token SET NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_orders_order_token ON orders(order_token);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS stock_released_at TIMESTAMP NULL DEFAULT NULL;
UPDATE orders SET stock_released_at = CURRENT_TIMESTAMP WHERE order_status = 'cancelled' AND stock_released_at IS NULL;
ALTER TABLE products ALTER COLUMN stock DROP NOT NULL;
ALTER TABLE products ALTER COLUMN stock DROP DEFAULT;
ALTER TABLE order_items ADD CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT;
CREATE TABLE IF NOT EXISTS rate_limits (
  rate_key CHAR(64) PRIMARY KEY,
  window_started TIMESTAMP NOT NULL,
  request_count INTEGER NOT NULL DEFAULT 0
);
