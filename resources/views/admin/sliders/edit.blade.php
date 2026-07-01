<x-layouts.admin>
    <div class="slider-admin-container slider-edit-layout">
        <div class="slider-admin-card slider-edit-card">
            
            <div class="slider-edit-header">
                <h2 class="slider-admin-card-title slider-edit-title">Editar Banner</h2>
                <a href="{{ route('admin.sliders.index') }}" class="slider-back-link">&larr; Volver</a>
            </div>

            <form action="{{ route('admin.sliders.update', $slider->id) }}" method="POST" enctype="multipart/form-data">
                @csrf 
                @method('PUT')
                
                <div class="slider-form-group slider-center-text">
                    <p class="slider-label">Imagen Actual:</p>
                    <img src="{{ $slider->imagen_url }}" class="slider-edit-img" alt="Banner Actual">
                </div>

                <div class="slider-form-group">
                    <label class="slider-label">Nueva Imagen (Vacío para conservar actual)</label>
                    <input type="file" name="imagen" class="slider-input-file">
                </div>
                <div class="slider-form-group">
                    <label class="slider-label">Título</label>
                    <input type="text" name="titulo" value="{{ old('titulo', $slider->titulo) }}" class="slider-input">
                </div>
                <div class="slider-form-group">
                    <label class="slider-label">Subtítulo</label>
                    <input type="text" name="subtitulo" value="{{ old('subtitulo', $slider->subtitulo) }}" class="slider-input">
                </div>
                <div class="slider-form-group">
                    <label class="slider-label">URL de Enlace</label>
                    <input type="url" name="url_enlace" value="{{ old('url_enlace', $slider->url_enlace) }}" class="slider-input">
                </div>
                
                <div class="slider-form-row">
                    <div>
                        <label class="slider-label">Orden</label>
                        <input type="number" name="orden" value="{{ old('orden', $slider->orden) }}" required class="slider-input">
                    </div>
                    <div class="slider-checkbox-wrapper">
                        <label class="slider-checkbox-label">
                            <input type="checkbox" name="activo" value="1" {{ $slider->activo ? 'checked' : '' }}> Mostrar
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="slider-btn-update">Guardar Cambios</button>
            </form>
        </div>
    </div>
</x-layouts.admin>