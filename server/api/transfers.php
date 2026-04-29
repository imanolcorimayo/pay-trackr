<?php
// $pdo and $user_id provided by index.php
//
// Transfers between accounts. A transfer is 2 (or 3 with a fee) `transaction`
// rows sharing a transfer_id. We do not store an exchange rate — each leg has
// its own truthful amount/currency, and that is the source of truth for the
// account it belongs to.
//
// Edits are delete-and-recreate: simpler than tracking partial updates across
// 2-3 rows, and matches how the user thinks about a transfer (one event).

switch (method()) {
    case 'POST':
        $data = get_json_body();
        $tid = create_transfer($pdo, $user_id, $data);
        json_response(get_transfer($pdo, $user_id, $tid), 201);

    case 'GET':
        $tid = $_GET['id'] ?? '';
        if (empty($tid)) json_error('id is required');
        $payload = get_transfer($pdo, $user_id, $tid);
        if ($payload === null) json_error('Transfer not found', 404);
        json_response($payload);

    case 'PUT':
        $tid = $_GET['id'] ?? '';
        if (empty($tid)) json_error('id is required');
        $data = get_json_body();

        $pdo->beginTransaction();
        try {
            $deleted = delete_transfer_legs($pdo, $user_id, $tid);
            if ($deleted === 0) {
                $pdo->rollBack();
                json_error('Transfer not found', 404);
            }
            $new_tid = create_transfer($pdo, $user_id, $data, $tid);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        json_response(get_transfer($pdo, $user_id, $new_tid));

    case 'DELETE':
        $tid = $_GET['id'] ?? '';
        if (empty($tid)) json_error('id is required');

        $deleted = delete_transfer_legs($pdo, $user_id, $tid);
        if ($deleted === 0) json_error('Transfer not found', 404);
        json_response(['deleted' => true, 'legs' => $deleted]);

    default:
        json_error('Method not allowed', 405);
}

/**
 * Insert 2 or 3 linked rows. Returns the transfer_id. Wraps in a transaction
 * unless we're already inside one (PUT path opens its own).
 *
 * $force_id lets PUT keep the same transfer_id across delete-recreate so
 * external references (links, bookmarks) survive an edit.
 */
function create_transfer(PDO $pdo, string $user_id, array $data, ?string $force_id = null): string {
    $from_id = $data['from_account_id'] ?? '';
    $to_id   = $data['to_account_id']   ?? '';
    if (empty($from_id) || empty($to_id)) {
        json_error('from_account_id and to_account_id are required');
    }
    if ($from_id === $to_id) {
        json_error('from_account_id and to_account_id must be different');
    }

    $amount_sent     = isset($data['amount_sent'])     ? (float)$data['amount_sent']     : 0;
    $amount_received = isset($data['amount_received']) ? (float)$data['amount_received'] : 0;
    if ($amount_sent <= 0 || $amount_received <= 0) {
        json_error('amount_sent and amount_received must be positive');
    }

    $fee = isset($data['fee']) && $data['fee'] !== '' ? (float)$data['fee'] : 0;
    if ($fee < 0) json_error('fee must be >= 0');

    // Load both accounts in one round-trip; verify ownership.
    $stmt = $pdo->prepare(
        "SELECT id, name, currency FROM account
         WHERE user_id = ? AND id IN (?, ?) AND deleted_ts IS NULL"
    );
    $stmt->execute([$user_id, $from_id, $to_id]);
    $accounts = [];
    foreach ($stmt->fetchAll() as $r) $accounts[$r['id']] = $r;
    if (count($accounts) !== 2) {
        json_error('One or both accounts not found', 404);
    }
    $from = $accounts[$from_id];
    $to   = $accounts[$to_id];

    $transfer_id = $force_id ?: bin2hex(random_bytes(18));
    $is_paid = !empty($data['is_paid']) ? 1 : (array_key_exists('is_paid', $data) ? 0 : 1);
    $paid_ts = $is_paid ? date('Y-m-d H:i:s') : null;
    $due_ts  = $data['due_ts'] ?? null;
    $description = $data['description'] ?? '';

    $own_tx = !$pdo->inTransaction();
    if ($own_tx) $pdo->beginTransaction();
    try {
        // Outflow leg (source loses money)
        insert_transfer_leg($pdo, $user_id, $transfer_id, [
            'title'        => 'Transferencia → ' . $to['name'],
            'description'  => $description,
            'amount'       => -abs($amount_sent),
            'currency'     => $from['currency'],
            'account_id'   => $from_id,
            'kind'         => 'transfer',
            'is_paid'      => $is_paid,
            'paid_ts'      => $paid_ts,
            'due_ts'       => $due_ts,
            'category_id'  => null,
        ]);

        // Inflow leg (destination gains money)
        insert_transfer_leg($pdo, $user_id, $transfer_id, [
            'title'        => 'Transferencia ← ' . $from['name'],
            'description'  => $description,
            'amount'       => abs($amount_received),
            'currency'     => $to['currency'],
            'account_id'   => $to_id,
            'kind'         => 'transfer',
            'is_paid'      => $is_paid,
            'paid_ts'      => $paid_ts,
            'due_ts'       => $due_ts,
            'category_id'  => null,
        ]);

        // Optional fee leg (third row, on the source account)
        if ($fee > 0) {
            insert_transfer_leg($pdo, $user_id, $transfer_id, [
                'title'        => 'Comisión transferencia',
                'description'  => $description,
                'amount'       => -abs($fee),
                'currency'     => $from['currency'],
                'account_id'   => $from_id,
                'kind'         => 'fee',
                'is_paid'      => $is_paid,
                'paid_ts'      => $paid_ts,
                'due_ts'       => $due_ts,
                'category_id'  => $data['fee_category_id'] ?? null,
            ]);
        }
        if ($own_tx) $pdo->commit();
    } catch (Throwable $e) {
        if ($own_tx) $pdo->rollBack();
        throw $e;
    }

    return $transfer_id;
}

function insert_transfer_leg(PDO $pdo, string $user_id, string $transfer_id, array $leg): void {
    $id = bin2hex(random_bytes(14));
    $stmt = $pdo->prepare(
        "INSERT INTO `transaction`
         (id, user_id, title, description, amount, currency, expense_category_id,
          is_paid, paid_ts, account_id, transfer_id, transaction_type, kind, due_ts, source, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'one-time', ?, ?, 'manual', 'reviewed')"
    );
    $stmt->execute([
        $id,
        $user_id,
        $leg['title'],
        $leg['description'],
        $leg['amount'],
        $leg['currency'],
        $leg['category_id'],
        $leg['is_paid'],
        $leg['paid_ts'],
        $leg['account_id'],
        $transfer_id,
        $leg['kind'],
        $leg['due_ts'],
    ]);
}

function delete_transfer_legs(PDO $pdo, string $user_id, string $transfer_id): int {
    $stmt = $pdo->prepare(
        "DELETE FROM `transaction` WHERE user_id = ? AND transfer_id = ?"
    );
    $stmt->execute([$user_id, $transfer_id]);
    return $stmt->rowCount();
}

function get_transfer(PDO $pdo, string $user_id, string $transfer_id): ?array {
    $stmt = $pdo->prepare(
        "SELECT t.*, a.name AS account_name, a.color AS account_color
         FROM `transaction` t
         LEFT JOIN account a ON a.id = t.account_id
         WHERE t.user_id = ? AND t.transfer_id = ?
         ORDER BY t.amount DESC"  // outflow first by sign? actually inflow has + amount, sort by kind+amount
    );
    $stmt->execute([$user_id, $transfer_id]);
    $rows = $stmt->fetchAll();
    if (empty($rows)) return null;

    $from_leg = null; $to_leg = null; $fee_leg = null;
    foreach ($rows as $r) {
        if ($r['kind'] === 'fee') {
            $fee_leg = $r;
        } elseif ((float)$r['amount'] < 0) {
            $from_leg = $r;
        } else {
            $to_leg = $r;
        }
    }

    return [
        'transfer_id' => $transfer_id,
        'from_leg'    => $from_leg,
        'to_leg'      => $to_leg,
        'fee_leg'     => $fee_leg,
        'legs'        => $rows,
    ];
}
