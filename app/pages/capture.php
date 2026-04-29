<!-- Page header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-semibold">Capturar</h1>
        <p class="text-sm text-muted mt-1">Sube capturas de tus transacciones y la IA arma los movimientos por vos</p>
    </div>
    <a href="/movimientos" class="btn btn-ghost">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
    </a>
</div>

<!-- Step 1: Upload zone -->
<section id="upload-section" class="card mb-6">
    <div id="drop-zone" class="border-2 border-dashed border-border rounded-lg p-8 text-center cursor-pointer hover:border-accent hover:bg-accent/5 transition-colors">
        <svg class="w-12 h-12 mx-auto text-muted mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
        <p class="text-sm font-medium text-dark">Arrastra capturas o haz click para elegir</p>
        <p class="text-xs text-muted mt-1">PNG, JPG · hasta 10 imagenes · max 6MB total</p>
        <input type="file" id="file-input" accept="image/*" multiple class="hidden">
    </div>

    <div id="thumbs" class="hidden mt-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2"></div>

    <div id="upload-actions" class="hidden mt-4 flex justify-between items-center">
        <p id="upload-summary" class="text-sm text-muted"></p>
        <div class="flex gap-2">
            <button type="button" id="clear-images" class="btn btn-ghost">Limpiar</button>
            <button type="button" id="analyze-btn" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Analizar
            </button>
        </div>
    </div>
</section>

<!-- Loading -->
<section id="loading-section" class="hidden card mb-6">
    <div class="flex items-center gap-4 py-8 px-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-accent"></div>
        <div>
            <p class="text-sm font-medium">Analizando capturas...</p>
            <p class="text-xs text-muted mt-0.5">Esto puede tardar unos segundos</p>
        </div>
    </div>
</section>

<!-- Review -->
<section id="review-section" class="hidden">
    <div id="warnings"></div>

    <div class="bg-white border border-border rounded-xl mb-4 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-border">
            <h2 class="text-sm font-semibold">Revisar y confirmar</h2>
            <button type="button" id="discard-btn" class="text-xs text-muted hover:text-danger">Descartar</button>
        </div>

        <div id="drafts-list" class="divide-y divide-border"></div>
    </div>

    <div class="sticky bottom-3 z-10">
        <div class="bg-white border border-border rounded-xl shadow-lg px-4 py-2.5 flex items-center justify-between gap-3 flex-wrap">
            <div class="text-xs sm:text-sm">
                <span id="footer-summary" class="text-muted">--</span>
            </div>
            <button type="button" id="confirm-btn" class="btn btn-primary py-2 px-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Confirmar
            </button>
        </div>
    </div>
</section>

<script>
const SVG_NS = 'http://www.w3.org/2000/svg';
const STORAGE_KEY = 'mangos.capture.drafts';

let images = [];
let drafts = [];
let categories = [];
let recurrents = [];
let cards = [];

// ── Helpers ─────────────────────────────────────────────
function parseAmount(input) {
    if (input == null) return NaN;
    const s = String(input).trim().replace(/\./g, '').replace(',', '.');
    return parseFloat(s);
}
function formatAmountForInput(amount) {
    if (amount == null) return '';
    const n = Number(amount);
    if (!isFinite(n)) return '';
    return n.toFixed(2).replace('.', ',');
}
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            const dataUrl = reader.result;
            const base64 = dataUrl.split(',', 2)[1] || '';
            resolve({ dataUrl, base64 });
        };
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}
function svgIconEl(pathD, sizeCls) {
    const svg = document.createElementNS(SVG_NS, 'svg');
    svg.setAttribute('class', sizeCls || 'w-3.5 h-3.5');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('viewBox', '0 0 24 24');
    const p = document.createElementNS(SVG_NS, 'path');
    p.setAttribute('stroke-linecap', 'round');
    p.setAttribute('stroke-linejoin', 'round');
    p.setAttribute('stroke-width', '2');
    p.setAttribute('d', pathD);
    svg.appendChild(p);
    return svg;
}

