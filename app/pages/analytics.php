<!-- ────────────────────────────────────────────────────────────────────
     Analytics — deeper exploratory view.

     Currency-aware: defaults to "ARS + USD convertido" (mixed mode). USD
     amounts are converted via fx.toArs() and totals show a chip-mixed flag.

     Mobile-first: filter bar collapses to 2×2; time series chart and
     transfer matrix get horizontal scroll containers so they stay readable
     on phones without ugly squashing.
──────────────────────────────────────────────────────────────────── -->

<!-- Page header (desktop only — mobile topbar shows the page title) -->
<div class="hidden lg:flex items-end justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold">Análisis</h1>
        <p class="text-sm text-muted mt-1">Tendencias, categorías, cuentas y transferencias del último año.</p>
    </div>
</div>

<!-- Filter rail -->
<div class="filter-bar mb-5" role="group" aria-label="Filtros del reporte">
    <button class="filter-cell" type="button" id="f-period" aria-haspopup="menu">
        <span class="filter-label">Período</span>
        <span class="filter-value">
            <span class="v" id="f-period-value">12 meses</span>
            <svg class="chev" width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4"/></svg>
        </span>
    </button>

    <button class="filter-cell" type="button" id="f-currency" aria-haspopup="menu">
        <span class="filter-label">Moneda</span>
        <span class="filter-value">
            <span class="v">
                <span id="f-currency-text">ARS + USD</span>
                <span class="pill-tag" id="f-currency-tag">Convert</span>
            </span>
            <svg class="chev" width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4"/></svg>
        </span>
    </button>

    <button class="filter-cell" type="button" id="f-account" aria-haspopup="menu">
        <span class="filter-label">Cuenta</span>
        <span class="filter-value">
            <span class="v" id="f-account-value">Todas</span>
            <svg class="chev" width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4"/></svg>
        </span>
    </button>

    <div class="filter-cell is-readonly" aria-readonly="true">
        <span class="filter-label">Cotización</span>
        <span class="filter-value">
            <span class="v">
                <span id="f-fx-rate">—</span>
                <span class="text-muted text-[11px] font-normal tracking-wide ml-0.5">ARS / USD</span>
            </span>
        </span>
    </div>
</div>

<!-- ───────── Section 01 — Time series ───────── -->
<section class="mb-6">
    <div class="flex items-end justify-between mb-3 lg:mb-4 flex-wrap gap-3">
        <h2 class="text-base lg:text-xl font-semibold">Tendencia</h2>
        <div class="flex items-center gap-3 lg:gap-4 flex-wrap">
            <!-- Series legend -->
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-3 h-[3px] rounded-full bg-success"></span>Ingresos</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-[3px] rounded-full bg-accent"></span>Gastos</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-[3px] rounded-full bg-dark"></span>Neto</span>
            </div>
            <!-- YoY toggle (12m only) -->
            <div class="flex items-center gap-2.5 hidden" id="yoy-toggle-wrap">
                <span class="yoy-label">Año pasado</span>
                <div class="yoy-seg" id="yoy-seg">
                    <button type="button" data-mode="neto"     class="is-active">Neto</button>
                    <button type="button" data-mode="ingresos">Ingresos</button>
                    <button type="button" data-mode="gastos">Gastos</button>
                    <button type="button" data-mode="off"      class="off-state">Off</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-5 lg:p-7">
        <!-- KPIs above the chart -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-y-4 gap-x-5 mb-6">
            <div>
                <div class="mono-label flex items-center gap-2">
                    <span>Ingreso promedio</span>
                    <span class="chip-usd-real hidden" id="chip-income">USD real</span>
                </div>
                <div class="font-bold tabular-nums text-2xl lg:text-[28px] mt-1 text-success" id="kpi-income">—</div>
                <div class="text-[11px] font-semibold tabular-nums mt-0.5" id="kpi-income-delta">&nbsp;</div>
                <div class="mt-1.5 hidden" id="kpi-income-yoy"></div>
            </div>
            <div>
                <div class="mono-label flex items-center gap-2">
                    <span>Gasto promedio</span>
                    <span class="chip-usd-real hidden" id="chip-expense">USD real</span>
                </div>
                <div class="font-bold tabular-nums text-2xl lg:text-[28px] mt-1 text-accent" id="kpi-expense">—</div>
                <div class="text-[11px] font-semibold tabular-nums mt-0.5" id="kpi-expense-delta">&nbsp;</div>
                <div class="mt-1.5 hidden" id="kpi-expense-yoy"></div>
            </div>
            <div>
                <div class="mono-label flex items-center gap-2">
                    <span>Neto promedio</span>
                    <span class="chip-usd-real hidden" id="chip-net">USD real</span>
                </div>
                <div class="font-bold tabular-nums text-2xl lg:text-[28px] mt-1" id="kpi-net">—</div>
                <div class="text-[11px] font-semibold tabular-nums mt-0.5" id="kpi-net-delta">&nbsp;</div>
                <div class="mt-1.5 hidden" id="kpi-net-yoy"></div>
            </div>
            <div>
                <div class="mono-label">Mejor mes</div>
                <div class="text-base lg:text-lg font-semibold mt-1" id="kpi-best-month">—</div>
                <div class="text-[11px] font-semibold tabular-nums text-muted mt-0.5" id="kpi-best-amount">&nbsp;</div>
            </div>
        </div>

        <div class="h-px bg-border mb-5"></div>

        <!-- Chart (horizontal scroll on mobile so labels stay readable) -->
        <div class="overflow-x-auto -mx-2 px-2" id="chart-scroll">
            <svg viewBox="0 0 920 320" class="w-full h-auto block" style="min-width:680px" id="trend-chart" role="img" aria-label="Ingresos, gastos y neto"></svg>
        </div>
    </div>
</section>

<!-- ───────── Section 02 — Categorías + 03 — Cuentas (side by side on desktop) ───────── -->
<section class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-5 mb-6">

    <!-- Categorías -->
    <div class="card p-5 lg:p-7">
        <div class="flex items-end justify-between flex-wrap gap-3">
            <h2 class="text-base lg:text-xl font-semibold">Categorías</h2>
            <!-- Income/Expense toggle pill -->
            <div class="inline-flex p-[3px] bg-white border border-border rounded-full text-[11.5px]" role="tablist" id="cat-toggle">
                <button type="button" class="px-3.5 py-1.5 rounded-full font-medium is-active" data-kind="expense">Gastos</button>
                <button type="button" class="px-3.5 py-1.5 rounded-full font-medium text-muted" data-kind="income">Ingresos</button>
            </div>
        </div>

        <div class="mt-4 flex items-baseline gap-3 flex-wrap">
            <span class="font-bold tabular-nums text-2xl lg:text-[28px]" id="cat-total">—</span>
            <span class="text-muted text-xs" id="cat-total-meta">&nbsp;</span>
        </div>

        <div class="h-px bg-border mt-4"></div>

        <div class="mt-3 space-y-3.5" id="cat-bars">
            <p class="text-xs text-muted py-4 text-center">Cargando…</p>
        </div>

        <div class="h-px bg-border my-5"></div>

        <div class="grid grid-cols-2 gap-3 text-xs">
            <div>
                <span class="overline-muted">Mayor crecimiento</span>
                <div class="mt-1.5 flex items-baseline gap-2 flex-wrap" id="cat-rising">
                    <span class="text-muted">—</span>
                </div>
            </div>
            <div>
                <span class="overline-muted">Mayor reducción</span>
                <div class="mt-1.5 flex items-baseline gap-2 flex-wrap" id="cat-falling">
                    <span class="text-muted">—</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Cuentas -->
    <div class="card p-5 lg:p-7">
        <div class="flex items-end justify-between flex-wrap gap-3">
            <h2 class="text-base lg:text-xl font-semibold">Cuentas</h2>
            <div class="flex items-center gap-2.5 text-[11px]">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-success"></span>Ingreso</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-accent"></span>Gasto</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#A8A29E"></span>Transf.</span>
            </div>
        </div>

        <div class="h-px bg-border mt-4"></div>

        <div class="divide-y divide-border" id="account-rows">
            <p class="text-xs text-muted py-4 text-center">Cargando…</p>
        </div>
    </div>
