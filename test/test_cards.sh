#!/usr/bin/env bash
. "$(dirname "$0")/lib.sh"

echo "== test_cards =="

# Create credit card
req POST /api/cards '{"name":"Visa Galicia","type":"credit","bank":"Galicia","last_four":"1234","color":"#FF0000","closing_day":15,"due_day":25}'
assert_status 201 "POST /api/cards (credit)"
credit_id=$(json_field id)

# Create debit card
req POST /api/cards '{"name":"Debit","type":"debit"}'
assert_status 201 "POST /api/cards (debit)"

# Create virtual card
req POST /api/cards '{"name":"MP Virtual","type":"virtual"}'
assert_status 201 "POST /api/cards (virtual)"

# Reject invalid type
req POST /api/cards '{"name":"Bad","type":"prepaid"}'
assert_status 400 "POST /api/cards rejects unknown type"

# Reject missing required fields
req POST /api/cards '{"name":"NoType"}'
assert_status 400 "POST /api/cards rejects missing type"

# List has 3 cards
req GET /api/cards
assert_status 200 "GET /api/cards"
count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$count" = "3" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] list contains 3 cards\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 3 cards, got $count")
    printf '  [FAIL] expected 3 cards, got %s\n' "$count"
fi

# Get single
req GET "/api/cards?id=$credit_id"
assert_status 200 "GET /api/cards?id=..."
assert_json type credit "single card has type=credit"
assert_json closing_day 15 "single card has closing_day=15"

# Update
req PUT "/api/cards?id=$credit_id" '{"name":"Visa Galicia Renamed","closing_day":20}'
assert_status 200 "PUT /api/cards"

# Verify update
req GET "/api/cards?id=$credit_id"
assert_json name "Visa Galicia Renamed" "name updated"
assert_json closing_day 20 "closing_day updated"

# Reject invalid type on update
req PUT "/api/cards?id=$credit_id" '{"type":"prepaid"}'
assert_status 400 "PUT /api/cards rejects unknown type"

# Soft delete
req DELETE "/api/cards?id=$credit_id"
assert_status 200 "DELETE /api/cards"

# Get deleted card returns 404
req GET "/api/cards?id=$credit_id"
assert_status 404 "GET deleted card returns 404"

# List now has 2
req GET /api/cards
count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$count" = "2" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] list excludes soft-deleted card\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected 2 after delete, got $count")
    printf '  [FAIL] expected 2 cards after delete, got %s\n' "$count"
fi

# Delete the same card again -> 404 (already soft-deleted)
req DELETE "/api/cards?id=$credit_id"
assert_status 404 "DELETE already-deleted card returns 404"

print_summary "test_cards"
