<!-- Page header (desktop only — mobile topbar shows the page title) -->
<div class="hidden lg:flex items-center justify-between mb-5">
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

<!-- Mobile action row -->
<div class="lg:hidden mb-3">
    <button type="button" onclick="openAccountModal()" class="w-full inline-flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg border border-border text-sm text-dark hover:bg-dark/5 active:scale-95 transition" title="Nueva cuenta">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Nueva cuenta</span>
    </button>
</div>

<!-- KPI strip: total in ARS + per-currency chips -->
<div class="card mb-6" id="totals-card">
    <div class="flex items-baseline justify-between gap-3 mb-1">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Total en ARS</p>
        <span id="totals-fx-stamp" class="text-[10px] text-muted">&nbsp;</span>
    </div>
    <p class="text-3xl lg:text-4xl font-bold tracking-tight" id="totals-ars">
        <span class="skeleton inline-block w-44 h-8">&nbsp;</span>
    </p>
    <div class="flex flex-wrap gap-2 mt-4" id="totals-chips"></div>
</div>

<!-- Carousel -->
<div class="card !p-4" id="accounts-carousel-card">
    <div id="accounts-stage" class="stage">
        <div class="track"></div>
        <div class="nav-arrows">
            <button type="button" data-prev aria-label="Anterior">
                <svg viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6"/></svg>
            </button>
            <button type="button" data-next aria-label="Siguiente">
                <svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
            </button>
        </div>
    </div>

    <!-- Info bar (active account details) -->
    <div class="mt-4 pt-4 border-t border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-2 flex-wrap min-w-0">
            <span id="acc-info-name" class="text-sm font-semibold truncate">—</span>
            <span id="acc-info-type" class="badge badge-muted">—</span>
            <span id="acc-info-currency" class="badge badge-muted font-mono">—</span>
            <span id="acc-info-default" class="badge badge-success hidden">Por defecto</span>
            <span id="acc-info-since" class="text-[11px] text-muted hidden">—</span>
        </div>
        <div class="flex items-center gap-3">
            <div id="acc-info-dots" class="dots"></div>
            <button id="acc-info-edit"   type="button" class="btn btn-ghost text-xs px-2 py-1">Editar</button>
            <button id="acc-info-delete" type="button" class="btn btn-ghost text-xs px-2 py-1 text-danger hover:text-danger">Eliminar</button>
        </div>
    </div>

    <!-- Empty state -->
    <div id="accounts-empty" class="hidden text-center py-8">
        <p class="text-sm text-muted">No tenes cuentas aun.</p>
        <button class="btn btn-outline mt-4" onclick="openAccountModal()">Crear la primera</button>
    </div>
</div>

<!-- ─────────────────────────── Form modal ─────────────────────────── -->
<div id="account-modal" class="fixed inset-0 z-50 hidden bg-dark/40">
    <!-- bottom: var(--keyboard-inset) lifts the sheet above the on-screen keyboard on iOS;
         max-h: 85dvh shrinks the sheet with the visual viewport when the keyboard opens. -->
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-md max-h-[85dvh] sm:max-h-[92vh] overflow-y-auto safe-bottom">
            <!-- Drag handle (mobile only, tap to close) -->
            <button type="button" onclick="closeAccountModal()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
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
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
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

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="account-opening-balance" class="block text-sm font-medium mb-1.5">Saldo inicial</label>
                        <input type="text" id="account-opening-balance" class="input" placeholder="0,00" inputmode="decimal">
                    </div>
                    <div>
                        <label for="account-opening-date" class="block text-sm font-medium mb-1.5">A partir de</label>
                        <input type="date" id="account-opening-date" class="input">
                    </div>
                </div>
                <p class="text-xs text-muted -mt-2">El saldo actual se calcula como saldo inicial + movimientos pagados desde esa fecha.</p>

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
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-sm safe-bottom">
            <!-- Drag handle (mobile only, tap to close) -->
            <button type="button" onclick="closeAccountDelete()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <div class="p-5">
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
</div>

<script>
const ACCOUNT_TYPE_LABEL = { bank: 'Banco', cash: 'Efectivo', crypto: 'Cripto', other: 'Otra' };
const ACCOUNT_TYPE_BADGE = { bank: 'badge-success', cash: 'badge-muted', crypto: 'badge-danger', other: 'badge-muted' };

let accounts = [];
let totals = { ars: 0, by_currency: {} };
let fxRates = { ARS: 1 };
let editingAccountId = null;
let pendingDeleteAccountId = null;

async function loadAccounts() {
    const result = await api.get('/accounts', { totals: 1 });
    if (result && Array.isArray(result.accounts)) {
        accounts = result.accounts;
        totals = result.totals || { ars: 0, by_currency: {} };
        fxRates = result.fx || { ARS: 1 };
    } else {
        accounts = Array.isArray(result) ? result : [];
        totals = { ars: 0, by_currency: {} };
        fxRates = { ARS: 1 };
    }
    renderTotals();
    renderAccounts();
}

