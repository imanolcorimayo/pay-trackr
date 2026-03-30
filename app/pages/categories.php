<!-- Page header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-semibold">Categorias</h1>
        <p class="text-sm text-muted mt-1">Administra tus categorias de gasto</p>
    </div>
    <button class="btn btn-primary" id="btn-new-category">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva
    </button>
</div>

<!-- Categories grid -->
<div id="categories-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <div class="card text-center py-8">
        <p class="text-sm text-muted">Cargando categorias...</p>
    </div>
</div>
