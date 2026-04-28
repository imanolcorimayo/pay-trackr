-- Adds 'ai-text', 'ai-pdf', 'ai-audio' to payment.source enum so the new
-- single-expense AI input flow on /pagos can tag the source per modality.
-- Idempotent: only ALTERs when the new values are missing.

SET @s := IF(
    (SELECT COUNT(*) FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'payment'
        AND column_name = 'source'
        AND column_type LIKE '%''ai-text''%') = 0,
    "ALTER TABLE payment MODIFY source ENUM('manual','whatsapp-text','whatsapp-audio','whatsapp-image','whatsapp-pdf','ai-image','ai-text','ai-pdf','ai-audio') NOT NULL DEFAULT 'manual'",
    'DO 0'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
