<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    // Agregamos 'referencia' al fillable
    protected $fillable = [
        'cliente_id',
        'carrito_id',
        'carrito_token_hash',
        'session_token_hash',
        'numero_pedido',
        'direccion_envio',
        'referencia',
        'total',
        'estado',
    ];

    // Relación: El pedido pertenece a un cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'id');
    }

    public function carrito()
    {
        return $this->belongsTo(Carrito::class, 'carrito_id', 'id');
    }

    // Relación: Un pedido se compone de muchos productos en su detalle
    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'pedido_id', 'id');
    }

    // Relación: Un pedido puede registrar varios intentos de pago (Auditoría)
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'pedido_id', 'id');
    }
}
