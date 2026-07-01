<x-layouts.admin>
    <x-slot name="title">Editar Promoción | DEVIOZ</x-slot>

    <div class="admin-header">
        <h2>Editar: {{ $promocion->titulo }}</h2>
        <a href="{{ route('admin.promociones.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('admin.promociones.update', $promocion->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-layout-split">
            
            <div class="main-column">
                <div class="panel-card">
                    <h3 class="panel-title">Información Básica</h3>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <label for="titulo">Título de la Campaña *</label>
                            <input type="text" name="titulo" id="titulo" class="form-control" value="{{ old('titulo', $promocion->titulo) }}" required>
                        </div>

                        <div class="form-group form-group-full">
                            <label for="subtitulo">Subtítulo</label>
                            <input type="text" name="subtitulo" id="subtitulo" class="form-control" value="{{ old('subtitulo', $promocion->subtitulo) }}">
                        </div>

                        <div class="form-group">
                            <label for="tipo_descuento">Tipo de Descuento *</label>
                            <select name="tipo_descuento" id="tipo_descuento" class="form-control" required>
                                <option value="porcentaje" {{ old('tipo_descuento', $promocion->tipo_descuento) == 'porcentaje' ? 'selected' : '' }}>Porcentaje (%)</option>
                                <option value="fijo" {{ old('tipo_descuento', $promocion->tipo_descuento) == 'fijo' ? 'selected' : '' }}>Monto Fijo (S/)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="valor_descuento">Valor del Descuento *</label>
                            <input type="number" step="0.01" min="0" name="valor_descuento" id="valor_descuento" class="form-control" value="{{ old('valor_descuento', $promocion->valor_descuento) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="fecha_inicio">Inicio *</label>
                            <input type="datetime-local" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', $promocion->fecha_inicio->format('Y-m-d\TH:i')) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="fecha_fin">Fin *</label>
                            <input type="datetime-local" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ old('fecha_fin', $promocion->fecha_fin->format('Y-m-d\TH:i')) }}" required>
                        </div>
                    </div>
                </div>

                @php
                    $catSeleccionadas = $promocion->categorias->pluck('id')->toArray();
                    $prodSeleccionados = $promocion->productos->pluck('id')->toArray();
                @endphp

                <div class="panel-card mt-4">
                    <h3 class="panel-title">Alcance de la Promoción</h3>
                    <div class="form-group">
                        <label for="categorias">Categorías Asociadas</label>
                        <select name="categorias[]" id="categorias" class="form-control select2-multiple" multiple>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id }}" {{ in_array($cat->id, old('categorias', $catSeleccionadas)) ? 'selected' : '' }}>{{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mt-4">
                        <label for="productos">Productos Específicos</label>
                        <select name="productos[]" id="productos" class="form-control select2-multiple" multiple>
                            @foreach($productos as $prod)
                                <option value="{{ $prod->id }}" data-categoria="{{ $prod->categoria_id }}" {{ in_array($prod->id, old('productos', $prodSeleccionados)) ? 'selected' : '' }}>{{ $prod->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="side-column">
                <div class="panel-card">
                    <h3 class="panel-title">Imágenes</h3>
                    <div class="form-group">
                        <label for="ruta_banner">Reemplazar Banner (Opcional)</label>
                        <input type="file" name="ruta_banner" id="ruta_banner" class="form-control" accept="image/*">
                        <div id="banner-preview" class="mt-2 text-center">
                            @if($promocion->ruta_banner)
                                <img src="{{ asset('storage/' . $promocion->ruta_banner) }}" alt="Banner actual" class="banner-preview-img">
                            @endif
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label for="url_destino">URL de Destino</label>
                        <input type="url" name="url_destino" id="url_destino" class="form-control" value="{{ old('url_destino', $promocion->url_destino) }}">
                    </div>
                </div>

                <div class="panel-card mt-4">
                    <h3 class="panel-title">Publicación</h3>
                    <div class="ui-switch-wrapper">
                        <label class="ui-switch">
                            <input type="checkbox" name="esta_activa" value="1" {{ old('esta_activa', $promocion->esta_activa) ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                        <span class="switch-label">Promoción Activa</span>
                    </div>

                    <div class="ui-switch-wrapper mt-3">
                        <label class="ui-switch">
                            <input type="checkbox" name="destacada_en_carrusel" value="1" {{ old('destacada_en_carrusel', $promocion->destacada_en_carrusel) ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                        <span class="switch-label">Destacar en Carrusel</span>
                    </div>

                    <hr class="divider">
                    <button type="submit" class="btn-warning w-100 btn-lg">Actualizar Promoción</button>
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