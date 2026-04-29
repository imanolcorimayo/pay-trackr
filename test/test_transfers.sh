#!/usr/bin/env bash
. "$(dirname "$0")/lib.sh"

echo "== test_transfers =="

# We need two accounts. The seeded one is ARS; create a USD wallet.
req POST /api/accounts '{"name":"USD Wallet","type":"bank","currency":"USD","opening_balance":1000,"opening_balance_date":"2026-04-01"}'
assert_status 201 "POST USD account"
usd_id=$(json_field id)

req POST /api/accounts '{"name":"ARS Bank","type":"bank","currency":"ARS","opening_balance":50000,"opening_balance_date":"2026-04-01"}'
assert_status 201 "POST ARS account"
ars_id=$(json_field id)

# ── Transfer without fee ─────────────────────────────────────────────────────

req POST /api/transfers "{\"from_account_id\":\"$usd_id\",\"to_account_id\":\"$ars_id\",\"amount_sent\":100,\"amount_received\":85000}"
assert_status 201 "POST transfer (no fee)"
tid1=$(json_field transfer_id)

# Should have 2 legs
legs1=$(printf '%s' "$last_body" | jq '.legs | length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$legs1" = "2" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] transfer without fee has 2 legs\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 2 legs, got $legs1")
    printf '  [FAIL] expected 2 legs, got %s\n' "$legs1"
fi

# Outflow: -100 USD on USD account
from_amount=$(printf '%s' "$last_body" | jq -r '.from_leg.amount')
from_currency=$(printf '%s' "$last_body" | jq -r '.from_leg.currency')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$from_amount" = "-100.00" ] && [ "$from_currency" = "USD" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] from_leg = -100 USD\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("from_leg amount=$from_amount currency=$from_currency")
    printf '  [FAIL] from_leg amount=%s currency=%s\n' "$from_amount" "$from_currency"
fi

# Inflow: +85000 ARS on ARS account
to_amount=$(printf '%s' "$last_body" | jq -r '.to_leg.amount')
to_currency=$(printf '%s' "$last_body" | jq -r '.to_leg.currency')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$to_amount" = "85000.00" ] && [ "$to_currency" = "ARS" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] to_leg = +85000 ARS\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("to_leg amount=$to_amount currency=$to_currency")
    printf '  [FAIL] to_leg amount=%s currency=%s\n' "$to_amount" "$to_currency"
fi

# Both legs share the transfer_id
shared=$(mysql_exec "SELECT COUNT(*) FROM \`transaction\` WHERE transfer_id='$tid1';")
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$shared" = "2" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] both legs share transfer_id in DB\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 2 rows with transfer_id=$tid1, got $shared")
    printf '  [FAIL] expected 2 rows in DB, got %s\n' "$shared"
fi

# Account balances reflect the transfer
req GET "/api/accounts?id=$usd_id"
# 1000 - 100 = 900
assert_json current_balance "900" "USD balance after outflow"

req GET "/api/accounts?id=$ars_id"
# 50000 + 85000 = 135000
assert_json current_balance "135000" "ARS balance after inflow"

# ── Transfer with fee ────────────────────────────────────────────────────────

req POST /api/transfers "{\"from_account_id\":\"$usd_id\",\"to_account_id\":\"$ars_id\",\"amount_sent\":50,\"amount_received\":42000,\"fee\":2}"
assert_status 201 "POST transfer with fee"
tid2=$(json_field transfer_id)

legs2=$(printf '%s' "$last_body" | jq '.legs | length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$legs2" = "3" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] transfer with fee has 3 legs\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 3 legs, got $legs2")
    printf '  [FAIL] expected 3 legs, got %s\n' "$legs2"
fi

# Fee leg: kind='fee', amount=-2, on USD account
fee_kind=$(printf '%s' "$last_body" | jq -r '.fee_leg.kind')
fee_amount=$(printf '%s' "$last_body" | jq -r '.fee_leg.amount')
fee_currency=$(printf '%s' "$last_body" | jq -r '.fee_leg.currency')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$fee_kind" = "fee" ] && [ "$fee_amount" = "-2.00" ] && [ "$fee_currency" = "USD" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] fee_leg = kind=fee, -2 USD\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("fee leg kind=$fee_kind amount=$fee_amount currency=$fee_currency")
    printf '  [FAIL] fee leg kind=%s amount=%s currency=%s\n' "$fee_kind" "$fee_amount" "$fee_currency"
fi

# USD balance: 900 - 50 - 2 = 848
req GET "/api/accounts?id=$usd_id"
assert_json current_balance "848" "USD balance after transfer with fee"

# ── Validation ───────────────────────────────────────────────────────────────

req POST /api/transfers "{\"from_account_id\":\"$usd_id\",\"to_account_id\":\"$usd_id\",\"amount_sent\":10,\"amount_received\":10}"
assert_status 400 "POST rejects same from/to account"

req POST /api/transfers "{\"from_account_id\":\"$usd_id\",\"to_account_id\":\"$ars_id\",\"amount_sent\":-5,\"amount_received\":1000}"
assert_status 400 "POST rejects negative amount"

req POST /api/transfers "{\"from_account_id\":\"$usd_id\",\"to_account_id\":\"$ars_id\",\"amount_sent\":10,\"amount_received\":1000,\"fee\":-1}"
assert_status 400 "POST rejects negative fee"

req POST /api/transfers "{\"from_account_id\":\"nonexistent-id-xxxxxxxxxxxxxxxx\",\"to_account_id\":\"$ars_id\",\"amount_sent\":10,\"amount_received\":1000}"
assert_status 404 "POST rejects foreign/missing account"

# ── DELETE single tx that belongs to a transfer ──────────────────────────────

leg_id=$(mysql_exec "SELECT id FROM \`transaction\` WHERE transfer_id='$tid1' LIMIT 1;")
req DELETE "/api/transactions?id=$leg_id"
assert_status 409 "DELETE on transfer leg via /transactions is rejected"

# ── PUT (delete-and-recreate) ────────────────────────────────────────────────

req PUT "/api/transfers?id=$tid2" "{\"from_account_id\":\"$usd_id\",\"to_account_id\":\"$ars_id\",\"amount_sent\":75,\"amount_received\":63000}"
assert_status 200 "PUT replaces transfer legs"

# After PUT: should be 2 legs (no fee), same transfer_id
new_legs=$(mysql_exec "SELECT COUNT(*) FROM \`transaction\` WHERE transfer_id='$tid2';")
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$new_legs" = "2" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] PUT resulted in 2 legs (was 3)\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 2 legs after PUT, got $new_legs")
    printf '  [FAIL] expected 2 legs after PUT, got %s\n' "$new_legs"
fi

# ── DELETE transfer ──────────────────────────────────────────────────────────

req DELETE "/api/transfers?id=$tid1"
assert_status 200 "DELETE transfer (no fee)"

remaining=$(mysql_exec "SELECT COUNT(*) FROM \`transaction\` WHERE transfer_id='$tid1';")
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$remaining" = "0" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] all legs gone after DELETE\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 0 rows after DELETE, got $remaining")
    printf '  [FAIL] expected 0 rows after DELETE, got %s\n' "$remaining"
fi

req DELETE "/api/transfers?id=nonexistent-xxxxxxxxxxxxxxxxxxxx"
assert_status 404 "DELETE non-existent transfer 404"

# Cleanup
req DELETE "/api/transfers?id=$tid2" >/dev/null
req DELETE "/api/accounts?id=$usd_id" >/dev/null
req DELETE "/api/accounts?id=$ars_id" >/dev/null

print_summary "test_transfers"
