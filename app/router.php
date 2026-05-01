<?php
switch ($route) {
    case '/dashboard':
        $pageTitle = 'Dashboard';
        require __DIR__ . '/pages/dashboard.php';
        break;

    case '/login':
        $pageTitle = 'Iniciar sesion';
        $layout = 'minimal';
        require __DIR__ . '/pages/login.php';
        break;

    case '/fijos':
        $pageTitle = 'Gastos Fijos';
        require __DIR__ . '/pages/fixed.php';
        break;

    case '/movimientos':
        $pageTitle = 'Movimientos';
        require __DIR__ . '/pages/movimientos.php';
        break;

    case '/categorias':
        $pageTitle = 'Categorias';
        require __DIR__ . '/pages/categories.php';
        break;

    case '/tarjetas':
        $pageTitle = 'Tarjetas';
        require __DIR__ . '/pages/cards.php';
        break;

    case '/cuentas':
        $pageTitle = 'Cuentas';
        require __DIR__ . '/pages/cuentas.php';
        break;

    case '/analisis':
        $pageTitle = 'Analisis';
        require __DIR__ . '/pages/analytics.php';
        break;

    case '/capturar':
        $pageTitle = 'Carga masiva';
        require __DIR__ . '/pages/capture.php';
        break;

    case '/notificaciones':
        $pageTitle = 'Notificaciones';
        require __DIR__ . '/pages/notifications.php';
        break;

    default:
        http_response_code(404);
        $pageTitle = '404';
        require __DIR__ . '/pages/404.php';
        break;
}
