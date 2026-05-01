<?php
// $pdo, $user_id, and (when present) $transactions_action provided by index.php
//
// Sign convention: this endpoint stores signed amounts. Sign is driven by
// `kind`: expense ⇒ -abs(input), income ⇒ +abs(input). Transfers and fees
// stay expense-managed via /api/transfers (this endpoint refuses to delete
// them and never creates them). Reads return the stored signed value as-is;
// the frontend `Math.abs()`es for display.
//
// Categories: expense rows use expense_category_id, income rows use
// income_category_id. The opposite column is forced to NULL on write.

if (($transactions_action ?? null) === 'artifact') {
    handle_transaction_artifact($pdo, $user_id);
    return;
}

switch (method()) {
    case 'GET':
        // Single transaction with recipient
        if (!empty($_GET['id'])) {
            $stmt = $pdo->prepare(
                "SELECT t.*, tr.name AS recipient_name, tr.cbu AS recipient_cbu,
                        tr.alias AS recipient_alias, tr.bank AS recipient_bank
                 FROM `transaction` t
                 LEFT JOIN transaction_recipient tr ON tr.transaction_id = t.id
                 WHERE t.id = ? AND t.user_id = ?"
            );
            $stmt->execute([$_GET['id'], $user_id]);
            $row = $stmt->fetch();

            if (!$row) json_error('Transaction not found', 404);

            // Nest recipient into sub-object if present
            if ($row['recipient_name']) {
                $row['recipient'] = [
                    'name'  => $row['recipient_name'],
                    'cbu'   => $row['recipient_cbu'],
                    'alias' => $row['recipient_alias'],
                    'bank'  => $row['recipient_bank'],
                ];
            } else {
                $row['recipient'] = null;
            }
            unset($row['recipient_name'], $row['recipient_cbu'], $row['recipient_alias'], $row['recipient_bank']);

            json_response($row);
        }

        // List with filters
        $sql = "SELECT * FROM `transaction` WHERE user_id = ?";
        $params = [$user_id];

        if (!empty($_GET['start_date'])) {
            $sql .= " AND due_ts >= ?";
            $params[] = $_GET['start_date'];
        }
        if (!empty($_GET['end_date'])) {
            $end = $_GET['end_date'];
            if (strlen($end) <= 10) $end .= ' 23:59:59';
            $sql .= " AND due_ts <= ?";
            $params[] = $end;
        }
        if (!empty($_GET['transaction_type'])) {
            $sql .= " AND transaction_type = ?";
            $params[] = $_GET['transaction_type'];
        }
        if (isset($_GET['is_paid'])) {
            $sql .= " AND is_paid = ?";
            $params[] = (int) $_GET['is_paid'];
        }
        if (!empty($_GET['expense_category_id'])) {
            $sql .= " AND expense_category_id = ?";
            $params[] = $_GET['expense_category_id'];
        }
        if (!empty($_GET['income_category_id'])) {
            $sql .= " AND income_category_id = ?";
            $params[] = $_GET['income_category_id'];
        }
        if (!empty($_GET['recurrent_id'])) {
            $sql .= " AND recurrent_id = ?";
            $params[] = $_GET['recurrent_id'];
        }
        if (!empty($_GET['card_id'])) {
            $sql .= " AND card_id = ?";
            $params[] = $_GET['card_id'];
        }
        if (!empty($_GET['account_id'])) {
            $sql .= " AND account_id = ?";
            $params[] = $_GET['account_id'];
        }
        if (!empty($_GET['currency'])) {
            $sql .= " AND currency = ?";
            $params[] = $_GET['currency'];
        }
        if (!empty($_GET['kind'])) {
            // Comma-separated list, e.g. ?kind=expense,fee for spend rollups.
            $kinds = array_filter(array_map('trim', explode(',', $_GET['kind'])));
            $allowed = ['expense','income','transfer','fee'];
            $kinds = array_values(array_intersect($kinds, $allowed));
            if (!empty($kinds)) {
                $placeholders = implode(',', array_fill(0, count($kinds), '?'));
                $sql .= " AND kind IN ($placeholders)";
                $params = array_merge($params, $kinds);
            }
        }

        // Sort by the most recent real-world timestamp: paid_ts when paid,
        // due_ts otherwise. Keeps just-paid items at the top of the list.
        $sql .= " ORDER BY COALESCE(paid_ts, due_ts) DESC, created_ts DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll());

    case 'POST':
        $data = get_json_body();
        if (empty($data['title']) || !isset($data['amount'])) {
            json_error('title and amount are required');
        }

        // Only expense | income are creatable here. Transfers and fees are
        // managed by /api/transfers and never come through this endpoint.
        $kind = $data['kind'] ?? 'expense';
        if (!in_array($kind, ['expense', 'income'], true)) {
            json_error('kind must be one of: expense, income');
        }

        $id = bin2hex(random_bytes(14));
        $is_paid = !empty($data['is_paid']) ? 1 : 0;
        // Allow clients to back-date paid_ts (e.g. logging an expense after the
        // fact). If is_paid but no paid_ts provided, default to now.
        $paid_ts = $is_paid
            ? (!empty($data['paid_ts']) ? $data['paid_ts'] : date('Y-m-d H:i:s'))
            : null;
        $signed_amount = $kind === 'income'
            ? abs((float)$data['amount'])
            : -abs((float)$data['amount']);

        // Force the opposite category_id to NULL so a row can never claim a
        // category from the wrong taxonomy.
        $expense_cat = $kind === 'expense' ? ($data['expense_category_id'] ?? null) : null;
        $income_cat  = $kind === 'income'  ? ($data['income_category_id']  ?? null) : null;

        [$account_id, $currency] = resolve_account_and_currency(
            $pdo, $user_id, $data['account_id'] ?? null, $data['currency'] ?? null
        );

        $stmt = $pdo->prepare(
            "INSERT INTO `transaction` (id, user_id, title, description, amount, currency, expense_category_id,
             income_category_id, is_paid, paid_ts, recurrent_id, card_id, account_id, transaction_type, due_ts,
             kind, source, status, needs_revision, is_whatsapp, audio_transcription, ai_artifact_path, ai_artifact_mime)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $id,
            $user_id,
            $data['title'],
            $data['description'] ?? '',
            $signed_amount,
            $currency,
            $expense_cat,
            $income_cat,
            $is_paid,
            $paid_ts,
            $data['recurrent_id'] ?? null,
            $data['card_id'] ?? null,
            $account_id,
            $data['transaction_type'] ?? 'one-time',
            $data['due_ts'] ?? null,
            $kind,
            $data['source'] ?? 'manual',
            $data['status'] ?? 'reviewed',
            !empty($data['needs_revision']) ? 1 : 0,
            !empty($data['is_whatsapp']) ? 1 : 0,
            $data['audio_transcription'] ?? null,
            $data['ai_artifact_path'] ?? null,
            $data['ai_artifact_mime'] ?? null,
        ]);

        // Optional recipient
        if (!empty($data['recipient']) && !empty($data['recipient']['name'])) {
            $r = $data['recipient'];
            $stmt = $pdo->prepare(
                "INSERT INTO transaction_recipient (transaction_id, name, cbu, alias, bank)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$id, $r['name'], $r['cbu'] ?? null, $r['alias'] ?? null, $r['bank'] ?? null]);
        }

        json_response(['id' => $id], 201);

    case 'PUT':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $data = get_json_body();
        $allowed = ['title', 'description', 'expense_category_id', 'income_category_id',
                     'card_id', 'account_id', 'currency',
                     'transaction_type', 'due_ts', 'source', 'status', 'needs_revision',
                     'is_whatsapp', 'audio_transcription', 'ai_artifact_path', 'ai_artifact_mime'];
        $fields = [];
        $params = [];

        if (isset($data['currency']) && !in_array($data['currency'], ['ARS','USD','USDT'], true)) {
            json_error('currency must be one of: ARS, USD, USDT');
        }

        // Amount sign follows the row's existing kind. Kind is immutable via
        // PUT — converting expense ↔ income requires delete + recreate (rare,
        // and avoids edge cases like flipping the sign while the opposite
        // category_id is still populated).
        if (array_key_exists('amount', $data)) {
            $stmt = $pdo->prepare("SELECT kind FROM `transaction` WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $existing_kind = $stmt->fetchColumn();
            if ($existing_kind === false) json_error('Transaction not found', 404);
            $fields[] = "amount = ?";
            $params[] = $existing_kind === 'income'
                ? abs((float)$data['amount'])
                : -abs((float)$data['amount']);
        }

        // Special handling for is_paid toggle. NOW() is AR-anchored via the
        // session timezone we set in config.php's PDO init command.
        // When the client supplies an explicit paid_ts (back-dating), use it
        // instead of NOW().
        if (isset($data['is_paid'])) {
            if ($data['is_paid']) {
                $fields[] = "is_paid = 1";
                if (!empty($data['paid_ts'])) {
                    $fields[] = "paid_ts = ?";
                    $params[] = $data['paid_ts'];
                } else {
                    $fields[] = "paid_ts = NOW()";
                }
            } else {
                $fields[] = "is_paid = 0";
                $fields[] = "paid_ts = NULL";
            }
        } elseif (array_key_exists('paid_ts', $data) && $data['paid_ts']) {
            // Allow updating paid_ts without toggling is_paid (edit existing paid row).
            $fields[] = "paid_ts = ?";
            $params[] = $data['paid_ts'];
        }

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[] = $data[$col];
            }
        }

        $has_recipient = array_key_exists('recipient', $data);
        if (empty($fields) && !$has_recipient) json_error('Nothing to update');

        if (!empty($fields)) {
            $params[] = $id;
            $params[] = $user_id;

            $stmt = $pdo->prepare(
                "UPDATE `transaction` SET " . implode(', ', $fields) .
                " WHERE id = ? AND user_id = ?"
            );
            $stmt->execute($params);
        }

        // Handle recipient sub-object
        if ($has_recipient) {
            if ($data['recipient'] === null) {
                $pdo->prepare("DELETE FROM transaction_recipient WHERE transaction_id = ?")->execute([$id]);
            } elseif (!empty($data['recipient']['name'])) {
                $r = $data['recipient'];
                $stmt = $pdo->prepare(
                    "REPLACE INTO transaction_recipient (transaction_id, name, cbu, alias, bank)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$id, $r['name'], $r['cbu'] ?? null, $r['alias'] ?? null, $r['bank'] ?? null]);
            }
        }

        json_response(['updated' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        // Look up the artifact path before deleting, so we can clean up Spaces.
        // Also fetch transfer_id: if this row belongs to a transfer, refuse the
        // delete and tell the caller to use /api/transfers DELETE — preventing
        // partial-transfer corruption.
        $stmt = $pdo->prepare("SELECT ai_artifact_path, transfer_id FROM `transaction` WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $row = $stmt->fetch();
        if (!$row) json_error('Transaction not found', 404);
        if (!empty($row['transfer_id'])) {
            json_error('This row is part of a transfer; delete it via /api/transfers', 409);
        }
        $artifact_path = $row['ai_artifact_path'] ?: null;

        $stmt = $pdo->prepare("DELETE FROM `transaction` WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() === 0) json_error('Transaction not found', 404);

        if ($artifact_path) {
            $spaces_conf = $GLOBALS['mangos_config']['spaces'] ?? null;
            if ($spaces_conf && !empty($spaces_conf['key']) && $spaces_conf['key'] !== 'CHANGE_ME') {
                require_once __DIR__ . '/../handlers/SpacesHandler.php';
                try { (new SpacesHandler($spaces_conf))->delete($artifact_path); }
                catch (\Throwable $e) { error_log('[transactions] artifact delete failed: ' . $e->getMessage()); }
            }
        }

        json_response(['deleted' => true]);

    default:
        json_error('Method not allowed', 405);
}

/**
 * Private proxy: streams the AI artifact stored on DO Spaces back to the
 * authenticated owner. Reached as GET /api/transactions/artifact?id=<transaction_id>.
 * Buckets are private (ACL=private) and DO's CDN public URL returns 403, so
 * this proxy is the only legitimate way for the browser to fetch the file.
 */
function handle_transaction_artifact(PDO $pdo, string $user_id): void {
    if (method() !== 'GET') json_error('Method not allowed', 405);
    $id = $_GET['id'] ?? '';
    if (empty($id)) json_error('id is required');

    $stmt = $pdo->prepare(
        "SELECT ai_artifact_path, ai_artifact_mime FROM `transaction` WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$id, $user_id]);
    $row = $stmt->fetch();

    if (!$row) json_error('Transaction not found', 404);
    if (empty($row['ai_artifact_path'])) json_error('No artifact for this transaction', 404);

    $spaces_conf = $GLOBALS['mangos_config']['spaces'] ?? null;
    if (!$spaces_conf || empty($spaces_conf['key']) || $spaces_conf['key'] === 'CHANGE_ME') {
        json_error('Spaces not configured', 503);
    }

    require_once __DIR__ . '/../handlers/SpacesHandler.php';
    $spaces = new SpacesHandler($spaces_conf);
    // Sets headers + writes body, or sets 404 and returns false.
    $spaces->streamToOutput($row['ai_artifact_path'], $row['ai_artifact_mime']);
    exit;
}
