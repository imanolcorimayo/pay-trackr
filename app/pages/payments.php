<!-- Page header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold">Pagos</h1>
        <p class="text-sm text-muted mt-1">Todos tus pagos del mes</p>
    </div>
    <button class="btn btn-primary" onclick="openPaymentModal()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo
    </button>
</div>

<!-- Filter bar -->
<div class="card mb-4 flex flex-col sm:flex-row sm:items-center gap-3">
    <!-- Month nav -->
    <div class="flex items-center gap-2 flex-shrink-0">
        <button type="button" id="month-prev" class="p-1.5 rounded text-muted hover:text-dark hover:bg-dark/5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <span id="month-label" class="text-sm font-medium min-w-[140px] text-center">--</span>
        <button type="button" id="month-next" class="p-1.5 rounded text-muted hover:text-dark hover:bg-dark/5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    <div class="hidden sm:block w-px h-6 bg-border"></div>

    <!-- Status tabs -->
    <div class="flex gap-1 text-sm" id="status-tabs">
        <button type="button" data-status="all" class="px-3 py-1.5 rounded-lg transition-colors hover:bg-dark/5">Todos</button>
        <button type="button" data-status="unpaid" class="px-3 py-1.5 rounded-lg transition-colors hover:bg-dark/5">Pendientes</button>
        <button type="button" data-status="paid" class="px-3 py-1.5 rounded-lg transition-colors hover:bg-dark/5">Pagados</button>
    </div>

    <div class="hidden sm:block flex-1"></div>

    <!-- Category filter -->
    <select id="filter-category" class="input sm:max-w-[200px]">
        <option value="">Todas las categorias</option>
    </select>
</div>

<!-- Summary -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Total</p>
        <p class="text-lg font-bold mt-0.5" id="sum-total">--</p>
        <p class="text-xs text-muted mt-0.5" id="sum-total-count">&nbsp;</p>
    </div>
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-success">Pagados</p>
        <p class="text-lg font-bold mt-0.5" id="sum-paid">--</p>
        <p class="text-xs text-muted mt-0.5" id="sum-paid-count">&nbsp;</p>
    </div>
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-danger">Pendientes</p>
        <p class="text-lg font-bold mt-0.5" id="sum-unpaid">--</p>
        <p class="text-xs text-muted mt-0.5" id="sum-unpaid-count">&nbsp;</p>
    </div>
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Fijos</p>
        <p class="text-lg font-bold mt-0.5" id="sum-fijo">--</p>
        <p class="text-xs text-muted mt-0.5" id="sum-fijo-count">&nbsp;</p>
    </div>
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Unicos</p>
        <p class="text-lg font-bold mt-0.5" id="sum-unico">--</p>
        <p class="text-xs text-muted mt-0.5" id="sum-unico-count">&nbsp;</p>
    </div>
</div>

<!-- List -->
<div class="card">
    <div id="payments-list">
        <div class="space-y-3">
            <div class="flex justify-between items-center py-3 border-b border-border"><span class="skeleton w-40 h-4">&nbsp;</span><span class="skeleton w-24 h-4">&nbsp;</span></div>
            <div class="flex justify-between items-center py-3 border-b border-border"><span class="skeleton w-32 h-4">&nbsp;</span><span class="skeleton w-20 h-4">&nbsp;</span></div>
            <div class="flex justify-between items-center py-3"><span class="skeleton w-36 h-4">&nbsp;</span><span class="skeleton w-24 h-4">&nbsp;</span></div>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Form modal ─────────────────────────── -->
