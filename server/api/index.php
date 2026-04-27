<?php
/**
 * Single entry point for the API.
 *
 * Routes:
 *   /api/categories    → categories.php
 *   /api/payments      → payments.php
 *   /api/recurrents    → recurrents.php
 *   /api/templates     → templates.php
 *   /api/cards         → card.php
 *
 * Usage with PHP built-in server:
 *   php -S localhost:8000 api/index.php
 *
 * Nginx: rewrite all /api/* requests to api/index.php
 */

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

// ── Auth middleware ──────────────────────────────
require __DIR__ . '/../middleware/auth.php';
$user_id = require_auth();

// ── Router ───────────────────────────────────────
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = '/' . trim($uri, '/');

// Strip /api prefix if present
$route = preg_replace('#^/api#', '', $route);
$route = '/' . trim($route, '/');

switch ($route) {
    case '/categories':
        require __DIR__ . '/categories.php';
        break;

    case '/payments':
        require __DIR__ . '/payments.php';
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

    default:
        json_error('Not found', 404);
}
