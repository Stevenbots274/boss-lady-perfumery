ALTER TABLE products ADD COLUMN IF NOT EXISTS video_url TEXT;
ALTER TABLE products ADD COLUMN IF NOT EXISTS video_thumbnail_url TEXT;
ALTER TABLE products ADD COLUMN IF NOT EXISTS video_imagekit_file_id VARCHAR(255);
ALTER TABLE products ADD COLUMN IF NOT EXISTS video_file_size BIGINT;
ALTER TABLE products ADD COLUMN IF NOT EXISTS video_duration_seconds NUMERIC(8,3);
ALTER TABLE products ADD COLUMN IF NOT EXISTS video_mime_type VARCHAR(100);
