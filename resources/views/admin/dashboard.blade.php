<x-layouts.admin>
    <x-slot:title>Dashboard | DEVIOZ</x-slot:title>

    <div class="admin-header">
        <h1>Bienvenido al Panel, King</h1>
    </div>

    <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <div class="admin-card">
            <h3 style="margin: 0; color: #4a6b82;">Total Categorías</h3>
            <p style="font-size: 2.5rem; font-weight: bold; margin: 10px 0;">{{ $total_categorias }}</p>
            <a href="{{ route('admin.categorias.index') }}" style="color: var(--teal); text-decoration: none; font-size: 0.9rem;">Gestionar categorías →</a>
        </div>

        <div class="admin-card">
            <h3 style="margin: 0; color: #4a6b82;">Total Productos</h3>
            <p style="font-size: 2.5rem; font-weight: bold; margin: 10px 0;">{{ $total_productos }}</p>
            <a href="{{ route('admin.productos.index') }}" style="color: var(--teal); text-decoration: none; font-size: 0.9rem;">Gestionar productos →</a>
        </div>
    </div>
</x-layouts.admin>