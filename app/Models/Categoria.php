<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'parent_id',
        'nombre',
        'slug',
        'esta_activa',
    ];

    public function padre()
    {
        return $this->belongsTo(Categoria::class, 'parent_id', 'id');
    }

    public function hijos()
    {
        return $this->hasMany(Categoria::class, 'parent_id', 'id');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id', 'id');
    }

    // Relación: Una categoría puede estar en muchas promociones
    public function promociones()
    {
        return $this->belongsToMany(Promocion::class, 'categoria_promocion', 'categoria_id', 'promocion_id');
    }
}