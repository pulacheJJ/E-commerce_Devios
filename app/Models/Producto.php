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

    // busca la imagen principal o devuelve la primera que encuentre
    public function getImagenPrincipalAttribute()
    {
        $imagen = $this->imagenes->where('es_principal', true)->first() ?? $this->imagenes->first();

        return $imagen ? $imagen->ruta_imagen : null;
    }

    public function getImagenPrincipalUrlAttribute(): ?string
    {
        $imagen = $this->imagenes->where('es_principal', true)->first() ?? $this->imagenes->first();

        return $imagen?->url;
    }

    // busca la primera variante en stock
    public function getVariantePorDefectoAttribute()
    {
        return $this->variantes->where('stock', '>', 0)->first() ?? $this->variantes->first();
    }

    // Relación: Un producto puede pertenecer a muchas promociones específicas
    public function promociones()
    {
        return $this->belongsToMany(Promocion::class, 'producto_promocion', 'producto_id', 'promocion_id');
    }

    // 1. Atributo: Obtiene la promoción activa aprovechando tu helper 'es_vigente'
    public function getPromocionActivaAttribute()
    {
        // Usamos la relación y el helper que definiste en Promocion.php
        $promoDirecta = $this->promociones->filter(function ($promo) {
            return $promo->es_vigente; // Aquí llama a tu nuevo helper
        })->first();

        if ($promoDirecta) {
            return $promoDirecta;
        }

        if ($this->categoria && $this->categoria->promociones) {
            return $this->categoria->promociones->filter(function ($promo) {
                return $promo->es_vigente;
            })->first();
        }

        return null;
    }

    // 2. Atributo: Trae el precio original puro (el que siempre se va a tachar visualmente)
    public function getPrecioOriginalAttribute()
    {
        $variante = $this->variante_por_defecto;

        return $variante ? $variante->precio : 0;
    }

    // 3. Atributo: El cerebro matemático. Calcula el precio final absoluto.
    public function getPrecioFinalAttribute()
    {
        $variante = $this->variante_por_defecto;

        if (! $variante) {
            return 0;
        }

        // Tomamos como base tu 'precio_real' para respetar si tenía un 'precio_oferta' manual
        $precioBase = $variante->precio_real;
        $promo = $this->promocion_activa;

        // Si no hay promoción de campaña, devuelve el precio real de la variante
        if (! $promo || $precioBase <= 0) {
            return $precioBase;
        }

        // Si hay promoción, aplicamos la matemática
        if ($promo->tipo_descuento === 'porcentaje') {
            $descuento = $precioBase * ($promo->valor_descuento / 100);

            return max(0, $precioBase - $descuento);
        }

        // Si es monto fijo
        return max(0, $precioBase - $promo->valor_descuento);
    }

    /**
     * Prepara la data de las variantes con sus descuentos aplicados para consumirla en JS.
     */
    public function getVariantesParaJsAttribute()
    {
        return $this->variantes->map(function ($variante) {
            $precioBase = $variante->precio_real;
            $precioFinal = $precioBase;
            $hayDescuento = false;
            $descuentoInfo = null;

            if ($this->promocion_activa) {
                $hayDescuento = true;
                if ($this->promocion_activa->tipo_descuento === 'porcentaje') {
                    $descuento = $precioBase * ($this->promocion_activa->valor_descuento / 100);
                    $precioFinal = max(0, $precioBase - $descuento);
                    $descuentoInfo = '-'.number_format($this->promocion_activa->valor_descuento, 0).'% OFF';
                } else {
                    $precioFinal = max(0, $precioBase - $this->promocion_activa->valor_descuento);
                    $descuentoInfo = '- S/ '.number_format($this->promocion_activa->valor_descuento, 2);
                }
            }

            return [
                'id' => $variante->id,
                'color' => $variante->color,
                'stock' => $variante->stock,
                'precio_original' => $precioBase,
                'precio_final' => $precioFinal,
                'hay_descuento' => $hayDescuento,
                'descuento_info' => $descuentoInfo,
            ];
        });
    }
}
