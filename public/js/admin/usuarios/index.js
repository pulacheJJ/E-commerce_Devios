document.addEventListener('DOMContentLoaded', () => {
    const toggleButtons = document.querySelectorAll('.toggle-status-btn');

    toggleButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const userId = this.getAttribute('data-id');
            const actionText = this.getAttribute('title');
            
            if (confirm(`¿Estás seguro que deseas ${actionText.toLowerCase()} a este usuario?`)) {
                try {
                    const response = await fetch(`/admin/usuarios/${userId}/toggle`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Refrescar la página para actualizar la tabla y los badges
                        window.location.reload();
                    } else {
                        alert(data.message || 'Ocurrió un error al procesar la solicitud.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error de conexión con el servidor.');
                }
            }
        });
    });
});