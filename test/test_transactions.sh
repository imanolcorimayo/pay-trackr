#!/usr/bin/env bash
. "$(dirname "$0")/lib.sh"

echo "== test_transactions =="

# Need a category first
req POST /api/categories '{"name":"Test tx cat","color":"#000000"}'
assert_status 201 "create supporting category"
cat_id=$(json_field id)

# Supporting card for card_id linkage
req POST /api/cards '{"name":"Test tx card","type":"credit"}'
card_id=$(json_field id)

# Minimal transaction (no category, no account → fall back to default)
req POST /api/transactions '{"title":"Coffee","amount":4500}'
assert_status 201 "POST /api/transactions minimal"
tx_id=$(json_field id)

# amount stored as signed (negative for expense)
req GET "/api/transactions?id=$tx_id"
assert_json amount "-4500.00" "amount stored signed (negative)"
# Defaults: should land on the seeded "Sin cuenta" with currency=ARS
default_acct=$(printf '%s' "$last_body" | jq -r '.account_id')
TESTS_RUN=$((TESTS_RUN + 1))
if [ -n "$default_acct" ] && [ "$default_acct" != "null" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] account_id auto-defaulted on POST\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("account_id missing on default POST")
    printf '  [FAIL] account_id was null on default POST\n'
fi
assert_json currency "ARS" "currency defaults to ARS"

# Currency override on POST
req POST /api/accounts '{"name":"USD acct","type":"bank","currency":"USD"}'
usd_acct=$(json_field id)
req POST /api/transactions "{\"title\":\"Hotel\",\"amount\":120,\"account_id\":\"$usd_acct\"}"
assert_status 201 "POST tx attached to USD account"
hotel_id=$(json_field id)
req GET "/api/transactions?id=$hotel_id"
assert_json currency "USD" "currency inherited from account"
assert_json account_id "$usd_acct" "account_id stored"

# Explicit currency overrides account currency
req POST /api/transactions "{\"title\":\"Mixed\",\"amount\":500,\"account_id\":\"$usd_acct\",\"currency\":\"USDT\"}"
assert_status 201 "POST tx with explicit currency override"
mixed_id=$(json_field id)
req GET "/api/transactions?id=$mixed_id"
assert_json currency "USDT" "explicit currency overrides account default"

# Reject unknown account_id
req POST /api/transactions '{"title":"Bad","amount":100,"account_id":"does-not-exist"}'
assert_status 400 "POST rejects unknown account_id"

# Reject unknown currency
req POST /api/transactions '{"title":"Bad","amount":100,"currency":"GBP"}'
assert_status 400 "POST rejects unknown currency"

# Filter by account_id
req GET "/api/transactions?account_id=$usd_acct"
assert_status 200 "GET filtered by account_id"
acct_count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$acct_count" = "2" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] account filter returns 2 txs\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 2 txs by account, got $acct_count")
    printf '  [FAIL] expected 2 txs by account, got %s\n' "$acct_count"
fi

# Filter by currency
req GET "/api/transactions?currency=USDT"
assert_status 200 "GET filtered by currency=USDT"
cur_count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$cur_count" = "1" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] currency filter returns 1 tx\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 1 tx by currency, got $cur_count")
    printf '  [FAIL] expected 1 tx by currency, got %s\n' "$cur_count"
fi

# Cleanup the extras
req DELETE "/api/transactions?id=$hotel_id" >/dev/null
req DELETE "/api/transactions?id=$mixed_id" >/dev/null
req DELETE "/api/accounts?id=$usd_acct" >/dev/null

# Full transaction with category + recipient
req POST /api/transactions "$(cat <<EOF
{
  "title": "Rent",
  "amount": 250000,
  "expense_category_id": "$cat_id",
  "due_ts": "2026-05-01 12:00:00",
  "recipient": {
    "name": "Landlord",
    "cbu": "0000000000000000000000",
    "alias": "landlord.alias",
    "bank": "Galicia"
  }
}
EOF
)"
assert_status 201 "POST /api/transactions with recipient"
rent_id=$(json_field id)

# Get single returns nested recipient
req GET "/api/transactions?id=$rent_id"
assert_status 200 "GET single transaction"
assert_json recipient.name "Landlord" "recipient.name nested correctly"
assert_json expense_category_id "$cat_id" "expense_category_id stored"

# Required field validation
req POST /api/transactions '{"amount":100}'
assert_status 400 "POST rejects missing title"

# Toggle is_paid via PUT
req PUT "/api/transactions?id=$tx_id" '{"is_paid":true}'
assert_status 200 "PUT is_paid=true"
req GET "/api/transactions?id=$tx_id"
assert_json is_paid 1 "is_paid persisted"

# Update title
req PUT "/api/transactions?id=$tx_id" '{"title":"Coffee renamed"}'
assert_status 200 "PUT title"

# Update amount (positive input → stored negative)
req PUT "/api/transactions?id=$tx_id" '{"amount":5500}'
assert_status 200 "PUT amount"
req GET "/api/transactions?id=$tx_id"
assert_json amount "-5500.00" "amount re-normalized to negative on PUT"

# Filter by expense_category_id
req GET "/api/transactions?expense_category_id=$cat_id"
assert_status 200 "GET filtered by expense_category_id"
count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$count" = "1" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] category filter returns 1 transaction\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 1 by category, got $count")
    printf '  [FAIL] expected 1 by category, got %s\n' "$count"
fi

# card_id linkage: attach the card to the minimal transaction
req PUT "/api/transactions?id=$tx_id" "{\"card_id\":\"$card_id\"}"
assert_status 200 "PUT card_id"
req GET "/api/transactions?id=$tx_id"
assert_json card_id "$card_id" "card_id stored on transaction"

# Filter by card_id
req GET "/api/transactions?card_id=$card_id"
assert_status 200 "GET filtered by card_id"
count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$count" = "1" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] card filter returns 1 transaction\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 1 by card, got $count")
    printf '  [FAIL] expected 1 by card, got %s\n' "$count"
fi

# Detach card
req PUT "/api/transactions?id=$tx_id" '{"card_id":null}'
assert_status 200 "PUT card_id=null"
req GET "/api/transactions?id=$tx_id"
assert_json card_id null "card_id cleared"

# Replace recipient
req PUT "/api/transactions?id=$rent_id" '{"recipient":{"name":"New Landlord","cbu":"1111","alias":"x","bank":"Y"}}'
assert_status 200 "PUT replaces recipient"
req GET "/api/transactions?id=$rent_id"
assert_json recipient.name "New Landlord" "recipient replaced"

# Null recipient deletes it
req PUT "/api/transactions?id=$rent_id" '{"recipient":null}'
assert_status 200 "PUT recipient=null"
req GET "/api/transactions?id=$rent_id"
assert_json recipient null "recipient cleared"

# Delete
req DELETE "/api/transactions?id=$tx_id"
assert_status 200 "DELETE /api/transactions"
req DELETE "/api/transactions?id=$rent_id"
assert_status 200 "DELETE second transaction"

# 404 after delete
req GET "/api/transactions?id=$tx_id"
assert_status 404 "GET deleted transaction 404"

# Cleanup
req DELETE "/api/categories?id=$cat_id" >/dev/null
req DELETE "/api/cards?id=$card_id" >/dev/null

print_summary "test_transactions"
