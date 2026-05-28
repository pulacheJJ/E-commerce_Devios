<x-layouts.admin>
    <x-slot:title>Listado de Categorías | Panel Admin</x-slot:title>

    <div class="admin-container">

        <header class="admin-header">
            <h1 class="admin-header__title">Mantenimiento de Categorías</h1>
            <a href="{{ route('admin.categorias.create') }}" class="btn btn--primary">Nueva Categoría</a>
        </header>

        @if (session('success'))
            <div class="alert alert--success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert--danger">
                {{ session('error') }}
            </div>
        @endif

        <section class="admin-card">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Slug / URL</th>
                        <th>Categoría Padre</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categorias as $cat)
                        <trstyle="{{ !$cat->esta_activa ? 'opacity: 0.6;' : '' }}>
                            <td>{{ $cat->id }}</td>
                            <td><strong>{{ $cat->nombre }}</strong></td>
                            <td><code>/{{ $cat->slug }}</code></td>
                            <td>
                                @if ($cat->padre)
<span class="badge
                        badge--parent">{{ $cat->padre->nombre }}</span>
                    @else
                        <span class="badge badge--root">Principal (Raíz)</span>
                    @endif
                    </td>
                    <td>
                        @if ($cat->esta_activa)
                            <span class="status status--active">● Activa</span>
                        @else
                            <span class="status status--inactive">○ Inactiva</span>
                        @endif
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('admin.categorias.edit', $cat) }}" class="btn btn--outline btn--sm">Editar</a>

                        <form action="{{ route('admin.categorias.toggle', $cat) }}" method="POST" class="form-inline"
                            onsubmit="return confirm('¿Estás seguro de {{ $cat->esta_activa ? 'desactivar' : 'activar' }} esta categoría?');">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                class="btn {{ $cat->esta_activa ? 'btn--danger' : 'btn--primary' }} btn--sm"
                                @if (!$cat->esta_activa) style="background-color: var(--teal); color: var(--bg); border: none;" @endif>
                                {{ $cat->esta_activa ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                    </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-empty">
                                No existen categorías registradas en el sistema. Presiona "Nueva Categoría" para
                                comenzar.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </x-layouts.admin>
