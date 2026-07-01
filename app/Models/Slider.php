<?php

namespace App\Models;

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
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function getImagenUrlAttribute()
    {
        return asset('img/sliders/' . $this->attributes['imagen_url']);
    }

    public function getUrlDestinoAttribute()
    {
        return $this->url_enlace;
    }
}