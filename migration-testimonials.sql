CREATE TABLE IF NOT EXISTS testimonials (
  id BIGSERIAL PRIMARY KEY,
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  order_id BIGINT NOT NULL UNIQUE REFERENCES orders(id) ON DELETE CASCADE,
  rating SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  message VARCHAR(3000) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at TIMESTAMPTZ NULL
);

CREATE TABLE IF NOT EXISTS testimonial_media (
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

CREATE TABLE IF NOT EXISTS testimonial_products (
  testimonial_id BIGINT NOT NULL REFERENCES testimonials(id) ON DELETE CASCADE,
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
  PRIMARY KEY (testimonial_id, product_id)
);

CREATE INDEX IF NOT EXISTS testimonials_status_created_idx ON testimonials(status, created_at DESC);
CREATE INDEX IF NOT EXISTS testimonials_user_id_idx ON testimonials(user_id);
CREATE INDEX IF NOT EXISTS testimonial_products_product_id_idx ON testimonial_products(product_id);

ALTER TABLE testimonial_media DROP CONSTRAINT IF EXISTS testimonial_media_testimonial_id_key;
CREATE UNIQUE INDEX IF NOT EXISTS testimonial_media_testimonial_type_idx ON testimonial_media(testimonial_id, media_type);

ALTER TABLE testimonials ENABLE ROW LEVEL SECURITY;
ALTER TABLE testimonial_media ENABLE ROW LEVEL SECURITY;
ALTER TABLE testimonial_products ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON testimonials, testimonial_media, testimonial_products FROM anon, authenticated;
