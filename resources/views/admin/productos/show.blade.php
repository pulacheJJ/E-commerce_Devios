<x-layouts.admin>
    <x-slot:title>Detalle de Producto | DEVIOZ</x-slot:title>

    <div class="admin-container" style="max-width: 1200px;">
        <header class="admin-header">
            <div>
                <h1 class="admin-header__title">Detalle del Producto</h1>
                <p style="color: #4a6b82; margin-top: 5px; font-family: monospace;">{{ $producto->slug }}</p>
            </div>
            <div style="display: flex; gap: 10px;">
                @can('productos.editar')
                    <a href="{{ route('admin.productos.edit', $producto) }}" class="btn btn--primary">Editar Producto</a>
                @endcan
                <a href="{{ route('admin.productos.index') }}" class="btn btn--outline">Volver al catálogo</a>
            </div>
        </header>

        <div class="product-grid">
            <section class="admin-card mb-20">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <h2 style="margin-top: 0; color: var(--ink);">{{ $producto->nombre }}</h2>
                    @if($producto->esta_activo)
                        <span class="status status--active" style="padding: 5px 10px; background: rgba(45, 212, 191, 0.1); border-radius: 4px;">● Activo en Tienda</span>
                    @else
                        <span class="status status--inactive" style="padding: 5px 10px; background: rgba(239, 68, 68, 0.1); border-radius: 4px;">○ Inactivo</span>
                    @endif
                </div>
                
                <hr style="border-color: rgba(255,255,255,0.05); margin: 15px 0;">

                <div class="grid-2 mb-20">
                    <div>
                        <strong style="color: var(--teal); font-size: 0.85em; text-transform: uppercase;">Categoría</strong>
                        <p style="margin: 5px 0;">{{ $producto->categoria->nombre ?? 'Sin Categoría' }}</p>
                    </div>
                    <div>
                        <strong style="color: var(--teal); font-size: 0.85em; text-transform: uppercase;">Marca</strong>
                        <p style="margin: 5px 0;">{{ $producto->marca ?? 'No especificada' }}</p>
                    </div>
                </div>

                <div>
                    <strong style="color: var(--teal); font-size: 0.85em; text-transform: uppercase;">Descripción</strong>
                    <p style="margin: 5px 0; line-height: 1.6; color: #a0b2c0;">
                        {{ $producto->descripcion ?? 'Este producto no tiene descripción detallada.' }}
                    </p>
                </div>
            </section>

            <section class="admin-card mb-20">
                <h3 style="margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;">Galería Visual</h3>
                
                @if($producto->imagenes->count() > 0)
                    <div class="image-grid">
                        @foreach($producto->imagenes as $img)
                            <div class="preview-item">
                                <img src="{{ asset('storage/' . $img->ruta_imagen) }}" alt="Imagen del producto">
                                @if($img->es_principal)
                                    <span class="badge-principal">Principal</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="padding: 40px; text-align: center; color: #4a6b82; border: 1px dashed #4a6b82; border-radius: 8px;">
                        Este producto no tiene imágenes registradas.
                    </div>
                @endif
            </section>
        </div>

        <section class="admin-card">
            <h3 style="margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;">
                Desglose de Variantes ({{ $producto->variantes->count() }})
            </h3>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Color</th>
                            <th>Especificación</th>
                            <th>Precio Normal</th>
                            <th>Precio Oferta</th>
                            <th>Stock Disponible</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($producto->variantes as $variante)
                            <tr>
                                <td><code>{{ $variante->sku ?? 'N/A' }}</code></td>
                                <td>{{ $variante->color ?? '-' }}</td>
                                <td>{{ $variante->especificacion ?? '-' }}</td>
                                <td><strong>S/ {{ number_format($variante->precio, 2) }}</strong></td>
                                <td>
                                    @if($variante->precio_oferta)
                                        <span style="color: var(--teal); font-weight: bold;">S/ {{ number_format($variante->precio_oferta, 2) }}</span>
                                    @else
                                        <span style="color: #4a6b82;">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($variante->stock > 5)
                                        <span style="color: var(--ink);">{{ $variante->stock }} unds.</span>
                                    @elseif($variante->stock > 0)
                                        <span style="color: #f59e0b; font-weight: bold;">{{ $variante->stock }} unds. (Bajo)</span>
                                    @else
                                        <span style="color: #ef4444; font-weight: bold;">Agotado</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-empty">Ocurrió un error: Este producto no tiene variantes asignadas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
