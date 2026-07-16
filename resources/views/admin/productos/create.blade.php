<x-layouts.admin>
    <x-slot:title>Nuevo Producto | DEVIOZ</x-slot:title>

    <div class="admin-container" style="max-width: 1200px;">
        <header class="admin-header">
            <h1 class="admin-header__title">Registrar Producto</h1>
            <a href="{{ route('admin.productos.index') }}" class="btn btn--outline">Volver</a>
        </header>

        @if ($errors->any())
            <div role="alert" style="margin-bottom: 20px; padding: 14px 16px; border: 1px solid #ef4444; border-radius: 8px; background: rgba(239, 68, 68, .12); color: #fecaca;">
                Existen campos por corregir. Revisa la información marcada antes de guardar.
            </div>
        @endif

        <form action="{{ route('admin.productos.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="product-grid">
                <div>
                    <section class="admin-card mb-20">
                        <h3>Información Básica</h3>
                        <div class="form-group">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" id="nombre" class="form-input" value="{{ old('nombre') }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug *</label>
                            <input type="text" name="slug" id="slug" class="form-input" value="{{ old('slug') }}" required readonly>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-input" value="{{ old('marca') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Categoría *</label>
                                <select name="categoria_id" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}" @selected(old('categoria_id') == $cat->id)>{{ $cat->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-input" rows="4">{{ old('descripcion') }}</textarea>
                        </div>
                    </section>

                    <section class="admin-card">
                        <div class="flex-between">
                            <h3>Variantes (Stock y Precios)</h3>
                            <button type="button" class="btn btn--outline btn--sm" id="btn-add-variante">+ Agregar Variante</button>
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
                                    @foreach (old('variantes', []) as $index => $variante)
                                        <tr class="variante-row" data-variant-index="{{ $index }}">
                                            <td>
                                                <input type="text" name="variantes[{{ $index }}][sku]"
                                                    class="form-input form-input--sm" value="{{ $variante['sku'] ?? '' }}"
                                                    placeholder="SKU" required
                                                    @error("variantes.$index.sku") aria-invalid="true" @enderror>
                                                @error("variantes.$index.sku")
                                                    <small class="variant-field-error" style="display: block; margin-top: 5px; color: #fca5a5;">{{ $message }}</small>
                                                @enderror
                                            </td>
                                            <td>
                                                <input type="text" name="variantes[{{ $index }}][color]" class="form-input form-input--sm mb-5" value="{{ $variante['color'] ?? '' }}" placeholder="Color">
                                                <input type="text" name="variantes[{{ $index }}][especificacion]" class="form-input form-input--sm" value="{{ $variante['especificacion'] ?? '' }}" placeholder="Esp.">
                                            </td>
                                            <td><input type="number" step="0.01" name="variantes[{{ $index }}][precio]" class="form-input form-input--sm" value="{{ $variante['precio'] ?? '' }}" required></td>
                                            <td><input type="number" step="0.01" name="variantes[{{ $index }}][precio_oferta]" class="form-input form-input--sm" value="{{ $variante['precio_oferta'] ?? '' }}"></td>
                                            <td><input type="number" name="variantes[{{ $index }}][stock]" class="form-input form-input--sm" value="{{ $variante['stock'] ?? 0 }}" required></td>
                                            <td><button type="button" class="btn btn--danger btn--sm btn-remove-var"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div>
                    <section class="admin-card mb-20">
                        <h3>Imágenes</h3>
                        <div class="form-group">
                            <label class="form-label">Subir Archivos (JPG, PNG)</label>
                            <input type="file" name="imagenes[]" id="imagenes" class="form-input" multiple accept="image/*" required>
                            <small class="text-muted">La primera imagen será la principal.</small>
                        </div>
                        <div id="image-preview-container" class="image-grid"></div>
                    </section>

                    <section class="admin-card">
                        <h3>Publicación</h3>
                        <div class="form-group">
                            <label class="form-checkbox-label">
                                <input type="checkbox" name="esta_activo" class="form-checkbox" value="1" checked> Producto Activo
                            </label>
                        </div>
                        <button type="submit" class="btn btn--primary btn-block">Guardar Producto</button>
                    </section>
                </div>
            </div>
        </form>
    </div>

    <x-slot:js>
        <script src="{{ asset('js/admin/productos/form.js') }}"></script>
    </x-slot:js>
</x-layouts.admin>
