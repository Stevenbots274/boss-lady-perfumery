CREATE TABLE products (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description TEXT,
  price_kobo INTEGER NOT NULL,
  image_url VARCHAR(500),
  video_url TEXT,
  video_thumbnail_url TEXT,
  video_imagekit_file_id VARCHAR(255),
  video_file_size BIGINT,
  video_duration_seconds NUMERIC(8,3),
  video_mime_type VARCHAR(100),
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

CREATE TABLE testimonials (
  id BIGSERIAL PRIMARY KEY,
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  order_id BIGINT NOT NULL UNIQUE REFERENCES orders(id) ON DELETE CASCADE,
  rating SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  message VARCHAR(3000) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at TIMESTAMPTZ NULL
);

CREATE TABLE testimonial_media (
  id BIGSERIAL PRIMARY KEY,
  testimonial_id BIGINT NOT NULL REFERENCES testimonials(id) ON DELETE CASCADE,
  provider VARCHAR(30) NOT NULL DEFAULT 'imagekit' CHECK (provider = 'imagekit'),
  media_type VARCHAR(10) NOT NULL CHECK (media_type IN ('image', 'video')),
  media_url TEXT NOT NULL,
  thumbnail_url TEXT NULL,
  imagekit_file_id VARCHAR(255) NOT NULL,
  file_size BIGINT NOT NULL CHECK (file_size > 0),
  duration_seconds NUMERIC(8,3) NULL CHECK (duration_seconds IS NULL OR duration_seconds > 0),
  mime_type VARCHAR(100) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE testimonial_products (
  testimonial_id BIGINT NOT NULL REFERENCES testimonials(id) ON DELETE CASCADE,
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
  PRIMARY KEY (testimonial_id, product_id)
);

CREATE INDEX testimonials_status_created_idx ON testimonials(status, created_at DESC);
CREATE INDEX testimonials_user_id_idx ON testimonials(user_id);
CREATE INDEX testimonial_products_product_id_idx ON testimonial_products(product_id);
CREATE UNIQUE INDEX testimonial_media_testimonial_type_idx ON testimonial_media(testimonial_id, media_type);

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
ALTER TABLE testimonials ENABLE ROW LEVEL SECURITY;
ALTER TABLE testimonial_media ENABLE ROW LEVEL SECURITY;
ALTER TABLE testimonial_products ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON products, rate_limits, orders, order_items FROM anon, authenticated;
REVOKE ALL ON testimonials, testimonial_media, testimonial_products FROM anon, authenticated;
