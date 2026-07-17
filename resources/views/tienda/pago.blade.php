<x-layouts.app>
    <x-slot:title>Pago pendiente | DEVIOZ</x-slot:title>

    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
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
            <section class="checkout-form-card pago-section-transparent">
                <h2 class="checkout-section-title">Pago pendiente de verificación</h2>
                <p>
                    La orden <strong class="text-teal">{{ $pedido->numero_pedido }}</strong> fue creada correctamente.
                </p>
                <p>
                    El pago en línea está temporalmente deshabilitado hasta conectar una pasarela con verificación
                    mediante webhook. No se ha realizado ningún cargo ni se marcará la orden como pagada desde esta página.
                </p>
            </section>

            <aside class="checkout-summary">
                <h2 class="checkout-section-title summary-title-pago">Resumen</h2>
                <div class="summary-grand-total pago-grand-total">
                    <span>Total pendiente:</span>
                    <span>S/ {{ number_format($pedido->total, 2) }}</span>
                </div>
                <a href="{{ route('tienda.inicio') }}" class="btn btn--primary checkout-btn-pay btn-confirm-pago">
                    Volver a la tienda
                </a>
            </aside>
        </div>
    </main>
</x-layouts.app>
