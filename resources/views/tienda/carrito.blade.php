<x-layouts.app>
    <x-slot:title>Carro de Compras | DEVIOZ</x-slot:title>
    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    </x-slot:css>

    <main class="store-container">

        <div class="cart-page-header">
            <h1 class="store-header__title cart-page-header__title">
                Carro ({{ $carrito ? $carrito->cantidad_total : 0 }} productos)
            </h1>
            <a href="{{ route('tienda.inicio') }}" class="cart-continue-link">
                <i class="fas fa-arrow-left"></i> Seguir comprando
            </a>
        </div>

        <div class="cart-layout">
            <div class="cart-items-section">
                @if ($carrito && $carrito->elementos->count() > 0)
                    @foreach ($carrito->elementos as $item)
                        <div class="cart-item">
                            <div class="cart-item-img">
                                @if ($item->variante->producto->imagen_principal)
                                    <img src="{{ $item->variante->producto->imagen_principal_url }}"
                                        alt="{{ $item->variante->producto->nombre }}">
                                @else
                                    <div class="cart-item-no-image">
                                        Sin foto</div>
                                @endif
                            </div>

                            <div class="cart-item-details">
                                <h3>{{ $item->variante->producto->nombre }}</h3>
                                <span
                                    class="cart-item-brand">{{ $item->variante->producto->marca ?? 'Genérica' }}</span>
                                <span class="cart-item-variant">Opción: {{ $item->variante->color }}</span>
                            </div>

                            <div class="cart-item-qty">
                                <div class="qty-controls">
                                    <button type="button" class="js-cart-quantity" data-item-id="{{ $item->id }}"
                                        data-change="-1" data-max="{{ $item->limite_maximo }}">-</button>
                                    <input type="text" id="qty-{{ $item->id }}" value="{{ $item->cantidad }}"
                                        readonly>
                                    <button type="button" class="js-cart-quantity" data-item-id="{{ $item->id }}"
                                        data-change="1" data-max="{{ $item->limite_maximo }}">+</button>
                                </div>
                            </div>

                            <div class="cart-item-price">
                                <h3>S/ {{ number_format($item->subtotal, 2) }}</h3>

                                @if ($item->cantidad > 1)
                                    <span class="cart-item-unit-price">
                                        (S/ {{ number_format($item->precio_unitario_real, 2) }} c/u)
                                    </span>
                                @endif

                                <button type="button" class="btn-delete-item js-cart-delete" data-item-id="{{ $item->id }}">
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="cart-empty">
                        <i class="fas fa-shopping-cart fa-3x cart-empty__icon"></i>
                        <h2>Tu carro está vacío</h2>
                        <a href="{{ route('tienda.inicio') }}" class="btn btn--primary cart-empty__action">Ir
                            a comprar</a>
                    </div>
                @endif
            </div>

            @if ($carrito && $carrito->elementos->count() > 0)
                <aside class="cart-summary-section">
                    <div class="cart-summary-card">
                        <h2>Resumen de la orden</h2>

                        <div class="summary-row">
                            <span>Productos ({{ $carrito->cantidad_total }})</span>
                            <span>S/ {{ number_format($carrito->total, 2) }}</span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total:</span>
                            <span>S/ {{ number_format($carrito->total, 2) }}</span>
                        </div>

                        <a href="{{ route('checkout.index') }}" class="btn btn--primary cart-checkout-action">
                            Continuar compra
                        </a>
                    </div>
                </aside>
            @endif
        </div>
    </main>

    <x-slot:js>
        <script src="{{ asset('js/tienda/carrito.js') }}"></script>
    </x-slot:js>
</x-layouts.app>
