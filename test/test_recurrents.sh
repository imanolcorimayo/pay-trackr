#!/usr/bin/env bash
. "$(dirname "$0")/lib.sh"

echo "== test_recurrents =="

# Create a card to FK against
req POST /api/cards '{"name":"Test Card","type":"credit"}'
card_id=$(json_field id)

# Required-fields validation
req POST /api/recurrents '{"title":"x","amount":100}'
assert_status 400 "POST rejects missing due_date_day"

# Create a recurrent
req POST /api/recurrents "$(cat <<EOF
{
  "title": "Netflix",
  "amount": 8000,
  "due_date_day": 5,
  "card_id": "$card_id"
}
EOF
)"
assert_status 201 "POST /api/recurrents"
rec_id=$(json_field id)

# Get back -> card_id stored, account_id auto-defaulted, currency defaulted to ARS
req GET "/api/recurrents?id=$rec_id"
assert_status 200 "GET single recurrent"
assert_json card_id "$card_id" "card_id stored on recurrent"
assert_json title "Netflix" "title stored"
assert_json currency "ARS" "currency defaults to ARS"
default_acct=$(printf '%s' "$last_body" | jq -r '.account_id')
TESTS_RUN=$((TESTS_RUN + 1))
if [ -n "$default_acct" ] && [ "$default_acct" != "null" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] account_id auto-defaulted on recurrent POST\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("account_id missing on default recurrent POST")
    printf '  [FAIL] account_id was null on default recurrent POST\n'
fi

# Recurrent with explicit USD account
req POST /api/accounts '{"name":"USD r","currency":"USD"}'
usd_a=$(json_field id)
req POST /api/recurrents "$(cat <<EOF
{
  "title": "Spotify USD",
  "amount": 11,
  "due_date_day": 1,
  "account_id": "$usd_a"
}
EOF
)"
assert_status 201 "POST recurrent with USD account"
usd_rec=$(json_field id)
req GET "/api/recurrents?id=$usd_rec"
assert_json currency "USD" "recurrent currency inherits account"
assert_json account_id "$usd_a" "recurrent account_id stored"
req DELETE "/api/recurrents?id=$usd_rec" >/dev/null
req DELETE "/api/accounts?id=$usd_a" >/dev/null

# Create a transaction instance referencing the recurrent
req POST /api/transactions "$(cat <<EOF
{
  "title": "Netflix April",
  "amount": 8000,
  "recurrent_id": "$rec_id",
  "transaction_type": "recurrent"
}
EOF
)"
assert_status 201 "POST transaction instance with recurrent_id"
inst_id=$(json_field id)

# Update recurrent (title only)
req PUT "/api/recurrents?id=$rec_id" '{"title":"Netflix Premium"}'
assert_status 200 "PUT recurrent title"

# Delete recurrent should cascade-delete the transaction instance
req DELETE "/api/recurrents?id=$rec_id"
assert_status 200 "DELETE recurrent"
assert_json instances_deleted 1 "1 instance was cascade-deleted"

# Transaction instance should be gone now
req GET "/api/transactions?id=$inst_id"
assert_status 404 "linked transaction instance deleted"

# Recurrent should be gone
req GET "/api/recurrents?id=$rec_id"
assert_status 404 "recurrent itself deleted"

# Cleanup card
req DELETE "/api/cards?id=$card_id" >/dev/null

print_summary "test_recurrents"
