-- Extend fx_rate to a time-series.
--
-- Previously fx_rate held one row per currency (a hot cache of the latest
-- rate). For the analytics YoY comparison we need historical daily rates,
-- so we change the grain: one row per (currency, date). The "current rate"
-- is now just the most recent row by date.
--
-- Source for daily backfill: argentinadatos.com (free, no auth, blue dollar
-- series since 2011). Loaded via server/scripts/backfill-fx-history.php.

ALTER TABLE fx_rate
    ADD COLUMN `date` DATE NOT NULL DEFAULT '1970-01-01' AFTER currency_code;

-- Bring forward whatever the cache had as today's rate, dated to its fetch.
UPDATE fx_rate SET `date` = DATE(fetched_ts);

-- Swap primary key: (currency_code) → (currency_code, date).
ALTER TABLE fx_rate DROP PRIMARY KEY;
ALTER TABLE fx_rate ADD PRIMARY KEY (currency_code, `date`);

-- Range lookups for analytics (latest by currency, span by date).
ALTER TABLE fx_rate ADD INDEX idx_currency_date (currency_code, `date`);
