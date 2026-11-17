document.addEventListener('DOMContentLoaded', async () => {
    const checkout = document.getElementById('mercadoPagoCheckout');

    if (!checkout || checkout.dataset.initialized === 'true') {
        return;
    }

    checkout.dataset.initialized = 'true';
    const loading = document.getElementById('mercadoPagoLoading');
    const error = document.getElementById('mercadoPagoError');
    const publicKey = checkout.dataset.publicKey;
    const preferenceId = checkout.dataset.preferenceId;

    const showError = () => {
        if (loading) {
            loading.hidden = true;
        }
        if (error) {
            error.hidden = false;
        }
    };

    if (!publicKey || !preferenceId || typeof window.MercadoPago !== 'function') {
        showError();
        return;
    }

    try {
        const mercadoPago = new window.MercadoPago(publicKey, { locale: 'es-PE' });
        const bricksBuilder = mercadoPago.bricks();

        await bricksBuilder.create('wallet', 'walletBrick_container', {
            initialization: {
                preferenceId,
            },
            callbacks: {
                onReady: () => {
                    if (loading) {
                        loading.hidden = true;
                    }
                },
                onError: showError,
            },
        });
    } catch (exception) {
        showError();
    }
});
