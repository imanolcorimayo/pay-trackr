-- Normalize legacy schema (plural -> singular tables, category_id -> expense_category_id,
-- drop is_credit_card, rename credit_card_id -> card_id) and add the `card` table.
--
-- Idempotent: safe to run on a fresh install (all ALTERs are no-ops because the
-- rewritten 001-009 migrations already produce the target schema) and on an
-- already-migrated database (where the ALTERs do real work).

-- ──────────────────────────────────────────────────────────────────────────────
-- 1. Rename tables (plural -> singular)
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'users') > 0,
             'RENAME TABLE users TO `user`', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'expense_categories') > 0,
             'RENAME TABLE expense_categories TO expense_category', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'recurrents') > 0,
             'RENAME TABLE recurrents TO recurrent', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'payments') > 0,
             'RENAME TABLE payments TO payment', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'payment_recipients') > 0,
             'RENAME TABLE payment_recipients TO payment_recipient', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'payment_templates') > 0,
             'RENAME TABLE payment_templates TO payment_template', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'fcm_tokens') > 0,
             'RENAME TABLE fcm_tokens TO fcm_token', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'weekly_summaries') > 0,
             'RENAME TABLE weekly_summaries TO weekly_summary', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'default_categories') > 0,
             'RENAME TABLE default_categories TO default_category', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 2. Rename category_id -> expense_category_id (recurrent, payment, payment_template)
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'recurrent'
                AND column_name = 'category_id') > 0,
             'ALTER TABLE recurrent CHANGE category_id expense_category_id VARCHAR(36)', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'payment'
                AND column_name = 'category_id') > 0,
             'ALTER TABLE payment CHANGE category_id expense_category_id VARCHAR(36)', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'payment_template'
                AND column_name = 'category_id') > 0,
             'ALTER TABLE payment_template CHANGE category_id expense_category_id VARCHAR(36)', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 3. Drop is_credit_card, rename credit_card_id -> card_id (recurrent)
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'recurrent'
                AND column_name = 'is_credit_card') > 0,
             'ALTER TABLE recurrent DROP COLUMN is_credit_card', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'recurrent'
                AND column_name = 'credit_card_id') > 0,
             'ALTER TABLE recurrent CHANGE credit_card_id card_id VARCHAR(36)', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 4. Create `card` table (always — does not exist in either state)
-- ──────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS card (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('credit', 'debit', 'virtual') NOT NULL,
    bank VARCHAR(100),
    last_four VARCHAR(4),
    color VARCHAR(20),
    closing_day TINYINT UNSIGNED,
    due_day TINYINT UNSIGNED,
    deleted_ts TIMESTAMP NULL DEFAULT NULL,
    created_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 5. Add FK recurrent.card_id -> card(id) (only if not already present)
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.referential_constraints
              WHERE constraint_schema = DATABASE()
                AND table_name = 'recurrent'
                AND constraint_name = 'fk_recurrent_card') = 0,
             'ALTER TABLE recurrent ADD CONSTRAINT fk_recurrent_card FOREIGN KEY (card_id) REFERENCES card(id) ON DELETE SET NULL', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
