-- Run this once on an existing database before using product archives.
ALTER TABLE products ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL DEFAULT NULL;
CREATE INDEX IF NOT EXISTS idx_products_archived_at ON products(archived_at);
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
DROP TRIGGER IF EXISTS product_archive_guard ON products;
CREATE TRIGGER product_archive_guard BEFORE UPDATE ON products FOR EACH ROW EXECUTE FUNCTION prevent_product_unarchive();
