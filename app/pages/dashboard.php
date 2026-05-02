<!-- ────────────────────────────────────────────────────────────────────
     Dashboard — at-a-glance for the current period.

     Currency-aware: defaults to "ARS + USD convertido" (mixed mode) where
     non-ARS amounts are converted via fx.toArs(). The .chip-mixed flag
     appears anywhere a total includes a converted USD leg.

     Mobile-first: filter bar collapses to 2×2 grid; account cards are
     horizontal-scroll-snap; transfer flows render as a top-list (no SVG
     sankey on mobile — the list reads better on small screens).
──────────────────────────────────────────────────────────────────── -->

<!-- Page header (desktop only — mobile topbar shows the page title) -->
<div class="hidden lg:flex items-end justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold" id="period-title">Cargando…</h1>
        <p class="text-sm text-muted mt-1" id="period-sub">&nbsp;</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="/movimientos" class="btn btn-outline">Movimientos</a>
        <a href="/capturar" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
            </svg>
            Capturar
        </a>
    </div>
</div>

<!-- Filter rail -->
<div class="filter-bar mb-5" role="group" aria-label="Filtros del resumen">
    <button class="filter-cell" type="button" id="f-period" aria-haspopup="menu">
        <span class="filter-label">Período</span>
        <span class="filter-value">
            <span class="v" id="f-period-value">Mes actual</span>
            <svg class="chev" width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4"/></svg>
        </span>
    </button>

    <button class="filter-cell" type="button" id="f-currency" aria-haspopup="menu">
        <span class="filter-label">Moneda</span>
        <span class="filter-value">
            <span class="v" id="f-currency-value">
                <span id="f-currency-text">ARS + USD</span>
                <span class="pill-tag" id="f-currency-tag">Convert</span>
            </span>
            <svg class="chev" width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4"/></svg>
        </span>
    </button>

    <div class="filter-cell" id="f-compare">
        <span class="filter-label">Comparar con</span>
        <span class="filter-value">
            <span class="v">Mes anterior</span>
            <svg class="chev" width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4"/></svg>
        </span>
    </div>

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

<!-- ───────── Hero — Net cashflow ───────── -->
<div class="card relative overflow-hidden mb-6 p-0">
    <div class="absolute inset-0 dotgrid opacity-50 pointer-events-none"></div>
    <div class="relative grid grid-cols-1 lg:grid-cols-[1.6fr_1fr]">
        <!-- LEFT: net hero -->
        <div class="p-5 lg:p-7">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <span class="mono-label">Flujo neto</span>
                <span class="chip-mixed hidden" id="hero-mixed-chip">Incluye USD convertido</span>
            </div>

            <div class="mt-3 flex items-baseline gap-3 flex-wrap">
                <span class="font-bold tracking-tight tabular-nums leading-none text-4xl sm:text-5xl lg:text-[64px]" id="hero-net">
                    <span class="skeleton inline-block w-44 h-9 lg:h-12">&nbsp;</span>
                </span>
                <span class="text-muted text-sm lg:text-base tabular-nums" id="hero-net-currency">ARS</span>
            </div>

            <div class="mt-2 flex items-center gap-3 flex-wrap text-xs">
                <span id="hero-trend" class="font-semibold tabular-nums hidden"></span>
                <span class="text-muted" id="hero-trend-sub">&nbsp;</span>
            </div>

            <!-- Split bar: income / expense / transfer -->
            <div class="mt-6">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="text-muted">Distribución del período</span>
                </div>
                <div class="splitbar" id="hero-splitbar">
                    <span class="income"   style="width:0"></span>
                    <span class="expense"  style="width:0"></span>
                    <span class="transfer" style="width:0"></span>
                </div>
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs">
                    <span class="flex items-center gap-1.5"><span class="kind-dot income"></span><span class="text-muted">Ingresos</span> <span class="font-semibold tabular-nums" id="legend-income">—</span></span>
                    <span class="flex items-center gap-1.5"><span class="kind-dot expense"></span><span class="text-muted">Gastos</span> <span class="font-semibold tabular-nums" id="legend-expense">—</span></span>
                    <span class="flex items-center gap-1.5"><span class="kind-dot transfer"></span><span class="text-muted">Transferencias</span> <span class="font-semibold tabular-nums" id="legend-transfer">—</span></span>
                </div>
            </div>
        </div>

        <!-- RIGHT: side stats (desktop) / quick stats grid (mobile bottom) -->
        <div class="border-t lg:border-t-0 lg:border-l border-border p-5 lg:p-7" style="background:rgba(217,119,6,.025)">
            <div class="mono-label mb-3">Composición por moneda</div>
            <div class="space-y-4" id="hero-currency-breakdown">
                <p class="text-xs text-muted">—</p>
            </div>

            <div class="h-px bg-border my-5"></div>

            <div class="mono-label mb-3">Indicadores</div>
            <div class="grid grid-cols-2 gap-y-3 gap-x-3 text-xs">
                <div>
                    <div class="text-muted">Tasa de ahorro</div>
                    <div class="mt-0.5 font-semibold tabular-nums text-base lg:text-lg" id="kpi-savings">—</div>
                </div>
                <div>
                    <div class="text-muted">Movimientos</div>
                    <div class="mt-0.5 font-semibold tabular-nums text-base lg:text-lg" id="kpi-tx-count">—</div>
                </div>
                <div>
                    <div class="text-muted">Día con más gasto</div>
                    <div class="mt-0.5 text-sm font-medium" id="kpi-peak-day">—</div>
                </div>
                <div>
                    <div class="text-muted">Recurrentes activos</div>
                    <div class="mt-0.5 font-semibold tabular-nums text-base lg:text-lg" id="kpi-recurrents">—</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ───────── Per-account cards ───────── -->
