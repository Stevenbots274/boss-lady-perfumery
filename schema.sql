CREATE TABLE products (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description TEXT,
  price_kobo INTEGER NOT NULL,
  image_url VARCHAR(500),
  stock INTEGER NULL DEFAULT NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  archived_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CHECK (price_kobo > 0),
  CHECK (stock IS NULL OR stock >= 0)
);

CREATE TABLE rate_limits (
  rate_key CHAR(64) PRIMARY KEY,
  window_started TIMESTAMP NOT NULL,
  request_count INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE orders (
  id BIGSERIAL PRIMARY KEY,
  order_code VARCHAR(40) NOT NULL UNIQUE,
  order_token CHAR(64) NOT NULL UNIQUE,
  customer_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  address TEXT NOT NULL,
  total_kobo INTEGER NOT NULL,
  payment_reference VARCHAR(100),
  payment_status VARCHAR(30) NOT NULL DEFAULT 'awaiting_whatsapp',
  order_status VARCHAR(30) NOT NULL DEFAULT 'new',
  stock_released_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CHECK (total_kobo > 0)
);

CREATE TABLE order_items (
  id BIGSERIAL PRIMARY KEY,
  order_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  product_name VARCHAR(160) NOT NULL,
  unit_price_kobo INTEGER NOT NULL,
  quantity INTEGER NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  CHECK (unit_price_kobo > 0),
  CHECK (quantity > 0)
);

CREATE OR REPLACE FUNCTION prevent_product_unarchive() RETURNS trigger AS $$
BEGIN
  IF OLD.archived_at IS NOT NULL AND NEW.archived_at IS NULL THEN
    RAISE EXCEPTION 'Archived products cannot be restored';
  END IF;
  IF NEW.archived_at IS NOT NULL THEN
    NEW.active = FALSE;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER product_archive_guard BEFORE UPDATE ON products FOR EACH ROW EXECUTE FUNCTION prevent_product_unarchive();

ALTER TABLE products ENABLE ROW LEVEL SECURITY;
ALTER TABLE rate_limits ENABLE ROW LEVEL SECURITY;
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE order_items ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON products, rate_limits, orders, order_items FROM anon, authenticated;
