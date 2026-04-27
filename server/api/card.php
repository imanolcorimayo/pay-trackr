<?php
// $pdo and $user_id provided by index.php

$allowed_types = ['credit', 'debit', 'virtual'];

switch (method()) {
    case 'GET':
        if (!empty($_GET['id'])) {
            $stmt = $pdo->prepare(
                "SELECT * FROM card WHERE id = ? AND user_id = ? AND deleted_ts IS NULL"
            );
            $stmt->execute([$_GET['id'], $user_id]);
            $row = $stmt->fetch();
            if (!$row) json_error('Card not found', 404);
            json_response($row);
        }

        $stmt = $pdo->prepare(
            "SELECT * FROM card WHERE user_id = ? AND deleted_ts IS NULL ORDER BY name"
        );
        $stmt->execute([$user_id]);
        json_response($stmt->fetchAll());

    case 'POST':
        $data = get_json_body();
        if (empty($data['name']) || empty($data['type'])) {
            json_error('name and type are required');
        }
        if (!in_array($data['type'], $allowed_types, true)) {
            json_error('type must be one of: ' . implode(', ', $allowed_types));
        }

        $id = bin2hex(random_bytes(14));
        $stmt = $pdo->prepare(
            "INSERT INTO card (id, user_id, name, type, bank, last_four, color, closing_day, due_day)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $id,
            $user_id,
            $data['name'],
            $data['type'],
            $data['bank'] ?? null,
            $data['last_four'] ?? null,
            $data['color'] ?? null,
            isset($data['closing_day']) ? (int) $data['closing_day'] : null,
            isset($data['due_day']) ? (int) $data['due_day'] : null,
        ]);

        json_response(['id' => $id], 201);

    case 'PUT':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $data = get_json_body();
        if (isset($data['type']) && !in_array($data['type'], $allowed_types, true)) {
            json_error('type must be one of: ' . implode(', ', $allowed_types));
        }

        $allowed = ['name', 'type', 'bank', 'last_four', 'color', 'closing_day', 'due_day'];
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
            "UPDATE card SET " . implode(', ', $fields) .
            " WHERE id = ? AND user_id = ? AND deleted_ts IS NULL"
        );
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) json_error('Card not found', 404);

        json_response(['updated' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $stmt = $pdo->prepare(
            "UPDATE card SET deleted_ts = NOW()
             WHERE id = ? AND user_id = ? AND deleted_ts IS NULL"
        );
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() === 0) json_error('Card not found', 404);

        json_response(['deleted' => true]);

    default:
        json_error('Method not allowed', 405);
}
