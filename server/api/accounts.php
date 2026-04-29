<?php
// $pdo and $user_id provided by index.php

$allowed_types = ['bank', 'cash', 'crypto', 'other'];
$allowed_currencies = ['ARS', 'USD', 'USDT'];

function clear_default_account(PDO $pdo, string $user_id, string $except_id = ''): void {
    $sql = "UPDATE account SET is_default = 0 WHERE user_id = ? AND is_default = 1 AND deleted_ts IS NULL";
    $params = [$user_id];
    if ($except_id !== '') {
        $sql .= " AND id != ?";
        $params[] = $except_id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

switch (method()) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $stmt = $pdo->prepare(
                "SELECT * FROM account WHERE id = ? AND user_id = ? AND deleted_ts IS NULL"
            );
            $stmt->execute([$_GET['id'], $user_id]);
            $row = $stmt->fetch();
            if (!$row) json_error('Account not found', 404);
            $row['current_balance'] = compute_account_balance($pdo, $user_id, $row);
            json_response($row);
        }

        $stmt = $pdo->prepare(
            "SELECT * FROM account WHERE user_id = ? AND deleted_ts IS NULL ORDER BY is_default DESC, name"
        );
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['current_balance'] = compute_account_balance($pdo, $user_id, $r);
        }
        unset($r);
        json_response($rows);

    case 'POST':
        $data = get_json_body();
        if (empty($data['name'])) json_error('name is required');

        $type = $data['type'] ?? 'bank';
        if (!in_array($type, $allowed_types, true)) {
            json_error('type must be one of: ' . implode(', ', $allowed_types));
        }
        $currency = $data['currency'] ?? 'ARS';
        if (!in_array($currency, $allowed_currencies, true)) {
            json_error('currency must be one of: ' . implode(', ', $allowed_currencies));
        }

        $opening_balance = isset($data['opening_balance']) ? (float)$data['opening_balance'] : 0;
        $opening_balance_date = $data['opening_balance_date'] ?? null;
        if ($opening_balance_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $opening_balance_date)) {
            json_error('opening_balance_date must be YYYY-MM-DD');
        }

        $is_default = !empty($data['is_default']) ? 1 : 0;
        $id = bin2hex(random_bytes(14));

        $pdo->beginTransaction();
        try {
            if ($is_default) clear_default_account($pdo, $user_id);

            $stmt = $pdo->prepare(
                "INSERT INTO account (id, user_id, name, type, currency, color, is_default, opening_balance, opening_balance_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $id,
                $user_id,
                $data['name'],
                $type,
                $currency,
                $data['color'] ?? null,
                $is_default,
                $opening_balance,
                $opening_balance_date,
            ]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        json_response(['id' => $id], 201);

    case 'PUT':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $data = get_json_body();
        if (isset($data['type']) && !in_array($data['type'], $allowed_types, true)) {
            json_error('type must be one of: ' . implode(', ', $allowed_types));
        }
        if (isset($data['currency']) && !in_array($data['currency'], $allowed_currencies, true)) {
            json_error('currency must be one of: ' . implode(', ', $allowed_currencies));
        }

        if (isset($data['opening_balance_date']) && $data['opening_balance_date'] !== null
            && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['opening_balance_date'])) {
            json_error('opening_balance_date must be YYYY-MM-DD');
        }

        $allowed = ['name', 'type', 'currency', 'color', 'is_default', 'opening_balance', 'opening_balance_date'];
        $fields = [];
        $params = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                if ($col === 'is_default') {
                    $params[] = !empty($data[$col]) ? 1 : 0;
                } elseif ($col === 'opening_balance') {
                    $params[] = (float)$data[$col];
                } else {
                    $params[] = $data[$col];
                }
            }
        }

        if (empty($fields)) json_error('Nothing to update');

        $params[] = $id;
        $params[] = $user_id;

        $pdo->beginTransaction();
        try {
            if (array_key_exists('is_default', $data) && !empty($data['is_default'])) {
                clear_default_account($pdo, $user_id, $id);
            }

            $stmt = $pdo->prepare(
                "UPDATE account SET " . implode(', ', $fields) .
                " WHERE id = ? AND user_id = ? AND deleted_ts IS NULL"
            );
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                json_error('Account not found', 404);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        json_response(['updated' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        // Refuse to delete the user's only remaining account — guarantees every
        // user keeps at least one account so transaction creation never has to
        // handle "no account exists" as a special case.
        $count = (int) $pdo->query(
            "SELECT COUNT(*) FROM account WHERE user_id = " . $pdo->quote($user_id) . " AND deleted_ts IS NULL"
        )->fetchColumn();
        if ($count <= 1) json_error('Cannot delete the last remaining account', 400);

        $stmt = $pdo->prepare(
            "UPDATE account SET deleted_ts = NOW(), is_default = 0
             WHERE id = ? AND user_id = ? AND deleted_ts IS NULL"
        );
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() === 0) json_error('Account not found', 404);

        // If we just deleted the default, promote another account to default.
        $has_default = (int) $pdo->query(
            "SELECT COUNT(*) FROM account WHERE user_id = " . $pdo->quote($user_id) .
            " AND is_default = 1 AND deleted_ts IS NULL"
        )->fetchColumn();
        if ($has_default === 0) {
            $promote = $pdo->prepare(
                "UPDATE account SET is_default = 1
                 WHERE user_id = ? AND deleted_ts IS NULL
                 ORDER BY created_ts ASC LIMIT 1"
            );
            $promote->execute([$user_id]);
        }

        json_response(['deleted' => true]);

    default:
        json_error('Method not allowed', 405);
}

/**
 * current_balance = opening_balance + SUM of paid transactions on/after the
 * opening_balance_date for this account. If the date is NULL, every paid
 * transaction is counted (handy for users who haven't set an anchor yet).
 *
 * Pending (unpaid) transactions are excluded — only money that has actually
 * moved counts. paid_ts is the cutoff field; we fall back to due_ts when a row
 * was marked paid without an explicit timestamp.
 */
function compute_account_balance(PDO $pdo, string $user_id, array $account): float {
    $sql = "SELECT COALESCE(SUM(amount), 0) FROM `transaction`
            WHERE user_id = ? AND account_id = ? AND is_paid = 1";
    $params = [$user_id, $account['id']];
    if (!empty($account['opening_balance_date'])) {
        $sql .= " AND DATE(COALESCE(paid_ts, due_ts)) >= ?";
        $params[] = $account['opening_balance_date'];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movements = (float) $stmt->fetchColumn();
    return round((float)$account['opening_balance'] + $movements, 2);
}
