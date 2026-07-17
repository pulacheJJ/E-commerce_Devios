<x-layouts.admin>
    <x-slot name="title">Promociones | DEVIOZ</x-slot>

    <div class="admin-header">
        <h2>Motor de Promociones</h2>
        @can('promociones.crear')
        <a href="{{ route('admin.promociones.create') }}" class="btn-primary">
            <i class="fas fa-plus"></i> Nueva Promoción
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert badge-success alert-custom">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="admin-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Banner</th>
                        <th>Campaña</th>
                        <th>Descuento</th>
                        <th>Vigencia</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promociones as $promo)
                        <tr>
                            <td>
                                @if($promo->ruta_banner)
                                    <img src="{{ asset('storage/' . $promo->ruta_banner) }}" alt="Banner" class="img-thumbnail-mini">
                                @else
                                    <span class="badge badge-secondary">Sin imagen</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $promo->titulo }}</strong><br>
                                <small class="text-muted">{{ $promo->subtitulo }}</small>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    {{ $promo->tipo_descuento === 'porcentaje' ? '-' . number_format($promo->valor_descuento, 0) . '%' : '- S/ ' . number_format($promo->valor_descuento, 2) }}
                                </span>
                            </td>
                            <td>
                                <span class="d-block text-success"><i class="fas fa-play"></i> {{ $promo->fecha_inicio->format('d/m/Y H:i') }}</span>
                                <span class="d-block text-danger mt-2"><i class="fas fa-stop"></i> {{ $promo->fecha_fin->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="text-center">
                                @if(!$promo->esta_activa)
                                    <span class="badge badge-dark">Apagada</span>
                                @elseif(now()->lt($promo->fecha_inicio))
                                    <span class="badge badge-warning">Programada</span>
                                @elseif(now()->gt($promo->fecha_fin))
                                    <span class="badge badge-danger">Expirada</span>
                                @else
                                    <span class="badge badge-success">En Curso</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="action-buttons">
                                    <a href="{{ route('admin.promociones.show', $promo->id) }}" class="btn-action text-info" title="Ver Detalles"><i class="fas fa-eye"></i></a>
                                    @can('promociones.editar')
                                    <a href="{{ route('admin.promociones.edit', $promo->id) }}" class="btn-action text-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                    @endcan
                                    @can('promociones.cambiar_estado')
                                    <form action="{{ route('admin.promociones.toggle', $promo->id) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-action {{ $promo->esta_activa ? 'text-danger' : 'text-success' }}" title="{{ $promo->esta_activa ? 'Apagar' : 'Encender' }}">
                                            <i class="fas {{ $promo->esta_activa ? 'fa-power-off' : 'fa-play' }}"></i>
                                        </button>
                                    </form>
                                    @endcan
                                    @can('promociones.editar')
                                    <form action="{{ route('admin.promociones.destroy', $promo->id) }}" method="POST" class="m-0" onsubmit="return confirm('¿Eliminar esta promoción?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action text-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center empty-state">
                                <i class="fas fa-tags empty-icon"></i>
                                <p class="m-0 text-muted">No hay promociones registradas</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($promociones->hasPages())
            <div class="mt-4">{{ $promociones->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
