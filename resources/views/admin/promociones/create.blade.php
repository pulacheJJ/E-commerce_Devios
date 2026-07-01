<x-layouts.admin>
    <x-slot name="title">Nueva Promoción | DEVIOZ</x-slot>

    <div class="admin-header">
        <h2>Crear Nueva Promoción</h2>
        <a href="{{ route('admin.promociones.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('admin.promociones.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-layout-split">
            
            <div class="main-column">
                <div class="panel-card">
                    <h3 class="panel-title">Información Básica</h3>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <label for="titulo">Título de la Campaña *</label>
                            <input type="text" name="titulo" id="titulo" class="form-control" value="{{ old('titulo') }}" required>
                            @error('titulo') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group form-group-full">
                            <label for="subtitulo">Subtítulo</label>
                            <input type="text" name="subtitulo" id="subtitulo" class="form-control" value="{{ old('subtitulo') }}">
                        </div>

                        <div class="form-group">
                            <label for="tipo_descuento">Tipo de Descuento *</label>
                            <select name="tipo_descuento" id="tipo_descuento" class="form-control" required>
                                <option value="porcentaje" {{ old('tipo_descuento') == 'porcentaje' ? 'selected' : '' }}>Porcentaje (%)</option>
                                <option value="fijo" {{ old('tipo_descuento') == 'fijo' ? 'selected' : '' }}>Monto Fijo (S/)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="valor_descuento">Valor del Descuento *</label>
                            <input type="number" step="0.01" min="0" name="valor_descuento" id="valor_descuento" class="form-control" value="{{ old('valor_descuento', 0) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="fecha_inicio">Inicio *</label>
                            <input type="datetime-local" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ old('fecha_inicio') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="fecha_fin">Fin *</label>
                            <input type="datetime-local" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ old('fecha_fin') }}" required>
                        </div>
                    </div>
                </div>

                <div class="panel-card mt-4">
                    <h3 class="panel-title">Alcance de la Promoción</h3>
                    <p class="text-muted help-text">Selecciona a qué categorías aplica. El selector de productos se filtrará automáticamente.</p>
                    
                    <div class="form-group">
                        <label for="categorias">Aplicar a Categorías</label>
                        <select name="categorias[]" id="categorias" class="form-control select2-multiple" multiple>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mt-4">
                        <label for="productos">Aplicar a Productos Específicos</label>
                        <select name="productos[]" id="productos" class="form-control select2-multiple" multiple>
                            @foreach($productos as $prod)
                                <option value="{{ $prod->id }}" data-categoria="{{ $prod->categoria_id }}">{{ $prod->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="side-column">
                <div class="panel-card">
                    <h3 class="panel-title">Imágenes</h3>
                    <div class="form-group">
                        <label for="ruta_banner">Banner (JPG, PNG) *</label>
                        <input type="file" name="ruta_banner" id="ruta_banner" class="form-control" accept="image/*" required>
                        <div id="banner-preview" class="mt-2 text-center"></div>
                    </div>
                    <div class="form-group mt-3">
                        <label for="url_destino">URL de Destino (Opcional)</label>
                        <input type="url" name="url_destino" id="url_destino" class="form-control" placeholder="https://...">
                    </div>
                </div>

                <div class="panel-card mt-4">
                    <h3 class="panel-title">Publicación</h3>
                    
                    <div class="ui-switch-wrapper">
                        <label class="ui-switch">
                            <input type="checkbox" name="esta_activa" value="1" {{ old('esta_activa', true) ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                        <span class="switch-label">Promoción Activa</span>
                    </div>

                    <div class="ui-switch-wrapper mt-3">
                        <label class="ui-switch">
                            <input type="checkbox" name="destacada_en_carrusel" value="1" {{ old('destacada_en_carrusel') ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                        <span class="switch-label">Destacar en Carrusel</span>
                    </div>

                    <hr class="divider">
                    <button type="submit" class="btn-primary w-100 btn-lg">Guardar Promoción</button>
                </div>
            </div>
        </div>
    </form>

    <x-slot name="js">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="{{ asset('js/admin/promociones/form.js') }}"></script>
    </x-slot>
</x-layouts.admin>