</section>

<!-- ───────── Section 04 — Transfer matrix ───────── -->
<section class="mb-6">
    <div class="flex items-end justify-between mb-3 lg:mb-4 flex-wrap gap-3">
        <div>
            <h2 class="text-base lg:text-xl font-semibold">Transferencias</h2>
            <p class="text-xs text-muted mt-1.5 max-w-xl">No afectan ingresos ni gastos. Mostramos hacia dónde se movió la plata.</p>
        </div>
        <div class="flex items-center gap-3 text-xs text-muted">
            <span>Total <span class="font-semibold tabular-nums text-dark" id="tr-volume">—</span></span>
            <span>·</span>
            <span><span class="font-semibold tabular-nums text-dark" id="tr-count">—</span> ops</span>
        </div>
    </div>

    <div class="card p-5 lg:p-7">
        <div class="grid grid-cols-1 lg:grid-cols-[1.4fr_1fr] gap-6 lg:gap-7">
            <div>
                <div class="mono-label mb-3">Origen ↓ &nbsp;·&nbsp; Destino →</div>
                <div class="overflow-x-auto -mx-2 px-2" id="matrix-scroll">
                    <div class="matrix-grid" id="tr-matrix">
                        <p class="text-xs text-muted py-4 text-center" style="grid-column: 1 / -1">Sin transferencias en el período.</p>
                    </div>
                </div>
                <p class="text-[11px] text-muted mt-3">USD se convierte a ARS al tipo de cambio del día.</p>
            </div>
            <div>
                <div class="mono-label mb-3">Top flujos</div>
                <ol id="tr-top-flows" class="space-y-3">
                    <li class="text-xs text-muted py-3 text-center">Sin transferencias.</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- ───────── Section 05 — Anotación delay ───────── -->
<section class="mb-10">
    <div class="card p-5 lg:p-7">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_1.5fr] gap-6 lg:gap-7 items-start">
            <div>
                <h2 class="text-base lg:text-xl font-semibold">Demora de anotación</h2>
                <p class="text-xs text-muted mt-2 max-w-md">Cuántos días tardás en cargar tus movimientos. Menos demora = mejor memoria, totales más confiables.</p>
                <div class="mt-5 flex items-baseline gap-2.5">
                    <span class="font-bold tabular-nums text-3xl lg:text-[36px]" id="delay-avg">—</span>
                    <span class="text-muted text-sm">días en promedio</span>
                </div>
                <div class="text-[11px] font-semibold tabular-nums mt-1" id="delay-delta">&nbsp;</div>
            </div>

            <div>
                <div class="mono-label mb-3" id="delay-meta">Distribución</div>
                <div class="space-y-2.5" id="delay-buckets">
                    <p class="text-xs text-muted py-3 text-center">Sin movimientos para calcular.</p>
                </div>
                <div class="h-px bg-border mt-5"></div>
                <p class="text-[11px] text-muted mt-3">Días entre la fecha del movimiento y la fecha de carga. Las recurrentes automáticas no cuentan.</p>
            </div>
        </div>
    </div>
</section>

<script>
const MONTH_LABELS_SHORT = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
const CHART_PALETTE = ['#D97706','#B45309','#92400E','#78350F','#57534E','#A8A29E','#1f2937','#7c3aed','#0ea5e9','#15803D','#84CC16','#EAB308'];

const STATE = {
    period: '12m',          // '3m' | '6m' | '12m' | 'ytd'
    currency: 'mixed',      // 'mixed' | 'ARS' | 'USD'
    accountId: 'all',       // 'all' | account.id
    catKind: 'expense',     // 'expense' | 'income'
    // YoY toggle on the time-series chart (12m period only). Persisted in
    // localStorage. Switching modes triggers re-render but no extra fetch.
    yoyMode: localStorage.getItem('mangos.yoyMode') || 'neto',  // 'neto' | 'ingresos' | 'gastos' | 'off'
};

let cache = {
    txs: [],
    cmpTxs: [],            // previous-period transactions for "vs antes" deltas
    yoyTxs: [],            // same calendar window 1 year ago, for YoY USD-real comparison
    fxHistory: {},         // { 'YYYY-MM-DD': rate } over the YoY window
    fxHistorySorted: [],   // sorted dates for fast nearest-preceding lookup
    accounts: [],
    expenseCats: [],
    incomeCats: [],
};

// ── Tiny DOM helper ───────────────────────────────────────────────────
function el(tag, opts) {
    const e = document.createElement(tag);
    if (!opts) return e;
    if (opts.className) e.className = opts.className;
    if (opts.text != null) e.textContent = opts.text;
    if (opts.style) for (const k of Object.keys(opts.style)) e.style[k] = opts.style[k];
    if (opts.attrs) for (const k of Object.keys(opts.attrs)) e.setAttribute(k, opts.attrs[k]);
    return e;
}
function elNS(tag, opts) {
    const e = document.createElementNS('http://www.w3.org/2000/svg', tag);
    if (!opts) return e;
    if (opts.attrs) for (const k of Object.keys(opts.attrs)) e.setAttribute(k, opts.attrs[k]);
    if (opts.text != null) e.textContent = opts.text;
    return e;
}
function appendAll(parent, ...children) {
    children.forEach(c => { if (c) parent.appendChild(c); });
    return parent;
}

function fmtArs(n)  { return formatPrice(n, 'ARS'); }
function fmtUsd(n)  { return formatPrice(n, 'USD'); }
function fmtKArs(n) {
    if (n === 0) return '$ 0';
    if (Math.abs(n) >= 1e6) return '$ ' + (n / 1e6).toLocaleString('es-AR', { maximumFractionDigits: 1 }) + ' M';
    if (Math.abs(n) >= 1000) return '$ ' + Math.round(n / 1000) + ' k';
    return fmtArs(n);
}
function isoDay(d) { return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; }
function parseLocalDate(s) {
    if (!s) return null;
    const m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? new Date(+m[1], +m[2]-1, +m[3]) : null;
}

function periodToMonths(p) {
    if (p === '3m') return 3;
    if (p === '6m') return 6;
    if (p === 'ytd') return new Date().getMonth() + 1;
    return 12;
}
function periodLabel(p) {
    if (p === '3m')  return '3 meses';
    if (p === '6m')  return '6 meses';
    if (p === 'ytd') return 'Este año';
    return '12 meses';
}

