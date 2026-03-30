<!-- Page header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-semibold">Pagos</h1>
        <p class="text-sm text-muted mt-1">Pagos unicos del mes</p>
    </div>
    <button class="btn btn-primary" id="btn-new-payment">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo
    </button>
</div>

<!-- Payments list -->
<div class="card">
    <div id="payments-list">
        <p class="text-sm text-muted py-8 text-center">Cargando pagos...</p>
    </div>
</div>
