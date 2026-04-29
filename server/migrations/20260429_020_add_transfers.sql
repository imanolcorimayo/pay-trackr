-- Phase 4: transfers between accounts.
--
-- A transfer is two (or three with a fee) `transaction` rows sharing a
-- `transfer_id`. There is no separate `transfer` table — legs are linked
-- purely by the shared UUID. Each leg lives on its own account with its own
-- currency, so account balances stay correct without storing an exchange rate.
--
-- `kind` classifies every row so we can exclude transfer legs from spend
-- rollups while keeping fees counted as expenses.
--
-- Idempotent: every step is guarded so re-runs are no-ops.

-- ──────────────────────────────────────────────────────────────────────────────
-- 1. transaction.transfer_id (nullable, indexed for fast group lookup)
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'transaction'
                AND column_name = 'transfer_id') = 0,
             'ALTER TABLE `transaction` ADD COLUMN transfer_id VARCHAR(36) NULL AFTER account_id',
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.statistics
              WHERE table_schema = DATABASE() AND table_name = 'transaction'
                AND index_name = 'idx_transaction_transfer') = 0,
             'ALTER TABLE `transaction` ADD INDEX idx_transaction_transfer (transfer_id)',
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 2. transaction.kind (expense | income | transfer | fee). Default 'expense'
--    matches the pre-Phase-4 reality where every row was an expense.
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'transaction'
                AND column_name = 'kind') = 0,
             "ALTER TABLE `transaction` ADD COLUMN kind ENUM('expense','income','transfer','fee') NOT NULL DEFAULT 'expense' AFTER transaction_type",
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
