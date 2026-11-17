<x-layouts.admin>
    <x-slot:title>Tarifas de envío | DEVIOZ</x-slot:title>

    <header class="admin-header">
        <h1 class="admin-header__title">Tarifas de envío</h1>
        @can('envios.crear')
            <a href="{{ route('admin.envios.create') }}" class="btn btn--primary">Nueva tarifa</a>
        @endcan
    </header>

    @if (session('success')) <div class="alert alert--success">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="alert alert--danger">{{ session('error') }}</div> @endif

    <section class="admin-card shipping-rates">
        <div class="shipping-rates__table-wrapper">
        <table class="table shipping-rates__table">
            <thead><tr><th>Tipo</th><th>Departamento</th><th>Provincia</th><th>Costo</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                @forelse ($tarifas as $tarifa)
                    <tr>
                        <td>{{ $tarifa->es_predeterminada ? 'Predeterminada' : 'Provincial' }}</td>
                        <td>{{ $tarifa->provincia->departamento->nombre ?? 'Todas' }}</td>
                        <td>{{ $tarifa->provincia->nombre ?? 'Sin tarifa específica' }}</td>
                        <td>S/ {{ number_format($tarifa->costo, 2) }}</td>
                        <td>{{ $tarifa->esta_activa ? 'Activa' : 'Inactiva' }}</td>
                        <td class="table-actions">
                            @can('envios.editar')
                                <a href="{{ route('admin.envios.edit', $tarifa) }}" class="btn btn--outline btn--sm">Editar</a>
                            @endcan
                            @can('envios.cambiar_estado')
                                <form action="{{ route('admin.envios.toggle', $tarifa) }}" method="POST" class="form-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn--sm" type="submit">{{ $tarifa->esta_activa ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty">No hay tarifas configuradas.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        {{ $tarifas->links() }}
    </section>
</x-layouts.admin>
