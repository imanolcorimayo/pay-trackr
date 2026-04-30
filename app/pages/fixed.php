<!-- Page header (desktop only — mobile topbar shows the page title) -->
<div class="hidden lg:flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold">Gastos Fijos</h1>
        <p class="text-sm text-muted mt-1">Movimientos recurrentes que se repiten cada mes</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="/capturar" class="btn btn-outline" title="Carga masiva con IA (imagenes o audio)">
            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.857 2.286L12 21l-2.286-6.857L3 12l6.857-2.286L12 3z"/>
            </svg>
            Carga masiva
        </a>
        <button class="btn btn-primary" onclick="openRecurrentModal()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo
        </button>
    </div>
</div>

<!-- Mobile action row (Carga masiva + Nuevo) -->
<div class="lg:hidden grid grid-cols-2 gap-2 mb-3">
    <a href="/capturar" class="inline-flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg border border-border text-sm text-dark hover:bg-dark/5 active:scale-95 transition" title="Carga masiva con IA (imagenes o audio)">
        <svg class="w-4 h-4 text-accent flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.857 2.286L12 21l-2.286-6.857L3 12l6.857-2.286L12 3z"/>
        </svg>
        <span>Carga masiva</span>
    </a>
    <button type="button" onclick="openRecurrentModal()" class="inline-flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg bg-accent text-white text-sm font-medium hover:opacity-90 active:scale-95 transition" title="Nuevo gasto fijo">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Nuevo</span>
    </button>
</div>

<!-- FX rate strip (shown once rates load; hidden when only ARS is in use) -->
<p id="fx-strip" class="hidden text-xs text-muted mb-3"></p>

<!-- Summary card — each amount stacks one line per currency present -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="card">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted mb-1">Total mensual</p>
        <p class="text-2xl font-bold" id="rec-total"><span class="skeleton inline-block w-32 h-7">&nbsp;</span></p>
    </div>
    <div class="card">
        <p class="text-xs font-semibold tracking-wide uppercase text-success mb-1">Pagados</p>
        <p class="text-2xl font-bold" id="rec-paid"><span class="skeleton inline-block w-28 h-7">&nbsp;</span></p>
    </div>
    <div class="card">
        <p class="text-xs font-semibold tracking-wide uppercase text-danger mb-1">Pendientes</p>
        <p class="text-2xl font-bold" id="rec-unpaid"><span class="skeleton inline-block w-28 h-7">&nbsp;</span></p>
    </div>
</div>

<!-- Search filter -->
<div class="card mb-4 py-3">
    <div class="relative">
        <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
        </svg>
        <input type="search" id="filter-search" class="input pl-8" placeholder="Buscar por titulo, monto, cuenta…" autocomplete="off">
    </div>
</div>

<!-- List — V3 skeleton mirrors the actual row layout (date-tile + title row + subline) -->
<div class="card !p-0">
    <div id="recurrents-list">
        <div class="py-3 px-3 border-b border-border">
            <div class="flex items-center gap-3"><span class="skeleton w-9 h-10 rounded-lg flex-shrink-0">&nbsp;</span><span class="skeleton flex-1 h-4 max-w-[220px]">&nbsp;</span></div>
            <div class="flex items-end justify-between gap-3 mt-2 pl-12"><span class="skeleton h-3 w-32">&nbsp;</span><span class="skeleton h-4 w-20">&nbsp;</span></div>
        </div>
        <div class="py-3 px-3 border-b border-border">
            <div class="flex items-center gap-3"><span class="skeleton w-9 h-10 rounded-lg flex-shrink-0">&nbsp;</span><span class="skeleton flex-1 h-4 max-w-[180px]">&nbsp;</span></div>
            <div class="flex items-end justify-between gap-3 mt-2 pl-12"><span class="skeleton h-3 w-40">&nbsp;</span><span class="skeleton h-4 w-16">&nbsp;</span></div>
        </div>
        <div class="py-3 px-3">
            <div class="flex items-center gap-3"><span class="skeleton w-9 h-10 rounded-lg flex-shrink-0">&nbsp;</span><span class="skeleton flex-1 h-4 max-w-[200px]">&nbsp;</span></div>
            <div class="flex items-end justify-between gap-3 mt-2 pl-12"><span class="skeleton h-3 w-28">&nbsp;</span><span class="skeleton h-4 w-20">&nbsp;</span></div>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Form modal ─────────────────────────── -->
