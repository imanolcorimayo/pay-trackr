<?php
/**
 * mangos — single entry point.
 *
 * Dev server: php -S localhost:3000 index.php
 */

require __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/includes/config.php';

// Serve static assets directly in dev server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('/\.(css|js|svg|png|jpg|ico|woff2?)$/', $uri)) {
    $file = __DIR__ . $uri;
    if (is_file($file)) return false;
}

// Parse route
$route = '/' . trim($uri, '/');
if ($route === '/') $route = '/dashboard';

// Buffer page content
ob_start();
require __DIR__ . '/router.php';
$pageContent = ob_get_clean();

// Wrap in layout
require __DIR__ . '/includes/header.php';
echo $pageContent;
require __DIR__ . '/includes/footer.php';
