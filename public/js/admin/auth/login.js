document.addEventListener('DOMContentLoaded', () => {
    const formLogin = document.getElementById('formLogin');
    const btnLogin = document.getElementById('btnLogin');
    const btnTogglePass = document.getElementById('btnTogglePass');
    const inputPassword = document.getElementById('password');

    // Toggle de visibilidad de contraseña
    if (btnTogglePass && inputPassword) {
        btnTogglePass.addEventListener('click', () => {
            const type = inputPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            inputPassword.setAttribute('type', type);
            
            const icon = btnTogglePass.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    // Prevención de doble clic 
    if (formLogin) {
        formLogin.addEventListener('submit', (e) => {
            if (btnLogin.disabled) {
                e.preventDefault();
                return;
            }
            
            // Cambiamos estado del botón a cargando
            btnLogin.disabled = true;
            btnLogin.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Verificando...</span>';
        });
    }
});