<section class="mb-6">
    <div class="flex items-end justify-between mb-3 lg:mb-4">
        <h2 class="text-lg lg:text-xl font-semibold">Cuentas</h2>
        <a href="/cuentas" class="text-xs lg:text-sm font-medium text-accent hover:underline">Ver todas →</a>
    </div>
    <div class="carousel-wrap">
        <button type="button" class="carousel-nav prev" id="account-prev" aria-label="Anterior">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <div class="h-scroll" id="account-cards">
            <div class="card p-5"><span class="skeleton block w-full h-24"></span></div>
            <div class="card p-5"><span class="skeleton block w-full h-24"></span></div>
            <div class="card p-5"><span class="skeleton block w-full h-24"></span></div>
        </div>
        <button type="button" class="carousel-nav next" id="account-next" aria-label="Siguiente">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
</section>

<!-- ───────── Recurrents + Recent transactions ───────── -->
<section class="grid grid-cols-1 lg:grid-cols-[1.05fr_1fr] gap-4 lg:gap-5 mb-6">

    <!-- Recurrents -->
    <div class="card p-5 lg:p-6">
        <div class="flex items-end justify-between mb-1">
            <h2 class="text-base lg:text-xl font-semibold">Recurrentes</h2>
            <span class="text-xs text-muted">Pendiente <span class="font-semibold tabular-nums text-dark" id="recurrent-pending-total">—</span></span>
        </div>

        <div class="h-px bg-border mt-4 mb-1"></div>

        <!-- Vencidos -->
        <div id="overdue-section" class="hidden">
            <div class="flex items-center justify-between pt-3">
                <span class="overline-muted">Vencidos</span>
                <span class="badge badge-danger" id="overdue-count">0</span>
            </div>
            <div id="overdue-list" class="divide-y divide-border"></div>
        </div>

        <!-- Próximos -->
        <div id="upcoming-section">
            <div class="flex items-center justify-between pt-4">
                <span class="overline-muted">Próximos · 7 días</span>
                <span class="badge badge-muted" id="upcoming-count">0</span>
            </div>
            <div id="upcoming-list" class="divide-y divide-border">
                <div class="py-3"><span class="skeleton inline-block w-32 h-4">&nbsp;</span></div>
                <div class="py-3"><span class="skeleton inline-block w-40 h-4">&nbsp;</span></div>
            </div>
        </div>
    </div>

    <!-- Recent transactions -->
    <div class="card p-5 lg:p-6">
        <div class="flex items-end justify-between">
            <h2 class="text-base lg:text-xl font-semibold">Movimientos</h2>
            <a href="/movimientos" class="text-xs lg:text-sm font-medium text-accent hover:underline">Ver todos →</a>
        </div>

        <div class="h-px bg-border mt-4"></div>

        <div id="recent-list" class="divide-y divide-border">
            <div class="py-3"><span class="skeleton inline-block w-32 h-4">&nbsp;</span></div>
            <div class="py-3"><span class="skeleton inline-block w-40 h-4">&nbsp;</span></div>
            <div class="py-3"><span class="skeleton inline-block w-36 h-4">&nbsp;</span></div>
        </div>
    </div>
</section>

<!-- ───────── Transfer activity ───────── -->
<section class="mb-10">
    <div class="card p-5 lg:p-7 relative overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_1.4fr] gap-6 lg:gap-7 items-start">
            <div>
                <h2 class="text-base lg:text-xl font-semibold">Transferencias</h2>
                <p class="text-xs text-muted mt-2 max-w-md">Las transferencias internas <strong class="text-dark font-semibold">no afectan</strong> ingresos ni gastos del período.</p>

                <div class="mt-5 grid grid-cols-2 gap-x-5 gap-y-4">
                    <div>
                        <div class="mono-label">Volumen</div>
                        <div class="font-bold tabular-nums text-xl lg:text-2xl mt-1" id="tr-volume">—</div>
                    </div>
                    <div>
                        <div class="mono-label">Operaciones</div>
                        <div class="font-bold tabular-nums text-xl lg:text-2xl mt-1" id="tr-count">—</div>
                    </div>
                    <div>
                        <div class="mono-label">Comisiones</div>
                        <div class="font-semibold tabular-nums text-sm mt-1" id="tr-fees">—</div>
                    </div>
                    <div>
                        <div class="mono-label">Más activa</div>
                        <div class="text-sm font-medium mt-1 truncate" id="tr-top">—</div>
                    </div>
                </div>
            </div>

            <!-- Top flows list (works on mobile and desktop) -->
            <div>
                <div class="mono-label mb-3">Top flujos</div>
                <ol id="tr-flows" class="space-y-2">
                    <li class="text-xs text-muted py-3 text-center">Sin transferencias este período.</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<script>
