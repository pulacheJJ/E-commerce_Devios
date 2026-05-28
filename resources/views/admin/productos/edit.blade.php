<x-layouts.admin>
    <x-slot:title>Editar Producto | DEVIOZ</x-slot:title>

    <div class="admin-container" style="max-width: 1200px;">
        <header class="admin-header">
            <h1 class="admin-header__title">Editar: {{ $producto->nombre }}</h1>
            <a href="{{ route('admin.productos.index') }}" class="btn btn--outline">Volver</a>
        </header>

        <form action="{{ route('admin.productos.update', $producto) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="product-grid">
                <div>
                    <section class="admin-card mb-20">
                        <h3>Información Básica</h3>
                        <div class="form-group">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" id="nombre" class="form-input"
                                value="{{ old('nombre', $producto->nombre) }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug *</label>
                            <input type="text" name="slug" id="slug" class="form-input"
                                value="{{ old('slug', $producto->slug) }}" required readonly>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-input"
                                    value="{{ old('marca', $producto->marca) }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria_id" class="form-select" required>
                                    @foreach ($categorias as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ $producto->categoria_id == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-input" rows="4">{{ old('descripcion', $producto->descripcion) }}</textarea>
                        </div>
                    </section>

                    <section class="admin-card">
                        <div class="flex-between">
                            <h3>Variantes (Stock y Precios)</h3>
                            <button type="button" class="btn btn--outline btn--sm" id="btn-add-variante">+ Agregar
                                Variante</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="tabla-variantes">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Color/Esp.</th>
                                        <th>Precio *</th>
                                        <th>Oferta</th>
                                        <th>Stock *</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="variantes-container">
                                    @foreach ($producto->variantes as $index => $variante)
                                        <tr class="variante-row">
                                            <td>
                                                <input type="hidden" name="variantes[{{ $index }}][id]"
                                                    value="{{ $variante->id }}">
                                                <input type="text" name="variantes[{{ $index }}][sku]"
                                                    class="form-input form-input--sm" value="{{ $variante->sku }}"
                                                    placeholder="SKU">
                                            </td>

                                            <td>
                                                <input type="text" name="variantes[{{ $index }}][color]"
                                                    class="form-input form-input--sm mb-5"
                                                    value="{{ $variante->color }}" placeholder="Color">
                                                <input type="text"
                                                    name="variantes[{{ $index }}][especificacion]"
                                                    class="form-input form-input--sm"
                                                    value="{{ $variante->especificacion }}" placeholder="Esp.">
                                            </td>

                                            <td>
                                                <input type="number" step="0.01"
                                                    name="variantes[{{ $index }}][precio]"
                                                    class="form-input form-input--sm" value="{{ $variante->precio }}"
                                                    required>
                                            </td>

                                            <td>
                                                <input type="number" step="0.01"
                                                    name="variantes[{{ $index }}][precio_oferta]"
                                                    class="form-input form-input--sm"
                                                    value="{{ $variante->precio_oferta }}">
                                            </td>

                                            <td>
                                                <input type="number" name="variantes[{{ $index }}][stock]"
                                                    class="form-input form-input--sm" value="{{ $variante->stock }}"
                                                    required>
                                            </td>

                                            <td>
                                                <button type="button" class="btn btn--danger btn--sm btn-remove-var"><i
                                                        class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div>
                    <section class="admin-card mb-20">
                        <h3>Imágenes Existentes</h3>
                        <div class="image-grid mb-20">
                            @foreach ($producto->imagenes as $img)
                                <div class="preview-item">
                                    <img src="{{ asset('storage/' . $img->ruta_imagen) }}" alt="Img">
                                    @if ($img->es_principal)
                                        <span class="badge-principal">Principal</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <hr style="border-color: rgba(255,255,255,0.05); margin: 15px 0;">

                        <h3>Añadir Nuevas (Opcional)</h3>
                        <div class="form-group">
                            <input type="file" name="imagenes[]" id="imagenes" class="form-input" multiple
                                accept="image/*">
                        </div>
                        <div id="image-preview-container" class="image-grid"></div>
                    </section>

                    <section class="admin-card">
                        <div class="form-group">
                            <label class="form-checkbox-label">
                                <input type="checkbox" name="esta_activo" class="form-checkbox" value="1"
                                    {{ $producto->esta_activo ? 'checked' : '' }}> Producto Activo
                            </label>
                        </div>
                        <button type="submit" class="btn btn--primary btn-block">Actualizar Producto</button>
                    </section>
                </div>
            </div>
        </form>
    </div>

    <x-slot:js>
        <script src="{{ asset('js/admin/productos/form.js') }}"></script>
    </x-slot:js>
</x-layouts.admin>
