<?php
// $pdo and $user_id provided by index.php

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
             needs_revision, is_whatsapp, audio_transcription)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
                     'is_whatsapp', 'audio_transcription'];
        $fields = [];
        $params = [];

        // Special handling for is_paid toggle
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

        $stmt = $pdo->prepare("DELETE FROM payment WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() === 0) json_error('Payment not found', 404);

        json_response(['deleted' => true]);

    default:
        json_error('Method not allowed', 405);
}