<div id="payment-modal" class="fixed inset-0 z-50 hidden bg-dark/40 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-border w-full max-w-lg">
            <header class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h2 id="payment-modal-title" class="text-lg font-semibold">Nuevo pago</h2>
                <button type="button" onclick="closePaymentModal()" class="text-muted hover:text-dark p-1 -m-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <form id="payment-form" class="p-5 space-y-4">
                <input type="hidden" id="pmt-id">

                <div>
                    <label for="pmt-title" class="block text-sm font-medium mb-1.5">Titulo <span class="text-danger">*</span></label>
                    <input type="text" id="pmt-title" class="input" placeholder="Ej: Super, Cafe, Alquiler" maxlength="200" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="pmt-amount" class="block text-sm font-medium mb-1.5">Monto <span class="text-danger">*</span></label>
                        <input type="text" id="pmt-amount" class="input" placeholder="1234,56" inputmode="decimal" required>
                    </div>
                    <div>
                        <label for="pmt-due" class="block text-sm font-medium mb-1.5">Fecha</label>
                        <input type="date" id="pmt-due" class="input">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="pmt-category" class="block text-sm font-medium mb-1.5">Categoria</label>
                        <div class="relative">
                            <span id="pmt-category-swatch" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border border-border pointer-events-none"></span>
                            <select id="pmt-category" class="input pl-9">
                                <option value="">Sin categoria</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="pmt-card" class="block text-sm font-medium mb-1.5">Tarjeta</label>
                        <select id="pmt-card" class="input">
                            <option value="">Sin tarjeta</option>
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="pmt-is-paid" class="w-4 h-4 rounded border-border text-accent focus:ring-accent/30">
                    <span class="text-sm">Marcar como pagado</span>
                </label>

                <div>
                    <label for="pmt-description" class="block text-sm font-medium mb-1.5">Descripcion</label>
                    <textarea id="pmt-description" class="input min-h-[64px]" maxlength="500" rows="2"></textarea>
                </div>

                <!-- Recipient subform (collapsible) -->
                <details id="pmt-recipient-details" class="border border-border rounded-lg">
                    <summary class="px-4 py-2.5 text-sm font-medium cursor-pointer select-none flex items-center justify-between">
                        <span>Destinatario <span id="pmt-recipient-indicator" class="text-xs text-muted ml-1"></span></span>
                        <svg class="w-4 h-4 text-muted transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="px-4 pb-4 pt-1 space-y-3 border-t border-border">
                        <div>
                            <label for="pmt-rec-name" class="block text-xs font-medium mb-1 text-muted">Nombre</label>
                            <input type="text" id="pmt-rec-name" class="input" maxlength="200" placeholder="Juan Perez">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="pmt-rec-cbu" class="block text-xs font-medium mb-1 text-muted">CBU</label>
                                <input type="text" id="pmt-rec-cbu" class="input font-mono" maxlength="30">
                            </div>
                            <div>
                                <label for="pmt-rec-alias" class="block text-xs font-medium mb-1 text-muted">Alias</label>
                                <input type="text" id="pmt-rec-alias" class="input" maxlength="100">
                            </div>
                        </div>
                        <div>
                            <label for="pmt-rec-bank" class="block text-xs font-medium mb-1 text-muted">Banco</label>
                            <input type="text" id="pmt-rec-bank" class="input" maxlength="100">
                        </div>
                        <p class="text-xs text-muted">Dejar Nombre vacio para no guardar destinatario.</p>
                    </div>
                </details>

                <p id="pmt-form-error" class="hidden text-sm text-danger">&nbsp;</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closePaymentModal()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" id="pmt-form-submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Confirm delete ─────────────────────────── -->
<div id="payment-delete-modal" class="fixed inset-0 z-50 hidden bg-dark/40">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-border w-full max-w-sm p-5">
            <h2 class="text-lg font-semibold">Eliminar pago</h2>
            <p class="text-sm text-muted mt-2">
                Vas a eliminar <span id="pmt-delete-name" class="font-medium text-dark"></span>.
                Esta accion no se puede deshacer.
            </p>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closePaymentDelete()" class="btn btn-ghost">Cancelar</button>
                <button type="button" onclick="confirmPaymentDelete()" id="pmt-delete-submit" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
const SVG_NS = 'http://www.w3.org/2000/svg';
const ICON_EDIT = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
const ICON_TRASH = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3';
const ICON_RECURRENT = 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15';

let payments = [];
let categories = [];
let cards = [];
let editingId = null;
let pendingDeleteId = null;

// View state — initialized from URL query string, persisted on every change.
let viewMonth = startOfMonth(new Date());
let statusFilter = 'all';   // all | paid | unpaid
let categoryFilter = '';

function readUrlState() {
    const p = new URLSearchParams(window.location.search);
    if (p.has('month')) {
        const [y, m] = p.get('month').split('-').map(Number);
        if (y && m >= 1 && m <= 12) viewMonth = new Date(y, m - 1, 1);
    }
    if (p.has('status') && ['all', 'paid', 'unpaid'].includes(p.get('status'))) {
        statusFilter = p.get('status');
    }
    if (p.has('category')) categoryFilter = p.get('category');
}

