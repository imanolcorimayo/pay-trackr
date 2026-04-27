<!-- Page header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-semibold">Tarjetas</h1>
        <p class="text-sm text-muted mt-1">Tus tarjetas de credito, debito y virtuales</p>
    </div>
    <button class="btn btn-primary" onclick="openCardModal()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva
    </button>
</div>

<!-- Grid -->
<div id="cards-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <div class="card"><div class="skeleton h-4 w-20 mb-3">&nbsp;</div><div class="skeleton h-5 w-32 mb-2">&nbsp;</div><div class="skeleton h-4 w-24">&nbsp;</div></div>
    <div class="card"><div class="skeleton h-4 w-20 mb-3">&nbsp;</div><div class="skeleton h-5 w-28 mb-2">&nbsp;</div><div class="skeleton h-4 w-24">&nbsp;</div></div>
    <div class="card"><div class="skeleton h-4 w-20 mb-3">&nbsp;</div><div class="skeleton h-5 w-36 mb-2">&nbsp;</div><div class="skeleton h-4 w-24">&nbsp;</div></div>
</div>

<!-- ─────────────────────────── Form modal ─────────────────────────── -->
<div id="card-modal" class="fixed inset-0 z-50 hidden bg-dark/40 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-border w-full max-w-md">
            <header class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h2 id="card-modal-title" class="text-lg font-semibold">Nueva tarjeta</h2>
                <button type="button" onclick="closeCardModal()" class="text-muted hover:text-dark p-1 -m-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <form id="card-form" class="p-5 space-y-4">
                <input type="hidden" id="card-id">

                <div>
                    <label for="card-name" class="block text-sm font-medium mb-1.5">Nombre <span class="text-danger">*</span></label>
                    <input type="text" id="card-name" class="input" placeholder="Ej: Visa Galicia" maxlength="100" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">Tipo <span class="text-danger">*</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center justify-center px-3 py-2 rounded-lg border border-border cursor-pointer text-sm transition-colors hover:bg-dark/5 has-[:checked]:bg-accent/10 has-[:checked]:border-accent has-[:checked]:text-accent has-[:checked]:font-medium">
                            <input type="radio" name="card-type" value="credit" class="sr-only" required>
                            Credito
                        </label>
                        <label class="flex items-center justify-center px-3 py-2 rounded-lg border border-border cursor-pointer text-sm transition-colors hover:bg-dark/5 has-[:checked]:bg-accent/10 has-[:checked]:border-accent has-[:checked]:text-accent has-[:checked]:font-medium">
                            <input type="radio" name="card-type" value="debit" class="sr-only">
                            Debito
                        </label>
                        <label class="flex items-center justify-center px-3 py-2 rounded-lg border border-border cursor-pointer text-sm transition-colors hover:bg-dark/5 has-[:checked]:bg-accent/10 has-[:checked]:border-accent has-[:checked]:text-accent has-[:checked]:font-medium">
                            <input type="radio" name="card-type" value="virtual" class="sr-only">
                            Virtual
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="card-bank" class="block text-sm font-medium mb-1.5">Banco</label>
                        <input type="text" id="card-bank" class="input" placeholder="Galicia" maxlength="100">
                    </div>
                    <div>
                        <label for="card-last-four" class="block text-sm font-medium mb-1.5">Ultimos 4</label>
                        <input type="text" id="card-last-four" class="input font-mono" placeholder="1234" maxlength="4" inputmode="numeric" pattern="[0-9]{0,4}">
                    </div>
                </div>

                <div id="credit-fields" class="grid grid-cols-2 gap-3 hidden">
                    <div>
                        <label for="card-closing-day" class="block text-sm font-medium mb-1.5">Cierre</label>
                        <input type="number" id="card-closing-day" class="input" min="1" max="31" placeholder="dia">
                    </div>
                    <div>
                        <label for="card-due-day" class="block text-sm font-medium mb-1.5">Vencimiento</label>
                        <input type="number" id="card-due-day" class="input" min="1" max="31" placeholder="dia">
                    </div>
                </div>

                <div>
                    <label for="card-color" class="block text-sm font-medium mb-1.5">Color</label>
                    <input type="color" id="card-color" class="h-10 w-full rounded-lg border border-border cursor-pointer" value="#D97706">
                </div>

                <p id="card-form-error" class="hidden text-sm text-danger">&nbsp;</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeCardModal()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" id="card-form-submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Confirm delete ─────────────────────────── -->