const MONTH_NAMES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Filter state — defaults: current month, mixed currency
const STATE = {
    period: 'current',     // 'current' | 'previous'
    currency: 'mixed',     // 'mixed' | 'ARS' | 'USD'
};

function fmtArs(n) { return formatPrice(n, 'ARS'); }
function fmtUsd(n) { return formatPrice(n, 'USD'); }
function isoDay(d) { return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; }
function parseLocalDate(s) {
    if (!s) return null;
    const m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? new Date(+m[1], +m[2]-1, +m[3]) : null;
}
function lastDayOfMonth(y, m) { return new Date(y, m+1, 0).getDate(); }

// Tiny DOM helper — avoids innerHTML, keeps construction concise
function el(tag, opts) {
    const e = document.createElement(tag);
    if (!opts) return e;
    if (opts.className) e.className = opts.className;
    if (opts.text != null) e.textContent = opts.text;
    if (opts.style) {
        for (const k of Object.keys(opts.style)) e.style[k] = opts.style[k];
    }
    if (opts.attrs) {
        for (const k of Object.keys(opts.attrs)) e.setAttribute(k, opts.attrs[k]);
    }
    return e;
}
function appendAll(parent, ...children) {
    children.forEach(c => { if (c) parent.appendChild(c); });
    return parent;
}

// ── Period range from STATE.period ────────────────────────────────────
function periodRange() {
    const now = new Date();
    const y = now.getFullYear(), m = now.getMonth();
    if (STATE.period === 'previous') {
        const start = new Date(y, m-1, 1);
        const end   = new Date(y, m, 0);
        return { start, end, label: `${MONTH_NAMES[start.getMonth()]} ${start.getFullYear()}` };
    }
    return {
        start: new Date(y, m, 1),
        end:   new Date(y, m+1, 0),
        label: `${MONTH_NAMES[m]} ${y}`,
    };
}
function comparisonRange() {
    const cur = periodRange();
    const start = new Date(cur.start.getFullYear(), cur.start.getMonth()-1, 1);
    const end   = new Date(cur.start.getFullYear(), cur.start.getMonth(), 0);
    return { start, end, label: `${MONTH_NAMES[start.getMonth()]} ${start.getFullYear()}` };
}

// ── Currency helpers ──────────────────────────────────────────────────
function amountInArs(tx) {
    return Number(tx.amount || 0) * fx.rateFor(tx.currency || 'ARS');
}
function txPassesCurrencyFilter(tx) {
    const c = tx.currency || 'ARS';
    if (STATE.currency === 'mixed') return true;
    return c === STATE.currency;
}

// ── Hero ──────────────────────────────────────────────────────────────
function renderHero(currentTxs, previousTxs) {
    let income = 0, expense = 0, transfer = 0;
    let incomeArs = 0, expenseArs = 0;
    let incomeUsd = 0, expenseUsd = 0;
    let hasNonArs = false;
    let txCount = 0;
    const dailyExpense = {};

    currentTxs.forEach(tx => {
        if (!txPassesCurrencyFilter(tx)) return;
        const ars = amountInArs(tx);
        const cur = tx.currency || 'ARS';
        if (cur !== 'ARS') hasNonArs = true;
        txCount++;

        if (tx.kind === 'income') {
            income += Math.abs(ars);
            if (cur === 'ARS') incomeArs += Math.abs(Number(tx.amount));
            else if (cur === 'USD') incomeUsd += Math.abs(Number(tx.amount));
        } else if (tx.kind === 'expense' || tx.kind === 'fee') {
            const abs = Math.abs(Number(tx.amount));
            expense += Math.abs(ars);
            if (cur === 'ARS') expenseArs += abs;
            else if (cur === 'USD') expenseUsd += abs;
            const d = parseLocalDate(tx.due_ts || tx.paid_ts);
            if (d) {
                const k = isoDay(d);
                dailyExpense[k] = (dailyExpense[k] || 0) + Math.abs(ars);
            }
        } else if (tx.kind === 'transfer') {
            // Outgoing legs only (avoid double-counting)
            if (Number(tx.amount) < 0) transfer += Math.abs(ars);
        }
    });

    const net = income - expense;
    const total = income + expense + transfer;

    const heroEl = document.getElementById('hero-net');
    const heroCurEl = document.getElementById('hero-net-currency');
    if (STATE.currency === 'USD') {
        const usdNet = (income - expense) / fx.rateFor('USD');
        heroEl.textContent = (usdNet >= 0 ? '+' : '−') + formatPrice(Math.abs(usdNet), 'USD');
        heroCurEl.textContent = '';
    } else {
        heroEl.textContent = (net >= 0 ? '+' : '−') + fmtArs(Math.abs(net));
        heroCurEl.textContent = 'ARS';
    }
    heroEl.classList.toggle('text-success', net > 0);
    heroEl.classList.toggle('text-danger',  net < 0);

    document.getElementById('hero-mixed-chip').classList.toggle('hidden', !(STATE.currency === 'mixed' && hasNonArs));

    // Trend vs comparison period
    let prevNet = 0;
    previousTxs.forEach(tx => {
        if (!txPassesCurrencyFilter(tx)) return;
        const ars = amountInArs(tx);
        if (tx.kind === 'income') prevNet += Math.abs(ars);
        else if (tx.kind === 'expense' || tx.kind === 'fee') prevNet -= Math.abs(ars);
    });

    const trendEl = document.getElementById('hero-trend');
    const trendSub = document.getElementById('hero-trend-sub');
    const cmp = comparisonRange();
    trendEl.classList.remove('text-success', 'text-danger');
    if (Math.abs(prevNet) > 1) {
        const pct = ((net - prevNet) / Math.abs(prevNet)) * 100;
        const better = (net - prevNet) > 0;
        trendEl.textContent = (pct > 0 ? '+' : '') + pct.toFixed(1) + '% vs ' + cmp.label;
        trendEl.classList.remove('hidden');
        trendEl.classList.add(better ? 'text-success' : 'text-danger');
        trendSub.textContent = `Neto comparable: ${fmtArs(prevNet)}`;
    } else {
        trendEl.classList.add('hidden');
        trendSub.textContent = `Sin historial comparable en ${cmp.label}`;
    }

    if (total > 0) {
        document.querySelector('#hero-splitbar .income').style.width   = (income/total*100) + '%';
        document.querySelector('#hero-splitbar .expense').style.width  = (expense/total*100) + '%';
        document.querySelector('#hero-splitbar .transfer').style.width = (transfer/total*100) + '%';
    } else {
        document.querySelector('#hero-splitbar .income').style.width = '0';
        document.querySelector('#hero-splitbar .expense').style.width = '0';
        document.querySelector('#hero-splitbar .transfer').style.width = '0';
    }
    document.getElementById('legend-income').textContent   = '+' + fmtArs(income);
    document.getElementById('legend-expense').textContent  = '−' + fmtArs(expense);
    document.getElementById('legend-transfer').textContent = fmtArs(transfer);

    renderCurrencyBreakdown(incomeArs, expenseArs, incomeUsd, expenseUsd, income, expense);

    const savings = income > 0 ? Math.max(0, (net / income) * 100) : 0;
    document.getElementById('kpi-savings').textContent = (income > 0) ? savings.toFixed(1) + '%' : '—';
    document.getElementById('kpi-tx-count').textContent = String(txCount);

    let peakDay = null, peakVal = 0;
    Object.entries(dailyExpense).forEach(([d, v]) => { if (v > peakVal) { peakVal = v; peakDay = d; } });
    const peakEl = document.getElementById('kpi-peak-day');
    peakEl.textContent = '';
    if (peakDay) {
        const d = parseLocalDate(peakDay);
        const dayStr = d.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
        peakEl.appendChild(document.createTextNode(dayStr + ' '));
        appendAll(peakEl, el('span', { className: 'text-muted tabular-nums', text: '· ' + fmtArs(peakVal) }));
    } else {
        peakEl.textContent = 'Sin gastos';
    }
}

