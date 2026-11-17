<x-layouts.app>
    <x-slot:title>Entrega | DEVIOZ</x-slot:title>

    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    </x-slot:css>

    <main class="store-container">
        <!-- STEPPER -->
        <div class="checkout-stepper">
            <div class="step completed">
                <div class="step-circle"><i class="fas fa-check"></i></div><span>Carro</span>
            </div>
            <div class="step-line completed"></div>
            <div class="step active">
                <div class="step-circle">2</div><span>Entrega</span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-circle">3</div><span>Pago</span>
            </div>
        </div>

        <div class="checkout-grid">
            <section class="checkout-form-card checkout-form-card--plain">
                <x-tienda.formulario-personal />
            </section>

            <!-- Resumen Lateral -->
            <aside class="checkout-summary">
                <h2 class="checkout-section-title checkout-summary__title">Resumen de la compra
                </h2>

                <div class="checkout-items-list">
                    @foreach ($carrito->elementos as $item)
                        <div class="summary-item">
                            <div class="summary-item-info">
                                @if ($item->variante->producto->imagen_principal)
                                    <img src="{{ $item->variante->producto->imagen_principal_url }}"
                                        alt="img">
                                @else
                                    <div class="summary-no-img">Sin foto</div>
                                @endif
                                <div>
                                    <h4>{{ $item->variante->producto->nombre }}</h4>
                                    <span>Cant: {{ $item->cantidad }} | {{ $item->variante->color }}</span>
                                </div>
                            </div>
                            <div class="summary-item-price">S/ {{ number_format($item->subtotal, 2) }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="summary-totals-row"><span>Productos</span><span>S/
                        {{ number_format($carrito->total, 2) }}</span></div>
                <div class="summary-totals-row"><span>Entregas</span><span id="resumenCostoEnvio">Por calcular</span>
                </div>

                <div class="summary-grand-total summary-grand-total--spaced">
                    <span>Total:</span><span>S/ {{ number_format($carrito->total, 2) }}</span>
                </div>

                <button type="button" id="btnProcesarPago" class="btn btn--primary checkout-btn-pay">
                    Ir a pagar
                </button>
            </aside>
        </div>

        <!-- LLAMADA AL COMPONENTE DEL MODAL -->
        <x-tienda.modal-direccion />

    </main>

    <x-slot:js>
        <script src="{{ asset('js/tienda/ubigeo.js') }}"></script>
        <script src="{{ asset('js/tienda/checkout.js') }}?v={{ time() }}"></script>
    </x-slot:js>
</x-layouts.app>
