-- Adds the columns that link a payment to its AI input artifact stored on
-- DigitalOcean Spaces. ai_artifact_path holds the full S3 key (relative to
-- the bucket, including the project prefix) — e.g.
--   mangos/ai-uploads/<user_id>/<uuid>.webm
-- ai_artifact_mime caches the original mime so the proxy can serve it back
-- without round-tripping HEAD on every read.
-- Idempotent: only ALTERs when the columns are missing.

SET @s := IF(
    (SELECT COUNT(*) FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'payment'
        AND column_name = 'ai_artifact_path') = 0,
    "ALTER TABLE payment ADD COLUMN ai_artifact_path VARCHAR(255) NULL AFTER source",
    'DO 0'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF(
    (SELECT COUNT(*) FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'payment'
        AND column_name = 'ai_artifact_mime') = 0,
    "ALTER TABLE payment ADD COLUMN ai_artifact_mime VARCHAR(64) NULL AFTER ai_artifact_path",
    'DO 0'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