function periodRange() {
    const months = periodToMonths(STATE.period);
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth() - (months - 1), 1);
    const end   = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    return { start, end, months };
}
function comparisonRange() {
    const cur = periodRange();
    const start = new Date(cur.start.getFullYear(), cur.start.getMonth() - cur.months, 1);
    const end   = new Date(cur.start.getFullYear(), cur.start.getMonth(), 0);
    return { start, end, months: cur.months };
}

// ── YoY (USD-real) helpers ────────────────────────────────────────────
// The YoY comparison window is the same calendar months as the current
// period but one year earlier. Always 12 months, since the toggle only
// shows on 12m periods.
function yoyRange() {
    const cur = periodRange();
    const start = new Date(cur.start.getFullYear() - 1, cur.start.getMonth(), 1);
    const end   = new Date(cur.end.getFullYear()   - 1, cur.end.getMonth(),   cur.end.getDate());
    return { start, end };
}

// Nearest-preceding lookup over the historical FX map. Walks backward from
// `dateStr` ('YYYY-MM-DD') and returns the closest available rate. Returns
// null if nothing is found (caller should fall through to today's rate).
function fxRateForDate(dateStr) {
    if (cache.fxHistory[dateStr] != null) return cache.fxHistory[dateStr];
    const dates = cache.fxHistorySorted;
    if (!dates || dates.length === 0) return null;
    // Binary search: find the largest date <= dateStr
    let lo = 0, hi = dates.length - 1, best = -1;
    while (lo <= hi) {
        const mid = (lo + hi) >> 1;
        if (dates[mid] <= dateStr) { best = mid; lo = mid + 1; }
        else hi = mid - 1;
    }
    return best >= 0 ? cache.fxHistory[dates[best]] : null;
}

// Re-express a historical ARS amount as "today's pesos" via the USD anchor:
//   ars_today = ars_then / rate_at_tx_date * rate_today
// USD-native transactions just multiply by today's rate (their "real value"
// already lives in USD — re-converting via historical rate would round-trip
// through the same number).
function txToTodayArs(tx, todayUsdRate) {
    const cur = tx.currency || 'ARS';
    const native = Math.abs(Number(tx.amount || 0));
    if (cur !== 'ARS') return native * fx.rateFor(cur);

    const date = (tx.due_ts || tx.paid_ts || '').slice(0, 10);
    const rateAtDate = fxRateForDate(date);
    if (!rateAtDate || rateAtDate <= 0) return native; // gracefully no-op
    return (native / rateAtDate) * todayUsdRate;
}

function amountInArs(tx) { return Number(tx.amount || 0) * fx.rateFor(tx.currency || 'ARS'); }
function txPassesCurrencyFilter(tx) {
    const c = tx.currency || 'ARS';
    if (STATE.currency === 'mixed') return true;
    return c === STATE.currency;
}
function txPassesAccountFilter(tx) {
    if (STATE.accountId === 'all') return true;
    return tx.account_id === STATE.accountId;
}
function txPassesAllFilters(tx) {
    return txPassesCurrencyFilter(tx) && txPassesAccountFilter(tx);
}

