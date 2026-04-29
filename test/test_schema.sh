#!/usr/bin/env bash
. "$(dirname "$0")/lib.sh"

echo "== test_schema =="

# Expected tables (singular, transaction-domain naming)
expected="card default_category expense_category fcm_token migrations recurrent recurrent_alias transaction transaction_recipient transaction_template user weekly_summary"
actual=$(mysql_exec "SHOW TABLES;" | sort | tr '\n' ' ' | sed 's/ $//')
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$actual" = "$expected" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] all expected tables present\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("table set mismatch -- expected: $expected actual: $actual")
    printf '  [FAIL] table set mismatch\n         expected: %s\n         actual:   %s\n' "$expected" "$actual"
fi

# No legacy plural tables and no payment-domain tables
for legacy in users expense_categories recurrents payments payment_recipients payment_templates fcm_tokens weekly_summaries default_categories payment payment_recipient payment_template; do
    found=$(mysql_exec "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='$legacy';")
    TESTS_RUN=$((TESTS_RUN + 1))
    if [ "$found" = "0" ]; then
        TESTS_PASSED=$((TESTS_PASSED + 1))
        printf '  [ OK ] legacy table %s does not exist\n' "$legacy"
    else
        TESTS_FAILED=$((TESTS_FAILED + 1))
        FAILURES+=("legacy table $legacy still exists")
        printf '  [FAIL] legacy table %s still exists\n' "$legacy"
    fi
done

# card table has the expected columns
card_cols=$(mysql_exec "SELECT GROUP_CONCAT(column_name ORDER BY ordinal_position) FROM information_schema.columns WHERE table_schema='$DB_NAME' AND table_name='card';")
expected_card_cols="id,user_id,name,type,bank,last_four,color,closing_day,due_day,deleted_ts,created_ts,updated_ts"
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$card_cols" = "$expected_card_cols" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] card columns match expected schema\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("card columns mismatch -- expected: $expected_card_cols  actual: $card_cols")
    printf '  [FAIL] card columns mismatch\n         expected: %s\n         actual:   %s\n' "$expected_card_cols" "$card_cols"
fi

# Named FKs present (constraint names are preserved across RENAME TABLE)
for fk_name in fk_recurrent_card fk_payment_card; do
    fk=$(mysql_exec "SELECT constraint_name FROM information_schema.referential_constraints WHERE constraint_schema='$DB_NAME' AND constraint_name='$fk_name';")
    TESTS_RUN=$((TESTS_RUN + 1))
    if [ "$fk" = "$fk_name" ]; then
        TESTS_PASSED=$((TESTS_PASSED + 1))
        printf '  [ OK ] FK %s present\n' "$fk_name"
    else
        TESTS_FAILED=$((TESTS_FAILED + 1))
        FAILURES+=("FK $fk_name missing")
        printf '  [FAIL] FK %s missing\n' "$fk_name"
    fi
done

# transaction.card_id column exists
found=$(mysql_exec "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='$DB_NAME' AND table_name='transaction' AND column_name='card_id';")
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$found" = "1" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] transaction.card_id column present\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("transaction.card_id column missing")
    printf '  [FAIL] transaction.card_id column missing\n'
fi

# transaction.transaction_type column exists (renamed from payment_type in 016)
found=$(mysql_exec "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='$DB_NAME' AND table_name='transaction' AND column_name='transaction_type';")
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$found" = "1" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] transaction.transaction_type column present\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("transaction.transaction_type column missing")
    printf '  [FAIL] transaction.transaction_type column missing\n'
fi

# transaction_recipient.transaction_id column exists (renamed from payment_id)
found=$(mysql_exec "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='$DB_NAME' AND table_name='transaction_recipient' AND column_name='transaction_id';")
TESTS_RUN=$((TESTS_RUN + 1))
if [ "$found" = "1" ]; then
    TESTS_PASSED=$((TESTS_PASSED + 1))
    printf '  [ OK ] transaction_recipient.transaction_id column present\n'
else
    TESTS_FAILED=$((TESTS_FAILED + 1))
    FAILURES+=("transaction_recipient.transaction_id column missing")
    printf '  [FAIL] transaction_recipient.transaction_id column missing\n'
fi

# No legacy columns: category_id / credit_card_id / is_credit_card / payment_type / payment_id
for legacy in "recurrent.category_id" "recurrent.credit_card_id" "recurrent.is_credit_card" "transaction.category_id" "transaction_template.category_id" "transaction.payment_type" "transaction_recipient.payment_id"; do
    table=${legacy%.*}
    column=${legacy#*.}
    found=$(mysql_exec "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='$DB_NAME' AND table_name='$table' AND column_name='$column';")
    TESTS_RUN=$((TESTS_RUN + 1))
    if [ "$found" = "0" ]; then
        TESTS_PASSED=$((TESTS_PASSED + 1))
        printf '  [ OK ] legacy column %s does not exist\n' "$legacy"
    else
        TESTS_FAILED=$((TESTS_FAILED + 1))
        FAILURES+=("legacy column $legacy still exists")
        printf '  [FAIL] legacy column %s still exists\n' "$legacy"
    fi
done

print_summary "test_schema"
