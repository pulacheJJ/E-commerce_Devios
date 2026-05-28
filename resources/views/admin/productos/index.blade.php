<x-layouts.admin>
    <x-slot:title>Listado de Productos | DEVIOZ</x-slot:title>

    <div class="admin-container" style="max-width: 1200px;">
        <header class="admin-header">
            <h1 class="admin-header__title">Catálogo de Productos</h1>
            <a href="{{ route('admin.productos.create') }}" class="btn btn--primary">Nuevo Producto</a>
        </header>

        @if (session('success'))
            <div class="alert alert--success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert--danger">{{ session('error') }}</div>
        @endif

        <section class="admin-card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Img</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Marca</th>
                        <th>Variantes</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $prod)
                        <tr style="{{ !$prod->esta_activo ? 'opacity: 0.6;' : '' }}">
                            <td>
                                @php $imgPrincipal = $prod->imagenes->where('es_principal', 1)->first(); @endphp
                                @if ($imgPrincipal)
                                    <img src="{{ asset('storage/' . $imgPrincipal->ruta_imagen) }}" alt="Img"
                                        class="img-thumbnail">
                                @else
                                    <div class="img-placeholder">Sin img</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $prod->nombre }}</strong><br>
                                <code>/{{ $prod->slug }}</code>
                            </td>
                            <td><span class="badge badge--parent">{{ $prod->categoria->nombre ?? '-' }}</span></td>
                            <td>{{ $prod->marca ?? '-' }}</td>
                            <td><span class="badge badge--root">{{ $prod->variantes->count() }} var(s)</span></td>
                            <td>
                                @if ($prod->esta_activo)
                                    <span class="status status--active">● Activo</span>
                                @else
                                    <span class="status status--inactive">○ Inactivo</span>
                                @endif
                            </td>
                            <td class="table-actions">
                                <a href="{{ route('admin.productos.show', $prod) }}" class="btn btn--sm"
                                    style="background-color: #1f3a4d; color: var(--ink);">Ver</a>

                                <a href="{{ route('admin.productos.edit', $prod) }}"
                                    class="btn btn--outline btn--sm">Editar</a>

                                <form action="{{ route('admin.productos.toggle', $prod) }}" method="POST"
                                    class="form-inline"
                                    onsubmit="return confirm('¿Seguro que deseas {{ $prod->esta_activo ? 'desactivar' : 'activar' }} este producto?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="btn {{ $prod->esta_activo ? 'btn--danger' : 'btn--primary' }} btn--sm"
                                        @if (!$prod->esta_activo) style="background-color: var(--teal); color: var(--bg); border: none;" @endif>
                                        {{ $prod->esta_activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-empty">No hay productos registrados en el sistema.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-layouts.admin>
