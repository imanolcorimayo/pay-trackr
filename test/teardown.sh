#!/usr/bin/env bash
# Wipes the test user (CASCADE deletes all their rows across every table).

set -euo pipefail

TEST_USER_ID="${TEST_USER_ID:-test-mangos-001}"
TOKEN_FILE="${TOKEN_FILE:-/tmp/mangos-test-token}"

DB_USER="imanol"
DB_PASS="1234"
DB_NAME="mangos"

echo "[teardown] deleting test user $TEST_USER_ID..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null \
    -e "DELETE FROM \`user\` WHERE id='$TEST_USER_ID';"

rm -f "$TOKEN_FILE"
echo "[teardown] done"
