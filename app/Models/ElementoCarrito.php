<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementoCarrito extends Model
{
    protected $table = 'elementos_carrito';

    protected $fillable = [
        'carrito_id',
        'variante_id',
        'cantidad',
    ];

    // Relación: El elemento pertenece a un carrito específico
    public function carrito()
    {
        return $this->belongsTo(Carrito::class, 'carrito_id', 'id');
    }

    // Relación: El elemento hace referencia a una variante de producto
    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_id', 'id');
    }

    //para calcular los precios por cada fila
    public function getSubtotalAttribute()
    {
        return $this->precio_unitario_real * $this->cantidad;
    }

    public function getPrecioUnitarioRealAttribute()
    {
        return $this->variante->precio_real;
    }
    
    public function getLimiteMaximoAttribute()
    {
        return min($this->variante->stock, 10);
    }
}