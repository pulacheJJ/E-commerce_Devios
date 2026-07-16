<?php

return [
    'expiration_minutes' => max(1, (int) env('PEDIDO_EXPIRATION_MINUTES', 30)),
    'expirable_states' => ['pendiente'],
    'cart_expiration_days' => max(1, (int) env('CARRITO_EXPIRATION_DAYS', 30)),
];
