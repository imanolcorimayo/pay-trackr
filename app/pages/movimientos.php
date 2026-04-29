<?php
// When arriving via the bottom-nav FAB from another page, render the AI modal
// already-open so it appears on first paint (no wait for auth + JS init).
$openAIOnLoad = !empty($_GET['ai']);
?>
<!-- Page header (desktop only — mobile topbar shows the page title; FAB covers AI input) -->
<div class="hidden lg:flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold">Movimientos</h1>
        <p class="text-sm text-muted mt-1">Todos tus movimientos del mes</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="/capturar" class="btn btn-outline" title="Capturar batch de imagenes con IA">
            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.857 2.286L12 21l-2.286-6.857L3 12l6.857-2.286L12 3z"/>
            </svg>
            Capturar
        </a>
        <button class="btn btn-primary" onclick="openAIModal()" title="Agregar un gasto con IA">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            IA
        </button>
        <button class="btn btn-outline" onclick="openPaymentModal()" title="Nuevo movimiento manual">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo
        </button>
    </div>
</div>

<!-- Mobile action row (Capturar + Nuevo manual; AI lives on the bottom-nav FAB) -->
<div class="lg:hidden flex items-center justify-end gap-2 mb-3">
    <a href="/capturar" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-border text-sm text-dark hover:bg-dark/5 active:scale-95 transition" title="Capturar batch de imagenes">
        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.857 2.286L12 21l-2.286-6.857L3 12l6.857-2.286L12 3z"/>
        </svg>
        <span>Capturar</span>
    </a>
    <button type="button" onclick="openPaymentModal()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-border text-sm text-dark hover:bg-dark/5 active:scale-95 transition" title="Nuevo movimiento manual">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Manual</span>
    </button>
</div>

<!-- Filter bar -->
<div class="card mb-4 flex flex-col sm:flex-row sm:items-center gap-3">
    <!-- Month nav (centered on mobile, left-aligned on desktop) -->
    <div class="flex items-center justify-center sm:justify-start gap-2 flex-shrink-0">
        <button type="button" id="month-prev" class="p-2 sm:p-1.5 rounded-lg text-muted hover:text-dark hover:bg-dark/5 active:scale-95 transition">
            <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <span id="month-label" class="text-sm font-medium min-w-[140px] text-center">--</span>
        <button type="button" id="month-next" class="p-2 sm:p-1.5 rounded-lg text-muted hover:text-dark hover:bg-dark/5 active:scale-95 transition">
            <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    <div class="hidden sm:block w-px h-6 bg-border"></div>

    <!-- Status tabs (equal-width row on mobile, inline tabs on desktop) -->
    <div class="grid grid-cols-3 sm:flex gap-1 text-sm" id="status-tabs">
        <button type="button" data-status="all" class="px-3 py-2 sm:py-1.5 rounded-lg transition-colors hover:bg-dark/5">Todos</button>
        <button type="button" data-status="unpaid" class="px-3 py-2 sm:py-1.5 rounded-lg transition-colors hover:bg-dark/5">Pendientes</button>
        <button type="button" data-status="paid" class="px-3 py-2 sm:py-1.5 rounded-lg transition-colors hover:bg-dark/5">Pagados</button>
    </div>

    <div class="hidden sm:block flex-1"></div>

    <!-- Category filter -->
    <select id="filter-category" class="input sm:max-w-[200px]">
        <option value="">Todas las categorias</option>
    </select>
</div>

<!-- Summary
     Mobile (<sm): Total full-width hero, Pagados + Pendientes split row, Fijos/Unicos hidden.
     sm-lg:        Total + Pagados + Pendientes in 3 cols, Fijos/Unicos hidden.
     lg+:          All 5 cards. -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
    <div class="card py-3 col-span-2 sm:col-span-1">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Total</p>
        <p class="text-xl sm:text-lg font-bold mt-0.5" id="sum-total">--</p>
        <p class="text-xs text-muted mt-0.5" id="sum-total-count">&nbsp;</p>
    </div>
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-success">Pagados</p>
        <p class="text-lg font-bold mt-0.5" id="sum-paid">--</p>
        <p class="text-xs text-muted mt-0.5" id="sum-paid-count">&nbsp;</p>
    </div>
    <div class="card py-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-danger">Pendientes</p>
        <p class="text-lg font-bold mt-0.5" id="sum-unpaid">--</p>
        <p class="text-xs text-muted mt-0.5" id="sum-unpaid-count">&nbsp;</p>
    </div>
    <div class="card py-3 hidden lg:block">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Fijos</p>
        <p class="text-lg font-bold mt-0.5" id="sum-fijo">--</p>
        <p class="text-xs text-muted mt-0.5" id="sum-fijo-count">&nbsp;</p>
    </div>
    <div class="card py-3 hidden lg:block">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Unicos</p>
        <p class="text-lg font-bold mt-0.5" id="sum-unico">--</p>
        <p class="text-xs text-muted mt-0.5" id="sum-unico-count">&nbsp;</p>
    </div>
</div>

<!-- List -->
<div class="card">
    <div id="payments-list">
        <div class="space-y-3">
            <div class="flex justify-between items-center py-3 border-b border-border"><span class="skeleton w-40 h-4">&nbsp;</span><span class="skeleton w-24 h-4">&nbsp;</span></div>
            <div class="flex justify-between items-center py-3 border-b border-border"><span class="skeleton w-32 h-4">&nbsp;</span><span class="skeleton w-20 h-4">&nbsp;</span></div>
            <div class="flex justify-between items-center py-3"><span class="skeleton w-36 h-4">&nbsp;</span><span class="skeleton w-24 h-4">&nbsp;</span></div>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Form modal ─────────────────────────── -->
