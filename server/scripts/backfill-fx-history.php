<?php
/**
 * Backfill / refresh historical USD blue rates from argentinadatos.com.
 *
 * Usage: php server/scripts/backfill-fx-history.php
 *
 * Source: https://api.argentinadatos.com/v1/cotizaciones/dolares/blue
 * Returns the full daily series since ~2011 as a JSON array of:
 *   { fecha: "YYYY-MM-DD", compra: 1000, venta: 1050, ... }
 *
 * Idempotent: rows are upserted by (currency_code, date), so re-running
 * just refreshes the most recent days. Safe to schedule daily via cron.
 *
 * Errors are non-fatal — if the remote is down, the script exits 1 but
 * leaves whatever was already in fx_rate_history untouched.
 */

$config = require __DIR__ . '/../config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
    $db['user'],
    $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

const SOURCE_URL = 'https://api.argentinadatos.com/v1/cotizaciones/dolares/blue';

echo "[fx-backfill] Fetching " . SOURCE_URL . " ...\n";

$ctx = stream_context_create([
    'http' => [
        'timeout' => 30,
        'header'  => "User-Agent: mangos/1.0 (fx-backfill)\r\n",
        'ignore_errors' => true,
    ],
]);
$body = @file_get_contents(SOURCE_URL, false, $ctx);

if ($body === false) {
    fwrite(STDERR, "[fx-backfill] Fetch failed (network error)\n");
    exit(1);
}

// $http_response_header is automatically populated by file_get_contents
$status_line = $http_response_header[0] ?? '';
if (!preg_match('#HTTP/\S+\s+200\b#', $status_line)) {
    fwrite(STDERR, "[fx-backfill] Fetch failed: $status_line\n");
    exit(1);
}

$rows = json_decode($body, true);
if (!is_array($rows) || empty($rows)) {
    fwrite(STDERR, "[fx-backfill] Unexpected response shape\n");
    exit(1);
}

echo "[fx-backfill] Got " . count($rows) . " daily rows. Upserting...\n";

$stmt = $pdo->prepare(
    "INSERT INTO fx_rate (currency_code, `date`, rate_to_ars, source)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       rate_to_ars = VALUES(rate_to_ars),
       source      = VALUES(source),
       fetched_ts  = CURRENT_TIMESTAMP"
);

$upserted = 0;
$skipped  = 0;
$pdo->beginTransaction();
try {
    foreach ($rows as $r) {
        $date   = $r['fecha']  ?? null;
        $compra = isset($r['compra']) ? (float)$r['compra'] : null;
        $venta  = isset($r['venta'])  ? (float)$r['venta']  : null;

        if (!$date || !$compra || !$venta) { $skipped++; continue; }

        $rate = round(($compra + $venta) / 2, 4);
        if ($rate <= 0) { $skipped++; continue; }

        $stmt->execute(['USD', $date, $rate, 'blue']);
        $upserted++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "[fx-backfill] DB error: " . $e->getMessage() . "\n");
    exit(1);
}

$count = $pdo->query("SELECT COUNT(*) FROM fx_rate WHERE currency_code = 'USD'")->fetchColumn();
$range = $pdo->query("SELECT MIN(`date`) AS min_d, MAX(`date`) AS max_d FROM fx_rate WHERE currency_code = 'USD'")->fetch(PDO::FETCH_ASSOC);

echo "[fx-backfill] Done. Upserted $upserted rows, skipped $skipped.\n";
echo "[fx-backfill] Total rows for USD: $count (from {$range['min_d']} to {$range['max_d']})\n";