// ── Image upload ────────────────────────────────────────
async function addFiles(fileList) {
    for (const file of fileList) {
        if (!file.type.startsWith('image/')) continue;
        if (images.length >= 10) {
            toast('Maximo 10 imagenes', 'error');
            break;
        }
        try {
            const { dataUrl, base64 } = await fileToBase64(file);
            images.push({ name: file.name, mimeType: file.type, dataUrl, base64 });
        } catch (e) {
            console.error(e);
        }
    }
    renderThumbs();
}
function clearImages() {
    images = [];
    renderThumbs();
}
function renderThumbs() {
    const thumbs = document.getElementById('thumbs');
    const actions = document.getElementById('upload-actions');
    thumbs.textContent = '';

    if (images.length === 0) {
        thumbs.classList.add('hidden');
        actions.classList.add('hidden');
        return;
    }

    images.forEach((img, idx) => {
        const wrap = document.createElement('div');
        wrap.className = 'relative group rounded-lg overflow-hidden border border-border aspect-square';

        const el = document.createElement('img');
        el.src = img.dataUrl;
        el.className = 'w-full h-full object-cover';
        el.alt = img.name;
        wrap.appendChild(el);

        const idxLabel = document.createElement('span');
        idxLabel.className = 'absolute top-1 left-1 bg-dark/70 text-white text-xs px-1.5 py-0.5 rounded';
        idxLabel.textContent = idx;
        wrap.appendChild(idxLabel);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'absolute top-1 right-1 bg-danger text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity';
        remove.textContent = '×';
        remove.setAttribute('aria-label', 'Quitar');
        remove.addEventListener('click', () => {
            images.splice(idx, 1);
            renderThumbs();
        });
        wrap.appendChild(remove);

        thumbs.appendChild(wrap);
    });

    thumbs.classList.remove('hidden');
    actions.classList.remove('hidden');

    const totalKb = Math.round(images.reduce((s, i) => s + i.base64.length, 0) / 1024);
    document.getElementById('upload-summary').textContent =
        `${images.length} imagen${images.length === 1 ? '' : 'es'} · ~${totalKb} KB en base64`;
}

// ── Drag & drop ─────────────────────────────────────────
function setupDropZone() {
    const zone = document.getElementById('drop-zone');
    const input = document.getElementById('file-input');

    zone.addEventListener('click', () => input.click());
    input.addEventListener('change', e => {
        addFiles(Array.from(e.target.files));
        input.value = '';
    });

    ['dragenter', 'dragover'].forEach(ev => {
        zone.addEventListener(ev, e => {
            e.preventDefault();
            zone.classList.add('border-accent', 'bg-accent/5');
        });
    });
    ['dragleave', 'drop'].forEach(ev => {
        zone.addEventListener(ev, e => {
            e.preventDefault();
            zone.classList.remove('border-accent', 'bg-accent/5');
        });
    });
    zone.addEventListener('drop', e => {
        e.preventDefault();
        if (e.dataTransfer?.files?.length) addFiles(Array.from(e.dataTransfer.files));
    });

    document.addEventListener('paste', e => {
        const items = e.clipboardData?.items || [];
        const files = [];
        for (const it of items) {
            if (it.type.startsWith('image/')) {
                const f = it.getAsFile();
                if (f) files.push(f);
            }
        }
        if (files.length) addFiles(files);
    });
}

// ── Analyze ─────────────────────────────────────────────
async function analyze() {
    if (images.length === 0) return;

    const total = images.reduce((s, i) => s + i.base64.length, 0);
    if (total > 6 * 1024 * 1024) {
        toast('Total excede 6MB. Quita alguna imagen', 'error');
        return;
    }

    document.getElementById('upload-section').classList.add('hidden');
    document.getElementById('loading-section').classList.remove('hidden');

    const payload = {
        images: images.map(i => ({ mimeType: i.mimeType, data: i.base64 }))
    };

    let result;
    try {
        result = await api.post('/ai/parse-transactions', payload);
    } catch (e) {
        console.error(e);
        toast('Error de red', 'error');
        backToUpload();
        return;
    }

    document.getElementById('loading-section').classList.add('hidden');

    if (!result || result.error) {
        toast(result?.error || 'No se pudieron analizar las capturas', 'error');
        backToUpload();
        return;
    }

    drafts = (result.drafts || []).map(d => ({
        ...d,
        amount: Math.abs(Number(d.amount) || 0),
        date: d.date || '',
        title: d.title || '',
        description: d.description || '',
        suggested_category_id: d.suggested_category_id || null,
        card_id: null,
        action: defaultActionFor(d),
        update_recurrent_amount: shouldUpdateRecurrentAmount(d),
        showOptions: false,
    }));

    persistDrafts(result.unreadable_screenshot_idxs || []);
    renderReview(result.unreadable_screenshot_idxs || []);
}