<div id="card-delete-modal" class="fixed inset-0 z-50 hidden bg-dark/40">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-border w-full max-w-sm p-5">
            <h2 class="text-lg font-semibold">Eliminar tarjeta</h2>
            <p class="text-sm text-muted mt-2">
                Vas a eliminar <span id="card-delete-name" class="font-medium text-dark"></span>.
                Esta accion no se puede deshacer.
            </p>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closeCardDelete()" class="btn btn-ghost">Cancelar</button>
                <button type="button" onclick="confirmCardDelete()" id="card-delete-submit" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
const TYPE_LABEL = { credit: 'Credito', debit: 'Debito', virtual: 'Virtual' };
const TYPE_BADGE = { credit: 'badge-success', debit: 'badge-muted', virtual: 'badge-danger' };
const SVG_NS = 'http://www.w3.org/2000/svg';

const ICON_EDIT = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
const ICON_TRASH = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3';

let cards = [];
let editingId = null;
let pendingDeleteId = null;

// ── Helpers ─────────────────────────────────────────────────────────
function svgIcon(pathD) {
    const svg = document.createElementNS(SVG_NS, 'svg');
    svg.setAttribute('class', 'w-4 h-4');
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
    btn.addEventListener('click', onClick);
    return btn;
}

// ── Loading & rendering ─────────────────────────────────────────────
async function loadCards() {
    cards = (await api.get('/cards')) || [];
    renderCards();
}

function renderCards() {
    const grid = document.getElementById('cards-grid');
    grid.textContent = '';

    if (cards.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'card col-span-full text-center py-12';

        const msg = document.createElement('p');
        msg.className = 'text-sm text-muted';
        msg.textContent = 'No tienes tarjetas aun.';
        empty.appendChild(msg);

        const btn = document.createElement('button');
        btn.className = 'btn btn-outline mt-4';
        btn.textContent = 'Crear la primera';
        btn.addEventListener('click', () => openCardModal());
        empty.appendChild(btn);

        grid.appendChild(empty);
        return;
    }

    cards.forEach(card => grid.appendChild(buildCardEl(card)));
}

function buildCardEl(c) {
    const wrap = document.createElement('div');
    wrap.className = 'card group relative overflow-hidden';

    if (c.color) {
        const stripe = document.createElement('div');
        stripe.className = 'absolute top-0 left-0 right-0 h-1';
        stripe.style.backgroundColor = c.color;
        wrap.appendChild(stripe);
    }

    const head = document.createElement('div');
    head.className = 'flex items-start justify-between mb-3';

    const badge = document.createElement('span');
    badge.className = `badge ${TYPE_BADGE[c.type] || 'badge-muted'}`;
    badge.textContent = TYPE_LABEL[c.type] || c.type;
    head.appendChild(badge);

    const actions = document.createElement('div');
    actions.className = 'flex gap-1 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity';
    actions.appendChild(iconButton(ICON_EDIT, 'text-muted hover:text-dark', () => openCardModal(c)));
    actions.appendChild(iconButton(ICON_TRASH, 'text-muted hover:text-danger', () => openCardDelete(c)));
    head.appendChild(actions);
    wrap.appendChild(head);

    const name = document.createElement('h3');
    name.className = 'text-base font-semibold truncate';
    name.textContent = c.name;
    wrap.appendChild(name);

    const bank = document.createElement('p');
    bank.className = 'text-sm text-muted mt-0.5 truncate';
    bank.textContent = c.bank || ' ';
    wrap.appendChild(bank);

    const foot = document.createElement('div');
    foot.className = 'mt-4 flex items-center justify-between text-xs';

    const lf = document.createElement('span');
    lf.className = 'font-mono text-muted';
    lf.textContent = '•••• ' + (c.last_four || '————');
    foot.appendChild(lf);

    if (c.type === 'credit' && (c.closing_day || c.due_day)) {
        const days = document.createElement('span');
        days.className = 'text-muted';
        const parts = [];
        if (c.closing_day) parts.push(`cierre ${c.closing_day}`);
        if (c.due_day) parts.push(`vence ${c.due_day}`);
        days.textContent = parts.join(' · ');
        foot.appendChild(days);
    }

    wrap.appendChild(foot);
    return wrap;
}

