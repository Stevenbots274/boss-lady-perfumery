-- Run this once on an existing database before using product archives.
ALTER TABLE products ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL DEFAULT NULL;
CREATE INDEX IF NOT EXISTS idx_products_archived_at ON products(archived_at);
