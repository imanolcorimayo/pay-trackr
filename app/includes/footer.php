<?php $isMinimal = isset($layout) && $layout === 'minimal'; ?>

<?php if ($isMinimal): ?>
</div><!-- end minimal layout -->
<?php else: ?>
        </main>

        <!-- Bottom tab bar (mobile only) -->
        <?php
        $tabActive = function($href) use ($route) {
            return ($route === $href) || ($href === '/' && $route === '/dashboard');
        };
        ?>
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur-sm border-t border-border safe-bottom"
             style="-webkit-user-select:none;user-select:none;">
            <div class="grid grid-cols-5 h-16">
                <!-- Inicio -->
                <a href="/" class="flex flex-col items-center justify-center gap-0.5 transition-colors active:scale-95
                    <?= $tabActive('/') ? 'text-accent' : 'text-muted' ?>"
                   style="touch-action: manipulation;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="text-[10px] font-medium leading-none">Inicio</span>
                </a>

                <!-- Movimientos -->
                <a href="/movimientos" class="flex flex-col items-center justify-center gap-0.5 transition-colors active:scale-95
                    <?= $tabActive('/movimientos') ? 'text-accent' : 'text-muted' ?>"
                   style="touch-action: manipulation;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="text-[10px] font-medium leading-none">Movimientos</span>
                </a>

                <!-- Agregar con IA (raised center FAB).
                     If already on /movimientos, opens the modal in place. Otherwise navigates. -->
                <a href="/movimientos?ai=1" onclick="return mangosOpenAIFab(event)"
                   class="flex items-center justify-center" aria-label="Agregar con IA"
                   style="touch-action: manipulation;">
                    <span class="-mt-6 w-14 h-14 rounded-full bg-accent text-white flex items-center justify-center shadow-lg active:scale-95 transition-transform ring-4 ring-light">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </span>
                </a>

                <!-- Fijos -->
                <a href="/fijos" class="flex flex-col items-center justify-center gap-0.5 transition-colors active:scale-95
                    <?= $tabActive('/fijos') ? 'text-accent' : 'text-muted' ?>"
                   style="touch-action: manipulation;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span class="text-[10px] font-medium leading-none">Fijos</span>
                </a>

                <!-- Más -->
                <button type="button" onclick="toggleMore()"
                        class="flex flex-col items-center justify-center gap-0.5 transition-colors active:scale-95
                        <?= in_array($route, ['/categorias','/tarjetas','/cuentas','/analisis']) ? 'text-accent' : 'text-muted' ?>"
                        style="touch-action: manipulation;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>
                    </svg>
                    <span class="text-[10px] font-medium leading-none">Más</span>
                </button>
            </div>
        </nav>

        <!-- More sheet (mobile only) -->
        <div id="more-overlay" class="lg:hidden fixed inset-0 bg-dark/40 z-40 hidden opacity-0 transition-opacity duration-200" onclick="toggleMore()"></div>
        <div id="more-sheet"
             class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-2xl shadow-lg translate-y-full transition-transform duration-200 safe-bottom">
            <!-- Drag handle -->
            <div class="pt-2 pb-1 flex justify-center">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </div>

            <!-- User -->
            <div id="more-user" class="px-5 py-3 flex items-center gap-3 border-b border-border hidden">
                <img id="more-avatar" src="" alt="" class="w-10 h-10 rounded-full bg-border">
                <div class="flex-1 min-w-0">
                    <p id="more-name" class="text-sm font-semibold truncate"></p>
                    <p id="more-email" class="text-xs text-muted truncate"></p>
                </div>
            </div>

            <!-- Links -->
            <nav class="p-2">
                <?php
                $moreItems = [
                    ['/categorias', 'Categorias', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>'],
                    ['/tarjetas',   'Tarjetas',   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h.01M11 15h2M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'],
                    ['/cuentas',    'Cuentas',    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10l9-6 9 6v9a2 2 0 01-2 2H5a2 2 0 01-2-2v-9zM12 14a2 2 0 100-4 2 2 0 000 4z"/>'],
                    ['/analisis',   'Analisis',   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
                ];
                foreach ($moreItems as [$href, $label, $icon]):
                    $active = ($route === $href);
                    $cls = $active ? 'bg-accent/10 text-accent font-medium' : 'text-dark hover:bg-dark/5';
                ?>
                <a href="<?= $href ?>" class="flex items-center gap-4 px-4 py-3 rounded-lg text-base transition-colors <?= $cls ?>">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $icon ?></svg>
                    <?= $label ?>
                </a>
                <?php endforeach; ?>

                <button onclick="mangosAuth.signOut()"
                        class="w-full flex items-center gap-4 px-4 py-3 rounded-lg text-base text-danger hover:bg-danger/5 transition-colors">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Cerrar sesion
                </button>
            </nav>
        </div>
        <!-- Install prompt (mobile only, populated by app.js when installable) -->
        <div id="install-prompt"
             class="hidden lg:hidden fixed left-3 right-3 z-40 bg-white border border-border rounded-2xl shadow-lg p-4"
             style="bottom: calc(5rem + env(safe-area-inset-bottom));">
            <button type="button" onclick="dismissInstallPrompt()" aria-label="Cerrar"
                    class="absolute top-2 right-2 text-muted hover:text-dark p-1.5 rounded-lg active:scale-95 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="flex items-start gap-3 pr-6">
                <div class="w-11 h-11 rounded-xl bg-accent/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-8 text-accent" viewBox="0 0 140 190" fill="none">
                        <path d="M 70 30 C 108 30, 122 70, 122 100 C 122 138, 106 160, 85 172 C 74 178, 62 170, 52 158 C 35 138, 18 118, 18 95 C 18 65, 35 30, 70 30 Z"
                              stroke="currentColor" stroke-width="6"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold leading-tight">Instalá mangos</p>
                    <p id="install-prompt-text" class="text-xs text-muted mt-1 leading-relaxed">
                        Tenelo siempre a mano en tu pantalla de inicio.
                    </p>
                    <div id="install-prompt-actions" class="mt-3"></div>
                </div>
            </div>
        </div>

    </div><!-- end main column -->
</div><!-- end app shell -->
<?php endif; ?>

<script>
// Bottom-nav FAB: when already on /movimientos, openAIModal is defined — call it
// directly to avoid a full page reload. Otherwise let the link navigate normally.
function mangosOpenAIFab(e) {
    if (typeof window.openAIModal === 'function') {
        e.preventDefault();
        window.openAIModal();
        return false;
    }
    return true;
}

function toggleMore() {
    const overlay = document.getElementById('more-overlay');
    const sheet = document.getElementById('more-sheet');
    if (!overlay || !sheet) return;
    const isOpen = !sheet.classList.contains('translate-y-full');
    if (isOpen) {
        sheet.classList.add('translate-y-full');
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.classList.add('hidden'), 200);
    } else {
        overlay.classList.remove('hidden');
        // force reflow so opacity transition applies
        void overlay.offsetWidth;
        overlay.classList.remove('opacity-0');
        sheet.classList.remove('translate-y-full');
    }
}

// Mirror sidebar user info into the more-sheet + topbar avatar
(function() {
    function syncUser() {
        const avatar = document.getElementById('sidebar-avatar');
        const name = document.getElementById('sidebar-name');
        if (!avatar || !avatar.src) return false;
        const moreUser = document.getElementById('more-user');
        const moreAvatar = document.getElementById('more-avatar');
        const moreName = document.getElementById('more-name');
        const moreEmail = document.getElementById('more-email');
        const topAvatar = document.getElementById('topbar-avatar');
        if (moreAvatar) moreAvatar.src = avatar.src;
        if (topAvatar) { topAvatar.src = avatar.src; topAvatar.classList.remove('hidden'); }
        if (moreName) moreName.textContent = (name && name.textContent) || '';
        if (moreEmail && window.mangosAuth && window.mangosAuth.user) {
            moreEmail.textContent = window.mangosAuth.user.email || '';
        }
        if (moreUser) moreUser.classList.remove('hidden');
        return true;
    }
    if (window.mangosAuth && window.mangosAuth.ready) {
        window.mangosAuth.ready.then(() => { syncUser(); });
    }
})();
</script>

</body>
</html>