function renderCurrencyBreakdown(incomeArs, expenseArs, incomeUsd, expenseUsd) {
    const cb = document.getElementById('hero-currency-breakdown');
    cb.textContent = '';

    const arsActivity = incomeArs + expenseArs;
    const usdActivity = incomeUsd + expenseUsd;
    if (arsActivity === 0 && usdActivity === 0) {
        appendAll(cb, el('p', { className: 'text-xs text-muted', text: 'Sin movimientos en el período.' }));
        return;
    }

    const usdInArs = usdActivity * fx.rateFor('USD');
    const total = arsActivity + usdInArs;
    const arsPct = total > 0 ? Math.round((arsActivity / total) * 100) : 0;
    const usdPct = 100 - arsPct;

    if (arsActivity > 0) {
        const arsNet = incomeArs - expenseArs;
        cb.appendChild(buildCurrencyRow({
            code: 'ARS', codeMeta: 'nativo',
            value: (arsNet >= 0 ? '+' : '−') + fmtArs(Math.abs(arsNet)),
            barColor: '#292524', barPct: Math.max(2, arsPct),
        }));
    }
    if (usdActivity > 0) {
        const usdNet = incomeUsd - expenseUsd;
        const arsConv = usdNet * fx.rateFor('USD');
        const rate = fx.rateFor('USD');
        cb.appendChild(buildCurrencyRow({
            code: 'USD', codeMeta: '@ ' + rate.toLocaleString('es-AR', { maximumFractionDigits: 0 }) + ' ARS',
            value: (usdNet >= 0 ? '+' : '−') + fmtUsd(Math.abs(usdNet)),
            valueMeta: '≈ ' + fmtArs(Math.abs(arsConv)),
            barColor: '#D97706', barPct: Math.max(2, usdPct),
        }));
    }
}

function buildCurrencyRow(cfg) {
    const wrap = el('div');
    const head = el('div', { className: 'flex items-center justify-between' });
    const left = el('div', { className: 'flex items-center gap-2' });
    appendAll(left,
        el('span', { className: 'text-[11px] font-semibold tracking-wider text-dark', text: cfg.code }),
        el('span', { className: 'text-muted text-[11px]', text: cfg.codeMeta }),
    );
    const valWrap = el('span', { className: 'font-semibold tabular-nums text-sm flex items-baseline gap-1.5' });
    appendAll(valWrap, el('span', { text: cfg.value }));
    if (cfg.valueMeta) appendAll(valWrap, el('span', { className: 'text-muted text-[11px] font-normal', text: cfg.valueMeta }));
    appendAll(head, left, valWrap);

    const bar = el('div', { className: 'mt-1.5 h-[3px] bg-border rounded-full overflow-hidden' });
    const fill = el('div', { className: 'h-full', style: { background: cfg.barColor, width: cfg.barPct + '%' } });
    appendAll(bar, fill);
    appendAll(wrap, head, bar);
    return wrap;
}

