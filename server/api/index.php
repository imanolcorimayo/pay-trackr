<?php
/**
 * Single entry point for the API.
 *
 * Routes:
 *   /api/categories               → categories.php
 *   /api/transactions             → transactions.php
 *   /api/recurrents               → recurrents.php
 *   /api/templates                → templates.php
 *   /api/cards                    → card.php
 *   /api/accounts                 → accounts.php
 *   /api/transfers                → transfers.php
 *   /api/fx-rates                 → fx.php
 *   /api/ai/parse-transactions    → ai.php
 *   /api/ai/commit-transactions   → ai.php
 *   /api/ai/parse-single          → ai.php
 *   /api/ai/discard-artifact      → ai.php
 *   /api/transactions/artifact    → transactions.php (private proxy)
 *
 * Usage with PHP built-in server:
 *   php -S localhost:8000 api/index.php
 *
 * Nginx: rewrite all /api/* requests to api/index.php
 */

// ── Timezone ─────────────────────────────────────
// All API timestamps live in Argentina time. Without this, PHP's date() falls
// back to the host's tz (UTC on the droplet) and paid_ts lands on the wrong
// day for late-evening saves. The MySQL session tz is pinned alongside in
// config.php so NOW()/CURDATE() match.
date_default_timezone_set('America/Argentina/Buenos_Aires');

// ── Config (DB + helpers) ────────────────────────
require __DIR__ . '/config.php';

// ── CORS ─────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (method() === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Route resolution ─────────────────────────────
// Done before auth so cron-only routes can short-circuit and use the shared
// secret instead of requiring a Firebase user token.
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = '/' . trim($uri, '/');
$route = preg_replace('#^/api#', '', $route);
$route = '/' . trim($route, '/');

$cron_routes = [
    '/notifications/daily'      => 'cron-daily',
    '/notifications/weekly'     => 'cron-weekly',
    '/notifications/cron-test'  => 'cron-test',
];

// ── Auth middleware ──────────────────────────────
if (isset($cron_routes[$route])) {
    $expected = $GLOBALS['mangos_config']['cron_secret'] ?? '';
    $given = $_SERVER['HTTP_X_CRON_SECRET'] ?? '';
    if (!$expected || !hash_equals($expected, $given)) {
        json_error('Unauthorized', 401);
    }
    $user_id = null; // cron routes operate across all users
} else {
    require __DIR__ . '/../middleware/auth.php';
    $user_id = require_auth();
}

// Wrap dispatch so any uncaught Throwable (PDOException, schema constraint
// violations, etc.) becomes a structured json_error rather than a raw 500
// with an empty body — otherwise the client sees no error and the form
// loses its state.
try {
    switch ($route) {
        case '/categories':
            require __DIR__ . '/categories.php';
            break;

        case '/transactions':
            require __DIR__ . '/transactions.php';
            break;

        case '/recurrents':
            require __DIR__ . '/recurrents.php';
            break;

        case '/templates':
            require __DIR__ . '/templates.php';
            break;

        case '/cards':
            require __DIR__ . '/card.php';
            break;

        case '/accounts':
            require __DIR__ . '/accounts.php';
            break;

        case '/transfers':
            require __DIR__ . '/transfers.php';
            break;

        case '/fx-rates':
            $fx_action = 'rates';
            require __DIR__ . '/fx.php';
            break;

        case '/fx-history':
            $fx_action = 'history';
            require __DIR__ . '/fx.php';
            break;

        case '/ai/parse-transactions':
            $ai_action = 'parse-transactions';
            require __DIR__ . '/ai.php';
            break;

        case '/ai/commit-transactions':
            $ai_action = 'commit-transactions';
            require __DIR__ . '/ai.php';
            break;

        case '/ai/parse-single':
            $ai_action = 'parse-single';
            require __DIR__ . '/ai.php';
            break;

        case '/ai/discard-artifact':
            $ai_action = 'discard-artifact';
            require __DIR__ . '/ai.php';
            break;

        case '/ai/preview-artifact':
            $ai_action = 'preview-artifact';
            require __DIR__ . '/ai.php';
            break;

        case '/transactions/artifact':
            $transactions_action = 'artifact';
            require __DIR__ . '/transactions.php';
            break;

        case '/notifications/prefs':
            $notif_action = 'prefs';
            require __DIR__ . '/notifications.php';
            break;

        case '/notifications/subscribe':
            $notif_action = 'subscribe';
            require __DIR__ . '/notifications.php';
            break;

        case '/notifications/unsubscribe':
            $notif_action = 'unsubscribe';
            require __DIR__ . '/notifications.php';
            break;

        case '/notifications/test-push':
            $notif_action = 'test-push';
            require __DIR__ . '/notifications.php';
            break;

        case '/notifications/daily':
            $notif_action = 'cron-daily';
            require __DIR__ . '/notifications.php';
            break;

        case '/notifications/cron-test':
            $notif_action = 'cron-test';
            require __DIR__ . '/notifications.php';
            break;

        default:
            json_error('Not found', 404);
    }
} catch (Throwable $e) {
    error_log('[api] uncaught ' . get_class($e) . ' on ' . $route . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    if (!headers_sent()) {
        json_error('Server error: ' . $e->getMessage(), 500);
    }
}
