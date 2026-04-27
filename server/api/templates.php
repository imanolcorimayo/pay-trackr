<?php
// $pdo and $user_id provided by index.php

switch (method()) {
    case 'GET':
        $stmt = $pdo->prepare(
            "SELECT * FROM payment_template WHERE user_id = ? ORDER BY usage_count DESC"
        );
        $stmt->execute([$user_id]);
        json_response($stmt->fetchAll());

    case 'POST':
        $data = get_json_body();
        if (empty($data['name'])) {
            json_error('name is required');
        }

        $id = bin2hex(random_bytes(14));

        $stmt = $pdo->prepare(
            "INSERT INTO payment_template (id, user_id, name, expense_category_id, description)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $id,
            $user_id,
            $data['name'],
            $data['expense_category_id'] ?? null,
            $data['description'] ?? '',
        ]);

        json_response(['id' => $id], 201);

    case 'PUT':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $data = get_json_body();

        // Special case: increment usage counter
        if (!empty($data['increment_usage'])) {
            $stmt = $pdo->prepare(
                "UPDATE payment_template SET usage_count = usage_count + 1
                 WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$id, $user_id]);
            json_response(['updated' => true]);
        }

        $allowed = ['name', 'expense_category_id', 'description'];
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
            "UPDATE payment_template SET " . implode(', ', $fields) .
            " WHERE id = ? AND user_id = ?"
        );
        $stmt->execute($params);

        json_response(['updated' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (empty($id)) json_error('id is required');

        $stmt = $pdo->prepare("DELETE FROM payment_template WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() === 0) json_error('Template not found', 404);

        json_response(['deleted' => true]);

    default:
        json_error('Method not allowed', 405);
}
