/**
 * Shared utilities for mangos app.
 */

// ── Currency formatting ──────────────────────────
window.formatPrice = function (amount) {
  const num = Number(amount) || 0;
  return num.toLocaleString('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
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
