#!/usr/bin/env bash
. "$(dirname "$0")/lib.sh"

echo "== test_auth =="

# 1. No token -> 401
req_noauth GET /api/categories
assert_status 401 "GET /api/categories without Authorization header rejected"

# 2. Junk token -> 401
saved="$ID_TOKEN"
ID_TOKEN="junk.token.value"
req GET /api/categories
assert_status 401 "GET /api/categories with invalid token rejected"
ID_TOKEN="$saved"

# 3. Valid token -> 200
req GET /api/categories
assert_status 200 "GET /api/categories with valid token accepted"

# 4. Unknown route -> 404 (after auth passes)
req GET /api/nonsense-endpoint
assert_status 404 "unknown route returns 404"

print_summary "test_auth"
