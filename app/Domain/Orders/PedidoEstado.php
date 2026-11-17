<?php

namespace App\Domain\Orders;

enum PedidoEstado: string
{
    case Creado = 'creado';
    case Reservado = 'reservado';
    case PendientePago = 'pendiente_pago';
    case Pagado = 'pagado';
    case PagoFallido = 'pago_fallido';
    case Expirado = 'expirado';
    case Cancelado = 'cancelado';
    case Enviado = 'enviado';
}
