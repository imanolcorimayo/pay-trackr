-- Web Push notifications scaffolding.
--
-- Three tables:
--   push_subscription  — one row per (user, browser/device). Endpoint is the
--                        push service URL the browser hands us; treated as a
--                        unique identifier. Re-subscriptions upsert.
--   notification_pref  — one row per user. Master kill switch + per-kind
--                        toggles. Auto-created on first read.
--   notification_log   — append-only delivery log. Used for dedup ("don't
--                        send the same fijo reminder twice in 24h") and
--                        future audit/observability.

CREATE TABLE IF NOT EXISTS push_subscription (
    id          VARCHAR(36)  NOT NULL,
    user_id     VARCHAR(36)  NOT NULL,
    endpoint    VARCHAR(500) NOT NULL,
    p256dh      VARCHAR(255) NOT NULL,
    auth        VARCHAR(255) NOT NULL,
    user_agent  VARCHAR(255) NULL,
    created_ts  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_ts DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_endpoint (endpoint),
    KEY idx_user (user_id),
    CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_pref (
    user_id              VARCHAR(36) NOT NULL,
    master_off           TINYINT(1)  NOT NULL DEFAULT 0,
    daily_enabled        TINYINT(1)  NOT NULL DEFAULT 1,
    weekly_enabled       TINYINT(1)  NOT NULL DEFAULT 1,
    anomaly_alerts_enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_ts           DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_notif_pref_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_log (
    id          VARCHAR(36)  NOT NULL,
    user_id     VARCHAR(36)  NOT NULL,
    kind        VARCHAR(64)  NOT NULL,
    ref_id      VARCHAR(64)  NULL,
    sent_ts     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_kind_ref (user_id, kind, ref_id, sent_ts),
    CONSTRAINT fk_notif_log_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
