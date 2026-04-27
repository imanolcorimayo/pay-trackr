#!/usr/bin/env bash
. "$(dirname "$0")/lib.sh"

echo "== test_templates =="

# Create supporting category
req POST /api/categories '{"name":"Tpl cat","color":"#222222"}'
cat_id=$(json_field id)

# Create
req POST /api/templates "$(cat <<EOF
{
  "name": "Quick coffee",
  "description": "Default coffee shop charge",
  "expense_category_id": "$cat_id"
}
EOF
)"
assert_status 201 "POST /api/templates"
tpl_id=$(json_field id)

# Validation
req POST /api/templates '{}'
assert_status 400 "POST rejects missing name"

# Increment usage 3 times
for i in 1 2 3; do
    req PUT "/api/templates?id=$tpl_id" '{"increment_usage":true}'
    assert_status 200 "increment_usage call $i"
done

# Verify usage_count = 3
req GET /api/templates
usage=$(printf '%s' "$last_body" | jq -r --arg id "$tpl_id" '.[] | select(.id==$id) | .usage_count')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$usage" = "3" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] usage_count=3 after 3 increments\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("usage_count expected 3, got $usage")
    printf '  [FAIL] usage_count expected 3, got %s\n' "$usage"
fi

# Update name
req PUT "/api/templates?id=$tpl_id" '{"name":"Quick coffee renamed"}'
assert_status 200 "PUT update name"

# Delete
req DELETE "/api/templates?id=$tpl_id"
assert_status 200 "DELETE /api/templates"

# Cleanup
req DELETE "/api/categories?id=$cat_id" >/dev/null

print_summary "test_templates"