function renderTotals() {
    const arsNum = Number(totals.ars || 0);
    const arsEl = document.getElementById('totals-ars');
    arsEl.textContent = (arsNum < 0 ? '−' : '') + formatPrice(Math.abs(arsNum));
    arsEl.classList.toggle('text-danger', arsNum < 0);

    const stamp = document.getElementById('totals-fx-stamp');
    const usdRate = fxRates.USD;
    const usdtRate = fxRates.USDT;
    const parts = [];
    if (usdRate && usdRate !== 1) parts.push(`USD ${formatPrice(usdRate)}`);
    if (usdtRate && usdtRate !== 1) parts.push(`USDT ${formatPrice(usdtRate)}`);
    stamp.textContent = parts.length ? `(${parts.join(' · ')})` : '';

    const chipsEl = document.getElementById('totals-chips');
    chipsEl.textContent = '';
    const order = ['ARS', 'USD', 'USDT'];
    order.forEach(code => {
        const v = totals.by_currency?.[code];
        if (v == null) return;
        const chip = document.createElement('div');
        chip.className = 'flex-1 min-w-[140px] rounded-lg border border-border px-3 py-2 bg-light';
        const label = document.createElement('p');
        label.className = 'text-[10px] font-semibold tracking-wide uppercase text-muted';
        label.textContent = code;
        chip.appendChild(label);
        const val = document.createElement('p');
        const isNeg = Number(v) < 0;
        val.className = `text-base font-bold tabular-nums mt-0.5 ${isNeg ? 'text-danger' : 'text-dark'}`;
        const prefix = code === 'ARS' ? '' : code + ' ';
        val.textContent = (isNeg ? '−' : '') + prefix + formatPrice(Math.abs(Number(v)));
        chip.appendChild(val);
        chipsEl.appendChild(chip);
    });
}

let accountCarousel = null;

function renderAccounts() {
    const carouselCard = document.getElementById('accounts-carousel-card');
    const empty = document.getElementById('accounts-empty');
    const stage = document.getElementById('accounts-stage');

    if (mangosPicker) mangosPicker.setAccounts(accounts);

    if (accounts.length === 0) {
        empty.classList.remove('hidden');
        stage.classList.add('hidden');
        return;
    }
    empty.classList.add('hidden');
    stage.classList.remove('hidden');

    if (!accountCarousel) {
        accountCarousel = mangosCarousel.init(stage, accounts, {
            kind: 'account',
            dotsEl: document.getElementById('acc-info-dots'),
            onChange: updateAccountInfoBar,
        });
    } else {
        accountCarousel.refresh(accounts);
    }
}

function updateAccountInfoBar(_idx, a) {
    const nameEl  = document.getElementById('acc-info-name');
    const typeEl  = document.getElementById('acc-info-type');
    const curEl   = document.getElementById('acc-info-currency');
    const defEl   = document.getElementById('acc-info-default');
    const sinceEl = document.getElementById('acc-info-since');
    const editBtn = document.getElementById('acc-info-edit');
    const delBtn  = document.getElementById('acc-info-delete');

    if (!a) {
        nameEl.textContent = '—';
        typeEl.textContent = '—';
        curEl.textContent = '—';
        defEl.classList.add('hidden');
        sinceEl.classList.add('hidden');
        return;
    }
    nameEl.textContent = a.name;
    typeEl.textContent = ACCOUNT_TYPE_LABEL[a.type] || a.type;
    typeEl.className = 'badge ' + (ACCOUNT_TYPE_BADGE[a.type] || 'badge-muted');
    curEl.textContent = a.currency;
    defEl.classList.toggle('hidden', Number(a.is_default) !== 1);
    if (a.opening_balance_date) {
        sinceEl.textContent = 'Desde ' + a.opening_balance_date;
        sinceEl.classList.remove('hidden');
    } else {
        sinceEl.classList.add('hidden');
    }
    editBtn.onclick = () => openAccountModal(a);
    delBtn.onclick  = () => openAccountDelete(a);
}

function parseBalanceInput(input) {
    if (input == null || input === '') return 0;
    const s = String(input).trim().replace(/\./g, '').replace(',', '.');
    const n = parseFloat(s);
    return isFinite(n) ? n : 0;
}

function formatBalanceForInput(amount) {
    if (amount == null) return '';
    const n = Number(amount);
    if (!isFinite(n) || n === 0) return '';
    return n.toFixed(2).replace('.', ',');
}

function openAccountModal(account) {
    editingAccountId = account?.id || null;
    document.getElementById('account-modal-title').textContent = account ? 'Editar cuenta' : 'Nueva cuenta';

    document.getElementById('account-id').value = account?.id || '';
    document.getElementById('account-name').value = account?.name || '';
    document.getElementById('account-color').value = account?.color || '#D97706';
    document.getElementById('account-default').checked = Number(account?.is_default || 0) === 1;
    document.getElementById('account-opening-balance').value = formatBalanceForInput(account?.opening_balance);
    document.getElementById('account-opening-date').value = account?.opening_balance_date || '';

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

    const openingDate = document.getElementById('account-opening-date').value || null;
    const openingRaw = document.getElementById('account-opening-balance').value;
    const body = {
        name: document.getElementById('account-name').value.trim(),
        type,
        currency,
        color: document.getElementById('account-color').value || null,
        is_default: document.getElementById('account-default').checked ? 1 : 0,
        opening_balance: parseBalanceInput(openingRaw),
        opening_balance_date: openingDate,
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
