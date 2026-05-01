<?php
// $pdo, $user_id, $ai_action provided by index.php

// Vision calls + model rotation can exceed PHP's default 30s. Cap is 120s
// (slightly above GeminiHandler's TOTAL_BUDGET_SEC so the handler returns
// a structured error rather than letting PHP hard-kill the request).
set_time_limit(120);

require_once __DIR__ . '/../handlers/GeminiHandler.php';

if (method() !== 'POST') json_error('Method not allowed', 405);

if ($ai_action === 'parse-transactions') {
    handle_parse_transactions($pdo, $user_id);
} elseif ($ai_action === 'commit-transactions') {
    handle_commit_transactions($pdo, $user_id);
} elseif ($ai_action === 'parse-single') {
    handle_parse_single($pdo, $user_id);
} elseif ($ai_action === 'discard-artifact') {
    handle_discard_artifact($user_id);
} else {
    json_error('Unknown AI action', 404);
}

// ──────────────────────────────────────────────────────────────────
// PARSE
// ──────────────────────────────────────────────────────────────────

function handle_parse_transactions(PDO $pdo, string $user_id): void {
    $body = get_json_body();
    // Default to 'image' so legacy clients (no `mode` field) keep working.
    $mode = $body['mode'] ?? 'image';
    if (!in_array($mode, ['image', 'audio'], true)) {
        json_error('mode must be one of: image, audio');
    }

    $images = [];
    $audio = null;

    if ($mode === 'image') {
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
    } else { // audio
        $mimeType = $body['mimeType'] ?? '';
        $data = $body['data'] ?? '';
        if (!$mimeType || !$data) json_error('mimeType and data are required for mode=audio');
        if (!str_starts_with($mimeType, 'audio/')) json_error('mimeType must be audio/*');
        if (strlen($data) > 8 * 1024 * 1024) json_error('audio exceeds 8MB (base64)', 413);
        $audio = ['mimeType' => $mimeType, 'data' => $data];
    }

    $api_key = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: '';
    if (!$api_key) json_error('GEMINI_API_KEY not configured', 500);

    $tz = new DateTimeZone('America/Argentina/Buenos_Aires');
    $now = new DateTime('now', $tz);
    $today = $now->format('Y-m-d');
    // Rolling 60-day window: covers backlog uploads without ballooning the
    // prompt. Late reconcilers can still process last-month receipts; the
    // model gets enough recent rows to spot dedup + recurring vendors.
    $window_start = (clone $now)->modify('-60 days')->format('Y-m-d 00:00:00');
    $window_end = $now->format('Y-m-d 23:59:59');

    // Recent transactions. ABS() so the amount handed to Gemini is the
    // displayed (positive) magnitude, matching what the user sees on screen.
    $stmt = $pdo->prepare(
        "SELECT id, title, ABS(amount) AS amount, DATE(due_ts) AS due_date, DATE(paid_ts) AS paid_date,
                transaction_type, recurrent_id, is_paid
         FROM `transaction`
         WHERE user_id = ? AND due_ts BETWEEN ? AND ?
         ORDER BY due_ts"
    );
    $stmt->execute([$user_id, $window_start, $window_end]);
    $existing = $stmt->fetchAll();

    // Recurrents + their aliases (LEFT JOIN, group by recurrent)
    $stmt = $pdo->prepare(
        "SELECT r.id, r.title, ABS(r.amount) AS amount, r.due_date_day, r.expense_category_id,
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

    // Categories — both taxonomies, prompt tells the AI which to pick from
    // based on each draft's `kind`.
    $stmt = $pdo->prepare("SELECT id, name FROM expense_category WHERE user_id = ? AND deleted_ts IS NULL ORDER BY name");
    $stmt->execute([$user_id]);
    $expense_cats = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT id, name FROM income_category WHERE user_id = ? AND deleted_ts IS NULL ORDER BY name");
    $stmt->execute([$user_id]);
    $income_cats = $stmt->fetchAll();

    // Accounts (for currency detection — Gemini sees the user's wallets and
    // their currencies, which helps it pick `detected_currency` correctly).
    $stmt = $pdo->prepare(
        "SELECT id, name, currency FROM account WHERE user_id = ? AND deleted_ts IS NULL ORDER BY is_default DESC, name"
    );
    $stmt->execute([$user_id]);
    $accounts = $stmt->fetchAll();

    $context = [
        'today' => $today,
        'mode' => $mode,
        'existing_transactions_recent' => array_map(fn($p) => [
            'id' => $p['id'],
            'title' => $p['title'],
            'amount' => (float)$p['amount'],
            'due_date' => $p['due_date'],
            'is_paid' => (bool)$p['is_paid'],
        ], $existing),
        'recurrents' => $recurrents,
        'expense_categories' => array_map(fn($c) => $c['name'], $expense_cats),
        'income_categories' => array_map(fn($c) => $c['name'], $income_cats),
        'accounts' => array_map(fn($a) => ['name' => $a['name'], 'currency' => $a['currency']], $accounts),
    ];

    $parts = [];
    if ($mode === 'image') {
        foreach ($images as $img) {
            $parts[] = ['inlineData' => ['mimeType' => $img['mimeType'], 'data' => $img['data']]];
        }
    } else { // audio
        $parts[] = ['inlineData' => ['mimeType' => $audio['mimeType'], 'data' => $audio['data']]];
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

    // Post-process — separate name→id maps per taxonomy so we route based on
    // each draft's kind. Defaults to 'expense' for back-compat with prompts
    // that don't return a kind.
    $expense_cat_by_name = [];
    foreach ($expense_cats as $c) { $expense_cat_by_name[mb_strtolower($c['name'])] = $c['id']; }
    $income_cat_by_name = [];
    foreach ($income_cats as $c)  { $income_cat_by_name[mb_strtolower($c['name'])]  = $c['id']; }
    $existing_ids = array_column($existing, 'id');
    $recurrent_ids = array_column($recurrents, 'id');

    foreach ($drafts as &$d) {
        // Normalize kind. Gemini may return missing/garbage; clamp to expense.
        $d_kind = $d['kind'] ?? 'expense';
        if (!in_array($d_kind, ['expense', 'income'], true)) $d_kind = 'expense';
        $d['kind'] = $d_kind;

        $name = $d['suggested_category_name'] ?? null;
        $map = $d_kind === 'income' ? $income_cat_by_name : $expense_cat_by_name;
        $d['suggested_category_id'] = $name ? ($map[mb_strtolower($name)] ?? null) : null;

        // Existing-match + recurrent-match only apply to expenses (income has
        // no recurrents, and existing-match is keyed by expense titles in this
        // batch context — incomes get a fresh draft every time).
        if ($d_kind === 'income') {
            $d['existing_transaction_id'] = null;
            $d['recurrent_match_id'] = null;
            $d['recurrent_match_confidence'] = 'low';
        } else {
            if (!empty($d['existing_transaction_id']) && !in_array($d['existing_transaction_id'], $existing_ids, true)) {
                $d['existing_transaction_id'] = null;
            }
            if (!empty($d['recurrent_match_id']) && !in_array($d['recurrent_match_id'], $recurrent_ids, true)) {
                $d['recurrent_match_id'] = null;
            }

            // Server-side dedup belt: if Gemini missed an existing match
            if (empty($d['existing_transaction_id'])) {
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

                    $d['existing_transaction_id'] = $ex['id'];
                    break;
                }
            }
        }
    }
    unset($d);

    json_response([
        'drafts' => $drafts,
        'unreadable_screenshot_idxs' => $unreadable,
        'transcription' => $result['transcription'] ?? null,
        'meta' => [
            'mode' => $mode,
            'image_count' => $mode === 'image' ? count($images) : 0,
            'processing_ms' => $elapsed_ms,
            'model_used' => $gem['model_used'] ?? null,
            'models_tried' => $gem['tried'] ?? [],
        ],
    ]);
}

function build_prompt(array $context): string {
    $context_json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $today = $context['today'];
    $mode = $context['mode'] ?? 'image';

    $intro = $mode === 'audio'
        ? "Te paso un audio en español argentino donde el usuario describe UNO O MAS MOVIMIENTOS de plata (gastos o ingresos) que ya hizo o tiene pendientes. Tu objetivo: TRANSCRIBIR el audio completo en el campo 'transcription' y extraer CADA movimiento mencionado como un draft separado, clasificado como gasto (kind='expense') o ingreso (kind='income'). Un solo audio puede generar 1, 2, 5 o mas drafts segun cuantos items enumere el usuario. NO consolides varios items en uno; cada uno va por separado."
        : "Analiza las capturas de pantalla adjuntas (en orden, indices 0..N-1) y extrae TODAS las transacciones que aparezcan, clasificadas como gasto (kind='expense') o ingreso (kind='income'). Tu objetivo es ser EXHAUSTIVO: las capturas pueden tener listas densas (un mix de gastos e ingresos), recorre cada una de arriba a abajo y NO te saltes ninguna fila.";

    $source_idx_rule = $mode === 'audio'
        ? "- screenshot_idx: en modo audio dejalo en null (no aplica)."
        : "- screenshot_idx: indice 0..N-1 de la captura donde aparece la fila.";

    $exhaustividad = $mode === 'audio'
        ? <<<TXT
EXHAUSTIVIDAD:
- El audio puede listar varios movimientos seguidos ("gaste 1500 en el super, 800 en el cafe, cobre 50000 de Juan"). Cada uno es un draft (con su kind correspondiente).
- Si el usuario menciona el mismo item dos veces, devuelvelo una sola vez.
- Si el usuario habla de algo que NO es un movimiento de plata concreto (anecdota, recordatorio futuro sin monto), ignoralo.
TXT
        : <<<TXT
EXHAUSTIVIDAD:
- Cada captura puede contener 5-30 filas. Extrae CADA fila (gasto o ingreso) sin omitir ninguna.
- Las capturas pueden solaparse: la misma transaccion puede aparecer en varias. Usa duplicate_in_batch_idx para señalarlo, no la omitas en la primera aparicion.
- "Saldo del dia" NO es una transaccion, es un saldo. Ignoralo.
TXT;

    $unreadable_rule = $mode === 'audio'
        ? "AUDIO NO PROCESABLE:\n- Si el audio no describe ningun movimiento concreto (silencio, ruido, charla sin importes), devolve drafts=[] y dejalo asi. No uses unreadable_screenshot_idxs (queda vacio en modo audio)."
        : "CAPTURAS NO LEIBLES:\n- Si una captura no se puede leer o no muestra transacciones, agrega su indice a \"unreadable_screenshot_idxs\".";

    $transcription_rule = $mode === 'audio'
        ? "TRANSCRIPCION:\n- transcription: el texto completo de lo que dice el audio (en español argentino). Obligatorio en modo audio."
        : "TRANSCRIPCION:\n- transcription: dejalo en null (no aplica fuera de audio).";

    $parsing_rules = $mode === 'audio'
        ? <<<TXT
REGLAS DE PARSEO:
- kind: "expense" para gastos, "income" para ingresos. Ver clasificacion abajo.
- amount: numero positivo siempre (la magnitud). El sistema le pone signo segun kind. "mil quinientos" = 1500, "doce mil" = 12000.
- title: max 80 chars. Para gastos usa el comercio/concepto ("Coto", "cafe con juan", "alquiler"); para ingresos usa el emisor o concepto ("sueldo abril", "pago freelance Juan").
- date: YYYY-MM-DD. Si no menciona fecha, usa $today. "ayer" = $today − 1, "anteayer" = $today − 2, "el lunes" se interpreta respecto a $today.
- description: detalle adicional si el usuario lo dice (ej: "milanesa con gaseosa"), o null.
- suggested_category_name: una EXACTA de la lista correspondiente — "expense_categories" si kind=expense, "income_categories" si kind=income. NO inventes.
$source_idx_rule
TXT
        : <<<TXT
REGLAS DE PARSEO:
- kind: "expense" para gastos, "income" para ingresos. Ver clasificacion abajo.
- Argentina: el punto es separador de miles. "\$67.506" = 67506. "\$67.506,08" o "\$67.506⁰⁸" = 67506.08. Decimales pueden aparecer en superindice o tamaño chico al lado del monto principal.
- amount: SIEMPRE positivo (la magnitud). El sistema le pone signo segun kind.
- Si la transaccion no muestra año, asumi $today.
- title: max 80 chars. Usa el NOMBRE DEL DESTINATARIO/COMERCIO o EMISOR si existe (ej: "NAVARRO AMADEO ANDRES", "MINIMERCADO MAURI"). Si no, usa el concepto.
- date: YYYY-MM-DD. Buscala en el encabezado de la seccion de la fila.
- suggested_category_name: una EXACTA de la lista correspondiente — "expense_categories" si kind=expense, "income_categories" si kind=income. NO inventes.
$source_idx_rule
TXT;

    $income_rule = $mode === 'audio'
        ? <<<TXT
CLASIFICACION GASTO vs INGRESO:
- kind="expense" cuando el usuario gasto plata: "gaste", "pague", "compre", "transferi a", "le di plata a".
- kind="income" cuando el usuario recibio plata: "cobre", "me transfirieron", "me devolvieron", "sueldo", "pago recibido", "reembolso", "rendimientos".
- Si el usuario habla de algo sin signo claro, asumi gasto.
TXT
        : <<<TXT
CLASIFICACION GASTO vs INGRESO (CRITICO):

GASTO (kind="expense") — indicadores visuales:
- Texto en NEGRO o ROJO
- Signo "-" o "$ -" antes del monto
- Flecha hacia ABAJO o icono de tarjeta saliente
- Texto: "Transferencia enviada", "Pago", "Pago de servicio", "Pago con QR", "Compra", "Debito", "Extraccion"

INGRESO (kind="income") — indicadores visuales:
- Texto en VERDE
- Signo "+" o "$ +" antes del monto
- Flecha hacia ARRIBA o icono de entrada
- Texto: "Transferencia recibida", "Rendimientos", "Ingreso", "Cobro recibido", "Devolucion", "Reintegro", "Acreditacion", "Pago recibido", "Sueldo"

Si tenes duda, observa COLOR y SIGNO. Verde con "+" → income; negro/rojo con "-" → expense. NUNCA mezcles signos.
TXT;

    return <<<PROMPT
$intro

CONTEXTO DEL USUARIO:
$context_json

$income_rule

$parsing_rules

MONEDA:
- detected_currency: una de "ARS", "USD", "USDT". Detecta la moneda del gasto a partir de signos visuales o textuales: "USD", "U\$D", "u\$s", "dolares", "USDT", "tether" → no-ARS. Default a "ARS" si no hay señal explicita. La lista "accounts" muestra las cuentas del usuario con su moneda; si el gasto coincide con una cuenta no-ARS, usa esa moneda.

DEDUPLICACION Y MATCHING (solo aplica a gastos — drafts con kind=income siempre llevan existing_transaction_id=null y recurrent_match_id=null):
- existing_transaction_id: si este gasto ya esta en "existing_transactions_recent" (titulo similar + monto cercano + fecha cercana), poner el id; si no, null.
- recurrent_match_id: si el destinatario o concepto coincide con el "title" O CUALQUIERA de los "aliases" de un recurrent, poner el id de ese recurrent. EJEMPLO: si un recurrent tiene title "Clases de running" y aliases ["NAVARRO AMADEO ANDRES"], y la transaccion es "Transferencia enviada NAVARRO AMADEO ANDRES", DEBE matchear ese recurrent_id. El monto puede variar ±20% (no exigir match exacto en plata).
- recurrent_match_confidence: "high" si el destinatario coincide casi exacto con title/alias. "medium" si es similar. "low" si solo coincide parcialmente o por monto.
- duplicate_in_batch_idx: si esta misma transaccion ya aparece en un draft anterior de esta respuesta (mismo monto + fecha + destinatario), poner el indice del draft anterior; si no, null.
- Si una transaccion coincide con AMBOS (existing_transaction_id Y recurrent_match_id), prioriza existing_transaction_id (ya esta en la tabla).

$exhaustividad

$transcription_rule

$unreadable_rule
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
                        'kind' => ['type' => 'STRING', 'enum' => ['expense', 'income']],
                        'screenshot_idx' => ['type' => 'INTEGER', 'nullable' => true],
                        'title' => ['type' => 'STRING'],
                        'amount' => ['type' => 'NUMBER'],
                        'date' => ['type' => 'STRING'],
                        'description' => ['type' => 'STRING', 'nullable' => true],
                        'suggested_category_name' => ['type' => 'STRING', 'nullable' => true],
                        'existing_transaction_id' => ['type' => 'STRING', 'nullable' => true],
                        'recurrent_match_id' => ['type' => 'STRING', 'nullable' => true],
                        'recurrent_match_confidence' => ['type' => 'STRING', 'enum' => ['high', 'medium', 'low']],
                        'duplicate_in_batch_idx' => ['type' => 'INTEGER', 'nullable' => true],
                        'detected_currency' => ['type' => 'STRING', 'enum' => ['ARS', 'USD', 'USDT']],
                    ],
                    'required' => ['kind', 'title', 'amount', 'date', 'recurrent_match_confidence', 'detected_currency'],
                ],
            ],
            'unreadable_screenshot_idxs' => [
                'type' => 'ARRAY',
                'items' => ['type' => 'INTEGER'],
            ],
            'transcription' => ['type' => 'STRING', 'nullable' => true],
        ],
        'required' => ['drafts', 'unreadable_screenshot_idxs'],
    ];
}

