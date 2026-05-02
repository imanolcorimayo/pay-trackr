-- AI bulk-upload traceability.
--
-- The single-artifact flow (parse-single) already persists one file per
-- transaction via `transaction.ai_artifact_path`. The bulk flow
-- (parse-transactions) groups N files under a single upload session and may
-- emit M transactions from those N files — so a 1:1 column does not fit.
--
-- Two tables:
--   ai_batch       — one row per upload session. Stores transcription for
--                    audio batches.
--   ai_batch_file  — one row per uploaded file. `idx` is the position in the
--                    batch (matches Gemini's `screenshot_idx` for image
--                    batches; always 0 for audio).
--
-- And two columns on `transaction`:
--   ai_batch_id          — FK to ai_batch.id (NULL for non-batch rows)
--   ai_batch_match_idx   — the AI-assigned screenshot_idx for this row,
--                          captured at commit-time so we keep the match
--                          even if reprocessing logic changes later.

CREATE TABLE IF NOT EXISTS ai_batch (
    id            VARCHAR(36) NOT NULL,
    user_id       VARCHAR(36) NOT NULL,
    source        ENUM('ai-image','ai-audio') NOT NULL,
    transcription TEXT NULL,
    created_ts    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_created (user_id, created_ts),
    CONSTRAINT fk_ai_batch_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_batch_file (
    id          VARCHAR(36) NOT NULL,
    batch_id    VARCHAR(36) NOT NULL,
    idx         INT NOT NULL,
    spaces_path VARCHAR(255) NOT NULL,
    mime        VARCHAR(64) NOT NULL,
    created_ts  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_batch_idx (batch_id, idx),
    CONSTRAINT fk_ai_batch_file_batch FOREIGN KEY (batch_id) REFERENCES ai_batch(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- transaction.ai_batch_id
SET @s := IF(
    (SELECT COUNT(*) FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'transaction'
        AND column_name = 'ai_batch_id') = 0,
    "ALTER TABLE `transaction` ADD COLUMN ai_batch_id VARCHAR(36) NULL AFTER ai_artifact_mime",
    'DO 0'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- transaction.ai_batch_match_idx
SET @s := IF(
    (SELECT COUNT(*) FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = 'transaction'
        AND column_name = 'ai_batch_match_idx') = 0,
    "ALTER TABLE `transaction` ADD COLUMN ai_batch_match_idx INT NULL AFTER ai_batch_id",
    'DO 0'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index for reverse-lookup (find all rows for a batch). Idempotent guard.
SET @s := IF(
    (SELECT COUNT(*) FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'transaction'
        AND index_name = 'idx_ai_batch') = 0,
    "ALTER TABLE `transaction` ADD INDEX idx_ai_batch (ai_batch_id)",
    'DO 0'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- FK from transaction.ai_batch_id → ai_batch.id (ON DELETE SET NULL: if a
-- batch is purged, the transactions stay but lose their batch link).
SET @s := IF(
    (SELECT COUNT(*) FROM information_schema.table_constraints
      WHERE table_schema = DATABASE()
        AND table_name = 'transaction'
        AND constraint_name = 'fk_transaction_ai_batch') = 0,
    "ALTER TABLE `transaction` ADD CONSTRAINT fk_transaction_ai_batch FOREIGN KEY (ai_batch_id) REFERENCES ai_batch(id) ON DELETE SET NULL",
    'DO 0'
);
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
