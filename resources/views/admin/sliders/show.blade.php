<x-layouts.admin>
    <div class="slider-admin-container slider-edit-layout">
        <div class="slider-admin-card slider-edit-card">

            <div class="slider-show-header">
                <h2 class="slider-admin-card-title slider-edit-title slider-show-title">Detalles del Banner</h2>
                <a href="{{ route('admin.sliders.index') }}" class="slider-back-link">&larr; Volver a la lista</a>
            </div>

            <div class="slider-show-preview">
                <p class="slider-label slider-show-preview-label">Vista previa de la imagen:</p>
                <img src="{{$slider->imagen_url}}" class="slider-show-image" alt="Banner Visual">
            </div>

            <div class="slider-show-card">
                <div class="slider-show-grid">

                    <div>
                        <p class="slider-show-label">Título</p>
                        <p class="slider-show-value">{{ $slider->titulo ?: '---' }}</p>
                    </div>

                    <div>
                        <p class="slider-show-label">Subtítulo</p>
                        <p class="slider-show-value">{{ $slider->subtitulo ?: '---' }}</p>
                    </div>

                    <div class="slider-show-full-col">
                        <p class="slider-show-label">URL de Enlace</p>
                        @if ($slider->url_enlace)
                            <a href="{{ $slider->url_enlace }}" target="_blank" class="slider-show-link">
                                {{ $slider->url_enlace }} <i class="fas fa-external-link-alt slider-show-link-icon"></i>
                            </a>
                        @else
                            <p class="slider-show-value-empty">Sin enlace asignado</p>
                        @endif
                    </div>

                    <div>
                        <p class="slider-show-label">Orden de visualización</p>
                        <p class="slider-show-value">{{ $slider->orden }}</p>
                    </div>

                    <div>
                        <p class="slider-show-label">Estado Actual</p>
                        <span
                            class="slider-show-badge-wrapper {{ $slider->activo ? 'slider-badge-active' : 'slider-badge-inactive' }}">
                            {{ $slider->activo ? 'Activo (Visible en tienda)' : 'Inactivo (Oculto)' }}
                        </span>
                    </div>

                </div>
            </div>

            <div class="slider-show-actions">
                @can('sliders.editar')
                <a href="{{ route('admin.sliders.edit', $slider->id) }}" class="slider-btn-update slider-show-btn-edit">
                    <i class="fas fa-edit"></i> Editar este Banner
                </a>
                @endcan
            </div>

        </div>
    </div>
</x-layouts.admin>
