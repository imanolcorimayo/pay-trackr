/**
 * Shared "⋮" row-actions menu used on /movimientos and /fijos.
 *
 *   rowMenu.trigger(onOpen)        → returns a 3-dots <button>. onOpen(btn) is
 *                                    called when the user clicks it; build the
 *                                    sections and call rowMenu.open(btn, …).
 *   rowMenu.open(anchor, sections) → renders the popover next to `anchor`.
 *                                    `sections` is an array of:
 *                                      { header?: string, items: [{ label, onClick, danger? }] }
 *   rowMenu.close()                → closes any open menu.
 *
 * One menu open at a time. Closes on outside click, scroll, or item pick.
 */
window.rowMenu = (function () {
    let openEl = null;

    function dotsSvg() {
        const NS = 'http://www.w3.org/2000/svg';
        const svg = document.createElementNS(NS, 'svg');
        svg.setAttribute('class', 'w-4 h-4');
        svg.setAttribute('fill', 'currentColor');
        svg.setAttribute('viewBox', '0 0 24 24');
        [6, 12, 18].forEach(cy => {
            const c = document.createElementNS(NS, 'circle');
            c.setAttribute('cx', '12');
            c.setAttribute('cy', String(cy));
            c.setAttribute('r', '1.5');
            svg.appendChild(c);
        });
        return svg;
    }

    function close() {
        if (openEl) {
            openEl.remove();
            openEl = null;
        }
        document.removeEventListener('mousedown', onOutside, true);
        document.removeEventListener('scroll', close, true);
    }

    function onOutside(e) {
        if (openEl && !openEl.contains(e.target)) close();
    }

    function open(anchor, sections) {
        close();

        const menu = document.createElement('div');
        menu.className = 'fixed z-30 bg-white border border-border rounded-xl shadow-xl py-1.5 min-w-[220px] overflow-hidden';

        sections.forEach((section, sIdx) => {
            if (sIdx > 0) {
                const sep = document.createElement('div');
                sep.className = 'h-px bg-border my-1.5 mx-1';
                menu.appendChild(sep);
            }
            if (section.header) {
                const h = document.createElement('p');
                h.className = 'px-3 pt-1 pb-1 text-[10px] font-semibold uppercase tracking-wide text-muted';
                h.textContent = section.header;
                menu.appendChild(h);
            }
            section.items.forEach(it => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-3 py-2 text-sm font-medium transition-colors '
                              + (it.danger
                                  ? 'text-danger hover:bg-danger/5 active:bg-danger/10'
                                  : 'text-dark hover:bg-accent/5 active:bg-accent/10');
                btn.textContent = it.label;
                btn.addEventListener('click', e => {
                    e.stopPropagation();
                    close();
                    it.onClick();
                });
                menu.appendChild(btn);
            });
        });

        document.body.appendChild(menu);

        const r = anchor.getBoundingClientRect();
        const mh = menu.offsetHeight;
        const flipUp = (r.bottom + mh + 8) > window.innerHeight && r.top > mh + 8;
        menu.style.top   = (flipUp ? r.top - mh - 4 : r.bottom + 4) + 'px';
        menu.style.right = (window.innerWidth - r.right) + 'px';

        openEl = menu;
        setTimeout(() => {
            document.addEventListener('mousedown', onOutside, true);
            document.addEventListener('scroll', close, true);
        }, 0);
    }

    function trigger(onOpen) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'p-1.5 rounded text-muted hover:text-dark hover:bg-dark/5 transition-colors flex-shrink-0';
        btn.title = 'Más acciones';
        btn.setAttribute('aria-label', 'Más acciones');
        btn.appendChild(dotsSvg());
        btn.addEventListener('click', e => {
            e.stopPropagation();
            onOpen(btn);
        });
        return btn;
    }

    return { open, close, trigger };
})();
