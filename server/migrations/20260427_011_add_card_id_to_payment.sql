-- Adds payment.card_id (nullable FK to card.id) so one-time payments can record
-- which card they were charged to. Idempotent: safe on fresh install (column
-- already created by the rewritten _004) and on live DB (adds column + FK).

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'payment'
                AND column_name = 'card_id') = 0,
             'ALTER TABLE payment ADD COLUMN card_id VARCHAR(36) AFTER recurrent_id', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.referential_constraints
              WHERE constraint_schema = DATABASE()
                AND table_name = 'payment'
                AND constraint_name = 'fk_payment_card') = 0,
             'ALTER TABLE payment ADD CONSTRAINT fk_payment_card FOREIGN KEY (card_id) REFERENCES card(id) ON DELETE SET NULL', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
