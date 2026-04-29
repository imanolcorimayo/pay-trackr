<?php
// $pdo and $user_id provided by index.php

switch (method()) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM recurrent WHERE id = ? AND user_id = ?");
            $stmt->execute([$_GET['id'], $user_id]);
            $row = $stmt->fetch();

            if (!$row) json_error('Recurrent not found', 404);
            $row['aliases'] = fetch_aliases($pdo, $row['id']);
            json_response($row);
        }

        $stmt = $pdo->prepare("SELECT * FROM recurrent WHERE user_id = ? ORDER BY title");
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll();

        if ($rows) {
            $aliases_by_recurrent = fetch_aliases_bulk($pdo, array_column($rows, 'id'));
            foreach ($rows as &$row) {
                $row['aliases'] = $aliases_by_recurrent[$row['id']] ?? [];
            }
            unset($row);
        }

        json_response($rows);

    case 'POST':
        $data = get_json_body();
        if (empty($data['title']) || !isset($data['amount']) || !isset($data['due_date_day'])) {
            json_error('title, amount, and due_date_day are required');
        }

        $id = bin2hex(random_bytes(14));

        [$account_id, $currency] = resolve_account_and_currency(
            $pdo, $user_id, $data['account_id'] ?? null, $data['currency'] ?? null
        );

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO recurrent (id, user_id, title, description, amount, currency, start_date,
                 due_date_day, end_date, time_period, expense_category_id, card_id, account_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $id,
                $user_id,
                $data['title'],
                $data['description'] ?? '',
                -abs((float)$data['amount']),
                $currency,
                $data['start_date'] ?? null,
                (int) $data['due_date_day'],
                $data['end_date'] ?? null,
                $data['time_period'] ?? 'monthly',
                $data['expense_category_id'] ?? null,
                $data['card_id'] ?? null,
                $account_id,
            ]);

            replace_aliases($pdo, $id, $data['aliases'] ?? []);

            $pdo->commit();
            json_response(['id' => $id], 201);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            json_error('Create failed: ' . $e->getMessage(), 500);
        }

    case 'PUT':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $data = get_json_body();
        $allowed = ['title', 'description', 'start_date', 'due_date_day',
                     'end_date', 'time_period', 'expense_category_id', 'card_id',
                     'account_id', 'currency'];
        $fields = [];
        $params = [];

        if (isset($data['currency']) && !in_array($data['currency'], ['ARS','USD','USDT'], true)) {
            json_error('currency must be one of: ARS, USD, USDT');
        }

        // Amount needs sign normalization (negative for expense).
        if (array_key_exists('amount', $data)) {
            $fields[] = "amount = ?";
            $params[] = -abs((float)$data['amount']);
        }

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[] = $data[$col];
            }
        }

        $has_aliases = array_key_exists('aliases', $data);
        if (empty($fields) && !$has_aliases) json_error('Nothing to update');

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM recurrent WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        if (!$stmt->fetch()) json_error('Recurrent not found', 404);

        $pdo->beginTransaction();
        try {
            if (!empty($fields)) {
                $params[] = $id;
                $params[] = $user_id;
                $stmt = $pdo->prepare(
                    "UPDATE recurrent SET " . implode(', ', $fields) .
                    " WHERE id = ? AND user_id = ?"
                );
                $stmt->execute($params);
            }
            if ($has_aliases) {
                replace_aliases($pdo, $id, $data['aliases']);
            }
            $pdo->commit();
            json_response(['updated' => true]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            json_error('Update failed: ' . $e->getMessage(), 500);
        }

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $stmt = $pdo->prepare("SELECT id FROM recurrent WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        if (!$stmt->fetch()) json_error('Recurrent not found', 404);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("DELETE FROM `transaction` WHERE recurrent_id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $instances_deleted = $stmt->rowCount();

            // recurrent_alias has ON DELETE CASCADE → cleaned up automatically
            $pdo->prepare("DELETE FROM recurrent WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);

            $pdo->commit();
            json_response(['deleted' => true, 'instances_deleted' => $instances_deleted]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            json_error('Delete failed: ' . $e->getMessage(), 500);
        }

    default:
        json_error('Method not allowed', 405);
}

// ──────────────────────────────────────────────────────────────────
// Aliases helpers
// ──────────────────────────────────────────────────────────────────

function fetch_aliases(PDO $pdo, string $recurrent_id): array {
    $stmt = $pdo->prepare("SELECT alias FROM recurrent_alias WHERE recurrent_id = ? ORDER BY alias");
    $stmt->execute([$recurrent_id]);
    return array_column($stmt->fetchAll(), 'alias');
}

function fetch_aliases_bulk(PDO $pdo, array $recurrent_ids): array {
    if (empty($recurrent_ids)) return [];
    $placeholders = implode(',', array_fill(0, count($recurrent_ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT recurrent_id, alias FROM recurrent_alias
         WHERE recurrent_id IN ($placeholders) ORDER BY alias"
    );
    $stmt->execute($recurrent_ids);
    $by_id = [];
    foreach ($stmt->fetchAll() as $row) {
        $by_id[$row['recurrent_id']][] = $row['alias'];
    }
    return $by_id;
}

function replace_aliases(PDO $pdo, string $recurrent_id, $aliases): void {
    if (!is_array($aliases)) $aliases = [];
    $clean = [];
    foreach ($aliases as $a) {
        if (!is_string($a)) continue;
        $a = trim($a);
        if ($a === '' || mb_strlen($a) > 200) continue;
        $clean[$a] = true; // dedupe
    }
    $clean = array_keys($clean);

    $pdo->prepare("DELETE FROM recurrent_alias WHERE recurrent_id = ?")->execute([$recurrent_id]);
    if (empty($clean)) return;

    $stmt = $pdo->prepare("INSERT INTO recurrent_alias (id, recurrent_id, alias) VALUES (?, ?, ?)");
    foreach ($clean as $alias) {
        $stmt->execute([bin2hex(random_bytes(14)), $recurrent_id, $alias]);
    }
}
