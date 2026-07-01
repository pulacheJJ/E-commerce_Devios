<div class="slider-admin-card">
    <h3 class="slider-admin-card-title">Nuevo Banner</h3>
    
    <form action="{{ route('admin.sliders.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="slider-form-group">
            <label class="slider-label">Título (Opcional)</label>
            <input type="text" name="titulo" class="slider-input">
        </div>
        <div class="slider-form-group">
            <label class="slider-label">Subtítulo (Opcional)</label>
            <input type="text" name="subtitulo" class="slider-input">
        </div>
        <div class="slider-form-group">
            <label class="slider-label">URL de Enlace (Opcional)</label>
            <input type="url" name="url_enlace" placeholder="https://devioz.com/producto/laptop" class="slider-input">
        </div>
        <div class="slider-form-group">
            <label class="slider-label">Imagen del Banner</label>
            <input type="file" name="imagen" required class="slider-input-file">
        </div>
        <div class="slider-form-row">
            <div>
                <label class="slider-label">Orden</label>
                <input type="number" name="orden" value="0" required class="slider-input">
            </div>
            <div class="slider-checkbox-wrapper">
                <label class="slider-checkbox-label">
                    <input type="checkbox" name="activo" value="1" checked> Mostrar
                </label>
            </div>
        </div>
        <button type="submit" class="slider-btn-submit">Subir Banner</button>
    </form>
</div>