<?php
// $pdo, $user_id, $ai_action provided by index.php

// Vision calls + model rotation can exceed PHP's default 30s. Cap is 120s
// (slightly above GeminiHandler's TOTAL_BUDGET_SEC so the handler returns
// a structured error rather than letting PHP hard-kill the request).
set_time_limit(120);

require_once __DIR__ . '/../handlers/GeminiHandler.php';

if (method() !== 'POST') json_error('Method not allowed', 405);

if ($ai_action === 'parse-payments') {
    handle_parse_payments($pdo, $user_id);
} elseif ($ai_action === 'commit-payments') {
    handle_commit_payments($pdo, $user_id);
} else {
    json_error('Unknown AI action', 404);
}

// ──────────────────────────────────────────────────────────────────
// PARSE
// ──────────────────────────────────────────────────────────────────

function handle_parse_payments(PDO $pdo, string $user_id): void {
    $body = get_json_body();
    $images = $body['images'] ?? [];

    if (!is_array($images) || count($images) === 0) {
        json_error('At least one image is required');
    }
    if (count($images) > 10) {
        json_error('Maximum 10 images per request', 413);
    }

    $total_b64 = 0;
    foreach ($images as $i => $img) {
        if (empty($img['mimeType']) || empty($img['data'])) {
            json_error("Image $i: mimeType and data required");
        }
        if (!str_starts_with($img['mimeType'], 'image/')) {
            json_error("Image $i: not an image mimeType");
        }
        $total_b64 += strlen($img['data']);
    }
    if ($total_b64 > 6 * 1024 * 1024) {
        json_error('Total image size exceeds 6MB (base64)', 413);
    }

    $api_key = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: '';
    if (!$api_key) json_error('GEMINI_API_KEY not configured', 500);

    $tz = new DateTimeZone('America/Argentina/Buenos_Aires');
    $now = new DateTime('now', $tz);
    $month_start = $now->format('Y-m-01 00:00:00');
    $month_end = $now->format('Y-m-t 23:59:59');
    $today = $now->format('Y-m-d');

    // Current month payments
    $stmt = $pdo->prepare(
        "SELECT id, title, amount, DATE(due_ts) AS due_date, DATE(paid_ts) AS paid_date,
                payment_type, recurrent_id, is_paid
         FROM payment
         WHERE user_id = ? AND due_ts BETWEEN ? AND ?
         ORDER BY due_ts"
    );
    $stmt->execute([$user_id, $month_start, $month_end]);
    $existing = $stmt->fetchAll();

    // Recurrents + their aliases (LEFT JOIN, group by recurrent)
    $stmt = $pdo->prepare(
        "SELECT r.id, r.title, r.amount, r.due_date_day, r.expense_category_id,
                COALESCE(GROUP_CONCAT(ra.alias SEPARATOR '\n'), '') AS aliases
         FROM recurrent r
         LEFT JOIN recurrent_alias ra ON ra.recurrent_id = r.id
         WHERE r.user_id = ?
         GROUP BY r.id
         ORDER BY r.title"
    );
    $stmt->execute([$user_id]);
    $recurrents_raw = $stmt->fetchAll();
    $recurrents = array_map(function($r) {
        return [
            'id' => $r['id'],
            'title' => $r['title'],
            'amount' => (float)$r['amount'],
            'due_date_day' => (int)$r['due_date_day'],
            'aliases' => $r['aliases'] ? array_values(array_filter(explode("\n", $r['aliases']))) : [],
        ];
    }, $recurrents_raw);

    // Categories
    $stmt = $pdo->prepare("SELECT id, name FROM expense_category WHERE user_id = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll();

    $context = [
        'today' => $today,
        'existing_payments_this_month' => array_map(fn($p) => [
            'id' => $p['id'],
            'title' => $p['title'],
            'amount' => (float)$p['amount'],
            'due_date' => $p['due_date'],
            'is_paid' => (bool)$p['is_paid'],
        ], $existing),
        'recurrents' => $recurrents,
        'categories' => array_map(fn($c) => $c['name'], $categories),
    ];

    $parts = [];
    foreach ($images as $img) {
        $parts[] = ['inlineData' => ['mimeType' => $img['mimeType'], 'data' => $img['data']]];
    }
    $parts[] = ['text' => build_prompt($context)];

    $start = microtime(true);
    $handler = new GeminiHandler($api_key);
    $gem = $handler->generateContent($parts, [
        'maxOutputTokens' => 16384,
        'temperature' => 0.2,
        'responseSchema' => build_schema(),
    ]);
    $elapsed_ms = (int)((microtime(true) - $start) * 1000);

    if (!empty($gem['error'])) {
        json_error('Gemini call failed: ' . $gem['error'] . ' (tried: ' . implode(', ', $gem['tried'] ?? []) . ')', 502);
    }

    $result = $gem['data'];
    $drafts = $result['drafts'] ?? [];
    $unreadable = $result['unreadable_screenshot_idxs'] ?? [];

    // Post-process
    $cat_by_name = [];
    foreach ($categories as $c) {
        $cat_by_name[mb_strtolower($c['name'])] = $c['id'];
    }
    $existing_ids = array_column($existing, 'id');
    $recurrent_ids = array_column($recurrents, 'id');

    foreach ($drafts as &$d) {
        $name = $d['suggested_category_name'] ?? null;
        $d['suggested_category_id'] = $name ? ($cat_by_name[mb_strtolower($name)] ?? null) : null;

        if (!empty($d['existing_payment_id']) && !in_array($d['existing_payment_id'], $existing_ids, true)) {
            $d['existing_payment_id'] = null;
        }
        if (!empty($d['recurrent_match_id']) && !in_array($d['recurrent_match_id'], $recurrent_ids, true)) {
            $d['recurrent_match_id'] = null;
        }

        // Server-side dedup belt: if Gemini missed an existing match
        if (empty($d['existing_payment_id'])) {
            $d_amount = (float)($d['amount'] ?? 0);
            $d_title = mb_strtolower($d['title'] ?? '');
            $d_date = $d['date'] ?? '';
            foreach ($existing as $ex) {
                $pct = 0;
                similar_text($d_title, mb_strtolower($ex['title']), $pct);
                if ($pct < 80) continue;

                $ex_amount = (float)$ex['amount'];
                $amount_diff = $ex_amount > 0 ? abs($d_amount - $ex_amount) / $ex_amount : 1;
                if ($amount_diff > 0.01) continue;

                $ref_date = $ex['due_date'] ?: $ex['paid_date'];
                if (!$ref_date || !$d_date) continue;
                $date_diff_days = abs(strtotime($d_date) - strtotime($ref_date)) / 86400;
                if ($date_diff_days > 2) continue;

                $d['existing_payment_id'] = $ex['id'];
                break;
            }
        }
    }
    unset($d);

    json_response([
        'drafts' => $drafts,
        'unreadable_screenshot_idxs' => $unreadable,
        'meta' => [
            'image_count' => count($images),
            'processing_ms' => $elapsed_ms,
            'model_used' => $gem['model_used'] ?? null,
            'models_tried' => $gem['tried'] ?? [],
        ],
    ]);
}

function build_prompt(array $context): string {
    $context_json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $today = $context['today'];

    return <<<PROMPT
Analiza las capturas de pantalla adjuntas (en orden, indices 0..N-1) y extrae TODAS las transacciones de GASTO (egreso de dinero) que aparezcan. Tu objetivo es ser EXHAUSTIVO: las capturas pueden tener listas densas, recorre cada una de arriba a abajo y NO te saltes ninguna fila de gasto.

CONTEXTO DEL USUARIO:
$context_json

DETECCION DE GASTO vs INGRESO (CRITICO):

GASTO (SI extraer) — indicadores visuales:
- Texto en NEGRO o ROJO
- Signo "-" o "$ -" antes del monto
- Flecha hacia ABAJO o icono de tarjeta saliente
- Texto: "Transferencia enviada", "Pago", "Pago de servicio", "Pago con QR", "Compra", "Debito", "Extraccion"

INGRESO (NO extraer, IGNORAR ABSOLUTAMENTE):
- Texto en VERDE
- Signo "+" o "$ +" antes del monto
- Flecha hacia ARRIBA o icono de entrada
- Texto: "Transferencia recibida", "Rendimientos", "Ingreso", "Cobro recibido", "Devolucion", "Reintegro", "Acreditacion"

Si tenes duda sobre si una linea es gasto o ingreso, observa el COLOR y el SIGNO. Verde con "+" SIEMPRE es ingreso; negro/rojo con "-" SIEMPRE es gasto.

REGLAS DE PARSEO:
- Argentina: el punto es separador de miles. "\$67.506" = 67506. "\$67.506,08" o "\$67.506⁰⁸" = 67506.08. Decimales pueden aparecer en superindice o tamaño chico al lado del monto principal.
- Si la transaccion no muestra año, asumi $today.
- title: max 80 chars. Usa el NOMBRE DEL DESTINATARIO/COMERCIO si existe (ej: "NAVARRO AMADEO ANDRES", "MINIMERCADO MAURI"). Si no, usa el concepto.
- date: YYYY-MM-DD. Buscala en el encabezado de la seccion de la fila.
- suggested_category_name: una EXACTA de la lista "categories" o null.

DEDUPLICACION Y MATCHING:
- existing_payment_id: si este gasto ya esta en "existing_payments_this_month" (titulo similar + monto cercano + fecha cercana), poner el id; si no, null.
- recurrent_match_id: si el destinatario o concepto coincide con el "title" O CUALQUIERA de los "aliases" de un recurrent, poner el id de ese recurrent. EJEMPLO: si un recurrent tiene title "Clases de running" y aliases ["NAVARRO AMADEO ANDRES"], y la transaccion es "Transferencia enviada NAVARRO AMADEO ANDRES", DEBE matchear ese recurrent_id. El monto puede variar ±20% (no exigir match exacto en plata).
- recurrent_match_confidence: "high" si el destinatario coincide casi exacto con title/alias. "medium" si es similar. "low" si solo coincide parcialmente o por monto.
- duplicate_in_batch_idx: si esta misma transaccion ya aparece en un draft anterior de esta respuesta (mismo monto + fecha + destinatario en otra captura), poner el indice del draft anterior; si no, null.
- Si una transaccion coincide con AMBOS (existing_payment_id Y recurrent_match_id), prioriza existing_payment_id (ya esta en la tabla).

EXHAUSTIVIDAD:
- Cada captura puede contener 5-30 filas. Extrae cada GASTO sin omitir ninguno.
- Las capturas pueden solaparse: la misma transaccion puede aparecer en varias. Usa duplicate_in_batch_idx para señalarlo, no la omitas en la primera aparicion.
- "Saldo del dia" NO es una transaccion, es un saldo. Ignoralo.

CAPTURAS NO LEIBLES:
- Si una captura no se puede leer, no muestra transacciones, o solo muestra ingresos/saldos, agrega su indice a "unreadable_screenshot_idxs".
PROMPT;
}

function build_schema(): array {
    return [
        'type' => 'OBJECT',
        'properties' => [
            'drafts' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'screenshot_idx' => ['type' => 'INTEGER'],
                        'title' => ['type' => 'STRING'],
                        'amount' => ['type' => 'NUMBER'],
                        'date' => ['type' => 'STRING'],
                        'description' => ['type' => 'STRING', 'nullable' => true],
                        'suggested_category_name' => ['type' => 'STRING', 'nullable' => true],
                        'existing_payment_id' => ['type' => 'STRING', 'nullable' => true],
                        'recurrent_match_id' => ['type' => 'STRING', 'nullable' => true],
                        'recurrent_match_confidence' => ['type' => 'STRING', 'enum' => ['high', 'medium', 'low']],
                        'duplicate_in_batch_idx' => ['type' => 'INTEGER', 'nullable' => true],
                    ],
                    'required' => ['screenshot_idx', 'title', 'amount', 'date', 'recurrent_match_confidence'],
                ],
            ],
            'unreadable_screenshot_idxs' => [
                'type' => 'ARRAY',
                'items' => ['type' => 'INTEGER'],
            ],
        ],
        'required' => ['drafts', 'unreadable_screenshot_idxs'],
    ];
}

