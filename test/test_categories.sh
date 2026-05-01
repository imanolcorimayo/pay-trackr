#!/usr/bin/env bash
. "$(dirname "$0")/lib.sh"

echo "== test_categories =="

# Create
req POST /api/categories '{"name":"Test Category","color":"#123456"}'
assert_status 201 "POST /api/categories"
cat_id=$(json_field id)

# List should now contain it
req GET /api/categories
assert_status 200 "GET /api/categories"
found=$(printf '%s' "$last_body" | jq -r --arg id "$cat_id" '.[] | select(.id==$id) | .name')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$found" = "Test Category" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] new category appears in list\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("category $cat_id not in list (got '$found')")
    printf '  [FAIL] new category not in list\n'
fi

# Update
req PUT "/api/categories?id=$cat_id" '{"name":"Renamed Category","color":"#abcdef"}'
assert_status 200 "PUT /api/categories"
assert_json updated true "PUT response has updated:true"

# Verify update via list
req GET /api/categories
new_name=$(printf '%s' "$last_body" | jq -r --arg id "$cat_id" '.[] | select(.id==$id) | .name')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$new_name" = "Renamed Category" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] category name updated\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("category name not updated (got '$new_name')")
    printf '  [FAIL] category name not updated\n'
fi

# Soft delete
req DELETE "/api/categories?id=$cat_id"
assert_status 200 "DELETE /api/categories"

# Should no longer appear
req GET /api/categories
gone=$(printf '%s' "$last_body" | jq -r --arg id "$cat_id" '[.[] | select(.id==$id)] | length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$gone" = "0" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] deleted category hidden from list\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("deleted category still in list")
    printf '  [FAIL] deleted category still in list\n'
fi

# Validation: missing fields
req POST /api/categories '{"name":"Only name"}'
assert_status 400 "POST without color rejected"

# ── Income categories ────────────────────────────────────────────
# Default income categories were seeded for the test user.
req GET '/api/categories?kind=income'
assert_status 200 "GET income categories"
income_count=$(printf '%s' "$last_body" | jq 'length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$income_count" -ge 7 ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] default income categories seeded (count=%s)\n' "$income_count"
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("expected ≥7 default income categories, got $income_count")
    printf '  [FAIL] default income categories missing (count=%s)\n' "$income_count"
fi

# Income POST → goes to income_category, not expense_category
req POST '/api/categories?kind=income' '{"name":"Bonus","color":"#00FF00"}'
assert_status 201 "POST income category"
inc_id=$(json_field id)

# Should appear on income list
req GET '/api/categories?kind=income'
inc_found=$(printf '%s' "$last_body" | jq -r --arg id "$inc_id" '.[] | select(.id==$id) | .name')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$inc_found" = "Bonus" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] new income category appears on income list\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("income category $inc_id not in income list (got '$inc_found')")
    printf '  [FAIL] new income category not in income list\n'
fi

# Should NOT appear on expense list
req GET '/api/categories'
inc_leaked=$(printf '%s' "$last_body" | jq -r --arg id "$inc_id" '[.[] | select(.id==$id)] | length')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$inc_leaked" = "0" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] income category isolated from expense list\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("income category leaked into expense list")
    printf '  [FAIL] income category leaked into expense list\n'
fi

# Soft delete on income kind
req DELETE "/api/categories?id=$inc_id&kind=income"
assert_status 200 "DELETE income category"

# Invalid kind rejected
req GET '/api/categories?kind=invalid'
assert_status 400 "invalid kind rejected"

print_summary "test_categories"