// ── Time-series chart ─────────────────────────────────────────────────
function renderTrend(txs) {
    const range = periodRange();
    const monthCount = range.months;
    const months = [];
    for (let i = 0; i < monthCount; i++) {
        const d = new Date(range.start.getFullYear(), range.start.getMonth() + i, 1);
        months.push({
            year: d.getFullYear(),
            month: d.getMonth(),
            label: MONTH_LABELS_SHORT[d.getMonth()],
            income: 0,
            expense: 0,
        });
    }
    const byKey = {};
    months.forEach((m, i) => byKey[`${m.year}-${m.month}`] = i);

    txs.filter(txPassesAllFilters).forEach(tx => {
        const d = parseLocalDate(tx.due_ts || tx.paid_ts);
        if (!d) return;
        const i = byKey[`${d.getFullYear()}-${d.getMonth()}`];
        if (i == null) return;
        const ars = amountInArs(tx);
        if (tx.kind === 'income') months[i].income += Math.abs(ars);
        else if (tx.kind === 'expense' || tx.kind === 'fee') months[i].expense += Math.abs(ars);
    });

    // YoY monthly buckets (USD-anchored to today's pesos). Only computed
    // when the toggle is on AND we're on 12m. Same shape as `months` so the
    // ghost-line plotting is symmetric with the current-year series.
    const yoyActive = STATE.period === '12m' && STATE.yoyMode !== 'off';
    const yoyMonths = months.map(m => ({ year: m.year - 1, month: m.month, income: 0, expense: 0 }));
    let yoyTotalIncome = 0, yoyTotalExpense = 0;
    if (yoyActive && cache.yoyTxs.length > 0) {
        const todayUsd = fx.rateFor('USD') || 1;
        const yoyByKey = {};
        yoyMonths.forEach((m, i) => yoyByKey[`${m.year}-${m.month}`] = i);

        cache.yoyTxs.filter(txPassesAllFilters).forEach(tx => {
            const d = parseLocalDate(tx.due_ts || tx.paid_ts);
            if (!d) return;
            const i = yoyByKey[`${d.getFullYear()}-${d.getMonth()}`];
            if (i == null) return;
            const adj = txToTodayArs(tx, todayUsd);
            if (tx.kind === 'income')                                yoyMonths[i].income  += adj;
            else if (tx.kind === 'expense' || tx.kind === 'fee')     yoyMonths[i].expense += adj;
        });
        yoyMonths.forEach(m => { yoyTotalIncome += m.income; yoyTotalExpense += m.expense; });
    }

    let maxVal = 0;
    months.forEach(m => {
        if (m.income > maxVal) maxVal = m.income;
        if (m.expense > maxVal) maxVal = m.expense;
    });
    if (yoyActive) {
        yoyMonths.forEach(m => {
            if (m.income > maxVal) maxVal = m.income;
            if (m.expense > maxVal) maxVal = m.expense;
        });
    }
    if (maxVal === 0) maxVal = 1;
    // Round up to a clean tick
    const niceMax = niceUpper(maxVal);

    const W = 920, H = 320;
    const PAD_L = 50, PAD_R = 20, PAD_T = 40, PAD_B = 40;
    const innerW = W - PAD_L - PAD_R;
    const innerH = H - PAD_T - PAD_B;

    const xs = months.map((_, i) =>
        monthCount === 1 ? PAD_L + innerW / 2 : PAD_L + (i / (monthCount - 1)) * innerW
    );
    const yScale = v => PAD_T + innerH - (v / niceMax) * innerH;

    const svg = document.getElementById('trend-chart');
    svg.textContent = '';

    // Grid lines
    const gridGroup = elNS('g', { attrs: { stroke: '#E8E2DA', 'stroke-width': '1' } });
    for (let i = 0; i <= 4; i++) {
        const y = PAD_T + (innerH / 4) * i;
        gridGroup.appendChild(elNS('line', { attrs: { x1: PAD_L, y1: y, x2: W - PAD_R, y2: y } }));
    }
    svg.appendChild(gridGroup);

    // Y labels
    const yLabels = elNS('g', { attrs: { 'font-family': 'Inter', 'font-size': '10', fill: '#8C857D', 'font-weight': '500' } });
    for (let i = 0; i <= 4; i++) {
        const v = niceMax - (niceMax / 4) * i;
        const y = PAD_T + (innerH / 4) * i;
        const t = elNS('text', { attrs: { x: PAD_L - 8, y: y + 3, 'text-anchor': 'end' }, text: fmtKArs(v) });
        yLabels.appendChild(t);
    }
    svg.appendChild(yLabels);

    // X labels (months) — last is bold (current month)
    const xLabels = elNS('g', { attrs: { 'font-family': 'Inter', 'font-size': '10.5', 'font-weight': '500' } });
    months.forEach((m, i) => {
        const isCurrent = i === months.length - 1;
        xLabels.appendChild(elNS('text', {
            attrs: {
                x: xs[i], y: H - 12, 'text-anchor': 'middle',
                fill: isCurrent ? '#292524' : '#8C857D',
                'font-weight': isCurrent ? '700' : '500',
            },
            text: m.label,
        }));
    });
    svg.appendChild(xLabels);

    // Income area + line
    const incomePts = months.map((m, i) => `${xs[i]},${yScale(m.income)}`).join(' ');
    const areaPath = `M ${xs[0]},${yScale(months[0].income)} ` +
        months.map((m, i) => `L ${xs[i]},${yScale(m.income)}`).join(' ') +
        ` L ${xs[xs.length-1]},${PAD_T + innerH} L ${xs[0]},${PAD_T + innerH} Z`;
    svg.appendChild(elNS('path', { attrs: { d: areaPath, fill: '#15803D', opacity: '0.06' } }));
    svg.appendChild(elNS('polyline', { attrs: {
        fill: 'none', stroke: '#15803D', 'stroke-width': '2',
        'stroke-linecap': 'round', 'stroke-linejoin': 'round', points: incomePts,
    }}));
    months.forEach((m, i) => {
        const isLast = i === months.length - 1;
        svg.appendChild(elNS('circle', { attrs: {
            cx: xs[i], cy: yScale(m.income), r: isLast ? 4 : 3,
            fill: isLast ? '#15803D' : '#FFFBF5', stroke: '#15803D', 'stroke-width': '2',
        }}));
    });

    // Expense line
    const expensePts = months.map((m, i) => `${xs[i]},${yScale(m.expense)}`).join(' ');
    svg.appendChild(elNS('polyline', { attrs: {
        fill: 'none', stroke: '#D97706', 'stroke-width': '2',
        'stroke-linecap': 'round', 'stroke-linejoin': 'round', points: expensePts,
    }}));
    months.forEach((m, i) => {
        const isLast = i === months.length - 1;
        svg.appendChild(elNS('circle', { attrs: {
            cx: xs[i], cy: yScale(m.expense), r: isLast ? 4 : 3,
            fill: isLast ? '#D97706' : '#FFFBF5', stroke: '#D97706', 'stroke-width': '2',
        }}));
    });

    // Net line (dashed)
    const netPts = months.map((m, i) => `${xs[i]},${yScale(Math.max(0, m.income - m.expense))}`).join(' ');
    svg.appendChild(elNS('polyline', { attrs: {
        fill: 'none', stroke: '#292524', 'stroke-width': '1.5',
        'stroke-dasharray': '2 4', 'stroke-linecap': 'round', points: netPts,
    }}));

    // YoY ghost line — drawn AFTER current-year series so it sits beneath
    // the focal lines (lower z-order in SVG paint order). Plot only the
    // single series the user picked so the chart doesn't get noisy.
    if (yoyActive) {
        let pts = null;
        if (STATE.yoyMode === 'ingresos') pts = yoyMonths.map((m, i) => `${xs[i]},${yScale(m.income)}`).join(' ');
        else if (STATE.yoyMode === 'gastos') pts = yoyMonths.map((m, i) => `${xs[i]},${yScale(m.expense)}`).join(' ');
        else if (STATE.yoyMode === 'neto') pts = yoyMonths.map((m, i) => `${xs[i]},${yScale(Math.max(0, m.income - m.expense))}`).join(' ');

        if (pts) {
            svg.appendChild(elNS('polyline', { attrs: {
                fill: 'none', stroke: '#1e3a8a', 'stroke-width': '1.8',
                'stroke-dasharray': '5 4', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
                opacity: '0.55', points: pts,
            }}));
            yoyMonths.forEach((m, i) => {
                let cy;
                if (STATE.yoyMode === 'ingresos') cy = yScale(m.income);
                else if (STATE.yoyMode === 'gastos') cy = yScale(m.expense);
                else cy = yScale(Math.max(0, m.income - m.expense));
                svg.appendChild(elNS('circle', { attrs: {
                    cx: xs[i], cy, r: 2.5, fill: '#FFFBF5', stroke: '#1e3a8a', 'stroke-width': '1.5', opacity: '0.55',
                }}));
            });
        }
    }

    // Best-month annotation
    let bestIdx = 0, bestNet = -Infinity;
    months.forEach((m, i) => { const n = m.income - m.expense; if (n > bestNet) { bestNet = n; bestIdx = i; } });
    if (bestNet > 0) {
        const bx = xs[bestIdx];
        const labelText = `${months[bestIdx].label} · ${fmtKArs(bestNet)}`;
        // Approx label width
        const lw = Math.max(80, labelText.length * 6.5);
        const lx = Math.min(W - PAD_R - lw, Math.max(PAD_L, bx - lw / 2));
        svg.appendChild(elNS('rect', { attrs: { x: lx, y: 14, width: lw, height: 22, rx: 6, fill: '#15803D' } }));
        svg.appendChild(elNS('text', { attrs: {
            x: lx + lw / 2, y: 29, 'text-anchor': 'middle',
            'font-family': 'Inter', 'font-size': '10.5', 'font-weight': '600', fill: '#FFFBF5',
        }, text: labelText }));
        svg.appendChild(elNS('line', { attrs: {
            x1: bx, y1: 36, x2: bx, y2: yScale(months[bestIdx].income) - 6,
            stroke: '#15803D', 'stroke-width': '1',
        }}));
    }

    // KPIs above chart
    const totalIncome  = months.reduce((s, m) => s + m.income, 0);
    const totalExpense = months.reduce((s, m) => s + m.expense, 0);
    const totalNet     = totalIncome - totalExpense;
    const monthsWithData = months.filter(m => m.income > 0 || m.expense > 0).length || 1;

    document.getElementById('kpi-income').textContent  = fmtKArs(totalIncome / monthsWithData);
    document.getElementById('kpi-expense').textContent = fmtKArs(totalExpense / monthsWithData);
    document.getElementById('kpi-net').textContent     = fmtKArs(totalNet / monthsWithData);

    if (bestNet > 0) {
        document.getElementById('kpi-best-month').textContent = months[bestIdx].label + ' ' + months[bestIdx].year;
        document.getElementById('kpi-best-amount').textContent = fmtArs(bestNet) + ' neto';
    } else {
        document.getElementById('kpi-best-month').textContent = '—';
        document.getElementById('kpi-best-amount').textContent = '';
    }

    // Compute cmp deltas (vs the equivalent previous period)
    let cmpInc = 0, cmpExp = 0;
    cache.cmpTxs.filter(txPassesAllFilters).forEach(tx => {
        const ars = amountInArs(tx);
        if (tx.kind === 'income') cmpInc += Math.abs(ars);
        else if (tx.kind === 'expense' || tx.kind === 'fee') cmpExp += Math.abs(ars);
    });
    setDeltaPill('kpi-income-delta', totalIncome, cmpInc);
    setDeltaPill('kpi-expense-delta', totalExpense, cmpExp);
    setDeltaPill('kpi-net-delta', totalNet, cmpInc - cmpExp);

    // YoY (USD-real) pill — only shows on the KPI matching the active mode.
    renderKpiYoy('income',  'income',  totalIncome,  yoyTotalIncome);
    renderKpiYoy('expense', 'expense', totalExpense, yoyTotalExpense);
    renderKpiYoy('net',     'net',     totalNet,     yoyTotalIncome - yoyTotalExpense);
}

