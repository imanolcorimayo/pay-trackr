-- Idempotent: seeds the test user. Tests run as this UID via the Firebase ID
-- token minted by scripts/get-test-id-token.mjs.

INSERT INTO `user` (id, email, name, google_id)
VALUES ('test-mangos-001', 'test-mangos-001@test.local', 'Test User', 'test-mangos-001')
ON DUPLICATE KEY UPDATE email = VALUES(email);
