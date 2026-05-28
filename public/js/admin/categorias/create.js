// public/js/admin/categorias/create.js

document.addEventListener('DOMContentLoaded', () => {
    // 1. Capturamos los elementos del DOM
    const inputNombre = document.getElementById('nombre');
    const inputSlug = document.getElementById('slug');

    // Validamos que los elementos existan en la página para evitar errores de consola
    if (inputNombre && inputSlug) {
        
        // 2. Escuchamos cada vez que el usuario escribe en el campo "Nombre"
        inputNombre.addEventListener('input', function() {
            
            // 3. Transformamos el texto ingresado
            let slug = this.value
                .toLowerCase() // Convertir todo a minúsculas
                .trim() // Quitar espacios al inicio y al final
                .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // Magia negra para quitar tildes y la 'ñ'
                .replace(/[^a-z0-9 -]/g, '') // Borrar cualquier carácter que no sea letra, número, espacio o guion
                .replace(/\s+/g, '-') // Reemplazar los espacios en blanco por guiones
                .replace(/-+/g, '-'); // Evitar que queden guiones duplicados juntos (ej: ---)

            // 4. Inyectamos el resultado en el campo "Slug" en tiempo real
            inputSlug.value = slug;
        });
    }
});