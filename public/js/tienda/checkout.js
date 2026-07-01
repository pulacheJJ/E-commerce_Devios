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

    // ==========================================
    // VARIABLES DOM (PASO 3: PAGO)
    // ==========================================
    const formPagoSeguro = document.getElementById('formPagoSeguro');
    const btnConfirmarPago = document.getElementById('btnConfirmarPago');
    const radiosPago = document.querySelectorAll('input[name="metodo_pago"]');


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
                    hDir.value = 'RETIRO EN TIENDA PIURA MALL';
                    hDep.value = 'Piura';
                    hProv.value = 'Piura';
                    hDist.value = 'Castilla';
                } else {
                    boxDireccionResumen.style.display = 'flex';
                    hDir.value = txtDir.value; // Restaura si había algo guardado
                }
            });
        });

        // 2. MODAL DE DIRECCIÓN
        if(btnAbrirModal) btnAbrirModal.addEventListener('click', () => modalDireccion.classList.add('active'));
        if(btnCerrarModal) btnCerrarModal.addEventListener('click', () => modalDireccion.classList.remove('active'));

        if(btnGuardarDireccion) {
            btnGuardarDireccion.addEventListener('click', () => {
                if(!selDep.value || !selProv.value || !selDist.value || !txtDir.value.trim()) {
                    alert("Por favor, completa los campos de ubicación y dirección exacta.");
                    return;
                }

                hDep.value = selDep.options[selDep.selectedIndex].text;
                hProv.value = selProv.options[selProv.selectedIndex].text;
                hDist.value = selDist.options[selDist.selectedIndex].text;
                hDir.value = txtDir.value.trim();
                hRef.value = txtRef.value.trim();

                txtDireccionSeleccionada.innerHTML = `
                    <strong style="color: var(--ink); display: block;">${hDir.value}</strong>
                    <span style="color: #64748b; font-size: 0.85rem;">${hDist.value}, ${hProv.value}, ${hDep.value}</span>
                `;

                modalDireccion.classList.remove('active');
            });
        }

        // 3. CONSUMO DE API UBIGEO
        async function cargarDepartamentos() {
            try {
                const data = [{ id: '20', nombre: 'Piura' }, { id: '15', nombre: 'Lima' }];
                selDep.innerHTML = '<option value="">Selecciona Departamento</option>';
                data.forEach(d => selDep.innerHTML += `<option value="${d.id}">${d.nombre}</option>`);
            } catch (error) { console.error(error); }
        }

        if(selDep) {
            selDep.addEventListener('change', async function() {
                selProv.innerHTML = '<option value="">Cargando...</option>';
                selProv.disabled = true;
                selDist.innerHTML = '<option value="">Selecciona una opción</option>';
                selDist.disabled = true;

                if(!this.value) return;

                const data = [{ id: '2001', nombre: 'Piura' }, { id: '2006', nombre: 'Sullana' }]; 
                selProv.innerHTML = '<option value="">Selecciona Provincia</option>';
                data.forEach(d => selProv.innerHTML += `<option value="${d.id}">${d.nombre}</option>`);
                selProv.disabled = false;
            });
        }

        if(selProv) {
            selProv.addEventListener('change', async function() {
                selDist.innerHTML = '<option value="">Cargando...</option>';
                selDist.disabled = true;
                
                if(!this.value) return;
                
                const data = [{ id: '200114', nombre: 'Tambogrande' }, { id: '200104', nombre: 'Castilla' }];
                selDist.innerHTML = '<option value="">Selecciona Distrito</option>';
                data.forEach(d => selDist.innerHTML += `<option value="${d.id}">${d.nombre}</option>`);
                selDist.disabled = false;
            });
        }

        cargarDepartamentos();

        // 4. PROCESAR PASO 2 (IR A PAGAR)
        formCheckout.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const metodoEntrega = document.querySelector('input[name="metodo_entrega"]:checked').value;
            if (metodoEntrega === 'domicilio' && (!hDir.value || hDir.value.trim() === '')) {
                alert("Por favor, ingresa una dirección de envío haciendo clic en 'Cambiar'.");
                return;
            }

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
                    // Redirige al paso 3
                    window.location.href = result.redirect_url; 
                } else {
                    alert('Error: ' + (result.message || 'Verifica tus datos.'));
                    btnProcesarPago.innerHTML = textoOriginal;
                    btnProcesarPago.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error de conexión con el servidor.');
                btnProcesarPago.innerHTML = textoOriginal;
                btnProcesarPago.disabled = false;
            }
        });
    }

    /* =========================================================
       LÓGICA DEL PASO 3 (PAGO)
       ========================================================= */
    if (formPagoSeguro && btnConfirmarPago) {

        // 1. ESTILOS VISUALES DE SELECCIÓN DE PAGO
        radiosPago.forEach(radio => {
            radio.addEventListener('change', (e) => {
                document.querySelectorAll('.delivery-option-card').forEach(c => {
                    c.classList.remove('active');
                    c.style.borderColor = '#1e293b';
                });
                const card = e.target.closest('.delivery-option-card');
                card.classList.add('active');
                card.style.borderColor = 'var(--teal)';
            });
        });

        // 2. CONFIRMAR PAGO FINAL (AJAX AL BACKEND)
        btnConfirmarPago.addEventListener('click', async () => {
            const originalText = btnConfirmarPago.innerHTML;
            btnConfirmarPago.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando transacción...';
            btnConfirmarPago.disabled = true;

            const formData = new FormData(formPagoSeguro);
            const data = Object.fromEntries(formData.entries());
            const csrfToken = document.querySelector('input[name="_token"]').value;

            try {
                const response = await fetch('/checkout/pago/confirmar', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': csrfToken, 
                        'Accept': 'application/json' 
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    alert(result.message);
                    window.location.href = result.redirect_url;
                } else {
                    alert(result.message || 'Error al procesar el pago.');
                    btnConfirmarPago.innerHTML = originalText;
                    btnConfirmarPago.disabled = false;
                }
            } catch (error) {
                alert('Error de conexión con el banco.');
                btnConfirmarPago.innerHTML = originalText;
                btnConfirmarPago.disabled = false;
            }
        });
    }
});