<div id="recurrent-modal" class="fixed inset-0 z-50 hidden bg-dark/40">
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-lg max-h-[85dvh] sm:max-h-[92vh] overflow-y-auto safe-bottom">
            <button type="button" onclick="closeRecurrentModal()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <header class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h2 id="recurrent-modal-title" class="text-lg font-semibold">Nuevo gasto fijo</h2>
                <button type="button" onclick="closeRecurrentModal()" class="text-muted hover:text-dark p-1 -m-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <form id="recurrent-form" class="p-5 space-y-4">
                <input type="hidden" id="rec-id">

                <div>
                    <label for="rec-title" class="block text-sm font-medium mb-1.5">Titulo <span class="text-danger">*</span></label>
                    <input type="text" id="rec-title" class="input" placeholder="Ej: Netflix, Alquiler" maxlength="200" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="rec-amount" class="block text-sm font-medium mb-1.5">Monto <span class="text-danger">*</span></label>
                        <input type="text" id="rec-amount" class="input" placeholder="1234,56" inputmode="decimal" data-amount required>
                    </div>
                    <div>
                        <label for="rec-due-day" class="block text-sm font-medium mb-1.5">Vence dia <span class="text-danger">*</span></label>
                        <input type="number" id="rec-due-day" class="input" min="1" max="31" placeholder="1-31" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="rec-category" class="block text-sm font-medium mb-1.5">Categoria</label>
                        <select id="rec-category" class="input">
                            <option value="">Sin categoria</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Tarjeta</label>
                        <input type="hidden" id="rec-card" value="">
                        <button type="button" id="rec-card-chip" class="picker-chip"></button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Cuenta</label>
                        <input type="hidden" id="rec-account" value="">
                        <button type="button" id="rec-account-chip" class="picker-chip"></button>
                    </div>
                    <div>
                        <label for="rec-currency" class="block text-sm font-medium mb-1.5">Moneda</label>
                        <select id="rec-currency" class="input">
                            <option value="ARS">ARS</option>
                            <option value="USD">USD</option>
                            <option value="USDT">USDT</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="rec-period" class="block text-sm font-medium mb-1.5">Frecuencia</label>
                        <select id="rec-period" class="input">
                            <option value="monthly">Mensual</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>
                    <div>
                        <label for="rec-start" class="block text-sm font-medium mb-1.5">Inicio</label>
                        <input type="date" id="rec-start" class="input">
                    </div>
                </div>

                <div>
                    <label for="rec-end" class="block text-sm font-medium mb-1.5">Fin <span class="text-muted text-xs font-normal">(opcional)</span></label>
                    <input type="date" id="rec-end" class="input">
                </div>

                <div>
                    <label for="rec-description" class="block text-sm font-medium mb-1.5">Descripcion</label>
                    <textarea id="rec-description" class="input min-h-[64px]" maxlength="500" rows="2"></textarea>
                </div>

                <div>
                    <label for="rec-aliases" class="block text-sm font-medium mb-1.5">
                        Aliases <span class="text-muted text-xs font-normal">(uno por linea — para que la IA matchee)</span>
                    </label>
                    <textarea id="rec-aliases" class="input min-h-[64px]" rows="3"
                              placeholder="NAVARRO AMADEO ANDRES&#10;Navarro Amadeo"></textarea>
                </div>

                <p id="rec-form-error" class="hidden text-sm text-danger">&nbsp;</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeRecurrentModal()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" id="rec-form-submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─────────────────────────── History modal ─────────────────────────── -->
<div id="recurrent-history-modal" class="fixed inset-0 z-50 hidden bg-dark/40">
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-lg max-h-[85dvh] sm:max-h-[92vh] overflow-y-auto safe-bottom">
            <button type="button" onclick="closeRecurrentHistory()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <header class="px-5 py-4 border-b border-border flex items-center justify-between">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold truncate" id="rec-history-title">Historial</h2>
                    <p class="text-xs text-muted mt-0.5" id="rec-history-subtitle"></p>
                </div>
                <button type="button" onclick="closeRecurrentHistory()" class="text-muted hover:text-dark p-1 -m-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>
            <div class="p-5">
                <div id="rec-history-list"></div>
            </div>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Confirm delete ─────────────────────────── -->
<div id="recurrent-delete-modal" class="fixed inset-0 z-50 hidden bg-dark/40">
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-sm max-h-[85dvh] sm:max-h-[92vh] overflow-y-auto safe-bottom">
            <button type="button" onclick="closeRecurrentDelete()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <div class="p-5">
                <h2 class="text-lg font-semibold">Eliminar gasto fijo</h2>
                <p class="text-sm text-muted mt-2">
                    Vas a eliminar <span id="rec-delete-name" class="font-medium text-dark"></span>.
                    Tambien se eliminaran sus movimientos asociados (de cualquier mes).
                </p>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" onclick="closeRecurrentDelete()" class="btn btn-ghost">Cancelar</button>
                    <button type="button" onclick="confirmRecurrentDelete()" id="rec-delete-submit" class="btn btn-danger">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const SVG_NS = 'http://www.w3.org/2000/svg';
const MONTH_LABEL = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const HISTORY_MAX_BUCKETS = 240;  // hard cap to avoid runaway loops on bad data

const PERIOD_LABEL = { monthly: 'Mensual', yearly: 'Anual', weekly: 'Semanal', biweekly: 'Quincenal' };

let recurrents = [];
let categories = [];
let cards = [];
let accounts = [];
let monthlyPayments = [];   // transactions for current month, used for paid status
let editingId = null;
let pendingDeleteId = null;
let searchQuery = '';       // free-text substring filter (case-insensitive)
// Per-row currency override. id → currency code. Absent = native (default).
// Lost on reload — this is a quick "show me the converted amount" affordance.
const currencyOverrides = new Map();
// Single row open at a time, synced to ?open=<id> in the URL.
let expandedId = null;

const CURRENCY_ORDER = ['ARS', 'USD', 'USDT'];

function readUrlState() {
    const p = new URLSearchParams(window.location.search);
    if (p.has('q')) searchQuery = p.get('q');
    if (p.has('open')) expandedId = p.get('open');
}

function writeUrlState() {
    const params = new URLSearchParams();
    if (searchQuery) params.set('q', searchQuery);
    if (expandedId) params.set('open', expandedId);
    const qs = params.toString();
    history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
}

function toggleExpanded(id) {
    expandedId = expandedId === id ? null : id;
    writeUrlState();
    renderRecurrents();
    if (expandedId) scrollToExpanded();
}

