<x-layouts.app>
    <x-slot:title>Entrega | DEVIOZ</x-slot:title>
    
    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    </x-slot:css>

    <main class="store-container">
        <!-- STEPPER TIPO FALABELLA -->
        <div class="checkout-stepper">
            <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><span>Carro</span></div>
            <div class="step-line completed"></div>
            <div class="step active"><div class="step-circle">2</div><span>Entrega</span></div>
            <div class="step-line"></div>
            <div class="step"><div class="step-circle">3</div><span>Pago</span></div>
        </div>

        <div class="checkout-grid">
            <section class="checkout-form-card" style="padding: 0; background: transparent; border: none;">
                
                <!-- FORMULARIO PRINCIPAL (Envía los datos al backend) -->
                <form action="#" method="POST" id="formCheckout">
                    @csrf
                    <!-- Inputs ocultos que se llenarán desde el Modal de Dirección -->
                    <input type="hidden" name="departamento" id="hidden_departamento">
                    <input type="hidden" name="provincia" id="hidden_provincia">
                    <input type="hidden" name="distrito" id="hidden_distrito">
                    <input type="hidden" name="direccion_envio" id="hidden_direccion">
                    <input type="hidden" name="referencia" id="hidden_referencia">

                    <!-- DATOS PERSONALES -->
                    <div class="checkout-card-box">
                        <h2 class="checkout-section-title">Datos Personales</h2>
                        <div class="form-row-2">
                            <div class="form-group"><label>Nombres</label><input type="text" name="nombres" class="form-control" required></div>
                            <div class="form-group"><label>Apellidos</label><input type="text" name="apellidos" class="form-control" required></div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group"><label>DNI / CE</label><input type="text" name="documento" class="form-control" required></div>
                            <div class="form-group"><label>Teléfono</label><input type="tel" name="telefono" class="form-control" required></div>
                        </div>
                        <div class="form-group"><label>Correo Electrónico</label><input type="email" name="email" class="form-control" required></div>
                    </div>

                    <!-- OPCIONES DE DESPACHO -->
                    <div class="checkout-card-box" style="margin-top: 20px; padding: 0;">
                        <h2 class="checkout-section-title" style="padding: 20px 20px 10px;">Opciones de entrega</h2>
                        
                        <label class="delivery-option-card">
                            <div style="display: flex; gap: 15px;">
                                <input type="radio" name="metodo_entrega" value="retiro" class="payment-radio">
                                <div>
                                    <strong><i class="fas fa-store"></i> Retiro en tienda</strong>
                                    <p class="delivery-desc">Retira gratis en DEVIOZ Piura Mall.</p>
                                </div>
                            </div>
                            <span class="delivery-price free">Gratis</span>
                        </label>

                        <label class="delivery-option-card active">
                            <div style="display: flex; gap: 15px;">
                                <input type="radio" name="metodo_entrega" value="domicilio" checked class="payment-radio">
                                <div>
                                    <strong><i class="fas fa-truck"></i> Envío a domicilio</strong>
                                    <p class="delivery-desc">Recibe tu pedido en la puerta de tu casa.</p>
                                </div>
                            </div>
                        </label>

                        <!-- CAJA RESUMEN DE DIRECCIÓN -->
                        <div id="boxDireccionResumen" class="address-summary-box">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <i class="fas fa-map-marker-alt" style="font-size: 1.5rem; color: #64748b;"></i>
                                <div id="textoDireccionSeleccionada">
                                    <span style="color: #ef4444; font-weight: 500;">Falta agregar dirección de entrega</span>
                                </div>
                            </div>
                            <button type="button" id="btnAbrirModalDireccion" class="btn-link-teal">
                                <i class="fas fa-pen"></i> Cambiar
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Resumen Lateral -->
            <aside class="checkout-summary">
                <h2 class="checkout-section-title" style="margin-top: 0; border-bottom: 1px solid #1e293b; padding-bottom: 10px;">Resumen de la compra</h2>
                
                <div class="checkout-items-list">
                    @foreach($carrito->elementos as $item)
                        <div class="summary-item">
                            <div class="summary-item-info">
                                @if($item->variante->producto->imagen_principal) 
                                    <img src="{{ asset('storage/' . $item->variante->producto->imagen_principal) }}" alt="img">
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

                <div class="summary-totals-row"><span>Productos</span><span>S/ {{ number_format($carrito->total, 2) }}</span></div>
                <div class="summary-totals-row"><span>Entregas</span><span id="resumenCostoEnvio">Por calcular</span></div>
                
                <div class="summary-grand-total" style="margin-bottom: 20px;">
                    <span>Total:</span><span>S/ {{ number_format($carrito->total, 2) }}</span>
                </div>

                <button type="submit" form="formCheckout" id="btnProcesarPago" class="btn btn--primary checkout-btn-pay">
                    Ir a pagar
                </button>
            </aside>
        </div>

        <!-- LLAMADA AL COMPONENTE DEL MODAL -->
        <x-tienda.modal-direccion />

    </main>

    <x-slot:js>
        <script src="{{ asset('js/tienda/checkout.js') }}"></script>
    </x-slot:js>
</x-layouts.app>