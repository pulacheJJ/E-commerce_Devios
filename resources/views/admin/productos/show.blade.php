<x-layouts.admin>
    <x-slot:title>Detalle de Producto | DEVIOZ</x-slot:title>

    <div class="admin-container admin-container--wide">
        <header class="admin-header">
            <div>
                <h1 class="admin-header__title">Detalle del Producto</h1>
                <p class="product-detail__slug">{{ $producto->slug }}</p>
            </div>
            <div class="product-detail__header-actions">
                @can('productos.editar')
                    <a href="{{ route('admin.productos.edit', $producto) }}" class="btn btn--primary">Editar Producto</a>
                @endcan
                <a href="{{ route('admin.productos.index') }}" class="btn btn--outline">Volver al catálogo</a>
            </div>
        </header>

        <div class="product-grid">
            <section class="admin-card mb-20">
                <div class="product-detail__heading">
                    <h2 class="product-detail__name">{{ $producto->nombre }}</h2>
                    @if($producto->esta_activo)
                        <span class="status status--active product-detail__status">● Activo en Tienda</span>
                    @else
                        <span class="status status--inactive product-detail__status">○ Inactivo</span>
                    @endif
                </div>
                
                <hr class="product-divider">

                <div class="grid-2 mb-20">
                    <div>
                        <strong class="product-detail__label">Categoría</strong>
                        <p class="product-detail__value">{{ $producto->categoria->nombre ?? 'Sin Categoría' }}</p>
                    </div>
                    <div>
                        <strong class="product-detail__label">Marca</strong>
                        <p class="product-detail__value">{{ $producto->marca ?? 'No especificada' }}</p>
                    </div>
                </div>

                <div>
                    <strong class="product-detail__label">Descripción</strong>
                    <p class="product-detail__description">
                        {{ $producto->descripcion ?? 'Este producto no tiene descripción detallada.' }}
                    </p>
                </div>
            </section>

            <section class="admin-card mb-20">
                <h3 class="product-detail__section-title">Galería Visual</h3>
                
                @if($producto->imagenes->count() > 0)
                    <div class="image-grid">
                        @foreach($producto->imagenes as $img)
                            <div class="preview-item">
                                <img src="{{ $img->url }}" alt="Imagen del producto">
                                @if($img->es_principal)
                                    <span class="badge-principal">Principal</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="product-detail__empty-gallery">
                        Este producto no tiene imágenes registradas.
                    </div>
                @endif
            </section>
        </div>

        <section class="admin-card">
            <h3 class="product-detail__section-title">
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
                                        <span class="product-detail__offer-price">S/ {{ number_format($variante->precio_oferta, 2) }}</span>
                                    @else
                                        <span class="product-detail__empty-value">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($variante->stock > 5)
                                        <span class="product-stock product-stock--available">{{ $variante->stock }} unds.</span>
                                    @elseif($variante->stock > 0)
                                        <span class="product-stock product-stock--low">{{ $variante->stock }} unds. (Bajo)</span>
                                    @else
                                        <span class="product-stock product-stock--empty">Agotado</span>
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