<div id="payment-modal" class="fixed inset-0 z-50 hidden bg-dark/40">
    <!-- bottom: var(--keyboard-inset) lifts the sheet above the on-screen keyboard on iOS;
         max-h: 85dvh shrinks the sheet with the visual viewport when the keyboard opens. -->
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-lg max-h-[85dvh] sm:max-h-[92vh] overflow-y-auto safe-bottom">
            <!-- Drag handle (mobile only, tap to close) -->
            <button type="button" onclick="closePaymentModal()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <header class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h2 id="payment-modal-title" class="text-lg font-semibold">Nuevo movimiento</h2>
                <button type="button" onclick="closePaymentModal()" class="text-muted hover:text-dark p-1 -m-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <!-- Recurrent match banner (shown when AI matches a recurrent) -->
            <div id="ai-recurrent-banner" class="hidden mx-5 mt-4 p-3 bg-accent/10 border border-accent/30 rounded-lg text-sm">
                <p>
                    Esto coincide con tu recurrente <strong id="ai-rec-banner-title"></strong>.
                </p>
                <label id="ai-rec-update-amount-wrap" class="hidden flex items-center gap-2 mt-2 cursor-pointer select-none">
                    <input type="checkbox" id="ai-rec-update-amount" class="w-4 h-4 rounded border-border text-accent focus:ring-accent/30">
                    <span>Actualizar el monto del recurrente <span id="ai-rec-amount-diff" class="text-muted"></span></span>
                </label>
                <div class="flex flex-wrap gap-2 mt-3">
                    <button type="button" onclick="markRecurrentPaidFromAI()" class="btn btn-primary py-1.5 px-3 text-xs">Marcar recurrente como pagado</button>
                    <button type="button" onclick="dismissRecurrentBanner()" class="btn btn-ghost py-1.5 px-3 text-xs">Crear movimiento nuevo</button>
                </div>
            </div>

            <form id="payment-form" class="p-5 space-y-4">
                <input type="hidden" id="pmt-id">

                <div>
                    <label for="pmt-title" class="block text-sm font-medium mb-1.5">Titulo <span class="text-danger">*</span></label>
                    <input type="text" id="pmt-title" class="input" placeholder="Ej: Super, Cafe, Alquiler" maxlength="200" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="pmt-amount" class="block text-sm font-medium mb-1.5">Monto <span class="text-danger">*</span></label>
                        <input type="text" id="pmt-amount" class="input" placeholder="1234,56" inputmode="decimal" required>
                    </div>
                    <div>
                        <label for="pmt-due" class="block text-sm font-medium mb-1.5">Fecha</label>
                        <input type="date" id="pmt-due" class="input">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="pmt-category" class="block text-sm font-medium mb-1.5">Categoria</label>
                        <div class="relative">
                            <span id="pmt-category-swatch" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border border-border pointer-events-none"></span>
                            <select id="pmt-category" class="input pl-9">
                                <option value="">Sin categoria</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="pmt-card" class="block text-sm font-medium mb-1.5">Tarjeta</label>
                        <select id="pmt-card" class="input">
                            <option value="">Sin tarjeta</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="pmt-account" class="block text-sm font-medium mb-1.5">Cuenta</label>
                        <select id="pmt-account" class="input"></select>
                    </div>
                    <div>
                        <label for="pmt-currency" class="block text-sm font-medium mb-1.5">Moneda</label>
                        <select id="pmt-currency" class="input">
                            <option value="ARS">ARS</option>
                            <option value="USD">USD</option>
                            <option value="USDT">USDT</option>
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="pmt-is-paid" class="w-4 h-4 rounded border-border text-accent focus:ring-accent/30">
                    <span class="text-sm">Marcar como pagado</span>
                </label>

                <div>
                    <label for="pmt-description" class="block text-sm font-medium mb-1.5">Descripcion</label>
                    <textarea id="pmt-description" class="input min-h-[64px]" maxlength="500" rows="2"></textarea>
                </div>

                <!-- Recipient subform (collapsible) -->
                <details id="pmt-recipient-details" class="border border-border rounded-lg">
                    <summary class="px-4 py-2.5 text-sm font-medium cursor-pointer select-none flex items-center justify-between">
                        <span>Destinatario <span id="pmt-recipient-indicator" class="text-xs text-muted ml-1"></span></span>
                        <svg class="w-4 h-4 text-muted transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="px-4 pb-4 pt-1 space-y-3 border-t border-border">
                        <div>
                            <label for="pmt-rec-name" class="block text-xs font-medium mb-1 text-muted">Nombre</label>
                            <input type="text" id="pmt-rec-name" class="input" maxlength="200" placeholder="Juan Perez">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="pmt-rec-cbu" class="block text-xs font-medium mb-1 text-muted">CBU</label>
                                <input type="text" id="pmt-rec-cbu" class="input font-mono" maxlength="30">
                            </div>
                            <div>
                                <label for="pmt-rec-alias" class="block text-xs font-medium mb-1 text-muted">Alias</label>
                                <input type="text" id="pmt-rec-alias" class="input" maxlength="100">
                            </div>
                        </div>
                        <div>
                            <label for="pmt-rec-bank" class="block text-xs font-medium mb-1 text-muted">Banco</label>
                            <input type="text" id="pmt-rec-bank" class="input" maxlength="100">
                        </div>
                        <p class="text-xs text-muted">Dejar Nombre vacio para no guardar destinatario.</p>
                    </div>
                </details>

                <!-- Original artifact (image / audio / PDF). Hidden unless the
                     payment has ai_artifact_path set; populated in openPaymentModal. -->
                <details id="pmt-artifact-details" class="hidden border border-border rounded-lg">
                    <summary class="px-4 py-2.5 text-sm font-medium cursor-pointer select-none flex items-center justify-between">
                        <span>Original <span id="pmt-artifact-kind" class="text-xs text-muted ml-1"></span></span>
                        <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div id="pmt-artifact-body" class="px-4 pb-4 pt-3 border-t border-border"></div>
                </details>

                <p id="pmt-form-error" class="hidden text-sm text-danger">&nbsp;</p>

                <div class="flex items-center gap-2 pt-2">
                    <!-- Delete: only shown when editing an existing payment -->
                    <button type="button" id="pmt-form-delete" onclick="deleteFromPaymentModal()"
                            class="hidden text-sm text-danger hover:bg-danger/5 px-3 py-2 rounded-lg transition-colors">
                        Eliminar
                    </button>
                    <div class="flex-1"></div>
                    <button type="button" onclick="closePaymentModal()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" id="pmt-form-submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Confirm delete ─────────────────────────── -->
<div id="payment-delete-modal" class="fixed inset-0 z-50 hidden bg-dark/40">
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-sm safe-bottom">
            <!-- Drag handle (mobile only, tap to close) -->
            <button type="button" onclick="closePaymentDelete()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <div class="p-5">
                <h2 class="text-lg font-semibold">Eliminar movimiento</h2>
                <p class="text-sm text-muted mt-2">
                    Vas a eliminar <span id="pmt-delete-name" class="font-medium text-dark"></span>.
                    Esta accion no se puede deshacer.
                </p>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" onclick="closePaymentDelete()" class="btn btn-ghost">Cancelar</button>
                    <button type="button" onclick="confirmPaymentDelete()" id="pmt-delete-submit" class="btn btn-danger">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─────────────────────────── AI input modal ─────────────────────────── -->