function defaultActionFor(d) {
    if (d.duplicate_in_batch_idx != null) return 'skip';
    if (d.existing_transaction_id) return 'skip';
    if (d.recurrent_match_id) return 'mark_recurrent_paid';
    return 'create';
}
// When the user explicitly clicks "Incluir", honor their override even when
// Gemini said this looks like a duplicate or an already-existing payment.
function nonSkipActionFor(d) {
    if (d.recurrent_match_id) return 'mark_recurrent_paid';
    return 'create';
}

function shouldUpdateRecurrentAmount(d) {
    if (!d.recurrent_match_id) return false;
    const r = recurrents.find(x => x.id === d.recurrent_match_id);
    if (!r) return false;
    const diff = Math.abs(Math.abs(Number(d.amount)) - Math.abs(Number(r.amount)));
    return diff > 0.01;
}

function backToUpload() {
    document.getElementById('upload-section').classList.remove('hidden');
    document.getElementById('loading-section').classList.add('hidden');
    document.getElementById('review-section').classList.add('hidden');
}

// ── Render review ───────────────────────────────────────
function renderReview(unreadableIdxs) {
    document.getElementById('upload-section').classList.add('hidden');
    document.getElementById('review-section').classList.remove('hidden');

    const warnEl = document.getElementById('warnings');
    warnEl.textContent = '';
    if (unreadableIdxs.length > 0) {
        const w = document.createElement('div');
        w.className = 'card mb-4 bg-accent/10 border-accent/30';
        const p = document.createElement('p');
        p.className = 'text-sm text-accent';
        p.textContent = `${unreadableIdxs.length} captura(s) no se pudieron leer (indices: ${unreadableIdxs.join(', ')})`;
        w.appendChild(p);
        warnEl.appendChild(w);
    }

    const list = document.getElementById('drafts-list');
    list.textContent = '';

    if (drafts.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'text-sm text-muted text-center py-8';
        empty.textContent = 'No se detectaron transacciones de gasto en las capturas.';
        list.appendChild(empty);
    } else {
        drafts.forEach((d, idx) => list.appendChild(buildDraftRow(d, idx)));
    }

    updateFooterSummary();
}

function statusInfo(d) {
    if (d.duplicate_in_batch_idx != null) {
        return { dot: 'bg-muted', text: `Dup. #${d.duplicate_in_batch_idx}`, tone: 'text-muted' };
    }
    if (d.existing_transaction_id) {
        return { dot: 'bg-muted', text: 'Ya existe', tone: 'text-muted' };
    }
    if (d.recurrent_match_id) {
        const r = recurrents.find(x => x.id === d.recurrent_match_id);
        return {
            dot: 'bg-blue-500',
            text: r?.title ? `Fijo: ${r.title}` : 'Fijo',
            tone: 'text-blue-700',
        };
    }
    return { dot: 'bg-green-500', text: 'Nueva', tone: 'text-green-700' };
}

