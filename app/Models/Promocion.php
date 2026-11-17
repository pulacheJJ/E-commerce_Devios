<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;

class Promocion extends Model
{
    protected $table = 'promociones';

    public $timestamps = false;

    // 1. Agregamos los nuevos campos al fillable
    protected $fillable = [
        'titulo',
        'subtitulo',
        'ruta_banner',
        'url_destino',
        'fecha_inicio',
        'fecha_fin',
        'esta_activa',
        'tipo_descuento',
        'valor_descuento',
        'destacada_en_carrusel',
    ];

    // 2. Casteamos los datos para que Laravel trabaje las fechas con Carbon y los booleanos correctamente
    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'esta_activa' => 'boolean',
        'destacada_en_carrusel' => 'boolean',
        'valor_descuento' => 'decimal:2',
    ];

    // 3. Relación Inversa: Una promoción se aplica a muchas categorías
    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_promocion', 'promocion_id', 'categoria_id');
    }

    // 4. Relación Inversa: Una promoción se aplica a muchos productos específicos
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_promocion', 'promocion_id', 'producto_id');
    }

    // 5. Helper (Opcional pero elegante): Verifica si la promoción está vigente HOY
    public function getEsVigenteAttribute()
    {
        return $this->esta_activa && now()->between($this->fecha_inicio, $this->fecha_fin);
    }

    public function getImagenUrlAttribute()
    {
        return MediaStorage::url($this->ruta_banner);
    }

    public function getLinkAttribute()
    {
        return $this->url_destino ?: route('tienda.promocion', $this->id);
    }
}
