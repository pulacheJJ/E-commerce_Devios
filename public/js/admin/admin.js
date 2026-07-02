document.addEventListener('DOMContentLoaded', () => {
    const formLogout = document.getElementById('formLogout');

    if (formLogout) {
        formLogout.addEventListener('submit', (e) => {
            // Puedes usar un confirm nativo o cambiarlo por un SweetAlert si lo usas en el proyecto
            const confirmar = confirm('¿Estás seguro que deseas cerrar tu sesión en el Workspace?');

            if (!confirmar) {
                e.preventDefault(); // Detiene el envío si el usuario cancela
            }
        });
    }
});