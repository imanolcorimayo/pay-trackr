<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-semibold">Analisis</h1>
        <p class="text-sm text-muted mt-1">Como gastas tu plata</p>
    </div>
    <select id="period-select" class="input sm:max-w-[220px]">
        <option value="3m">Ultimos 3 meses</option>
        <option value="6m" selected>Ultimos 6 meses</option>
        <option value="12m">Ultimos 12 meses</option>
        <option value="ytd">Este año</option>
        <option value="all">Todo</option>
    </select>
</div>

<!-- KPI row -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Total gastado</p>
        <p class="text-lg font-bold mt-0.5" id="kpi-total">--</p>
        <p class="text-xs text-muted mt-0.5" id="kpi-total-count">&nbsp;</p>
    </div>
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Promedio mensual</p>
        <p class="text-lg font-bold mt-0.5" id="kpi-avg">--</p>
        <p class="text-xs text-muted mt-0.5" id="kpi-avg-sub">&nbsp;</p>
    </div>
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-success">Pagados</p>
        <p class="text-lg font-bold mt-0.5" id="kpi-paid">--</p>
        <p class="text-xs text-muted mt-0.5" id="kpi-paid-count">&nbsp;</p>
    </div>
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-danger">Pendientes</p>
        <p class="text-lg font-bold mt-0.5" id="kpi-unpaid">--</p>
        <p class="text-xs text-muted mt-0.5" id="kpi-unpaid-count">&nbsp;</p>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="card">
        <h2 class="text-base font-semibold mb-4">Gasto por mes</h2>
        <div class="relative h-64">
            <canvas id="chart-monthly"></canvas>
        </div>
    </div>
    <div class="card">
        <h2 class="text-base font-semibold mb-4">Gasto por categoria</h2>
        <div class="relative h-64">
            <canvas id="chart-category"></canvas>
        </div>
    </div>
</div>

<!-- Top categories list -->
<div class="card">
    <h2 class="text-base font-semibold mb-4">Top categorias</h2>
    <div id="top-categories">
        <p class="text-sm text-muted py-6 text-center">Cargando...</p>
    </div>
</div>

<!-- Empty state, hidden by default -->
<div id="empty-state" class="hidden card text-center py-12">
    <p class="text-sm text-muted">No hay pagos en este periodo.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
let payments = [];
let categories = [];
let monthlyChart = null;
let categoryChart = null;
let period = '6m';

// ── URL state ───────────────────────────────────────────────────────
function readUrlState() {
    const p = new URLSearchParams(window.location.search);
    const v = p.get('period');
    if (['3m', '6m', '12m', 'ytd', 'all'].includes(v)) period = v;
}

function writeUrlState() {
    const params = new URLSearchParams();
    if (period !== '6m') params.set('period', period);
    const qs = params.toString();
    history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
}

// ── Period → date range ─────────────────────────────────────────────
function periodRange(p) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    let start;
    switch (p) {
        case '3m':  start = new Date(today.getFullYear(), today.getMonth() - 2, 1); break;
        case '6m':  start = new Date(today.getFullYear(), today.getMonth() - 5, 1); break;
        case '12m': start = new Date(today.getFullYear(), today.getMonth() - 11, 1); break;
        case 'ytd': start = new Date(today.getFullYear(), 0, 1); break;
        case 'all': start = null; break;
        default:    start = new Date(today.getFullYear(), today.getMonth() - 5, 1);
    }
    const end = new Date(today.getFullYear(), today.getMonth() + 1, 0); // last day of current month
    return { start, end };
}

