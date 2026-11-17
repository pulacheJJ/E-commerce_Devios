<x-layouts.app>
    <x-slot:title>Pago con Mercado Pago | DEVIOZ</x-slot:title>

    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
        <link rel="stylesheet" href="{{ asset('css/tienda/pagos/mercadopago.css') }}">
    </x-slot:css>

    <main class="store-container mercado-pago-page">
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

        <div class="mercado-pago-grid">
            <section class="mercado-pago-panel">
                <span class="mercado-pago-badge">Entorno de prueba</span>
                <h1>Completa tu pago en Mercado Pago</h1>
                <p>El pago se abrirá mediante el componente oficial de Mercado Pago.</p>
                <p class="mercado-pago-notice">Esta integración utiliza credenciales de prueba y no realiza cobros reales.</p>

                <div
                    id="mercadoPagoCheckout"
                    class="mercado-pago-checkout"
                    data-public-key="{{ $publicKey }}"
                    data-preference-id="{{ $preferenceId }}"
                >
                    <div id="mercadoPagoLoading" class="mercado-pago-loading" role="status">
                        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        <span>Preparando Mercado Pago...</span>
                    </div>
                    <div id="walletBrick_container"></div>
                    <div id="mercadoPagoError" class="mercado-pago-error" role="alert" hidden>
                        No fue posible cargar Mercado Pago. Verifica tu conexión e inténtalo nuevamente.
                    </div>
                </div>
            </section>

            <aside class="checkout-summary">
                <h2 class="checkout-section-title checkout-summary__title">Resumen</h2>
                <div class="summary-totals-row"><span>Pedido</span><span>{{ $pedido->numero_pedido }}</span></div>
                <div class="summary-totals-row"><span>Subtotal</span><span>S/ {{ number_format($pedido->subtotal, 2) }}</span></div>
                <div class="summary-totals-row"><span>Descuento</span><span>- S/ {{ number_format($pedido->descuento_total, 2) }}</span></div>
                <div class="summary-totals-row"><span>Entrega</span><span>S/ {{ number_format($pedido->costo_envio, 2) }}</span></div>
                <div class="summary-grand-total mercado-pago-total">
                    <span>Total</span><span>S/ {{ number_format($pedido->total, 2) }}</span>
                </div>
                <p class="mercado-pago-reservation">Tu stock permanece reservado mientras completas el pago.</p>
            </aside>
        </div>
    </main>

    <x-slot:js>
        <script src="https://sdk.mercadopago.com/js/v2"></script>
        <script src="{{ asset('js/tienda/pagos/mercadopago.js') }}"></script>
    </x-slot:js>
</x-layouts.app>
