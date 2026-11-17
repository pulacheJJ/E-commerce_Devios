<x-layouts.app>
    <x-slot:title>Confirmando pago | DEVIOZ</x-slot:title>

    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
        <link rel="stylesheet" href="{{ asset('css/tienda/pagos/estado.css') }}">
    </x-slot:css>

    <main class="store-container">
        <div class="checkout-stepper">
            <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><span>Carro</span></div>
            <div class="step-line completed"></div>
            <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><span>Entrega</span></div>
            <div class="step-line"></div>
            <div class="step active"><div class="step-circle">3</div><span>Pago</span></div>
        </div>

        <div class="checkout-grid">
            <section
                id="paymentStatus"
                class="checkout-form-card pago-section-transparent"
                data-status-url="{{ route('checkout.pago.estado', $pedido->numero_pedido) }}"
                data-confirmed="{{ $pedido->estado === 'pagado' ? 'true' : 'false' }}"
                data-redirect-url="{{ route('tienda.inicio') }}"
            >
                <h2 id="paymentStatusTitle" class="checkout-section-title">
                    {{ $pedido->estado === 'pagado' ? 'Pago confirmado' : 'Confirmando tu pago' }}
                </h2>
                <p>
                    La orden <strong class="text-teal">{{ $pedido->numero_pedido }}</strong> fue creada correctamente.
                </p>
                <p id="paymentStatusMessage">
                    @if ($pedido->estado === 'pagado')
                        Mercado Pago confirmó la operación de forma segura.
                    @else
                        Estamos esperando la confirmación segura de Mercado Pago. Esta página se actualizará automáticamente.
                    @endif
                </p>
                <p id="paymentStatusActivity" role="status" @if ($pedido->estado === 'pagado') hidden @endif>
                    Verificando estado...
                </p>
            </section>

            <aside class="checkout-summary">
                <h2 class="checkout-section-title summary-title-pago">Resumen</h2>
                <div class="summary-totals-row"><span>Subtotal:</span><span>S/ {{ number_format($pedido->subtotal, 2) }}</span></div>
                <div class="summary-totals-row"><span>Descuento:</span><span>- S/ {{ number_format($pedido->descuento_total, 2) }}</span></div>
                <div class="summary-totals-row"><span>Envío:</span><span>S/ {{ number_format($pedido->costo_envio, 2) }}</span></div>
                <div class="summary-grand-total pago-grand-total">
                    <span id="paymentTotalLabel">{{ $pedido->estado === 'pagado' ? 'Total pagado:' : 'Total pendiente:' }}</span>
                    <span>S/ {{ number_format($pedido->total, 2) }}</span>
                </div>
                <a href="{{ route('tienda.inicio') }}" class="btn btn--primary checkout-btn-pay btn-confirm-pago">
                    Volver a la tienda
                </a>
            </aside>
        </div>
    </main>

    <div id="paymentSuccessModal" class="payment-success-modal" role="dialog" aria-modal="true" aria-labelledby="paymentSuccessTitle" hidden>
        <div class="payment-success-modal__card">
            <div class="payment-success-modal__icon" aria-hidden="true">
                <i class="fas fa-check"></i>
            </div>
            <h2 id="paymentSuccessTitle">Pago realizado con éxito</h2>
            <p>Tu pedido <strong>{{ $pedido->numero_pedido }}</strong> fue confirmado correctamente.</p>
            <p class="payment-success-modal__redirect">
                Te llevaremos a la tienda en <strong><span id="paymentRedirectCountdown">3</span> segundos</strong>.
            </p>
            <a href="{{ route('tienda.inicio') }}" class="payment-success-modal__button">Ir a la tienda ahora</a>
        </div>
    </div>

    <x-slot:js>
        <script src="{{ asset('js/tienda/pagos/estado.js') }}"></script>
    </x-slot:js>
</x-layouts.app>