function writeUrlState() {
    const y = viewMonth.getFullYear();
    const m = String(viewMonth.getMonth() + 1).padStart(2, '0');
    const params = new URLSearchParams();
    params.set('month', `${y}-${m}`);
    if (statusFilter !== 'all') params.set('status', statusFilter);
    if (categoryFilter) params.set('category', categoryFilter);
    const qs = params.toString();
    history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
}

// ── Helpers ─────────────────────────────────────────────────────────
function startOfMonth(d) { return new Date(d.getFullYear(), d.getMonth(), 1); }
function endOfMonth(d)   { return new Date(d.getFullYear(), d.getMonth() + 1, 0); }
function fmtDateRange(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const start = `${y}-${m}-01`;
    const lastDay = endOfMonth(d).getDate();
    const end = `${y}-${m}-${String(lastDay).padStart(2, '0')}`;
    return { start, end };
}
function monthLabel(d) {
    const s = d.toLocaleDateString('es-AR', { month: 'long', year: 'numeric' });
    return s.charAt(0).toUpperCase() + s.slice(1);
}
function svgIcon(pathD, cls = 'w-4 h-4') {
    const svg = document.createElementNS(SVG_NS, 'svg');
    svg.setAttribute('class', cls);
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
function iconButton(pathD, cls, onClick) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `p-1.5 rounded ${cls} hover:bg-dark/5 transition-colors`;
    btn.appendChild(svgIcon(pathD));
    btn.addEventListener('click', e => { e.stopPropagation(); onClick(); });
    return btn;
}
function parseAmount(input) {
    if (input == null) return NaN;
    const s = String(input).trim().replace(/\./g, '').replace(',', '.');
    return parseFloat(s);
}
function formatAmountForInput(amount) {
    if (amount == null) return '';
    const n = Number(amount);
    if (!isFinite(n)) return '';
    return n.toFixed(2).replace('.', ',');
}
function todayISODate() {
    const d = new Date();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${m}-${day}`;
}
function categoryById(id) { return categories.find(c => c.id === id); }
function cardById(id)     { return cards.find(c => c.id === id); }

function hexToRgba(hex, alpha) {
    if (!hex || hex.length < 7) return null;
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    if (isNaN(r) || isNaN(g) || isNaN(b)) return null;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

// Most-used category across the loaded payment set; '' if none qualify.
function mostCommonCategoryId() {
    const counts = {};
    payments.forEach(p => {
        if (!p.expense_category_id) return;
        counts[p.expense_category_id] = (counts[p.expense_category_id] || 0) + 1;
    });
    let bestId = '', bestCount = 0;
    for (const id in counts) {
        if (counts[id] > bestCount) { bestId = id; bestCount = counts[id]; }
    }
    return bestId;
}

function updateCategorySwatch() {
    const id = document.getElementById('pmt-category').value;
    const cat = categories.find(c => c.id === id);
    document.getElementById('pmt-category-swatch').style.backgroundColor = cat?.color || 'transparent';
}

// ── Loading & rendering ─────────────────────────────────────────────
async function loadAll() {
    document.getElementById('month-label').textContent = monthLabel(viewMonth);
    const range = fmtDateRange(viewMonth);

    const [pays, cats, crds] = await Promise.all([
        api.get('/payments', { start_date: range.start, end_date: range.end }),
        api.get('/categories'),
        api.get('/cards'),
    ]);

    payments = pays || [];
    categories = cats || [];
    cards = crds || [];

    populateDropdowns();
    document.getElementById('filter-category').value = categoryFilter;
    renderSummary();
    renderPayments();
}

function populateDropdowns() {
    // Form modal: tint each option with its category color (best-effort —
    // works on Chromium browsers; degrades to plain text elsewhere).
    const catSel = document.getElementById('pmt-category');
    catSel.textContent = '';
    catSel.appendChild(makeOption('', 'Sin categoria'));
    categories.forEach(c => {
        const opt = makeOption(c.id, c.name);
        const tint = hexToRgba(c.color, 0.18);
        if (tint) opt.style.backgroundColor = tint;
        catSel.appendChild(opt);
    });

    const cardSel = document.getElementById('pmt-card');
    cardSel.textContent = '';
    cardSel.appendChild(makeOption('', 'Sin tarjeta'));
    cards.forEach(c => cardSel.appendChild(makeOption(c.id, c.name + (c.last_four ? ` ····${c.last_four}` : ''))));

    // Filter bar (preserve current selection)
    const filterSel = document.getElementById('filter-category');
    const prev = filterSel.value;
    filterSel.textContent = '';
    filterSel.appendChild(makeOption('', 'Todas las categorias'));
    categories.forEach(c => filterSel.appendChild(makeOption(c.id, c.name)));
    filterSel.value = prev;
}

function makeOption(value, label) {
    const o = document.createElement('option');
    o.value = value;
    o.textContent = label;
    return o;
}

function applyFilters(list) {
    return list.filter(p => {
        if (statusFilter === 'paid' && p.is_paid != 1) return false;
        if (statusFilter === 'unpaid' && p.is_paid == 1) return false;
        if (categoryFilter && p.expense_category_id !== categoryFilter) return false;
        return true;
    });
}

function renderSummary() {
    const filtered = applyFilters(payments);
    let total = 0, paid = 0, unpaid = 0;
    let paidCount = 0, unpaidCount = 0;
    let fijoTotal = 0, fijoCount = 0;
    let unicoTotal = 0, unicoCount = 0;

    filtered.forEach(p => {
        const a = Number(p.amount);
        total += a;
        if (p.is_paid == 1) { paid += a; paidCount++; }
        else { unpaid += a; unpaidCount++; }
        if (p.payment_type === 'recurrent') { fijoTotal += a; fijoCount++; }
        else { unicoTotal += a; unicoCount++; }
    });

    const plural = (n) => `${n} pago${n === 1 ? '' : 's'}`;

    document.getElementById('sum-total').textContent = formatPrice(total);
    document.getElementById('sum-total-count').textContent = plural(filtered.length);
    document.getElementById('sum-paid').textContent = formatPrice(paid);
    document.getElementById('sum-paid-count').textContent = plural(paidCount);
    document.getElementById('sum-unpaid').textContent = formatPrice(unpaid);
    document.getElementById('sum-unpaid-count').textContent = plural(unpaidCount);
    document.getElementById('sum-fijo').textContent = formatPrice(fijoTotal);
    document.getElementById('sum-fijo-count').textContent = plural(fijoCount);
    document.getElementById('sum-unico').textContent = formatPrice(unicoTotal);
    document.getElementById('sum-unico-count').textContent = plural(unicoCount);
}

function renderPayments() {
    const list = document.getElementById('payments-list');
    list.textContent = '';

    const filtered = applyFilters(payments);

    if (filtered.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'text-center py-12';
        const msg = document.createElement('p');
        msg.className = 'text-sm text-muted';
        msg.textContent = payments.length === 0
            ? 'No hay pagos en este mes.'
            : 'Ningun pago coincide con los filtros.';
        empty.appendChild(msg);
        list.appendChild(empty);
        return;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    filtered.forEach((p, i) => {
        const isLast = i === filtered.length - 1;
        const isPaid = p.is_paid == 1;
        const dueDate = p.due_ts ? new Date(p.due_ts.replace(' ', 'T')) : null;
        const isOverdue = !isPaid && dueDate && dueDate < today;
        list.appendChild(buildPaymentRow(p, isPaid, isOverdue, isLast));
    });
}

function buildPaymentRow(p, isPaid, isOverdue, isLast) {
    const row = document.createElement('div');
    row.className = `group flex items-center gap-3 py-3 px-1 cursor-pointer hover:bg-dark/5 -mx-1 rounded transition-colors ${isLast ? '' : 'border-b border-border'}`;
    row.addEventListener('click', () => openPaymentModal(p));

    const cat = categoryById(p.expense_category_id);
    const dot = document.createElement('div');
    dot.className = 'h-2.5 w-2.5 rounded-full flex-shrink-0';
    dot.style.backgroundColor = cat?.color || '#E8E2DA';
    dot.title = cat?.name || 'Sin categoria';
    row.appendChild(dot);

    const info = document.createElement('div');
    info.className = 'flex-1 min-w-0';

    const titleRow = document.createElement('p');
    titleRow.className = 'text-sm font-medium flex items-center gap-1.5 min-w-0';

    if (p.payment_type === 'recurrent') {
        const recIcon = svgIcon(ICON_RECURRENT, 'w-3.5 h-3.5 text-muted flex-shrink-0');
        const recTitle = document.createElementNS(SVG_NS, 'title');
        recTitle.textContent = 'Pago de gasto fijo';
        recIcon.appendChild(recTitle);
        titleRow.appendChild(recIcon);
    }

    const titleText = document.createElement('span');
    titleText.className = 'truncate';
    titleText.textContent = p.title;
    titleRow.appendChild(titleText);
    info.appendChild(titleRow);

    const subParts = [];
    if (isPaid && p.paid_ts) {
        subParts.push(`Pagado: ${formatDate(p.paid_ts)}`);
    } else if (p.due_ts) {
        subParts.push(formatDate(p.due_ts));
    }
    const card = cardById(p.card_id);
    if (card) subParts.push(card.name + (card.last_four ? ` ····${card.last_four}` : ''));
    if (p.is_whatsapp == 1) subParts.push('whatsapp');

    const sub = document.createElement('p');
    sub.className = 'text-xs text-muted truncate';
    sub.textContent = subParts.join(' · ') || ' ';
    info.appendChild(sub);

    row.appendChild(info);

    const right = document.createElement('div');
    right.className = 'flex items-center gap-2 flex-shrink-0';

    const amount = document.createElement('span');
    amount.className = 'text-sm font-semibold';
    amount.textContent = formatPrice(p.amount);
    right.appendChild(amount);

    const badge = document.createElement('button');
    badge.type = 'button';
    let variant;
    if (isPaid) { variant = 'badge-success'; badge.textContent = 'Pagado'; }
    else if (isOverdue) { variant = 'badge-danger'; badge.textContent = 'Vencido'; }
    else { variant = 'badge-muted'; badge.textContent = 'Pendiente'; }
    badge.className = `badge ${variant} cursor-pointer hover:opacity-80 active:scale-95 transition disabled:opacity-50 disabled:cursor-wait`;
    badge.title = isPaid ? 'Marcar como pendiente' : 'Marcar como pagado';
    badge.addEventListener('click', e => {
        e.stopPropagation();
        togglePaymentPaid(p, badge);
    });
    right.appendChild(badge);

    const actions = document.createElement('div');
    actions.className = 'flex gap-0.5 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity';
    actions.appendChild(iconButton(ICON_EDIT, 'text-muted hover:text-dark', () => openPaymentModal(p)));
    actions.appendChild(iconButton(ICON_TRASH, 'text-muted hover:text-danger', () => openPaymentDelete(p)));
    right.appendChild(actions);

    row.appendChild(right);
    return row;
}

// ── Inline paid toggle ──────────────────────────────────────────────
async function togglePaymentPaid(p, btn) {
    btn.disabled = true;
    const wasPaid = p.is_paid == 1;
    try {
        const result = await api.put('/payments', { is_paid: !wasPaid }, { id: p.id });
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
async function openPaymentModal(p) {
    editingId = p?.id || null;
    document.getElementById('payment-modal-title').textContent = p ? 'Editar pago' : 'Nuevo pago';

    // For edit, fetch single to get nested recipient
    let full = p;
    if (p?.id) {
        const fetched = await api.get('/payments', { id: p.id });
        if (fetched && !fetched.error) full = fetched;
    }

    document.getElementById('pmt-id').value = full?.id || '';
    document.getElementById('pmt-title').value = full?.title || '';
    document.getElementById('pmt-amount').value = full ? formatAmountForInput(full.amount) : '';
    document.getElementById('pmt-due').value = full?.due_ts ? full.due_ts.slice(0, 10) : (p ? '' : todayISODate());
    // For new payments default to the most-used category; for edits use the stored one.
    document.getElementById('pmt-category').value = full
        ? (full.expense_category_id || '')
        : mostCommonCategoryId();
    document.getElementById('pmt-card').value = full?.card_id || '';
    // New payments: default checked (most logging happens after paying); edits: actual state.
    document.getElementById('pmt-is-paid').checked = full ? full.is_paid == 1 : true;
    document.getElementById('pmt-description').value = full?.description || '';
    updateCategorySwatch();

    const r = full?.recipient || {};
    document.getElementById('pmt-rec-name').value = r.name || '';
    document.getElementById('pmt-rec-cbu').value = r.cbu || '';
    document.getElementById('pmt-rec-alias').value = r.alias || '';
    document.getElementById('pmt-rec-bank').value = r.bank || '';

    // Open the recipient details if there's existing data
    document.getElementById('pmt-recipient-details').open = !!r.name;
    document.getElementById('pmt-recipient-indicator').textContent = r.name ? `(${r.name})` : '';

    document.getElementById('pmt-form-error').classList.add('hidden');
    document.getElementById('payment-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('pmt-title').focus(), 50);
}

function closePaymentModal() {
    document.getElementById('payment-modal').classList.add('hidden');
    editingId = null;
}

async function submitPaymentForm(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('pmt-form-submit');
    const errorEl = document.getElementById('pmt-form-error');
    errorEl.classList.add('hidden');
    submitBtn.disabled = true;

    const amountNum = parseAmount(document.getElementById('pmt-amount').value);
    if (!isFinite(amountNum) || amountNum <= 0) {
        errorEl.textContent = 'El monto debe ser un numero mayor a cero';
        errorEl.classList.remove('hidden');
        submitBtn.disabled = false;
        return;
    }

    const dueDate = document.getElementById('pmt-due').value;

    const recName = document.getElementById('pmt-rec-name').value.trim();
    const recipient = recName
        ? {
              name: recName,
              cbu: document.getElementById('pmt-rec-cbu').value.trim() || null,
              alias: document.getElementById('pmt-rec-alias').value.trim() || null,
              bank: document.getElementById('pmt-rec-bank').value.trim() || null,
          }
        : null;

    const body = {
        title: document.getElementById('pmt-title').value.trim(),
        amount: amountNum,
        due_ts: dueDate ? `${dueDate} 12:00:00` : null,
        expense_category_id: document.getElementById('pmt-category').value || null,
        card_id: document.getElementById('pmt-card').value || null,
        is_paid: document.getElementById('pmt-is-paid').checked,
        description: document.getElementById('pmt-description').value.trim(),
        recipient,
    };

    try {
        const result = editingId
            ? await api.put('/payments', body, { id: editingId })
            : await api.post('/payments', body);

        if (!result || result.error) {
            errorEl.textContent = result?.error || 'Error al guardar';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            return;
        }

        toast(editingId ? 'Pago actualizado' : 'Pago creado', 'success');
        closePaymentModal();
        await loadAll();
    } catch (err) {
        console.error(err);
        errorEl.textContent = 'Error de red';
        errorEl.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
    }
}

// ── Modal: delete ───────────────────────────────────────────────────
function openPaymentDelete(p) {
    pendingDeleteId = p.id;
    document.getElementById('pmt-delete-name').textContent = p.title;
    document.getElementById('payment-delete-modal').classList.remove('hidden');
}

function closePaymentDelete() {
    document.getElementById('payment-delete-modal').classList.add('hidden');
    pendingDeleteId = null;
}

async function confirmPaymentDelete() {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('pmt-delete-submit');
    btn.disabled = true;
    try {
        const result = await api.del('/payments', { id: pendingDeleteId });
        if (!result || result.error) {
            toast(result?.error || 'No se pudo eliminar', 'error');
            return;
        }
        toast('Pago eliminado', 'success');
        closePaymentDelete();
        await loadAll();
    } catch (err) {
        toast('Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
}

// ── Filter handlers ─────────────────────────────────────────────────
function setStatusFilter(status) {
    statusFilter = status;
    document.querySelectorAll('#status-tabs button').forEach(btn => {
        const active = btn.dataset.status === status;
        btn.classList.toggle('bg-accent/10', active);
        btn.classList.toggle('text-accent', active);
        btn.classList.toggle('font-medium', active);
    });
    writeUrlState();
    renderSummary();
    renderPayments();
}

// ── Init ────────────────────────────────────────────────────────────
mangosAuth.ready.then(user => {
    if (!user) return;

    readUrlState();
    setStatusFilter(statusFilter);

    document.getElementById('month-prev').addEventListener('click', () => {
        viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() - 1, 1);
        writeUrlState();
        loadAll();
    });
    document.getElementById('month-next').addEventListener('click', () => {
        viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() + 1, 1);
        writeUrlState();
        loadAll();
    });

    document.querySelectorAll('#status-tabs button').forEach(btn => {
        btn.addEventListener('click', () => setStatusFilter(btn.dataset.status));
    });

    document.getElementById('filter-category').addEventListener('change', e => {
        categoryFilter = e.target.value;
        writeUrlState();
        renderSummary();
        renderPayments();
    });

    document.getElementById('payment-form').addEventListener('submit', submitPaymentForm);
    document.getElementById('pmt-category').addEventListener('change', updateCategorySwatch);

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (!document.getElementById('payment-modal').classList.contains('hidden')) closePaymentModal();
        else if (!document.getElementById('payment-delete-modal').classList.contains('hidden')) closePaymentDelete();
    });

    loadAll();
});
</script>
