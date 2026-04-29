-- Phase 1 of the income/wallets/multi-currency expansion.
--
-- Renames the payment-domain tables to a transaction-domain naming, and flips
-- every existing amount to negative. From this migration forward, signed
-- amounts are the source of truth: expense = negative, income (Phase 4) =
-- positive. Direction is derived from the sign + the category type, so we do
-- not introduce a `direction` / `kind` column.
--
-- Renames:
--   payment            → transaction
--   payment_recipient  → transaction_recipient   (col payment_id → transaction_id)
--   payment_template   → transaction_template
--
-- Sign flip:
--   transaction.amount        := -ABS(amount)
--   recurrent.amount          := -ABS(amount)
--
-- Note: `transaction` is a reserved word in MySQL; PHP code uses backticks
-- when referencing the table.
--
-- Idempotent: safe on fresh install (no rows to flip; renames are no-ops if
-- target tables already exist) and on a live DB.

-- ──────────────────────────────────────────────────────────────────────────────
-- 1. Rename payment_template → transaction_template
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'payment_template') > 0
             AND (SELECT COUNT(*) FROM information_schema.tables
                  WHERE table_schema = DATABASE() AND table_name = 'transaction_template') = 0,
             'RENAME TABLE payment_template TO transaction_template', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 2. Rename payment_recipient → transaction_recipient
--    (FK to payment.id auto-updates when payment is renamed below)
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'payment_recipient') > 0
             AND (SELECT COUNT(*) FROM information_schema.tables
                  WHERE table_schema = DATABASE() AND table_name = 'transaction_recipient') = 0,
             'RENAME TABLE payment_recipient TO transaction_recipient', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 3. Rename payment → transaction
--    MySQL adjusts FKs from transaction_recipient and from any recurrent_id
--    references on payment automatically.
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'payment') > 0
             AND (SELECT COUNT(*) FROM information_schema.tables
                  WHERE table_schema = DATABASE() AND table_name = 'transaction') = 0,
             'RENAME TABLE payment TO `transaction`', 'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 4. Rename column transaction_recipient.payment_id → transaction_id
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'transaction_recipient'
                AND column_name = 'payment_id') > 0,
             'ALTER TABLE transaction_recipient CHANGE payment_id transaction_id VARCHAR(36) NOT NULL',
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 5. Rename column `transaction`.payment_type → transaction_type
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'transaction'
                AND column_name = 'payment_type') > 0,
             "ALTER TABLE `transaction` CHANGE payment_type transaction_type ENUM('one-time','recurrent') NOT NULL DEFAULT 'one-time'",
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 6. Sign flip — every current row is an expense, normalize to negative.
--    Using -ABS() makes this idempotent: re-running flips nothing.
-- ──────────────────────────────────────────────────────────────────────────────

UPDATE `transaction` SET amount = -ABS(amount);
UPDATE recurrent       SET amount = -ABS(amount);
