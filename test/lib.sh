#!/usr/bin/env bash
# Shared helpers for /test/test_*.sh scripts.
# Source this from each test file: . "$(dirname "$0")/lib.sh"

set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TEST_DIR="$REPO_ROOT/test"

: "${BASE_URL:=http://mangos-api.ima-wiseutils.com}"
: "${TEST_USER_ID:=test-mangos-001}"

DB_USER="imanol"
DB_PASS="1234"
DB_NAME="mangos"

TOKEN_FILE="${TOKEN_FILE:-/tmp/mangos-test-token}"
ID_TOKEN=""
if [ -f "$TOKEN_FILE" ]; then
    ID_TOKEN=$(cat "$TOKEN_FILE")
fi

TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0
FAILURES=()

last_body=""
last_status=""

req() {
    local method=$1 path=$2 body=${3:-}
    local out
    if [ -n "$body" ]; then
        out=$(curl -sS -w "\n%{http_code}" -X "$method" \
            -H "Authorization: Bearer ${ID_TOKEN:-}" \
            -H "Content-Type: application/json" \
            --data "$body" \
            "$BASE_URL$path")
    else
        out=$(curl -sS -w "\n%{http_code}" -X "$method" \
            -H "Authorization: Bearer ${ID_TOKEN:-}" \
            "$BASE_URL$path")
    fi
    last_status=$(printf '%s' "$out" | tail -n1)
    last_body=$(printf '%s' "$out" | sed '$d')
}

req_noauth() {
    local method=$1 path=$2
    local out
    out=$(curl -sS -w "\n%{http_code}" -X "$method" \
        -H "Content-Type: application/json" \
        "$BASE_URL$path")
    last_status=$(printf '%s' "$out" | tail -n1)
    last_body=$(printf '%s' "$out" | sed '$d')
}

assert_status() {
    local expected=$1 desc=$2
    TESTS_RUN=$((TESTS_RUN + 1))
    if [ "$last_status" = "$expected" ]; then
        TESTS_PASSED=$((TESTS_PASSED + 1))
        printf '  [ OK ] %s (HTTP %s)\n' "$desc" "$last_status"
    else
        TESTS_FAILED=$((TESTS_FAILED + 1))
        FAILURES+=("$desc -- expected $expected, got $last_status: $last_body")
        printf '  [FAIL] %s -- expected HTTP %s, got %s\n         body: %s\n' \
            "$desc" "$expected" "$last_status" "$last_body"
    fi
}

assert_json() {
    local field=$1 expected=$2 desc=$3
    local actual
    actual=$(printf '%s' "$last_body" | jq -r ".$field" 2>/dev/null || printf 'JQ_ERROR')
    TESTS_RUN=$((TESTS_RUN + 1))
    if [ "$actual" = "$expected" ]; then
        TESTS_PASSED=$((TESTS_PASSED + 1))
        printf '  [ OK ] %s\n' "$desc"
    else
        TESTS_FAILED=$((TESTS_FAILED + 1))
        FAILURES+=("$desc -- expected $field=$expected, got $actual")
        printf '  [FAIL] %s -- expected %s=%s, got %s\n' \
            "$desc" "$field" "$expected" "$actual"
    fi
}

json_field() {
    printf '%s' "$last_body" | jq -r ".$1"
}

mysql_exec() {
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -B -e "$1" 2>/dev/null
}

print_summary() {
    local script=${1:-test}
    if [ "$TESTS_FAILED" -eq 0 ]; then
        printf '\n%s: %d passed\n' "$script" "$TESTS_PASSED"
        return 0
    fi
    printf '\n%s: %d passed, %d FAILED\n' "$script" "$TESTS_PASSED" "$TESTS_FAILED"
    for f in "${FAILURES[@]}"; do
        printf '  - %s\n' "$f"
    done
    return 1
}
