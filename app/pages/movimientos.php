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
        <a href="/capturar" class="btn btn-outline" title="Carga masiva con IA (imagenes o audio)">
            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.857 2.286L12 21l-2.286-6.857L3 12l6.857-2.286L12 3z"/>
            </svg>
            Carga masiva
        </a>
        <button class="btn btn-primary" onclick="openAIModal()" title="Agregar un gasto con IA">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            IA
        </button>
        <button class="btn btn-outline" onclick="openTransferModal()" title="Transferir entre cuentas">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Transferir
        </button>
        <button class="btn btn-outline" onclick="openPaymentModal()" title="Nuevo gasto manual">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Gasto
        </button>
        <button class="btn btn-outline" onclick="openIncomeModal()" title="Nuevo ingreso manual">
            <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Ingreso
        </button>
    </div>
</div>

<!-- Mobile action row (Carga masiva + Transferir + Gasto + Ingreso; AI lives on the bottom-nav FAB).
     Grid keeps all four buttons on-screen on narrow phones. -->
<div class="lg:hidden grid grid-cols-4 gap-2 mb-3">
    <a href="/capturar" class="inline-flex items-center justify-center gap-1 px-1 py-2 rounded-lg border border-border text-xs text-dark hover:bg-dark/5 active:scale-95 transition" title="Carga masiva con IA (imagenes o audio)">
        <svg class="w-4 h-4 text-accent flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.857 2.286L12 21l-2.286-6.857L3 12l6.857-2.286L12 3z"/>
        </svg>
        <span class="truncate">Carga</span>
    </a>
    <button type="button" onclick="openTransferModal()" class="inline-flex items-center justify-center gap-1 px-1 py-2 rounded-lg border border-border text-xs text-dark hover:bg-dark/5 active:scale-95 transition" title="Transferir entre cuentas">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
        </svg>
        <span>Transferir</span>
    </button>
    <button type="button" onclick="openPaymentModal()" class="inline-flex items-center justify-center gap-1 px-1 py-2 rounded-lg border border-border text-xs text-dark hover:bg-dark/5 active:scale-95 transition" title="Nuevo gasto manual">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Gasto</span>
    </button>
    <button type="button" onclick="openIncomeModal()" class="inline-flex items-center justify-center gap-1 px-1 py-2 rounded-lg border border-border text-xs text-dark hover:bg-dark/5 active:scale-95 transition" title="Nuevo ingreso manual">
        <svg class="w-4 h-4 text-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Ingreso</span>
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

    <!-- Account filter (hidden when only one account exists — see populateDropdowns) -->
    <select id="filter-account" class="input sm:max-w-[180px] hidden">
        <option value="">Todas las cuentas</option>
    </select>

    <!-- Category filter -->
    <select id="filter-category" class="input sm:max-w-[200px]">
        <option value="">Todas las categorias</option>
    </select>

    <!-- Search filter -->
    <div class="relative sm:max-w-[220px] flex-1">
        <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
        </svg>
        <input type="search" id="filter-search" class="input pl-8" placeholder="Buscar…" autocomplete="off">
    </div>
</div>

<!-- FX rate strip (shown once rates load; hidden when only ARS is in use) -->
<p id="fx-strip" class="hidden text-xs text-muted mb-3"></p>

<!-- Summary
     Mobile (<sm): Total full-width hero, Pagados + Pendientes split row, Fijos/Unicos hidden.
     sm-lg:        Total + Pagados + Pendientes in 3 cols, Fijos/Unicos hidden.
     lg+:          All 5 cards.
     Each amount is a stack (one line per currency present). -->
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

<!-- List — V3 skeleton mirrors the actual row layout (title row + subline row) -->
<div class="card !p-0">
    <div id="payments-list">
        <div class="py-3 px-3 border-b border-border">
            <div class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full skeleton flex-shrink-0">&nbsp;</span><span class="skeleton flex-1 h-4 max-w-[220px]">&nbsp;</span></div>
            <div class="flex items-end justify-between gap-3 mt-2 pl-[1.375rem]"><span class="skeleton h-3 w-32">&nbsp;</span><span class="skeleton h-4 w-20">&nbsp;</span></div>
        </div>
        <div class="py-3 px-3 border-b border-border">
            <div class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full skeleton flex-shrink-0">&nbsp;</span><span class="skeleton flex-1 h-4 max-w-[180px]">&nbsp;</span></div>
            <div class="flex items-end justify-between gap-3 mt-2 pl-[1.375rem]"><span class="skeleton h-3 w-40">&nbsp;</span><span class="skeleton h-4 w-16">&nbsp;</span></div>
        </div>
        <div class="py-3 px-3">
            <div class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full skeleton flex-shrink-0">&nbsp;</span><span class="skeleton flex-1 h-4 max-w-[200px]">&nbsp;</span></div>
            <div class="flex items-end justify-between gap-3 mt-2 pl-[1.375rem]"><span class="skeleton h-3 w-28">&nbsp;</span><span class="skeleton h-4 w-20">&nbsp;</span></div>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Form modal ─────────────────────────── -->
<div id="payment-modal" class="fixed inset-0 z-50 hidden bg-dark/40" data-bs-modal="closePaymentModal">
    <!-- bottom: var(--keyboard-inset) lifts the sheet above the on-screen keyboard on iOS;
         max-h: 85dvh shrinks the sheet with the visual viewport when the keyboard opens. -->
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-lg max-h-sheet overflow-y-auto safe-bottom">
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
                        <input type="text" id="pmt-amount" class="input" placeholder="1234,56" inputmode="decimal" data-amount required>
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
                        <label class="block text-sm font-medium mb-1.5">Tarjeta</label>
                        <input type="hidden" id="pmt-card" value="">
                        <button type="button" id="pmt-card-chip" class="picker-chip"></button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Cuenta</label>
                        <input type="hidden" id="pmt-account" value="">
                        <button type="button" id="pmt-account-chip" class="picker-chip"></button>
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

                <!-- Paid date — only meaningful when "Marcar como pagado" is on.
                     Lets the user back-date when the payment actually happened. -->
                <div id="pmt-paid-ts-wrap" class="hidden">
                    <label for="pmt-paid-ts" class="block text-sm font-medium mb-1.5">Fecha de pago</label>
                    <input type="date" id="pmt-paid-ts" class="input">
                </div>

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
<div id="payment-delete-modal" class="fixed inset-0 z-50 hidden bg-dark/40" data-bs-modal="closePaymentDelete">
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

<!-- ─────────────────────────── Income modal ─────────────────────────── -->
<!-- Slimmer than the payment modal: no card, no recurrent banner, no recipient.
     Income flow is intentionally simpler since the data model for incoming
     money has fewer dimensions. -->
<div id="income-modal" class="fixed inset-0 z-50 hidden bg-dark/40" data-bs-modal="closeIncomeModal">
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-lg max-h-sheet overflow-y-auto safe-bottom">
            <button type="button" onclick="closeIncomeModal()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <header class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h2 id="income-modal-title" class="text-lg font-semibold">Nuevo ingreso</h2>
                <button type="button" onclick="closeIncomeModal()" class="text-muted hover:text-dark p-1 -m-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <form id="income-form" class="p-5 space-y-4">
                <input type="hidden" id="inc-id">

                <div>
                    <label for="inc-title" class="block text-sm font-medium mb-1.5">Titulo <span class="text-danger">*</span></label>
                    <input type="text" id="inc-title" class="input" placeholder="Ej: Sueldo abril, Pago freelance Juan" maxlength="200" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="inc-amount" class="block text-sm font-medium mb-1.5">Monto <span class="text-danger">*</span></label>
                        <input type="text" id="inc-amount" class="input" placeholder="1234,56" inputmode="decimal" data-amount required>
                    </div>
                    <div>
                        <label for="inc-due" class="block text-sm font-medium mb-1.5">Fecha</label>
                        <input type="date" id="inc-due" class="input">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="inc-category" class="block text-sm font-medium mb-1.5">Categoria</label>
                        <div class="relative">
                            <span id="inc-category-swatch" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border border-border pointer-events-none"></span>
                            <select id="inc-category" class="input pl-9">
                                <option value="">Sin categoria</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="inc-currency" class="block text-sm font-medium mb-1.5">Moneda</label>
                        <select id="inc-currency" class="input">
                            <option value="ARS">ARS</option>
                            <option value="USD">USD</option>
                            <option value="USDT">USDT</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">Cuenta (donde se acredito)</label>
                    <input type="hidden" id="inc-account" value="">
                    <button type="button" id="inc-account-chip" class="picker-chip"></button>
                </div>

                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="inc-is-paid" class="w-4 h-4 rounded border-border text-accent focus:ring-accent/30" checked>
                    <span class="text-sm">Ya acreditado</span>
                </label>

                <div id="inc-paid-ts-wrap">
                    <label for="inc-paid-ts" class="block text-sm font-medium mb-1.5">Fecha de acreditacion</label>
                    <input type="date" id="inc-paid-ts" class="input">
                </div>

                <div>
                    <label for="inc-description" class="block text-sm font-medium mb-1.5">Descripcion</label>
                    <textarea id="inc-description" class="input min-h-[64px]" maxlength="500" rows="2"></textarea>
                </div>

                <!-- Original artifact (image / audio / PDF). Mirrors the
                     expense modal — populated in openIncomeModal. -->
                <details id="inc-artifact-details" class="hidden border border-border rounded-lg">
                    <summary class="px-4 py-2.5 text-sm font-medium cursor-pointer select-none flex items-center justify-between">
                        <span>Original <span id="inc-artifact-kind" class="text-xs text-muted ml-1"></span></span>
                        <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div id="inc-artifact-body" class="px-4 pb-4 pt-3 border-t border-border"></div>
                </details>

                <p id="inc-form-error" class="hidden text-sm text-danger">&nbsp;</p>

                <div class="flex items-center gap-2 pt-2">
                    <button type="button" id="inc-form-delete" onclick="deleteFromIncomeModal()"
                            class="hidden text-sm text-danger hover:bg-danger/5 px-3 py-2 rounded-lg transition-colors">
                        Eliminar
                    </button>
                    <div class="flex-1"></div>
                    <button type="button" onclick="closeIncomeModal()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" id="inc-form-submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─────────────────────────── Transfer modal ─────────────────────────── -->