function scrollToExpanded() {
    if (!expandedId) return;
    requestAnimationFrame(() => {
        const el = document.querySelector(`[data-row-id="${CSS.escape(expandedId)}"]`);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
}

function recurrentSearchableText(r) {
    const parts = [
        r.title,
        r.description,
        r.currency,
        r.time_period,
        PERIOD_LABEL[r.time_period],
        categoryById(r.expense_category_id)?.name,
    ];
    const card = cardById(r.card_id);
    if (card) {
        parts.push(card.name);
        if (card.last_four) parts.push(card.last_four);
    }
    const account = accountById(r.account_id);
    if (account) parts.push(account.name);
    if (r.amount != null) {
        const abs = Math.abs(Number(r.amount));
        parts.push(formatPrice(abs), String(abs));
    }
    if (r.due_date_day) parts.push(`vence el ${r.due_date_day}`, String(r.due_date_day));
    if (Array.isArray(r.aliases)) parts.push(...r.aliases);
    return parts.filter(Boolean).join(' ').toLowerCase();
}

function applySearchFilter(list) {
    const q = searchQuery.trim().toLowerCase();
    if (!q) return list;
    return list.filter(r => recurrentSearchableText(r).includes(q));
}

// ── Helpers ─────────────────────────────────────────────────────────
function svgIcon(pathD, sizeCls = 'w-4 h-4') {
    const svg = document.createElementNS(SVG_NS, 'svg');
    svg.setAttribute('class', sizeCls);
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('viewBox', '0 0 24 24');
    const path = document.createElementNS(SVG_NS, 'path');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    path.setAttribute('stroke-width', '1.5');
    path.setAttribute('d', pathD);
    svg.appendChild(path);
    return svg;
}

// User input "1.234,56" → number 1234.56 ; "1234.56" also accepted
function parseAmount(input) {
    if (input == null) return NaN;
    const s = String(input).trim().replace(/\./g, '').replace(',', '.');
    return parseFloat(s);
}

function categoryById(id) {
    return categories.find(c => c.id === id);
}

function cardById(id) {
    return cards.find(c => c.id === id);
}

function accountById(id) {
    return accounts.find(a => a.id === id);
}

function defaultAccount() {
    return accounts.find(a => Number(a.is_default) === 1) || accounts[0] || null;
}

// ── Loading & rendering ─────────────────────────────────────────────
async function loadAll() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    const startDate = `${year}-${String(month + 1).padStart(2, '0')}-01`;
    const lastDay = new Date(year, month + 1, 0).getDate();
    const endDate = `${year}-${String(month + 1).padStart(2, '0')}-${lastDay}`;

    const [recs, cats, crds, accs, pays] = await Promise.all([
        api.get('/recurrents'),
        api.get('/categories'),
        api.get('/cards'),
        api.get('/accounts'),
        api.get('/transactions', { start_date: startDate, end_date: endDate }),
    ]);

    recurrents = recs || [];
    categories = cats || [];
    cards = crds || [];
    accounts = accs || [];
    monthlyPayments = pays || [];

    populateDropdowns();
    renderSummary();
    renderRecurrents();
    // If we landed via /fijos?open=<id>, scroll to that row once mounted.
    scrollToExpanded();
}

function populateDropdowns() {
    const catSel = document.getElementById('rec-category');
    catSel.textContent = '';
    const catEmpty = document.createElement('option');
    catEmpty.value = '';
    catEmpty.textContent = 'Sin categoria';
    catSel.appendChild(catEmpty);
    categories.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.name;
        catSel.appendChild(opt);
    });

    if (window.mangosPicker) {
        mangosPicker.setData({ accounts, cards });
        mangosPicker.updateChip(document.getElementById('rec-card-chip'));
        mangosPicker.updateChip(document.getElementById('rec-account-chip'));
    }
}

function summaryBuckets() {
    const paidRecIds = new Set(
        monthlyPayments.filter(p => p.is_paid == 1 && p.recurrent_id).map(p => p.recurrent_id)
    );

    const buckets = {};
    applySearchFilter(recurrents).forEach(r => {
        const cur = r.currency || 'ARS';
        const b = buckets[cur] || (buckets[cur] = { total: 0, paid: 0, unpaid: 0 });
        // Annualize yearly: amount/12 contributes to the monthly total
        const monthlyEquivalent = r.time_period === 'yearly' ? Number(r.amount) / 12 : Number(r.amount);
        b.total += monthlyEquivalent;
        if (paidRecIds.has(r.id)) b.paid += monthlyEquivalent;
        else b.unpaid += monthlyEquivalent;
    });
    return buckets;
}

function sortedCurrencyCodes(buckets) {
    return Object.keys(buckets).sort((a, b) => {
        const ai = CURRENCY_ORDER.indexOf(a), bi = CURRENCY_ORDER.indexOf(b);
        return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi);
    });
}

// Hero is the ARS grand total (everything converted via fx). Below it,
// secondary line shows the same total expressed in USD + USDT. When the
// bucket spans more than one currency, a third tiny line lists the pure
// per-currency amounts so the conversions can be reconciled.
function renderStackedAmount(elId, buckets, key) {
    const el = document.getElementById(elId);
    el.textContent = '';
    const codes = sortedCurrencyCodes(buckets);
    if (codes.length === 0) {
        el.textContent = formatPrice(0, 'ARS');
        return;
    }

    const totalArs = codes.reduce((a, c) => a + Math.abs(buckets[c][key]) * fx.rateFor(c), 0);
    const isPureSingle = (cur) => codes.length === 1 && codes[0] === cur;

    const hero = document.createElement('span');
    hero.className = 'block';
    hero.textContent = (isPureSingle('ARS') ? '' : '≈ ') + formatPrice(totalArs, 'ARS');
    el.appendChild(hero);

    const convParts = [];
    ['USD', 'USDT'].forEach(target => {
        const r = fx.rates[target];
        if (!r) return;
        const v = totalArs / r;
        if (!Number.isFinite(v)) return;
        convParts.push((isPureSingle(target) ? '' : '≈ ') + formatPrice(v, target));
    });
    if (convParts.length) {
        const conv = document.createElement('span');
        conv.className = 'block text-sm font-semibold text-muted -mt-0.5';
        conv.textContent = convParts.join(' · ');
        el.appendChild(conv);
    }

    if (codes.length > 1) {
        const pure = document.createElement('span');
        pure.className = 'block text-xs text-muted -mt-0.5';
        pure.textContent = codes.map(c => formatPrice(Math.abs(buckets[c][key]), c)).join(' · ');
        el.appendChild(pure);
    }
}