// ── Account cards ─────────────────────────────────────────────────────
function renderAccounts(accounts, currentTxs) {
    const root = document.getElementById('account-cards');
    root.textContent = '';
    if (!accounts || accounts.length === 0) {
        const empty = el('div', { className: 'card p-5 text-sm text-muted' });
        empty.appendChild(document.createTextNode('Sin cuentas. '));
        appendAll(empty, el('a', { className: 'text-accent hover:underline', text: 'Crear una', attrs: { href: '/cuentas' } }));
        appendAll(empty, document.createTextNode('.'));
        appendAll(root, empty);
        return;
    }

    const perAcc = {};
    currentTxs.forEach(tx => {
        if (!tx.account_id) return;
        const a = perAcc[tx.account_id] = perAcc[tx.account_id] || { income: 0, expense: 0, deltaArs: 0 };
        const amt = Number(tx.amount || 0);
        const ars = amountInArs(tx);
        if (tx.kind === 'income') a.income += Math.abs(amt);
        else if (tx.kind === 'expense' || tx.kind === 'fee') a.expense += Math.abs(amt);
        a.deltaArs += ars;
    });

    accounts.forEach(acc => {
        root.appendChild(buildAccountCard(acc, perAcc[acc.id] || { income: 0, expense: 0, deltaArs: 0 }));
    });
}

function buildAccountCard(acc, stats) {
    const isUsd = (acc.currency || 'ARS') !== 'ARS';
    const card = el('article', { className: 'card p-4 lg:p-5' });
    if (isUsd) {
        card.style.borderColor = 'rgba(217,119,6,.30)';
        card.style.background  = 'rgba(217,119,6,.04)';
    }

    // Header row
    const head = el('div', { className: 'flex items-center justify-between gap-2' });
    const left = el('div', { className: 'flex items-center gap-2.5 min-w-0' });

    const swatch = el('span', {
        className: 'w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center text-[10px] font-semibold tracking-wider text-light',
    });
    swatch.style.background = acc.color || '#1f2937';
    const initials = (acc.name || '?').split(/\s+/).slice(0,2).map(s => s[0] || '').join('').toUpperCase().slice(0,3) || '?';
    swatch.textContent = initials;

    const txt = el('div', { className: 'min-w-0' });
    appendAll(txt,
        el('div', { className: 'text-sm font-semibold leading-tight truncate', text: acc.name || 'Cuenta' }),
        el('div', { className: 'text-[11px] text-muted truncate', text: (acc.type || 'cuenta') + ' · ' + (acc.currency || 'ARS') }),
    );
    appendAll(left, swatch, txt);
    head.appendChild(left);

    if (acc.is_default == 1) head.appendChild(el('span', { className: 'badge badge-muted', text: 'Default' }));
    if (isUsd) head.appendChild(el('span', { className: 'chip-mixed', text: 'USD' }));
    card.appendChild(head);

    // Balance block
    const bal = el('div', { className: 'mt-4' });
    appendAll(bal,
        el('div', { className: 'text-[11px] text-muted', text: 'Saldo' }),
        el('div', { className: 'font-bold tabular-nums text-xl lg:text-2xl mt-0.5', text: formatPrice(Number(acc.current_balance || 0), acc.currency) }),
    );
    if (isUsd) {
        bal.appendChild(el('div', {
            className: 'text-[11px] text-muted tabular-nums',
            text: '≈ ' + fmtArs(Number(acc.current_balance_ars || 0)),
        }));
    }
    if (stats.deltaArs !== 0) {
        const delta = el('div', { className: 'text-xs font-semibold tabular-nums mt-1' });
        delta.classList.add(stats.deltaArs > 0 ? 'text-success' : 'text-danger');
        delta.textContent = (stats.deltaArs > 0 ? '+ ' : '− ') + fmtArs(Math.abs(stats.deltaArs)) + ' este período';
        bal.appendChild(delta);
    } else {
        bal.appendChild(el('div', { className: 'text-xs text-muted mt-1', text: 'Sin movimientos' }));
    }
    card.appendChild(bal);

    // Income/expense row
    const row = el('div', { className: 'mt-3 grid grid-cols-2 gap-3 text-xs' });
    const inWrap = el('div');
    appendAll(inWrap,
        el('div', { className: 'text-muted', text: 'Ingresos' }),
        el('div', {
            className: 'font-semibold tabular-nums',
            text: stats.income > 0 ? ('+' + formatPrice(stats.income, acc.currency)) : '—',
        }),
    );
    const exWrap = el('div');
    appendAll(exWrap,
        el('div', { className: 'text-muted', text: 'Gastos' }),
        el('div', {
            className: 'font-semibold tabular-nums',
            text: stats.expense > 0 ? ('−' + formatPrice(stats.expense, acc.currency)) : '—',
        }),
    );
    appendAll(row, inWrap, exWrap);
    card.appendChild(row);

    return card;
}

