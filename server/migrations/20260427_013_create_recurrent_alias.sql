-- Adds 1:N aliases per recurrent so the AI capture flow can match expenses by
-- recipient/merchant variants (e.g. "NAVARRO AMADEO ANDRES" → "Clases de running"
-- recurrent), even when the recurrent's title doesn't contain the recipient name.
-- Idempotent: safe on fresh install and on live DB.

SET @s := IF((SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = DATABASE() AND table_name = 'recurrent_alias') = 0,
             "CREATE TABLE recurrent_alias (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                recurrent_id VARCHAR(36) NOT NULL,
                alias VARCHAR(200) NOT NULL,
                created_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (recurrent_id) REFERENCES recurrent(id) ON DELETE CASCADE,
                UNIQUE KEY uniq_recurrent_alias (recurrent_id, alias),
                INDEX idx_recurrent (recurrent_id)
              )",
             'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
