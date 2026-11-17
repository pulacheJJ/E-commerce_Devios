document.addEventListener('DOMContentLoaded', () => {
    const statusPanel = document.getElementById('paymentStatus');

    if (!statusPanel || statusPanel.dataset.polling === 'true') {
        return;
    }

    statusPanel.dataset.polling = 'true';
    const statusUrl = statusPanel.dataset.statusUrl;
    const redirectUrl = statusPanel.dataset.redirectUrl;
    const title = document.getElementById('paymentStatusTitle');
    const message = document.getElementById('paymentStatusMessage');
    const activity = document.getElementById('paymentStatusActivity');
    const totalLabel = document.getElementById('paymentTotalLabel');
    const successModal = document.getElementById('paymentSuccessModal');
    const countdown = document.getElementById('paymentRedirectCountdown');
    let attempts = 0;
    let completionHandled = false;

    const showPaymentSuccess = () => {
        if (completionHandled) {
            return;
        }

        completionHandled = true;
        title.textContent = 'Pago confirmado';
        message.textContent = 'Mercado Pago confirmó la operación de forma segura.';
        activity.hidden = true;

        if (totalLabel) {
            totalLabel.textContent = 'Total pagado:';
        }

        if (!successModal || !redirectUrl) {
            window.location.assign(redirectUrl || '/');
            return;
        }

        successModal.hidden = false;
        window.requestAnimationFrame(() => successModal.classList.add('is-visible'));

        let remainingSeconds = 3;
        if (countdown) {
            countdown.textContent = String(remainingSeconds);
        }

        const countdownTimer = window.setInterval(() => {
            remainingSeconds -= 1;
            if (countdown) {
                countdown.textContent = String(Math.max(remainingSeconds, 0));
            }

            if (remainingSeconds <= 0) {
                window.clearInterval(countdownTimer);
            }
        }, 1000);

        window.setTimeout(() => window.location.assign(redirectUrl), 3000);
    };

    if (statusPanel.dataset.confirmed === 'true') {
        showPaymentSuccess();
        return;
    }

    const update = async () => {
        attempts += 1;

        try {
            const response = await fetch(statusUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Payment status request failed.');
            }

            const result = await response.json();
            if (result.confirmado) {
                showPaymentSuccess();
                return;
            }

            if (result.finalizado) {
                title.textContent = 'No fue posible confirmar el pago';
                message.textContent = 'El pedido finalizó sin una confirmación de pago. Puedes volver a la tienda.';
                activity.hidden = true;
                return;
            }

            activity.textContent = 'Aún estamos esperando la confirmación de Mercado Pago...';
        } catch (error) {
            activity.textContent = 'No pudimos consultar el estado. Volveremos a intentarlo...';
        }

        if (attempts < 30) {
            window.setTimeout(update, 2000);
        } else {
            activity.textContent = 'La confirmación está tardando más de lo esperado. Puedes volver a cargar esta página.';
        }
    };

    update();
});
