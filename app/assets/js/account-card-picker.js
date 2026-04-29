/* mangos · account/card picker
 *
 * Shared infra for the Apple-Wallet style stacked carousel and its bottom-sheet
 * picker variant. Used by /cuentas, /tarjetas, and any transaction form that
 * needs to select an account or a card.
 *
 * Public API (window.mangosPicker):
 *   setData({ accounts, cards })   — call after fetching both lists
 *   setAccounts(accounts)          — partial refresh
 *   setCards(cards)                — partial refresh
 *   bindChip(buttonEl, opts)       — wire a .picker-chip to open a sheet
 *     opts: { mode: 'account'|'card', valueId, allowNone, onChange(item|null) }
 *   updateChip(buttonEl)           — rerender chip face from its current valueId
 *   open({ mode, currentId, allowNone, onConfirm })
 *
 * Public API (window.mangosCarousel):
 *   init(stageEl, items, opts)
 *     opts: { kind: 'account'|'card', dotsEl, onChange(idx, item) }
 *     returns: { set(i), go(d), get(), refresh(items) }
 */

(function () {
  'use strict';

  const SVG_NS = 'http://www.w3.org/2000/svg';

  const ACCOUNT_TYPE_LABEL = {
    bank: 'Banco', cash: 'Efectivo', crypto: 'Cripto', other: 'Otra',
  };

  // Glyph paths for account types — drawn inside the small badge in the corner.
  const GLYPHS = {
    bank:   ['M3 21h18', 'M5 21V10', 'M19 21V10', 'M3 10l9-6 9 6', 'M9 21v-7', 'M15 21v-7'],
    cash:   ['M3 6h18v12H3z', 'M9.5 12a2.5 2.5 0 005 0 2.5 2.5 0 00-5 0z', 'M6 9v6', 'M18 9v6'],
    crypto: ['M12 3a9 9 0 100 18 9 9 0 000-18z', 'M8 12l3 3 5-6'],
    other:  ['M12 3a9 9 0 100 18 9 9 0 000-18z', 'M9.5 9.5a2.5 2.5 0 015 0c0 1.5-2.5 2-2.5 4', 'M12 17h.01'],
  };

  function el(tag, attrs, kids, ns) {
    const node = ns ? document.createElementNS(ns, tag) : document.createElement(tag);
    if (attrs) for (const [k, v] of Object.entries(attrs)) {
      if (v == null) continue;
      if (k === 'class') node.setAttribute('class', v);
      else if (k === 'text') node.textContent = v;
      else if (k === 'style') node.setAttribute('style', v);
      else node.setAttribute(k, v);
    }
    if (kids) for (const k of kids) {
      if (k == null || k === false) continue;
      node.appendChild(typeof k === 'string' ? document.createTextNode(k) : k);
    }
    return node;
  }

  function svgGlyph(kind) {
    const svg = el('svg', { viewBox: '0 0 24 24' }, null, SVG_NS);
    svg.setAttribute('stroke', '#fff');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke-width', '1.6');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    (GLYPHS[kind] || GLYPHS.other).forEach(d => {
      svg.appendChild(el('path', { d }, null, SVG_NS));
    });
    return svg;
  }

  function svgChev(d) {
    const svg = el('svg', { viewBox: '0 0 24 24' }, null, SVG_NS);
    svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '2'); svg.setAttribute('stroke-linecap', 'round'); svg.setAttribute('stroke-linejoin', 'round');
    svg.appendChild(el('path', { d }, null, SVG_NS));
    return svg;
  }

  // Format an amount on a tile. We use the number's sign to choose color in CSS,
  // but here we just produce the integer + decimal halves.
  function splitAmount(n, currency) {
    const num = Number(n) || 0;
    const isArs = currency === 'ARS';
    const abs = Math.abs(num);
    const sign = num < 0 ? '−' : '';
    const fixed = abs.toFixed(2);
    const [intPart, frac] = fixed.split('.');
    const grouped = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, isArs ? '.' : ',');
    const prefix = isArs ? '$' : (currency + ' ');
    return { head: sign + prefix + grouped, frac: (isArs ? ',' : '.') + frac };
  }

  function applyStack(tiles, idx) {
    const n = tiles.length;
    tiles.forEach((node, i) => {
      node.classList.remove('active', 'peek-l', 'peek-ll', 'peek-r', 'peek-rr', 'is-hidden');
      if (n === 0) return;
      const fwd = ((i - idx) % n + n) % n;
      const back = (n - fwd) % n;
      if (fwd === 0) node.classList.add('active');
      else if (fwd === 1) node.classList.add('peek-r');
      else if (fwd === 2) node.classList.add('peek-rr');
      else if (back === 1) node.classList.add('peek-l');
      else if (back === 2) node.classList.add('peek-ll');
      else node.classList.add('is-hidden');
    });
  }

  function buildAccountTile(a) {
    const tile = el('article', { class: 'tile', style: '--c:' + (a.color || '#292524') });
    const top = el('div', { class: 'flex items-start justify-between' }, [
      el('div', null, [
        el('div', { class: 'tile-label', text: (ACCOUNT_TYPE_LABEL[a.type] || 'Otra') + ' · ' + a.currency }),
        el('div', { class: 'tile-name', text: a.name }),
      ]),
      el('div', { class: 'tile-glyph', title: a.type }, [svgGlyph(a.type)]),
    ]);

    const amt = splitAmount(a.current_balance, a.currency);
    const isNeg = Number(a.current_balance || 0) < 0;
    const amountEl = el('div', { class: 'tile-amount' + (isNeg ? ' text-rose-200' : '') }, [
      amt.head, el('span', { class: 'frac', text: amt.frac }),
    ]);
    const bot = el('div', { class: 'flex items-end justify-between gap-3' }, [
      el('div', null, [
        Number(a.is_default) === 1
          ? el('div', { class: 'default-tag', text: 'POR DEFECTO' })
          : el('div', { class: 'tile-label', style: 'opacity:.7', text: 'Saldo actual' }),
        amountEl,
      ]),
      el('div', { class: 'tile-pill' }, [el('span', { class: 'dot' }), a.currency]),
    ]);
    tile.appendChild(el('div', { class: 'face' }, [top, bot]));
    return tile;
  }

  function buildCardTile(c) {
    const tile = el('article', { class: 'tile', style: '--c:' + (c.color || '#292524') });
    const typeLabel = (c.type || '').toString();
    const top = el('div', { class: 'flex items-start justify-between' }, [
      el('div', { class: 'tile-chip' }),
      el('span', { class: 'tile-label', text: typeLabel ? typeLabel.toUpperCase() : '' }),
    ]);
    const pan = el('div', { class: 'tile-pan' }, [
      el('span', { class: 'dim', text: '•••• ••••' }),
      ' ' + (c.last_four || '----'),
    ]);
    const issuer = el('div', { class: 'tile-label', style: 'margin-top:6px', text: c.bank || c.name || '' });
    const bot = el('div', { class: 'flex items-end justify-between gap-3' }, [
      el('div', null, [pan, issuer]),
      el('div', { class: 'tile-net', text: c.name || '' }),
    ]);
    tile.appendChild(el('div', { class: 'face' }, [top, bot]));
    return tile;
  }

  function buildEmptyTile(label) {
    const tile = el('article', { class: 'tile', style: '--c:#8C857D' });
    tile.appendChild(el('div', { class: 'face flex items-center justify-center text-center' }, [
      el('div', { class: 'tile-name', style: 'max-width:none;text-align:center', text: label }),
    ]));
    return tile;
  }

  // ─── carousel ────────────────────────────────────────────────────────
  function initCarousel(stage, items, opts) {
    opts = opts || {};
    const kind = opts.kind || 'account';
    let data = items || [];
    let idx = 0;

    const track = stage.querySelector('.track') || el('div', { class: 'track' });
    if (!track.parentElement) stage.appendChild(track);
    const dotsEl = opts.dotsEl || null;

    function render() {
      while (track.firstChild) track.removeChild(track.firstChild);
      if (data.length === 0) {
        track.appendChild(buildEmptyTile(opts.emptyLabel || 'Sin datos'));
      } else {
        data.forEach((it, i) => {
          const tile = (kind === 'card') ? buildCardTile(it) : buildAccountTile(it);
          tile.addEventListener('click', () => set(i));
          track.appendChild(tile);
        });
      }
      const tiles = [...track.querySelectorAll('.tile')];
      applyStack(tiles, idx);
      if (dotsEl) {
        while (dotsEl.firstChild) dotsEl.removeChild(dotsEl.firstChild);
        for (let k = 0; k < data.length; k++) {
          const b = el('button', { type: 'button' });
          if (k === idx) b.classList.add('is-active');
          b.addEventListener('click', () => set(k));
          dotsEl.appendChild(b);
        }
      }
      if (opts.onChange) opts.onChange(idx, data[idx] || null);
    }

    function set(k) {
      if (data.length === 0) return;
      idx = ((k % data.length) + data.length) % data.length;
      const tiles = [...track.querySelectorAll('.tile')];
      applyStack(tiles, idx);
      if (dotsEl) {
        [...dotsEl.querySelectorAll('button')].forEach((b, j) => b.classList.toggle('is-active', j === idx));
      }
      if (opts.onChange) opts.onChange(idx, data[idx] || null);
    }
    function go(d) { set(idx + d); }

    stage.querySelector('[data-prev]')?.addEventListener('click', () => go(-1));
    stage.querySelector('[data-next]')?.addEventListener('click', () => go(1));

    // Touch swipe
    let touchStartX = null;
    stage.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    stage.addEventListener('touchend', e => {
      if (touchStartX == null) return;
      const dx = (e.changedTouches[0].clientX - touchStartX);
      touchStartX = null;
      if (Math.abs(dx) > 40) go(dx < 0 ? 1 : -1);
    });

    render();

    return {
      set, go,
      get: () => idx,
      getItem: () => data[idx] || null,
      refresh: (newItems) => { data = newItems || []; idx = Math.min(idx, Math.max(0, data.length - 1)); render(); },
    };
  }

  // ─── sheet picker ────────────────────────────────────────────────────
  let sheetEl, backdropEl, sheetTitleEl, sheetMetaEl, sheetTrackEl;
  let sheetOpen = false;
  let sheetMode = 'account';
  let sheetItems = [];
  let sheetIdx = 0;
  let sheetAllowNone = false;
  let sheetOnConfirm = null;

  function ensureSheetMounted() {
    if (sheetEl) return;
    sheetEl = document.querySelector('[data-mangos-picker-sheet]');
    backdropEl = document.querySelector('[data-mangos-picker-backdrop]');
    if (!sheetEl || !backdropEl) {
      // Lazy-mount fallback if footer didn't render the markup.
      mountSheet();
    }
    sheetTitleEl = sheetEl.querySelector('[data-mangos-picker-title]');
    sheetMetaEl = sheetEl.querySelector('[data-mangos-picker-meta]');
    sheetTrackEl = sheetEl.querySelector('[data-mangos-picker-track]');

    backdropEl.addEventListener('click', closeSheet);
    sheetEl.querySelector('[data-mangos-picker-close]').addEventListener('click', closeSheet);
    sheetEl.querySelector('[data-mangos-picker-prev]').addEventListener('click', () => moveSheet(-1));
    sheetEl.querySelector('[data-mangos-picker-next]').addEventListener('click', () => moveSheet(1));
    sheetEl.querySelector('[data-mangos-picker-confirm]').addEventListener('click', confirmSheet);
    sheetEl.querySelector('[data-mangos-picker-none]')?.addEventListener('click', () => {
      const cb = sheetOnConfirm; closeSheet(); if (cb) cb(null);
    });

    window.addEventListener('keydown', e => {
      if (!sheetOpen) return;
      if (e.key === 'Escape')     closeSheet();
      if (e.key === 'ArrowLeft')  moveSheet(-1);
      if (e.key === 'ArrowRight') moveSheet(1);
      if (e.key === 'Enter')      confirmSheet();
    });
  }

  function mountSheet() {
    backdropEl = el('div', { class: 'sheet-backdrop', 'data-mangos-picker-backdrop': '' });
    sheetEl = el('aside', { class: 'sheet', 'data-mangos-picker-sheet': '' }, [
      el('div', { class: 'grabber' }),
      el('div', { class: 'flex items-center justify-between mb-3' }, [
        el('h3', { class: 'text-base font-semibold', 'data-mangos-picker-title': '', text: 'Elegir cuenta' }),
        el('button', { type: 'button', class: 'btn btn-ghost text-xs px-2 py-1', 'data-mangos-picker-close': '', text: 'Cerrar' }),
      ]),
      (() => {
        const stage = el('div', { class: 'stage' });
        stage.appendChild(el('div', { class: 'track', 'data-mangos-picker-track': '' }));
        const arrows = el('div', { class: 'nav-arrows' });
        const prev = el('button', { type: 'button', 'data-mangos-picker-prev': '', 'aria-label': 'Anterior' });
        prev.appendChild(svgChev('M15 6l-6 6 6 6'));
        const next = el('button', { type: 'button', 'data-mangos-picker-next': '', 'aria-label': 'Siguiente' });
        next.appendChild(svgChev('M9 6l6 6-6 6'));
        arrows.appendChild(prev); arrows.appendChild(next);
        stage.appendChild(arrows);
        return stage;
      })(),
      el('div', { class: 'flex items-center justify-between mt-3 gap-2' }, [
        el('span', { class: 'text-xs text-muted', 'data-mangos-picker-meta': '', text: '—' }),
        el('div', { class: 'flex items-center gap-2' }, [
          el('button', { type: 'button', class: 'btn btn-ghost text-xs px-3 py-2 hidden', 'data-mangos-picker-none': '', text: 'Sin selección' }),
          el('button', { type: 'button', class: 'btn btn-primary text-xs px-3 py-2', 'data-mangos-picker-confirm': '', text: 'Usar esta' }),
        ]),
      ]),
    ]);
    document.body.appendChild(backdropEl);
    document.body.appendChild(sheetEl);
  }

  function renderSheet() {
    while (sheetTrackEl.firstChild) sheetTrackEl.removeChild(sheetTrackEl.firstChild);
    if (sheetItems.length === 0) {
      sheetTrackEl.appendChild(buildEmptyTile(sheetMode === 'account' ? 'No hay cuentas' : 'No hay tarjetas'));
    } else {
      sheetItems.forEach((it, k) => {
        const tile = (sheetMode === 'card') ? buildCardTile(it) : buildAccountTile(it);
        tile.addEventListener('click', () => { sheetIdx = k; afterSheetMove(); });
        sheetTrackEl.appendChild(tile);
      });
    }
    afterSheetMove();
  }

  function afterSheetMove() {
    const tiles = [...sheetTrackEl.querySelectorAll('.tile')];
    applyStack(tiles, sheetIdx);
    const it = sheetItems[sheetIdx];
    if (sheetMetaEl) {
      if (!it) sheetMetaEl.textContent = '—';
      else if (sheetMode === 'card') sheetMetaEl.textContent = (it.bank || it.name || '') + (it.last_four ? ' · •••• ' + it.last_four : '');
      else sheetMetaEl.textContent = it.name + ' · ' + it.currency + ' · ' + (ACCOUNT_TYPE_LABEL[it.type] || 'Otra');
    }
  }

  function moveSheet(d) {
    if (sheetItems.length === 0) return;
    sheetIdx = (sheetIdx + d + sheetItems.length) % sheetItems.length;
    afterSheetMove();
  }

  function confirmSheet() {
    const it = sheetItems[sheetIdx] || null;
    const cb = sheetOnConfirm;
    closeSheet();
    if (cb) cb(it);
  }

  function openSheet(opts) {
    ensureSheetMounted();
    sheetMode = opts.mode === 'card' ? 'card' : 'account';
    sheetItems = sheetMode === 'card' ? (mangos._cards || []) : (mangos._accounts || []);
    sheetAllowNone = !!opts.allowNone;
    sheetOnConfirm = opts.onConfirm || null;
    sheetIdx = 0;
    if (opts.currentId) {
      const k = sheetItems.findIndex(it => it.id === opts.currentId);
      if (k >= 0) sheetIdx = k;
    }
    sheetTitleEl.textContent = sheetMode === 'card' ? 'Elegir tarjeta' : 'Elegir cuenta';
    const noneBtn = sheetEl.querySelector('[data-mangos-picker-none]');
    if (noneBtn) noneBtn.classList.toggle('hidden', !sheetAllowNone);
    renderSheet();
    sheetEl.classList.add('open');
    backdropEl.classList.add('open');
    sheetOpen = true;
  }

  function closeSheet() {
    if (!sheetEl) return;
    sheetEl.classList.remove('open');
    backdropEl.classList.remove('open');
    sheetOpen = false;
  }

  // ─── chip wiring ─────────────────────────────────────────────────────
  function chipFaceFor(mode, item) {
    // returns { color, name, meta, empty }
    if (!item) {
      return { color: '#8C857D', name: mode === 'card' ? 'Sin tarjeta' : 'Sin cuenta', meta: '', empty: true };
    }
    if (mode === 'card') {
      return {
        color: item.color || '#3F3A36',
        name: item.name || (item.bank || 'Tarjeta'),
        meta: item.last_four ? '•••• ' + item.last_four : '',
        empty: false,
      };
    }
    return {
      color: item.color || '#292524',
      name: item.name,
      meta: item.currency,
      empty: false,
    };
  }

  function paintChip(buttonEl, mode, item) {
    const face = chipFaceFor(mode, item);
    buttonEl.style.setProperty('--c', face.color);
    buttonEl.classList.toggle('empty', !!face.empty);
    const swatch = buttonEl.querySelector('.swatch');
    const nm = buttonEl.querySelector('.nm');
    const mt = buttonEl.querySelector('.mt');
    if (nm) nm.textContent = face.name;
    if (mt) mt.textContent = face.meta;
    // ensure swatch element exists
    if (!swatch) {
      const sw = el('span', { class: 'swatch' });
      buttonEl.insertBefore(sw, buttonEl.firstChild);
    }
  }

  function bindChip(buttonEl, opts) {
    if (!buttonEl) return;
    opts = opts || {};
    const mode = opts.mode === 'card' ? 'card' : 'account';
    const allowNone = !!opts.allowNone;
    const valueInput = opts.valueInput || (opts.valueInputId ? document.getElementById(opts.valueInputId) : null);

    // Build internal structure if not already present.
    if (!buttonEl.querySelector('.swatch')) {
      while (buttonEl.firstChild) buttonEl.removeChild(buttonEl.firstChild);
      buttonEl.appendChild(el('span', { class: 'swatch' }));
      buttonEl.appendChild(el('span', { class: 'nm' }));
      buttonEl.appendChild(el('span', { class: 'mt' }));
      const chev = svgChev('M6 9l6 6 6-6');
      chev.setAttribute('class', 'chev');
      buttonEl.appendChild(chev);
    }
    buttonEl.classList.add('picker-chip');
    buttonEl.type = 'button';

    function currentItem() {
      const id = valueInput?.value || null;
      if (!id) return null;
      const list = mode === 'card' ? (mangos._cards || []) : (mangos._accounts || []);
      return list.find(x => x.id === id) || null;
    }

    function refresh() { paintChip(buttonEl, mode, currentItem()); }
    refresh();

    buttonEl.addEventListener('click', () => {
      openSheet({
        mode,
        currentId: valueInput?.value || null,
        allowNone,
        onConfirm: (item) => {
          if (valueInput) {
            valueInput.value = item ? item.id : '';
            valueInput.dispatchEvent(new Event('change', { bubbles: true }));
          }
          paintChip(buttonEl, mode, item);
          if (opts.onChange) opts.onChange(item);
        },
      });
    });

    // Refresh chip when external code changes the hidden input value (e.g. on
    // form open / edit). We listen for synthetic 'change' events too so callers
    // can opt-in by dispatching one after `input.value = '...'`.
    if (valueInput) {
      valueInput.addEventListener('change', refresh);
      // Also expose a manual hook for callers that bypass change events.
      buttonEl._mangosRefreshChip = refresh;
    }
  }

  function updateChip(buttonEl) {
    if (buttonEl && typeof buttonEl._mangosRefreshChip === 'function') buttonEl._mangosRefreshChip();
  }

  // ─── public surface ──────────────────────────────────────────────────
  const mangos = {
    _accounts: [],
    _cards: [],
    setData(d) {
      if (d.accounts) this._accounts = d.accounts;
      if (d.cards) this._cards = d.cards;
    },
    setAccounts(a) { this._accounts = a || []; },
    setCards(c)    { this._cards = c || []; },
    open: openSheet,
    close: closeSheet,
    bindChip,
    updateChip,
  };

  window.mangosPicker = mangos;
  window.mangosCarousel = { init: initCarousel };
})();