// ── Recurrents ────────────────────────────────────────────────────────
function renderRecurrents(recurrents, monthTxs) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const sevenDays = new Date(now.getFullYear(), now.getMonth(), now.getDate()+7);
    const month = now.getMonth();
    const isThisMonth = STATE.period === 'current';

    const paidRecurrentIds = new Set(
        monthTxs.filter(p => p.is_paid == 1 && p.recurrent_id).map(p => p.recurrent_id)
    );

    const overdue = [];
    const upcoming = [];
    let pendingArs = 0;

    if (isThisMonth) {
        recurrents.forEach(r => {
            if (paidRecurrentIds.has(r.id)) return;
            const day = r.due_date_day;
            const arsAmt = Math.abs(Number(r.amount)) * fx.rateFor(r.currency || 'ARS');
            pendingArs += arsAmt;
            if (day < now.getDate()) {
                overdue.push({ kind: 'recurrent', item: r, dueDay: day });
            } else {
                if (sevenDays.getMonth() === month) {
                    if (day <= sevenDays.getDate()) upcoming.push({ kind: 'recurrent', item: r, dueDay: day });
                } else {
                    upcoming.push({ kind: 'recurrent', item: r, dueDay: day });
                }
            }
        });

        monthTxs.forEach(p => {
            if (p.is_paid == 1) return;
            if (p.transaction_type === 'recurrent') return;
            if (p.kind !== 'expense' && p.kind !== 'fee') return;
            const d = parseLocalDate(p.due_ts);
            if (!d) return;
            const arsAmt = Math.abs(Number(p.amount)) * fx.rateFor(p.currency || 'ARS');
            pendingArs += arsAmt;
            if (d < today) overdue.push({ kind: 'payment', item: p });
            else if (d <= sevenDays) upcoming.push({ kind: 'payment', item: p, dueDay: d.getDate() });
        });
    }

    document.getElementById('recurrent-pending-total').textContent = pendingArs > 0 ? fmtArs(pendingArs) : '—';

    const overdueSection = document.getElementById('overdue-section');
    const overdueList = document.getElementById('overdue-list');
    overdueList.textContent = '';
    if (overdue.length === 0) {
        overdueSection.classList.add('hidden');
    } else {
        overdueSection.classList.remove('hidden');
        document.getElementById('overdue-count').textContent = String(overdue.length);
        overdue.forEach(({ kind, item, dueDay }) => {
            const sub = kind === 'recurrent' ? `Vencía el ${dueDay}` : `Vencía ${formatDate(item.due_ts)}`;
            overdueList.appendChild(buildRecurrentRow({ item, sub, kind, monthTxs }));
        });
    }

    const upcomingList = document.getElementById('upcoming-list');
    upcomingList.textContent = '';
    document.getElementById('upcoming-count').textContent = String(upcoming.length);
    if (upcoming.length === 0) {
        upcomingList.appendChild(el('p', { className: 'text-xs text-muted py-4 text-center', text: 'Nada vence en los próximos 7 días.' }));
    } else {
        upcoming.sort((a,b) => (a.dueDay || 99) - (b.dueDay || 99));
        upcoming.forEach(({ kind, item, dueDay }) => {
            const isToday = dueDay === now.getDate();
            const sub = kind === 'recurrent'
                ? (isToday ? 'Vence hoy' : `Vence el ${dueDay}`)
                : (isToday ? 'Vence hoy' : `Vence ${formatDate(item.due_ts)}`);
            upcomingList.appendChild(buildRecurrentRow({ item, sub, kind, monthTxs }));
        });
    }
}

function buildRecurrentRow({ item, sub, kind, monthTxs }) {
    const row = el('div', { className: 'flex items-center gap-3 py-3' });
    appendAll(row, el('span', { className: 'kind-dot expense' }));

    const main = el('div', { className: 'flex-1 min-w-0' });
    appendAll(main,
        el('div', { className: 'text-sm font-medium truncate', text: item.title || 'Recurrente' }),
        el('div', { className: 'text-[11px] text-muted mt-0.5', text: sub }),
    );
    row.appendChild(main);

    const right = el('div', { className: 'text-right flex-shrink-0' });
    appendAll(right, el('div', {
        className: 'font-semibold tabular-nums text-sm',
        text: formatPrice(Math.abs(Number(item.amount)), item.currency),
    }));

    const btn = el('button', { className: 'text-[11px] font-semibold text-accent hover:underline mt-0.5', text: 'Marcar pagado' });
    btn.type = 'button';
    btn.style.touchAction = 'manipulation';
    btn.addEventListener('click', () => kind === 'recurrent'
        ? markRecurrentPaid(item, monthTxs, btn)
        : markPaymentPaid(item, btn));
    right.appendChild(btn);
    row.appendChild(right);
    return row;
}

// ── Recent transactions ───────────────────────────────────────────────
function renderRecent(currentTxs) {
    const root = document.getElementById('recent-list');
    root.textContent = '';

    const top = currentTxs
        .filter(txPassesCurrencyFilter)
        .slice()
        .sort((a, b) => {
            const da = a.created_ts || a.due_ts || '';
            const db = b.created_ts || b.due_ts || '';
            return db.localeCompare(da);
        })
        .slice(0, 6);

    if (top.length === 0) {
        appendAll(root, el('p', { className: 'text-xs text-muted py-4 text-center', text: 'Sin movimientos en el período.' }));
        return;
    }

    top.forEach(tx => root.appendChild(buildRecentRow(tx)));
}

