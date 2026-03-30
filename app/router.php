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

    case '/pagos':
        $pageTitle = 'Pagos';
        require __DIR__ . '/pages/payments.php';
        break;

    case '/categorias':
        $pageTitle = 'Categorias';
        require __DIR__ . '/pages/categories.php';
        break;

    default:
        http_response_code(404);
        $pageTitle = '404';
        require __DIR__ . '/pages/404.php';
        break;
}