<div id="transfer-modal" class="fixed inset-0 z-50 hidden bg-dark/40" data-bs-modal="closeTransferModal">
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-lg max-h-sheet overflow-y-auto safe-bottom">
            <button type="button" onclick="closeTransferModal()" class="w-full pt-2 pb-1 flex justify-center sm:hidden" aria-label="Cerrar">
                <div class="w-10 h-1 rounded-full bg-border"></div>
            </button>
            <header class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h2 id="transfer-modal-title" class="text-lg font-semibold">Nueva transferencia</h2>
                <button type="button" onclick="closeTransferModal()" class="text-muted hover:text-dark p-1 -m-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <form id="transfer-form" class="p-5 space-y-4">
                <input type="hidden" id="trf-id">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="trf-from" class="block text-sm font-medium mb-1.5">Desde <span class="text-danger">*</span></label>
                        <select id="trf-from" class="input" required></select>
                    </div>
                    <div>
                        <label for="trf-to" class="block text-sm font-medium mb-1.5">Hacia <span class="text-danger">*</span></label>
                        <select id="trf-to" class="input" required></select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="trf-sent" class="block text-sm font-medium mb-1.5">
                            Monto enviado <span class="text-danger">*</span>
                            <span id="trf-sent-cur" class="text-xs text-muted ml-1"></span>
                        </label>
                        <input type="text" id="trf-sent" class="input" placeholder="100,00" inputmode="decimal" data-amount required>
                    </div>
                    <div>
                        <label for="trf-received" class="block text-sm font-medium mb-1.5">
                            Monto recibido <span class="text-danger">*</span>
                            <span id="trf-received-cur" class="text-xs text-muted ml-1"></span>
                        </label>
                        <input type="text" id="trf-received" class="input" placeholder="85.000,00" inputmode="decimal" data-amount required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="trf-fee" class="block text-sm font-medium mb-1.5">
                            Comisión
                            <span id="trf-fee-cur" class="text-xs text-muted ml-1"></span>
                        </label>
                        <input type="text" id="trf-fee" class="input" placeholder="Opcional" inputmode="decimal" data-amount>
                    </div>
                    <div>
                        <label for="trf-fee-cat" class="block text-sm font-medium mb-1.5">Categoría comisión</label>
                        <select id="trf-fee-cat" class="input">
                            <option value="">Sin categoría</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="trf-description" class="block text-sm font-medium mb-1.5">Descripción</label>
                    <input type="text" id="trf-description" class="input" placeholder="Opcional" maxlength="200">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="trf-due" class="block text-sm font-medium mb-1.5">Fecha</label>
                        <input type="date" id="trf-due" class="input">
                    </div>
                    <label class="flex items-end pb-2 gap-2 cursor-pointer select-none">
                        <input type="checkbox" id="trf-is-paid" class="w-4 h-4 rounded border-border text-accent focus:ring-accent/30" checked>
                        <span class="text-sm">Marcar como pagada</span>
                    </label>
                </div>

                <div class="flex justify-between items-center gap-2 pt-2">
                    <button type="button" id="trf-delete-btn" onclick="confirmTransferDelete()" class="btn btn-ghost text-danger hidden">
                        Eliminar
                    </button>
                    <div class="flex justify-end gap-2 ml-auto">
                        <button type="button" onclick="closeTransferModal()" class="btn btn-ghost">Cancelar</button>
                        <button type="submit" id="trf-submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─────────────────────────── AI input modal ─────────────────────────── -->
<div id="ai-input-modal" class="fixed inset-0 z-50 <?= $openAIOnLoad ? '' : 'hidden ' ?>bg-dark/40" data-bs-modal="closeAIModal">
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4"
         style="bottom: var(--keyboard-inset, 0px);">
        <div class="bg-white rounded-t-2xl sm:rounded-xl border-t sm:border border-border w-full sm:max-w-lg max-h-sheet overflow-y-auto safe-bottom">
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
                <!-- Kind toggle (Gasto / Ingreso) — drives the prompt + which form opens after parse -->
                <div class="flex gap-1 p-1 bg-dark/5 rounded-lg">
                    <button type="button" data-kind="expense" class="ai-kind-tab flex-1 text-sm py-2 rounded-md transition-colors bg-white shadow-sm text-dark font-medium">Gasto</button>
                    <button type="button" data-kind="income" class="ai-kind-tab flex-1 text-sm py-2 rounded-md transition-colors text-muted">Ingreso</button>
                </div>

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

                <div class="flex items-center gap-2 pt-2">
                    <!-- Escape hatch: bail out of AI and open the manual form pre-empty.
                         Useful when the user already knows the data and AI is overkill. -->
                    <button type="button" onclick="switchAIToManual()" class="text-sm text-muted hover:text-dark underline-offset-2 hover:underline transition-colors">
                        Cargar manualmente
                    </button>
                    <div class="flex-1"></div>
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
const ICON_RECURRENT = 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15';
let payments = [];
let categories = [];        // expense categories
let incomeCategories = [];  // income categories (separate taxonomy)
let cards = [];
let accounts = [];
let editingId = null;
let pendingDeleteId = null;

// AI input state. aiSourceTag is non-null while the payment-modal is pre-filled
// from /api/ai/parse-single — used to tag the resulting payment with source=ai-*.
let aiSourceTag = null;
let aiState = { mode: 'text', kind: 'expense', image: null, pdf: null, audio: null, draft: null, matched: null, artifact: null };

// MediaRecorder runtime (only populated while a recording is in progress or just-finished).
let aiAudio = { recorder: null, stream: null, chunks: [], startTime: 0, mimeType: null, timerInterval: null, autoStopTimeout: null };

// View state — initialized from URL query string, persisted on every change.
let viewMonth = startOfMonth(new Date());
let statusFilter = 'all';   // all | paid | unpaid
let categoryFilter = '';
let accountFilter = '';     // account_id; '' = all accounts
let searchQuery = '';       // free-text substring search (case-insensitive)
// Per-row currency override. id → currency code. Absent = native (default).
// Cycled by the row's currency action; lost on reload.
const currencyOverrides = new Map();
// Single row open at a time. For transfers the id is the transfer_id; for
// regular payments it's the transaction id. Synced to ?open=<id> in the URL.
let expandedId = null;

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
    if (p.has('account')) accountFilter = p.get('account');
    if (p.has('q')) searchQuery = p.get('q');
    if (p.has('open')) expandedId = p.get('open');
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
    if (accountFilter) params.set('account', accountFilter);
    if (searchQuery) params.set('q', searchQuery);
    if (expandedId) params.set('open', expandedId);
    const qs = params.toString();
    history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
}

// Toggle the expansion for a given row. One row open at a time. After the
// re-render, the newly-open row is scrolled into view (no-op when collapsing).
function toggleExpanded(id) {
    expandedId = expandedId === id ? null : id;
    writeUrlState();
    renderPayments();
    if (expandedId) scrollToExpanded();
}

