-- Run this after schema.sql to enable authenticated product image uploads.
INSERT INTO storage.buckets (id, name, public)
VALUES ('product-images', 'product-images', true)
ON CONFLICT (id) DO UPDATE SET public = true;

DROP POLICY IF EXISTS boss_lady_authenticated_image_uploads ON storage.objects;
CREATE POLICY boss_lady_authenticated_image_uploads
ON storage.objects FOR INSERT TO authenticated
WITH CHECK (
  bucket_id = 'product-images'
  AND lower(storage.extension(name)) IN ('jpg', 'jpeg', 'png', 'webp')
);