// One KPI's chip + pill. `kind` is 'income' | 'expense' | 'net' and
// determines which mode triggers visibility.
function renderKpiYoy(idKey, kind, currentTotal, yoyTotal) {
    const chip     = document.getElementById('chip-'    + idKey);
    const pillSlot = document.getElementById('kpi-'     + idKey + '-yoy');
    if (!chip || !pillSlot) return;

    const yoyActive = STATE.period === '12m' && STATE.yoyMode !== 'off';
    const matches = (
        (STATE.yoyMode === 'ingresos' && kind === 'income') ||
        (STATE.yoyMode === 'gastos'   && kind === 'expense') ||
        (STATE.yoyMode === 'neto'     && kind === 'net')
    );

    if (!yoyActive || !matches) {
        chip.classList.add('hidden');
        pillSlot.classList.add('hidden');
        pillSlot.textContent = '';
        return;
    }

    chip.classList.remove('hidden');
    pillSlot.classList.remove('hidden');
    pillSlot.textContent = '';

    if (Math.abs(yoyTotal) < 1) {
        appendAll(pillSlot, el('span', { className: 'pill-yoy', text: 'Sin datos del año pasado' }));
        return;
    }
    const pct = ((currentTotal - yoyTotal) / Math.abs(yoyTotal)) * 100;
    // Coloring: income/net up = good, expense up = bad
    const goodWhenUp = (kind !== 'expense');
    const cls = pct === 0 ? '' : ((pct > 0) === goodWhenUp ? 'is-pos' : 'is-neg');
    const lastYear = yoyRange().start.getFullYear();
    appendAll(pillSlot, el('span', {
        className: 'pill-yoy ' + cls,
        text: (pct > 0 ? '+' : '') + pct.toFixed(1) + '% real vs ' + lastYear,
    }));
}

function setDeltaPill(id, current, prev) {
    const el = document.getElementById(id);
    el.classList.remove('text-success', 'text-danger', 'text-muted');
    if (Math.abs(prev) < 1) {
        el.textContent = '—';
        el.classList.add('text-muted');
        return;
    }
    const pct = ((current - prev) / Math.abs(prev)) * 100;
    el.textContent = (pct > 0 ? '+' : '') + pct.toFixed(1) + '%';
    el.classList.add(pct >= 0 ? 'text-success' : 'text-danger');
}

function niceUpper(v) {
    if (v <= 0) return 1;
    const exp = Math.pow(10, Math.floor(Math.log10(v)));
    const m = v / exp;
    let nice;
    if (m <= 1) nice = 1;
    else if (m <= 2) nice = 2;
    else if (m <= 5) nice = 5;
    else nice = 10;
    return nice * exp;
}

// ── Categories ────────────────────────────────────────────────────────
function renderCategories(txs, cmpTxs) {
    const kind = STATE.catKind;
    const cats = kind === 'income' ? cache.incomeCats : cache.expenseCats;
    const idField = kind === 'income' ? 'income_category_id' : 'expense_category_id';

    const period = aggregateByCategory(txs, kind, idField);
    const cmpAgg = aggregateByCategory(cmpTxs, kind, idField);

    const total = period.reduce((s, c) => s + c.amount, 0);
    document.getElementById('cat-total').textContent = total > 0 ? fmtArs(total) : '—';
    document.getElementById('cat-total-meta').textContent = period.length === 0
        ? 'sin movimientos'
        : `en ${period.length} categoría${period.length === 1 ? '' : 's'} · ${periodLabel(STATE.period)}`;

    const root = document.getElementById('cat-bars');
    root.textContent = '';

    if (period.length === 0 || total === 0) {
        appendAll(root, el('p', { className: 'text-xs text-muted py-4 text-center', text: 'Sin movimientos en el período.' }));
        document.getElementById('cat-rising').textContent = '';
        document.getElementById('cat-falling').textContent = '';
        return;
    }

    period.forEach((c, i) => {
        const pct = (c.amount / total) * 100;
        const color = c.color || CHART_PALETTE[i % CHART_PALETTE.length];

        const wrap = el('div');
        const head = el('div', { className: 'flex items-center justify-between text-[13px]' });
        const left = el('span', { className: 'flex items-center gap-2 min-w-0' });
        appendAll(left,
            el('span', { className: 'w-2 h-2 rounded-full flex-shrink-0', style: { background: color } }),
            el('span', { className: 'truncate', text: c.name }),
        );
        const right = el('span', { className: 'font-semibold tabular-nums flex-shrink-0 ml-2' });
        appendAll(right,
            el('span', { text: fmtArs(c.amount) }),
            el('span', { className: 'text-muted text-[11px] ml-1.5 font-normal', text: pct.toFixed(0) + '%' }),
        );
        appendAll(head, left, right);
        wrap.appendChild(head);

        const bar = el('div', { className: 'mt-1.5 h-1.5 rounded-full overflow-hidden bg-[#F4EFE7]' });
        const fill = el('div', { className: 'h-full', style: { width: pct + '%', background: color } });
        bar.appendChild(fill);
        wrap.appendChild(bar);
        root.appendChild(wrap);
    });

    // Rising / falling
    const cmpById = Object.fromEntries(cmpAgg.map(c => [c.id, c.amount]));
    let rising = null, falling = null;
    period.forEach(c => {
        const prev = cmpById[c.id] || 0;
        if (prev <= 0) return;
        const pct = ((c.amount - prev) / prev) * 100;
        if (!rising || pct > rising.pct)  rising  = { ...c, pct };
        if (!falling || pct < falling.pct) falling = { ...c, pct };
    });
    setCatDelta('cat-rising', rising, kind);
    setCatDelta('cat-falling', falling, kind);
}

function setCatDelta(id, item, kind) {
    const root = document.getElementById(id);
    root.textContent = '';
    if (!item) {
        appendAll(root, el('span', { className: 'text-muted', text: '—' }));
        return;
    }
    appendAll(root, el('span', { className: 'text-sm font-medium', text: item.name }));
    const pillCls = id === 'cat-rising'
        ? (kind === 'income' ? 'text-success' : 'text-danger')
        : (kind === 'income' ? 'text-danger' : 'text-success');
    appendAll(root, el('span', {
        className: 'text-xs font-semibold tabular-nums ' + pillCls,
        text: (item.pct > 0 ? '+' : '') + item.pct.toFixed(0) + '% vs antes',
    }));
}

