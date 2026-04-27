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

# Get back -> card_id stored
req GET "/api/recurrents?id=$rec_id"
assert_status 200 "GET single recurrent"
assert_json card_id "$card_id" "card_id stored on recurrent"
assert_json title "Netflix" "title stored"

# Create a payment instance referencing the recurrent
req POST /api/payments "$(cat <<EOF
{
  "title": "Netflix April",
  "amount": 8000,
  "recurrent_id": "$rec_id",
  "payment_type": "recurrent"
}
EOF
)"
assert_status 201 "POST payment instance with recurrent_id"
inst_id=$(json_field id)

# Update recurrent (title only)
req PUT "/api/recurrents?id=$rec_id" '{"title":"Netflix Premium"}'
assert_status 200 "PUT recurrent title"

# Delete recurrent should cascade-delete the payment instance
req DELETE "/api/recurrents?id=$rec_id"
assert_status 200 "DELETE recurrent"
assert_json instances_deleted 1 "1 instance was cascade-deleted"

# Payment instance should be gone now
req GET "/api/payments?id=$inst_id"
assert_status 404 "linked payment instance deleted"

# Recurrent should be gone
req GET "/api/recurrents?id=$rec_id"
assert_status 404 "recurrent itself deleted"

# Cleanup card
req DELETE "/api/cards?id=$card_id" >/dev/null

print_summary "test_recurrents"
