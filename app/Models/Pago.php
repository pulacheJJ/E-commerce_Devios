<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'pedido_id',
        'proveedor',
        'metodo_pago',
        'id_transaccion',
        'clave_idempotencia',
        'monto',
        'moneda',
        'estado_pago',
        'iniciado_at',
        'confirmado_at',
        'evento_confirmacion_id',
        'respuesta_pasarela',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'iniciado_at' => 'datetime',
        'confirmado_at' => 'datetime',
        'respuesta_pasarela' => 'array',
    ];

    // Relación: El pago está amarrado a un pedido
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id', 'id');
    }

    public function eventosWebhook()
    {
        return $this->hasMany(EventoWebhookPago::class, 'pago_id');
    }
}