function aggregateByCategory(txs, kind, idField) {
    const cats = kind === 'income' ? cache.incomeCats : cache.expenseCats;
    const map = {};
    txs.filter(txPassesAllFilters).forEach(tx => {
        if (kind === 'income') {
            if (tx.kind !== 'income') return;
        } else {
            if (tx.kind !== 'expense' && tx.kind !== 'fee') return;
        }
        const cid = tx[idField] || '__none';
        const ars = Math.abs(amountInArs(tx));
        map[cid] = (map[cid] || 0) + ars;
    });
    return Object.entries(map).map(([id, amount]) => {
        if (id === '__none') return { id, name: 'Sin categoría', color: '#A8A29E', amount };
        const cat = cats.find(c => c.id === id);
        return { id, name: cat ? cat.name : 'Eliminada', color: (cat && cat.color) || '#A8A29E', amount };
    }).sort((a, b) => b.amount - a.amount);
}

// ── Per-account stacked bars ──────────────────────────────────────────
function renderAccountRows(txs) {
    const root = document.getElementById('account-rows');
    root.textContent = '';

    const visibleAccounts = STATE.accountId === 'all' ? cache.accounts : cache.accounts.filter(a => a.id === STATE.accountId);
    if (visibleAccounts.length === 0) {
        appendAll(root, el('p', { className: 'text-xs text-muted py-4 text-center', text: 'Sin cuentas para mostrar.' }));
        return;
    }

    // Per-account totals (account-native amounts so the labels make sense per currency)
    const perAcc = {};
    txs.forEach(tx => {
        if (!tx.account_id) return;
        const a = perAcc[tx.account_id] = perAcc[tx.account_id] || { income: 0, expense: 0, transfer: 0, netArs: 0 };
        const amt = Number(tx.amount || 0);
        const ars = amountInArs(tx);
        if (tx.kind === 'income') a.income += Math.abs(amt);
        else if (tx.kind === 'expense' || tx.kind === 'fee') a.expense += Math.abs(amt);
        else if (tx.kind === 'transfer') a.transfer += Math.abs(amt);
        if (tx.kind === 'income') a.netArs += Math.abs(ars);
        else if (tx.kind === 'expense' || tx.kind === 'fee') a.netArs -= Math.abs(ars);
    });

    // Find the global ARS max to scale bars consistently
    let globalMaxArs = 0;
    visibleAccounts.forEach(acc => {
        const s = perAcc[acc.id] || { income: 0, expense: 0, transfer: 0 };
        const rate = fx.rateFor(acc.currency || 'ARS');
        const totalArs = (s.income + s.expense + s.transfer) * rate;
        if (totalArs > globalMaxArs) globalMaxArs = totalArs;
    });
    if (globalMaxArs === 0) globalMaxArs = 1;

    visibleAccounts.forEach(acc => {
        const s = perAcc[acc.id] || { income: 0, expense: 0, transfer: 0, netArs: 0 };
        const rate = fx.rateFor(acc.currency || 'ARS');
        const totalArs = (s.income + s.expense + s.transfer) * rate;
        const widthPct = (totalArs / globalMaxArs) * 100;

        const incPct = totalArs > 0 ? (s.income * rate / globalMaxArs) * 100 : 0;
        const expPct = totalArs > 0 ? (s.expense * rate / globalMaxArs) * 100 : 0;
        const trPct  = totalArs > 0 ? (s.transfer * rate / globalMaxArs) * 100 : 0;

        const wrap = el('div', { className: 'py-3.5' });

        // Header row
        const head = el('div', { className: 'flex items-center justify-between mb-2 gap-2 flex-wrap' });
        const left = el('div', { className: 'flex items-center gap-2.5 min-w-0' });
        const swatch = el('span', {
            className: 'w-7 h-7 rounded-lg flex-shrink-0 flex items-center justify-center text-[10px] font-semibold tracking-wider text-light',
            style: { background: acc.color || '#1f2937' },
        });
        const initials = (acc.name || '?').split(/\s+/).slice(0,2).map(s => s[0] || '').join('').toUpperCase().slice(0,3) || '?';
        swatch.textContent = initials;
        appendAll(left, swatch, el('span', { className: 'text-sm font-semibold truncate', text: acc.name || 'Cuenta' }));
        if (acc.is_default == 1) appendAll(left, el('span', { className: 'badge badge-muted', text: 'Default' }));
        if ((acc.currency || 'ARS') !== 'ARS') appendAll(left, el('span', { className: 'chip-mixed', text: acc.currency }));
        head.appendChild(left);

        const right = el('span', { className: 'text-xs text-muted flex items-center gap-1' });
        right.appendChild(document.createTextNode('Neto '));
        const netSpan = el('span', { className: 'font-semibold tabular-nums', text: (s.netArs >= 0 ? '+' : '−') + fmtArs(Math.abs(s.netArs)) });
        netSpan.classList.add(s.netArs >= 0 ? 'text-success' : 'text-danger');
        right.appendChild(netSpan);
        head.appendChild(right);
        wrap.appendChild(head);

        // Stacked bar
        const bar = el('div', { className: 'flex h-2.5 rounded-full overflow-hidden bg-[#F4EFE7]' });
        if (incPct > 0) bar.appendChild(el('span', { style: { width: incPct + '%', background: '#15803D' } }));
        if (expPct > 0) bar.appendChild(el('span', { style: { width: expPct + '%', background: '#D97706' } }));
        if (trPct  > 0) bar.appendChild(el('span', { style: { width: trPct  + '%', background: '#A8A29E' } }));
        wrap.appendChild(bar);

        const meta = el('div', { className: 'mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11.5px] text-muted' });
        appendAll(meta,
            buildMetaPair('+', s.income, acc.currency, 'ingreso'),
            buildMetaPair('−', s.expense, acc.currency, 'gasto'),
            buildMetaPair('', s.transfer, acc.currency, 'transferido'),
        );
        wrap.appendChild(meta);

        root.appendChild(wrap);
    });
}

function buildMetaPair(sign, amount, currency, label) {
    const span = el('span');
    if (amount === 0) {
        span.classList.add('opacity-50');
        span.textContent = '— ' + label;
        return span;
    }
    span.appendChild(document.createTextNode(sign));
    appendAll(span, el('span', { className: 'font-semibold tabular-nums text-dark', text: formatPrice(amount, currency) }));
    span.appendChild(document.createTextNode(' ' + label));
    return span;
}

