document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // 1. GALERÍA DE IMÁGENES
    // ==========================================
    const imagenPrincipal = document.getElementById('imagenPrincipal');
    const thumbnails = document.querySelectorAll('.pdp-thumb');

    thumbnails.forEach(thumb => {
        thumb.addEventListener('mouseenter', function () {
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
        cantidadInput.addEventListener('input', function () {
            errorCantidad.textContent = ''; // Limpiamos el error
            const max = parseInt(this.max);

            if (parseInt(this.value) > max) {
                errorCantidad.textContent = `El límite es de ${max} unidades para este producto.`;
                this.value = max; // Auto-corrige el input
            }
        });

        variantPills.forEach(pill => {
            pill.addEventListener('click', function () {
                if (this.classList.contains('disabled')) return;

                variantPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                errorCantidad.textContent = '';

                const varianteId = parseInt(this.getAttribute('data-id'));
                const varianteSeleccionada = variantes.find(v => v.id === varianteId);

                if (varianteSeleccionada) {
                    opcionSeleccionadaText.textContent = varianteSeleccionada.color;

                    // --- ACTUALIZACIÓN DE PRECIOS SIN NaN ---
                    const precioDisplay = document.getElementById('precioDisplay');
                    const precioOriginalDisplay = document.getElementById('precioOriginalDisplay');
                    const promoBadge = document.getElementById('promoBadge');

                    // Actualizamos precio principal (final)
                    if (precioDisplay) {
                        precioDisplay.textContent = `S/ ${parseFloat(varianteSeleccionada.precio_final).toFixed(2)}`;
                    }

                    // Actualizamos precio tachado si existe
                    if (precioOriginalDisplay) {
                        precioOriginalDisplay.textContent = `S/ ${parseFloat(varianteSeleccionada.precio_original).toFixed(2)}`;
                    }

                    // Actualizamos la etiqueta de descuento (badge)
                    if (promoBadge) {
                        if (varianteSeleccionada.hay_descuento) {
                            promoBadge.textContent = varianteSeleccionada.descuento_info;
                            promoBadge.style.display = 'inline-block';
                        } else {
                            promoBadge.style.display = 'none';
                        }
                    }

                    // Lógica Visual de Stock
                    const stock = varianteSeleccionada.stock;
                    if (stock === 0) {
                        stockDisplay.textContent = 'Sin stock';
                        cantidadInput.disabled = true;
                        cantidadInput.value = 1;
                        btnAgregarCarrito.disabled = true;
                    } else {
                        stockDisplay.textContent = '';
                        cantidadInput.disabled = false;

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
    const btnComprarAhora = document.getElementById('btnComprarAhora'); // El nuevo botón

    // Función unificada para no repetir código (DRY)
    // 1. CREAMOS LA VARIABLE GLOBAL FUERA DE LA FUNCIÓN
    let isProcessing = false;

    async function procesarAccionCarrito(btnElement, esCompraRapida = false) {
        // 2. CANDADO DE ENTRADA: Si ya se está procesando una petición, bloqueamos esta.
        if (isProcessing) {
            console.warn("Petición bloqueada: Ya se está procesando un producto.");
            return;
        }

        const varianteActiva = document.querySelector('.variant-pill.active');

        if (!varianteActiva) {
            errorCantidad.textContent = 'Por favor, selecciona una opción válida.';
            return;
        }

        // 3. CERRAMOS EL CANDADO: Indicamos que empezó el proceso
        isProcessing = true;

        const productoId = productDataElement.getAttribute('data-producto-id');
        const varianteId = varianteActiva.getAttribute('data-id');
        const cantidad = cantidadInput.value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        errorCantidad.textContent = '';
        const textoOriginal = btnElement.textContent;
        btnElement.textContent = esCompraRapida ? 'Redirigiendo...' : 'Agregando...';
        btnElement.disabled = true;

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
                if (esCompraRapida) {
                    // Si es compra rápida, lo mandamos directo a la caja
                    window.location.href = '/checkout';
                } else {
                    // Si es agregar normal, restauramos el botón y mostramos el modal
                    btnElement.textContent = textoOriginal;
                    btnElement.disabled = false;

                    document.getElementById('modalImagen').src = imagenPrincipal.src;
                    document.getElementById('modalVariante').textContent = 'Opción: ' + varianteActiva.querySelector('strong').textContent;
                    document.getElementById('modalPrecio').textContent = precioDisplay.textContent;
                    document.getElementById('modalCantidad').textContent = cantidad;

                    modalCarrito.classList.add('active');
                }
            } else {
                errorCantidad.textContent = data.message || 'Error al procesar';
                btnElement.textContent = textoOriginal;
                btnElement.disabled = false;
            }
        } catch (error) {
            console.error('Error:', error);
            errorCantidad.textContent = 'Ocurrió un error de conexión.';
            btnElement.textContent = textoOriginal;
            btnElement.disabled = false;
        } finally {
            // 4. ABRIMOS EL CANDADO SIEMPRE: Pase lo que pase (éxito o error), liberamos el proceso
            isProcessing = false;
        }
    }

    // Asignamos los eventos a ambos botones
    if (btnAgregarCarrito && productDataElement) {
        btnAgregarCarrito.addEventListener('click', function () {
            procesarAccionCarrito(this, false); // false = Mostrar modal
        });
    }

    if (btnComprarAhora && productDataElement) {
        btnComprarAhora.addEventListener('click', function () {
            procesarAccionCarrito(this, true); // true = Redirigir al checkout
        });
    }

    if (btnCerrarModal) btnCerrarModal.addEventListener('click', () => modalCarrito.classList.remove('active'));
    if (btnSeguirComprando) btnSeguirComprando.addEventListener('click', () => modalCarrito.classList.remove('active'));
});