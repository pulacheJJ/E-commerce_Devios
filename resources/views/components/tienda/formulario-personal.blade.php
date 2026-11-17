<form action="#" method="POST" id="formCheckout">
    @csrf
    <input type="hidden" name="departamento" id="hidden_departamento">
    <input type="hidden" name="provincia" id="hidden_provincia">
    <input type="hidden" name="distrito" id="hidden_distrito">
    <input type="hidden" name="direccion_envio" id="hidden_direccion">
    <input type="hidden" name="referencia" id="hidden_referencia">

    <div class="checkout-card-box">
        <h2 class="checkout-section-title">Datos Personales</h2>
        <div class="form-row-2">
            <div class="form-group"><label>Nombres</label><input type="text" name="nombres" class="form-control"
                    required></div>
            <div class="form-group"><label>Apellidos</label><input type="text" name="apellidos" class="form-control"
                    required></div>
        </div>
        <div class="form-row-2">
            <div class="form-group"><label>DNI / CE</label><input type="text" name="documento" class="form-control"
                    required></div>
            <div class="form-group"><label>Teléfono</label><input type="tel" name="telefono" class="form-control"
                    required></div>
        </div>
        <div class="form-group"><label>Correo Electrónico</label><input type="email" name="email"
                class="form-control" required></div>
    </div>

    <div class="checkout-card-box checkout-card-box--delivery">
        <h2 class="checkout-section-title checkout-section-title--delivery">Opciones de entrega</h2>

        <label class="delivery-option-card">
            <div class="delivery-option-card__content">
                <input type="radio" name="metodo_entrega" value="retiro" class="payment-radio">
                <div>
                    <strong><i class="fas fa-store"></i> Retiro en tienda</strong>
                    <p class="delivery-desc">Retira gratis en DEVIOZ Piura Mall.</p>
                </div>
            </div>
            <span class="delivery-price free">Gratis</span>
        </label>

        <label class="delivery-option-card active">
            <div class="delivery-option-card__content">
                <input type="radio" name="metodo_entrega" value="domicilio" checked class="payment-radio">
                <div>
                    <strong><i class="fas fa-truck"></i> Envío a domicilio</strong>
                    <p class="delivery-desc">Recibe tu pedido en la puerta de tu casa.</p>
                </div>
            </div>
        </label>

        <div id="boxDireccionResumen" class="address-summary-box">
            <div class="address-summary-box__content">
                <i class="fas fa-map-marker-alt address-summary-box__icon"></i>
                <div id="textoDireccionSeleccionada">
                    <span class="address-summary-box__missing">Falta agregar dirección de entrega</span>
                </div>
            </div>
            <button type="button" id="btnAbrirModalDireccion" class="btn-link-teal">
                <i class="fas fa-pen"></i> Cambiar
            </button>
        </div>
    </div>
</form>