// ── Modal: form ─────────────────────────────────────────────────────
function openCardModal(card) {
    editingId = card?.id || null;
    document.getElementById('card-modal-title').textContent = card ? 'Editar tarjeta' : 'Nueva tarjeta';

    document.getElementById('card-id').value = card?.id || '';
    document.getElementById('card-name').value = card?.name || '';
    document.getElementById('card-bank').value = card?.bank || '';
    document.getElementById('card-last-four').value = card?.last_four || '';
    document.getElementById('card-color').value = card?.color || '#D97706';
    document.getElementById('card-closing-day').value = card?.closing_day || '';
    document.getElementById('card-due-day').value = card?.due_day || '';

    document.querySelectorAll('input[name="card-type"]').forEach(r => {
        r.checked = (r.value === (card?.type || 'credit'));
    });

    updateCreditFieldsVisibility();
    document.getElementById('card-form-error').classList.add('hidden');
    document.getElementById('card-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('card-name').focus(), 50);
}

function closeCardModal() {
    document.getElementById('card-modal').classList.add('hidden');
    editingId = null;
}

function updateCreditFieldsVisibility() {
    const type = document.querySelector('input[name="card-type"]:checked')?.value;
    document.getElementById('credit-fields').classList.toggle('hidden', type !== 'credit');
}

async function submitCardForm(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('card-form-submit');
    const errorEl = document.getElementById('card-form-error');
    errorEl.classList.add('hidden');
    submitBtn.disabled = true;

    const type = document.querySelector('input[name="card-type"]:checked')?.value;
    if (!type) {
        errorEl.textContent = 'Selecciona el tipo';
        errorEl.classList.remove('hidden');
        submitBtn.disabled = false;
        return;
    }

    const body = {
        name: document.getElementById('card-name').value.trim(),
        type,
        bank: document.getElementById('card-bank').value.trim() || null,
        last_four: document.getElementById('card-last-four').value.trim() || null,
        color: document.getElementById('card-color').value || null,
    };

    if (type === 'credit') {
        const cd = document.getElementById('card-closing-day').value;
        const dd = document.getElementById('card-due-day').value;
        body.closing_day = cd ? parseInt(cd, 10) : null;
        body.due_day = dd ? parseInt(dd, 10) : null;
    } else {
        body.closing_day = null;
        body.due_day = null;
    }

    try {
        const result = editingId
            ? await api.put('/cards', body, { id: editingId })
            : await api.post('/cards', body);

        if (!result || result.error) {
            errorEl.textContent = result?.error || 'Error al guardar';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            return;
        }

        toast(editingId ? 'Tarjeta actualizada' : 'Tarjeta creada', 'success');
        closeCardModal();
        await loadCards();
    } catch (err) {
        console.error(err);
        errorEl.textContent = 'Error de red';
        errorEl.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
    }
}

// ── Modal: delete confirm ───────────────────────────────────────────
function openCardDelete(card) {
    pendingDeleteId = card.id;
    document.getElementById('card-delete-name').textContent = card.name;
    document.getElementById('card-delete-modal').classList.remove('hidden');
}

function closeCardDelete() {
    document.getElementById('card-delete-modal').classList.add('hidden');
    pendingDeleteId = null;
}

async function confirmCardDelete() {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('card-delete-submit');
    btn.disabled = true;
    try {
        const result = await api.del('/cards', { id: pendingDeleteId });
        if (!result || result.error) {
            toast(result?.error || 'No se pudo eliminar', 'error');
            return;
        }
        toast('Tarjeta eliminada', 'success');
        closeCardDelete();
        await loadCards();
    } catch (err) {
        toast('Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
}

// ── Init ────────────────────────────────────────────────────────────
mangosAuth.ready.then(user => {
    if (!user) return;
    loadCards();
    document.getElementById('card-form').addEventListener('submit', submitCardForm);
    document.querySelectorAll('input[name="card-type"]').forEach(r => {
        r.addEventListener('change', updateCreditFieldsVisibility);
    });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (!document.getElementById('card-modal').classList.contains('hidden')) closeCardModal();
        else if (!document.getElementById('card-delete-modal').classList.contains('hidden')) closeCardDelete();
    });
});
</script>
