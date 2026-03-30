<!-- Page header -->
<div class="mb-8">
    <h1 class="text-2xl font-semibold">Dashboard</h1>
    <p class="text-sm text-muted mt-1" id="dashboard-period">--</p>
</div>

<!-- Summary cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="card">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted mb-1">Total Mes</p>
        <p class="text-2xl font-bold" id="total-amount">
            <span class="skeleton inline-block w-32 h-7">&nbsp;</span>
        </p>
    </div>
    <div class="card">
        <p class="text-xs font-semibold tracking-wide uppercase text-success mb-1">Pagados</p>
        <p class="text-2xl font-bold" id="paid-amount">
            <span class="skeleton inline-block w-28 h-7">&nbsp;</span>
        </p>
    </div>
    <div class="card">
        <p class="text-xs font-semibold tracking-wide uppercase text-danger mb-1">Pendientes</p>
        <p class="text-2xl font-bold" id="unpaid-amount">
            <span class="skeleton inline-block w-28 h-7">&nbsp;</span>
        </p>
    </div>
</div>

<!-- Recent payments -->
<div class="card">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Pagos recientes</h2>
        <a href="/pagos" class="text-sm text-accent hover:underline">Ver todos</a>
    </div>
    <div id="recent-payments">
        <div class="space-y-3">
            <div class="flex justify-between items-center py-3 border-b border-border">
                <span class="skeleton w-40 h-4">&nbsp;</span>
                <span class="skeleton w-24 h-4">&nbsp;</span>
            </div>
            <div class="flex justify-between items-center py-3 border-b border-border">
                <span class="skeleton w-32 h-4">&nbsp;</span>
                <span class="skeleton w-20 h-4">&nbsp;</span>
            </div>
            <div class="flex justify-between items-center py-3">
                <span class="skeleton w-36 h-4">&nbsp;</span>
                <span class="skeleton w-24 h-4">&nbsp;</span>
            </div>
        </div>
    </div>
</div>

<!-- Recurrents summary -->
<div class="card mt-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Gastos fijos del mes</h2>
        <a href="/fijos" class="text-sm text-accent hover:underline">Ver todos</a>
    </div>
    <div id="recurrents-summary">
        <div class="space-y-3">
            <div class="flex justify-between items-center py-3 border-b border-border">
                <span class="skeleton w-40 h-4">&nbsp;</span>
                <span class="skeleton w-24 h-4">&nbsp;</span>
            </div>
            <div class="flex justify-between items-center py-3">
                <span class="skeleton w-32 h-4">&nbsp;</span>
                <span class="skeleton w-20 h-4">&nbsp;</span>
            </div>
        </div>
    </div>
</div>

<script>
function createPaymentRow(title, dateText, amount, badgeClass, badgeText, isLast) {
    const row = document.createElement('div');
    row.className = `flex items-center justify-between py-3 ${isLast ? '' : 'border-b border-border'}`;

    const info = document.createElement('div');
    info.className = 'flex-1 min-w-0';

    const titleEl = document.createElement('p');
    titleEl.className = 'text-sm font-medium truncate';
    titleEl.textContent = title;

    const dateEl = document.createElement('p');
    dateEl.className = 'text-xs text-muted';
    dateEl.textContent = dateText;

    info.appendChild(titleEl);
    info.appendChild(dateEl);

    const right = document.createElement('div');
    right.className = 'flex items-center gap-2 ml-4';

    const amountEl = document.createElement('span');
    amountEl.className = 'text-sm font-semibold';
    amountEl.textContent = formatPrice(amount);

    const badge = document.createElement('span');
    badge.className = `badge ${badgeClass}`;
    badge.textContent = badgeText;

    right.appendChild(amountEl);
    right.appendChild(badge);

    row.appendChild(info);
    row.appendChild(right);
    return row;
}

function showEmpty(el, message) {
    el.textContent = '';
    const p = document.createElement('p');
    p.className = 'text-sm text-muted py-6 text-center';
    p.textContent = message;
    el.appendChild(p);
}

// Wait for auth to be ready
mangosAuth.ready.then(async (user) => {
    if (!user) return;

    // Current month date range
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    const startDate = `${year}-${String(month + 1).padStart(2, '0')}-01`;
    const lastDay = new Date(year, month + 1, 0).getDate();
    const endDate = `${year}-${String(month + 1).padStart(2, '0')}-${lastDay}`;

    const monthName = now.toLocaleDateString('es-AR', { month: 'long', year: 'numeric' });
    document.getElementById('dashboard-period').textContent =
        monthName.charAt(0).toUpperCase() + monthName.slice(1);

    // Fetch payments + recurrents in parallel
    const [payments, recurrents] = await Promise.all([
        api.get('/payments', { start_date: startDate, end_date: endDate }),
        api.get('/recurrents'),
    ]);

    if (!payments || !recurrents) return;

    // ── Summary cards ────────────────────────────
    let totalPaid = 0, totalUnpaid = 0;

    payments.forEach(p => {
        if (p.is_paid == 1) totalPaid += Number(p.amount);
        else totalUnpaid += Number(p.amount);
    });

    document.getElementById('total-amount').textContent = formatPrice(totalPaid + totalUnpaid);
    document.getElementById('paid-amount').textContent = formatPrice(totalPaid);
    document.getElementById('unpaid-amount').textContent = formatPrice(totalUnpaid);

    // ── Recent payments list ─────────────────────
    const recentEl = document.getElementById('recent-payments');
    const recent = payments.slice(0, 8);

    if (recent.length === 0) {
        showEmpty(recentEl, 'No hay pagos este mes.');
    } else {
        recentEl.textContent = '';
        recent.forEach((p, i) => {
            const isLast = i === recent.length - 1;
            const isPaid = p.is_paid == 1;
            recentEl.appendChild(createPaymentRow(
                p.title,
                formatDate(p.due_ts),
                p.amount,
                isPaid ? 'badge-success' : 'badge-danger',
                isPaid ? 'Pagado' : 'Pendiente',
                isLast
            ));
        });
    }

    // ── Recurrents summary ───────────────────────
    const recEl = document.getElementById('recurrents-summary');
    const currentDay = now.getDate();

    if (recurrents.length === 0) {
        showEmpty(recEl, 'No hay gastos fijos.');
    } else {
        const recurrentPayments = payments.filter(p => p.payment_type === 'recurrent' && p.recurrent_id);
        const paidRecIds = new Set(recurrentPayments.filter(p => p.is_paid == 1).map(p => p.recurrent_id));

        recEl.textContent = '';
        recurrents.forEach((r, i) => {
            const isLast = i === recurrents.length - 1;
            const isPaid = paidRecIds.has(r.id);
            const isOverdue = !isPaid && r.due_date_day < currentDay;

            let badgeClass, badgeText;
            if (isPaid) { badgeClass = 'badge-success'; badgeText = 'Pagado'; }
            else if (isOverdue) { badgeClass = 'badge-danger'; badgeText = 'Vencido'; }
            else { badgeClass = 'badge-muted'; badgeText = 'Pendiente'; }

            recEl.appendChild(createPaymentRow(
                r.title,
                `Vence el ${r.due_date_day} de cada mes`,
                r.amount,
                badgeClass,
                badgeText,
                isLast
            ));
        });
    }
});
</script>

