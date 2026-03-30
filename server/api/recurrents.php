<?php
// $pdo and $user_id provided by index.php

switch (method()) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM recurrents WHERE id = ? AND user_id = ?");
            $stmt->execute([$_GET['id'], $user_id]);
            $row = $stmt->fetch();

            if (!$row) json_error('Recurrent not found', 404);
            json_response($row);
        }

        $stmt = $pdo->prepare("SELECT * FROM recurrents WHERE user_id = ? ORDER BY title");
        $stmt->execute([$user_id]);
        json_response($stmt->fetchAll());

    case 'POST':
        $data = get_json_body();
        if (empty($data['title']) || !isset($data['amount']) || !isset($data['due_date_day'])) {
            json_error('title, amount, and due_date_day are required');
        }

        $id = bin2hex(random_bytes(14));

        $stmt = $pdo->prepare(
            "INSERT INTO recurrents (id, user_id, title, description, amount, start_date,
             due_date_day, end_date, time_period, category_id, is_credit_card, credit_card_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $id,
            $user_id,
            $data['title'],
            $data['description'] ?? '',
            $data['amount'],
            $data['start_date'] ?? null,
            (int) $data['due_date_day'],
            $data['end_date'] ?? null,
            $data['time_period'] ?? 'monthly',
            $data['category_id'] ?? null,
            !empty($data['is_credit_card']) ? 1 : 0,
            $data['credit_card_id'] ?? null,
        ]);

        json_response(['id' => $id], 201);

    case 'PUT':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $data = get_json_body();
        $allowed = ['title', 'description', 'amount', 'start_date', 'due_date_day',
                     'end_date', 'time_period', 'category_id', 'is_credit_card', 'credit_card_id'];
        $fields = [];
        $params = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[] = $data[$col];
            }
        }

        if (empty($fields)) json_error('Nothing to update');

        $params[] = $id;
        $params[] = $user_id;

        $stmt = $pdo->prepare(
            "UPDATE recurrents SET " . implode(', ', $fields) .
            " WHERE id = ? AND user_id = ?"
        );
        $stmt->execute($params);

        json_response(['updated' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM recurrents WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        if (!$stmt->fetch()) json_error('Recurrent not found', 404);

        // Cascade delete: instances first, then template
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("DELETE FROM payments WHERE recurrent_id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $instances_deleted = $stmt->rowCount();

            $pdo->prepare("DELETE FROM recurrents WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);

            $pdo->commit();
            json_response(['deleted' => true, 'instances_deleted' => $instances_deleted]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            json_error('Delete failed: ' . $e->getMessage(), 500);
        }

    default:
        json_error('Method not allowed', 405);
}
