<!-- Page header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-semibold">Cuentas</h1>
        <p class="text-sm text-muted mt-1">Tus billeteras: cuentas bancarias, efectivo, cripto</p>
    </div>
    <button class="btn btn-primary" onclick="openAccountModal()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva
    </button>
</div>

<!-- Grid -->
<div id="accounts-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <div class="card"><div class="skeleton h-4 w-20 mb-3">&nbsp;</div><div class="skeleton h-5 w-32 mb-2">&nbsp;</div><div class="skeleton h-4 w-24">&nbsp;</div></div>
    <div class="card"><div class="skeleton h-4 w-20 mb-3">&nbsp;</div><div class="skeleton h-5 w-28 mb-2">&nbsp;</div><div class="skeleton h-4 w-24">&nbsp;</div></div>
</div>

<!-- ─────────────────────────── Form modal ─────────────────────────── -->
<div id="account-modal" class="fixed inset-0 z-50 hidden bg-dark/40 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-border w-full max-w-md">
            <header class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h2 id="account-modal-title" class="text-lg font-semibold">Nueva cuenta</h2>
                <button type="button" onclick="closeAccountModal()" class="text-muted hover:text-dark p-1 -m-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <form id="account-form" class="p-5 space-y-4">
                <input type="hidden" id="account-id">

                <div>
                    <label for="account-name" class="block text-sm font-medium mb-1.5">Nombre <span class="text-danger">*</span></label>
                    <input type="text" id="account-name" class="input" placeholder="Ej: Galicia ahorros" maxlength="100" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">Tipo <span class="text-danger">*</span></label>
                    <div class="grid grid-cols-4 gap-2">
                        <label class="flex items-center justify-center px-2 py-2 rounded-lg border border-border cursor-pointer text-sm transition-colors hover:bg-dark/5 has-[:checked]:bg-accent/10 has-[:checked]:border-accent has-[:checked]:text-accent has-[:checked]:font-medium">
                            <input type="radio" name="account-type" value="bank" class="sr-only" required>
                            Banco
                        </label>
                        <label class="flex items-center justify-center px-2 py-2 rounded-lg border border-border cursor-pointer text-sm transition-colors hover:bg-dark/5 has-[:checked]:bg-accent/10 has-[:checked]:border-accent has-[:checked]:text-accent has-[:checked]:font-medium">
                            <input type="radio" name="account-type" value="cash" class="sr-only">
                            Efectivo
                        </label>
                        <label class="flex items-center justify-center px-2 py-2 rounded-lg border border-border cursor-pointer text-sm transition-colors hover:bg-dark/5 has-[:checked]:bg-accent/10 has-[:checked]:border-accent has-[:checked]:text-accent has-[:checked]:font-medium">
                            <input type="radio" name="account-type" value="crypto" class="sr-only">
                            Cripto
                        </label>
                        <label class="flex items-center justify-center px-2 py-2 rounded-lg border border-border cursor-pointer text-sm transition-colors hover:bg-dark/5 has-[:checked]:bg-accent/10 has-[:checked]:border-accent has-[:checked]:text-accent has-[:checked]:font-medium">
                            <input type="radio" name="account-type" value="other" class="sr-only">
                            Otro
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">Moneda <span class="text-danger">*</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center justify-center px-3 py-2 rounded-lg border border-border cursor-pointer text-sm transition-colors hover:bg-dark/5 has-[:checked]:bg-accent/10 has-[:checked]:border-accent has-[:checked]:text-accent has-[:checked]:font-medium">
                            <input type="radio" name="account-currency" value="ARS" class="sr-only" required>
                            ARS
                        </label>
                        <label class="flex items-center justify-center px-3 py-2 rounded-lg border border-border cursor-pointer text-sm transition-colors hover:bg-dark/5 has-[:checked]:bg-accent/10 has-[:checked]:border-accent has-[:checked]:text-accent has-[:checked]:font-medium">
                            <input type="radio" name="account-currency" value="USD" class="sr-only">
                            USD
                        </label>
                        <label class="flex items-center justify-center px-3 py-2 rounded-lg border border-border cursor-pointer text-sm transition-colors hover:bg-dark/5 has-[:checked]:bg-accent/10 has-[:checked]:border-accent has-[:checked]:text-accent has-[:checked]:font-medium">
                            <input type="radio" name="account-currency" value="USDT" class="sr-only">
                            USDT
                        </label>
                    </div>
                </div>

                <div>
                    <label for="account-color" class="block text-sm font-medium mb-1.5">Color</label>
                    <input type="color" id="account-color" class="h-10 w-full rounded-lg border border-border cursor-pointer" value="#D97706">
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" id="account-default" class="w-4 h-4 rounded border-border text-accent focus:ring-accent/30">
                    <span>Marcar como cuenta por defecto</span>
                </label>

                <p id="account-form-error" class="hidden text-sm text-danger">&nbsp;</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeAccountModal()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" id="account-form-submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Confirm delete ─────────────────────────── -->
