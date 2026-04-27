-- Adds 'ai-image' to payment.source enum so AI-extracted payments can be tagged.
-- Idempotent: safe on fresh install (already includes the value via rewritten _004)
-- and on live DB (extends the enum without dropping data).

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'payment'
                AND column_name = 'source'
                AND FIND_IN_SET('ai-image', REPLACE(REPLACE(REPLACE(SUBSTRING(column_type, 6, LENGTH(column_type) - 6), '''', ''), ' ', ''), ',', ',')) = 0) = 1,
             "ALTER TABLE payment MODIFY source ENUM('manual','whatsapp-text','whatsapp-audio','whatsapp-image','whatsapp-pdf','ai-image') NOT NULL DEFAULT 'manual'",
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