// ── Transfer matrix ───────────────────────────────────────────────────
function renderTransferMatrix(txs) {
    const accs = cache.accounts;
    const accById = Object.fromEntries(accs.map(a => [a.id, a]));

    const groups = {};
    txs.forEach(tx => {
        if (!txPassesCurrencyFilter(tx)) return;
        if (tx.kind !== 'transfer' || !tx.transfer_id) return;
        (groups[tx.transfer_id] = groups[tx.transfer_id] || []).push(tx);
    });

    let volume = 0, count = 0;
    const flowTotals = {};   // "fromId|toId" → ars
    const flowCounts = {};

    Object.values(groups).forEach(legs => {
        const out = legs.find(t => Number(t.amount) < 0 && t.kind === 'transfer');
        const inn = legs.find(t => Number(t.amount) > 0 && t.kind === 'transfer');
        if (!out || !inn) return;

        // Account filter for the matrix: include if either leg matches
        if (STATE.accountId !== 'all' && out.account_id !== STATE.accountId && inn.account_id !== STATE.accountId) return;

        count++;
        const ars = Math.abs(amountInArs(out));
        volume += ars;
        const key = `${out.account_id}|${inn.account_id}`;
        flowTotals[key] = (flowTotals[key] || 0) + ars;
        flowCounts[key] = (flowCounts[key] || 0) + 1;
    });

    document.getElementById('tr-volume').textContent = volume > 0 ? fmtArs(volume) : '—';
    document.getElementById('tr-count').textContent = String(count);

    // Build matrix grid
    const grid = document.getElementById('tr-matrix');
    grid.textContent = '';

    if (accs.length === 0 || count === 0) {
        const empty = el('p', { className: 'text-xs text-muted py-6 text-center' });
        empty.style.gridColumn = '1 / -1';
        empty.textContent = 'Sin transferencias en el período.';
        grid.appendChild(empty);
        // Top flows empty too
        const ol = document.getElementById('tr-top-flows');
        ol.textContent = '';
        ol.appendChild(el('li', { className: 'text-xs text-muted py-3 text-center', text: 'Sin transferencias.' }));
        return;
    }

    // Set grid template based on N accounts (capped at 6 cols for sanity)
    const cols = Math.min(accs.length, 6);
    grid.style.gridTemplateColumns = `100px repeat(${cols}, minmax(0, 1fr))`;
    grid.style.minWidth = (100 + cols * 110) + 'px';

    // Header row: empty corner + each account name
    grid.appendChild(el('div', { className: 'h' }));
    accs.slice(0, cols).forEach(a => {
        const cell = el('div', { className: 'h' });
        cell.appendChild(el('span', { text: a.name }));
        if ((a.currency || 'ARS') !== 'ARS') {
            const meta = el('span', { className: 'text-[10px] text-muted font-medium ml-1', text: a.currency });
            cell.appendChild(meta);
        }
        grid.appendChild(cell);
    });

    // Body rows
    accs.slice(0, cols).forEach(from => {
        const rh = el('div', { className: 'row-h' });
        rh.appendChild(el('span', { text: from.name }));
        if ((from.currency || 'ARS') !== 'ARS') {
            const meta = el('span', { className: 'text-[10px] text-muted font-medium ml-1', text: from.currency });
            rh.appendChild(meta);
        }
        grid.appendChild(rh);

        accs.slice(0, cols).forEach(to => {
            if (from.id === to.id) {
                grid.appendChild(el('div', { className: 'self matrix-cell', text: '—' }));
                return;
            }
            const key = `${from.id}|${to.id}`;
            const ars = flowTotals[key] || 0;
            const c = flowCounts[key] || 0;
            const cell = el('div', { className: 'matrix-cell' });
            if (ars === 0) {
                cell.classList.add('text-muted');
                cell.textContent = '—';
            } else {
                appendAll(cell,
                    el('div', { text: fmtArs(ars) }),
                    el('div', { className: 'meta', text: c + ' op' + (c === 1 ? '' : 's') }),
                );
            }
            grid.appendChild(cell);
        });
    });

    // Top flows list
    const ol = document.getElementById('tr-top-flows');
    ol.textContent = '';
    const sorted = Object.entries(flowTotals).sort((a,b) => b[1] - a[1]).slice(0, 5);
    sorted.forEach(([key, ars], i) => {
        const [fromId, toId] = key.split('|');
        const li = el('li', { className: 'flex items-center justify-between border-b border-border pb-3 last:border-b-0 last:pb-0' });
        const left = el('div', { className: 'flex items-center gap-2 text-[13.5px] min-w-0' });
        appendAll(left,
            el('span', { className: 'text-muted tabular-nums w-5 flex-shrink-0', text: '0' + (i+1) }),
            el('span', { className: 'font-medium truncate', text: (accById[fromId] && accById[fromId].name) || '?' }),
            el('span', { className: 'text-muted flex-shrink-0', text: '→' }),
            el('span', { className: 'font-medium truncate', text: (accById[toId] && accById[toId].name) || '?' }),
        );
        appendAll(li, left, el('div', { className: 'font-semibold tabular-nums text-sm flex-shrink-0', text: fmtArs(ars) }));
        ol.appendChild(li);
    });
}

// ── Anotación delay ───────────────────────────────────────────────────
function renderDelay(txs) {
    // Filter:
    //  - paid_ts and created_ts both present
    //  - recurrent_id is null (auto-generated rows excluded)
    //  - paid_ts <= created_ts (clamp future-dated)
    //  - account / currency filter
    const eligible = txs.filter(tx => {
        if (!txPassesAllFilters(tx)) return false;
        if (tx.recurrent_id) return false;
        const paid = parseLocalDate(tx.paid_ts);
        const created = parseLocalDate(tx.created_ts);
        if (!paid || !created) return false;
        if (paid > created) return false;
        return true;
    });

    if (eligible.length === 0) {
        document.getElementById('delay-avg').textContent = '—';
        document.getElementById('delay-delta').textContent = '';
        document.getElementById('delay-meta').textContent = 'Distribución';
        const root = document.getElementById('delay-buckets');
        root.textContent = '';
        appendAll(root, el('p', { className: 'text-xs text-muted py-3 text-center', text: 'Sin movimientos para calcular.' }));
        return;
    }

    const buckets = [
        { label: 'Mismo día',  test: d => d === 0,             color: '#15803D', count: 0 },
        { label: '1 día',      test: d => d === 1,             color: '#84CC16', count: 0 },
        { label: '2–3 días',   test: d => d >= 2 && d <= 3,    color: '#EAB308', count: 0 },
        { label: '4–7 días',   test: d => d >= 4 && d <= 7,    color: '#D97706', count: 0 },
        { label: '8+ días',    test: d => d >= 8,              color: '#B91C1C', count: 0 },
    ];

    let sum = 0;
    eligible.forEach(tx => {
        const paid = parseLocalDate(tx.paid_ts);
        const created = parseLocalDate(tx.created_ts);
        const days = Math.round((created - paid) / 86400000);
        sum += days;
        for (const b of buckets) { if (b.test(days)) { b.count++; break; } }
    });
    const avg = sum / eligible.length;

    document.getElementById('delay-avg').textContent = avg.toLocaleString('es-AR', { maximumFractionDigits: 1 });
    document.getElementById('delay-delta').textContent = '';
    document.getElementById('delay-meta').textContent = `Distribución · ${eligible.length} movimiento${eligible.length === 1 ? '' : 's'}`;

    // Compare to previous period
    const cmpEligible = cache.cmpTxs.filter(tx => {
        if (!txPassesAllFilters(tx)) return false;
        if (tx.recurrent_id) return false;
        const paid = parseLocalDate(tx.paid_ts);
        const created = parseLocalDate(tx.created_ts);
        return paid && created && paid <= created;
    });
    if (cmpEligible.length > 0) {
        let cmpSum = 0;
        cmpEligible.forEach(tx => {
            const paid = parseLocalDate(tx.paid_ts);
            const created = parseLocalDate(tx.created_ts);
            cmpSum += Math.round((created - paid) / 86400000);
        });
        const cmpAvg = cmpSum / cmpEligible.length;
        const diff = avg - cmpAvg;
        const deltaEl = document.getElementById('delay-delta');
        deltaEl.textContent = (diff > 0 ? '+' : '') + diff.toFixed(1) + ' días vs antes';
        deltaEl.classList.remove('text-success', 'text-danger');
        deltaEl.classList.add(diff <= 0 ? 'text-success' : 'text-danger');
    }

    const max = Math.max(...buckets.map(b => b.count), 1);
    const root = document.getElementById('delay-buckets');
    root.textContent = '';
    buckets.forEach(b => {
        const row = el('div', { className: 'flex items-center gap-3' });
        appendAll(row, el('span', { className: 'text-[11px] text-muted w-20 flex-shrink-0', text: b.label }));
        const barWrap = el('div', { className: 'flex-1 h-5 rounded bg-[#F4EFE7] overflow-hidden' });
        const fill = el('div', { className: 'h-full', style: { width: (b.count / max * 100) + '%', background: b.color } });
        barWrap.appendChild(fill);
        row.appendChild(barWrap);
        appendAll(row, el('span', { className: 'font-semibold tabular-nums text-xs w-8 text-right flex-shrink-0', text: String(b.count) }));
        root.appendChild(row);
    });
}