function buildDraftRow(d, idx) {
    const row = document.createElement('div');
    row.className = 'px-3 py-2 sm:px-4 sm:py-2.5 hover:bg-dark/[0.02]';
    row.dataset.idx = idx;
    if (d.action === 'skip') row.classList.add('opacity-50');

    const status = statusInfo(d);

    // ── First row: include checkbox + title + amount + date + cat + more
    const main = document.createElement('div');
    main.className = 'flex items-center gap-2 sm:gap-2.5';

    // Include toggle (checkbox)
    const cbWrap = document.createElement('label');
    cbWrap.className = 'flex-shrink-0 cursor-pointer p-1 -m-1';
    cbWrap.title = d.action === 'skip' ? 'Incluir' : 'Saltar';
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.className = 'w-4 h-4 rounded border-border accent-accent';
    cb.checked = d.action !== 'skip';
    cb.addEventListener('change', () => {
        d.action = cb.checked ? nonSkipActionFor(d) : 'skip';
        renderReview([]);
    });
    cbWrap.appendChild(cb);
    main.appendChild(cbWrap);

    // Status dot
    const dot = document.createElement('span');
    dot.className = `flex-shrink-0 w-2 h-2 rounded-full ${status.dot}`;
    dot.title = status.text;
    main.appendChild(dot);

    // Mobile: stack title + grid below. Desktop: all inline.
    const fields = document.createElement('div');
    fields.className = 'flex-1 min-w-0 grid grid-cols-12 gap-2 items-center';

    // Title — full width on mobile (col-12), 5 on desktop
    const titleInp = document.createElement('input');
    titleInp.type = 'text';
    titleInp.className = 'input-sm col-span-12 sm:col-span-5';
    titleInp.value = d.title;
    titleInp.maxLength = 100;
    titleInp.addEventListener('input', () => { d.title = titleInp.value; });
    fields.appendChild(titleInp);

    // Amount — col-5 mobile, 2 desktop
    const amtInp = document.createElement('input');
    amtInp.type = 'text';
    amtInp.className = 'input-sm font-mono col-span-5 sm:col-span-2 text-right';
    amtInp.value = formatAmountForInput(Math.abs(d.amount));
    amtInp.inputMode = 'decimal';
    amtInp.addEventListener('input', () => {
        d.amount = Math.abs(parseAmount(amtInp.value) || 0);
        updateFooterSummary();
    });
    fields.appendChild(amtInp);

    // Date — col-4 mobile, 2 desktop
    const dateInp = document.createElement('input');
    dateInp.type = 'date';
    dateInp.className = 'input-sm col-span-4 sm:col-span-2';
    dateInp.value = d.date;
    dateInp.addEventListener('input', () => { d.date = dateInp.value; });
    fields.appendChild(dateInp);

    // Category — col-3 mobile (icon-ish), 3 desktop
    const catSel = document.createElement('select');
    catSel.className = 'input-sm col-span-3 sm:col-span-3';
    const catEmpty = document.createElement('option');
    catEmpty.value = '';
    catEmpty.textContent = '—';
    catSel.appendChild(catEmpty);
    categories.forEach(c => {
        const o = document.createElement('option');
        o.value = c.id;
        o.textContent = c.name;
        if (c.id === d.suggested_category_id) o.selected = true;
        catSel.appendChild(o);
    });
    catSel.addEventListener('change', () => { d.suggested_category_id = catSel.value || null; });
    fields.appendChild(catSel);

    main.appendChild(fields);

    // More options chevron
    const moreBtn = document.createElement('button');
    moreBtn.type = 'button';
    moreBtn.className = 'flex-shrink-0 p-1.5 rounded text-muted hover:text-dark hover:bg-dark/5 transition-colors';
    moreBtn.title = d.showOptions ? 'Ocultar opciones' : 'Mas opciones';
    const chevron = document.createElementNS(SVG_NS, 'svg');
    chevron.setAttribute('class', 'w-4 h-4 transition-transform' + (d.showOptions ? ' rotate-180' : ''));
    chevron.setAttribute('fill', 'none');
    chevron.setAttribute('stroke', 'currentColor');
    chevron.setAttribute('viewBox', '0 0 24 24');
    const chPath = document.createElementNS(SVG_NS, 'path');
    chPath.setAttribute('stroke-linecap', 'round');
    chPath.setAttribute('stroke-linejoin', 'round');
    chPath.setAttribute('stroke-width', '2');
    chPath.setAttribute('d', 'M19 9l-7 7-7-7');
    chevron.appendChild(chPath);
    moreBtn.appendChild(chevron);
    moreBtn.addEventListener('click', () => {
        d.showOptions = !d.showOptions;
        renderReview([]);
    });
    main.appendChild(moreBtn);

    row.appendChild(main);

    // ── Second row: status subtitle (only when interesting) + recurrent diff hint
    const subtitleParts = [];
    if (status.text !== 'Nueva') subtitleParts.push(status.text);
    if (d.screenshot_idx != null) subtitleParts.push(`captura #${d.screenshot_idx}`);

    const recurrentDiff = (() => {
        if (!d.recurrent_match_id || d.action !== 'mark_recurrent_paid') return null;
        const r = recurrents.find(x => x.id === d.recurrent_match_id);
        if (!r) return null;
        const diff = Math.abs(Math.abs(Number(d.amount)) - Math.abs(Number(r.amount)));
        return diff > 0.01 ? r : null;
    })();

    if (subtitleParts.length || recurrentDiff) {
        const sub = document.createElement('div');
        sub.className = 'flex items-center gap-3 text-xs text-muted mt-1 pl-12 sm:pl-12 flex-wrap';

        if (subtitleParts.length) {
            const t = document.createElement('span');
            t.className = status.tone;
            t.textContent = subtitleParts.join(' · ');
            sub.appendChild(t);
        }

        if (recurrentDiff) {
            const lbl = document.createElement('label');
            lbl.className = 'flex items-center gap-1.5 cursor-pointer';
            const ucb = document.createElement('input');
            ucb.type = 'checkbox';
            ucb.className = 'w-3.5 h-3.5 rounded border-border accent-accent';
            ucb.checked = !!d.update_recurrent_amount;
            ucb.addEventListener('change', () => { d.update_recurrent_amount = ucb.checked; });
            lbl.appendChild(ucb);
            const t = document.createElement('span');
            t.textContent = `Actualizar fijo: ${formatPrice(Math.abs(recurrentDiff.amount))} → ${formatPrice(Math.abs(d.amount))}`;
            lbl.appendChild(t);
            sub.appendChild(lbl);
        }

        row.appendChild(sub);
    }

    // ── Expanded panel (Más opciones)
    if (d.showOptions) {
        const more = document.createElement('div');
        more.className = 'mt-2 pl-12 grid grid-cols-12 gap-2 pb-1';

        // Vincular con fijo
        const recWrap = document.createElement('div');
        recWrap.className = 'col-span-12 sm:col-span-6';
        const recLab = document.createElement('label');
        recLab.className = 'block text-[11px] font-medium text-muted mb-0.5';
        recLab.textContent = 'Vincular con fijo';
        recWrap.appendChild(recLab);
        const recSel = document.createElement('select');
        recSel.className = 'input-sm';
        const recEmpty = document.createElement('option');
        recEmpty.value = '';
        recEmpty.textContent = '— ninguno —';
        recSel.appendChild(recEmpty);
        recurrents.forEach(r => {
            const o = document.createElement('option');
            o.value = r.id;
            o.textContent = `${r.title} (${formatPrice(Math.abs(r.amount))})`;
            if (r.id === d.recurrent_match_id) o.selected = true;
            recSel.appendChild(o);
        });
        recSel.addEventListener('change', () => {
            const rid = recSel.value || null;
            d.recurrent_match_id = rid;
            if (rid) {
                d.recurrent_match_confidence = d.recurrent_match_confidence || 'medium';
                d.update_recurrent_amount = shouldUpdateRecurrentAmount(d);
                if (d.action !== 'skip') d.action = 'mark_recurrent_paid';
            } else {
                d.update_recurrent_amount = false;
                if (d.action === 'mark_recurrent_paid') d.action = 'create';
            }
            renderReview([]);
        });
        recWrap.appendChild(recSel);
        more.appendChild(recWrap);

        // Tarjeta
        const cardWrap = document.createElement('div');
        cardWrap.className = 'col-span-12 sm:col-span-6';
        const cardLab = document.createElement('label');
        cardLab.className = 'block text-[11px] font-medium text-muted mb-0.5';
        cardLab.textContent = 'Tarjeta';
        cardWrap.appendChild(cardLab);
        const cardSel = document.createElement('select');
        cardSel.className = 'input-sm';
        const cardEmpty = document.createElement('option');
        cardEmpty.value = '';
        cardEmpty.textContent = '—';
        cardSel.appendChild(cardEmpty);
        cards.forEach(c => {
            const o = document.createElement('option');
            o.value = c.id;
            o.textContent = c.name;
            if (c.id === d.card_id) o.selected = true;
            cardSel.appendChild(o);
        });
        cardSel.addEventListener('change', () => { d.card_id = cardSel.value || null; });
        cardWrap.appendChild(cardSel);
        more.appendChild(cardWrap);

        // Descripcion
        const descWrap = document.createElement('div');
        descWrap.className = 'col-span-12';
        const descLab = document.createElement('label');
        descLab.className = 'block text-[11px] font-medium text-muted mb-0.5';
        descLab.textContent = 'Descripcion';
        descWrap.appendChild(descLab);
        const descInp = document.createElement('input');
        descInp.type = 'text';
        descInp.className = 'input-sm';
        descInp.value = d.description || '';
        descInp.maxLength = 500;
        descInp.addEventListener('input', () => { d.description = descInp.value; });
        descWrap.appendChild(descInp);
        more.appendChild(descWrap);

        row.appendChild(more);
    }

    return row;
}

