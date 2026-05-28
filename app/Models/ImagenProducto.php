<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagenProducto extends Model
{
    protected $table = 'imagenes_producto';

    // Desactivamos los timestamps porque en la migración solo pusimos created_at
    public $timestamps = false; 

    protected $fillable = [
        'producto_id',
        'ruta_imagen',
        'es_principal',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'id');
    }
}