<div id="ai-input-modal" class="fixed inset-0 z-50 <?= $openAIOnLoad ? '' : 'hidden ' ?>bg-dark/40">
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-lg max-h-[85dvh] sm:max-h-[92vh] overflow-y-auto safe-bottom">
            <!-- Drag handle (mobile only, tap to close) -->
            <button type="button" onclick="closeAIModal()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <header class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h2 class="text-lg font-semibold">Agregar con IA</h2>
                <button type="button" onclick="closeAIModal()" class="text-muted hover:text-dark p-1 -m-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <div class="p-5 space-y-4">
                <!-- Mode tabs (text active by default — JS keeps these classes in sync after init) -->
                <div class="flex gap-1 p-1 bg-dark/5 rounded-lg">
                    <button type="button" data-mode="text" class="ai-mode-tab flex-1 text-sm py-2 rounded-md transition-colors bg-white shadow-sm text-dark font-medium">Texto</button>
                    <button type="button" data-mode="image" class="ai-mode-tab flex-1 text-sm py-2 rounded-md transition-colors text-muted">Foto</button>
                    <button type="button" data-mode="audio" class="ai-mode-tab flex-1 text-sm py-2 rounded-md transition-colors text-muted">Audio</button>
                    <button type="button" data-mode="pdf" class="ai-mode-tab flex-1 text-sm py-2 rounded-md transition-colors text-muted">PDF</button>
                </div>

                <!-- Text mode -->
                <div id="ai-mode-text" class="ai-mode-pane">
                    <label for="ai-text" class="block text-sm font-medium mb-1.5">Describi el gasto</label>
                    <textarea id="ai-text" class="input min-h-[100px]" placeholder="Ej: 1500 super coto ayer; 800 cafe con juan; transferencia 5000 a Maria por alquiler"></textarea>
                    <p class="text-xs text-muted mt-1.5">Texto libre. La IA extrae monto, titulo, fecha y categoria.</p>
                </div>

                <!-- Image mode -->
                <div id="ai-mode-image" class="ai-mode-pane hidden">
                    <input type="file" id="ai-image-input" accept="image/*" class="hidden">
                    <div id="ai-image-zone" class="border-2 border-dashed border-border rounded-lg p-6 text-center cursor-pointer hover:border-accent hover:bg-accent/5 transition-colors">
                        <svg class="w-10 h-10 mx-auto text-muted mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm font-medium">Sacar foto o elegir imagen</p>
                        <p class="text-xs text-muted mt-1">JPG, PNG, WebP · max 4MB</p>
                    </div>
                    <div id="ai-image-preview" class="hidden mt-3 relative">
                        <img id="ai-image-thumb" alt="" class="w-full max-h-64 object-contain rounded-lg border border-border bg-dark/5">
                        <button type="button" onclick="clearAIImage()" class="absolute top-2 right-2 bg-danger text-white rounded-full w-7 h-7 flex items-center justify-center text-sm shadow">×</button>
                    </div>
                </div>

                <!-- Audio mode -->
                <div id="ai-mode-audio" class="ai-mode-pane hidden">
                    <!-- Idle -->
                    <div id="ai-audio-idle" class="border-2 border-dashed border-border rounded-lg p-6 text-center">
                        <button type="button" id="ai-audio-start"
                                class="w-16 h-16 mx-auto rounded-full bg-accent text-white flex items-center justify-center shadow hover:bg-accent/90 active:scale-95 transition">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-14 0m7 7v4m-4 0h8m-4-8a3 3 0 01-3-3V6a3 3 0 116 0v5a3 3 0 01-3 3z"/>
                            </svg>
                        </button>
                        <p class="text-sm font-medium mt-3">Grabar audio</p>
                        <p class="text-xs text-muted mt-1">Decí monto, lugar, fecha. Max 60s.</p>
                    </div>
                    <!-- Recording -->
                    <div id="ai-audio-recording" class="hidden border-2 border-danger/40 bg-danger/5 rounded-lg p-6 text-center">
                        <div class="w-16 h-16 mx-auto rounded-full bg-danger text-white flex items-center justify-center animate-pulse">
                            <span class="w-5 h-5 rounded-sm bg-white"></span>
                        </div>
                        <p class="text-sm font-medium text-danger mt-3">Grabando...</p>
                        <p id="ai-audio-timer" class="text-2xl font-mono text-danger mt-1">0:00</p>
                        <button type="button" id="ai-audio-stop" class="btn btn-outline mt-3 py-1.5 px-4 text-sm">Detener</button>
                    </div>
                    <!-- Recorded -->
                    <div id="ai-audio-recorded" class="hidden border border-border rounded-lg p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <svg class="w-5 h-5 text-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm font-medium">Grabado · <span id="ai-audio-duration">0:00</span></span>
                            <button type="button" id="ai-audio-redo" class="ml-auto text-xs text-muted hover:text-dark">Re-grabar</button>
                        </div>
                        <audio id="ai-audio-playback" controls class="w-full"></audio>
                    </div>
                </div>

                <!-- PDF mode -->
                <div id="ai-mode-pdf" class="ai-mode-pane hidden">
                    <input type="file" id="ai-pdf-input" accept="application/pdf" class="hidden">
                    <div id="ai-pdf-zone" class="border-2 border-dashed border-border rounded-lg p-6 text-center cursor-pointer hover:border-accent hover:bg-accent/5 transition-colors">
                        <svg class="w-10 h-10 mx-auto text-muted mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm font-medium">Subir comprobante PDF</p>
                        <p class="text-xs text-muted mt-1">max 7MB</p>
                    </div>
                    <div id="ai-pdf-preview" class="hidden mt-3 flex items-center gap-2 p-3 border border-border rounded-lg">
                        <svg class="w-5 h-5 text-muted flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span id="ai-pdf-name" class="text-sm truncate flex-1"></span>
                        <button type="button" onclick="clearAIPdf()" class="text-muted hover:text-danger px-1">×</button>
                    </div>
                </div>

                <!-- Caption (image/pdf only) -->
                <div id="ai-caption-wrap" class="hidden">
                    <label for="ai-caption" class="block text-xs font-medium mb-1 text-muted">Texto adicional (opcional)</label>
                    <input type="text" id="ai-caption" class="input" placeholder="Ej: cafe en Coto, ayer">
                </div>

                <p id="ai-error" class="hidden text-sm text-danger"></p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeAIModal()" class="btn btn-ghost">Cancelar</button>
                    <button type="button" id="ai-submit-btn" onclick="submitAIInput()" class="btn btn-primary">
                        <svg id="ai-submit-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span id="ai-submit-label">Analizar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const SVG_NS = 'http://www.w3.org/2000/svg';
const ICON_EDIT = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
const ICON_TRASH = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3';
const ICON_RECURRENT = 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15';

let payments = [];
let categories = [];
let cards = [];
let accounts = [];
let editingId = null;
let pendingDeleteId = null;

// AI input state. aiSourceTag is non-null while the payment-modal is pre-filled
// from /api/ai/parse-single — used to tag the resulting payment with source=ai-*.
let aiSourceTag = null;
let aiState = { mode: 'text', image: null, pdf: null, audio: null, draft: null, matched: null, artifact: null };

// MediaRecorder runtime (only populated while a recording is in progress or just-finished).
let aiAudio = { recorder: null, stream: null, chunks: [], startTime: 0, mimeType: null, timerInterval: null, autoStopTimeout: null };

// View state — initialized from URL query string, persisted on every change.
let viewMonth = startOfMonth(new Date());
let statusFilter = 'all';   // all | paid | unpaid
let categoryFilter = '';

function readUrlState() {
    const p = new URLSearchParams(window.location.search);
    if (p.has('month')) {
        const [y, m] = p.get('month').split('-').map(Number);
        if (y && m >= 1 && m <= 12) viewMonth = new Date(y, m - 1, 1);
    }
    if (p.has('status') && ['all', 'paid', 'unpaid'].includes(p.get('status'))) {
        statusFilter = p.get('status');
    }
    if (p.has('category')) categoryFilter = p.get('category');
}

// Mobile FAB lands on /movimientos?ai=1 (or legacy /movimientos#ai) when the user wants the
// AI input modal. Detect either, strip from URL, and open the modal.
function openAIFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const wantsAI = params.get('ai') === '1' || window.location.hash === '#ai';
    if (!wantsAI) return;
    params.delete('ai');
    const qs = params.toString();
    history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
    openAIModal();
}

function writeUrlState() {
    const y = viewMonth.getFullYear();
    const m = String(viewMonth.getMonth() + 1).padStart(2, '0');
    const params = new URLSearchParams();
    params.set('month', `${y}-${m}`);
    if (statusFilter !== 'all') params.set('status', statusFilter);
    if (categoryFilter) params.set('category', categoryFilter);
    const qs = params.toString();
    history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
}

