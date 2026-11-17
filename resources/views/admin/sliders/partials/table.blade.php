<div class="slider-admin-card slider-table-wrapper">
    <h3 class="slider-admin-card-title">Banners Activos</h3>
    <table class="slider-table">
        <thead>
            <tr>
                <th>Imagen</th>
                <th>Info</th>
                <th>Orden</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sliders as $slider)
                <tr>
                    <td>
                        <img src="{{ $slider->imagen_url }}" class="slider-preview-img" alt="Banner">
                    </td>
                    <td>
                        <strong>{{ $slider->titulo ?? 'Sin título' }}</strong><br>
                        @if ($slider->url_enlace)
                            <a href="{{ $slider->url_enlace }}" target="_blank" class="slider-link">Ver Link</a>
                        @endif
                    </td>
                    <td>{{ $slider->orden }}</td>
                    <td>
                        <span class="{{ $slider->activo ? 'slider-badge-active' : 'slider-badge-inactive' }}">
                            {{ $slider->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="slider-actions">
                        <a href="{{ route('admin.sliders.show', $slider->id) }}" class="slider-btn-ver">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                        @can('sliders.editar')
                        <a href="{{ route('admin.sliders.edit', $slider->id) }}" class="slider-btn-edit">Editar</a>
                        <form action="{{ route('admin.sliders.destroy', $slider->id) }}" method="POST"
                            onsubmit="return confirm('¿Eliminar banner?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="slider-btn-delete">Borrar</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="slider-empty">No hay banners registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