// YoY toggle wrapper visibility — only on 12m period, since shorter spans
// don't have a matching same-window comparison anyway.
function syncYoyToggleVisibility() {
    const wrap = document.getElementById('yoy-toggle-wrap');
    if (STATE.period === '12m') wrap.classList.remove('hidden');
    else wrap.classList.add('hidden');

    document.querySelectorAll('#yoy-seg button').forEach(b => {
        const active = b.getAttribute('data-mode') === STATE.yoyMode;
        b.classList.toggle('is-active', active);
    });
}

// ── Filter bar interactions ───────────────────────────────────────────
function bindFilterInteractions() {
    document.getElementById('f-period').addEventListener('click', () => {
        const order = ['3m', '6m', '12m', 'ytd'];
        const idx = order.indexOf(STATE.period);
        STATE.period = order[(idx + 1) % order.length];
        document.getElementById('f-period-value').textContent = periodLabel(STATE.period);
        syncYoyToggleVisibility();
        loadAll();
    });

    // YoY toggle — switching modes either re-renders (cheap) or triggers a
    // fetch when going off→on (we may not have fxHistory yet).
    document.querySelectorAll('#yoy-seg button').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.getAttribute('data-mode');
            if (mode === STATE.yoyMode) return;
            const wasOff = STATE.yoyMode === 'off';
            STATE.yoyMode = mode;
            try { localStorage.setItem('mangos.yoyMode', mode); } catch (e) {}
            document.querySelectorAll('#yoy-seg button').forEach(b => {
                b.classList.toggle('is-active', b === btn);
            });
            // If we're entering YoY for the first time this session, fxHistory
            // isn't loaded yet — fetch it. Otherwise just re-render.
            if (wasOff && mode !== 'off') loadAll();
            else renderAll();
        });
    });

    document.getElementById('f-currency').addEventListener('click', () => {
        STATE.currency = STATE.currency === 'mixed' ? 'ARS' : (STATE.currency === 'ARS' ? 'USD' : 'mixed');
        const txt = document.getElementById('f-currency-text');
        const tag = document.getElementById('f-currency-tag');
        if (STATE.currency === 'mixed') {
            txt.textContent = 'ARS + USD';
            tag.classList.remove('hidden');
        } else if (STATE.currency === 'ARS') {
            txt.textContent = 'Solo ARS';
            tag.classList.add('hidden');
        } else {
            txt.textContent = 'Solo USD';
            tag.classList.add('hidden');
        }
        renderAll();
    });

    document.getElementById('f-account').addEventListener('click', () => {
        const ids = ['all', ...cache.accounts.map(a => a.id)];
        const idx = ids.indexOf(STATE.accountId);
        STATE.accountId = ids[(idx + 1) % ids.length];
        const v = document.getElementById('f-account-value');
        if (STATE.accountId === 'all') {
            v.textContent = `Todas`;
        } else {
            const acc = cache.accounts.find(a => a.id === STATE.accountId);
            v.textContent = (acc && acc.name) || '?';
        }
        renderAll();
    });

    // Categories toggle
    document.querySelectorAll('#cat-toggle button').forEach(b => {
        b.addEventListener('click', () => {
            const k = b.getAttribute('data-kind');
            if (k === STATE.catKind) return;
            STATE.catKind = k;
            document.querySelectorAll('#cat-toggle button').forEach(x => {
                const active = x === b;
                x.classList.toggle('is-active', active);
                if (active) {
                    x.style.background = '#292524';
                    x.style.color = '#FFFBF5';
                    x.classList.remove('text-muted');
                } else {
                    x.style.background = '';
                    x.style.color = '';
                    x.classList.add('text-muted');
                }
            });
            renderCategories(cache.txs, cache.cmpTxs);
        });
    });
    // Set initial active state styling on first render
    document.querySelectorAll('#cat-toggle button').forEach(x => {
        if (x.classList.contains('is-active')) {
            x.style.background = '#292524';
            x.style.color = '#FFFBF5';
        }
    });
}

// ── Re-render without re-fetching ─────────────────────────────────────
function renderAll() {
    renderTrend(cache.txs);
    renderCategories(cache.txs, cache.cmpTxs);
    renderAccountRows(cache.txs.filter(txPassesCurrencyFilter));
    renderTransferMatrix(cache.txs);
    renderDelay(cache.txs);
}

// ── Main loader ───────────────────────────────────────────────────────
async function loadAll() {
    await fx.ready;

    const usdRate = fx.rateFor('USD');
    document.getElementById('f-fx-rate').textContent = usdRate
        ? usdRate.toLocaleString('es-AR', { maximumFractionDigits: 0 })
        : '—';

    const range = periodRange();
    const cmp = comparisonRange();

    // YoY history fetch only fires when the chart is on 12m AND the toggle
    // isn't off — otherwise we save the round-trip.
    const yoyActive = STATE.period === '12m' && STATE.yoyMode !== 'off';
    const yoy = yoyActive ? yoyRange() : null;
    const fxHistoryPromise = yoyActive
        ? api.get('/fx-history', { currency: 'USD', start: isoDay(yoy.start), end: isoDay(yoy.end) })
        : Promise.resolve(null);

    const [txs, cmpTxs, expCats, incCats, accountsResp, fxHist] = await Promise.all([
        api.get('/transactions', { start_date: isoDay(range.start), end_date: isoDay(range.end) }),
        api.get('/transactions', { start_date: isoDay(cmp.start),   end_date: isoDay(cmp.end) }),
        api.get('/categories'),
        api.get('/categories', { kind: 'income' }),
        api.get('/accounts', { totals: 1 }),
        fxHistoryPromise,
    ]);

    cache.txs         = txs || [];
    cache.cmpTxs      = cmpTxs || [];
    cache.expenseCats = expCats || [];
    cache.incomeCats  = incCats || [];
    cache.accounts    = (accountsResp && accountsResp.accounts) ? accountsResp.accounts : [];

    // YoY data: cmpTxs already covers the previous 12m for period=12m, so we
    // reuse it instead of double-fetching. fxHistory feeds the per-tx
    // USD-anchor conversion in txToTodayArs().
    cache.yoyTxs = (STATE.period === '12m') ? cache.cmpTxs : [];
    if (fxHist && fxHist.rates) {
        cache.fxHistory = fxHist.rates;
        cache.fxHistorySorted = Object.keys(cache.fxHistory).sort();
    } else {
        cache.fxHistory = {};
        cache.fxHistorySorted = [];
    }

    renderAll();
}

mangosAuth.ready.then(user => {
    if (!user) return;
    bindFilterInteractions();
    syncYoyToggleVisibility();
    loadAll();
});
</script>