function isoDate(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// Build sequential month buckets covering the data range
function monthBucketsFor(payments, range) {
    let earliest;
    if (range.start) {
        earliest = new Date(range.start.getFullYear(), range.start.getMonth(), 1);
    } else {
        // 'all' → derive earliest from the data
        const dates = payments.map(p => p.due_ts).filter(Boolean).map(s => new Date(s.replace(' ', 'T')));
        if (dates.length === 0) return [];
        const min = new Date(Math.min(...dates));
        earliest = new Date(min.getFullYear(), min.getMonth(), 1);
    }
    const latest = new Date(range.end.getFullYear(), range.end.getMonth(), 1);

    const buckets = [];
    const cur = new Date(earliest);
    while (cur <= latest) {
        const label = cur.toLocaleDateString('es-AR', { month: 'short', year: '2-digit' });
        buckets.push({
            key: `${cur.getFullYear()}-${String(cur.getMonth() + 1).padStart(2, '0')}`,
            label: label.charAt(0).toUpperCase() + label.slice(1),
            year: cur.getFullYear(),
            month: cur.getMonth(),
        });
        cur.setMonth(cur.getMonth() + 1);
    }
    return buckets;
}

// ── Loading ─────────────────────────────────────────────────────────
async function loadAll() {
    const range = periodRange(period);
    const params = {};
    if (range.start) {
        params.start_date = isoDate(range.start);
        params.end_date = isoDate(range.end);
    }

    const [pays, cats] = await Promise.all([
        api.get('/payments', params),
        api.get('/categories'),
    ]);
    payments = pays || [];
    categories = cats || [];

    if (payments.length === 0) {
        document.querySelectorAll('.card').forEach(el => el.classList.add('hidden'));
        document.getElementById('empty-state').classList.remove('hidden');
        return;
    }
    document.querySelectorAll('.card').forEach(el => el.classList.remove('hidden'));
    document.getElementById('empty-state').classList.add('hidden');

    renderKpis();
    renderMonthlyChart(range);
    renderCategoryChart();
    renderTopCategories();
}

// ── KPIs ────────────────────────────────────────────────────────────
function renderKpis() {
    let total = 0, paid = 0, unpaid = 0;
    let paidCount = 0, unpaidCount = 0;
    const monthsWithData = new Set();

    payments.forEach(p => {
        const a = Number(p.amount);
        total += a;
        if (p.is_paid == 1) { paid += a; paidCount++; }
        else { unpaid += a; unpaidCount++; }
        if (p.due_ts) {
            const d = new Date(p.due_ts.replace(' ', 'T'));
            monthsWithData.add(`${d.getFullYear()}-${d.getMonth()}`);
        }
    });

    const monthCount = Math.max(monthsWithData.size, 1);
    const avg = total / monthCount;

    const plural = (n) => `${n} pago${n === 1 ? '' : 's'}`;

    document.getElementById('kpi-total').textContent = formatPrice(total);
    document.getElementById('kpi-total-count').textContent = plural(payments.length);
    document.getElementById('kpi-avg').textContent = formatPrice(avg);
    document.getElementById('kpi-avg-sub').textContent = `sobre ${monthCount} mes${monthCount === 1 ? '' : 'es'}`;
    document.getElementById('kpi-paid').textContent = formatPrice(paid);
    document.getElementById('kpi-paid-count').textContent = plural(paidCount);
    document.getElementById('kpi-unpaid').textContent = formatPrice(unpaid);
    document.getElementById('kpi-unpaid-count').textContent = plural(unpaidCount);
}

// ── Monthly bar chart ───────────────────────────────────────────────
function renderMonthlyChart(range) {
    const buckets = monthBucketsFor(payments, range);
    const totals = buckets.map(() => 0);
    const indexByKey = Object.fromEntries(buckets.map((b, i) => [b.key, i]));

    payments.forEach(p => {
        if (!p.due_ts) return;
        const d = new Date(p.due_ts.replace(' ', 'T'));
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        if (key in indexByKey) totals[indexByKey[key]] += Number(p.amount);
    });

    if (monthlyChart) monthlyChart.destroy();
    const ctx = document.getElementById('chart-monthly').getContext('2d');
    monthlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: buckets.map(b => b.label),
            datasets: [{
                label: 'Gasto',
                data: totals,
                backgroundColor: '#D97706',
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: (ctx) => formatPrice(ctx.parsed.y) },
                },
            },
            scales: {
                y: {
                    ticks: {
                        callback: (v) => v >= 1000 ? `${(v / 1000).toFixed(0)}k` : v,
                    },
                    grid: { color: '#E8E2DA' },
                },
                x: { grid: { display: false } },
            },
        },
    });
}

