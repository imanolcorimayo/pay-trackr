<!-- Page header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-semibold">Gastos Fijos</h1>
        <p class="text-sm text-muted mt-1">Pagos recurrentes que se repiten cada mes</p>
    </div>
    <button class="btn btn-primary" onclick="openRecurrentModal()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo
    </button>
</div>

<!-- Summary card -->
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

<!-- List -->
<div class="card">
    <div id="recurrents-list">
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

<!-- ─────────────────────────── Form modal ─────────────────────────── -->
<div id="recurrent-modal" class="fixed inset-0 z-50 hidden bg-dark/40 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-border w-full max-w-lg">
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
                        <input type="text" id="rec-amount" class="input" placeholder="1234,56" inputmode="decimal" required>
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
                        <label for="rec-card" class="block text-sm font-medium mb-1.5">Tarjeta</label>
                        <select id="rec-card" class="input">
                            <option value="">Sin tarjeta</option>
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

                <p id="rec-form-error" class="hidden text-sm text-danger">&nbsp;</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeRecurrentModal()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" id="rec-form-submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Confirm delete ─────────────────────────── -->
<div id="recurrent-delete-modal" class="fixed inset-0 z-50 hidden bg-dark/40">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-border w-full max-w-sm p-5">
            <h2 class="text-lg font-semibold">Eliminar gasto fijo</h2>
            <p class="text-sm text-muted mt-2">
                Vas a eliminar <span id="rec-delete-name" class="font-medium text-dark"></span>.
                Tambien se eliminaran sus pagos asociados (de cualquier mes).
            </p>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closeRecurrentDelete()" class="btn btn-ghost">Cancelar</button>
                <button type="button" onclick="confirmRecurrentDelete()" id="rec-delete-submit" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
const SVG_NS = 'http://www.w3.org/2000/svg';
const ICON_EDIT = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
const ICON_TRASH = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3';

const PERIOD_LABEL = { monthly: 'Mensual', yearly: 'Anual', weekly: 'Semanal', biweekly: 'Quincenal' };

let recurrents = [];
let categories = [];
let cards = [];
let monthlyPayments = [];   // payments for current month, used for paid status
let editingId = null;
let pendingDeleteId = null;

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

function iconButton(pathD, cls, onClick) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `p-1.5 rounded ${cls} hover:bg-dark/5 transition-colors`;
    btn.appendChild(svgIcon(pathD));
    btn.addEventListener('click', e => { e.stopPropagation(); onClick(); });
    return btn;
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

// ── Loading & rendering ─────────────────────────────────────────────
async function loadAll() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    const startDate = `${year}-${String(month + 1).padStart(2, '0')}-01`;
    const lastDay = new Date(year, month + 1, 0).getDate();
    const endDate = `${year}-${String(month + 1).padStart(2, '0')}-${lastDay}`;

    const [recs, cats, crds, pays] = await Promise.all([
        api.get('/recurrents'),
        api.get('/categories'),
        api.get('/cards'),
        api.get('/payments', { start_date: startDate, end_date: endDate }),
    ]);

    recurrents = recs || [];
    categories = cats || [];
    cards = crds || [];
    monthlyPayments = pays || [];

    populateDropdowns();
    renderSummary();
    renderRecurrents();
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

    const cardSel = document.getElementById('rec-card');
    cardSel.textContent = '';
    const cardEmpty = document.createElement('option');
    cardEmpty.value = '';
    cardEmpty.textContent = 'Sin tarjeta';
    cardSel.appendChild(cardEmpty);
    cards.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.name + (c.last_four ? ` ····${c.last_four}` : '');
        cardSel.appendChild(opt);
    });
}

function renderSummary() {
    let total = 0, paid = 0, unpaid = 0;
    const paidRecIds = new Set(
        monthlyPayments.filter(p => p.is_paid == 1 && p.recurrent_id).map(p => p.recurrent_id)
    );

    recurrents.forEach(r => {
        // Annualize yearly: amount/12 contributes to monthly total
        const monthlyEquivalent = r.time_period === 'yearly' ? Number(r.amount) / 12 : Number(r.amount);
        total += monthlyEquivalent;
        if (paidRecIds.has(r.id)) paid += monthlyEquivalent;
        else unpaid += monthlyEquivalent;
    });

    document.getElementById('rec-total').textContent = formatPrice(total);
    document.getElementById('rec-paid').textContent = formatPrice(paid);
    document.getElementById('rec-unpaid').textContent = formatPrice(unpaid);
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

    // Sort by due_date_day ascending so upcoming bills surface first
    const sorted = [...recurrents].sort((a, b) => a.due_date_day - b.due_date_day);

    const paidRecIds = new Set(
        monthlyPayments.filter(p => p.is_paid == 1 && p.recurrent_id).map(p => p.recurrent_id)
    );
    const today = new Date().getDate();

    sorted.forEach((r, i) => {
        const isLast = i === sorted.length - 1;
        const isPaid = paidRecIds.has(r.id);
        const isOverdue = !isPaid && r.due_date_day < today;
        listEl.appendChild(buildRecurrentRow(r, isPaid, isOverdue, isLast));
    });
}

