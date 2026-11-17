<x-layouts.admin>
    <x-slot:title>Dashboard | DEVIOZ</x-slot:title>

    <div class="admin-header">
        <h1>Bienvenido al Panel, King</h1>
    </div>

    <div class="dashboard-grid">
        <div class="admin-card">
            <h3 class="dashboard-card__title">Total Categorías</h3>
            <p class="dashboard-card__value">{{ $total_categorias }}</p>
            <a href="{{ route('admin.categorias.index') }}" class="dashboard-card__link">Gestionar categorías →</a>
        </div>

        <div class="admin-card">
            <h3 class="dashboard-card__title">Total Productos</h3>
            <p class="dashboard-card__value">{{ $total_productos }}</p>
            <a href="{{ route('admin.productos.index') }}" class="dashboard-card__link">Gestionar productos →</a>
        </div>
    </div>
</x-layouts.admin>
