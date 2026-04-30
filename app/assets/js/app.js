/**
 * Shared utilities for mangos app.
 */

// ── Currency formatting ──────────────────────────
// ARS: native currency style with 0 decimals ("$ 1.234").
// Non-ARS (USD, USDT, …): "USD 1.234,56" — 2 decimals, code as prefix. Keeping
// the prefix style instead of style:'currency' avoids surprising symbols
// (US$ vs $) and keeps numbers aligned with the ARS column visually.
window.formatPrice = function (amount, currency) {
  const num = Number(amount) || 0;
  const cur = currency || 'ARS';
  if (cur === 'ARS') {
    return num.toLocaleString('es-AR', {
      style: 'currency',
      currency: 'ARS',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    });
  }
  return cur + ' ' + num.toLocaleString('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

// ── Date formatting ──────────────────────────────
window.formatDate = function (dateStr) {
  if (!dateStr) return '--';
  const date = new Date(dateStr);
  return date.toLocaleDateString('es-AR', {
    day: 'numeric',
    month: 'short',
  });
};

window.formatDateLong = function (dateStr) {
  if (!dateStr) return '--';
  const date = new Date(dateStr);
  return date.toLocaleDateString('es-AR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
};

// ── Toast notifications ──────────────────────────
window.toast = function (message, type = 'default') {
  const container = document.getElementById('toast-container') || createToastContainer();
  const toast = document.createElement('div');

  const colors = {
    default: 'bg-dark text-white',
    success: 'bg-success text-white',
    error: 'bg-danger text-white',
  };

  toast.className = `${colors[type] || colors.default} px-4 py-3 rounded-lg shadow-lg text-sm font-medium
                     transition-all duration-300 translate-y-2 opacity-0`;
  toast.textContent = message;
  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.classList.remove('translate-y-2', 'opacity-0');
  });

  setTimeout(() => {
    toast.classList.add('translate-y-2', 'opacity-0');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
};

function createToastContainer() {
  const container = document.createElement('div');
  container.id = 'toast-container';
  container.className = 'fixed bottom-4 right-4 z-50 flex flex-col gap-2';
  document.body.appendChild(container);
  return container;
}

// ── PWA install prompt ───────────────────────────
// Shows a custom mobile banner suggesting the user install the app.
//   - Android Chrome/Edge: captures `beforeinstallprompt` and triggers the
//     native dialog when the user taps "Instalar".
//   - iOS Safari: no programmatic install — show step-by-step instructions
//     for Share → "Añadir a pantalla de inicio".
//   - Hides if already installed (display-mode: standalone), if dismissed
//     within the cooldown window, or on desktop.
(function installPrompt() {
    const STORAGE_KEY    = 'mangos_install_dismissed_at';
    const COOLDOWN_DAYS  = 7;
    const SHOW_DELAY_MS  = 8000;

    const isMobile     = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);
    const isIOS        = /iPhone|iPad|iPod/i.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
                      || window.navigator.standalone === true;

    function dismissedRecently() {
        const ts = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
        if (!ts) return false;
        return (Date.now() - ts) < COOLDOWN_DAYS * 86400000;
    }

    if (!isMobile || isStandalone || dismissedRecently()) return;
    if (window.location.pathname === '/login') return;

    let deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault();
        deferredPrompt = e;
        scheduleShow();
    });

    window.addEventListener('appinstalled', () => {
        const banner = document.getElementById('install-prompt');
        if (banner) banner.classList.add('hidden');
        try { localStorage.setItem(STORAGE_KEY, Date.now().toString()); } catch (e) {}
    });

    let scheduled = false;
    function scheduleShow() {
        if (scheduled) return;
        scheduled = true;
        setTimeout(showPrompt, SHOW_DELAY_MS);
    }

    function buildShareIcon() {
        const svgNS = 'http://www.w3.org/2000/svg';
        const wrap = document.createElement('span');
        wrap.className = 'inline-flex align-middle items-center justify-center w-5 h-5 mx-0.5 rounded bg-dark/5';
        const svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('class', 'w-3 h-3');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '2');
        svg.setAttribute('viewBox', '0 0 24 24');
        const path = document.createElementNS(svgNS, 'path');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('stroke-linejoin', 'round');
        path.setAttribute('d', 'M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4-4 4m4-4v13');
        svg.appendChild(path);
        wrap.appendChild(svg);
        return wrap;
    }

    function showPrompt() {
        const banner  = document.getElementById('install-prompt');
        const textEl  = document.getElementById('install-prompt-text');
        const actions = document.getElementById('install-prompt-actions');
        if (!banner || !textEl || !actions) return;

        textEl.textContent = '';
        actions.textContent = '';

        if (isIOS) {
            // Build the instruction with safe DOM nodes (no innerHTML).
            textEl.append('Tocá ', buildShareIcon(), ' y luego ');
            const strong = document.createElement('strong');
            strong.textContent = 'Añadir a pantalla de inicio';
            textEl.append(strong, '.');
        } else if (deferredPrompt) {
            textEl.textContent = 'Tenelo siempre a mano en tu pantalla de inicio.';
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'inline-flex items-center gap-1.5 bg-accent text-white text-xs font-medium px-3 py-2 rounded-lg active:scale-95 transition';
            btn.textContent = 'Instalar';
            btn.onclick = async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                try {
                    const { outcome } = await deferredPrompt.userChoice;
                    if (outcome === 'accepted') banner.classList.add('hidden');
                } finally {
                    deferredPrompt = null;
                }
            };
            actions.appendChild(btn);
        } else {
            return;
        }

        banner.classList.remove('hidden');
    }

    if (isIOS) scheduleShow();
})();

window.dismissInstallPrompt = function() {
    const banner = document.getElementById('install-prompt');
    if (banner) banner.classList.add('hidden');
    try { localStorage.setItem('mangos_install_dismissed_at', Date.now().toString()); } catch (e) {}
};

// ── Service worker registration ──────────────────
// Browsers refuse to register a SW on insecure origins (anything other than
// HTTPS / localhost / 127.0.0.1) — registration will throw on local nginx
// over plain HTTP, which is fine. Catch and ignore.
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js').catch(err => {
      console.debug('SW registration skipped:', err.message);
    });
  });
}

// ── Keyboard inset tracking ──────────────────────
// Exposes the on-screen keyboard's height (or any other obscured area below
// the visual viewport) as a CSS custom property `--keyboard-inset` on <html>.
// Bottom-sheet modals use this to keep their content above the keyboard on iOS,
// where the layout viewport doesn't shrink when the keyboard appears.
(function trackKeyboardInset() {
  if (!window.visualViewport) return;
  const root = document.documentElement;
  function update() {
    const vv = window.visualViewport;
    const inset = Math.max(0, window.innerHeight - vv.height - vv.offsetTop);
    root.style.setProperty('--keyboard-inset', inset + 'px');
  }
  window.visualViewport.addEventListener('resize', update);
  window.visualViewport.addEventListener('scroll', update);
  update();
})();
