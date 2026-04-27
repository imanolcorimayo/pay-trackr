<?php
// $pdo and $user_id provided by index.php

switch (method()) {
    case 'GET':
        $stmt = $pdo->prepare(
            "SELECT id, name, color, created_ts FROM expense_category
             WHERE user_id = ? AND deleted_ts IS NULL
             ORDER BY name"
        );
        $stmt->execute([$user_id]);
        json_response($stmt->fetchAll());

    case 'POST':
        $data = get_json_body();
        if (empty($data['name']) || empty($data['color'])) {
            json_error('name and color are required');
        }

        $id = bin2hex(random_bytes(14));
        $stmt = $pdo->prepare(
            "INSERT INTO expense_category (id, user_id, name, color) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$id, $user_id, $data['name'], $data['color']]);

        json_response(['id' => $id, 'name' => $data['name'], 'color' => $data['color']], 201);

    case 'PUT':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $data = get_json_body();
        $allowed = ['name', 'color'];
        $fields = [];
        $params = [];

        foreach ($allowed as $col) {
            if (isset($data[$col])) {
                $fields[] = "$col = ?";
                $params[] = $data[$col];
            }
        }

        if (empty($fields)) json_error('Nothing to update');

        $params[] = $id;
        $params[] = $user_id;

        $stmt = $pdo->prepare(
            "UPDATE expense_category SET " . implode(', ', $fields) .
            " WHERE id = ? AND user_id = ? AND deleted_ts IS NULL"
        );
        $stmt->execute($params);

        json_response(['updated' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $stmt = $pdo->prepare(
            "UPDATE expense_category SET deleted_ts = NOW()
             WHERE id = ? AND user_id = ? AND deleted_ts IS NULL"
        );
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() === 0) json_error('Category not found', 404);

        json_response(['deleted' => true]);

    default:
        json_error('Method not allowed', 405);
}
