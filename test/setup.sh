#!/usr/bin/env bash
# Resets the test user, mints a fresh Firebase ID token, writes it to /tmp.
# Idempotent — safe to run multiple times.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
TEST_USER_ID="${TEST_USER_ID:-test-mangos-001}"
TOKEN_FILE="${TOKEN_FILE:-/tmp/mangos-test-token}"

DB_USER="imanol"
DB_PASS="1234"
DB_NAME="mangos"

echo "[setup] resetting test user $TEST_USER_ID..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null \
    -e "DELETE FROM \`user\` WHERE id='$TEST_USER_ID';"

mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null \
    < "$SCRIPT_DIR/fixtures.sql"

echo "[setup] minting Firebase ID token..."
ID_TOKEN=$(node "$REPO_ROOT/server/scripts/get-test-id-token.mjs" "$TEST_USER_ID")
if [ -z "$ID_TOKEN" ]; then
    echo "[setup] FAILED: empty token"
    exit 1
fi

printf '%s' "$ID_TOKEN" > "$TOKEN_FILE"
chmod 600 "$TOKEN_FILE"
echo "[setup] token written to $TOKEN_FILE (${#ID_TOKEN} chars)"
