#!/usr/bin/env bash
. "$(dirname "$0")/lib.sh"

echo "== test_accounts =="

# Test user starts with the seeded "Sin cuenta" default account from fixtures.sql.
req GET /api/accounts
assert_status 200 "GET /api/accounts"
seed_count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$seed_count" = "1" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] starts with 1 seeded account\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 1 seeded account, got $seed_count")
    printf '  [FAIL] expected 1 seeded account, got %s\n' "$seed_count"
fi

# Create USD bank account, mark as default → should demote the existing default.
req POST /api/accounts '{"name":"USD Wallet","type":"bank","currency":"USD","is_default":1}'
assert_status 201 "POST /api/accounts (USD, default)"
usd_id=$(json_field id)

# Create another, ARS, not default
req POST /api/accounts '{"name":"Cash","type":"cash","currency":"ARS"}'
assert_status 201 "POST /api/accounts (cash, ARS)"
cash_id=$(json_field id)

# Reject invalid currency
req POST /api/accounts '{"name":"Bad","currency":"GBP"}'
assert_status 400 "POST rejects unknown currency"

# Reject invalid type
req POST /api/accounts '{"name":"Bad","type":"savings"}'
assert_status 400 "POST rejects unknown type"

# Reject missing name
req POST /api/accounts '{"type":"bank"}'
assert_status 400 "POST rejects missing name"

# List should now have 3
req GET /api/accounts
count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$count" = "3" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] list has 3 accounts\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 3 accounts, got $count")
    printf '  [FAIL] expected 3 accounts, got %s\n' "$count"
fi

# Only one is_default
default_count=$(printf '%s' "$last_body" | jq '[.[] | select(.is_default == 1)] | length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$default_count" = "1" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] only one is_default account\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 1 default account, got $default_count")
    printf '  [FAIL] expected 1 default account, got %s\n' "$default_count"
fi

# The new USD account should be the default
req GET "/api/accounts?id=$usd_id"
assert_status 200 "GET single account"
assert_json is_default 1 "USD account is is_default"
assert_json currency "USD" "currency=USD persisted"

# Update: promote the cash account; USD should be demoted
req PUT "/api/accounts?id=$cash_id" '{"is_default":1}'
assert_status 200 "PUT promote cash to default"

req GET "/api/accounts?id=$usd_id"
assert_json is_default 0 "USD account demoted to non-default"
req GET "/api/accounts?id=$cash_id"
assert_json is_default 1 "cash account is now default"

# Update name + currency
req PUT "/api/accounts?id=$usd_id" '{"name":"USD Renamed","currency":"USDT"}'
assert_status 200 "PUT update name + currency"
req GET "/api/accounts?id=$usd_id"
assert_json name "USD Renamed" "name updated"
assert_json currency "USDT" "currency updated"

# Reject invalid currency on update
req PUT "/api/accounts?id=$usd_id" '{"currency":"BTC"}'
assert_status 400 "PUT rejects unknown currency"

# Soft delete
req DELETE "/api/accounts?id=$usd_id"
assert_status 200 "DELETE account"
req GET "/api/accounts?id=$usd_id"
assert_status 404 "GET deleted account 404"

# List now has 2
req GET /api/accounts
count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$count" = "2" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] list excludes soft-deleted account\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 2 after delete, got $count")
    printf '  [FAIL] expected 2 accounts after delete, got %s\n' "$count"
fi

# Opening balance behavior
req POST /api/accounts '{"name":"Bal acct","type":"bank","currency":"ARS","opening_balance":100000,"opening_balance_date":"2026-04-01"}'
assert_status 201 "POST account with opening balance"
bal_acct=$(json_field id)
req GET "/api/accounts?id=$bal_acct"
assert_json opening_balance "100000.00" "opening_balance stored"
assert_json opening_balance_date "2026-04-01" "opening_balance_date stored"
assert_json current_balance "100000" "current_balance equals opening when no movements"

# Add a paid expense after the opening date — balance should drop.
req POST /api/transactions "{\"title\":\"Test exp\",\"amount\":15000,\"account_id\":\"$bal_acct\",\"is_paid\":true}"
bal_tx=$(json_field id)
req GET "/api/accounts?id=$bal_acct"
assert_json current_balance "85000" "current_balance after one paid expense"

# Unpaid expense should NOT affect balance.
req POST /api/transactions "{\"title\":\"Pending\",\"amount\":50000,\"account_id\":\"$bal_acct\",\"is_paid\":false}"
pending_tx=$(json_field id)
req GET "/api/accounts?id=$bal_acct"
assert_json current_balance "85000" "unpaid expense does not affect balance"

# Reject malformed date
req PUT "/api/accounts?id=$bal_acct" '{"opening_balance_date":"not-a-date"}'
assert_status 400 "PUT rejects malformed opening_balance_date"

# Cleanup
req DELETE "/api/transactions?id=$bal_tx" >/dev/null
req DELETE "/api/transactions?id=$pending_tx" >/dev/null
req DELETE "/api/accounts?id=$bal_acct" >/dev/null

# Refuse to delete the last account (delete cash, then try to delete the original seeded "Sin cuenta")
req DELETE "/api/accounts?id=$cash_id"
assert_status 200 "DELETE cash account"

# Now only the original seeded one remains — DELETE must refuse
seed=$(printf '%s' "$(curl -sS -X GET -H "Authorization: Bearer ${ID_TOKEN:-}" "$BASE_URL/api/accounts")" | jq -r '.[0].id')
req DELETE "/api/accounts?id=$seed"
assert_status 400 "DELETE last remaining account refused"

print_summary "test_accounts"