function renderFxStrip() {
    const el = document.getElementById('fx-strip');
    const parts = [];
    ['USD', 'USDT'].forEach(code => {
        const r = fx.rateFor(code);
        if (r && r !== 1) parts.push(`${code} ${formatPrice(r, 'ARS')}`);
    });
    if (parts.length === 0) {
        el.classList.add('hidden');
        el.textContent = '';
        return;
    }
    const age = fx.relativeAge();
    el.textContent = `Cotización: ${parts.join(' · ')}${age ? ' · ' + age : ''}`;
    el.classList.remove('hidden');
}

function renderSummary() {
    const buckets = summaryBuckets();
    renderStackedAmount('rec-total',  buckets, 'total');
    renderStackedAmount('rec-paid',   buckets, 'paid');
    renderStackedAmount('rec-unpaid', buckets, 'unpaid');
}

function renderRecurrents() {
    const listEl = document.getElementById('recurrents-list');
    listEl.textContent = '';

    if (recurrents.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'text-center py-12';
        const msg = document.createElement('p');
        msg.className = 'text-sm text-muted';
        msg.textContent = 'No tienes gastos fijos aun.';
        empty.appendChild(msg);
        const btn = document.createElement('button');
        btn.className = 'btn btn-outline mt-4';
        btn.textContent = 'Crear el primero';
        btn.addEventListener('click', () => openRecurrentModal());
        empty.appendChild(btn);
        listEl.appendChild(empty);
        return;
    }

    const filtered = applySearchFilter(recurrents);

    if (filtered.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'text-center py-12';
        const msg = document.createElement('p');
        msg.className = 'text-sm text-muted';
        msg.textContent = 'Sin resultados para tu busqueda.';
        empty.appendChild(msg);
        listEl.appendChild(empty);
        return;
    }

    // Sort by due_date_day ascending so upcoming bills surface first
    const sorted = [...filtered].sort((a, b) => a.due_date_day - b.due_date_day);

    const paidRecIds = new Set(
        monthlyPayments.filter(p => p.is_paid == 1 && p.recurrent_id).map(p => p.recurrent_id)
    );
    const now = new Date();
    const todayDay = now.getDate();
    const todayMonth = now.getMonth();

    sorted.forEach((r, i) => {
        const isLast = i === sorted.length - 1;
        const dueMonth = recurrentDueMonth(r);          // null = every month (monthly)
        const isDueThisMonth = dueMonth === null || dueMonth === todayMonth;
        const isPaid = isDueThisMonth && paidRecIds.has(r.id);
        const isOverdue = isDueThisMonth && !isPaid && r.due_date_day < todayDay;
        listEl.appendChild(buildRecurrentRow(r, isPaid, isOverdue, isDueThisMonth, dueMonth, isLast));
    });
}

// For yearly recurrents, the "due month" comes from start_date. Returns null
// for non-yearly (monthly counts every month as due).
function recurrentDueMonth(r) {
    if (r.time_period !== 'yearly') return null;
    if (!r.start_date) return null;
    const d = new Date(r.start_date + 'T00:00:00');
    if (isNaN(d.getTime())) return null;
    return d.getMonth();
}

// Single-line right-aligned amount. Defaults to native currency; when a
// per-row override is set, shows the converted value with an "≈" prefix to
// flag it as inexact.
function buildAmountStack(r) {
    const native = r.currency || 'ARS';
    const display = currencyOverrides.get(r.id) || native;
    const nativeAmt = Math.abs(Number(r.amount) || 0);
    const value = display === native ? nativeAmt : fx.convert(nativeAmt, native, display);

    const span = document.createElement('span');
    span.className = 'text-sm font-semibold tabular-nums';
    span.textContent = (display === native ? '' : '≈ ') + formatPrice(value, display);
    return span;
}

// Switches the row's display currency. Storing native as an override would be
// pointless — clear it instead so default rendering kicks in.
function setRowDisplay(r, currency) {
    const native = r.currency || 'ARS';
    if (currency === native) currencyOverrides.delete(r.id);
    else currencyOverrides.set(r.id, currency);
    renderRecurrents();
}

// Builds the "..." trigger + menu sections for a recurrent row. Uses the
// shared rowMenu module — see app/assets/js/row-menu.js.
function makeRowActions(r) {
    return rowMenu.trigger(anchor => {
        const native = r.currency || 'ARS';
        const display = currencyOverrides.get(r.id) || native;
        const nativeAmt = Math.abs(Number(r.amount) || 0);

        const currencyItems = CURRENCY_ORDER
            .filter(c => c !== display)
            .map(c => ({ value: c, amount: fx.convert(nativeAmt, native, c) }))
            .filter(o => Number.isFinite(o.amount))
            .map(o => ({
                label: formatPrice(o.amount, o.value),
                onClick: () => setRowDisplay(r, o.value),
            }));

        const sections = [];
        if (currencyItems.length) {
            sections.push({ header: 'Mostrar como', items: currencyItems });
        }
        sections.push({
            items: [
                { label: 'Editar',    onClick: () => openRecurrentModal(r) },
                { label: 'Historial', onClick: () => openRecurrentHistory(r) },
                { label: 'Eliminar',  danger: true, onClick: () => openRecurrentDelete(r) },
            ],
        });

        rowMenu.open(anchor, sections);
    });
}

