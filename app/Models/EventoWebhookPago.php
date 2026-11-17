<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoWebhookPago extends Model
{
    protected $table = 'eventos_webhook_pago';

    protected $fillable = [
        'pago_id',
        'proveedor',
        'evento_externo_id',
        'payload_hash',
        'estado_normalizado',
        'procesado_at',
    ];

    protected $casts = ['procesado_at' => 'datetime'];

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }
}
