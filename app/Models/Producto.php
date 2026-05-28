<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes; // Activamos el borrado lógico que pusimos en la migración

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'slug',
        'descripcion',
        'marca',
        'esta_activo',
    ];

    // Relación: El producto pertenece a una categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id', 'id');
    }

    // Relación: Un producto tiene muchas variantes de stock/precio
    public function variantes()
    {
        return $this->hasMany(VarianteProducto::class, 'producto_id', 'id');
    }

    // Relación: Un producto tiene muchas imágenes en su galería
    public function imagenes()
    {
        return $this->hasMany(ImagenProducto::class, 'producto_id', 'id');
    }

    //busca la imagen principal o devuelve la primera que encuentre
    public function getImagenPrincipalAttribute()
    {
        $imagen = $this->imagenes->where('es_principal', true)->first() ?? $this->imagenes->first();
        return $imagen ? $imagen->ruta_imagen : null;
    }

    //busca la primera variante en stock
    public function getVariantePorDefectoAttribute()
    {
        return $this->variantes->where('stock', '>', 0)->first() ?? $this->variantes->first();
    }
}