// V3 row layout: title on its own line (full width); subline + amount/badge
// below. The "Día N" date-tile replaces the category dot — strong visual cue
// that this is a recurring bill, with day-of-month right at the front.
function buildRecurrentRow(r, isPaid, isOverdue, isDueThisMonth, dueMonth, isLast) {
    const wrap = document.createElement('div');
    wrap.dataset.rowId = r.id;
    if (!isLast) wrap.classList.add('border-b', 'border-border');

    const header = document.createElement('div');
    header.className = 'py-3 px-3 cursor-pointer hover:bg-dark/5 active:bg-dark/5 transition-colors';
    header.addEventListener('click', () => toggleExpanded(r.id));

    // Title row: date-tile + title + ⋮
    const titleRow = document.createElement('div');
    titleRow.className = 'flex items-center gap-3';

    const cat = categoryById(r.expense_category_id);
    const tile = document.createElement('div');
    // Yearly recurrents in off-months get a muted tile so they fade visually.
    const tileMuted = !isDueThisMonth;
    tile.className = 'rounded-lg flex flex-col items-center justify-center flex-shrink-0 leading-none w-9 h-10 ' +
                     (tileMuted ? 'bg-muted/10 text-muted' : 'bg-accent/10 text-accent');
    tile.title = cat?.name ? `${cat.name} · día ${r.due_date_day}` : `Día ${r.due_date_day}`;
    const dayLabel = document.createElement('span');
    dayLabel.className = 'text-[9px] font-semibold uppercase tracking-wide';
    dayLabel.textContent = 'Día';
    const dayValue = document.createElement('span');
    dayValue.className = 'text-base font-bold mt-0.5';
    dayValue.textContent = String(r.due_date_day);
    tile.appendChild(dayLabel);
    tile.appendChild(dayValue);
    titleRow.appendChild(tile);

    const titleText = document.createElement('p');
    titleText.className = 'flex-1 text-[15px] font-medium truncate';
    titleText.textContent = r.title;
    titleRow.appendChild(titleText);
    titleRow.appendChild(makeRowActions(r));
    header.appendChild(titleRow);

    // Subline + amount row, indented to align with the title text
    const subRow = document.createElement('div');
    subRow.className = 'flex items-end justify-between gap-3 mt-1 pl-12';

    const subParts = [];
    if (r.time_period && r.time_period !== 'monthly') {
        subParts.push((PERIOD_LABEL[r.time_period] || r.time_period).toLowerCase());
    } else {
        subParts.push('Mensual');
    }
    const card = cardById(r.card_id);
    if (card) subParts.push(card.name + (card.last_four ? ` ····${card.last_four}` : ''));
    const sub = document.createElement('p');
    sub.className = 'text-xs text-muted truncate flex-1 min-w-0';
    sub.textContent = subParts.join(' · ');
    subRow.appendChild(sub);

    const right = document.createElement('div');
    right.className = 'flex items-center gap-2 flex-shrink-0';
    right.appendChild(buildAmountStack(r));

    const badge = document.createElement('button');
    badge.type = 'button';
    let badgeVariant;
    if (!isDueThisMonth) {
        badgeVariant = 'badge-muted';
        badge.textContent = 'Anual';
        badge.disabled = true;
        badge.className = `badge ${badgeVariant} cursor-default opacity-70`;
        const monthName = (dueMonth != null) ? MONTH_LABEL[dueMonth] : null;
        badge.title = monthName ? `Vence en ${monthName.toLowerCase()}` : 'No vence este mes';
    } else {
        if (isPaid)        { badgeVariant = 'badge-success'; badge.textContent = 'Pagado'; }
        else if (isOverdue){ badgeVariant = 'badge-danger';  badge.textContent = 'Vencido'; }
        else               { badgeVariant = 'badge-muted';   badge.textContent = 'Pendiente'; }
        badge.className = `badge ${badgeVariant} cursor-pointer hover:opacity-80 active:scale-95 transition disabled:opacity-50 disabled:cursor-wait`;
        badge.title = isPaid ? 'Marcar como pendiente' : 'Marcar como pagado';
        badge.addEventListener('click', e => {
            e.stopPropagation();
            toggleRecurrentPaid(r, badge);
        });
    }
    right.appendChild(badge);
    subRow.appendChild(right);
    header.appendChild(subRow);

    wrap.appendChild(header);

    if (expandedId === r.id) {
        wrap.appendChild(buildRecurrentExpansion(r));
    }
    return wrap;
}

// Expansion: gray panel with key/value rows. No buttons — actions live in "⋮".
function buildRecurrentExpansion(r) {
    const exp = document.createElement('div');
    exp.className = 'border-t border-border bg-muted/[.04] px-4 py-3';

    const kv = (k, v) => {
        if (v == null || v === '') return;
        const row = document.createElement('div');
        row.className = 'flex items-baseline justify-between gap-3 py-1 text-[13px]';
        const ks = document.createElement('span');
        ks.className = 'text-muted flex-shrink-0';
        ks.textContent = k;
        const vs = document.createElement('span');
        vs.className = 'text-dark font-medium text-right break-words min-w-0';
        vs.textContent = v;
        row.appendChild(ks); row.appendChild(vs);
        exp.appendChild(row);
    };

    if (r.description) kv('Descripción', r.description);

    const cat = categoryById(r.expense_category_id);
    if (cat) kv('Categoría', cat.name);

    const acct = accountById(r.account_id);
    if (acct) kv('Cuenta', acct.name);

    const card = cardById(r.card_id);
    if (card) kv('Tarjeta', card.name + (card.last_four ? ` ····${card.last_four}` : ''));

    const period = PERIOD_LABEL[r.time_period] || r.time_period || 'Mensual';
    kv('Frecuencia', `${period} · día ${r.due_date_day}`);

    if (r.start_date) kv('Vigente desde', formatDateLong(r.start_date));
    if (r.end_date)   kv('Hasta',          formatDateLong(r.end_date));

    if (Array.isArray(r.aliases) && r.aliases.length) {
        kv('Aliases IA', r.aliases.join(', '));
    }

    const native = r.currency || 'ARS';
    if (native !== 'ARS') {
        const arsEquiv = fx.toArs(Math.abs(Number(r.amount) || 0), native);
        kv('Equivalente', '≈ ' + formatPrice(arsEquiv, 'ARS'));
    }

    return exp;
}

