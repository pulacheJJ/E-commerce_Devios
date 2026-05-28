<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'pedido_id',
        'metodo_pago',
        'id_transaccion',
        'monto',
        'estado_pago',
        'respuesta_pasarela',
    ];

    // Relación: El pago está amarrado a un pedido
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id', 'id');
    }
}
