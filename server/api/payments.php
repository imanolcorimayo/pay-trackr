<?php
// $pdo, $user_id, and (when present) $payments_action provided by index.php

if (($payments_action ?? null) === 'artifact') {
    handle_payment_artifact($pdo, $user_id);
    return;
}

switch (method()) {
    case 'GET':
        // Single payment with recipient
        if (!empty($_GET['id'])) {
            $stmt = $pdo->prepare(
                "SELECT p.*, pr.name AS recipient_name, pr.cbu AS recipient_cbu,
                        pr.alias AS recipient_alias, pr.bank AS recipient_bank
                 FROM payment p
                 LEFT JOIN payment_recipient pr ON pr.payment_id = p.id
                 WHERE p.id = ? AND p.user_id = ?"
            );
            $stmt->execute([$_GET['id'], $user_id]);
            $row = $stmt->fetch();

            if (!$row) json_error('Payment not found', 404);

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
        $sql = "SELECT * FROM payment WHERE user_id = ?";
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
        if (!empty($_GET['payment_type'])) {
            $sql .= " AND payment_type = ?";
            $params[] = $_GET['payment_type'];
        }
        if (isset($_GET['is_paid'])) {
            $sql .= " AND is_paid = ?";
            $params[] = (int) $_GET['is_paid'];
        }
        if (!empty($_GET['expense_category_id'])) {
            $sql .= " AND expense_category_id = ?";
            $params[] = $_GET['expense_category_id'];
        }
        if (!empty($_GET['recurrent_id'])) {
            $sql .= " AND recurrent_id = ?";
            $params[] = $_GET['recurrent_id'];
        }
        if (!empty($_GET['card_id'])) {
            $sql .= " AND card_id = ?";
            $params[] = $_GET['card_id'];
        }

        $sql .= " ORDER BY due_ts DESC, created_ts DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response($stmt->fetchAll());

    case 'POST':
        $data = get_json_body();
        if (empty($data['title']) || !isset($data['amount'])) {
            json_error('title and amount are required');
        }

        $id = bin2hex(random_bytes(14));
        $is_paid = !empty($data['is_paid']) ? 1 : 0;
        $paid_ts = $is_paid ? date('Y-m-d H:i:s') : null;

        $stmt = $pdo->prepare(
            "INSERT INTO payment (id, user_id, title, description, amount, expense_category_id,
             is_paid, paid_ts, recurrent_id, card_id, payment_type, due_ts, source, status,
             needs_revision, is_whatsapp, audio_transcription, ai_artifact_path, ai_artifact_mime)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $id,
            $user_id,
            $data['title'],
            $data['description'] ?? '',
            $data['amount'],
            $data['expense_category_id'] ?? null,
            $is_paid,
            $paid_ts,
            $data['recurrent_id'] ?? null,
            $data['card_id'] ?? null,
            $data['payment_type'] ?? 'one-time',
            $data['due_ts'] ?? null,
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
                "INSERT INTO payment_recipient (payment_id, name, cbu, alias, bank)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$id, $r['name'], $r['cbu'] ?? null, $r['alias'] ?? null, $r['bank'] ?? null]);
        }

        json_response(['id' => $id], 201);

    case 'PUT':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $data = get_json_body();
        $allowed = ['title', 'description', 'amount', 'expense_category_id', 'card_id',
                     'payment_type', 'due_ts', 'source', 'status', 'needs_revision',
                     'is_whatsapp', 'audio_transcription', 'ai_artifact_path', 'ai_artifact_mime'];
        $fields = [];
        $params = [];

        // Special handling for is_paid toggle. NOW() is AR-anchored via the
        // session timezone we set in config.php's PDO init command.
        if (isset($data['is_paid'])) {
            if ($data['is_paid']) {
                $fields[] = "is_paid = 1";
                $fields[] = "paid_ts = NOW()";
            } else {
                $fields[] = "is_paid = 0";
                $fields[] = "paid_ts = NULL";
            }
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
                "UPDATE payment SET " . implode(', ', $fields) .
                " WHERE id = ? AND user_id = ?"
            );
            $stmt->execute($params);
        }

        // Handle recipient sub-object
        if ($has_recipient) {
            if ($data['recipient'] === null) {
                $pdo->prepare("DELETE FROM payment_recipient WHERE payment_id = ?")->execute([$id]);
            } elseif (!empty($data['recipient']['name'])) {
                $r = $data['recipient'];
                $stmt = $pdo->prepare(
                    "REPLACE INTO payment_recipient (payment_id, name, cbu, alias, bank)
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
        $stmt = $pdo->prepare("SELECT ai_artifact_path FROM payment WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $artifact_path = $stmt->fetchColumn() ?: null;

        $stmt = $pdo->prepare("DELETE FROM payment WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() === 0) json_error('Payment not found', 404);

        if ($artifact_path) {
            $spaces_conf = $GLOBALS['mangos_config']['spaces'] ?? null;
            if ($spaces_conf && !empty($spaces_conf['key']) && $spaces_conf['key'] !== 'CHANGE_ME') {
                require_once __DIR__ . '/../handlers/SpacesHandler.php';
                try { (new SpacesHandler($spaces_conf))->delete($artifact_path); }
                catch (\Throwable $e) { error_log('[payments] artifact delete failed: ' . $e->getMessage()); }
            }
        }

        json_response(['deleted' => true]);

    default:
        json_error('Method not allowed', 405);
}

/**
 * Private proxy: streams the AI artifact stored on DO Spaces back to the
 * authenticated owner. Reached as GET /api/payments/artifact?id=<payment_id>.
 * Buckets are private (ACL=private) and DO's CDN public URL returns 403, so
 * this proxy is the only legitimate way for the browser to fetch the file.
 */
function handle_payment_artifact(PDO $pdo, string $user_id): void {
    if (method() !== 'GET') json_error('Method not allowed', 405);
    $id = $_GET['id'] ?? '';
    if (empty($id)) json_error('id is required');

    $stmt = $pdo->prepare(
        "SELECT ai_artifact_path, ai_artifact_mime FROM payment WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$id, $user_id]);
    $row = $stmt->fetch();

    if (!$row) json_error('Payment not found', 404);
    if (empty($row['ai_artifact_path'])) json_error('No artifact for this payment', 404);

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
