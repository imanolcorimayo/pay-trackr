-- Phase 3 (light): cache table for FX rates fetched from dolarapi.com.
--
-- Stores one row per non-ARS currency. Source endpoints:
--   USD  → https://dolarapi.com/v1/dolares/blue
--   USDT → https://dolarapi.com/v1/dolares/cripto
--
-- The handler refreshes a row when fetched_ts is older than the TTL (24h);
-- otherwise it returns the cached value. ARS itself is implicit (rate=1).
--
-- Idempotent.

CREATE TABLE IF NOT EXISTS fx_rate (
    currency_code VARCHAR(10) NOT NULL PRIMARY KEY,
    rate_to_ars DECIMAL(14,4) NOT NULL,
    source VARCHAR(20) NOT NULL,
    fetched_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
