<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalles_pedido';

    protected $fillable = [
        'pedido_id',
        'variante_id',
        'cantidad',
        'precio_unitario',
    ];

    // Relación: El detalle pertenece a una orden o pedido superior
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id', 'id');
    }

    // Relación: El detalle conoce la variante vendida
    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_id', 'id');
    }
}