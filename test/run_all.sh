#!/usr/bin/env bash
# Orchestrator: setup -> all tests -> teardown.
# Continues on test failure to surface every problem in one run.
# Exits non-zero if any test failed.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Preflight: jq + mysql + node available
for cmd in jq curl mysql node; do
    if ! command -v "$cmd" >/dev/null; then
        echo "[run_all] missing required command: $cmd"
        exit 2
    fi
done

bash "$SCRIPT_DIR/setup.sh"

tests=(
    test_schema.sh
    test_auth.sh
    test_categories.sh
    test_cards.sh
    test_transactions.sh
    test_recurrents.sh
    test_templates.sh
)

failed=0
for t in "${tests[@]}"; do
    echo
    if ! bash "$SCRIPT_DIR/$t"; then
        failed=$((failed + 1))
    fi
done

echo
bash "$SCRIPT_DIR/teardown.sh"

echo
if [ "$failed" -eq 0 ]; then
    echo "==================================="
    echo "  ALL SUITES PASSED"
    echo "==================================="
    exit 0
else
    echo "==================================="
    echo "  $failed SUITE(S) FAILED"
    echo "==================================="
    exit 1
fi
