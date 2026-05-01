-- Add income_category_id to transaction. Mutually exclusive with
-- expense_category_id at the application layer (POST/PUT enforces it):
-- expense rows use expense_category_id, income rows use income_category_id.
-- We don't add a CHECK constraint because MySQL 8 enforcement is opt-in and
-- the API is the single source of truth for the kind ↔ column mapping.

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'transaction'
      AND column_name = 'income_category_id'
);

SET @ddl := IF(@col_exists = 0,
    "ALTER TABLE `transaction` ADD COLUMN income_category_id VARCHAR(36) NULL AFTER expense_category_id, ADD CONSTRAINT fk_transaction_income_category FOREIGN KEY (income_category_id) REFERENCES income_category(id) ON DELETE SET NULL",
    "SELECT 'income_category_id already exists' AS msg"
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
