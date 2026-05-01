<!-- Page header (desktop only — mobile topbar shows the page title) -->
<div class="hidden lg:flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold">Categorias</h1>
        <p class="text-sm text-muted mt-1">Clasifica tus gastos para entender en que se va tu plata</p>
    </div>
    <button class="btn btn-primary" onclick="openCategoryModal()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva
    </button>
</div>

<!-- Mobile action row -->
<div class="lg:hidden mb-3">
    <button type="button" onclick="openCategoryModal()" class="w-full inline-flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg border border-border text-sm text-dark hover:bg-dark/5 active:scale-95 transition" title="Nueva categoria">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Nueva categoria</span>
    </button>
</div>

<!-- Grid -->
<div id="categories-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
    <div class="card flex items-center gap-3 py-4"><div class="skeleton h-6 w-6 sm:h-8 sm:w-8 rounded-full">&nbsp;</div><div class="skeleton h-4 w-24">&nbsp;</div></div>
    <div class="card flex items-center gap-3 py-4"><div class="skeleton h-6 w-6 sm:h-8 sm:w-8 rounded-full">&nbsp;</div><div class="skeleton h-4 w-20">&nbsp;</div></div>
    <div class="card flex items-center gap-3 py-4"><div class="skeleton h-6 w-6 sm:h-8 sm:w-8 rounded-full">&nbsp;</div><div class="skeleton h-4 w-28">&nbsp;</div></div>
    <div class="card flex items-center gap-3 py-4"><div class="skeleton h-6 w-6 sm:h-8 sm:w-8 rounded-full">&nbsp;</div><div class="skeleton h-4 w-24">&nbsp;</div></div>
</div>

<!-- ─────────────────────────── Form modal ─────────────────────────── -->
<div id="category-modal" class="fixed inset-0 z-50 hidden bg-dark/40" data-bs-modal="closeCategoryModal">
    <!-- bottom: var(--keyboard-inset) lifts the sheet above the on-screen keyboard on iOS;
         max-h: 85dvh shrinks the sheet with the visual viewport when the keyboard opens. -->
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-md max-h-sheet overflow-y-auto safe-bottom">
            <!-- Drag handle (mobile only, tap to close) -->
            <button type="button" onclick="closeCategoryModal()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <header class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h2 id="category-modal-title" class="text-lg font-semibold">Nueva categoria</h2>
                <button type="button" onclick="closeCategoryModal()" class="text-muted hover:text-dark p-1 -m-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <form id="category-form" class="p-5 space-y-4">
                <input type="hidden" id="category-id">

                <div>
                    <label for="category-name" class="block text-sm font-medium mb-1.5">Nombre <span class="text-danger">*</span></label>
                    <input type="text" id="category-name" class="input" placeholder="Ej: Cafe" maxlength="100" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">Color <span class="text-danger">*</span></label>

                    <!-- Quick palette: tap to set both swatches at once -->
                    <div id="category-palette" class="flex flex-wrap gap-2 mb-3"></div>

                    <div class="flex items-center gap-3">
                        <input type="color" id="category-color" class="h-10 w-16 rounded-lg border border-border cursor-pointer" value="#D97706">
                        <span id="category-color-value" class="text-sm font-mono text-muted">#D97706</span>
                    </div>
                </div>

                <p id="category-form-error" class="hidden text-sm text-danger">&nbsp;</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeCategoryModal()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" id="category-form-submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Confirm delete ─────────────────────────── -->
<div id="category-delete-modal" class="fixed inset-0 z-50 hidden bg-dark/40" data-bs-modal="closeCategoryDelete">
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-sm safe-bottom">
            <!-- Drag handle (mobile only, tap to close) -->
            <button type="button" onclick="closeCategoryDelete()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <div class="p-5">
                <h2 class="text-lg font-semibold">Eliminar categoria</h2>
                <p class="text-sm text-muted mt-2">
                    Vas a eliminar <span id="category-delete-name" class="font-medium text-dark"></span>.
                    Los movimientos que la usaban quedaran sin categoria.
                </p>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" onclick="closeCategoryDelete()" class="btn btn-ghost">Cancelar</button>
                    <button type="button" onclick="confirmCategoryDelete()" id="category-delete-submit" class="btn btn-danger">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const SVG_NS = 'http://www.w3.org/2000/svg';
const ICON_EDIT = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
const ICON_TRASH = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3';

// Curated palette (same hues used by the seeded defaults)
const PALETTE = [
    '#4682B4', '#0072DF', '#1D9A38', '#FF6347', '#E6AE2C', '#6158FF',
    '#E84A8A', '#FF4500', '#DDA0DD', '#3CAEA3', '#800020', '#FF8C00',
    '#9370DB', '#20B2AA', '#FF1493', '#8B4513', '#808080',
];

let categories = [];
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
    btn.className = `p-2.5 sm:p-1.5 rounded ${cls} hover:bg-dark/5 transition-colors`;
    btn.appendChild(svgIcon(pathD));
    btn.addEventListener('click', onClick);
    return btn;
}

// ── Loading & rendering ─────────────────────────────────────────────
async function loadCategories() {
    categories = (await api.get('/categories')) || [];
    renderCategories();
}