// ── Toggle paid status for the current month ────────────────────────
async function toggleRecurrentPaid(r, btn) {
    btn.disabled = true;

    // Find any existing instance for this recurrent in the current month
    const instance = monthlyPayments.find(
        p => p.recurrent_id === r.id && p.transaction_type === 'recurrent'
    );
    const wasPaid = instance && instance.is_paid == 1;

    try {
        let result;
        if (instance) {
            // Flip is_paid on the existing instance (preserves any custom edits)
            result = await api.put('/transactions', { is_paid: !wasPaid }, { id: instance.id });
        } else {
            // No instance yet -> create a paid one carrying over the recurrent's data.
            // Clamp due_date_day to the month's last day so e.g. day=31 in February doesn't break.
            const now = new Date();
            const y = now.getFullYear();
            const m = now.getMonth();
            const lastDay = new Date(y, m + 1, 0).getDate();
            const day = Math.min(r.due_date_day, lastDay);
            const due_ts = `${y}-${String(m + 1).padStart(2, '0')}-${String(day).padStart(2, '0')} 00:00:00`;

            result = await api.post('/transactions', {
                title: r.title,
                description: r.description || '',
                amount: r.amount,
                expense_category_id: r.expense_category_id || null,
                card_id: r.card_id || null,
                account_id: r.account_id || null,
                currency: r.currency || 'ARS',
                recurrent_id: r.id,
                transaction_type: 'recurrent',
                due_ts,
                is_paid: true,
            });
        }

        if (!result || result.error) {
            toast(result?.error || 'No se pudo actualizar', 'error');
            btn.disabled = false;
            return;
        }

        toast(wasPaid ? 'Marcado como pendiente' : 'Marcado como pagado', 'success');
        await loadAll();
    } catch (err) {
        console.error(err);
        toast('Error de red', 'error');
        btn.disabled = false;
    }
}