// ──────────────────────────────────────────────────────────────────
// COMMIT
// ──────────────────────────────────────────────────────────────────

function handle_commit_transactions(PDO $pdo, string $user_id): void {
    $body = get_json_body();
    $rows = $body['rows'] ?? [];
    if (!is_array($rows) || empty($rows)) {
        json_error('rows is required and must be non-empty');
    }

    // Source defaults to 'ai-image' for back-compat with older clients that
    // didn't send this field. Whitelist the values the row label resolver knows.
    $source = $body['source'] ?? 'ai-image';
    if (!in_array($source, ['ai-image', 'ai-audio', 'ai-text', 'ai-pdf'], true)) {
        $source = 'ai-image';
    }

    $created = 0;
    $updated_recurrents = 0;
    $marked_paid = 0;
    $skipped = 0;
    $transaction_ids = [];

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
                $row_kind = $row['kind'] ?? 'expense';
                if (!in_array($row_kind, ['expense', 'income'], true)) {
                    throw new RuntimeException("Row $i: kind must be expense or income");
                }
                $id = bin2hex(random_bytes(14));
                $is_paid = !empty($row['is_paid']) ? 1 : 0;
                $paid_ts = $is_paid ? ($row['paid_ts'] ?? date('Y-m-d H:i:s')) : null;

                [$account_id, $currency] = resolve_account_and_currency(
                    $pdo, $user_id, $row['account_id'] ?? null, $row['currency'] ?? null
                );

                // Sign + category column routing follow the row's kind.
                $signed_amount = $row_kind === 'income'
                    ? abs((float)$row['amount'])
                    : -abs((float)$row['amount']);
                $expense_cat = $row_kind === 'expense' ? ($row['expense_category_id'] ?? null) : null;
                $income_cat  = $row_kind === 'income'  ? ($row['income_category_id']  ?? null) : null;

                $stmt = $pdo->prepare(
                    "INSERT INTO `transaction` (id, user_id, title, description, amount, currency, expense_category_id,
                     income_category_id, is_paid, paid_ts, recurrent_id, card_id, account_id, transaction_type, due_ts,
                     kind, source, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, 'reviewed')"
                );
                $stmt->execute([
                    $id, $user_id,
                    $row['title'],
                    $row['description'] ?? '',
                    $signed_amount,
                    $currency,
                    $expense_cat,
                    $income_cat,
                    $is_paid, $paid_ts,
                    $row['card_id'] ?? null,
                    $account_id,
                    $row['transaction_type'] ?? 'one-time',
                    $row['due_ts'] ?? null,
                    $row_kind,
                    $source,
                ]);
                $transaction_ids[] = $id;
                $created++;
                continue;
            }

            if ($action === 'mark_recurrent_paid') {
                $rid = $row['recurrent_id'] ?? '';
                if (empty($rid)) throw new RuntimeException("Row $i: recurrent_id required");

                $paid_ts = $row['paid_ts'] ?? date('Y-m-d H:i:s');
                // Frontend sends a positive magnitude; normalize to negative.
                $signed_amount = isset($row['amount']) ? -abs((float)$row['amount']) : null;

                $stmt = $pdo->prepare("SELECT * FROM recurrent WHERE id = ? AND user_id = ?");
                $stmt->execute([$rid, $user_id]);
                $r = $stmt->fetch();
                if (!$r) throw new RuntimeException("Row $i: recurrent $rid not found");

                $month = (new DateTime($paid_ts))->format('Y-m');
                $stmt = $pdo->prepare(
                    "SELECT id FROM `transaction`
                     WHERE user_id = ? AND recurrent_id = ?
                       AND transaction_type = 'recurrent'
                       AND DATE_FORMAT(due_ts, '%Y-%m') = ?
                     LIMIT 1"
                );
                $stmt->execute([$user_id, $rid, $month]);
                $current = $stmt->fetch();

                if ($current) {
                    $sql = "UPDATE `transaction` SET is_paid = 1, paid_ts = ?";
                    $params = [$paid_ts];
                    if ($signed_amount !== null) {
                        $sql .= ", amount = ?";
                        $params[] = $signed_amount;
                    }
                    $sql .= " WHERE id = ? AND user_id = ?";
                    $params[] = $current['id'];
                    $params[] = $user_id;
                    $pdo->prepare($sql)->execute($params);
                    $transaction_ids[] = $current['id'];
                    $marked_paid++;
                } else {
                    $id = bin2hex(random_bytes(14));
                    $dt = new DateTime($paid_ts);
                    $year = (int)$dt->format('Y');
                    $mon = (int)$dt->format('m');
                    $last_day = (int)$dt->format('t');
                    $day = min((int)$r['due_date_day'], $last_day);
                    $due_ts = sprintf('%04d-%02d-%02d 00:00:00', $year, $mon, $day);

                    // Recurrent.amount is already stored signed (negative).
                    $insert_amount = $signed_amount ?? (float)$r['amount'];

                    $stmt = $pdo->prepare(
                        "INSERT INTO `transaction` (id, user_id, title, description, amount, currency, expense_category_id,
                         is_paid, paid_ts, recurrent_id, card_id, account_id, transaction_type, due_ts, source, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 'recurrent', ?, ?, 'reviewed')"
                    );
                    $stmt->execute([
                        $id, $user_id,
                        $r['title'],
                        $r['description'] ?? '',
                        $insert_amount,
                        $r['currency'] ?? 'ARS',
                        $r['expense_category_id'],
                        $paid_ts,
                        $rid,
                        $r['card_id'],
                        $r['account_id'] ?? null,
                        $due_ts,
                        $source,
                    ]);
                    $transaction_ids[] = $id;
                    $created++;
                    $marked_paid++;
                }

                if (!empty($row['update_recurrent_amount']) && $signed_amount !== null) {
                    $pdo->prepare("UPDATE recurrent SET amount = ? WHERE id = ? AND user_id = ?")
                        ->execute([$signed_amount, $rid, $user_id]);
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
            'transaction_ids' => $transaction_ids,
        ]);
    } catch (\Throwable $e) {
        $pdo->rollBack();
        json_error('Commit failed: ' . $e->getMessage(), 500);
    }
}

