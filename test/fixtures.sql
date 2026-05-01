-- Idempotent: seeds the test user. Tests run as this UID via the Firebase ID
-- token minted by scripts/get-test-id-token.mjs.

INSERT INTO `user` (id, email, name, google_id)
VALUES ('test-mangos-001', 'test-mangos-001@test.local', 'Test User', 'test-mangos-001')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Seed default account so the test user can create transactions/recurrents.
INSERT INTO account (id, user_id, name, type, currency, is_default)
SELECT 'test-mangos-001-acct', 'test-mangos-001', 'Sin cuenta', 'other', 'ARS', 1
WHERE NOT EXISTS (
    SELECT 1 FROM account WHERE user_id = 'test-mangos-001' AND deleted_ts IS NULL
);

-- Mirror the auth-flow seed: every real user gets default income categories on
-- first login. The test user is created via this fixture (not through auth),
-- so we seed them here to keep parity with production state.
INSERT INTO income_category (id, user_id, name, color)
SELECT LOWER(HEX(RANDOM_BYTES(14))), 'test-mangos-001', d.name, d.color
FROM default_income_category d
WHERE NOT EXISTS (
    SELECT 1 FROM income_category WHERE user_id = 'test-mangos-001'
);
