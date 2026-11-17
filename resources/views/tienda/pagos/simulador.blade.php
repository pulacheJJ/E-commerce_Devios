<x-layouts.app>
    <x-slot:title>Pasarela demostrativa | DEVIOZ</x-slot:title>
    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
        <link rel="stylesheet" href="{{ asset('css/tienda/pagos/simulador.css') }}">
    </x-slot:css>

    <main class="store-container payment-simulator">
        <section class="payment-simulator__panel">
            <div class="payment-simulator__warning" role="alert">
                <strong class="payment-simulator__warning-title">MODO DEMOSTRACIÓN</strong>
                <span>Esta pasarela es una simulación y no realiza cobros reales.</span><br>
                <span>No ingrese información bancaria real.</span>
            </div>

            @if (session('simulated_result'))
                <div class="payment-simulator__result" role="status">
                    @if (session('simulated_result') === 'aprobado')
                        <h2>Pago simulado aprobado</h2>
                        <p>El pedido fue confirmado correctamente.</p>
                        <p>Esta operación fue una demostración y no realizó ningún cobro real.</p>
                    @elseif (session('simulated_result') === 'rechazado')
                        <h2>Pago simulado rechazado</h2>
                        <p>El pedido no fue pagado. No se realizó ningún cobro real.</p>
                    @elseif (session('simulated_result') === 'pendiente')
                        <h2>Pago simulado pendiente</h2>
                        <p>El pedido todavía no ha sido confirmado. No se realizó ningún cobro real.</p>
                    @else
                        <h2>Proceso simulado cancelado</h2>
                        <p>El pedido no fue pagado y no se realizó ningún cobro real.</p>
                    @endif
                </div>
            @endif

            <h1>Transacción de demostración</h1>
            <dl class="payment-simulator__summary">
                <dt>Pedido</dt><dd>{{ $pedido->numero_pedido }}</dd>
                <dt>Identificador externo</dt><dd><code>{{ $pago->id_transaccion }}</code></dd>
                <dt>Subtotal</dt><dd>S/ {{ number_format($pedido->subtotal, 2) }}</dd>
                <dt>Descuento</dt><dd>S/ {{ number_format($pedido->descuento_total, 2) }}</dd>
                <dt>Envío</dt><dd>S/ {{ number_format($pedido->costo_envio, 2) }}</dd>
                <dt>Total</dt><dd><strong>S/ {{ number_format($pedido->total, 2) }} {{ $pago->moneda }}</strong></dd>
            </dl>

            @if ($pedido->estado === 'pendiente_pago')
                <div class="payment-simulator__actions">
                    @foreach ([
                        'aprobado' => 'Simular pago aprobado',
                        'rechazado' => 'Simular pago rechazado',
                        'pendiente' => 'Simular pago pendiente',
                    ] as $scenario => $label)
                        <form method="POST" action="{{ route('payments.simulator.simulate', $pago) }}">
                            @csrf
                            <input type="hidden" name="scenario" value="{{ $scenario }}">
                            <button class="btn btn--primary" type="submit">{{ $label }}</button>
                        </form>
                    @endforeach
                    <form method="POST" action="{{ route('payments.simulator.cancel', $pago) }}">
                        @csrf
                        <button class="btn btn--outline" type="submit">Cancelar simulación</button>
                    </form>
                </div>
            @else
                <a class="btn btn--primary" href="{{ route('tienda.inicio') }}">Volver a la tienda</a>
            @endif
        </section>
    </main>
</x-layouts.app>
