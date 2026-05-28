<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promocion extends Model
{
    protected $table = 'promociones';
    
    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'subtitulo',
        'ruta_banner',
        'url_destino',
        'fecha_inicio',
        'fecha_fin',
        'esta_activa',
    ];
}