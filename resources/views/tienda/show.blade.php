<x-layouts.app>
    <x-slot:title>{{ $producto->nombre }} | DEVIOZ</x-slot:title>

    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    </x-slot:css>

    <main class="store-container">

        <nav class="breadcrumb" style="margin-bottom: 20px; font-size: 0.9rem;">
            <a href="{{ route('tienda.catalogo') }}" style="color: var(--teal); text-decoration: none;">Catálogo</a>
            <span style="color: #64748b; margin: 0 5px;">/</span>
            <a href="{{ route('tienda.catalogo', ['categoria' => $producto->categoria->slug ?? '']) }}"
                style="color: var(--teal); text-decoration: none;">{{ $producto->categoria->nombre ?? 'General' }}</a>
            <span style="color: #64748b; margin: 0 5px;">/</span>
            <span style="color: #94a3b8;">{{ $producto->nombre }}</span>
        </nav>

        <div class="pdp-layout">
            <div class="pdp-left-column">
                <section class="pdp-gallery-section">
                    <div class="pdp-thumbnails">
                        @foreach ($producto->imagenes as $imagen)
                            <div class="pdp-thumb {{ $loop->first ? 'active' : '' }}"
                                data-src="{{ asset('storage/' . $imagen->ruta_imagen) }}">
                                <img src="{{ asset('storage/' . $imagen->ruta_imagen) }}" alt="Miniatura">
                            </div>
                        @endforeach
                    </div>

                    <div class="pdp-main-image">
                        @if ($producto->imagen_principal)
                            <img src="{{ asset('storage/' . $producto->imagen_principal) }}"
                                alt="{{ $producto->nombre }}" id="imagenPrincipal">
                        @else
                            <div class="product-card__no-image">Sin imagen</div>
                        @endif
                    </div>
                </section>

                <div class="pdp-description-box">
                    <h3>Lo que tienes que saber de este producto</h3>
                    <p>{{ $producto->descripcion }}</p>
                </div>
            </div>

            <aside class="pdp-right-column">
                <div class="pdp-info-card sticky-card">
                    <span class="pdp-brand">Marca: {{ $producto->marca ?? 'Genérica' }}</span>
                    <h1 class="pdp-title">{{ $producto->nombre }}</h1>

                    <div class="pdp-price-container" id="precioContainer">
                        @if ($producto->promocion_activa)
                            <div class="pdp-prices">
                                <del class="pdp-price-old" id="precioOriginalDisplay">S/
                                    {{ number_format($producto->precio_original, 2) }}</del>
                                <span class="pdp-price pdp-price--discount" id="precioDisplay">S/
                                    {{ number_format($producto->precio_final, 2) }}</span>
                                <span class="pdp-promo-badge" id="promoBadge">
                                    @if ($producto->promocion_activa->tipo_descuento === 'porcentaje')
                                        -{{ number_format($producto->promocion_activa->valor_descuento, 0) }}% OFF
                                    @else
                                        - S/ {{ number_format($producto->promocion_activa->valor_descuento, 2) }}
                                    @endif
                                </span>
                            </div>
                        @else
                            <span class="pdp-price" id="precioDisplay">
                                S/ {{ number_format($producto->precio_original, 2) }}
                            </span>
                        @endif
                    </div>

                    <div id="productData" data-producto-id="{{ $producto->id }}"
                        data-variantes="{{ $producto->variantes_para_js->toJson() }}">
                    </div>
                    <div class="pdp-variants-container">
                        <h3 class="pdp-variants-title">Opciones disponibles: <span id="opcionSeleccionadaText"
                                style="color: var(--teal); font-weight: normal;">{{ $producto->variante_por_defecto->color }}</span>
                        </h3>

                        <div class="pdp-variant-pills">
                            @foreach ($producto->variantes as $variante)
                                <button type="button"
                                    class="variant-pill {{ $variante->id == $producto->variante_por_defecto->id ? 'active' : '' }} {{ $variante->stock == 0 ? 'disabled' : '' }}"
                                    data-id="{{ $variante->id }}" {{ $variante->stock == 0 ? 'disabled' : '' }}>
                                    <strong>{{ $variante->color }}</strong>
                                    <span>{{ $variante->especificacion }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="pdp-actions">
                        <div class="pdp-quantity">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <label for="cantidadInput" style="margin: 0;">Cantidad:</label>
                                <strong id="stockDisplay" style="color: #ef4444; font-size: 0.85rem;">
                                    {{ $producto->variante_por_defecto->stock == 0 ? 'Sin stock' : '' }}
                                </strong>
                            </div>

                            <div class="quantity-wrapper"
                                style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                <input type="number" id="cantidadInput" value="1" min="1"
                                    class="quantity-input"
                                    {{ $producto->variante_por_defecto->stock == 0 ? 'disabled' : '' }}>
                                <span id="errorCantidad"
                                    style="color: #ef4444; font-size: 0.85rem; font-weight: 600;"></span>
                            </div>
                        </div>

                        <div class="pdp-buttons-group">
                            <button id="btnComprarAhora" class="btn btn--primary pdp-btn-buy"
                                {{ $producto->variante_por_defecto->stock == 0 ? 'disabled' : '' }}>
                                Comprar ahora
                            </button>
                            <button id="btnAgregarCarrito" class="btn btn--secondary pdp-btn-cart"
                                {{ $producto->variante_por_defecto->stock == 0 ? 'disabled' : '' }}>
                                Agregar al carrito
                            </button>
                        </div>
                    </div>

                    <x-tienda.beneficios-pago />
                </div>
            </aside>
        </div>

        <x-tienda.productos-relacionados :productos="$relacionados" />
        <x-tienda.modal-carrito :producto="$producto" />

    </main>

    <x-slot:js>
        <script src="{{ asset('js/tienda/producto.js') }}"></script>
    </x-slot:js>
</x-layouts.app>