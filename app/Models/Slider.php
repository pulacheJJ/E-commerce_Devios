<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $table = 'sliders';

    protected $fillable = [
        'titulo',
        'subtitulo',
        'imagen_url',
        'url_enlace',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function getImagenUrlAttribute()
    {
        $ruta = $this->attributes['imagen_url'];

        if (str_contains($ruta, '/')) {
            return MediaStorage::url($ruta);
        }

        // Compatibilidad con los registros existentes del directorio publico.
        return asset('img/sliders/'.$ruta);
    }

    public function getUrlDestinoAttribute()
    {
        return $this->url_enlace;
    }
}
