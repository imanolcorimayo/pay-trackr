<div class="max-w-6xl mx-auto">

<!-- Page header -->
<div class="mb-5">
    <h1 class="text-2xl font-semibold">Dashboard</h1>
    <p class="text-sm text-muted mt-1" id="dashboard-period">--</p>
    <p id="fx-footnote" class="hidden text-xs text-muted mt-1.5 italic">
        Sólo ARS — multimoneda llega en próxima fase
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

<!-- Hero: this month total + comparison + 6-month sparkline -->
<div class="card lg:col-span-12 lg:p-7" id="hero-card">
    <div class="flex items-baseline justify-between gap-3 mb-1">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Total mes</p>
        <span id="hero-trend" class="text-xs font-medium hidden"></span>
    </div>
    <p class="text-3xl lg:text-5xl font-bold tracking-tight" id="hero-total">
        <span class="skeleton inline-block w-40 h-8 lg:h-12">&nbsp;</span>
    </p>
    <p class="text-xs lg:text-sm text-muted mt-1 lg:mt-2" id="hero-compare">&nbsp;</p>

    <!-- Sparkline -->
    <div class="mt-4 lg:mt-6 flex items-end gap-1.5 lg:gap-2 h-24 lg:h-48" id="hero-sparkline">
        <span class="skeleton flex-1 h-full"></span>
        <span class="skeleton flex-1 h-2/3"></span>
        <span class="skeleton flex-1 h-1/2"></span>
        <span class="skeleton flex-1 h-3/4"></span>
        <span class="skeleton flex-1 h-2/3"></span>
        <span class="skeleton flex-1 h-full"></span>
    </div>
    <div class="mt-1 flex gap-1.5 text-[10px] text-muted" id="hero-sparkline-labels"></div>
</div>

<!-- Left column on desktop: vencidos + próximos -->
<div class="lg:col-span-7 flex flex-col gap-5">

<!-- Vencidos (hidden when none) -->
<div class="card border-danger/30 bg-danger/5 hidden" id="overdue-card">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-semibold text-danger flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
            </svg>
            Vencidos
        </h2>
        <span id="overdue-count" class="badge badge-danger"></span>
    </div>
    <div id="overdue-list" class="divide-y divide-border"></div>
</div>

<!-- Próximos 7 días -->
<div class="card">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-semibold">Próximos 7 días</h2>
        <a href="/fijos" class="text-xs text-accent hover:underline">Ver todos</a>
    </div>
    <div id="upcoming-list">
        <div class="space-y-3">
            <div class="flex justify-between items-center py-3 border-b border-border">
                <span class="skeleton w-40 h-4">&nbsp;</span>
                <span class="skeleton w-20 h-4">&nbsp;</span>
            </div>
            <div class="flex justify-between items-center py-3">
                <span class="skeleton w-32 h-4">&nbsp;</span>
                <span class="skeleton w-20 h-4">&nbsp;</span>
            </div>
        </div>
    </div>
</div>

    </div><!-- /left column -->

    <!-- Right column on desktop: categorías -->
    <div class="lg:col-span-5 lg:sticky lg:top-20 lg:self-start">

        <!-- Top categorías del mes -->
        <div class="card">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold">Categorías del mes</h2>
                <a href="/analisis" class="text-xs text-accent hover:underline">Ver todas</a>
            </div>
            <div id="top-categories">
                <div class="space-y-3">
                    <div><span class="skeleton block w-full h-3"></span></div>
                    <div><span class="skeleton block w-2/3 h-3"></span></div>
                    <div><span class="skeleton block w-1/2 h-3"></span></div>
                </div>
            </div>
        </div>

    </div><!-- /right column -->

</div><!-- /grid -->
</div><!-- /max-w wrapper -->

<script>
const MONTH_LABELS_SHORT = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];

function isoDay(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
function startOfMonth(year, month) { return new Date(year, month, 1); }
function lastDayOfMonth(year, month) { return new Date(year, month + 1, 0).getDate(); }

function parseLocalDate(s) {
    if (!s) return null;
    const m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!m) return null;
    return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
}