function renderCategories() {
    const grid = document.getElementById('categories-grid');
    grid.textContent = '';

    if (categories.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'card col-span-full text-center py-12';

        const msg = document.createElement('p');
        msg.className = 'text-sm text-muted';
        msg.textContent = 'No tienes categorias aun.';
        empty.appendChild(msg);

        const btn = document.createElement('button');
        btn.className = 'btn btn-outline mt-4';
        btn.textContent = 'Crear la primera';
        btn.addEventListener('click', () => openCategoryModal());
        empty.appendChild(btn);

        grid.appendChild(empty);
        return;
    }

    categories.forEach(c => grid.appendChild(buildCategoryEl(c)));
}

function buildCategoryEl(c) {
    const wrap = document.createElement('div');
    wrap.className = 'card group flex items-center gap-3 py-4 px-4';

    const swatch = document.createElement('div');
    swatch.className = 'h-6 w-6 sm:h-9 sm:w-9 rounded-full flex-shrink-0 border border-border';
    swatch.style.backgroundColor = c.color;
    wrap.appendChild(swatch);

    const name = document.createElement('p');
    name.className = 'flex-1 text-sm font-medium truncate';
    name.textContent = c.name;
    wrap.appendChild(name);

    const actions = document.createElement('div');
    actions.className = 'flex gap-0.5 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity';
    actions.appendChild(iconButton(ICON_EDIT, 'text-muted hover:text-dark', () => openCategoryModal(c)));
    actions.appendChild(iconButton(ICON_TRASH, 'text-muted hover:text-danger', () => openCategoryDelete(c)));
    wrap.appendChild(actions);

    // Whole card clickable for editing (when actions aren't hovered)
    wrap.style.cursor = 'pointer';
    wrap.addEventListener('click', e => {
        if (e.target.closest('button')) return;
        openCategoryModal(c);
    });

    return wrap;
}

// ── Modal: form ─────────────────────────────────────────────────────
function buildPalette() {
    const container = document.getElementById('category-palette');
    container.textContent = '';
    PALETTE.forEach(hex => {
        const sw = document.createElement('button');
        sw.type = 'button';
        sw.className = 'h-9 w-9 sm:h-8 sm:w-8 rounded-full border border-border transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-accent/30';
        sw.style.backgroundColor = hex;
        sw.title = hex;
        sw.addEventListener('click', () => setColor(hex));
        container.appendChild(sw);
    });
}

function setColor(hex) {
    document.getElementById('category-color').value = hex;
    document.getElementById('category-color-value').textContent = hex.toUpperCase();
}

function openCategoryModal(category) {
    editingId = category?.id || null;
    document.getElementById('category-modal-title').textContent =
        category ? 'Editar categoria' : 'Nueva categoria';

    document.getElementById('category-id').value = category?.id || '';
    document.getElementById('category-name').value = category?.name || '';
    setColor(category?.color || '#D97706');

    document.getElementById('category-form-error').classList.add('hidden');
    document.getElementById('category-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('category-name').focus(), 50);
}

function closeCategoryModal() {
    document.getElementById('category-modal').classList.add('hidden');
    editingId = null;
}

async function submitCategoryForm(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('category-form-submit');
    const errorEl = document.getElementById('category-form-error');
    errorEl.classList.add('hidden');
    submitBtn.disabled = true;

    const body = {
        name: document.getElementById('category-name').value.trim(),
        color: document.getElementById('category-color').value,
    };

    try {
        const result = editingId
            ? await api.put('/categories', body, { id: editingId })
            : await api.post('/categories', body);

        if (!result || result.error) {
            errorEl.textContent = result?.error || 'Error al guardar';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            return;
        }

        toast(editingId ? 'Categoria actualizada' : 'Categoria creada', 'success');
        closeCategoryModal();
        await loadCategories();
    } catch (err) {
        console.error(err);
        errorEl.textContent = 'Error de red';
        errorEl.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
    }
}

// ── Modal: delete ───────────────────────────────────────────────────
function openCategoryDelete(c) {
    pendingDeleteId = c.id;
    document.getElementById('category-delete-name').textContent = c.name;
    document.getElementById('category-delete-modal').classList.remove('hidden');
}

function closeCategoryDelete() {
    document.getElementById('category-delete-modal').classList.add('hidden');
    pendingDeleteId = null;
}

async function confirmCategoryDelete() {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('category-delete-submit');
    btn.disabled = true;
    try {
        const result = await api.del('/categories', { id: pendingDeleteId });
        if (!result || result.error) {
            toast(result?.error || 'No se pudo eliminar', 'error');
            return;
        }
        toast('Categoria eliminada', 'success');
        closeCategoryDelete();
        await loadCategories();
    } catch (err) {
        toast('Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
}

// ── Init ────────────────────────────────────────────────────────────
mangosAuth.ready.then(user => {
    if (!user) return;
    buildPalette();
    loadCategories();
    document.getElementById('category-form').addEventListener('submit', submitCategoryForm);
    document.getElementById('category-color').addEventListener('input', e => {
        document.getElementById('category-color-value').textContent = e.target.value.toUpperCase();
    });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (!document.getElementById('category-modal').classList.contains('hidden')) closeCategoryModal();
        else if (!document.getElementById('category-delete-modal').classList.contains('hidden')) closeCategoryDelete();
    });
});
</script>