<div id="account-delete-modal" class="fixed inset-0 z-50 hidden bg-dark/40">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-border w-full max-w-sm p-5">
            <h2 class="text-lg font-semibold">Eliminar cuenta</h2>
            <p class="text-sm text-muted mt-2">
                Vas a eliminar <span id="account-delete-name" class="font-medium text-dark"></span>.
                Las transacciones asociadas se mantendran pero perderan el vinculo a la cuenta.
            </p>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closeAccountDelete()" class="btn btn-ghost">Cancelar</button>
                <button type="button" onclick="confirmAccountDelete()" id="account-delete-submit" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
const ACCOUNT_TYPE_LABEL = { bank: 'Banco', cash: 'Efectivo', crypto: 'Cripto', other: 'Otra' };
const ACCOUNT_TYPE_BADGE = { bank: 'badge-success', cash: 'badge-muted', crypto: 'badge-danger', other: 'badge-muted' };
const ACCOUNT_SVG_NS = 'http://www.w3.org/2000/svg';

const ICON_EDIT_A = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
const ICON_TRASH_A = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3';

let accounts = [];
let editingAccountId = null;
let pendingDeleteAccountId = null;

function svgIconA(pathD) {
    const svg = document.createElementNS(ACCOUNT_SVG_NS, 'svg');
    svg.setAttribute('class', 'w-4 h-4');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('viewBox', '0 0 24 24');
    const path = document.createElementNS(ACCOUNT_SVG_NS, 'path');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    path.setAttribute('stroke-width', '1.5');
    path.setAttribute('d', pathD);
    svg.appendChild(path);
    return svg;
}

function iconButtonA(pathD, cls, onClick) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `p-1.5 rounded ${cls} hover:bg-dark/5 transition-colors`;
    btn.appendChild(svgIconA(pathD));
    btn.addEventListener('click', onClick);
    return btn;
}

async function loadAccounts() {
    accounts = (await api.get('/accounts')) || [];
    renderAccounts();
}

function renderAccounts() {
    const grid = document.getElementById('accounts-grid');
    grid.textContent = '';

    if (accounts.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'card col-span-full text-center py-12';

        const msg = document.createElement('p');
        msg.className = 'text-sm text-muted';
        msg.textContent = 'No tienes cuentas aun.';
        empty.appendChild(msg);

        const btn = document.createElement('button');
        btn.className = 'btn btn-outline mt-4';
        btn.textContent = 'Crear la primera';
        btn.addEventListener('click', () => openAccountModal());
        empty.appendChild(btn);

        grid.appendChild(empty);
        return;
    }

    accounts.forEach(a => grid.appendChild(buildAccountEl(a)));
}

function buildAccountEl(a) {
    const wrap = document.createElement('div');
    wrap.className = 'card group relative overflow-hidden';

    if (a.color) {
        const stripe = document.createElement('div');
        stripe.className = 'absolute top-0 left-0 right-0 h-1';
        stripe.style.backgroundColor = a.color;
        wrap.appendChild(stripe);
    }

    const head = document.createElement('div');
    head.className = 'flex items-start justify-between mb-3';

    const badges = document.createElement('div');
    badges.className = 'flex items-center gap-2 flex-wrap';

    const typeBadge = document.createElement('span');
    typeBadge.className = `badge ${ACCOUNT_TYPE_BADGE[a.type] || 'badge-muted'}`;
    typeBadge.textContent = ACCOUNT_TYPE_LABEL[a.type] || a.type;
    badges.appendChild(typeBadge);

    const curBadge = document.createElement('span');
    curBadge.className = 'badge badge-muted font-mono';
    curBadge.textContent = a.currency;
    badges.appendChild(curBadge);

    if (Number(a.is_default) === 1) {
        const def = document.createElement('span');
        def.className = 'badge badge-success';
        def.textContent = 'Por defecto';
        badges.appendChild(def);
    }
    head.appendChild(badges);

    const actions = document.createElement('div');
    actions.className = 'flex gap-1 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity';
    actions.appendChild(iconButtonA(ICON_EDIT_A, 'text-muted hover:text-dark', () => openAccountModal(a)));
    actions.appendChild(iconButtonA(ICON_TRASH_A, 'text-muted hover:text-danger', () => openAccountDelete(a)));
    head.appendChild(actions);
    wrap.appendChild(head);

    const name = document.createElement('h3');
    name.className = 'text-base font-semibold truncate';
    name.textContent = a.name;
    wrap.appendChild(name);

    return wrap;
}

