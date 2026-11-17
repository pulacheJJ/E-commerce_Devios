<div id="modalCarrito" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-success-title">
                <i class="fas fa-check-circle modal-success-title__icon"></i>
                <span>Producto agregado a tu Carro</span>
            </div>
            <button class="modal-close" id="btnCerrarModal">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="modal-product-info">
                <img id="modalImagen" src="" alt="Producto">
                <div class="modal-product-details">
                    <span class="modal-brand">{{ $producto->marca ?? 'Marca' }}</span>
                    <h4 id="modalNombre">{{ $producto->nombre }}</h4>
                    <span class="modal-variant" id="modalVariante"></span>
                </div>
                <div class="modal-product-price">
                    <h3 id="modalPrecio">S/ 0.00</h3>
                </div>
                <div class="modal-product-qty">
                    <span>Cant: <b id="modalCantidad">1</b></span>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-link" id="btnSeguirComprando">Seguir comprando</button>
            <a href="{{ route('carrito.index') }}" class="btn btn--primary">Ir al Carro</a>
        </div>
    </div>
</div>
