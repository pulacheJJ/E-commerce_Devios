document.addEventListener('DOMContentLoaded', () => {
    
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

});