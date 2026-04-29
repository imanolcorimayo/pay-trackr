-- Phase 2.5: per-account opening balance.
--
-- Lets the user declare "as of date X, this account had Y", so we can compute
-- a current balance without backfilling historical incomes/cash. Going
-- forward, balance = opening_balance + SUM(paid transactions on/after that
-- date). NULL date means "include everything".
--
-- Idempotent: column adds are guarded.

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'account'
                AND column_name = 'opening_balance') = 0,
             'ALTER TABLE account ADD COLUMN opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER currency',
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'account'
                AND column_name = 'opening_balance_date') = 0,
             'ALTER TABLE account ADD COLUMN opening_balance_date DATE NULL AFTER opening_balance',
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
