<x-layouts.app>
    <x-slot:title>Reintentar pago | DEVIOZ</x-slot:title>

    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    </x-slot:css>

    <main class="store-container">
        <div class="checkout-stepper">
            <div class="step completed">
                <div class="step-circle"><i class="fas fa-check"></i></div><span>Carro</span>
            </div>
            <div class="step-line completed"></div>
            <div class="step completed">
                <div class="step-circle"><i class="fas fa-check"></i></div><span>Entrega</span>
            </div>
            <div class="step-line"></div>
            <div class="step active">
                <div class="step-circle">3</div><span>Pago</span>
            </div>
        </div>

        @if (session('error'))
            <div class="checkout-retry-alert" role="alert">{{ session('error') }}</div>
        @endif

        <div class="checkout-grid">
            <section class="checkout-card-box checkout-retry-card">
                <h1 class="checkout-section-title">Tu pedido continúa reservado</h1>
                @if ($pedido->pagos->isNotEmpty())
                    <p>El pago de prueba ya fue preparado. Puedes continuar hacia Mercado Pago sin crear otro pedido ni descontar nuevamente el stock.</p>
                @else
                    <p>La conexión anterior se interrumpió antes de crear el pago. No se realizó ningún cobro ni se volvió a descontar stock.</p>
                @endif
                <dl class="checkout-retry-data">
                    <div><dt>Pedido</dt><dd>{{ $pedido->numero_pedido }}</dd></div>
                    <div><dt>Estado</dt><dd>{{ $pedido->pagos->isNotEmpty() ? 'Pago preparado' : 'Esperando inicio de pago' }}</dd></div>
                    <div><dt>Reserva válida hasta</dt><dd>{{ $pedido->expires_at->format('d/m/Y H:i') }}</dd></div>
                </dl>
                <p class="checkout-retry-note">El resumen está bloqueado para conservar exactamente las cantidades y precios reservados.</p>
            </section>

            <aside class="checkout-summary">
                <h2 class="checkout-section-title checkout-summary__title">Resumen de la compra</h2>

                <div class="checkout-items-list">
                    @foreach ($pedido->detalles as $detalle)
                        <div class="summary-item">
                            <div class="summary-item-info">
                                @if ($detalle->variante?->producto?->imagen_principal)
                                    <img src="{{ $detalle->variante->producto->imagen_principal_url }}"
                                        alt="{{ $detalle->producto_nombre }}">
                                @else
                                    <div class="summary-no-img">Sin foto</div>
                                @endif
                                <div>
                                    <h4>{{ $detalle->producto_nombre }}</h4>
                                    <span>Cant: {{ $detalle->cantidad }}@if ($detalle->variante_descripcion) | {{ $detalle->variante_descripcion }}@endif</span>
                                </div>
                            </div>
                            <div class="summary-item-price">S/ {{ number_format($detalle->subtotal_linea, 2) }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="summary-totals-row"><span>Productos</span><span>S/ {{ number_format($pedido->subtotal - $pedido->descuento_total, 2) }}</span></div>
                <div class="summary-totals-row"><span>Entrega</span><span>S/ {{ number_format($pedido->costo_envio, 2) }}</span></div>
                <div class="summary-grand-total summary-grand-total--spaced">
                    <span>Total:</span><span>S/ {{ number_format($pedido->total, 2) }}</span>
                </div>

                <form method="POST" action="{{ route('checkout.reintentar', $pedido->numero_pedido) }}">
                    @csrf
                    <button type="submit" class="btn btn--primary checkout-btn-pay">Continuar pago de prueba</button>
                </form>
            </aside>
        </div>
    </main>
</x-layouts.app>
