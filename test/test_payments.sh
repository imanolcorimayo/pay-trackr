#!/usr/bin/env bash
. "$(dirname "$0")/lib.sh"

echo "== test_payments =="

# Need a category first
req POST /api/categories '{"name":"Test pmt cat","color":"#000000"}'
assert_status 201 "create supporting category"
cat_id=$(json_field id)

# Supporting card for card_id linkage
req POST /api/cards '{"name":"Test pmt card","type":"credit"}'
card_id=$(json_field id)

# Minimal payment (no category)
req POST /api/payments '{"title":"Coffee","amount":4500}'
assert_status 201 "POST /api/payments minimal"
pay_id=$(json_field id)

# Full payment with category + recipient
req POST /api/payments "$(cat <<EOF
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
assert_status 201 "POST /api/payments with recipient"
rent_id=$(json_field id)

# Get single returns nested recipient
req GET "/api/payments?id=$rent_id"
assert_status 200 "GET single payment"
assert_json recipient.name "Landlord" "recipient.name nested correctly"
assert_json expense_category_id "$cat_id" "expense_category_id stored"

# Required field validation
req POST /api/payments '{"amount":100}'
assert_status 400 "POST rejects missing title"

# Toggle is_paid via PUT
req PUT "/api/payments?id=$pay_id" '{"is_paid":true}'
assert_status 200 "PUT is_paid=true"
req GET "/api/payments?id=$pay_id"
assert_json is_paid 1 "is_paid persisted"

# Update title
req PUT "/api/payments?id=$pay_id" '{"title":"Coffee renamed"}'
assert_status 200 "PUT title"

# Filter by expense_category_id
req GET "/api/payments?expense_category_id=$cat_id"
assert_status 200 "GET filtered by expense_category_id"
count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$count" = "1" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] category filter returns 1 payment\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 1 by category, got $count")
    printf '  [FAIL] expected 1 by category, got %s\n' "$count"
fi

# card_id linkage: attach the card to the minimal payment
req PUT "/api/payments?id=$pay_id" "{\"card_id\":\"$card_id\"}"
assert_status 200 "PUT card_id"
req GET "/api/payments?id=$pay_id"
assert_json card_id "$card_id" "card_id stored on payment"

# Filter by card_id
req GET "/api/payments?card_id=$card_id"
assert_status 200 "GET filtered by card_id"
count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$count" = "1" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] card filter returns 1 payment\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 1 by card, got $count")
    printf '  [FAIL] expected 1 by card, got %s\n' "$count"
fi

# Detach card
req PUT "/api/payments?id=$pay_id" '{"card_id":null}'
assert_status 200 "PUT card_id=null"
req GET "/api/payments?id=$pay_id"
assert_json card_id null "card_id cleared"

# Replace recipient
req PUT "/api/payments?id=$rent_id" '{"recipient":{"name":"New Landlord","cbu":"1111","alias":"x","bank":"Y"}}'
assert_status 200 "PUT replaces recipient"
req GET "/api/payments?id=$rent_id"
assert_json recipient.name "New Landlord" "recipient replaced"

# Null recipient deletes it
req PUT "/api/payments?id=$rent_id" '{"recipient":null}'
assert_status 200 "PUT recipient=null"
req GET "/api/payments?id=$rent_id"
assert_json recipient null "recipient cleared"

# Delete
req DELETE "/api/payments?id=$pay_id"
assert_status 200 "DELETE /api/payments"
req DELETE "/api/payments?id=$rent_id"
assert_status 200 "DELETE second payment"

# 404 after delete
req GET "/api/payments?id=$pay_id"
assert_status 404 "GET deleted payment 404"

# Cleanup
req DELETE "/api/categories?id=$cat_id" >/dev/null
req DELETE "/api/cards?id=$card_id" >/dev/null

print_summary "test_payments"
