document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    document.querySelectorAll('.js-cart-delete').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!window.confirm('¿Quitar este producto del carrito?')) return;

            try {
                const response = await fetch(`/carrito/eliminar/${button.dataset.itemId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
                });
                if (response.ok) window.location.reload();
            } catch (error) {
                console.error('Error al eliminar el producto del carrito.', error);
            }
        });
    });

    document.querySelectorAll('.js-cart-quantity').forEach((button) => {
        button.addEventListener('click', async () => {
            const itemId = button.dataset.itemId;
            const input = document.getElementById(`qty-${itemId}`);
            const newQuantity = Number.parseInt(input.value, 10) + Number.parseInt(button.dataset.change, 10);
            const maximum = Number.parseInt(button.dataset.max, 10);

            if (newQuantity < 1) return;
            if (newQuantity > maximum) {
                window.alert(`Solo puedes agregar hasta ${maximum} unidades de este producto.`);
                return;
            }

            input.value = '...';
            try {
                const response = await fetch(`/carrito/actualizar/${itemId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ cantidad: newQuantity }),
                });
                const data = await response.json();
                if (!response.ok || data.status !== 'success') {
                    window.alert(data.message || 'Error al actualizar la cantidad');
                }
            } catch (error) {
                window.alert('Error de conexión.');
            }
            window.location.reload();
        });
    });
});
