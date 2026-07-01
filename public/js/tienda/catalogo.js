document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // 1. LÓGICA DEL BUSCADOR PRINCIPAL
    // ==========================================
    const btnLimpiar = document.getElementById('btnLimpiarBuscar');
    const inputBuscar = document.getElementById('inputBuscar');
    const formBuscador = document.getElementById('formBuscador');

    // Si existe el botón (es decir, si hay una búsqueda activa)
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            // Limpiamos el texto
            inputBuscar.value = '';
            // Enviamos el formulario vacío para limpiar la URL
            formBuscador.submit();
        });
    }

    // ==========================================
    // 2. LÓGICA DE FILTROS AUTOMÁTICOS (SIDEBAR)
    // ==========================================
    const filtersForm = document.getElementById('storeFiltersForm');
    const autoInputs = document.querySelectorAll('.store-input-auto');

    if (filtersForm) {
        // Le añadimos una clase al body/form para saber que JS está activo
        // Esto oculta el botón "Aplicar Filtros" (ver CSS .js-active)
        document.body.classList.add('js-active');

        // Escuchamos cada vez que se marca/desmarca un checkbox o radio
        autoInputs.forEach(input => {
            input.addEventListener('change', () => {
                
                // Opcional: Feedback visual de que está cargando (estilo DEVIOZ)
                document.body.style.opacity = '0.7';
                document.body.style.pointerEvents = 'none'; // Evita doble clic

                // Enviamos el formulario automáticamente al servidor
                filtersForm.submit();
            });
        });
    }
});