// ── Hero ──────────────────────────────────────────────────────────────
function renderHero(monthTotals, currentMonthIdx) {
    const current = monthTotals[currentMonthIdx];
    document.getElementById('hero-total').textContent = formatPrice(current);

    const prev = monthTotals.slice(Math.max(0, currentMonthIdx - 3), currentMonthIdx).filter(v => v > 0);
    const compareEl = document.getElementById('hero-compare');
    const trendEl = document.getElementById('hero-trend');

    if (prev.length > 0) {
        const avg = prev.reduce((s, v) => s + v, 0) / prev.length;
        compareEl.textContent = `Promedio últimos ${prev.length} meses: ${formatPrice(avg)}`;
        if (avg > 0) {
            const pct = Math.round(((current - avg) / avg) * 100);
            trendEl.textContent = `${pct > 0 ? '+' : ''}${pct}% vs promedio`;
            trendEl.classList.remove('hidden', 'text-success', 'text-danger', 'text-muted');
            trendEl.classList.add(pct < 0 ? 'text-success' : (pct > 0 ? 'text-danger' : 'text-muted'));
        }
    } else {
        compareEl.textContent = 'Sin historial para comparar';
    }

    const max = Math.max(...monthTotals, 1);
    const sparkEl = document.getElementById('hero-sparkline');
    sparkEl.textContent = '';
    monthTotals.forEach((v, i) => {
        const bar = document.createElement('div');
        const heightPct = Math.max(2, Math.round((v / max) * 100));
        bar.className = `flex-1 rounded-t transition-colors ${i === currentMonthIdx ? 'bg-accent' : 'bg-border'}`;
        bar.style.height = `${heightPct}%`;
        bar.title = formatPrice(v);
        sparkEl.appendChild(bar);
    });
}

// ── Row builder ───────────────────────────────────────────────────────
function pillRow({ title, sub, amount, action }) {
    const row = document.createElement('div');
    row.className = 'flex items-center gap-3 py-3';

    const info = document.createElement('div');
    info.className = 'flex-1 min-w-0';

    const t = document.createElement('p');
    t.className = 'text-sm font-medium truncate';
    t.textContent = title;

    const s = document.createElement('p');
    s.className = 'text-xs text-muted mt-0.5';
    s.textContent = sub;

    info.appendChild(t);
    info.appendChild(s);

    const right = document.createElement('div');
    right.className = 'flex items-center gap-2 flex-shrink-0';

    const amt = document.createElement('span');
    amt.className = 'text-sm font-semibold tabular-nums';
    amt.textContent = formatPrice(Math.abs(amount));
    right.appendChild(amt);

    if (action) right.appendChild(action);

    row.appendChild(info);
    row.appendChild(right);
    return row;
}

function makePayBtn(onClick) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'px-3 py-1.5 rounded-md bg-accent text-white text-xs font-medium active:scale-95 transition disabled:opacity-50';
    btn.textContent = 'Pagado';
    btn.style.touchAction = 'manipulation';
    btn.addEventListener('click', onClick);
    return btn;
}

// ── Top categories ────────────────────────────────────────────────────
function renderTopCategories(monthPayments, categoriesById) {
    const totals = {};
    let grand = 0;
    monthPayments.forEach(p => {
        const id = p.expense_category_id || '__none';
        const amt = Math.abs(Number(p.amount) || 0);
        totals[id] = (totals[id] || 0) + amt;
        grand += amt;
    });

    const sorted = Object.entries(totals).sort((a, b) => b[1] - a[1]).slice(0, 3);
    const el = document.getElementById('top-categories');
    el.textContent = '';

    if (sorted.length === 0 || grand === 0) {
        const p = document.createElement('p');
        p.className = 'text-sm text-muted py-6 text-center';
        p.textContent = 'Sin gastos este mes.';
        el.appendChild(p);
        return;
    }

    const wrap = document.createElement('div');
    wrap.className = 'space-y-3';
    const max = sorted[0][1];

    sorted.forEach(([id, amount]) => {
        const cat = categoriesById[id];
        const name = cat ? cat.name : 'Sin categoría';
        const pct = Math.round((amount / grand) * 100);
        const widthPct = Math.max(4, Math.round((amount / max) * 100));

        const row = document.createElement('div');

        const head = document.createElement('div');
        head.className = 'flex items-center justify-between text-sm mb-1';
        const nameEl = document.createElement('span');
        nameEl.className = 'font-medium truncate pr-2';
        nameEl.textContent = name;
        const valEl = document.createElement('span');
        valEl.className = 'text-muted tabular-nums flex-shrink-0';
        valEl.textContent = `${formatPrice(amount)} · ${pct}%`;
        head.appendChild(nameEl);
        head.appendChild(valEl);

        const bar = document.createElement('div');
        bar.className = 'h-2 bg-border rounded-full overflow-hidden';
        const fill = document.createElement('div');
        fill.className = 'h-full bg-accent rounded-full';
        fill.style.width = `${widthPct}%`;
        bar.appendChild(fill);

        row.appendChild(head);
        row.appendChild(bar);
        wrap.appendChild(row);
    });

    el.appendChild(wrap);
}

