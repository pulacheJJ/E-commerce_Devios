<x-layouts.app>
    <x-slot:title>Pago Seguro | DEVIOZ</x-slot:title>
    
    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    </x-slot:css>

    <main class="store-container">
        
        <div class="checkout-stepper">
            <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><span>Carro</span></div>
            <div class="step-line completed"></div>
            <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><span>Entrega</span></div>
            <div class="step-line completed"></div>
            <div class="step active"><div class="step-circle">3</div><span>Pago</span></div>
        </div>

        <div class="checkout-grid">
            <section class="checkout-form-card pago-section-transparent">
                
                <h2 class="checkout-section-title">Selecciona tu método de pago</h2>
                <p class="pago-subtitle">Estás pagando la orden: <strong class="text-teal">{{ $pedido->numero_pedido }}</strong></p>

                <form id="formPagoSeguro">
                    @csrf
                    <input type="hidden" name="numero_pedido" value="{{ $pedido->numero_pedido }}">
                    
                    <div class="delivery-options-container pago-options-wrapper">
                        
                        <label class="delivery-option-card pago-option-card active">
                            <div class="pago-option-inner">
                                <input type="radio" name="metodo_pago" value="tarjeta" checked class="payment-radio">
                                <div>
                                    <strong><i class="far fa-credit-card"></i> Tarjeta de Crédito / Débito</strong>
                                    <p class="delivery-desc">Paga seguro a través de MercadoPago.</p>
                                </div>
                            </div>
                        </label>

                        <label class="delivery-option-card pago-option-card">
                            <div class="pago-option-inner">
                                <input type="radio" name="metodo_pago" value="yape" class="payment-radio">
                                <div>
                                    <strong><img src="https://seeklogo.com/images/Y/yape-logo-3E473EE7E5-seeklogo.com.png" alt="Yape" class="yape-logo"> Yape / Plin</strong>
                                    <p class="delivery-desc">Escanea el código QR desde tu celular.</p>
                                </div>
                            </div>
                        </label>

                    </div>
                </form>
            </section>

            <aside class="checkout-summary">
                <h2 class="checkout-section-title summary-title-pago">Resumen</h2>
                
                <div class="summary-totals-row mt-20">
                    <span>Subtotal de productos:</span>
                    <span>S/ {{ number_format($pedido->total, 2) }}</span>
                </div>
                
                <div class="summary-grand-total pago-grand-total">
                    <span>Total a pagar:</span>
                    <span>S/ {{ number_format($pedido->total, 2) }}</span>
                </div>

                <button type="button" id="btnConfirmarPago" class="btn btn--primary checkout-btn-pay btn-confirm-pago">
                    <i class="fas fa-lock"></i> Confirmar Pago
                </button>
            </aside>
        </div>
    </main>

    <x-slot:js>
        <script src="{{ asset('js/tienda/checkout.js') }}"></script>
    </x-slot:js>
</x-layouts.app>