function buildRecentRow(tx) {
    const row = el('div', { className: 'flex items-center gap-3 py-3' });
    const dotKind = tx.kind === 'income' ? 'income' : (tx.kind === 'transfer' ? 'transfer' : 'expense');
    appendAll(row, el('span', { className: 'kind-dot ' + dotKind }));

    const main = el('div', { className: 'flex-1 min-w-0' });
    main.appendChild(el('div', {
        className: 'text-sm font-medium truncate',
        text: tx.title || (tx.kind === 'transfer' ? 'Transferencia' : 'Movimiento'),
    }));
    const sub = el('div', { className: 'text-[11px] text-muted mt-0.5' });
    const date = parseLocalDate(tx.due_ts) || parseLocalDate(tx.paid_ts);
    sub.textContent = date ? formatDate(tx.due_ts || tx.paid_ts) : '';
    if ((tx.currency || 'ARS') !== 'ARS') {
        sub.appendChild(el('span', { className: 'text-accent font-semibold', text: ' · ' + tx.currency }));
    }
    main.appendChild(sub);
    row.appendChild(main);

    const right = el('div', { className: 'text-right flex-shrink-0' });
    const native = Number(tx.amount || 0);
    const amtEl = el('div', { className: 'font-semibold tabular-nums text-sm' });
    if (tx.kind === 'transfer') {
        amtEl.classList.add('text-muted');
        amtEl.textContent = formatPrice(Math.abs(native), tx.currency);
    } else if (tx.kind === 'income') {
        amtEl.classList.add('text-success');
        amtEl.textContent = '+' + formatPrice(Math.abs(native), tx.currency);
    } else {
        amtEl.textContent = '−' + formatPrice(Math.abs(native), tx.currency);
    }
    right.appendChild(amtEl);
    if ((tx.currency || 'ARS') !== 'ARS') {
        right.appendChild(el('div', {
            className: 'text-[11px] text-muted tabular-nums',
            text: '≈ ' + fmtArs(Math.abs(amountInArs(tx))),
        }));
    }
    row.appendChild(right);
    return row;
}

// ── Transfers ─────────────────────────────────────────────────────────
function renderTransfers(currentTxs, accounts) {
    const accById = Object.fromEntries(accounts.map(a => [a.id, a]));

    const groups = {};
    currentTxs.forEach(tx => {
        if (tx.kind !== 'transfer' || !tx.transfer_id) return;
        (groups[tx.transfer_id] = groups[tx.transfer_id] || []).push(tx);
    });

    let volume = 0, count = 0, fees = 0;
    const flowTotals = {};
    const flowMeta   = {};

    Object.values(groups).forEach(legs => {
        const out = legs.find(t => Number(t.amount) < 0 && t.kind === 'transfer');
        const inn = legs.find(t => Number(t.amount) > 0 && t.kind === 'transfer');
        if (!out || !inn) return;
        count++;
        const ars = Math.abs(amountInArs(out));
        volume += ars;
        const key = `${out.account_id}|${inn.account_id}`;
        flowTotals[key] = (flowTotals[key] || 0) + ars;
        flowMeta[key] = {
            from: accById[out.account_id],
            to: accById[inn.account_id],
            count: ((flowMeta[key] && flowMeta[key].count) || 0) + 1,
        };

        const fee = legs.find(t => t.kind === 'fee');
        if (fee) fees += Math.abs(amountInArs(fee));
    });

    document.getElementById('tr-volume').textContent = count > 0 ? fmtArs(volume) : '—';
    document.getElementById('tr-count').textContent = String(count);
    document.getElementById('tr-fees').textContent = fees > 0 ? fmtArs(fees) : '$ 0';

    const sorted = Object.entries(flowTotals).sort((a,b) => b[1] - a[1]);
    const top = sorted[0];
    document.getElementById('tr-top').textContent = top
        ? `${(flowMeta[top[0]].from?.name || '?').slice(0,12)} → ${(flowMeta[top[0]].to?.name || '?').slice(0,12)}`
        : '—';

    const list = document.getElementById('tr-flows');
    list.textContent = '';
    if (sorted.length === 0) {
        list.appendChild(el('li', { className: 'text-xs text-muted py-3 text-center', text: 'Sin transferencias este período.' }));
        return;
    }
    sorted.slice(0, 5).forEach(([key, ars], i) => {
        const meta = flowMeta[key];
        const li = el('li', { className: 'flex items-center justify-between border-b border-border pb-2.5 last:border-b-0 last:pb-0' });

        const left = el('div', { className: 'flex items-center gap-2 text-sm min-w-0' });
        appendAll(left,
            el('span', { className: 'text-muted tabular-nums w-5 flex-shrink-0', text: '0' + (i+1) }),
            el('span', { className: 'font-medium truncate', text: (meta.from && meta.from.name) || '?' }),
            el('span', { className: 'text-muted flex-shrink-0', text: '→' }),
            el('span', { className: 'font-medium truncate', text: (meta.to && meta.to.name) || '?' }),
        );
        appendAll(li, left, el('div', { className: 'font-semibold tabular-nums text-sm flex-shrink-0', text: fmtArs(ars) }));
        list.appendChild(li);
    });
}