// ── Modal: form ─────────────────────────────────────────────────────
function openRecurrentModal(r) {
    editingId = r?.id || null;
    document.getElementById('recurrent-modal-title').textContent =
        r ? 'Editar gasto fijo' : 'Nuevo gasto fijo';

    document.getElementById('rec-id').value = r?.id || '';
    document.getElementById('rec-title').value = r?.title || '';
    document.getElementById('rec-amount').value = r ? formatAmountForInput(Math.abs(r.amount)) : '';
    document.getElementById('rec-due-day').value = r?.due_date_day || '';
    document.getElementById('rec-category').value = r?.expense_category_id || '';
    const recCardInput = document.getElementById('rec-card');
    recCardInput.value = r?.card_id || '';
    recCardInput.dispatchEvent(new Event('change', { bubbles: true }));
    const defAcct = defaultAccount();
    const recAcctInput = document.getElementById('rec-account');
    recAcctInput.value = r?.account_id || (defAcct?.id || '');
    recAcctInput.dispatchEvent(new Event('change', { bubbles: true }));
    document.getElementById('rec-currency').value = r?.currency || (defAcct?.currency || 'ARS');
    document.getElementById('rec-period').value = r?.time_period || 'monthly';
    document.getElementById('rec-start').value = r?.start_date || '';
    document.getElementById('rec-end').value = r?.end_date || '';
    document.getElementById('rec-description').value = r?.description || '';
    document.getElementById('rec-aliases').value = (r?.aliases || []).join('\n');

    document.getElementById('rec-form-error').classList.add('hidden');
    document.getElementById('recurrent-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('rec-title').focus(), 50);
}

// Number → "1234,56" for AR display
function formatAmountForInput(amount) {
    if (amount == null) return '';
    const n = Number(amount);
    if (!isFinite(n)) return '';
    // toLocaleString es-AR with 2 decimals, then strip thousand separators
    return n.toFixed(2).replace('.', ',');
}

function closeRecurrentModal() {
    document.getElementById('recurrent-modal').classList.add('hidden');
    editingId = null;
}

async function submitRecurrentForm(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('rec-form-submit');
    const errorEl = document.getElementById('rec-form-error');
    errorEl.classList.add('hidden');
    submitBtn.disabled = true;

    const amountNum = parseAmount(document.getElementById('rec-amount').value);
    if (!isFinite(amountNum) || amountNum <= 0) {
        errorEl.textContent = 'El monto debe ser un numero mayor a cero';
        errorEl.classList.remove('hidden');
        submitBtn.disabled = false;
        return;
    }

    const dueDay = parseInt(document.getElementById('rec-due-day').value, 10);
    if (!(dueDay >= 1 && dueDay <= 31)) {
        errorEl.textContent = 'El dia de vencimiento debe estar entre 1 y 31';
        errorEl.classList.remove('hidden');
        submitBtn.disabled = false;
        return;
    }

    const aliases = document.getElementById('rec-aliases').value
        .split('\n').map(s => s.trim()).filter(Boolean);

    const body = {
        title: document.getElementById('rec-title').value.trim(),
        amount: amountNum,
        due_date_day: dueDay,
        expense_category_id: document.getElementById('rec-category').value || null,
        card_id: document.getElementById('rec-card').value || null,
        account_id: document.getElementById('rec-account').value || null,
        currency: document.getElementById('rec-currency').value || 'ARS',
        time_period: document.getElementById('rec-period').value || 'monthly',
        start_date: document.getElementById('rec-start').value || null,
        end_date: document.getElementById('rec-end').value || null,
        description: document.getElementById('rec-description').value.trim(),
        aliases,
    };

    try {
        const result = editingId
            ? await api.put('/recurrents', body, { id: editingId })
            : await api.post('/recurrents', body);

        if (!result || result.error) {
            errorEl.textContent = result?.error || 'Error al guardar';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            return;
        }

        toast(editingId ? 'Gasto fijo actualizado' : 'Gasto fijo creado', 'success');
        closeRecurrentModal();
        await loadAll();
    } catch (err) {
        console.error(err);
        errorEl.textContent = 'Error de red';
        errorEl.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
    }
}

// ── Modal: history ──────────────────────────────────────────────────
// "Virtual" history: occurrences are computed from start_date + due_date_day +
// time_period, then matched against transactions tied to this recurrent.
// No table is materialized. Guarded against bad data (missing start, weird
// period, runaway loops) so we never render an unbounded list.

let historyRecurrent = null;
let historyTxs = [];

function buildOccurrences(r) {
    if (!r.start_date) return { error: 'Configura una fecha de inicio para ver el historial.', items: [] };

    const day = Number(r.due_date_day);
    if (!Number.isInteger(day) || day < 1 || day > 31) {
        return { error: 'Dia de vencimiento invalido.', items: [] };
    }

    const period = r.time_period || 'monthly';
    if (period !== 'monthly' && period !== 'yearly') {
        return { error: `Frecuencia "${period}" todavia no soportada en el historial.`, items: [] };
    }

    const start = new Date(r.start_date + 'T00:00:00');
    if (isNaN(start.getTime())) return { error: 'Fecha de inicio invalida.', items: [] };

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let endLimit = today;
    if (r.end_date) {
        const e = new Date(r.end_date + 'T00:00:00');
        if (!isNaN(e.getTime()) && e < endLimit) endLimit = e;
    }

    if (start > endLimit) return { error: null, items: [] };

    const items = [];
    let y = start.getFullYear();
    let m = start.getMonth();
    const stepMonths = period === 'yearly' ? 12 : 1;

    while (items.length < HISTORY_MAX_BUCKETS) {
        // Stop once we pass the end limit
        if (y > endLimit.getFullYear() ||
            (y === endLimit.getFullYear() && m > endLimit.getMonth())) break;

        const lastDayOfMonth = new Date(y, m + 1, 0).getDate();
        const dueDay = Math.min(day, lastDayOfMonth);
        const dueDate = new Date(y, m, dueDay);

        items.push({ year: y, month: m, dueDate });

        m += stepMonths;
        while (m > 11) { m -= 12; y += 1; }
    }

    return { error: null, items };
}

async function openRecurrentHistory(r) {
    historyRecurrent = r;
    historyTxs = [];

    document.getElementById('rec-history-title').textContent = `Historial · ${r.title}`;
    const sub = [`Vence el ${r.due_date_day}`];
    if (r.time_period && r.time_period !== 'monthly') {
        sub.push((PERIOD_LABEL[r.time_period] || r.time_period).toLowerCase());
    }
    if (r.start_date) sub.push(`desde ${formatDateLong(r.start_date)}`);
    document.getElementById('rec-history-subtitle').textContent = sub.join(' · ');

    const listEl = document.getElementById('rec-history-list');
    listEl.textContent = '';
    const loading = document.createElement('p');
    loading.className = 'text-sm text-muted text-center py-6';
    loading.textContent = 'Cargando historial…';
    listEl.appendChild(loading);

    document.getElementById('recurrent-history-modal').classList.remove('hidden');

    try {
        historyTxs = await api.get('/transactions', { recurrent_id: r.id }) || [];
    } catch (err) {
        console.error(err);
        historyTxs = [];
    }
    renderRecurrentHistory();
}

function closeRecurrentHistory() {
    document.getElementById('recurrent-history-modal').classList.add('hidden');
    historyRecurrent = null;
    historyTxs = [];
}

function findTxForOccurrence(occ) {
    // Match by (year, month) of due_ts. If multiple, prefer paid > unpaid, then most recent.
    const matches = historyTxs.filter(t => {
        if (!t.due_ts) return false;
        const d = new Date(t.due_ts.replace(' ', 'T'));
        return d.getFullYear() === occ.year && d.getMonth() === occ.month;
    });
    if (matches.length === 0) return null;
    matches.sort((a, b) => {
        const pa = Number(a.is_paid) === 1 ? 1 : 0;
        const pb = Number(b.is_paid) === 1 ? 1 : 0;
        if (pa !== pb) return pb - pa;
        return (b.due_ts || '').localeCompare(a.due_ts || '');
    });
    return matches[0];
}

function renderRecurrentHistory() {
    const r = historyRecurrent;
    if (!r) return;
    const listEl = document.getElementById('rec-history-list');
    listEl.textContent = '';

    const { error, items } = buildOccurrences(r);

    if (error) {
        const msg = document.createElement('p');
        msg.className = 'text-sm text-muted text-center py-6';
        msg.textContent = error;
        listEl.appendChild(msg);
        return;
    }

    if (items.length === 0) {
        const msg = document.createElement('p');
        msg.className = 'text-sm text-muted text-center py-6';
        msg.textContent = 'Aun no hay vencimientos para este gasto fijo.';
        listEl.appendChild(msg);
        return;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Newest first
    const sorted = [...items].sort((a, b) => b.dueDate - a.dueDate);

    const wrap = document.createElement('div');
    wrap.className = 'divide-y divide-border -mx-1';
    sorted.forEach(occ => wrap.appendChild(buildHistoryRow(r, occ, today)));
    listEl.appendChild(wrap);
}

function buildHistoryRow(r, occ, today) {
    const tx = findTxForOccurrence(occ);
    const isPaid = tx && Number(tx.is_paid) === 1;
    const isPast = occ.dueDate < today;

    const row = document.createElement('div');
    row.className = 'flex items-center gap-3 py-3 px-1';

    const info = document.createElement('div');
    info.className = 'flex-1 min-w-0';
    const label = document.createElement('p');
    label.className = 'text-sm font-medium';
    label.textContent = `${MONTH_LABEL[occ.month]} ${occ.year}`;
    info.appendChild(label);

    const sub = document.createElement('p');
    sub.className = 'text-xs text-muted';
    const subParts = [`Vence ${formatDate(occ.dueDate.toISOString().slice(0, 10))}`];
    if (tx) {
        const txAmount = Math.abs(Number(tx.amount));
        if (txAmount && txAmount !== Math.abs(Number(r.amount))) {
            subParts.push(`pagado ${formatPrice(txAmount, tx.currency)}`);
        }
    }
    sub.textContent = subParts.join(' · ');
    info.appendChild(sub);

    row.appendChild(info);

    const right = document.createElement('div');
    right.className = 'flex items-center gap-2 flex-shrink-0';

    const badge = document.createElement('span');
    badge.className = 'badge ' + (isPaid ? 'badge-success' : (isPast ? 'badge-danger' : 'badge-muted'));
    badge.textContent = isPaid ? 'Pagado' : (isPast ? 'Vencido' : 'Pendiente');
    right.appendChild(badge);

    const action = document.createElement('button');
    action.type = 'button';
    action.className = 'btn btn-ghost text-xs px-2 py-1 disabled:opacity-50 disabled:cursor-wait';
    action.textContent = isPaid ? 'Anular' : 'Marcar pagada';
    action.addEventListener('click', () => toggleHistoryOccurrence(r, occ, tx, action));
    right.appendChild(action);

    row.appendChild(right);
    return row;
}

async function toggleHistoryOccurrence(r, occ, tx, btn) {
    btn.disabled = true;
    const wasPaid = tx && Number(tx.is_paid) === 1;

    try {
        let result;
        if (tx) {
            result = await api.put('/transactions', { is_paid: !wasPaid }, { id: tx.id });
        } else {
            const lastDay = new Date(occ.year, occ.month + 1, 0).getDate();
            const day = Math.min(Number(r.due_date_day), lastDay);
            const due_ts = `${occ.year}-${String(occ.month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')} 00:00:00`;
            result = await api.post('/transactions', {
                title: r.title,
                description: r.description || '',
                amount: r.amount,
                expense_category_id: r.expense_category_id || null,
                card_id: r.card_id || null,
                account_id: r.account_id || null,
                currency: r.currency || 'ARS',
                recurrent_id: r.id,
                transaction_type: 'recurrent',
                due_ts,
                is_paid: true,
            });
        }

        if (!result || result.error) {
            toast(result?.error || 'No se pudo actualizar', 'error');
            btn.disabled = false;
            return;
        }

        toast(wasPaid ? 'Marcado como pendiente' : 'Marcado como pagado', 'success');

        // Refresh history list + the page-level data (summary, current month badges).
        historyTxs = await api.get('/transactions', { recurrent_id: r.id }) || [];
        renderRecurrentHistory();
        await loadAll();
    } catch (err) {
        console.error(err);
        toast('Error de red', 'error');
        btn.disabled = false;
    }
}

// ── Modal: delete ───────────────────────────────────────────────────
function openRecurrentDelete(r) {
    pendingDeleteId = r.id;
    document.getElementById('rec-delete-name').textContent = r.title;
    document.getElementById('recurrent-delete-modal').classList.remove('hidden');
}

function closeRecurrentDelete() {
    document.getElementById('recurrent-delete-modal').classList.add('hidden');
    pendingDeleteId = null;
}

async function confirmRecurrentDelete() {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('rec-delete-submit');
    btn.disabled = true;
    try {
        const result = await api.del('/recurrents', { id: pendingDeleteId });
        if (!result || result.error) {
            toast(result?.error || 'No se pudo eliminar', 'error');
            return;
        }
        const n = result.instances_deleted || 0;
        const msg = n > 0
            ? `Gasto fijo eliminado (${n} movimiento${n === 1 ? '' : 's'} asociado${n === 1 ? '' : 's'} eliminado${n === 1 ? '' : 's'})`
            : 'Gasto fijo eliminado';
        toast(msg, 'success');
        closeRecurrentDelete();
        await loadAll();
    } catch (err) {
        toast('Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
}

// ── Init ────────────────────────────────────────────────────────────
mangosAuth.ready.then(user => {
    if (!user) return;
    readUrlState();
    loadAll();

    // Refresh once FX rates are in so the rate strip and any non-ARS row
    // amounts switch to the freshly fetched rates.
    fx.ready.then(() => {
        renderFxStrip();
        renderSummary();
        renderRecurrents();
    });

    const searchInput = document.getElementById('filter-search');
    searchInput.value = searchQuery;
    let searchDebounce;
    searchInput.addEventListener('input', e => {
        clearTimeout(searchDebounce);
        const value = e.target.value;
        searchDebounce = setTimeout(() => {
            searchQuery = value;
            writeUrlState();
            renderSummary();
            renderRecurrents();
        }, 150);
    });

    document.getElementById('recurrent-form').addEventListener('submit', submitRecurrentForm);
    document.getElementById('rec-account').addEventListener('change', e => {
        const a = accountById(e.target.value);
        if (a) document.getElementById('rec-currency').value = a.currency;
    });

    mangosPicker.bindChip(document.getElementById('rec-card-chip'), {
        mode: 'card',
        valueInputId: 'rec-card',
        allowNone: true,
    });
    mangosPicker.bindChip(document.getElementById('rec-account-chip'), {
        mode: 'account',
        valueInputId: 'rec-account',
    });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (!document.getElementById('recurrent-modal').classList.contains('hidden')) closeRecurrentModal();
        else if (!document.getElementById('recurrent-history-modal').classList.contains('hidden')) closeRecurrentHistory();
        else if (!document.getElementById('recurrent-delete-modal').classList.contains('hidden')) closeRecurrentDelete();
    });
});
</script>
