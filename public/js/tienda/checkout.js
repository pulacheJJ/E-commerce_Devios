document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // VARIABLES DOM (PASO 2: ENTREGA)
    // ==========================================
    const formCheckout = document.getElementById('formCheckout');
    const btnProcesarPago = document.getElementById('btnProcesarPago');

    const radiosEntrega = document.querySelectorAll('input[name="metodo_entrega"]');
    const boxDireccionResumen = document.getElementById('boxDireccionResumen');

    const modalDireccion = document.getElementById('modalDireccion');
    const btnAbrirModal = document.getElementById('btnAbrirModalDireccion');
    const btnCerrarModal = document.getElementById('btnCerrarModalDir');
    const btnGuardarDireccion = document.getElementById('btnGuardarDireccion');

    const selDep = document.getElementById('selDepartamento');
    const selProv = document.getElementById('selProvincia');
    const selDist = document.getElementById('selDistrito');
    const txtDir = document.getElementById('txtDireccion');
    const txtRef = document.getElementById('txtReferencia');

    const hDep = document.getElementById('hidden_departamento');
    const hProv = document.getElementById('hidden_provincia');
    const hDist = document.getElementById('hidden_distrito');
    const hDir = document.getElementById('hidden_direccion');
    const hRef = document.getElementById('hidden_referencia');

    const txtDireccionSeleccionada = document.getElementById('textoDireccionSeleccionada');

    /* =========================================================
       LÓGICA DEL PASO 2 (ENTREGA)
       ========================================================= */
    if (formCheckout) {

        // 1. RETIRO VS DOMICILIO
        radiosEntrega.forEach(radio => {
            radio.addEventListener('change', (e) => {
                document.querySelectorAll('.delivery-option-card').forEach(c => c.classList.remove('active'));
                e.target.closest('.delivery-option-card').classList.add('active');

                if (e.target.value === 'retiro') {
                    boxDireccionResumen.style.display = 'none';
                    hDir.value = 'RETIRO EN TIENDA DEVIOZ ';
                    hDep.value = '';
                    hProv.value = '';
                    hDist.value = '';
                } else {
                    boxDireccionResumen.style.display = 'flex';
                    hDir.value = txtDir.value; // Restaura si había algo guardado
                }
            });
        });

        // 2. MODAL DE DIRECCIÓN
        if (btnAbrirModal) btnAbrirModal.addEventListener('click', () => modalDireccion.classList.add('active'));
        if (btnCerrarModal) btnCerrarModal.addEventListener('click', () => modalDireccion.classList.remove('active'));

        if (btnGuardarDireccion) {
            btnGuardarDireccion.addEventListener('click', () => {
                // 1. Validación de campos obligatorios
                if (!selDep.value || !selProv.value || !selDist.value || !txtDir.value.trim()) {
                    alert("Por favor, completa los campos de ubicación y la dirección exacta.");
                    return;
                }

                // 2. Asignación de nombres (para el resumen visual)
                hDep.value = selDep.value;
                hProv.value = selProv.value;
                hDist.value = selDist.value;

                // 3. Asignación del ID del distrito (ESTO ES LO QUE ESTABA FALTANDO PARA TU ERROR)
                // El value de selDist YA es el ID (ej: 200114), así que lo asignamos directamente
                const distritoIdInput = document.getElementById('hidden_distrito');
                distritoIdInput.value = selDist.value;

                // 4. Asignación de dirección y referencia
                hDir.value = txtDir.value.trim();
                hRef.value = txtRef.value.trim();

                // 5. Actualización visual en el resumen
                txtDireccionSeleccionada.innerHTML = `
            <strong style="color: var(--ink); display: block;">${hDir.value}</strong>
            <span style="color: #64748b; font-size: 0.85rem;">${selDist.options[selDist.selectedIndex].text}, ${selProv.options[selProv.selectedIndex].text}, ${selDep.options[selDep.selectedIndex].text}</span>
        `;

                // 6. Cierre del modal
                modalDireccion.classList.remove('active');

                console.log("Datos capturados correctamente:", {
                    distrito_id: distritoIdInput.value,
                    direccion: hDir.value
                });
            });
        }

        // 4. PROCESAR PASO 2 (IR A PAGAR)
        let enviandoPago = false;

        btnProcesarPago.addEventListener('click', async function (e) {
            e.preventDefault();

            // 1. Si el candado está cerrado, abortamos la función instantáneamente
            if (enviandoPago) {
                console.log("Bloqueando doble clic fantasma...");
                return;
            }

            const metodoEntrega = document.querySelector('input[name="metodo_entrega"]:checked').value;
            if (metodoEntrega === 'domicilio' && (!hDir.value || hDir.value.trim() === '')) {
                alert("Por favor, ingresa una dirección de envío.");
                return;
            }

            // 2. CERRAMOS EL CANDADO
            enviandoPago = true;

            // Solo para depurar: Si ves esto 2 veces en consola, tienes doble evento
            console.log(">>> ENVIANDO FETCH AL BACKEND <<<");

            const textoOriginal = btnProcesarPago.innerHTML;
            btnProcesarPago.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparando pago...';
            btnProcesarPago.disabled = true;

            const formData = new FormData(formCheckout);
            const data = Object.fromEntries(formData.entries());
            const csrfToken = document.querySelector('input[name="_token"]').value;

            try {
                const response = await fetch('/checkout/procesar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    window.location.href = result.redirect_url;
                } else {
                    alert('Error: ' + (result.message || 'Verifica tus datos.'));
                    btnProcesarPago.innerHTML = textoOriginal;
                    btnProcesarPago.disabled = false;
                    enviandoPago = false; // Abrimos el candado
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error de conexión con el servidor.');
                btnProcesarPago.innerHTML = textoOriginal;
                btnProcesarPago.disabled = false;
                enviandoPago = false; // Abrimos el candado
            }
        });
    }

});