// ── Category donut ──────────────────────────────────────────────────
function aggregateByCategory() {
    const map = {};
    payments.forEach(p => {
        const id = p.expense_category_id || 'none';
        map[id] = (map[id] || 0) + Number(p.amount);
    });
    return Object.entries(map)
        .map(([id, amount]) => {
            if (id === 'none') return { id, name: 'Sin categoria', color: '#A8A29E', amount };
            const cat = categories.find(c => c.id === id);
            return {
                id,
                name: cat?.name || 'Eliminada',
                color: cat?.color || '#A8A29E',
                amount,
            };
        })
        .sort((a, b) => b.amount - a.amount);
}

function renderCategoryChart() {
    const agg = aggregateByCategory();

    if (categoryChart) categoryChart.destroy();
    const ctx = document.getElementById('chart-category').getContext('2d');
    categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: agg.map(c => c.name),
            datasets: [{
                data: agg.map(c => c.amount),
                backgroundColor: agg.map(c => c.color),
                borderWidth: 2,
                borderColor: '#FFFBF5',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                            return `${ctx.label}: ${formatPrice(ctx.parsed)} (${pct}%)`;
                        },
                    },
                },
            },
        },
    });
}

// ── Top categories list ─────────────────────────────────────────────
function renderTopCategories() {
    const agg = aggregateByCategory();
    const top = agg.slice(0, 10);
    const total = agg.reduce((s, c) => s + c.amount, 0);

    const container = document.getElementById('top-categories');
    container.textContent = '';

    if (top.length === 0 || total === 0) {
        const p = document.createElement('p');
        p.className = 'text-sm text-muted py-6 text-center';
        p.textContent = 'Sin datos.';
        container.appendChild(p);
        return;
    }

    top.forEach((c, i) => {
        const pct = (c.amount / total) * 100;
        const row = document.createElement('div');
        row.className = `flex items-center gap-3 py-2 ${i === top.length - 1 ? '' : 'border-b border-border'}`;

        const dot = document.createElement('div');
        dot.className = 'h-3 w-3 rounded-full flex-shrink-0';
        dot.style.backgroundColor = c.color;
        row.appendChild(dot);

        const main = document.createElement('div');
        main.className = 'flex-1 min-w-0';

        const head = document.createElement('div');
        head.className = 'flex items-center justify-between gap-3';
        const name = document.createElement('span');
        name.className = 'text-sm font-medium truncate';
        name.textContent = c.name;
        const amount = document.createElement('span');
        amount.className = 'text-sm font-semibold flex-shrink-0';
        amount.textContent = formatPrice(c.amount);
        head.appendChild(name);
        head.appendChild(amount);
        main.appendChild(head);

        // Progress bar with category color
        const barWrap = document.createElement('div');
        barWrap.className = 'mt-1.5 h-1.5 bg-border/50 rounded-full overflow-hidden';
        const bar = document.createElement('div');
        bar.className = 'h-full rounded-full';
        bar.style.width = `${pct.toFixed(1)}%`;
        bar.style.backgroundColor = c.color;
        barWrap.appendChild(bar);
        main.appendChild(barWrap);

        const pctEl = document.createElement('p');
        pctEl.className = 'text-xs text-muted mt-1';
        pctEl.textContent = `${pct.toFixed(1)}% del total`;
        main.appendChild(pctEl);

        row.appendChild(main);
        container.appendChild(row);
    });
}

// ── Init ────────────────────────────────────────────────────────────
mangosAuth.ready.then(user => {
    if (!user) return;

    readUrlState();
    document.getElementById('period-select').value = period;

    document.getElementById('period-select').addEventListener('change', (e) => {
        period = e.target.value;
        writeUrlState();
        loadAll();
    });

    loadAll();
});
</script>
