<?php
/**
 * Shared API configuration.
 * - DB connection (PDO) — reads creds from /server/config.php
 * - JSON response helpers
 * - .env loader
 */

// ── .env loader ──────────────────────────────────
function load_env(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $k = trim($parts[0]);
        $v = trim($parts[1], " \t\"'");
        if ($k !== '' && !isset($_ENV[$k])) {
            $_ENV[$k] = $v;
            putenv("$k=$v");
        }
    }
}
load_env(__DIR__ . '/../.env');

// ── DB ───────────────────────────────────────────
$shared = require __DIR__ . '/../config.php';
$db_conf = $shared['db'];
// Expose the full config to handlers that need other sections (e.g. 'spaces').
// Endpoints reach it via `global $shared;` since require executes in the
// includer's scope and the variable becomes part of the request's global state.
$GLOBALS['mangos_config'] = $shared;

$pdo = new PDO(
    "mysql:host={$db_conf['host']};dbname={$db_conf['name']};charset=utf8mb4",
    $db_conf['user'],
    $db_conf['pass'],
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Pin the session timezone so MySQL NOW()/CURDATE()/etc. always
        // return Argentina time regardless of the DB server's system tz.
        // Argentina is a fixed UTC-3 (no DST since 2009), so the offset is
        // safe and doesn't depend on named-tz tables being loaded.
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-03:00'",
    ]
);

// ── Helpers ──────────────────────────────────────
function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400): void {
    json_response(['error' => $message], $status);
}

function get_json_body(): array {
    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}

function method(): string {
    return $_SERVER['REQUEST_METHOD'];
}
