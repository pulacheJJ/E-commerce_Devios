<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalles_pedido';

    protected $fillable = [
        'pedido_id',
        'variante_id',
        'producto_nombre',
        'sku',
        'variante_descripcion',
        'precio_base_unitario',
        'descuento_unitario',
        'cantidad',
        'precio_unitario',
        'subtotal_linea',
    ];

    protected $casts = [
        'precio_base_unitario' => 'decimal:2',
        'descuento_unitario' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal_linea' => 'decimal:2',
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
