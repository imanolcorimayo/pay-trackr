-- Phase 2 of the income/wallets/multi-currency expansion.
--
-- Introduces the wallet/account concept (bank, cash, crypto) and per-row
-- currency. Existing rows are backfilled to a seeded "Sin cuenta" account.
--
-- Cards stay untouched: account is the source-of-money concept; card remains
-- the payment-instrument concept. A transaction can reference both.
--
-- Currency ENUM is intentionally narrow ('ARS','USD','USDT') to match actual
-- usage. Add more currencies via a follow-up ALTER if needed.
--
-- Idempotent: every step is guarded so re-runs are no-ops.

-- ──────────────────────────────────────────────────────────────────────────────
-- 1. Create `account` table
-- ──────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS account (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('bank','cash','crypto','other') NOT NULL DEFAULT 'bank',
    currency ENUM('ARS','USD','USDT') NOT NULL DEFAULT 'ARS',
    color VARCHAR(20),
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    deleted_ts TIMESTAMP NULL DEFAULT NULL,
    created_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 2. Seed "Sin cuenta" per existing user (skip if user already has a default)
-- ──────────────────────────────────────────────────────────────────────────────

INSERT INTO account (id, user_id, name, type, currency, is_default)
SELECT UUID(), u.id, 'Sin cuenta', 'other', 'ARS', 1
FROM `user` u
WHERE NOT EXISTS (
    SELECT 1 FROM account a
    WHERE a.user_id = u.id AND a.is_default = 1 AND a.deleted_ts IS NULL
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 3. Add transaction.account_id + transaction.currency
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'transaction'
                AND column_name = 'account_id') = 0,
             'ALTER TABLE `transaction` ADD COLUMN account_id VARCHAR(36) NULL AFTER card_id',
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'transaction'
                AND column_name = 'currency') = 0,
             "ALTER TABLE `transaction` ADD COLUMN currency ENUM('ARS','USD','USDT') NOT NULL DEFAULT 'ARS' AFTER amount",
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.referential_constraints
              WHERE constraint_schema = DATABASE()
                AND table_name = 'transaction'
                AND constraint_name = 'fk_transaction_account') = 0,
             'ALTER TABLE `transaction` ADD CONSTRAINT fk_transaction_account FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE SET NULL',
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 4. Add recurrent.account_id + recurrent.currency
-- ──────────────────────────────────────────────────────────────────────────────

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'recurrent'
                AND column_name = 'account_id') = 0,
             'ALTER TABLE recurrent ADD COLUMN account_id VARCHAR(36) NULL AFTER card_id',
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'recurrent'
                AND column_name = 'currency') = 0,
             "ALTER TABLE recurrent ADD COLUMN currency ENUM('ARS','USD','USDT') NOT NULL DEFAULT 'ARS' AFTER amount",
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s := IF((SELECT COUNT(*) FROM information_schema.referential_constraints
              WHERE constraint_schema = DATABASE()
                AND table_name = 'recurrent'
                AND constraint_name = 'fk_recurrent_account') = 0,
             'ALTER TABLE recurrent ADD CONSTRAINT fk_recurrent_account FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE SET NULL',
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ──────────────────────────────────────────────────────────────────────────────
-- 5. Backfill — point existing rows at the user's "Sin cuenta"
--    Only touches rows where account_id is still NULL, so re-runs are no-ops.
-- ──────────────────────────────────────────────────────────────────────────────

UPDATE `transaction` t
JOIN account a ON a.user_id = t.user_id AND a.is_default = 1 AND a.deleted_ts IS NULL
SET t.account_id = a.id
WHERE t.account_id IS NULL;

UPDATE recurrent r
JOIN account a ON a.user_id = r.user_id AND a.is_default = 1 AND a.deleted_ts IS NULL
SET r.account_id = a.id
WHERE r.account_id IS NULL;
