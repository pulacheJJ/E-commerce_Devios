document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // 1. GALERÍA DE IMÁGENES
    // ==========================================
    const imagenPrincipal = document.getElementById('imagenPrincipal');
    const thumbnails = document.querySelectorAll('.pdp-thumb');

    thumbnails.forEach(thumb => {
        thumb.addEventListener('mouseenter', function() { 
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            imagenPrincipal.src = this.getAttribute('data-src');
        });
    });

    // ==========================================
    // 2. VARIANTES Y VALIDACIÓN DE STOCK INLINE
    // ==========================================
    const variantPills = document.querySelectorAll('.variant-pill');
    const precioDisplay = document.getElementById('precioDisplay');
    const stockDisplay = document.getElementById('stockDisplay');
    const cantidadInput = document.getElementById('cantidadInput');
    const btnAgregarCarrito = document.getElementById('btnAgregarCarrito');
    const productDataElement = document.getElementById('productData');
    const opcionSeleccionadaText = document.getElementById('opcionSeleccionadaText');
    const errorCantidad = document.getElementById('errorCantidad'); // El nuevo span de error

    if (variantPills.length > 0 && productDataElement) {
        const variantes = JSON.parse(productDataElement.getAttribute('data-variantes'));

        // VALIDACIÓN EN VIVO: Si el cliente escribe un número mayor
        cantidadInput.addEventListener('input', function() {
            errorCantidad.textContent = ''; // Limpiamos el error
            const max = parseInt(this.max);
            
            if (parseInt(this.value) > max) {
                errorCantidad.textContent = `El límite es de ${max} unidades para este producto.`;
                this.value = max; // Auto-corrige el input
            }
        });

        variantPills.forEach(pill => {
            pill.addEventListener('click', function() {
                if(this.classList.contains('disabled')) return;

                variantPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                errorCantidad.textContent = ''; // Limpiar errores al cambiar opción

                const varianteId = parseInt(this.getAttribute('data-id'));
                const varianteSeleccionada = variantes.find(v => v.id === varianteId);

                if (varianteSeleccionada) {
                    opcionSeleccionadaText.textContent = varianteSeleccionada.color;
                    const precioReal = parseFloat(varianteSeleccionada.precio_oferta || varianteSeleccionada.precio);
                    precioDisplay.textContent = `S/ ${precioReal.toFixed(2)}`;

                    const stock = varianteSeleccionada.stock;
                    
                    // Lógica Visual de Stock
                    if (stock === 0) {
                        stockDisplay.textContent = 'Sin stock';
                        cantidadInput.disabled = true;
                        cantidadInput.value = 1;
                        btnAgregarCarrito.disabled = true;
                    } else {
                        stockDisplay.textContent = ''; // Ya no mostramos el "(8 disponibles)"
                        cantidadInput.disabled = false;
                        
                        // El máximo es el stock o 10
                        const limite = Math.min(stock, 10);
                        cantidadInput.max = limite;
                        
                        if (parseInt(cantidadInput.value) > limite) {
                            cantidadInput.value = limite; 
                        }

                        btnAgregarCarrito.disabled = false;
                        btnAgregarCarrito.textContent = 'Agregar al Carrito';
                    }
                }
            });
        });
    }

    // ==========================================
    // 3. AGREGAR AL CARRITO (AJAX + MODAL)
    // ==========================================
    const modalCarrito = document.getElementById('modalCarrito');
    const btnCerrarModal = document.getElementById('btnCerrarModal');
    const btnSeguirComprando = document.getElementById('btnSeguirComprando');

    if (btnAgregarCarrito && productDataElement) {
        btnAgregarCarrito.addEventListener('click', async function() {
            const varianteActiva = document.querySelector('.variant-pill.active');
            
            if (!varianteActiva) {
                errorCantidad.textContent = 'Por favor, selecciona una opción válida.';
                return;
            }

            const productoId = productDataElement.getAttribute('data-producto-id');
            const varianteId = varianteActiva.getAttribute('data-id');
            const cantidad = cantidadInput.value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            errorCantidad.textContent = ''; // Limpiar errores previos
            const textoOriginal = this.textContent;
            this.textContent = 'Agregando...';
            this.disabled = true;

            try {
                const response = await fetch('/carrito/agregar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ producto_id: productoId, variante_id: varianteId, cantidad: cantidad })
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    this.textContent = textoOriginal;
                    this.disabled = false;

                    document.getElementById('modalImagen').src = imagenPrincipal.src;
                    document.getElementById('modalVariante').textContent = 'Opción: ' + varianteActiva.querySelector('strong').textContent;
                    document.getElementById('modalPrecio').textContent = precioDisplay.textContent;
                    document.getElementById('modalCantidad').textContent = cantidad;

                    modalCarrito.classList.add('active');
                } else {
                    // AQUÍ ESTÁ LA MAGIA: Imprime el error del backend al costado del input
                    errorCantidad.textContent = data.message || 'Error al agregar';
                    this.textContent = textoOriginal;
                    this.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                errorCantidad.textContent = 'Ocurrió un error de conexión.';
                this.textContent = textoOriginal;
                this.disabled = false;
            }
        });
    }

    if (btnCerrarModal) btnCerrarModal.addEventListener('click', () => modalCarrito.classList.remove('active'));
    if (btnSeguirComprando) btnSeguirComprando.addEventListener('click', () => modalCarrito.classList.remove('active'));
});