function scrollToExpanded() {
    if (!expandedId) return;
    requestAnimationFrame(() => {
        const el = document.querySelector(`[data-row-id="${CSS.escape(expandedId)}"]`);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
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
function incomeCategoryById(id) { return incomeCategories.find(c => c.id === id); }
function cardById(id)     { return cards.find(c => c.id === id); }
function accountById(id)  { return accounts.find(a => a.id === id); }
function defaultAccount() { return accounts.find(a => Number(a.is_default) === 1) || accounts[0] || null; }
// Resolve the right category for a transaction regardless of kind.
function categoryForRow(p) {
    return p.kind === 'income'
        ? incomeCategoryById(p.income_category_id)
        : categoryById(p.expense_category_id);
}

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

    const [pays, cats, incCats, crds, accs] = await Promise.all([
        api.get('/transactions', { start_date: range.start, end_date: range.end }),
        api.get('/categories'),
        api.get('/categories', { kind: 'income' }),
        api.get('/cards'),
        api.get('/accounts'),
    ]);

    payments = Array.isArray(pays) ? pays : [];
    categories = Array.isArray(cats) ? cats : [];
    incomeCategories = Array.isArray(incCats) ? incCats : [];
    cards = Array.isArray(crds) ? crds : [];
    accounts = Array.isArray(accs) ? accs : [];
    if (pays && pays.error) toast(`No se pudieron cargar los movimientos: ${pays.error}`, 'error');

    populateDropdowns();
    document.getElementById('filter-category').value = categoryFilter;
    document.getElementById('filter-account').value = accountFilter;
    renderSummary();
    renderPayments();
    // If we landed via /movimientos?open=<id>, scroll to that row once the
    // list is in the DOM. Subsequent toggles handle their own scroll.
    scrollToExpanded();
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

    // Account/card pickers read from window.mangosPicker; refresh its data and
    // re-render whichever chip is currently bound.
    if (window.mangosPicker) {
        mangosPicker.setData({ accounts, cards });
        mangosPicker.updateChip(document.getElementById('pmt-card-chip'));
        mangosPicker.updateChip(document.getElementById('pmt-account-chip'));
    }

    // Filter bar (preserve current selection)
    const filterSel = document.getElementById('filter-category');
    const prev = filterSel.value;
    filterSel.textContent = '';
    filterSel.appendChild(makeOption('', 'Todas las categorias'));
    categories.forEach(c => filterSel.appendChild(makeOption(c.id, c.name)));
    filterSel.value = prev;

    // Account filter — hidden when there's only one account, since "filter
    // by the only account I have" is a no-op and adds visual noise.
    const acctSel = document.getElementById('filter-account');
    const acctPrev = acctSel.value;
    acctSel.textContent = '';
    acctSel.appendChild(makeOption('', 'Todas las cuentas'));
    accounts.forEach(a => acctSel.appendChild(makeOption(a.id, a.name)));
    acctSel.value = acctPrev;
    acctSel.classList.toggle('hidden', accounts.length <= 1);
}

function makeOption(value, label) {
    const o = document.createElement('option');
    o.value = value;
    o.textContent = label;
    return o;
}

// Concatenated lowercase string of every visible field, used for substring search.
// Built lazily per row inside applyFilters; if searchQuery is empty we skip it.
function paymentSearchableText(p) {
    const parts = [
        p.title,
        p.description,
        p.currency,
        labelForSource(p.source),
        categoryForRow(p)?.name,
    ];
    const card = cardById(p.card_id);
    if (card) {
        parts.push(card.name);
        if (card.last_four) parts.push(card.last_four);
    }
    const account = accountById(p.account_id);
    if (account) parts.push(account.name);
    if (p.amount != null) {
        const abs = Math.abs(Number(p.amount));
        parts.push(formatPrice(abs), String(abs));
    }
    if (p.due_ts) {
        parts.push(p.due_ts, formatDate(p.due_ts), formatDateLong(p.due_ts));
    }
    if (p.paid_ts) {
        parts.push(p.paid_ts, formatDate(p.paid_ts), formatDateLong(p.paid_ts));
    }
    return parts.filter(Boolean).join(' ').toLowerCase();
}

function applyFilters(list) {
    const q = searchQuery.trim().toLowerCase();
    return list.filter(p => {
        if (statusFilter === 'paid' && p.is_paid != 1) return false;
        if (statusFilter === 'unpaid' && p.is_paid == 1) return false;
        if (categoryFilter && p.expense_category_id !== categoryFilter) return false;
        // Account filter: matches per-leg, so transfers involving the chosen
        // account show via either leg (the leg-grouping in renderPayments
        // pulls the full transfer back together once any leg matches).
        if (accountFilter && p.account_id !== accountFilter) return false;
        if (q && !paymentSearchableText(p).includes(q)) return false;
        return true;
    });
}

// Stable display order for stacked currency rows; unknown codes appended.
const CURRENCY_ORDER = ['ARS', 'USD', 'USDT'];

function emptyBucket() {
    return { total: 0, paid: 0, unpaid: 0, fijoTotal: 0, fijoCount: 0,
             unicoTotal: 0, unicoCount: 0, paidCount: 0, unpaidCount: 0, count: 0 };
}

// Buckets are built off the same filtered set the list renders, but transfer
// legs are excluded (they move money, they don't spend it). Income is also
// excluded so the spend rollup stays accurate; income totals will get their
// own treatment when the dashboard/analytics rework lands (#56).
function summaryBuckets() {
    const filtered = applyFilters(payments).filter(p => p.kind !== 'transfer' && p.kind !== 'income');
    const buckets = {};
    filtered.forEach(p => {
        const cur = p.currency || 'ARS';
        const b = buckets[cur] || (buckets[cur] = emptyBucket());
        const a = Math.abs(Number(p.amount));
        b.total += a; b.count++;
        if (p.is_paid == 1) { b.paid += a; b.paidCount++; }
        else { b.unpaid += a; b.unpaidCount++; }
        if (p.transaction_type === 'recurrent') { b.fijoTotal += a; b.fijoCount++; }
        else { b.unicoTotal += a; b.unicoCount++; }
    });
    return buckets;
}

function sortedCurrencyCodes(buckets) {
    return Object.keys(buckets).sort((a, b) => {
        const ai = CURRENCY_ORDER.indexOf(a), bi = CURRENCY_ORDER.indexOf(b);
        return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi);
    });
}

// Hero is the ARS grand total (everything converted via fx). Below it,
// secondary line shows the same total expressed in USD + USDT. When the
// bucket spans more than one currency, a third tiny line lists the pure
// per-currency amounts so the conversions can be reconciled.
function renderStackedAmount(elId, buckets, key, fallbackCur) {
    const el = document.getElementById(elId);
    el.textContent = '';
    const codes = sortedCurrencyCodes(buckets);
    if (codes.length === 0) {
        el.textContent = formatPrice(0, fallbackCur || 'ARS');
        return;
    }

    // Sum to ARS, then divide by target rate for USD / USDT.
    const totalArs = codes.reduce((a, c) => a + buckets[c][key] * fx.rateFor(c), 0);
    const isPureSingle = (cur) => codes.length === 1 && codes[0] === cur;

    const hero = document.createElement('span');
    hero.className = 'block';
    hero.textContent = (isPureSingle('ARS') ? '' : '≈ ') + formatPrice(totalArs, 'ARS');
    el.appendChild(hero);

    const convParts = [];
    ['USD', 'USDT'].forEach(target => {
        const r = fx.rates[target];
        if (!r) return;  // rate not loaded yet — skip; renderSummary re-runs after fx.ready
        const v = totalArs / r;
        if (!Number.isFinite(v)) return;
        convParts.push((isPureSingle(target) ? '' : '≈ ') + formatPrice(v, target));
    });
    if (convParts.length) {
        const conv = document.createElement('span');
        conv.className = 'block text-sm font-semibold text-muted -mt-0.5';
        conv.textContent = convParts.join(' · ');
        el.appendChild(conv);
    }

    if (codes.length > 1) {
        const pure = document.createElement('span');
        pure.className = 'block text-xs text-muted -mt-0.5';
        pure.textContent = codes.map(c => formatPrice(buckets[c][key], c)).join(' · ');
        el.appendChild(pure);
    }
}

// Sums a per-currency field across all buckets, used for the count subline.
function sumAcross(buckets, key) {
    return Object.values(buckets).reduce((acc, b) => acc + (b[key] || 0), 0);
}

function renderFxStrip() {
    const el = document.getElementById('fx-strip');
    const parts = [];
    ['USD', 'USDT'].forEach(code => {
        const r = fx.rateFor(code);
        if (r && r !== 1) parts.push(`${code} ${formatPrice(r, 'ARS')}`);
    });
    if (parts.length === 0) {
        el.classList.add('hidden');
        el.textContent = '';
        return;
    }
    const age = fx.relativeAge();
    el.textContent = `Cotización: ${parts.join(' · ')}${age ? ' · ' + age : ''}`;
    el.classList.remove('hidden');
}

function renderSummary() {
    const buckets = summaryBuckets();
    const plural = (n) => `${n} movimiento${n === 1 ? '' : 's'}`;

    renderStackedAmount('sum-total',  buckets, 'total');
    renderStackedAmount('sum-paid',   buckets, 'paid');
    renderStackedAmount('sum-unpaid', buckets, 'unpaid');
    renderStackedAmount('sum-fijo',   buckets, 'fijoTotal');
    renderStackedAmount('sum-unico',  buckets, 'unicoTotal');

    document.getElementById('sum-total-count').textContent  = plural(sumAcross(buckets, 'count'));
    document.getElementById('sum-paid-count').textContent   = plural(sumAcross(buckets, 'paidCount'));
    document.getElementById('sum-unpaid-count').textContent = plural(sumAcross(buckets, 'unpaidCount'));
    document.getElementById('sum-fijo-count').textContent   = plural(sumAcross(buckets, 'fijoCount'));
    document.getElementById('sum-unico-count').textContent  = plural(sumAcross(buckets, 'unicoCount'));
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

    // Group transfer legs by transfer_id so we render one composite row per
    // transfer instead of two/three separate rows. The first leg encountered
    // (in date-desc order) becomes the anchor; later legs are skipped.
    // We pull legs from the unfiltered `payments` so a transfer never shows as
    // a half-pair if one leg falls outside the active filters.
    const renderedTransfers = new Set();
    const itemsToRender = [];
    filtered.forEach(p => {
        if (p.transfer_id) {
            if (renderedTransfers.has(p.transfer_id)) return;
            renderedTransfers.add(p.transfer_id);
            const legs = payments.filter(x => x.transfer_id === p.transfer_id);
            itemsToRender.push({ kind: 'transfer', legs });
        } else {
            itemsToRender.push({ kind: 'payment', p });
        }
    });

    itemsToRender.forEach((item, i) => {
        const isLast = i === itemsToRender.length - 1;
        if (item.kind === 'transfer') {
            list.appendChild(buildTransferRow(item.legs, isLast));
        } else {
            const p = item.p;
            const isPaid = p.is_paid == 1;
            const dueDate = p.due_ts ? new Date(p.due_ts.replace(' ', 'T')) : null;
            const isOverdue = !isPaid && dueDate && dueDate < today;
            list.appendChild(buildPaymentRow(p, isPaid, isOverdue, isLast));
        }
    });
}

const ICON_TRANSFER = 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4';

// ── V3 row layout: title on its own line (full width), subline + amount/badge
//    below. Tap the row to expand inline (close modal-on-click behavior). The
//    "⋮" trigger short-circuits the row click via stopPropagation.

function buildTransferRow(legs, isLast) {
    const wrap = document.createElement('div');
    const transferId = legs[0].transfer_id;
    wrap.dataset.rowId = transferId;
    if (!isLast) wrap.classList.add('border-b', 'border-border');

    const fromLeg = legs.find(l => l.kind === 'transfer' && Number(l.amount) < 0);
    const toLeg   = legs.find(l => l.kind === 'transfer' && Number(l.amount) > 0);
    const feeLeg  = legs.find(l => l.kind === 'fee');
    const fromAcct = fromLeg ? accountById(fromLeg.account_id) : null;
    const toAcct   = toLeg   ? accountById(toLeg.account_id)   : null;

    const header = document.createElement('div');
    header.className = 'py-3 px-3 cursor-pointer hover:bg-dark/5 active:bg-dark/5 transition-colors';
    header.addEventListener('click', () => toggleExpanded(transferId));

    // Title row (icon-tile + title + ⋮)
    const titleRow = document.createElement('div');
    titleRow.className = 'flex items-center gap-3';

    const tile = document.createElement('div');
    tile.className = 'h-7 w-7 rounded-lg flex items-center justify-center flex-shrink-0 bg-accent/10 text-accent';
    tile.title = 'Transferencia';
    tile.appendChild(svgIcon(ICON_TRANSFER, 'w-3.5 h-3.5'));
    titleRow.appendChild(tile);

    const titleText = document.createElement('p');
    titleText.className = 'flex-1 text-[15px] font-medium truncate';
    titleText.textContent = `${fromAcct?.name || '?'} → ${toAcct?.name || '?'}`;
    titleRow.appendChild(titleText);

    titleRow.appendChild(makeTransferActions(transferId));
    header.appendChild(titleRow);

    // Subline + amounts row, indented to align with the title text
    const subRow = document.createElement('div');
    subRow.className = 'flex items-end justify-between gap-3 mt-1 pl-10';

    const subParts = [];
    const anchor = legs[0];
    if (anchor.is_paid == 1 && anchor.paid_ts) subParts.push(`Pagado: ${formatDate(anchor.paid_ts)}`);
    else if (anchor.due_ts) subParts.push(formatDate(anchor.due_ts));
    if (feeLeg) {
        subParts.push(`Comisión ${formatPrice(Math.abs(feeLeg.amount), feeLeg.currency)}`);
    }
    const sub = document.createElement('p');
    sub.className = 'text-xs text-muted truncate flex-1 min-w-0';
    sub.textContent = subParts.join(' · ') || ' ';
    subRow.appendChild(sub);

    const amounts = document.createElement('div');
    amounts.className = 'flex flex-col items-end gap-0.5 flex-shrink-0';
    const out = document.createElement('span');
    out.className = 'text-[13px] font-semibold tabular-nums text-danger leading-tight';
    if (fromLeg) out.textContent = '−' + formatPrice(Math.abs(fromLeg.amount), fromLeg.currency);
    amounts.appendChild(out);
    const inn = document.createElement('span');
    inn.className = 'text-[13px] font-semibold tabular-nums text-success leading-tight';
    if (toLeg) inn.textContent = '+' + formatPrice(Math.abs(toLeg.amount), toLeg.currency);
    amounts.appendChild(inn);
    subRow.appendChild(amounts);
    header.appendChild(subRow);

    wrap.appendChild(header);

    if (expandedId === transferId) {
        wrap.appendChild(buildTransferExpansion(legs, fromLeg, toLeg, feeLeg, fromAcct, toAcct));
    }
    return wrap;
}

function buildPaymentRow(p, isPaid, isOverdue, isLast) {
    const wrap = document.createElement('div');
    wrap.dataset.rowId = p.id;
    if (!isLast) wrap.classList.add('border-b', 'border-border');

    const isRecurrent = p.transaction_type === 'recurrent';
    const cat = categoryForRow(p);
    const catColor = cat?.color || '#8C857D';

    const header = document.createElement('div');
    header.className = 'py-3 px-3 cursor-pointer hover:bg-dark/5 active:bg-dark/5 transition-colors';
    header.addEventListener('click', () => toggleExpanded(p.id));

    // Title row
    const titleRow = document.createElement('div');
    titleRow.className = 'flex items-center gap-3';

    // Recurrent instance: bigger icon-tile tinted with the category color so
    // both "this is a fijo" and "this is category X" read at a glance.
    let lead;
    if (isRecurrent) {
        lead = document.createElement('div');
        lead.className = 'h-7 w-7 rounded-lg flex items-center justify-center flex-shrink-0';
        lead.style.backgroundColor = hexToRgba(catColor, 0.12) || 'rgba(140,133,125,0.1)';
        lead.style.color = catColor;
        lead.title = (cat?.name ? `${cat.name} · ` : '') + 'Movimiento de gasto fijo';
        lead.appendChild(svgIcon(ICON_RECURRENT, 'w-3.5 h-3.5'));
    } else {
        lead = document.createElement('div');
        lead.className = 'h-2.5 w-2.5 rounded-full flex-shrink-0';
        lead.style.backgroundColor = catColor;
        lead.title = cat?.name || 'Sin categoria';
    }
    titleRow.appendChild(lead);

    const titleText = document.createElement('p');
    titleText.className = 'flex-1 text-[15px] font-medium truncate';
    titleText.textContent = p.title;
    titleRow.appendChild(titleText);
    if (p.ai_artifact_path) titleRow.appendChild(buildArtifactClipIcon());
    titleRow.appendChild(makeRowActions(p));
    header.appendChild(titleRow);

    // Subline + amount row, indented to match the title text start
    const subRow = document.createElement('div');
    subRow.className = `flex items-end justify-between gap-3 mt-1 ${isRecurrent ? 'pl-10' : 'pl-[1.375rem]'}`;

    const subParts = [];
    if (isPaid && p.paid_ts) subParts.push(`Pagado: ${formatDate(p.paid_ts)}`);
    else if (p.due_ts) subParts.push(formatDate(p.due_ts));
    const card = cardById(p.card_id);
    if (card) subParts.push(card.name + (card.last_four ? ` ····${card.last_four}` : ''));
    const acct = accountById(p.account_id);
    if (acct && acct.is_default != 1) subParts.push(acct.name);
    const sourceLabel = labelForSource(p.source);
    if (sourceLabel) subParts.push(sourceLabel);

    const sub = document.createElement('p');
    sub.className = 'text-xs text-muted truncate flex-1 min-w-0';
    sub.textContent = subParts.join(' · ') || ' ';
    subRow.appendChild(sub);

    const right = document.createElement('div');
    right.className = 'flex items-center gap-2 flex-shrink-0';
    right.appendChild(buildAmountStack(p));

    const badge = document.createElement('button');
    badge.type = 'button';
    let badgeColor;
    const isIncomeRow = p.kind === 'income';
    if (isPaid)        { badgeColor = 'bg-success/10 text-success'; badge.textContent = isIncomeRow ? 'Acreditado' : 'Pagado'; }
    else if (isOverdue){ badgeColor = 'bg-danger/10 text-danger';   badge.textContent = 'Vencido'; }
    else               { badgeColor = 'bg-muted/10 text-muted';     badge.textContent = 'Pendiente'; }
    badge.className = `inline-block font-semibold tracking-wide uppercase rounded cursor-pointer hover:opacity-80 active:scale-95 transition disabled:opacity-50 disabled:cursor-wait whitespace-nowrap text-[10px] px-2 py-0.5 ${badgeColor}`;
    badge.title = isPaid ? 'Marcar como pendiente' : 'Marcar como pagado';
    badge.addEventListener('click', e => {
        e.stopPropagation();
        togglePaymentPaid(p, badge);
    });
    right.appendChild(badge);
    subRow.appendChild(right);
    header.appendChild(subRow);

    wrap.appendChild(header);

    if (expandedId === p.id) {
        wrap.appendChild(buildPaymentExpansion(p));
    }
    return wrap;
}

// ── Expansion blocks: gray panel with key/value rows ────────────────
// No action buttons inside; all actions live in the "⋮" menu.

function buildExpansionPanel() {
    const exp = document.createElement('div');
    exp.className = 'border-t border-border bg-muted/[.04] px-4 py-3';
    return exp;
}

function appendKv(panel, key, value) {
    if (value == null || value === '') return;
    const r = document.createElement('div');
    r.className = 'flex items-baseline justify-between gap-3 py-1 text-[13px]';
    const k = document.createElement('span');
    k.className = 'text-muted flex-shrink-0';
    k.textContent = key;
    const v = document.createElement('span');
    v.className = 'text-dark font-medium text-right break-words min-w-0';
    v.textContent = value;
    r.appendChild(k);
    r.appendChild(v);
    panel.appendChild(r);
}

function buildPaymentExpansion(p) {
    const exp = buildExpansionPanel();

    if (p.description) appendKv(exp, 'Descripción', p.description);

    const cat = categoryForRow(p);
    if (cat) appendKv(exp, 'Categoría', cat.name);

    const acct = accountById(p.account_id);
    if (acct) appendKv(exp, 'Cuenta', acct.name);

    const card = cardById(p.card_id);
    if (card) appendKv(exp, 'Tarjeta', card.name + (card.last_four ? ` ····${card.last_four}` : ''));

    const native = p.currency || 'ARS';
    if (native !== 'ARS') {
        const arsEquiv = fx.toArs(Math.abs(Number(p.amount) || 0), native);
        appendKv(exp, 'Equivalente', '≈ ' + formatPrice(arsEquiv, 'ARS'));
    }

    const sourceLabel = labelForSource(p.source);
    if (sourceLabel) appendKv(exp, 'Origen', sourceLabel);

    if (p.paid_ts) appendKv(exp, 'Pagado el', formatDateLong(p.paid_ts));
    else if (p.due_ts) appendKv(exp, 'Vence', formatDateLong(p.due_ts));

    if (p.ai_artifact_path) appendArtifactRow(exp, p);

    return exp;
}

// Small paperclip icon shown on the row title when a transaction has an
// associated original (image / audio / PDF). Universal "attachment" symbol —
// keeps the row tidy regardless of artifact kind.
function buildArtifactClipIcon() {
    const ICON_CLIP = 'M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13';
    const wrap = document.createElement('span');
    wrap.className = 'flex-shrink-0 text-muted';
    wrap.title = 'Tiene adjunto';
    wrap.appendChild(svgIcon(ICON_CLIP, 'w-3 h-3'));
    return wrap;
}

// Expansion-panel row: image thumbnail / audio chip / PDF chip → opens the
// edit modal where the full preview lives. Click stops propagation so the
// row doesn't collapse on tap.
function appendArtifactRow(panel, p) {
    const r = document.createElement('div');
    r.className = 'flex items-start justify-between gap-3 py-1 text-[13px]';

    const k = document.createElement('span');
    k.className = 'text-muted flex-shrink-0 pt-1';
    k.textContent = 'Adjunto';
    r.appendChild(k);

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'flex items-center gap-2.5 p-1.5 border border-border rounded-lg bg-light hover:bg-dark/5 transition-colors max-w-[60%]';
    btn.title = 'Ver original';

    const mime = (p.ai_artifact_mime || '').toLowerCase();
    // Image artifacts get a direct lightbox (one tap → fullscreen). Audio/PDF
    // route through the edit modal where the player / pdf link already lives.
    const openInModal = e => {
        e.stopPropagation();
        if (p.kind === 'income') openIncomeModal(p);
        else openPaymentModal(p);
    };

    const appendStackedLabel = (top, bottomText, bottomClass) => {
        const wrap = document.createElement('span');
        wrap.className = 'flex flex-col items-start text-[11px] leading-tight';
        const a = document.createElement('span');
        a.className = 'text-muted';
        a.textContent = top;
        wrap.appendChild(a);
        const b = document.createElement('span');
        b.className = bottomClass;
        b.textContent = bottomText;
        wrap.appendChild(b);
        btn.appendChild(wrap);
    };

    const appendInlineLabel = (text, sub) => {
        const main = document.createElement('span');
        main.className = 'text-[12px] font-medium text-dark';
        main.textContent = text;
        btn.appendChild(main);
        if (sub) {
            const s = document.createElement('span');
            s.className = 'text-[11px] text-muted';
            s.textContent = sub;
            btn.appendChild(s);
        }
    };

    if (mime.startsWith('image/')) {
        const img = document.createElement('img');
        img.alt = 'Original';
        img.className = 'w-12 h-9 object-cover rounded flex-shrink-0 bg-dark/5';
        btn.appendChild(img);
        // Endpoint requires bearer auth — fetch as blob and use an object URL
        // so the <img> can render without sending the Authorization header.
        // Reuse the same blob URL for the lightbox: avoids a second fetch.
        let blobUrl = null;
        api.getBlobUrl('/transactions/artifact', { id: p.id }).then(b => {
            if (b) { blobUrl = b; img.src = b; }
        });
        btn.addEventListener('click', e => {
            e.stopPropagation();
            if (blobUrl) openImageLightbox(blobUrl);
            else openInModal(e); // fall back to modal if blob hasn't arrived
        });
        appendStackedLabel('Imagen', 'Ampliar', 'text-accent font-medium');
    } else if (mime.startsWith('audio/')) {
        const ICON_PLAY = 'M5 3l14 9-14 9V3z';
        btn.appendChild(svgIcon(ICON_PLAY, 'w-3.5 h-3.5 text-accent'));
        appendInlineLabel('Audio', '· Reproducir');
        btn.addEventListener('click', openInModal);
    } else if (mime === 'application/pdf') {
        const ICON_PDF = 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z';
        btn.appendChild(svgIcon(ICON_PDF, 'w-3.5 h-3.5 text-accent'));
        appendInlineLabel('PDF', '· Abrir');
        btn.addEventListener('click', openInModal);
    } else {
        appendInlineLabel('Archivo · Abrir', null);
        btn.addEventListener('click', openInModal);
    }

    r.appendChild(btn);
    panel.appendChild(r);
}

function buildTransferExpansion(legs, fromLeg, toLeg, feeLeg, fromAcct, toAcct) {
    const exp = buildExpansionPanel();

    if (fromAcct) appendKv(exp, 'De', `${fromAcct.name}${fromLeg ? ' · ' + (fromLeg.currency || 'ARS') : ''}`);
    if (fromLeg) {
        const out = '−' + formatPrice(Math.abs(fromLeg.amount), fromLeg.currency);
        const fromCur = fromLeg.currency || 'ARS';
        const arsEq = fromCur === 'ARS' ? '' : ' · ≈ ' + formatPrice(fx.toArs(Math.abs(fromLeg.amount), fromCur), 'ARS');
        appendKv(exp, 'Salida', out + arsEq);
    }

    if (toAcct) appendKv(exp, 'A', `${toAcct.name}${toLeg ? ' · ' + (toLeg.currency || 'ARS') : ''}`);
    if (toLeg) {
        const inn = '+' + formatPrice(Math.abs(toLeg.amount), toLeg.currency);
        const toCur = toLeg.currency || 'ARS';
        const arsEq = toCur === 'ARS' ? '' : ' · ≈ ' + formatPrice(fx.toArs(Math.abs(toLeg.amount), toCur), 'ARS');
        appendKv(exp, 'Entrada', inn + arsEq);
    }

    if (feeLeg) {
        const feeCur = feeLeg.currency || 'ARS';
        const arsEq = feeCur === 'ARS' ? '' : ' · ≈ ' + formatPrice(fx.toArs(Math.abs(feeLeg.amount), feeCur), 'ARS');
        appendKv(exp, 'Comisión', formatPrice(Math.abs(feeLeg.amount), feeCur) + arsEq);
    }

    // Implied FX rate when the two legs are in different currencies.
    if (fromLeg && toLeg) {
        const fc = fromLeg.currency || 'ARS', tc = toLeg.currency || 'ARS';
        if (fc !== tc) {
            const fa = Math.abs(Number(fromLeg.amount));
            const ta = Math.abs(Number(toLeg.amount));
            if (fa > 0 && ta > 0) {
                const rate = ta / fa;
                appendKv(exp, 'Tipo de cambio', `${formatPrice(rate, tc)} / ${fc}`);
            }
        }
    }

    const anchor = legs[0];
    if (anchor.due_ts) appendKv(exp, 'Fecha', formatDateLong(anchor.due_ts));

    return exp;
}

// Transfer's 3-dots menu: edit + delete (no currency switcher — each leg is
// already shown in its own native currency).
function makeTransferActions(transferId) {
    return rowMenu.trigger(anchor => {
        rowMenu.open(anchor, [{
            items: [
                { label: 'Editar',   onClick: () => openTransferModal(transferId) },
                { label: 'Eliminar', danger: true, onClick: () => deleteTransferDirect(transferId) },
            ],
        }]);
    });
}

async function deleteTransferDirect(transferId) {
    if (!confirm('¿Eliminar esta transferencia y todos sus movimientos?')) return;
    try {
        const result = await api.del('/transfers', { id: transferId });
        if (!result || result.error) {
            toast(result?.error || 'No se pudo eliminar', 'error');
            return;
        }
        toast('Transferencia eliminada', 'success');
        if (expandedId === transferId) {
            expandedId = null;
            writeUrlState();
        }
        await loadAll();
    } catch (err) {
        toast('Error de red', 'error');
    }
}

// Right-aligned amount, single line. Defaults to the row's native currency;
// when a per-row override is set, shows the converted value prefixed with "≈"
// so the user can tell at a glance it isn't an exact amount.
function buildAmountStack(p) {
    const native = p.currency || 'ARS';
    const display = currencyOverrides.get(p.id) || native;
    const nativeAmt = Math.abs(Number(p.amount) || 0);
    const value = display === native ? nativeAmt : fx.convert(nativeAmt, native, display);

    const span = document.createElement('span');
    const isIncome = p.kind === 'income';
    // Income reads as positive cashflow — green text + leading "+" sets it
    // apart from expenses without needing a separate column.
    const colorCls = isIncome ? ' text-success' : '';
    span.className = 'text-[15px] sm:text-sm font-semibold tabular-nums' + colorCls;
    const prefix = display === native ? (isIncome ? '+' : '') : (isIncome ? '+≈ ' : '≈ ');
    span.textContent = prefix + formatPrice(value, display);
    return span;
}

// Switches the row's display currency. Stores an override unless we're going
// back to native (in which case the override is cleared).
function setRowDisplay(p, currency) {
    const native = p.currency || 'ARS';
    if (currency === native) currencyOverrides.delete(p.id);
    else currencyOverrides.set(p.id, currency);
    renderPayments();
}

// Builds the "..." trigger + menu sections for a payment row. Uses the shared
// rowMenu module — see app/assets/js/row-menu.js.
function makeRowActions(p) {
    return rowMenu.trigger(anchor => {
        const native = p.currency || 'ARS';
        const display = currencyOverrides.get(p.id) || native;
        const nativeAmt = Math.abs(Number(p.amount) || 0);

        // Currency items: every code except the one currently displayed.
        // formatPrice already includes the code/symbol so the label doesn't
        // repeat it ("USD 5,00" rather than "Mostrar en USD · USD 5,00").
        const currencyItems = CURRENCY_ORDER
            .filter(c => c !== display)
            .map(c => ({ value: c, amount: fx.convert(nativeAmt, native, c) }))
            .filter(o => Number.isFinite(o.amount))
            .map(o => ({
                label: formatPrice(o.amount, o.value),
                onClick: () => setRowDisplay(p, o.value),
            }));

        const sections = [];
        if (currencyItems.length) {
            sections.push({ header: 'Mostrar como', items: currencyItems });
        }
        sections.push({
            items: [
                { label: 'Editar',   onClick: () => p.kind === 'income' ? openIncomeModal(p) : openPaymentModal(p) },
                { label: 'Eliminar', danger: true, onClick: () => openPaymentDelete(p) },
            ],
        });

        rowMenu.open(anchor, sections);
    });
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
    const cardInput = document.getElementById('pmt-card');
    cardInput.value = full?.card_id || '';
    cardInput.dispatchEvent(new Event('change', { bubbles: true }));
    const defAcct = defaultAccount();
    const acctInput = document.getElementById('pmt-account');
    acctInput.value = full?.account_id || (defAcct?.id || '');
    acctInput.dispatchEvent(new Event('change', { bubbles: true }));
    document.getElementById('pmt-currency').value = full?.currency || (defAcct?.currency || 'ARS');
    // New payments: default checked (most logging happens after paying); edits: actual state.
    const isPaidNow = full ? full.is_paid == 1 : true;
    document.getElementById('pmt-is-paid').checked = isPaidNow;
    // Paid date defaults to the stored paid_ts on edit, or the due date / today
    // for new payments. Visibility tracks the "Marcar como pagado" checkbox.
    const dueIso = document.getElementById('pmt-due').value || todayISODate();
    const paidIso = full?.paid_ts ? full.paid_ts.slice(0, 10) : dueIso;
    document.getElementById('pmt-paid-ts').value = paidIso;
    document.getElementById('pmt-paid-ts-wrap').classList.toggle('hidden', !isPaidNow);
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
    // Original artifact viewer: shown for saved rows that have one persisted,
    // and for unsaved AI drafts via the path-based preview endpoint.
    const artifactDescriptor = artifactUrlForPayment(full);
    if (artifactDescriptor) {
        renderArtifactViewer(artifactDescriptor, artifactMimeForPayment(full), 'pmt');
    } else {
        hideArtifactViewer('pmt');
    }
    document.getElementById('payment-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('pmt-title').focus(), 50);
}

// Render the "Original" details element with an inline preview of the AI input
// artifact (image / audio / PDF). The actual fetch goes through our private
// proxy at /api/transactions/artifact?id=<id> (saved rows) or
// /api/ai/preview-artifact?path=<path> (unsaved AI drafts). The bucket itself
// is not exposed.
function renderArtifactViewer(descriptor, mime, prefix = 'pmt') {
    const details = document.getElementById(`${prefix}-artifact-details`);
    const body = document.getElementById(`${prefix}-artifact-body`);
    const kind = document.getElementById(`${prefix}-artifact-kind`);
    if (!details || !body || !kind) return;

    body.textContent = '';

    const showFallback = () => {
        body.textContent = '';
        const p = document.createElement('p');
        p.className = 'text-xs text-muted';
        p.textContent = 'Original ya no disponible.';
        body.appendChild(p);
    };

    // Async preview chain: hit the auth-protected endpoint via api.getBlobUrl,
    // wrap the bytes in an object URL, then assign to the media element. Plain
    // <img src=...> can't pass the bearer token so it would 401.
    const fillMedia = (assignSrc, fallbackOnError = true) => {
        api.getBlobUrl(descriptor.endpoint, descriptor.params)
            .then(blobUrl => {
                if (blobUrl) assignSrc(blobUrl);
                else if (fallbackOnError) showFallback();
            })
            .catch(() => fallbackOnError && showFallback());
    };

    let label = 'archivo';
    if (mime.startsWith('image/')) {
        label = 'imagen';
        const img = document.createElement('img');
        img.alt = 'Original';
        img.className = 'w-full max-h-64 object-contain rounded-lg bg-dark/5 cursor-zoom-in';
        img.title = 'Click para ampliar';
        img.addEventListener('error', showFallback);
        body.appendChild(img);
        fillMedia(blobUrl => {
            img.src = blobUrl;
            img.addEventListener('click', () => openImageLightbox(blobUrl));
        });
    } else if (mime.startsWith('audio/')) {
        label = 'audio';
        const audio = document.createElement('audio');
        audio.controls = true;
        audio.className = 'w-full';
        audio.addEventListener('error', showFallback);
        body.appendChild(audio);
        fillMedia(blobUrl => { audio.src = blobUrl; });
    } else if (mime === 'application/pdf') {
        label = 'PDF';
        const link = document.createElement('a');
        link.target = '_blank';
        link.rel = 'noopener';
        link.className = 'inline-flex items-center gap-2 text-sm text-accent hover:underline';
        link.textContent = 'Abrir PDF en pestaña nueva';
        body.appendChild(link);
        fillMedia(blobUrl => { link.href = blobUrl; }, false);
    } else {
        const link = document.createElement('a');
        link.target = '_blank';
        link.rel = 'noopener';
        link.className = 'text-sm text-accent hover:underline';
        link.textContent = 'Descargar archivo original';
        body.appendChild(link);
        fillMedia(blobUrl => { link.href = blobUrl; }, false);
    }
    kind.textContent = `(${label})`;
    details.classList.remove('hidden');
    details.open = false;
}

function hideArtifactViewer(prefix = 'pmt') {
    const details = document.getElementById(`${prefix}-artifact-details`);
    if (!details) return;
    details.classList.add('hidden');
    details.open = false;
    const body = document.getElementById(`${prefix}-artifact-body`);
    const kind = document.getElementById(`${prefix}-artifact-kind`);
    if (body) body.textContent = '';
    if (kind) kind.textContent = '';
}

// Fullscreen image preview. Click anywhere or Esc to dismiss. The blob URL is
// reused — the caller already paid the fetch — so this is purely DOM work.
function openImageLightbox(blobUrl) {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 z-[60] bg-dark/85 flex items-center justify-center p-4 cursor-zoom-out';

    const img = document.createElement('img');
    img.src = blobUrl;
    img.alt = 'Original';
    img.className = 'max-w-full max-h-full object-contain rounded';
    overlay.appendChild(img);

    const close = () => {
        overlay.remove();
        document.removeEventListener('keydown', onKey);
        document.body.style.overflow = prevOverflow;
    };
    const onKey = e => { if (e.key === 'Escape') close(); };

    overlay.addEventListener('click', close);
    document.addEventListener('keydown', onKey);
    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    document.body.appendChild(overlay);
}

// Resolve the {endpoint, params} for an artifact, depending on whether the
// row is saved (id-based proxy) or still a draft from the AI flow (path-based
// preview). Both endpoints sit behind bearer-token auth, so the renderer must
// fetch via api.getBlobUrl rather than setting a raw <img src>.
function artifactUrlForPayment(full) {
    if (full?.id && full.ai_artifact_path) {
        return { endpoint: '/transactions/artifact', params: { id: full.id } };
    }
    if (aiState.artifact && aiState.artifact.path) {
        return {
            endpoint: '/ai/preview-artifact',
            params: { path: aiState.artifact.path, mime: aiState.artifact.mime || '' },
        };
    }
    return null;
}

function artifactMimeForPayment(full) {
    if (full?.id && full.ai_artifact_mime) return full.ai_artifact_mime;
    return aiState.artifact?.mime || '';
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

    const isPaidChecked = document.getElementById('pmt-is-paid').checked;
    const paidDate = document.getElementById('pmt-paid-ts').value;
    const body = {
        title: document.getElementById('pmt-title').value.trim(),
        amount: amountNum,
        due_ts: dueDate ? `${dueDate} 12:00:00` : null,
        expense_category_id: document.getElementById('pmt-category').value || null,
        card_id: document.getElementById('pmt-card').value || null,
        account_id: document.getElementById('pmt-account').value || null,
        currency: document.getElementById('pmt-currency').value || 'ARS',
        is_paid: isPaidChecked,
        // Only send paid_ts when the row is paid; otherwise let the API NULL it.
        paid_ts: (isPaidChecked && paidDate) ? `${paidDate} 12:00:00` : null,
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

// ── Income modal ────────────────────────────────────────────────────
// Slimmer than the payment modal: no card, no recurrent banner, no recipient.
// Lives separately so the expense flow keeps its specialized behavior without
// branching on `kind` at every line.
let incomeEditingId = null;

function populateIncomeCategorySelect() {
    const sel = document.getElementById('inc-category');
    sel.textContent = '';
    sel.appendChild(makeOption('', 'Sin categoria'));
    incomeCategories.forEach(c => sel.appendChild(makeOption(c.id, c.name)));
}

function updateIncomeCategorySwatch() {
    const id = document.getElementById('inc-category').value;
    const cat = incomeCategoryById(id);
    document.getElementById('inc-category-swatch').style.backgroundColor = cat?.color || 'transparent';
}

async function openIncomeModal(p) {
    incomeEditingId = p?.id || null;
    document.getElementById('income-modal-title').textContent = incomeEditingId ? 'Editar ingreso' : 'Nuevo ingreso';

    populateIncomeCategorySelect();

    // Editing an existing income row: hydrate from the latest server copy so we
    // pick up any fields not present on the list (e.g. description).
    const full = incomeEditingId ? (await api.get('/transactions', { id: incomeEditingId })) : null;
    const data = full || p || {};

    document.getElementById('inc-id').value = data.id || '';
    document.getElementById('inc-title').value = data.title || '';
    document.getElementById('inc-amount').value = data.amount != null ? formatAmountForInput(Math.abs(data.amount)) : '';
    document.getElementById('inc-due').value = data.due_ts ? data.due_ts.slice(0, 10) : todayISODate();
    document.getElementById('inc-category').value = data.income_category_id || '';
    document.getElementById('inc-currency').value = data.currency || 'ARS';
    document.getElementById('inc-account').value = data.account_id || defaultAccount()?.id || '';
    document.getElementById('inc-is-paid').checked = data.is_paid != 0;
    document.getElementById('inc-paid-ts').value = data.paid_ts ? data.paid_ts.slice(0, 10) : (data.due_ts ? data.due_ts.slice(0, 10) : todayISODate());
    document.getElementById('inc-description').value = data.description || '';

    updateIncomeCategorySwatch();
    mangosPicker.updateChip(document.getElementById('inc-account-chip'));
    document.getElementById('inc-paid-ts-wrap').classList.toggle('hidden', !document.getElementById('inc-is-paid').checked);

    document.getElementById('inc-form-error').classList.add('hidden');
    document.getElementById('inc-form-delete').classList.toggle('hidden', !incomeEditingId);

    const artifactDescriptor = artifactUrlForPayment(full);
    if (artifactDescriptor) {
        renderArtifactViewer(artifactDescriptor, artifactMimeForPayment(full), 'inc');
    } else {
        hideArtifactViewer('inc');
    }

    document.getElementById('income-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('inc-title').focus(), 50);
}

function closeIncomeModal() {
    // If we still have an unsaved AI artifact when closing, the user dismissed
    // the prefilled form — fire-and-forget a discard so we don't leak orphans.
    if (aiState.artifact && aiState.artifact.path) {
        const path = aiState.artifact.path;
        aiState.artifact = null;
        api.post('/ai/discard-artifact', { path }).catch(() => {});
    }
    hideArtifactViewer('inc');
    document.getElementById('income-modal').classList.add('hidden');
    incomeEditingId = null;
    aiSourceTag = null;
}

async function submitIncomeForm(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('inc-form-submit');
    const errorEl = document.getElementById('inc-form-error');
    errorEl.classList.add('hidden');
    submitBtn.disabled = true;

    const amountStr = document.getElementById('inc-amount').value;
    const amountNum = parseAmount(amountStr);
    if (!isFinite(amountNum) || amountNum <= 0) {
        errorEl.textContent = 'Ingresa un monto valido';
        errorEl.classList.remove('hidden');
        submitBtn.disabled = false;
        return;
    }

    const dueDate = document.getElementById('inc-due').value;
    const isPaidChecked = document.getElementById('inc-is-paid').checked;
    const paidDate = document.getElementById('inc-paid-ts').value;

    const body = {
        kind: 'income',
        title: document.getElementById('inc-title').value.trim(),
        amount: amountNum,
        due_ts: dueDate ? `${dueDate} 12:00:00` : null,
        income_category_id: document.getElementById('inc-category').value || null,
        account_id: document.getElementById('inc-account').value || null,
        currency: document.getElementById('inc-currency').value || 'ARS',
        is_paid: isPaidChecked,
        paid_ts: (isPaidChecked && paidDate) ? `${paidDate} 12:00:00` : null,
        description: document.getElementById('inc-description').value.trim(),
    };
    if (!incomeEditingId && aiSourceTag) body.source = aiSourceTag;
    if (!incomeEditingId && aiState.artifact) {
        body.ai_artifact_path = aiState.artifact.path;
        body.ai_artifact_mime = aiState.artifact.mime;
    }

    try {
        const result = incomeEditingId
            ? await api.put('/transactions', body, { id: incomeEditingId })
            : await api.post('/transactions', body);

        const ok = incomeEditingId ? result?.updated === true : !!result?.id;
        if (!ok) {
            errorEl.textContent = result?.error || 'Error al guardar';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            return;
        }

        toast(incomeEditingId ? 'Ingreso actualizado' : 'Ingreso creado', 'success');
        aiState.artifact = null;
        closeIncomeModal();
        await loadAll();
    } catch (err) {
        console.error(err);
        errorEl.textContent = 'Error de red';
        errorEl.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
    }
}

function deleteFromIncomeModal() {
    if (!incomeEditingId) return;
    const p = payments.find(x => x.id === incomeEditingId);
    if (!p) return;
    closeIncomeModal();
    openPaymentDelete(p);  // shared confirm dialog — same backend DELETE
}

// Bridge from AI parse-single (kind=income) → income modal with prefilled values.
function openIncomeModalFromAI(draft, submittedMode) {
    const detectedCur = draft.detected_currency || 'ARS';
    const matchByCurrency = accounts.find(a => a.currency === detectedCur);
    const synthetic = {
        title: draft.title || '',
        amount: draft.amount,
        due_ts: draft.date ? `${draft.date} 12:00:00` : null,
        income_category_id: draft.suggested_category_id || null,
        account_id: matchByCurrency?.id || defaultAccount()?.id || null,
        currency: detectedCur,
        is_paid: draft.is_paid ? 1 : 0,
        description: draft.description || '',
    };
    const mode = submittedMode || aiState.mode;
    openIncomeModal(synthetic);
    aiSourceTag = `ai-${mode}`;
}

// ── Transfer modal ──────────────────────────────────────────────────
let editingTransferId = null;

function populateTransferDropdowns() {
    const fromSel = document.getElementById('trf-from');
    const toSel   = document.getElementById('trf-to');
    fromSel.textContent = '';
    toSel.textContent = '';
    accounts.forEach(a => {
        fromSel.appendChild(makeOption(a.id, `${a.name} (${a.currency})`));
        toSel.appendChild(makeOption(a.id, `${a.name} (${a.currency})`));
    });
    if (accounts.length >= 2) {
        fromSel.value = accounts[0].id;
        toSel.value   = accounts[1].id;
    }

    // Fee category dropdown reuses the same category list as expenses.
    const catSel = document.getElementById('trf-fee-cat');
    catSel.textContent = '';
    catSel.appendChild(makeOption('', 'Sin categoría'));
    categories.forEach(c => catSel.appendChild(makeOption(c.id, c.name)));
}

function updateTransferCurrencyBadges() {
    const from = accountById(document.getElementById('trf-from').value);
    const to   = accountById(document.getElementById('trf-to').value);
    document.getElementById('trf-sent-cur').textContent     = from ? `(${from.currency})` : '';
    document.getElementById('trf-received-cur').textContent = to   ? `(${to.currency})`   : '';
    document.getElementById('trf-fee-cur').textContent      = from ? `(${from.currency})` : '';
    // Auto-mirror when currencies match and user hasn't typed a different value yet.
    const sentEl = document.getElementById('trf-sent');
    const recvEl = document.getElementById('trf-received');
    if (from && to && from.currency === to.currency && sentEl.value && !recvEl.value) {
        recvEl.value = sentEl.value;
    }
}

async function openTransferModal(transferId) {
    editingTransferId = transferId || null;
    document.getElementById('transfer-modal-title').textContent =
        transferId ? 'Editar transferencia' : 'Nueva transferencia';
    populateTransferDropdowns();

    const form = document.getElementById('transfer-form');
    form.reset();
    document.getElementById('trf-id').value = transferId || '';
    document.getElementById('trf-due').value = todayISODate();
    document.getElementById('trf-is-paid').checked = true;
    document.getElementById('trf-delete-btn').classList.toggle('hidden', !transferId);

    if (transferId) {
        try {
            const t = await api.get('/transfers', { id: transferId });
            if (t && !t.error && t.from_leg && t.to_leg) {
                document.getElementById('trf-from').value = t.from_leg.account_id;
                document.getElementById('trf-to').value   = t.to_leg.account_id;
                document.getElementById('trf-sent').value     = formatAmountForInput(Math.abs(t.from_leg.amount));
                document.getElementById('trf-received').value = formatAmountForInput(Math.abs(t.to_leg.amount));
                if (t.fee_leg) {
                    document.getElementById('trf-fee').value     = formatAmountForInput(Math.abs(t.fee_leg.amount));
                    document.getElementById('trf-fee-cat').value = t.fee_leg.expense_category_id || '';
                }
                document.getElementById('trf-description').value = t.from_leg.description || '';
                if (t.from_leg.due_ts) {
                    document.getElementById('trf-due').value = t.from_leg.due_ts.slice(0, 10);
                }
                document.getElementById('trf-is-paid').checked = t.from_leg.is_paid == 1;
            }
        } catch (e) {
            toast('No se pudo cargar la transferencia', 'error');
        }
    }

    updateTransferCurrencyBadges();
    document.getElementById('transfer-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('trf-sent').focus(), 50);
}

function closeTransferModal() {
    document.getElementById('transfer-modal').classList.add('hidden');
    editingTransferId = null;
}

async function submitTransferForm(e) {
    e.preventDefault();
    const fromId = document.getElementById('trf-from').value;
    const toId   = document.getElementById('trf-to').value;
    if (fromId === toId) {
        toast('Las cuentas deben ser distintas', 'error');
        return;
    }
    const sent = parseAmount(document.getElementById('trf-sent').value);
    const received = parseAmount(document.getElementById('trf-received').value);
    if (!isFinite(sent) || sent <= 0 || !isFinite(received) || received <= 0) {
        toast('Montos inválidos', 'error');
        return;
    }
    const feeRaw = document.getElementById('trf-fee').value.trim();
    const fee = feeRaw ? parseAmount(feeRaw) : 0;
    if (feeRaw && (!isFinite(fee) || fee < 0)) {
        toast('Comisión inválida', 'error');
        return;
    }

    const body = {
        from_account_id: fromId,
        to_account_id:   toId,
        amount_sent:     sent,
        amount_received: received,
        description: document.getElementById('trf-description').value.trim(),
        due_ts:      document.getElementById('trf-due').value || null,
        is_paid:     document.getElementById('trf-is-paid').checked,
    };
    if (fee > 0) {
        body.fee = fee;
        body.fee_category_id = document.getElementById('trf-fee-cat').value || null;
    }

    const btn = document.getElementById('trf-submit');
    btn.disabled = true;
    try {
        const result = editingTransferId
            ? await api.put('/transfers', body, { id: editingTransferId })
            : await api.post('/transfers', body);
        if (!result || result.error) {
            toast(result?.error || 'No se pudo guardar', 'error');
            return;
        }
        toast(editingTransferId ? 'Transferencia actualizada' : 'Transferencia creada', 'success');
        closeTransferModal();
        await loadAll();
    } catch (err) {
        toast('Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
}

async function confirmTransferDelete() {
    if (!editingTransferId) return;
    if (!confirm('¿Eliminar esta transferencia y todos sus movimientos?')) return;
    try {
        const result = await api.del('/transfers', { id: editingTransferId });
        if (!result || result.error) {
            toast(result?.error || 'No se pudo eliminar', 'error');
            return;
        }
        toast('Transferencia eliminada', 'success');
        closeTransferModal();
        await loadAll();
    } catch (err) {
        toast('Error de red', 'error');
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

// Bail out of the AI flow into the plain manual form. Closing the AI modal
// first keeps the two modals from layering and resets any in-flight AI state.
function switchAIToManual() {
    closeAIModal();
    openPaymentModal();
}

function resetAIModal() {
    cancelAudioRecording();
    aiState = { mode: 'text', kind: 'expense', image: null, pdf: null, audio: null, draft: null, matched: null };
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
    setAIKind('expense');
}

function setAIKind(kind) {
    aiState.kind = kind;
    document.querySelectorAll('.ai-kind-tab').forEach(t => {
        const active = t.dataset.kind === kind;
        t.classList.toggle('bg-white', active);
        t.classList.toggle('shadow-sm', active);
        t.classList.toggle('text-dark', active);
        t.classList.toggle('font-medium', active);
        t.classList.toggle('text-muted', !active);
    });
    // Hint copy on the text-mode placeholder so the user knows what to write.
    const textarea = document.getElementById('ai-text');
    if (textarea) {
        textarea.placeholder = kind === 'income'
            ? 'Ej: sueldo abril 850000; transferencia recibida 50000 de Juan; reembolso obra social 12000'
            : 'Ej: 1500 super coto ayer; 800 cafe con juan; transferencia 5000 a Maria por alquiler';
    }
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

    const payload = { mode: aiState.mode, kind: aiState.kind };
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

    // Capture mode + kind + artifact BEFORE closeAIModal — resetAIModal()
    // rebuilds aiState from scratch, dropping any keys we don't restore.
    const submittedMode = aiState.mode;
    const submittedKind = result.kind || aiState.kind;
    const submittedArtifact = result.ai_artifact || null;
    closeAIModal();
    aiState.draft = result.draft;
    aiState.matched = result.matched_recurrent || null;
    aiState.artifact = submittedArtifact;
    if (submittedKind === 'income') {
        openIncomeModalFromAI(result.draft, submittedMode);
    } else {
        await openPaymentModalFromAI(result.draft, result.matched_recurrent || null, submittedMode);
    }
}

function showAIError(msg) {
    const el = document.getElementById('ai-error');
    el.textContent = msg;
    el.classList.remove('hidden');
}

async function openPaymentModalFromAI(draft, matched, submittedMode) {
    const detectedCur = draft.detected_currency || 'ARS';
    // Prefer the account the AI explicitly matched (e.g. user said
    // "lo pagué con mercado pago"); fall back to currency-match, then default.
    const aiAccount = draft.suggested_account_id ? accountById(draft.suggested_account_id) : null;
    const matchByCurrency = accounts.find(a => a.currency === detectedCur);
    const synthetic = {
        title: draft.title || '',
        amount: draft.amount,
        due_ts: draft.date ? `${draft.date} 12:00:00` : null,
        expense_category_id: draft.suggested_category_id || null,
        card_id: null,
        account_id: aiAccount?.id || matchByCurrency?.id || defaultAccount()?.id || null,
        currency: detectedCur,
        is_paid: draft.is_paid ? 1 : 0,
        description: draft.description || '',
        recipient: (draft.recipient && draft.recipient.name) ? draft.recipient : null,
    };
    const mode = submittedMode || aiState.mode;
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

    // Kick off the FX rate fetch in parallel with the data loads. When it
    // resolves, the strip and any non-ARS amount conversions get refreshed.
    fx.ready.then(() => {
        renderFxStrip();
        renderSummary();
        renderPayments();
    });

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

    document.getElementById('filter-account').addEventListener('change', e => {
        accountFilter = e.target.value;
        writeUrlState();
        renderSummary();
        renderPayments();
    });

    const searchInput = document.getElementById('filter-search');
    searchInput.value = searchQuery;
    let searchDebounce;
    searchInput.addEventListener('input', e => {
        clearTimeout(searchDebounce);
        const value = e.target.value;
        searchDebounce = setTimeout(() => {
            searchQuery = value;
            writeUrlState();
            renderSummary();
            renderPayments();
        }, 150);
    });

    document.getElementById('payment-form').addEventListener('submit', submitPaymentForm);
    document.getElementById('pmt-category').addEventListener('change', updateCategorySwatch);
    document.getElementById('pmt-account').addEventListener('change', e => {
        const a = accountById(e.target.value);
        if (a) document.getElementById('pmt-currency').value = a.currency;
    });

    // Wire account/card picker chips. The hidden inputs hold the IDs; chip face
    // refreshes whenever its input fires a 'change' event.
    mangosPicker.bindChip(document.getElementById('pmt-card-chip'), {
        mode: 'card',
        valueInputId: 'pmt-card',
        allowNone: true,
    });
    mangosPicker.bindChip(document.getElementById('pmt-account-chip'), {
        mode: 'account',
        valueInputId: 'pmt-account',
    });
    // Income modal wiring
    mangosPicker.bindChip(document.getElementById('inc-account-chip'), {
        mode: 'account',
        valueInputId: 'inc-account',
    });
    document.getElementById('income-form').addEventListener('submit', submitIncomeForm);
    document.getElementById('inc-category').addEventListener('change', updateIncomeCategorySwatch);
    document.getElementById('inc-is-paid').addEventListener('change', e => {
        const wrap = document.getElementById('inc-paid-ts-wrap');
        wrap.classList.toggle('hidden', !e.target.checked);
        if (e.target.checked && !document.getElementById('inc-paid-ts').value) {
            const dueIso = document.getElementById('inc-due').value || todayISODate();
            document.getElementById('inc-paid-ts').value = dueIso;
        }
    });
    // Show/hide the paid-date row in lockstep with the "Marcar como pagado"
    // checkbox so unpaid rows don't carry a stray date.
    document.getElementById('pmt-is-paid').addEventListener('change', e => {
        const wrap = document.getElementById('pmt-paid-ts-wrap');
        wrap.classList.toggle('hidden', !e.target.checked);
        if (e.target.checked && !document.getElementById('pmt-paid-ts').value) {
            const dueIso = document.getElementById('pmt-due').value || todayISODate();
            document.getElementById('pmt-paid-ts').value = dueIso;
        }
    });

    // Transfer modal wiring
    document.getElementById('transfer-form').addEventListener('submit', submitTransferForm);
    document.getElementById('trf-from').addEventListener('change', updateTransferCurrencyBadges);
    document.getElementById('trf-to').addEventListener('change', updateTransferCurrencyBadges);

    // AI modal: tab switching
    document.querySelectorAll('.ai-mode-tab').forEach(t => {
        t.addEventListener('click', () => setAIMode(t.dataset.mode));
    });
    document.querySelectorAll('.ai-kind-tab').forEach(t => {
        t.addEventListener('click', () => setAIKind(t.dataset.kind));
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
        else if (!document.getElementById('income-modal').classList.contains('hidden')) closeIncomeModal();
        else if (!document.getElementById('transfer-modal').classList.contains('hidden')) closeTransferModal();
        else if (!document.getElementById('payment-modal').classList.contains('hidden')) closePaymentModal();
        else if (!document.getElementById('payment-delete-modal').classList.contains('hidden')) closePaymentDelete();
    });

    window.addEventListener('hashchange', openAIFromUrl);

    loadAll();
});
</script>
