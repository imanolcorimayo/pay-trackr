<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#FFFBF5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="mangos">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?= ($pageTitle ?? 'Dashboard') . ' — mangos' ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('/assets/img/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('/assets/img/favicon-32.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('/assets/img/apple-touch-icon.png') ?>">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('/assets/css/output.css') ?>">
    <script>
        window.MANGOS_CONFIG = <?= json_encode($config['firebase']) ?>;
        window.MANGOS_API_URL = <?= json_encode($config['api_url']) ?>;
    </script>
    <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-auth-compat.js"></script>
    <script src="<?= asset('/assets/js/auth.js') ?>"></script>
    <script src="<?= asset('/assets/js/api.js') ?>"></script>
    <script src="<?= asset('/assets/js/app.js') ?>"></script>
</head>
<body class="min-h-screen">

<?php $isMinimal = isset($layout) && $layout === 'minimal'; ?>

<?php if (!$isMinimal): ?>
<?php
$primaryNav = [
    ['/',          'Inicio',    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
    ['/movimientos', 'Movimientos', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>'],
    ['/fijos',     'Fijos',     '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>'],
];
$secondaryNav = [
    ['/categorias', 'Categorias', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>'],
    ['/tarjetas',   'Tarjetas',   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h.01M11 15h2M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'],
    ['/cuentas',    'Cuentas',    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10l9-6 9 6v9a2 2 0 01-2 2H5a2 2 0 01-2-2v-9zM12 14a2 2 0 100-4 2 2 0 000 4z"/>'],
    ['/analisis',   'Analisis',   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
];
$allNav = array_merge($primaryNav, $secondaryNav);
$isActive = function($href) use ($route) {
    return ($route === $href) || ($href === '/' && $route === '/dashboard');
};
?>
<!-- App Shell: sidebar (desktop) + content + bottom nav (mobile) -->
<div class="flex min-h-screen">

    <!-- Sidebar (desktop only) -->
    <aside class="hidden lg:flex lg:sticky top-0 left-0 z-40 h-screen w-60 bg-white border-r border-border flex-col">
        <!-- Logo -->
        <div class="p-5 border-b border-border">
            <a href="/" class="flex items-center gap-3">
                <svg class="w-8 h-10 text-accent" viewBox="0 0 140 190" fill="none">
                    <path d="M 70 30 C 108 30, 122 70, 122 100 C 122 138, 106 160, 85 172 C 74 178, 62 170, 52 158 C 35 138, 18 118, 18 95 C 18 65, 35 30, 70 30 Z"
                          stroke="currentColor" stroke-width="5"/>
                </svg>
                <span class="text-lg font-semibold tracking-wide text-dark">mangos</span>
            </a>
        </div>

        <!-- Nav links -->
        <nav class="flex-1 p-3 space-y-1">
            <?php
            $sidebarItems = [
                ['/', 'Dashboard'],
                ['/fijos', 'Gastos Fijos'],
                ['/movimientos', 'Movimientos'],
                ['/capturar', 'Capturar'],
                ['/categorias', 'Categorias'],
                ['/tarjetas', 'Tarjetas'],
                ['/cuentas', 'Cuentas'],
                ['/analisis', 'Analisis'],
            ];
            $iconMap = [];
            foreach ($allNav as [$h, $l, $i]) { $iconMap[$h] = $i; }
            $iconMap['/capturar'] = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.857 2.286L12 21l-2.286-6.857L3 12l6.857-2.286L12 3z"/>';
            foreach ($sidebarItems as [$href, $label]):
                $active = $isActive($href);
                $cls = $active
                    ? 'bg-accent/10 text-accent font-medium'
                    : 'text-muted hover:text-dark hover:bg-dark/5';
                $icon = $iconMap[$href] ?? '';
            ?>
            <a href="<?= $href ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?= $cls ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $icon ?></svg>
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- User area -->
        <div id="sidebar-user" class="p-4 border-t border-border hidden">
            <div class="flex items-center gap-3">
                <img id="sidebar-avatar" src="" alt="" class="w-8 h-8 rounded-full bg-border">
                <div class="flex-1 min-w-0">
                    <p id="sidebar-name" class="text-sm font-medium truncate"></p>
                    <button onclick="mangosAuth.signOut()" class="text-xs text-muted hover:text-danger transition-colors">Cerrar sesion</button>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main column -->
    <div class="flex-1 flex flex-col min-h-screen min-w-0">
        <!-- Topbar (mobile only) -->
        <header class="sticky top-0 z-20 bg-light/85 backdrop-blur-sm border-b border-border lg:hidden safe-top">
            <div class="px-4 h-14 flex items-center justify-between">
                <h1 class="text-base font-semibold truncate"><?= $pageTitle ?? 'Dashboard' ?></h1>
                <img id="topbar-avatar" src="" alt="" class="w-8 h-8 rounded-full bg-border hidden" onclick="toggleMore()">
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 px-4 py-5 sm:p-6 lg:p-8 w-full pb-[calc(5rem+env(safe-area-inset-bottom))] lg:pb-8">
<?php endif; ?>

<?php if ($isMinimal): ?>
<!-- Minimal layout (login) -->
<div class="min-h-screen flex items-center justify-center p-4">
<?php endif; ?>