// ── Mark-paid actions ─────────────────────────────────────────────────
async function markRecurrentPaid(rec, monthTxs, btn) {
    btn.disabled = true;
    const existing = monthTxs.find(p => p.recurrent_id === rec.id && p.transaction_type === 'recurrent');
    let result;
    try {
        if (existing) {
            result = await api.put('/transactions', { is_paid: 1 }, { id: existing.id });
        } else {
            const now = new Date();
            const y = now.getFullYear(), m = now.getMonth();
            const day = Math.min(rec.due_date_day, lastDayOfMonth(y, m));
            const due_ts = `${y}-${String(m+1).padStart(2,'0')}-${String(day).padStart(2,'0')} 00:00:00`;
            result = await api.post('/transactions', {
                title: rec.title,
                description: rec.description || '',
                amount: rec.amount,
                expense_category_id: rec.expense_category_id || null,
                card_id: rec.card_id || null,
                account_id: rec.account_id || null,
                currency: rec.currency || 'ARS',
                recurrent_id: rec.id,
                transaction_type: 'recurrent',
                due_ts,
                is_paid: true,
            });
        }
        if (!result || result.error) {
            toast(result?.error || 'No se pudo actualizar', 'error');
            btn.disabled = false; return;
        }
        toast('Marcado como pagado', 'success');
        loadAll();
    } catch (err) {
        console.error(err);
        toast('Error de red', 'error');
        btn.disabled = false;
    }
}
async function markPaymentPaid(payment, btn) {
    btn.disabled = true;
    const result = await api.put('/transactions', { is_paid: 1 }, { id: payment.id });
    if (!result || result.error) {
        toast(result?.error || 'No se pudo actualizar', 'error');
        btn.disabled = false; return;
    }
    toast('Marcado como pagado', 'success');
    loadAll();
}

// ── Account carousel (desktop prev/next) ──────────────────────────────
function bindAccountCarousel() {
    const scroller = document.getElementById('account-cards');
    const prev = document.getElementById('account-prev');
    const next = document.getElementById('account-next');
    if (!scroller || !prev || !next) return;

    const stepSize = () => {
        const first = scroller.firstElementChild;
        if (!first) return 280;
        const style = getComputedStyle(scroller);
        const gap = parseFloat(style.columnGap || style.gap || '0') || 0;
        return first.getBoundingClientRect().width + gap;
    };
    const updateNav = () => {
        const max = scroller.scrollWidth - scroller.clientWidth - 1;
        const overflows = max > 1;
        prev.disabled = !overflows || scroller.scrollLeft <= 1;
        next.disabled = !overflows || scroller.scrollLeft >= max;
    };

    prev.addEventListener('click', () => scroller.scrollBy({ left: -stepSize(), behavior: 'smooth' }));
    next.addEventListener('click', () => scroller.scrollBy({ left:  stepSize(), behavior: 'smooth' }));
    scroller.addEventListener('scroll', updateNav, { passive: true });
    window.addEventListener('resize', updateNav);

    // Re-evaluate after the dynamic cards render
    new MutationObserver(updateNav).observe(scroller, { childList: true });
    updateNav();
}

// ── Filter bar interactions ───────────────────────────────────────────
function bindFilterInteractions() {
    document.getElementById('f-period').addEventListener('click', () => {
        STATE.period = STATE.period === 'current' ? 'previous' : 'current';
        document.getElementById('f-period-value').textContent =
            STATE.period === 'current' ? 'Mes actual' : 'Mes anterior';
        loadAll();
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
        loadAll();
    });
}

// ── Main loader ───────────────────────────────────────────────────────
async function loadAll() {
    const range = periodRange();
    const cmp = comparisonRange();

    document.getElementById('period-title').textContent = range.label;
    const left = Math.max(0, Math.ceil((range.end - new Date()) / 86400000));
    document.getElementById('period-sub').textContent = STATE.period === 'current'
        ? `Tu plata, clara. Quedan ${left} días en el período.`
        : `Resumen cerrado de ${range.label}.`;

    await fx.ready;
    const usdRate = fx.rateFor('USD');
    document.getElementById('f-fx-rate').textContent = usdRate
        ? usdRate.toLocaleString('es-AR', { maximumFractionDigits: 0 })
        : '—';

    const [allCurrent, allPrevious, allRecurrents, accountsResp] = await Promise.all([
        api.get('/transactions', { start_date: isoDay(range.start), end_date: isoDay(range.end) }),
        api.get('/transactions', { start_date: isoDay(cmp.start),   end_date: isoDay(cmp.end)   }),
        api.get('/recurrents'),
        api.get('/accounts', { totals: 1 }),
    ]);

    const currentTxs  = allCurrent || [];
    const previousTxs = allPrevious || [];
    const recurrents  = allRecurrents || [];
    const accounts    = (accountsResp && accountsResp.accounts) ? accountsResp.accounts : [];

    document.getElementById('kpi-recurrents').textContent = String(recurrents.length);

    renderHero(currentTxs, previousTxs);
    renderAccounts(accounts, currentTxs);
    renderRecurrents(recurrents, currentTxs);
    renderRecent(currentTxs);
    renderTransfers(currentTxs, accounts);
}

mangosAuth.ready.then((user) => {
    if (!user) return;
    bindFilterInteractions();
    bindAccountCarousel();
    loadAll();
});
</script>
