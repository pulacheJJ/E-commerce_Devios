<x-layouts.app>
    <x-slot:title>Carro de Compras | DEVIOZ</x-slot:title>
    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    </x-slot:css>

    <main class="store-container">

        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <h1 class="store-header__title" style="text-align: left; margin: 0;">
                Carro ({{ $carrito ? $carrito->cantidad_total : 0 }} productos)
            </h1>
            <a href="{{ route('tienda.inicio') }}"
                style="color: var(--teal); text-decoration: none; font-weight: 600; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
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
                                    <img src="{{ asset('storage/' . $item->variante->producto->imagen_principal) }}"
                                        alt="{{ $item->variante->producto->nombre }}">
                                @else
                                    <div
                                        style="width: 100px; height: 100px; background: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
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
                                    <button
                                        onclick="cambiarCantidad({{ $item->id }}, -1, {{ $item->limite_maximo }})">-</button>
                                    <input type="text" id="qty-{{ $item->id }}" value="{{ $item->cantidad }}"
                                        readonly>
                                    <button
                                        onclick="cambiarCantidad({{ $item->id }}, 1, {{ $item->limite_maximo }})">+</button>
                                </div>
                            </div>

                            <div class="cart-item-price">
                                <h3>S/ {{ number_format($item->subtotal, 2) }}</h3>

                                @if ($item->cantidad > 1)
                                    <span
                                        style="font-size: 0.8rem; color: #94a3b8; display: block; margin-bottom: 10px;">
                                        (S/ {{ number_format($item->precio_unitario_real, 2) }} c/u)
                                    </span>
                                @endif

                                <button class="btn-delete-item" onclick="eliminarItem({{ $item->id }})">
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="cart-empty">
                        <i class="fas fa-shopping-cart fa-3x" style="color: #64748b; margin-bottom: 15px;"></i>
                        <h2>Tu carro está vacío</h2>
                        <a href="{{ route('tienda.inicio') }}" class="btn btn--primary" style="margin-top: 15px;">Ir
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

                        <a href="{{ route('checkout.index') }}" class="btn btn--primary"
                            style="width: 100%; padding: 15px; margin-top: 30px; font-size: 1.1rem; text-align: center; display: block; text-decoration: none;">
                            Continuar compra
                        </a>
                    </div>
                </aside>
            @endif
        </div>
    </main>

    <x-slot:js>
        <script>
            async function eliminarItem(id) {
                if (!confirm('¿Quitar este producto del carrito?')) return;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                try {
                    const res = await fetch(`/carrito/eliminar/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) window.location.reload();
                } catch (e) {
                    console.error('Error:', e);
                }
            }

            async function cambiarCantidad(id, cambio, limiteMaximo) {
                const input = document.getElementById(`qty-${id}`);
                let nuevaCantidad = parseInt(input.value) + cambio;

                if (nuevaCantidad < 1) return;
                if (nuevaCantidad > limiteMaximo) {
                    alert(`Solo puedes agregar hasta ${limiteMaximo} unidades de este producto.`);
                    return;
                }

                input.value = '...';
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                try {
                    const res = await fetch(`/carrito/actualizar/${id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            cantidad: nuevaCantidad
                        })
                    });

                    const data = await res.json();

                    if (res.ok && data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error al actualizar la cantidad');
                        window.location.reload();
                    }
                } catch (e) {
                    alert('Error de conexión.');
                    window.location.reload();
                }
            }
        </script>
    </x-slot:js>
</x-layouts.app>