// ──────────────────────────────────────────────────────────────────
// PARSE SINGLE
// One-shot AI input for the "Nuevo pago" form. Accepts text, image,
// audio or PDF and returns one structured draft to pre-fill the form.
// ──────────────────────────────────────────────────────────────────

function handle_parse_single(PDO $pdo, string $user_id): void {
    $body = get_json_body();
    $mode = $body['mode'] ?? '';

    if (!in_array($mode, ['text', 'image', 'audio', 'pdf'], true)) {
        json_error('mode must be one of: text, image, audio, pdf');
    }

    // kind drives prompt selection + which category table to load. Defaults to
    // expense for back-compat with clients that haven't been updated yet.
    $kind = $body['kind'] ?? 'expense';
    if (!in_array($kind, ['expense', 'income'], true)) {
        json_error('kind must be one of: expense, income');
    }

    $parts = [];
    $caption = trim((string)($body['caption'] ?? ''));
    $user_input = null;

    if ($mode === 'text') {
        $text = trim((string)($body['text'] ?? ''));
        if ($text === '') json_error('text is required for mode=text');
        if (mb_strlen($text) > 5000) json_error('text exceeds 5000 chars', 413);
        $user_input = $text;
    } else {
        $mimeType = $body['mimeType'] ?? '';
        $data = $body['data'] ?? '';
        if (!$mimeType || !$data) json_error('mimeType and data are required');

        $size = strlen($data);
        if ($mode === 'image') {
            if (!str_starts_with($mimeType, 'image/')) json_error('mimeType must be image/*');
            if ($size > 6 * 1024 * 1024) json_error('image exceeds 6MB (base64)', 413);
        } elseif ($mode === 'pdf') {
            if ($mimeType !== 'application/pdf') json_error('mimeType must be application/pdf');
            if ($size > 10 * 1024 * 1024) json_error('pdf exceeds 10MB (base64)', 413);
        } elseif ($mode === 'audio') {
            if (!str_starts_with($mimeType, 'audio/')) json_error('mimeType must be audio/*');
            if ($size > 8 * 1024 * 1024) json_error('audio exceeds 8MB (base64)', 413);
        }

        $parts[] = ['inlineData' => ['mimeType' => $mimeType, 'data' => $data]];
        $user_input = $caption !== '' ? $caption : null;
    }

    $api_key = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: '';
    if (!$api_key) json_error('GEMINI_API_KEY not configured', 500);

    $tz = new DateTimeZone('America/Argentina/Buenos_Aires');
    $today = (new DateTime('now', $tz))->format('Y-m-d');

    // Recurrents only matter for expense parsing (no income recurrents in
    // scope). For income, we skip the SELECT entirely and pass empty arrays
    // through the prompt context.
    if ($kind === 'expense') {
        $stmt = $pdo->prepare(
            "SELECT r.id, r.title, ABS(r.amount) AS amount, r.expense_category_id,
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
                'aliases' => $r['aliases'] ? array_values(array_filter(explode("\n", $r['aliases']))) : [],
            ];
        }, $recurrents_raw);
    } else {
        $recurrents_raw = [];
        $recurrents = [];
    }

    // Categories from the right taxonomy.
    $cat_table = $kind === 'income' ? 'income_category' : 'expense_category';
    $stmt = $pdo->prepare("SELECT id, name FROM `$cat_table` WHERE user_id = ? AND deleted_ts IS NULL ORDER BY name");
    $stmt->execute([$user_id]);
    $categories = $stmt->fetchAll();

    // Accounts (for currency detection)
    $stmt = $pdo->prepare(
        "SELECT id, name, currency FROM account WHERE user_id = ? AND deleted_ts IS NULL ORDER BY is_default DESC, name"
    );
    $stmt->execute([$user_id]);
    $accounts = $stmt->fetchAll();

    $context = [
        'today' => $today,
        'recurrents' => $recurrents,
        'categories' => array_map(fn($c) => $c['name'], $categories),
        'accounts' => array_map(fn($a) => ['name' => $a['name'], 'currency' => $a['currency']], $accounts),
    ];

    $parts[] = ['text' => build_single_prompt($context, $mode, $user_input, $kind)];

    $start = microtime(true);
    $handler = new GeminiHandler($api_key);
    $gem = $handler->generateContent($parts, [
        'maxOutputTokens' => 4096,
        'temperature' => 0.2,
        'responseSchema' => build_single_schema(),
    ]);
    $elapsed_ms = (int)((microtime(true) - $start) * 1000);

    if (!empty($gem['error'])) {
        json_error('Gemini call failed: ' . $gem['error'] . ' (tried: ' . implode(', ', $gem['tried'] ?? []) . ')', 502);
    }

    $result = $gem['data'];
    $unreadable = !empty($result['unreadable']);
    $reason = $result['reason'] ?? null;
    $draft = $result['draft'] ?? null;
    $matched_recurrent = null;

    if (is_array($draft)) {
        // Resolve category name → id (case-insensitive)
        $cat_by_name = [];
        foreach ($categories as $c) {
            $cat_by_name[mb_strtolower($c['name'])] = $c['id'];
        }
        $name = $draft['suggested_category_name'] ?? null;
        $draft['suggested_category_id'] = $name ? ($cat_by_name[mb_strtolower($name)] ?? null) : null;

        // Validate recurrent_match_id and resolve the full row from raw rows (so we keep expense_category_id)
        if (!empty($draft['recurrent_match_id'])) {
            $matched_raw = null;
            foreach ($recurrents_raw as $rr) {
                if ($rr['id'] === $draft['recurrent_match_id']) { $matched_raw = $rr; break; }
            }
            if ($matched_raw) {
                $matched_recurrent = [
                    'id' => $matched_raw['id'],
                    'title' => $matched_raw['title'],
                    'amount' => (float)$matched_raw['amount'],
                    'expense_category_id' => $matched_raw['expense_category_id'],
                ];
            } else {
                $draft['recurrent_match_id'] = null;
                $draft['recurrent_match_confidence'] = 'low';
            }
        }

        // Default date to today when missing or malformed
        if (empty($draft['date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $draft['date'])) {
            $draft['date'] = $today;
        }

        // Ensure recipient is always an object (Gemini may omit it)
        if (!isset($draft['recipient']) || !is_array($draft['recipient'])) {
            $draft['recipient'] = ['name' => null, 'cbu' => null, 'alias' => null, 'bank' => null];
        }
    }

    // Persist the original artifact (image/audio/pdf) to DO Spaces if Gemini
    // returned anything usable. Text-mode inputs have nothing to store. The
    // artifact is nice-to-have — failures here are logged but don't block the
    // response, since the user can still review/save the draft without it.
    $ai_artifact = null;
    if ($mode !== 'text' && is_array($draft) && !$unreadable) {
        $ai_artifact = upload_ai_artifact($user_id, $mode, $body['mimeType'] ?? '', $body['data'] ?? '');
    }

    json_response([
        'draft' => $draft,
        'matched_recurrent' => $matched_recurrent,
        'ai_artifact' => $ai_artifact,
        'unreadable' => $unreadable,
        'reason' => $reason,
        'kind' => $kind,
        'meta' => [
            'mode' => $mode,
            'kind' => $kind,
            'processing_ms' => $elapsed_ms,
            'model_used' => $gem['model_used'] ?? null,
            'models_tried' => $gem['tried'] ?? [],
        ],
    ]);
}

// ──────────────────────────────────────────────────────────────────
// AI artifact storage (DO Spaces, ACL=private)
// ──────────────────────────────────────────────────────────────────

function upload_ai_artifact(string $user_id, string $mode, string $mimeType, string $base64): ?array {
    $spaces_conf = $GLOBALS['mangos_config']['spaces'] ?? null;
    if (!$spaces_conf || empty($spaces_conf['key']) || $spaces_conf['key'] === 'CHANGE_ME') {
        // Spaces not configured — silently skip. Useful for dev environments.
        return null;
    }
    if (!$mimeType || !$base64) return null;

    $bytes = base64_decode($base64, true);
    if ($bytes === false) {
        error_log('[ai] artifact base64 decode failed');
        return null;
    }

    $ext = ai_artifact_extension($mode, $mimeType);
    $uuid = bin2hex(random_bytes(12));

    require_once __DIR__ . '/../handlers/SpacesHandler.php';
    try {
        $spaces = new SpacesHandler($spaces_conf);
        $key = $spaces->artifactKey($user_id, $uuid, $ext);
        if (!$spaces->put($key, $bytes, $mimeType)) return null;
        return ['path' => $key, 'mime' => $mimeType];
    } catch (\Throwable $e) {
        error_log('[ai] artifact upload error: ' . $e->getMessage());
        return null;
    }
}

function ai_artifact_extension(string $mode, string $mimeType): string {
    $map = [
        'image/jpeg'      => 'jpg',
        'image/jpg'       => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'image/heic'      => 'heic',
        'image/heif'      => 'heif',
        'audio/webm'      => 'webm',
        'audio/ogg'       => 'ogg',
        'audio/mp4'       => 'm4a',
        'audio/x-m4a'     => 'm4a',
        'audio/mpeg'      => 'mp3',
        'audio/mp3'       => 'mp3',
        'audio/wav'       => 'wav',
        'audio/x-wav'     => 'wav',
        'audio/flac'      => 'flac',
        'audio/aac'       => 'aac',
        'application/pdf' => 'pdf',
    ];
    $base = strtolower(trim(explode(';', $mimeType, 2)[0]));
    if (isset($map[$base])) return $map[$base];
    return match ($mode) {
        'image' => 'bin', 'audio' => 'audio', 'pdf' => 'pdf', default => 'bin',
    };
}

// ──────────────────────────────────────────────────────────────────
// DISCARD ARTIFACT (called by frontend when AI modal closes without save)
// ──────────────────────────────────────────────────────────────────

function handle_discard_artifact(string $user_id): void {
    if (method() !== 'POST' && method() !== 'DELETE') json_error('Method not allowed', 405);
    $body = get_json_body();
    $path = $body['path'] ?? ($_GET['path'] ?? '');
    if (!is_string($path) || $path === '') json_error('path is required');

    $spaces_conf = $GLOBALS['mangos_config']['spaces'] ?? null;
    if (!$spaces_conf || empty($spaces_conf['key']) || $spaces_conf['key'] === 'CHANGE_ME') {
        // Nothing to delete; treat as a no-op.
        json_response(['deleted' => false, 'reason' => 'spaces not configured']);
    }

    // Authorization: the path MUST live under this user's namespace, otherwise
    // a discard call could nuke someone else's artifact.
    $expectedPrefix = rtrim($spaces_conf['prefix'], '/') . '/ai-uploads/' . $user_id . '/';
    if (!str_starts_with($path, $expectedPrefix)) {
        json_error('Path outside user namespace', 403);
    }

    require_once __DIR__ . '/../handlers/SpacesHandler.php';
    $spaces = new SpacesHandler($spaces_conf);
    $ok = $spaces->delete($path);
    json_response(['deleted' => $ok]);
}

function build_single_prompt(array $context, string $mode, ?string $user_input, string $kind = 'expense'): string {
    if ($kind === 'income') {
        return build_single_income_prompt($context, $mode, $user_input);
    }
    return build_single_expense_prompt($context, $mode, $user_input);
}

function build_single_expense_prompt(array $context, string $mode, ?string $user_input): string {
    $context_json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $today = $context['today'];

    $intro = match ($mode) {
        'text' => "El usuario describio un gasto en texto libre (en español argentino). Texto del usuario:\n\"\"\"\n$user_input\n\"\"\"",
        'image' => "Adjunto una imagen (foto de ticket, comprobante de transferencia, captura de pago)." . ($user_input ? " El usuario ademas escribio:\n\"\"\"\n$user_input\n\"\"\"" : " El usuario no agrego texto."),
        'pdf' => "Adjunto un PDF (comprobante o ticket)." . ($user_input ? " El usuario ademas escribio:\n\"\"\"\n$user_input\n\"\"\"" : " El usuario no agrego texto."),
        'audio' => "Adjunto un audio en español argentino describiendo un gasto. Transcribilo COMPLETO en el campo 'transcription' antes de extraer los datos." . ($user_input ? " El usuario ademas escribio:\n\"\"\"\n$user_input\n\"\"\"" : ""),
    };

    return <<<PROMPT
Tu tarea: extraer UN solo GASTO (egreso de dinero) y devolverlo estructurado para precargar el formulario "Nuevo pago" de la app.

$intro

CONTEXTO DEL USUARIO:
$context_json

REGLAS DE PARSEO:
- Argentina: el punto separa miles. "\$67.506" = 67506. "\$1.234,56" = 1234.56. Decimales pueden aparecer en superindice o tamaño chico.
- amount: numero (no string). Si una imagen muestra un ticket con varios items, suma sus subtotales para el total.
- title: max 80 chars. Si hay destinatario o comercio, usalo (ej: "NAVARRO AMADEO ANDRES", "Coto"). Si es texto libre tipo "cafe con juan", usa eso.
- date: YYYY-MM-DD. Si no aparece, usa $today. "ayer", "anteayer", nombres de dias se interpretan respecto a $today.
- description: detalle util adicional (ej. "milanesa + gaseosa", concepto de la transferencia), o null si no hay nada relevante.
- is_paid: true por defecto (la mayoria de los inputs son gastos ya hechos). Solo false si el texto/audio dice claramente que es un gasto FUTURO ("la semana que viene", "voy a pagar", "tengo que pagar").
- suggested_category_name: una EXACTA de la lista "categories" o null. NO inventes categorias.
- recipient: completar SOLO si es una transferencia bancaria con datos del destinatario (CBU/CVU, alias, banco). Para tickets de comercio, compras casuales o gastos en efectivo, todos los campos en null.

DETECCION DE GASTO vs INGRESO (rechazar ingresos):
- GASTO (extraer): "Transferencia enviada", "Pago", "Compra", "Debito", "Extraccion", monto en negro/rojo, signo "-".
- INGRESO (rechazar): "Transferencia recibida", "Cobro", "Acreditacion", "Rendimientos", "Devolucion", monto en verde, signo "+".
- Si el input es claramente un ingreso, devuelve unreadable=true con reason="Es un ingreso, no un gasto".

MONEDA:
- detected_currency: una de "ARS", "USD", "USDT". Detecta la moneda a partir de signos textuales/visuales ("USD", "U\$D", "u\$s", "dolares", "USDT", "tether"). Default a "ARS" si no hay indicio explicito.

MATCHING DE RECURRENTES:
- Si el destinatario o concepto coincide con el "title" o cualquiera de los "aliases" de algun recurrent, devolve recurrent_match_id con su id.
- recurrent_match_confidence: "high" si coincide casi exacto. "medium" si es similar. "low" si solo coincide parcial o por monto.
- El monto puede variar ±20%, no exigir match exacto en plata.
- Si no hay match, recurrent_match_id=null y recurrent_match_confidence="low".

TRANSCRIPCION:
- transcription: en modo audio, el texto completo de lo que se dice. En cualquier otro modo, null.

CASOS NO PROCESABLES:
- Si la imagen/PDF no muestra un gasto identificable, o el audio/texto no describe un gasto concreto, devolve unreadable=true con un reason corto.
- En esos casos, los campos de draft pueden ir en null/0/"" pero la estructura debe estar completa.
PROMPT;
}

function build_single_income_prompt(array $context, string $mode, ?string $user_input): string {
    $context_json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $today = $context['today'];

    $intro = match ($mode) {
        'text' => "El usuario describio un INGRESO de dinero en texto libre (en español argentino). Texto del usuario:\n\"\"\"\n$user_input\n\"\"\"",
        'image' => "Adjunto una imagen (comprobante de transferencia recibida, recibo de sueldo, captura de acreditacion)." . ($user_input ? " El usuario ademas escribio:\n\"\"\"\n$user_input\n\"\"\"" : " El usuario no agrego texto."),
        'pdf' => "Adjunto un PDF (recibo de sueldo, factura cobrada, comprobante de cobro)." . ($user_input ? " El usuario ademas escribio:\n\"\"\"\n$user_input\n\"\"\"" : " El usuario no agrego texto."),
        'audio' => "Adjunto un audio en español argentino describiendo un ingreso de plata. Transcribilo COMPLETO en el campo 'transcription' antes de extraer los datos." . ($user_input ? " El usuario ademas escribio:\n\"\"\"\n$user_input\n\"\"\"" : ""),
    };

    return <<<PROMPT
Tu tarea: extraer UN solo INGRESO (entrada de dinero) y devolverlo estructurado para precargar el formulario "Nuevo ingreso" de la app.

$intro

CONTEXTO DEL USUARIO:
$context_json

REGLAS DE PARSEO:
- Argentina: el punto separa miles. "\$67.506" = 67506. "\$1.234,56" = 1234.56. Decimales pueden aparecer en superindice o tamaño chico.
- amount: numero positivo (no string). Para ingresos siempre devolve la magnitud sin signo.
- title: max 80 chars. Si hay un emisor o cliente, usalo (ej: "Empresa SRL", "Pago freelance Juan"). Si es texto libre tipo "sueldo abril", usa eso.
- date: YYYY-MM-DD. Si no aparece, usa $today. "ayer", "anteayer", nombres de dias se interpretan respecto a $today.
- description: detalle util adicional (ej. concepto de la transferencia, mes del sueldo), o null si no hay nada relevante.
- is_paid: true por defecto (la mayoria de los ingresos ya estan acreditados). Solo false si el texto/audio dice claramente que es un cobro PENDIENTE ("me van a depositar", "todavia no entro").
- suggested_category_name: una EXACTA de la lista "categories" (Salario, Freelance, Reembolso, etc) o null. NO inventes categorias.
- recipient: para ingresos generalmente sera null en todos los campos. Solo completar si hay datos bancarios EXPLICITOS del emisor (CBU/CVU, alias, banco) que valga la pena guardar.

DETECCION DE INGRESO vs GASTO (rechazar gastos):
- INGRESO (extraer): "Transferencia recibida", "Cobro", "Acreditacion", "Rendimientos", "Devolucion", "Sueldo", "Pago recibido", monto en verde, signo "+".
- GASTO (rechazar): "Transferencia enviada", "Pago a", "Compra", "Debito", "Extraccion", monto en negro/rojo, signo "-".
- Si el input es claramente un gasto, devuelve unreadable=true con reason="Es un gasto, no un ingreso".

MONEDA:
- detected_currency: una de "ARS", "USD", "USDT". Detecta la moneda a partir de signos textuales/visuales ("USD", "U\$D", "u\$s", "dolares", "USDT", "tether"). Default a "ARS" si no hay indicio explicito.

MATCHING DE RECURRENTES:
- No hay recurrentes de ingreso por ahora. Devolve siempre recurrent_match_id=null y recurrent_match_confidence="low".

TRANSCRIPCION:
- transcription: en modo audio, el texto completo de lo que se dice. En cualquier otro modo, null.

CASOS NO PROCESABLES:
- Si la imagen/PDF no muestra un ingreso identificable, o el audio/texto no describe un ingreso concreto, devolve unreadable=true con un reason corto.
- En esos casos, los campos de draft pueden ir en null/0/"" pero la estructura debe estar completa.
PROMPT;
}

function build_single_schema(): array {
    return [
        'type' => 'OBJECT',
        'properties' => [
            'draft' => [
                'type' => 'OBJECT',
                'properties' => [
                    'title' => ['type' => 'STRING', 'nullable' => true],
                    'amount' => ['type' => 'NUMBER', 'nullable' => true],
                    'date' => ['type' => 'STRING', 'nullable' => true],
                    'description' => ['type' => 'STRING', 'nullable' => true],
                    'is_paid' => ['type' => 'BOOLEAN'],
                    'suggested_category_name' => ['type' => 'STRING', 'nullable' => true],
                    'recipient' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'name'  => ['type' => 'STRING', 'nullable' => true],
                            'cbu'   => ['type' => 'STRING', 'nullable' => true],
                            'alias' => ['type' => 'STRING', 'nullable' => true],
                            'bank'  => ['type' => 'STRING', 'nullable' => true],
                        ],
                    ],
                    'recurrent_match_id' => ['type' => 'STRING', 'nullable' => true],
                    'recurrent_match_confidence' => ['type' => 'STRING', 'enum' => ['high', 'medium', 'low']],
                    'detected_currency' => ['type' => 'STRING', 'enum' => ['ARS', 'USD', 'USDT']],
                    'transcription' => ['type' => 'STRING', 'nullable' => true],
                ],
                'required' => ['is_paid', 'recurrent_match_confidence', 'detected_currency'],
            ],
            'unreadable' => ['type' => 'BOOLEAN'],
            'reason' => ['type' => 'STRING', 'nullable' => true],
        ],
        'required' => ['draft', 'unreadable'],
    ];
}