function buildRecurrentRow(r, isPaid, isOverdue, isLast) {
    const row = document.createElement('div');
    row.className = `group flex items-center gap-3 py-3 px-1 cursor-pointer hover:bg-dark/5 -mx-1 rounded transition-colors ${isLast ? '' : 'border-b border-border'}`;
    row.addEventListener('click', () => openRecurrentModal(r));

    const cat = categoryById(r.expense_category_id);
    const dot = document.createElement('div');
    dot.className = 'h-2.5 w-2.5 rounded-full flex-shrink-0';
    dot.style.backgroundColor = cat?.color || '#E8E2DA';
    dot.title = cat?.name || 'Sin categoria';
    row.appendChild(dot);

    const info = document.createElement('div');
    info.className = 'flex-1 min-w-0';

    const title = document.createElement('p');
    title.className = 'text-sm font-medium truncate';
    title.textContent = r.title;
    info.appendChild(title);

    const subtitle = document.createElement('p');
    subtitle.className = 'text-xs text-muted truncate';
    const subParts = [`Vence el ${r.due_date_day}`];
    if (r.time_period && r.time_period !== 'monthly') {
        subParts.push((PERIOD_LABEL[r.time_period] || r.time_period).toLowerCase());
    }
    const card = cardById(r.card_id);
    if (card) subParts.push(card.name + (card.last_four ? ` ····${card.last_four}` : ''));
    subtitle.textContent = subParts.join(' · ');
    info.appendChild(subtitle);

    row.appendChild(info);

    const right = document.createElement('div');
    right.className = 'flex items-center gap-2 flex-shrink-0';

    const amountEl = document.createElement('span');
    amountEl.className = 'text-sm font-semibold';
    amountEl.textContent = formatPrice(r.amount);
    right.appendChild(amountEl);

    const badge = document.createElement('button');
    badge.type = 'button';
    let badgeVariant;
    if (isPaid) { badgeVariant = 'badge-success'; badge.textContent = 'Pagado'; }
    else if (isOverdue) { badgeVariant = 'badge-danger'; badge.textContent = 'Vencido'; }
    else { badgeVariant = 'badge-muted'; badge.textContent = 'Pendiente'; }
    badge.className = `badge ${badgeVariant} cursor-pointer hover:opacity-80 active:scale-95 transition disabled:opacity-50 disabled:cursor-wait`;
    badge.title = isPaid ? 'Marcar como pendiente' : 'Marcar como pagado';
    badge.addEventListener('click', e => {
        e.stopPropagation();
        toggleRecurrentPaid(r, badge);
    });
    right.appendChild(badge);

    const actions = document.createElement('div');
    actions.className = 'flex gap-0.5 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity';
    actions.appendChild(iconButton(ICON_EDIT, 'text-muted hover:text-dark', () => openRecurrentModal(r)));
    actions.appendChild(iconButton(ICON_TRASH, 'text-muted hover:text-danger', () => openRecurrentDelete(r)));
    right.appendChild(actions);

    row.appendChild(right);
    return row;
}

// ── Toggle paid status for the current month ────────────────────────
async function toggleRecurrentPaid(r, btn) {
    btn.disabled = true;

    // Find any existing instance for this recurrent in the current month
    const instance = monthlyPayments.find(
        p => p.recurrent_id === r.id && p.payment_type === 'recurrent'
    );
    const wasPaid = instance && instance.is_paid == 1;

    try {
        let result;
        if (instance) {
            // Flip is_paid on the existing instance (preserves any custom edits)
            result = await api.put('/payments', { is_paid: !wasPaid }, { id: instance.id });
        } else {
            // No instance yet -> create a paid one carrying over the recurrent's data.
            // Clamp due_date_day to the month's last day so e.g. day=31 in February doesn't break.
            const now = new Date();
            const y = now.getFullYear();
            const m = now.getMonth();
            const lastDay = new Date(y, m + 1, 0).getDate();
            const day = Math.min(r.due_date_day, lastDay);
            const due_ts = `${y}-${String(m + 1).padStart(2, '0')}-${String(day).padStart(2, '0')} 00:00:00`;

            result = await api.post('/payments', {
                title: r.title,
                description: r.description || '',
                amount: r.amount,
                expense_category_id: r.expense_category_id || null,
                card_id: r.card_id || null,
                recurrent_id: r.id,
                payment_type: 'recurrent',
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
    document.getElementById('rec-amount').value = r ? formatAmountForInput(r.amount) : '';
    document.getElementById('rec-due-day').value = r?.due_date_day || '';
    document.getElementById('rec-category').value = r?.expense_category_id || '';
    document.getElementById('rec-card').value = r?.card_id || '';
    document.getElementById('rec-period').value = r?.time_period || 'monthly';
    document.getElementById('rec-start').value = r?.start_date || '';
    document.getElementById('rec-end').value = r?.end_date || '';
    document.getElementById('rec-description').value = r?.description || '';

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

    const body = {
        title: document.getElementById('rec-title').value.trim(),
        amount: amountNum,
        due_date_day: dueDay,
        expense_category_id: document.getElementById('rec-category').value || null,
        card_id: document.getElementById('rec-card').value || null,
        time_period: document.getElementById('rec-period').value || 'monthly',
        start_date: document.getElementById('rec-start').value || null,
        end_date: document.getElementById('rec-end').value || null,
        description: document.getElementById('rec-description').value.trim(),
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
            ? `Gasto fijo eliminado (${n} pago${n === 1 ? '' : 's'} asociado${n === 1 ? '' : 's'} eliminado${n === 1 ? '' : 's'})`
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
    loadAll();
    document.getElementById('recurrent-form').addEventListener('submit', submitRecurrentForm);
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (!document.getElementById('recurrent-modal').classList.contains('hidden')) closeRecurrentModal();
        else if (!document.getElementById('recurrent-delete-modal').classList.contains('hidden')) closeRecurrentDelete();
    });
});
</script>
