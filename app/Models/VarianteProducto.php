<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VarianteProducto extends Model
{
    protected $table = 'variantes_producto';

    protected $fillable = [
        'producto_id',
        'sku',
        'precio',
        'precio_oferta',
        'stock',
        'color',
        'especificacion',
    ];

    // Relación: La variante pertenece a un producto base
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'id');
    }

    //decide si cobrar el precio real o si hay un precio oferta activo
    public function getPrecioRealAttribute()
    {
        return $this->precio_oferta > 0 ? $this->precio_oferta : $this->precio;
    }
}