// ── Mark-paid actions ─────────────────────────────────────────────────
async function markRecurrentPaid(rec, monthPayments, btn) {
    btn.disabled = true;
    const existing = monthPayments.find(p => p.recurrent_id === rec.id && p.transaction_type === 'recurrent');
    let result;
    try {
        if (existing) {
            result = await api.put('/transactions', { is_paid: 1 }, { id: existing.id });
        } else {
            const now = new Date();
            const y = now.getFullYear();
            const m = now.getMonth();
            const day = Math.min(rec.due_date_day, lastDayOfMonth(y, m));
            const due_ts = `${y}-${String(m + 1).padStart(2, '0')}-${String(day).padStart(2, '0')} 00:00:00`;
            result = await api.post('/transactions', {
                title: rec.title,
                description: rec.description || '',
                amount: rec.amount,
                expense_category_id: rec.expense_category_id || null,
                card_id: rec.card_id || null,
                recurrent_id: rec.id,
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
        btn.disabled = false;
        return;
    }
    toast('Marcado como pagado', 'success');
    loadAll();
}

// ── Main ──────────────────────────────────────────────────────────────
async function loadAll() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    const today = new Date(year, month, now.getDate());
    const sevenDays = new Date(year, month, now.getDate() + 7);

    const monthName = now.toLocaleDateString('es-AR', { month: 'long', year: 'numeric' });
    document.getElementById('dashboard-period').textContent =
        monthName.charAt(0).toUpperCase() + monthName.slice(1);

    const sparkStart = startOfMonth(year, month - 5);
    const sparkEnd = new Date(year, month + 1, 0);

    const [allPayments, allRecurrents, categories] = await Promise.all([
        api.get('/transactions', { start_date: isoDay(sparkStart), end_date: isoDay(sparkEnd) }),
        api.get('/recurrents'),
        api.get('/categories'),
    ]);

    if (!allPayments || !allRecurrents) return;
    // Phase 2: dashboard aggregations are ARS-only. Multi-currency support
    // arrives in Phase 3 alongside FX rates. Non-ARS rows still live on
    // /movimientos but don't appear in totals/charts here.
    const payments = allPayments.filter(p => (p.currency || 'ARS') === 'ARS');
    const recurrents = allRecurrents.filter(r => (r.currency || 'ARS') === 'ARS');
    const hasNonArs = allPayments.length !== payments.length || allRecurrents.length !== recurrents.length;
    const fxFootnote = document.getElementById('fx-footnote');
    if (fxFootnote) fxFootnote.classList.toggle('hidden', !hasNonArs);
    const cats = categories || [];
    const categoriesById = {};
    cats.forEach(c => { categoriesById[c.id] = c; });

    const monthTotals = new Array(6).fill(0);
    payments.forEach(p => {
        const d = parseLocalDate(p.due_ts);
        if (!d) return;
        const idx = (d.getFullYear() - sparkStart.getFullYear()) * 12 + (d.getMonth() - sparkStart.getMonth());
        if (idx >= 0 && idx < 6) monthTotals[idx] += Math.abs(Number(p.amount) || 0);
    });

    const labelsEl = document.getElementById('hero-sparkline-labels');
    labelsEl.textContent = '';
    for (let i = 0; i < 6; i++) {
        const d = new Date(sparkStart.getFullYear(), sparkStart.getMonth() + i, 1);
        const span = document.createElement('span');
        span.className = 'flex-1 text-center';
        span.textContent = MONTH_LABELS_SHORT[d.getMonth()];
        labelsEl.appendChild(span);
    }

    renderHero(monthTotals, 5);

    const monthPayments = payments.filter(p => {
        const d = parseLocalDate(p.due_ts);
        return d && d.getFullYear() === year && d.getMonth() === month;
    });
    const paidRecurrentIds = new Set(
        monthPayments.filter(p => p.is_paid == 1 && p.recurrent_id).map(p => p.recurrent_id)
    );

    // ── Vencidos ──
    const overdueRows = [];
    recurrents.forEach(r => {
        if (paidRecurrentIds.has(r.id)) return;
        if (r.due_date_day < now.getDate()) {
            overdueRows.push({ kind: 'recurrent', item: r, dueDay: r.due_date_day });
        }
    });
    monthPayments.forEach(p => {
        if (p.is_paid == 1) return;
        if (p.transaction_type === 'recurrent') return;
        const d = parseLocalDate(p.due_ts);
        if (d && d < today) overdueRows.push({ kind: 'payment', item: p });
    });

    const overdueCard = document.getElementById('overdue-card');
    const overdueList = document.getElementById('overdue-list');
    if (overdueRows.length === 0) {
        overdueCard.classList.add('hidden');
    } else {
        overdueCard.classList.remove('hidden');
        document.getElementById('overdue-count').textContent = String(overdueRows.length);
        overdueList.textContent = '';
        overdueRows.forEach(({ kind, item, dueDay }) => {
            const sub = kind === 'recurrent' ? `Vencía el ${dueDay}` : `Vencía ${formatDate(item.due_ts)}`;
            const btn = makePayBtn(() => kind === 'recurrent'
                ? markRecurrentPaid(item, monthPayments, btn)
                : markPaymentPaid(item, btn));
            overdueList.appendChild(pillRow({ title: item.title, sub, amount: item.amount, action: btn }));
        });
    }

    // ── Próximos 7 días ──
    const upcomingRows = [];
    recurrents.forEach(r => {
        if (paidRecurrentIds.has(r.id)) return;
        const day = r.due_date_day;
        if (day < now.getDate()) return;
        // Within current month and within 7 days from today
        if (sevenDays.getMonth() === month) {
            if (day <= sevenDays.getDate()) {
                upcomingRows.push({ kind: 'recurrent', item: r, dueDay: day });
            }
        } else {
            // Window crosses month boundary — include all remaining days of this month
            upcomingRows.push({ kind: 'recurrent', item: r, dueDay: day });
        }
    });
    monthPayments.forEach(p => {
        if (p.is_paid == 1) return;
        if (p.transaction_type === 'recurrent') return;
        const d = parseLocalDate(p.due_ts);
        if (d && d >= today && d <= sevenDays) {
            upcomingRows.push({ kind: 'payment', item: p, dueDay: d.getDate() });
        }
    });
    upcomingRows.sort((a, b) => a.dueDay - b.dueDay);

    const upcomingEl = document.getElementById('upcoming-list');
    upcomingEl.textContent = '';
    if (upcomingRows.length === 0) {
        const p = document.createElement('p');
        p.className = 'text-sm text-muted py-6 text-center';
        p.textContent = 'Nada vence en los próximos 7 días.';
        upcomingEl.appendChild(p);
    } else {
        const wrap = document.createElement('div');
        wrap.className = 'divide-y divide-border';
        upcomingRows.forEach(({ kind, item, dueDay }) => {
            const isToday = dueDay === now.getDate();
            const sub = kind === 'recurrent'
                ? (isToday ? 'Vence hoy' : `Vence el ${dueDay}`)
                : (isToday ? 'Vence hoy' : `Vence ${formatDate(item.due_ts)}`);
            const btn = makePayBtn(() => kind === 'recurrent'
                ? markRecurrentPaid(item, monthPayments, btn)
                : markPaymentPaid(item, btn));
            wrap.appendChild(pillRow({ title: item.title, sub, amount: item.amount, action: btn }));
        });
        upcomingEl.appendChild(wrap);
    }

    renderTopCategories(monthPayments, categoriesById);
}

mangosAuth.ready.then((user) => {
    if (!user) return;
    loadAll();
});
</script>