function openAccountModal(account) {
    editingAccountId = account?.id || null;
    document.getElementById('account-modal-title').textContent = account ? 'Editar cuenta' : 'Nueva cuenta';

    document.getElementById('account-id').value = account?.id || '';
    document.getElementById('account-name').value = account?.name || '';
    document.getElementById('account-color').value = account?.color || '#D97706';
    document.getElementById('account-default').checked = Number(account?.is_default || 0) === 1;

    document.querySelectorAll('input[name="account-type"]').forEach(r => {
        r.checked = (r.value === (account?.type || 'bank'));
    });
    document.querySelectorAll('input[name="account-currency"]').forEach(r => {
        r.checked = (r.value === (account?.currency || 'ARS'));
    });

    document.getElementById('account-form-error').classList.add('hidden');
    document.getElementById('account-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('account-name').focus(), 50);
}

function closeAccountModal() {
    document.getElementById('account-modal').classList.add('hidden');
    editingAccountId = null;
}

async function submitAccountForm(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('account-form-submit');
    const errorEl = document.getElementById('account-form-error');
    errorEl.classList.add('hidden');
    submitBtn.disabled = true;

    const type = document.querySelector('input[name="account-type"]:checked')?.value;
    const currency = document.querySelector('input[name="account-currency"]:checked')?.value;
    if (!type || !currency) {
        errorEl.textContent = 'Selecciona tipo y moneda';
        errorEl.classList.remove('hidden');
        submitBtn.disabled = false;
        return;
    }

    const body = {
        name: document.getElementById('account-name').value.trim(),
        type,
        currency,
        color: document.getElementById('account-color').value || null,
        is_default: document.getElementById('account-default').checked ? 1 : 0,
    };

    try {
        const result = editingAccountId
            ? await api.put('/accounts', body, { id: editingAccountId })
            : await api.post('/accounts', body);

        if (!result || result.error) {
            errorEl.textContent = result?.error || 'Error al guardar';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            return;
        }

        toast(editingAccountId ? 'Cuenta actualizada' : 'Cuenta creada', 'success');
        closeAccountModal();
        await loadAccounts();
    } catch (err) {
        console.error(err);
        errorEl.textContent = 'Error de red';
        errorEl.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
    }
}

function openAccountDelete(account) {
    pendingDeleteAccountId = account.id;
    document.getElementById('account-delete-name').textContent = account.name;
    document.getElementById('account-delete-modal').classList.remove('hidden');
}

function closeAccountDelete() {
    document.getElementById('account-delete-modal').classList.add('hidden');
    pendingDeleteAccountId = null;
}

async function confirmAccountDelete() {
    if (!pendingDeleteAccountId) return;
    const btn = document.getElementById('account-delete-submit');
    btn.disabled = true;
    try {
        const result = await api.del('/accounts', { id: pendingDeleteAccountId });
        if (!result || result.error) {
            toast(result?.error || 'No se pudo eliminar', 'error');
            return;
        }
        toast('Cuenta eliminada', 'success');
        closeAccountDelete();
        await loadAccounts();
    } catch (err) {
        toast('Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
}

mangosAuth.ready.then(user => {
    if (!user) return;
    loadAccounts();
    document.getElementById('account-form').addEventListener('submit', submitAccountForm);
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (!document.getElementById('account-modal').classList.contains('hidden')) closeAccountModal();
        else if (!document.getElementById('account-delete-modal').classList.contains('hidden')) closeAccountDelete();
    });
});
</script>