// ──────────────────────────────────────────────────────────────────
// COMMIT
// ──────────────────────────────────────────────────────────────────

function handle_commit_payments(PDO $pdo, string $user_id): void {
    $body = get_json_body();
    $rows = $body['rows'] ?? [];
    if (!is_array($rows) || empty($rows)) {
        json_error('rows is required and must be non-empty');
    }

    $created = 0;
    $updated_recurrents = 0;
    $marked_paid = 0;
    $skipped = 0;
    $payment_ids = [];

    $pdo->beginTransaction();
    try {
        foreach ($rows as $i => $row) {
            $action = $row['action'] ?? '';

            if ($action === 'skip') {
                $skipped++;
                continue;
            }

            if ($action === 'create') {
                if (empty($row['title']) || !isset($row['amount'])) {
                    throw new RuntimeException("Row $i: title and amount required");
                }
                $id = bin2hex(random_bytes(14));
                $is_paid = !empty($row['is_paid']) ? 1 : 0;
                $paid_ts = $is_paid ? ($row['paid_ts'] ?? date('Y-m-d H:i:s')) : null;

                $stmt = $pdo->prepare(
                    "INSERT INTO payment (id, user_id, title, description, amount, expense_category_id,
                     is_paid, paid_ts, recurrent_id, card_id, payment_type, due_ts, source, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, 'ai-image', 'reviewed')"
                );
                $stmt->execute([
                    $id, $user_id,
                    $row['title'],
                    $row['description'] ?? '',
                    $row['amount'],
                    $row['expense_category_id'] ?? null,
                    $is_paid, $paid_ts,
                    $row['card_id'] ?? null,
                    $row['payment_type'] ?? 'one-time',
                    $row['due_ts'] ?? null,
                ]);
                $payment_ids[] = $id;
                $created++;
                continue;
            }

            if ($action === 'mark_recurrent_paid') {
                $rid = $row['recurrent_id'] ?? '';
                if (empty($rid)) throw new RuntimeException("Row $i: recurrent_id required");

                $paid_ts = $row['paid_ts'] ?? date('Y-m-d H:i:s');
                $amount = isset($row['amount']) ? (float)$row['amount'] : null;

                $stmt = $pdo->prepare("SELECT * FROM recurrent WHERE id = ? AND user_id = ?");
                $stmt->execute([$rid, $user_id]);
                $r = $stmt->fetch();
                if (!$r) throw new RuntimeException("Row $i: recurrent $rid not found");

                $month = (new DateTime($paid_ts))->format('Y-m');
                $stmt = $pdo->prepare(
                    "SELECT id FROM payment
                     WHERE user_id = ? AND recurrent_id = ?
                       AND payment_type = 'recurrent'
                       AND DATE_FORMAT(due_ts, '%Y-%m') = ?
                     LIMIT 1"
                );
                $stmt->execute([$user_id, $rid, $month]);
                $current = $stmt->fetch();

                if ($current) {
                    $sql = "UPDATE payment SET is_paid = 1, paid_ts = ?";
                    $params = [$paid_ts];
                    if ($amount !== null) {
                        $sql .= ", amount = ?";
                        $params[] = $amount;
                    }
                    $sql .= " WHERE id = ? AND user_id = ?";
                    $params[] = $current['id'];
                    $params[] = $user_id;
                    $pdo->prepare($sql)->execute($params);
                    $payment_ids[] = $current['id'];
                    $marked_paid++;
                } else {
                    $id = bin2hex(random_bytes(14));
                    $dt = new DateTime($paid_ts);
                    $year = (int)$dt->format('Y');
                    $mon = (int)$dt->format('m');
                    $last_day = (int)$dt->format('t');
                    $day = min((int)$r['due_date_day'], $last_day);
                    $due_ts = sprintf('%04d-%02d-%02d 00:00:00', $year, $mon, $day);

                    $stmt = $pdo->prepare(
                        "INSERT INTO payment (id, user_id, title, description, amount, expense_category_id,
                         is_paid, paid_ts, recurrent_id, card_id, payment_type, due_ts, source, status)
                         VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, 'recurrent', ?, 'ai-image', 'reviewed')"
                    );
                    $stmt->execute([
                        $id, $user_id,
                        $r['title'],
                        $r['description'] ?? '',
                        $amount ?? (float)$r['amount'],
                        $r['expense_category_id'],
                        $paid_ts,
                        $rid,
                        $r['card_id'],
                        $due_ts,
                    ]);
                    $payment_ids[] = $id;
                    $created++;
                    $marked_paid++;
                }

                if (!empty($row['update_recurrent_amount']) && $amount !== null) {
                    $pdo->prepare("UPDATE recurrent SET amount = ? WHERE id = ? AND user_id = ?")
                        ->execute([$amount, $rid, $user_id]);
                    $updated_recurrents++;
                }
                continue;
            }

            throw new RuntimeException("Row $i: unknown action '$action'");
        }

        $pdo->commit();
        json_response([
            'created' => $created,
            'marked_paid' => $marked_paid,
            'updated_recurrents' => $updated_recurrents,
            'skipped' => $skipped,
            'payment_ids' => $payment_ids,
        ]);
    } catch (\Throwable $e) {
        $pdo->rollBack();
        json_error('Commit failed: ' . $e->getMessage(), 500);
    }
}