// ── Helpers ─────────────────────────────────────────────────────────
function startOfMonth(d) { return new Date(d.getFullYear(), d.getMonth(), 1); }
function endOfMonth(d)   { return new Date(d.getFullYear(), d.getMonth() + 1, 0); }
function fmtDateRange(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const start = `${y}-${m}-01`;
    const lastDay = endOfMonth(d).getDate();
    const end = `${y}-${m}-${String(lastDay).padStart(2, '0')}`;
    return { start, end };
}
function monthLabel(d) {
    const s = d.toLocaleDateString('es-AR', { month: 'long', year: 'numeric' });
    return s.charAt(0).toUpperCase() + s.slice(1);
}
function svgIcon(pathD, cls = 'w-4 h-4') {
    const svg = document.createElementNS(SVG_NS, 'svg');
    svg.setAttribute('class', cls);
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
function todayISODate() {
    const d = new Date();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${m}-${day}`;
}
function categoryById(id) { return categories.find(c => c.id === id); }
function cardById(id)     { return cards.find(c => c.id === id); }
function accountById(id)  { return accounts.find(a => a.id === id); }
function defaultAccount() { return accounts.find(a => Number(a.is_default) === 1) || accounts[0] || null; }
function fmtCurrencyBadge(currency) { return currency && currency !== 'ARS' ? currency : null; }

// Origin label shown in the row's subline. Keeps 'manual' silent so default
// rows stay clean. Whatsapp variants collapse to a single label since the
// distinction (text vs audio vs image) isn't useful at the row level.
function labelForSource(source) {
    switch (source) {
        case 'ai-text':  return 'IA texto';
        case 'ai-image': return 'IA foto';
        case 'ai-audio': return 'IA audio';
        case 'ai-pdf':   return 'IA PDF';
        case 'whatsapp-text':
        case 'whatsapp-audio':
        case 'whatsapp-image':
        case 'whatsapp-pdf':
            return 'whatsapp';
        default: return null;
    }
}

function hexToRgba(hex, alpha) {
    if (!hex || hex.length < 7) return null;
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    if (isNaN(r) || isNaN(g) || isNaN(b)) return null;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

// Most-used category across the loaded payment set; '' if none qualify.
function mostCommonCategoryId() {
    const counts = {};
    payments.forEach(p => {
        if (!p.expense_category_id) return;
        counts[p.expense_category_id] = (counts[p.expense_category_id] || 0) + 1;
    });
    let bestId = '', bestCount = 0;
    for (const id in counts) {
        if (counts[id] > bestCount) { bestId = id; bestCount = counts[id]; }
    }
    return bestId;
}

function updateCategorySwatch() {
    const id = document.getElementById('pmt-category').value;
    const cat = categories.find(c => c.id === id);
    document.getElementById('pmt-category-swatch').style.backgroundColor = cat?.color || 'transparent';
}

// ── Loading & rendering ─────────────────────────────────────────────
async function loadAll() {
    document.getElementById('month-label').textContent = monthLabel(viewMonth);
    const range = fmtDateRange(viewMonth);

    const [pays, cats, crds, accs] = await Promise.all([
        api.get('/transactions', { start_date: range.start, end_date: range.end }),
        api.get('/categories'),
        api.get('/cards'),
        api.get('/accounts'),
    ]);

    payments = Array.isArray(pays) ? pays : [];
    categories = Array.isArray(cats) ? cats : [];
    cards = Array.isArray(crds) ? crds : [];
    accounts = Array.isArray(accs) ? accs : [];
    if (pays && pays.error) toast(`No se pudieron cargar los movimientos: ${pays.error}`, 'error');

    populateDropdowns();
    document.getElementById('filter-category').value = categoryFilter;
    renderSummary();
    renderPayments();
}

function populateDropdowns() {
    // Form modal: tint each option with its category color (best-effort —
    // works on Chromium browsers; degrades to plain text elsewhere).
    const catSel = document.getElementById('pmt-category');
    catSel.textContent = '';
    catSel.appendChild(makeOption('', 'Sin categoria'));
    categories.forEach(c => {
        const opt = makeOption(c.id, c.name);
        const tint = hexToRgba(c.color, 0.18);
        if (tint) opt.style.backgroundColor = tint;
        catSel.appendChild(opt);
    });

    const cardSel = document.getElementById('pmt-card');
    cardSel.textContent = '';
    cardSel.appendChild(makeOption('', 'Sin tarjeta'));
    cards.forEach(c => cardSel.appendChild(makeOption(c.id, c.name + (c.last_four ? ` ····${c.last_four}` : ''))));

    const acctSel = document.getElementById('pmt-account');
    acctSel.textContent = '';
    accounts.forEach(a => acctSel.appendChild(makeOption(a.id, a.name + ' (' + a.currency + ')')));

    // Filter bar (preserve current selection)
    const filterSel = document.getElementById('filter-category');
    const prev = filterSel.value;
    filterSel.textContent = '';
    filterSel.appendChild(makeOption('', 'Todas las categorias'));
    categories.forEach(c => filterSel.appendChild(makeOption(c.id, c.name)));
    filterSel.value = prev;
}

function makeOption(value, label) {
    const o = document.createElement('option');
    o.value = value;
    o.textContent = label;
    return o;
}

function applyFilters(list) {
    return list.filter(p => {
        if (statusFilter === 'paid' && p.is_paid != 1) return false;
        if (statusFilter === 'unpaid' && p.is_paid == 1) return false;
        if (categoryFilter && p.expense_category_id !== categoryFilter) return false;
        return true;
    });
}

function renderSummary() {
    const filtered = applyFilters(payments);
    let total = 0, paid = 0, unpaid = 0;
    let paidCount = 0, unpaidCount = 0;
    let fijoTotal = 0, fijoCount = 0;
    let unicoTotal = 0, unicoCount = 0;

    filtered.forEach(p => {
        const a = Math.abs(Number(p.amount));
        total += a;
        if (p.is_paid == 1) { paid += a; paidCount++; }
        else { unpaid += a; unpaidCount++; }
        if (p.transaction_type === 'recurrent') { fijoTotal += a; fijoCount++; }
        else { unicoTotal += a; unicoCount++; }
    });

    const plural = (n) => `${n} movimiento${n === 1 ? '' : 's'}`;

    document.getElementById('sum-total').textContent = formatPrice(total);
    document.getElementById('sum-total-count').textContent = plural(filtered.length);
    document.getElementById('sum-paid').textContent = formatPrice(paid);
    document.getElementById('sum-paid-count').textContent = plural(paidCount);
    document.getElementById('sum-unpaid').textContent = formatPrice(unpaid);
    document.getElementById('sum-unpaid-count').textContent = plural(unpaidCount);
    document.getElementById('sum-fijo').textContent = formatPrice(fijoTotal);
    document.getElementById('sum-fijo-count').textContent = plural(fijoCount);
    document.getElementById('sum-unico').textContent = formatPrice(unicoTotal);
    document.getElementById('sum-unico-count').textContent = plural(unicoCount);
}

function renderPayments() {
    const list = document.getElementById('payments-list');
    list.textContent = '';

    const filtered = applyFilters(payments);

    if (filtered.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'text-center py-12';
        const msg = document.createElement('p');
        msg.className = 'text-sm text-muted';
        msg.textContent = payments.length === 0
            ? 'No hay movimientos en este mes.'
            : 'Ningun movimiento coincide con los filtros.';
        empty.appendChild(msg);
        list.appendChild(empty);
        return;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    filtered.forEach((p, i) => {
        const isLast = i === filtered.length - 1;
        const isPaid = p.is_paid == 1;
        const dueDate = p.due_ts ? new Date(p.due_ts.replace(' ', 'T')) : null;
        const isOverdue = !isPaid && dueDate && dueDate < today;
        list.appendChild(buildPaymentRow(p, isPaid, isOverdue, isLast));
    });
}

function buildPaymentRow(p, isPaid, isOverdue, isLast) {
    const row = document.createElement('div');
    row.className = `group flex items-center gap-3 py-3.5 sm:py-3 px-1 cursor-pointer hover:bg-dark/5 active:bg-dark/5 -mx-1 rounded transition-colors ${isLast ? '' : 'border-b border-border'}`;
    row.addEventListener('click', () => openPaymentModal(p));

    const cat = categoryById(p.expense_category_id);
    const dot = document.createElement('div');
    dot.className = 'h-3 w-3 sm:h-2.5 sm:w-2.5 rounded-full flex-shrink-0';
    dot.style.backgroundColor = cat?.color || '#E8E2DA';
    dot.title = cat?.name || 'Sin categoria';
    row.appendChild(dot);

    const info = document.createElement('div');
    info.className = 'flex-1 min-w-0';

    const titleRow = document.createElement('p');
    titleRow.className = 'text-[15px] sm:text-sm font-medium flex items-center gap-1.5 min-w-0';

    if (p.transaction_type === 'recurrent') {
        const recIcon = svgIcon(ICON_RECURRENT, 'w-3.5 h-3.5 text-muted flex-shrink-0');
        const recTitle = document.createElementNS(SVG_NS, 'title');
        recTitle.textContent = 'Movimiento de gasto fijo';
        recIcon.appendChild(recTitle);
        titleRow.appendChild(recIcon);
    }

    const titleText = document.createElement('span');
    titleText.className = 'truncate';
    titleText.textContent = p.title;
    titleRow.appendChild(titleText);
    info.appendChild(titleRow);

    const subParts = [];
    if (isPaid && p.paid_ts) {
        subParts.push(`Pagado: ${formatDate(p.paid_ts)}`);
    } else if (p.due_ts) {
        subParts.push(formatDate(p.due_ts));
    }
    const card = cardById(p.card_id);
    if (card) subParts.push(card.name + (card.last_four ? ` ····${card.last_four}` : ''));
    const acct = accountById(p.account_id);
    if (acct && acct.is_default != 1) subParts.push(acct.name);
    const sourceLabel = labelForSource(p.source);
    if (sourceLabel) subParts.push(sourceLabel);

    const sub = document.createElement('p');
    sub.className = 'text-xs text-muted truncate mt-0.5';
    sub.textContent = subParts.join(' · ') || ' ';
    info.appendChild(sub);

    row.appendChild(info);

    const right = document.createElement('div');
    right.className = 'flex items-center gap-2 flex-shrink-0';

    const amount = document.createElement('span');
    amount.className = 'text-[15px] sm:text-sm font-semibold tabular-nums';
    const curBadge = fmtCurrencyBadge(p.currency);
    amount.textContent = (curBadge ? curBadge + ' ' : '') + formatPrice(Math.abs(p.amount));
    right.appendChild(amount);

    const badge = document.createElement('button');
    badge.type = 'button';
    let badgeColor;
    if (isPaid) { badgeColor = 'bg-success/10 text-success'; badge.textContent = 'Pagado'; }
    else if (isOverdue) { badgeColor = 'bg-danger/10 text-danger'; badge.textContent = 'Vencido'; }
    else { badgeColor = 'bg-muted/10 text-muted'; badge.textContent = 'Pendiente'; }
    // Inline badge styling (instead of .badge component) — bigger tap target on mobile.
    badge.className = `inline-block font-semibold tracking-wide uppercase rounded cursor-pointer hover:opacity-80 active:scale-95 transition disabled:opacity-50 disabled:cursor-wait whitespace-nowrap text-[10px] px-2.5 py-1 sm:px-2 sm:py-0.5 ${badgeColor}`;
    badge.title = isPaid ? 'Marcar como pendiente' : 'Marcar como pagado';
    badge.addEventListener('click', e => {
        e.stopPropagation();
        togglePaymentPaid(p, badge);
    });
    right.appendChild(badge);

    // Edit + delete: desktop hover only. Mobile: row tap = edit; delete via the edit modal.
    const actions = document.createElement('div');
    actions.className = 'hidden lg:flex gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity';
    actions.appendChild(iconButton(ICON_EDIT, 'text-muted hover:text-dark', () => openPaymentModal(p)));
    actions.appendChild(iconButton(ICON_TRASH, 'text-muted hover:text-danger', () => openPaymentDelete(p)));
    right.appendChild(actions);

    row.appendChild(right);
    return row;
}

// ── Inline paid toggle ──────────────────────────────────────────────
async function togglePaymentPaid(p, btn) {
    btn.disabled = true;
    const wasPaid = p.is_paid == 1;
    try {
        const result = await api.put('/transactions', { is_paid: !wasPaid }, { id: p.id });
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
async function openPaymentModal(p) {
    editingId = p?.id || null;
    // Default to clean AI state. AI flow re-applies banner + source after this returns.
    aiSourceTag = null;
    hideRecurrentBanner();
    document.getElementById('payment-modal-title').textContent = editingId ? 'Editar movimiento' : 'Nuevo movimiento';

    // For edit, fetch single to get nested recipient
    let full = p;
    if (p?.id) {
        const fetched = await api.get('/transactions', { id: p.id });
        if (fetched && !fetched.error) full = fetched;
    }

    document.getElementById('pmt-id').value = full?.id || '';
    document.getElementById('pmt-title').value = full?.title || '';
    document.getElementById('pmt-amount').value = full ? formatAmountForInput(Math.abs(full.amount)) : '';
    document.getElementById('pmt-due').value = full?.due_ts ? full.due_ts.slice(0, 10) : (p ? '' : todayISODate());
    // For new payments default to the most-used category; for edits use the stored one.
    document.getElementById('pmt-category').value = full
        ? (full.expense_category_id || '')
        : mostCommonCategoryId();
    document.getElementById('pmt-card').value = full?.card_id || '';
    const defAcct = defaultAccount();
    document.getElementById('pmt-account').value = full?.account_id || (defAcct?.id || '');
    document.getElementById('pmt-currency').value = full?.currency || (defAcct?.currency || 'ARS');
    // New payments: default checked (most logging happens after paying); edits: actual state.
    document.getElementById('pmt-is-paid').checked = full ? full.is_paid == 1 : true;
    document.getElementById('pmt-description').value = full?.description || '';
    updateCategorySwatch();

    const r = full?.recipient || {};
    document.getElementById('pmt-rec-name').value = r.name || '';
    document.getElementById('pmt-rec-cbu').value = r.cbu || '';
    document.getElementById('pmt-rec-alias').value = r.alias || '';
    document.getElementById('pmt-rec-bank').value = r.bank || '';

    // Open the recipient details if there's existing data
    document.getElementById('pmt-recipient-details').open = !!r.name;
    document.getElementById('pmt-recipient-indicator').textContent = r.name ? `(${r.name})` : '';

    document.getElementById('pmt-form-error').classList.add('hidden');
    // Delete button: only visible when editing a real (saved) payment.
    document.getElementById('pmt-form-delete').classList.toggle('hidden', !editingId);
    // Original artifact viewer: only when this payment has one persisted.
    if (full?.id && full.ai_artifact_path) {
        renderArtifactViewer(full.id, full.ai_artifact_mime || '');
    } else {
        hideArtifactViewer();
    }
    document.getElementById('payment-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('pmt-title').focus(), 50);
}

// Render the "Original" details element with an inline preview of the AI input
// artifact (image / audio / PDF). The actual fetch goes through our private
// proxy at /api/transactions/artifact?id=<id>; the bucket itself is not exposed.
function renderArtifactViewer(paymentId, mime) {
    const details = document.getElementById('pmt-artifact-details');
    const body = document.getElementById('pmt-artifact-body');
    const kind = document.getElementById('pmt-artifact-kind');
    const url = `${window.MANGOS_API_URL}/transactions/artifact?id=${encodeURIComponent(paymentId)}`;

    body.textContent = '';

    const showFallback = () => {
        body.textContent = '';
        const p = document.createElement('p');
        p.className = 'text-xs text-muted';
        p.textContent = 'Original ya no disponible.';
        body.appendChild(p);
    };

    let label = 'archivo';
    if (mime.startsWith('image/')) {
        label = 'imagen';
        const img = document.createElement('img');
        img.src = url;
        img.alt = 'Original';
        img.className = 'w-full max-h-64 object-contain rounded-lg bg-dark/5';
        img.addEventListener('error', showFallback);
        body.appendChild(img);
    } else if (mime.startsWith('audio/')) {
        label = 'audio';
        const audio = document.createElement('audio');
        audio.controls = true;
        audio.src = url;
        audio.className = 'w-full';
        audio.addEventListener('error', showFallback);
        body.appendChild(audio);
    } else if (mime === 'application/pdf') {
        label = 'PDF';
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener';
        link.className = 'inline-flex items-center gap-2 text-sm text-accent hover:underline';
        link.textContent = 'Abrir PDF en pestaña nueva';
        body.appendChild(link);
    } else {
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener';
        link.className = 'text-sm text-accent hover:underline';
        link.textContent = 'Descargar archivo original';
        body.appendChild(link);
    }
    kind.textContent = `(${label})`;
    details.classList.remove('hidden');
    details.open = false;
}

function hideArtifactViewer() {
    const details = document.getElementById('pmt-artifact-details');
    if (!details) return;
    details.classList.add('hidden');
    details.open = false;
    document.getElementById('pmt-artifact-body').textContent = '';
    document.getElementById('pmt-artifact-kind').textContent = '';
}

// Triggered from inside the edit modal — delegates to the existing delete confirm flow.
function deleteFromPaymentModal() {
    if (!editingId) return;
    const p = payments.find(x => x.id === editingId);
    if (!p) return;
    closePaymentModal();
    openPaymentDelete(p);
}

function closePaymentModal() {
    // If we still have an unsaved AI artifact when closing, the user dismissed
    // the prefilled form — fire-and-forget a discard so we don't leak orphans.
    // Successful saves null `aiState.artifact` before reaching this function.
    if (aiState.artifact && aiState.artifact.path) {
        const path = aiState.artifact.path;
        aiState.artifact = null;
        api.post('/ai/discard-artifact', { path }).catch(err => console.warn('discard failed', err));
    }
    document.getElementById('payment-modal').classList.add('hidden');
    editingId = null;
    aiSourceTag = null;
    hideRecurrentBanner();
    hideArtifactViewer();
}

async function submitPaymentForm(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('pmt-form-submit');
    const errorEl = document.getElementById('pmt-form-error');
    errorEl.classList.add('hidden');
    submitBtn.disabled = true;

    const amountNum = parseAmount(document.getElementById('pmt-amount').value);
    if (!isFinite(amountNum) || amountNum <= 0) {
        errorEl.textContent = 'El monto debe ser un numero mayor a cero';
        errorEl.classList.remove('hidden');
        submitBtn.disabled = false;
        return;
    }

    const dueDate = document.getElementById('pmt-due').value;

    const recName = document.getElementById('pmt-rec-name').value.trim();
    const recipient = recName
        ? {
              name: recName,
              cbu: document.getElementById('pmt-rec-cbu').value.trim() || null,
              alias: document.getElementById('pmt-rec-alias').value.trim() || null,
              bank: document.getElementById('pmt-rec-bank').value.trim() || null,
          }
        : null;

    const body = {
        title: document.getElementById('pmt-title').value.trim(),
        amount: amountNum,
        due_ts: dueDate ? `${dueDate} 12:00:00` : null,
        expense_category_id: document.getElementById('pmt-category').value || null,
        card_id: document.getElementById('pmt-card').value || null,
        account_id: document.getElementById('pmt-account').value || null,
        currency: document.getElementById('pmt-currency').value || 'ARS',
        is_paid: document.getElementById('pmt-is-paid').checked,
        description: document.getElementById('pmt-description').value.trim(),
        recipient,
    };
    if (!editingId && aiSourceTag) body.source = aiSourceTag;
    if (!editingId && aiState.artifact) {
        body.ai_artifact_path = aiState.artifact.path;
        body.ai_artifact_mime = aiState.artifact.mime;
    }

    try {
        const result = editingId
            ? await api.put('/transactions', body, { id: editingId })
            : await api.post('/transactions', body);

        // Success shape: POST → {id}, PUT → {updated: true}. Anything else
        // (including [] from a hard 500 with no JSON body) is a failure —
        // keep the form open with the values intact.
        const ok = editingId ? result?.updated === true : !!result?.id;
        if (!ok) {
            errorEl.textContent = result?.error || 'Error al guardar';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            return;
        }

        toast(editingId ? 'Movimiento actualizado' : 'Movimiento creado', 'success');
        // Payment now owns the artifact; null it so closePaymentModal's
        // discard guard doesn't try to delete it from Spaces.
        aiState.artifact = null;
        closePaymentModal();
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
function openPaymentDelete(p) {
    pendingDeleteId = p.id;
    document.getElementById('pmt-delete-name').textContent = p.title;
    document.getElementById('payment-delete-modal').classList.remove('hidden');
}

function closePaymentDelete() {
    document.getElementById('payment-delete-modal').classList.add('hidden');
    pendingDeleteId = null;
}

async function confirmPaymentDelete() {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('pmt-delete-submit');
    btn.disabled = true;
    try {
        const result = await api.del('/transactions', { id: pendingDeleteId });
        if (!result || result.error) {
            toast(result?.error || 'No se pudo eliminar', 'error');
            return;
        }
        toast('Movimiento eliminado', 'success');
        closePaymentDelete();
        await loadAll();
    } catch (err) {
        toast('Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
}

// ── Filter handlers ─────────────────────────────────────────────────
function setStatusFilter(status) {
    statusFilter = status;
    document.querySelectorAll('#status-tabs button').forEach(btn => {
        const active = btn.dataset.status === status;
        btn.classList.toggle('bg-accent/10', active);
        btn.classList.toggle('text-accent', active);
        btn.classList.toggle('font-medium', active);
    });
    writeUrlState();
    renderSummary();
    renderPayments();
}

// ── AI input modal ──────────────────────────────────────────────────
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const r = new FileReader();
        r.onload = () => {
            const dataUrl = r.result;
            const base64 = dataUrl.split(',', 2)[1] || '';
            resolve({ dataUrl, base64 });
        };
        r.onerror = reject;
        r.readAsDataURL(file);
    });
}

function openAIModal() {
    resetAIModal();
    document.getElementById('ai-input-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('ai-text').focus(), 50);
}

function closeAIModal() {
    document.getElementById('ai-input-modal').classList.add('hidden');
    resetAIModal();
}

function resetAIModal() {
    cancelAudioRecording();
    aiState = { mode: 'text', image: null, pdf: null, audio: null, draft: null, matched: null };
    document.getElementById('ai-text').value = '';
    document.getElementById('ai-image-input').value = '';
    document.getElementById('ai-pdf-input').value = '';
    document.getElementById('ai-image-preview').classList.add('hidden');
    document.getElementById('ai-pdf-preview').classList.add('hidden');
    document.getElementById('ai-caption').value = '';
    document.getElementById('ai-error').classList.add('hidden');
    showAudioState('idle');
    setAILoading(false);
    setAIMode('text');
}

function setAIMode(mode) {
    // Stop any in-flight recording when leaving the audio tab.
    if (aiState.mode === 'audio' && mode !== 'audio' && aiAudio.recorder?.state === 'recording') {
        cancelAudioRecording();
    }
    aiState.mode = mode;
    document.querySelectorAll('.ai-mode-tab').forEach(t => {
        const active = t.dataset.mode === mode;
        t.classList.toggle('bg-white', active);
        t.classList.toggle('shadow-sm', active);
        t.classList.toggle('text-dark', active);
        t.classList.toggle('font-medium', active);
        t.classList.toggle('text-muted', !active);
    });
    document.querySelectorAll('.ai-mode-pane').forEach(p => p.classList.add('hidden'));
    document.getElementById(`ai-mode-${mode}`).classList.remove('hidden');
    document.getElementById('ai-caption-wrap').classList.toggle('hidden', mode === 'text');
}

async function handleAIImageFile(file) {
    if (!file.type.startsWith('image/')) { toast('El archivo no es una imagen', 'error'); return; }
    if (file.size > 4 * 1024 * 1024) { toast('La imagen supera los 4MB', 'error'); return; }
    try {
        const { dataUrl, base64 } = await fileToBase64(file);
        aiState.image = { mimeType: file.type, base64 };
        document.getElementById('ai-image-thumb').src = dataUrl;
        document.getElementById('ai-image-preview').classList.remove('hidden');
    } catch (e) {
        console.error(e);
        toast('No se pudo leer la imagen', 'error');
    }
}

function clearAIImage() {
    aiState.image = null;
    document.getElementById('ai-image-input').value = '';
    document.getElementById('ai-image-preview').classList.add('hidden');
}

async function handleAIPdfFile(file) {
    if (file.type !== 'application/pdf') { toast('El archivo no es un PDF', 'error'); return; }
    if (file.size > 7 * 1024 * 1024) { toast('El PDF supera los 7MB', 'error'); return; }
    try {
        const { base64 } = await fileToBase64(file);
        aiState.pdf = { mimeType: 'application/pdf', base64, name: file.name };
        document.getElementById('ai-pdf-name').textContent = file.name;
        document.getElementById('ai-pdf-preview').classList.remove('hidden');
    } catch (e) {
        console.error(e);
        toast('No se pudo leer el PDF', 'error');
    }
}

function clearAIPdf() {
    aiState.pdf = null;
    document.getElementById('ai-pdf-input').value = '';
    document.getElementById('ai-pdf-preview').classList.add('hidden');
}

// Audio: prefer Gemini-supported containers (OGG, MP4) before falling back to
// WebM. MediaRecorder support varies — Chrome/Firefox usually do OGG/Opus,
// Safari does MP4/AAC, both Chromium variants do WebM/Opus.
function pickAudioMimeType() {
    if (typeof MediaRecorder === 'undefined') return '';
    const candidates = ['audio/ogg;codecs=opus', 'audio/mp4', 'audio/webm;codecs=opus', 'audio/webm'];
    for (const m of candidates) {
        if (MediaRecorder.isTypeSupported(m)) return m;
    }
    return '';
}

function showAudioState(state) {
    document.getElementById('ai-audio-idle').classList.toggle('hidden', state !== 'idle');
    document.getElementById('ai-audio-recording').classList.toggle('hidden', state !== 'recording');
    document.getElementById('ai-audio-recorded').classList.toggle('hidden', state !== 'recorded');
}

function formatAudioDuration(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

async function startAudioRecording() {
    if (!navigator.mediaDevices?.getUserMedia) {
        toast('Tu navegador no soporta grabacion de audio', 'error');
        return;
    }
    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (e) {
        if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
            toast('Permitir el acceso al microfono para grabar', 'error');
        } else {
            console.error(e);
            toast('No se pudo acceder al microfono', 'error');
        }
        return;
    }

    const mimeType = pickAudioMimeType();
    let recorder;
    try {
        recorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream);
    } catch (e) {
        console.error(e);
        stream.getTracks().forEach(t => t.stop());
        toast('No se pudo iniciar la grabacion', 'error');
        return;
    }

    aiAudio.recorder = recorder;
    aiAudio.stream = stream;
    aiAudio.chunks = [];
    aiAudio.mimeType = recorder.mimeType || mimeType || 'audio/webm';
    aiAudio.startTime = Date.now();

    recorder.addEventListener('dataavailable', e => {
        if (e.data && e.data.size > 0) aiAudio.chunks.push(e.data);
    });
    recorder.addEventListener('stop', onAudioRecordingStop);
    recorder.start();

    showAudioState('recording');
    aiAudio.timerInterval = setInterval(updateAudioTimer, 250);
    updateAudioTimer();
    // Hard cap at 60s — beyond that the base64 payload starts crossing 8MB.
    aiAudio.autoStopTimeout = setTimeout(() => {
        if (aiAudio.recorder?.state === 'recording') stopAudioRecording();
    }, 60_000);
}

function stopAudioRecording() {
    if (aiAudio.recorder?.state === 'recording') aiAudio.recorder.stop();
    if (aiAudio.stream) {
        aiAudio.stream.getTracks().forEach(t => t.stop());
        aiAudio.stream = null;
    }
    if (aiAudio.timerInterval) { clearInterval(aiAudio.timerInterval); aiAudio.timerInterval = null; }
    if (aiAudio.autoStopTimeout) { clearTimeout(aiAudio.autoStopTimeout); aiAudio.autoStopTimeout = null; }
}

function cancelAudioRecording() {
    stopAudioRecording();
    aiAudio.chunks = [];
    aiAudio.recorder = null;
    aiState.audio = null;
    const playback = document.getElementById('ai-audio-playback');
    if (playback) {
        try { playback.pause(); } catch (_) {}
        playback.src = '';
    }
    showAudioState('idle');
}

function onAudioRecordingStop() {
    const blob = new Blob(aiAudio.chunks, { type: aiAudio.mimeType });
    const durationSec = Math.max(1, Math.round((Date.now() - aiAudio.startTime) / 1000));

    const reader = new FileReader();
    reader.onload = () => {
        const dataUrl = reader.result;
        const base64 = dataUrl.split(',', 2)[1] || '';
        if (base64.length > 8 * 1024 * 1024) {
            toast('El audio supera los 8MB. Probá con uno mas corto.', 'error');
            cancelAudioRecording();
            return;
        }
        aiState.audio = { mimeType: aiAudio.mimeType, base64, durationSec };
        document.getElementById('ai-audio-playback').src = dataUrl;
        document.getElementById('ai-audio-duration').textContent = formatAudioDuration(durationSec);
        showAudioState('recorded');
    };
    reader.onerror = () => {
        toast('No se pudo leer el audio grabado', 'error');
        cancelAudioRecording();
    };
    reader.readAsDataURL(blob);
}

function updateAudioTimer() {
    const elapsed = Math.floor((Date.now() - aiAudio.startTime) / 1000);
    document.getElementById('ai-audio-timer').textContent = formatAudioDuration(elapsed);
}

function setAILoading(loading) {
    document.getElementById('ai-submit-btn').disabled = loading;
    document.getElementById('ai-submit-spinner').classList.toggle('hidden', !loading);
    document.getElementById('ai-submit-label').textContent = loading ? 'Analizando...' : 'Analizar';
}

async function submitAIInput() {
    const errEl = document.getElementById('ai-error');
    errEl.classList.add('hidden');

    const payload = { mode: aiState.mode };
    const caption = document.getElementById('ai-caption').value.trim();

    if (aiState.mode === 'text') {
        const text = document.getElementById('ai-text').value.trim();
        if (!text) { showAIError('Escribi una descripcion del gasto'); return; }
        payload.text = text;
    } else if (aiState.mode === 'image') {
        if (!aiState.image) { showAIError('Adjunta una imagen'); return; }
        payload.mimeType = aiState.image.mimeType;
        payload.data = aiState.image.base64;
        if (caption) payload.caption = caption;
    } else if (aiState.mode === 'pdf') {
        if (!aiState.pdf) { showAIError('Adjunta un PDF'); return; }
        payload.mimeType = aiState.pdf.mimeType;
        payload.data = aiState.pdf.base64;
        if (caption) payload.caption = caption;
    } else if (aiState.mode === 'audio') {
        if (!aiState.audio) { showAIError('Grabá un audio antes de analizar'); return; }
        payload.mimeType = aiState.audio.mimeType;
        payload.data = aiState.audio.base64;
        if (caption) payload.caption = caption;
    }

    setAILoading(true);

    let result;
    try {
        result = await api.post('/ai/parse-single', payload);
    } catch (e) {
        console.error(e);
        setAILoading(false);
        showAIError('Error de red');
        return;
    }
    setAILoading(false);

    // api.js returns [] on HTTP error, the success shape is an object with `draft`.
    if (!result || Array.isArray(result) || result.error) {
        showAIError(result?.error || 'No se pudo analizar el input');
        return;
    }
    if (result.unreadable) {
        showAIError(result.reason || 'No se detecto un gasto. Probá con otra imagen o reformulando el texto.');
        return;
    }
    if (!result.draft) {
        showAIError('No se obtuvo un resultado');
        return;
    }

    aiState.draft = result.draft;
    aiState.matched = result.matched_recurrent || null;
    aiState.artifact = result.ai_artifact || null;
    closeAIModal();
    await openPaymentModalFromAI(result.draft, result.matched_recurrent || null);
}

function showAIError(msg) {
    const el = document.getElementById('ai-error');
    el.textContent = msg;
    el.classList.remove('hidden');
}

async function openPaymentModalFromAI(draft, matched) {
    const detectedCur = draft.detected_currency || 'ARS';
    // If AI detected a non-ARS currency, prefer matching it to a same-currency account.
    const matchByCurrency = accounts.find(a => a.currency === detectedCur);
    const synthetic = {
        title: draft.title || '',
        amount: draft.amount,
        due_ts: draft.date ? `${draft.date} 12:00:00` : null,
        expense_category_id: draft.suggested_category_id || null,
        card_id: null,
        account_id: matchByCurrency?.id || defaultAccount()?.id || null,
        currency: detectedCur,
        is_paid: draft.is_paid ? 1 : 0,
        description: draft.description || '',
        recipient: (draft.recipient && draft.recipient.name) ? draft.recipient : null,
    };
    const mode = aiState.mode;
    await openPaymentModal(synthetic);  // clears aiSourceTag + banner
    aiSourceTag = `ai-${mode}`;
    aiState.draft = draft;
    aiState.matched = matched;
    if (matched && draft.recurrent_match_id && draft.recurrent_match_confidence !== 'low') {
        showRecurrentBanner(matched, draft);
    }
}

function showRecurrentBanner(matched, draft) {
    document.getElementById('ai-rec-banner-title').textContent = matched.title;
    const draftAmount = Math.abs(Number(draft.amount));
    const recAmount = Math.abs(Number(matched.amount));
    const diffOk = isFinite(draftAmount) && draftAmount > 0;
    const diff = diffOk ? Math.abs(draftAmount - recAmount) : 0;
    const wrap = document.getElementById('ai-rec-update-amount-wrap');
    const cb = document.getElementById('ai-rec-update-amount');
    if (diffOk && diff > 0.01) {
        wrap.classList.remove('hidden');
        document.getElementById('ai-rec-amount-diff').textContent = `(${formatPrice(recAmount)} → ${formatPrice(draftAmount)})`;
        cb.checked = true;
    } else {
        wrap.classList.add('hidden');
        cb.checked = false;
    }
    document.getElementById('ai-recurrent-banner').classList.remove('hidden');
}

function hideRecurrentBanner() {
    const el = document.getElementById('ai-recurrent-banner');
    if (el) el.classList.add('hidden');
}

function dismissRecurrentBanner() {
    aiState.matched = null;
    hideRecurrentBanner();
}

async function markRecurrentPaidFromAI() {
    const matched = aiState.matched;
    const draft = aiState.draft;
    if (!matched || !draft) return;

    const updateAmount = document.getElementById('ai-rec-update-amount').checked;
    const paidTs = draft.date ? `${draft.date} 12:00:00` : null;

    const row = {
        action: 'mark_recurrent_paid',
        recurrent_id: matched.id,
        paid_ts: paidTs,
    };
    if (isFinite(Number(draft.amount)) && Number(draft.amount) > 0) {
        row.amount = Number(draft.amount);
    }
    if (updateAmount) row.update_recurrent_amount = true;

    try {
        const result = await api.post('/ai/commit-transactions', { rows: [row] });
        if (!result || Array.isArray(result) || result.error) {
            toast(result?.error || 'No se pudo marcar el recurrente', 'error');
            return;
        }
        toast('Recurrente marcado como pagado', 'success');
        closePaymentModal();
        await loadAll();
    } catch (e) {
        console.error(e);
        toast('Error de red', 'error');
    }
}

// ── Init ────────────────────────────────────────────────────────────
mangosAuth.ready.then(user => {
    if (!user) return;

    readUrlState();
    // Auto-open the AI input modal when arriving via the mobile FAB (?ai=1 or #ai).
    // MUST run before setStatusFilter, which calls writeUrlState() and would otherwise
    // strip the trigger param/hash before we get a chance to read it.
    openAIFromUrl();
    setStatusFilter(statusFilter);

    document.getElementById('month-prev').addEventListener('click', () => {
        viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() - 1, 1);
        writeUrlState();
        loadAll();
    });
    document.getElementById('month-next').addEventListener('click', () => {
        viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() + 1, 1);
        writeUrlState();
        loadAll();
    });

    document.querySelectorAll('#status-tabs button').forEach(btn => {
        btn.addEventListener('click', () => setStatusFilter(btn.dataset.status));
    });

    document.getElementById('filter-category').addEventListener('change', e => {
        categoryFilter = e.target.value;
        writeUrlState();
        renderSummary();
        renderPayments();
    });

    document.getElementById('payment-form').addEventListener('submit', submitPaymentForm);
    document.getElementById('pmt-category').addEventListener('change', updateCategorySwatch);
    document.getElementById('pmt-account').addEventListener('change', e => {
        const a = accountById(e.target.value);
        if (a) document.getElementById('pmt-currency').value = a.currency;
    });

    // AI modal: tab switching
    document.querySelectorAll('.ai-mode-tab').forEach(t => {
        t.addEventListener('click', () => setAIMode(t.dataset.mode));
    });

    // AI modal: image input
    document.getElementById('ai-image-input').addEventListener('change', async e => {
        const f = e.target.files?.[0];
        if (f) await handleAIImageFile(f);
    });
    const imgZone = document.getElementById('ai-image-zone');
    imgZone.addEventListener('click', () => document.getElementById('ai-image-input').click());
    imgZone.addEventListener('dragover', e => { e.preventDefault(); imgZone.classList.add('border-accent', 'bg-accent/5'); });
    imgZone.addEventListener('dragleave', () => imgZone.classList.remove('border-accent', 'bg-accent/5'));
    imgZone.addEventListener('drop', async e => {
        e.preventDefault();
        imgZone.classList.remove('border-accent', 'bg-accent/5');
        const f = e.dataTransfer.files?.[0];
        if (f) await handleAIImageFile(f);
    });

    // AI modal: PDF input
    document.getElementById('ai-pdf-input').addEventListener('change', async e => {
        const f = e.target.files?.[0];
        if (f) await handleAIPdfFile(f);
    });
    const pdfZone = document.getElementById('ai-pdf-zone');
    pdfZone.addEventListener('click', () => document.getElementById('ai-pdf-input').click());
    pdfZone.addEventListener('dragover', e => { e.preventDefault(); pdfZone.classList.add('border-accent', 'bg-accent/5'); });
    pdfZone.addEventListener('dragleave', () => pdfZone.classList.remove('border-accent', 'bg-accent/5'));
    pdfZone.addEventListener('drop', async e => {
        e.preventDefault();
        pdfZone.classList.remove('border-accent', 'bg-accent/5');
        const f = e.dataTransfer.files?.[0];
        if (f) await handleAIPdfFile(f);
    });

    // AI modal: audio recording controls
    document.getElementById('ai-audio-start').addEventListener('click', startAudioRecording);
    document.getElementById('ai-audio-stop').addEventListener('click', stopAudioRecording);
    document.getElementById('ai-audio-redo').addEventListener('click', cancelAudioRecording);

    // Ctrl/Cmd+Enter inside the AI text mode submits.
    document.getElementById('ai-text').addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            submitAIInput();
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (!document.getElementById('ai-input-modal').classList.contains('hidden')) closeAIModal();
        else if (!document.getElementById('payment-modal').classList.contains('hidden')) closePaymentModal();
        else if (!document.getElementById('payment-delete-modal').classList.contains('hidden')) closePaymentDelete();
    });

    window.addEventListener('hashchange', openAIFromUrl);

    loadAll();
});
</script>