function updateFooterSummary() {
    const create = drafts.filter(d => d.action === 'create');
    const markPaid = drafts.filter(d => d.action === 'mark_recurrent_paid');
    const skip = drafts.filter(d => d.action === 'skip');
    const total = [...create, ...markPaid].reduce((s, d) => s + Math.abs(Number(d.amount) || 0), 0);

    document.getElementById('footer-summary').textContent =
        `${create.length} nuevos · ${markPaid.length} fijos pagados · ${skip.length} saltados · Total ${formatPrice(total)}`;
}

// ── Persist drafts (localStorage) ───────────────────────
function persistDrafts(unreadable) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            ts: Date.now(),
            drafts,
            unreadable,
        }));
    } catch (e) { /* quota exceeded — ignore */ }
}
function loadPersistedDrafts() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (Date.now() - parsed.ts > 60 * 60 * 1000) {
            localStorage.removeItem(STORAGE_KEY);
            return null;
        }
        return parsed;
    } catch (e) { return null; }
}
function clearPersistedDrafts() {
    localStorage.removeItem(STORAGE_KEY);
}

// ── Confirm ─────────────────────────────────────────────
async function commit() {
    const rows = drafts.map(d => {
        if (d.action === 'skip') return { action: 'skip' };

        const paid_ts = (d.date ? `${d.date} 00:00:00` : null);

        if (d.action === 'mark_recurrent_paid') {
            return {
                action: 'mark_recurrent_paid',
                recurrent_id: d.recurrent_match_id,
                amount: Number(d.amount) || 0,
                paid_ts,
                update_recurrent_amount: !!d.update_recurrent_amount,
            };
        }
        return {
            action: 'create',
            title: d.title,
            description: d.description || '',
            amount: Number(d.amount) || 0,
            expense_category_id: d.suggested_category_id,
            card_id: d.card_id,
            transaction_type: 'one-time',
            is_paid: true,
            paid_ts,
            due_ts: paid_ts,
        };
    });

    const btn = document.getElementById('confirm-btn');
    btn.disabled = true;

    let result;
    try {
        result = await api.post('/ai/commit-transactions', { rows });
    } catch (e) {
        console.error(e);
        toast('Error de red', 'error');
        btn.disabled = false;
        return;
    }

    if (!result || result.error) {
        toast(result?.error || 'No se pudo confirmar', 'error');
        btn.disabled = false;
        return;
    }

    clearPersistedDrafts();

    const monthStr = new Date().toISOString().slice(0, 7);
    toast(`${result.created} creados, ${result.marked_paid} fijos pagados, ${result.skipped} saltados`, 'success');
    setTimeout(() => { window.location.href = `/movimientos?month=${monthStr}`; }, 600);
}

function discard() {
    if (!window.confirm('Vas a descartar la revision actual. Continuar?')) return;
    drafts = [];
    images = [];
    clearPersistedDrafts();
    backToUpload();
    renderThumbs();
}

// ── Init ────────────────────────────────────────────────
mangosAuth.ready.then(async user => {
    if (!user) return;

    const [cs, rs, cards_] = await Promise.all([
        api.get('/categories'),
        api.get('/recurrents'),
        api.get('/cards'),
    ]);
    categories = cs || [];
    recurrents = rs || [];
    cards = cards_ || [];

    setupDropZone();
    document.getElementById('analyze-btn').addEventListener('click', analyze);
    document.getElementById('clear-images').addEventListener('click', clearImages);
    document.getElementById('confirm-btn').addEventListener('click', commit);
    document.getElementById('discard-btn').addEventListener('click', discard);

    const persisted = loadPersistedDrafts();
    if (persisted && Array.isArray(persisted.drafts) && persisted.drafts.length > 0) {
        drafts = persisted.drafts;
        renderReview(persisted.unreadable || []);
    }
});
</script>
