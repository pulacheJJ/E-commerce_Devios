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
        $variante = $this->variante;
        if (!$variante)
            return 0;

        // Obtenemos el producto padre a través de la variante
        $producto = $variante->producto;

        // Obtenemos el precio base de la variante
        $precioBase = $variante->precio_real;

        // Si el producto tiene una promoción activa, calculamos el precio final
        if ($producto && $producto->promocion_activa) {
            $promo = $producto->promocion_activa;

            if ($promo->tipo_descuento === 'porcentaje') {
                $descuento = $precioBase * ($promo->valor_descuento / 100);
                return max(0, $precioBase - $descuento);
            }

            // Si es monto fijo
            return max(0, $precioBase - $promo->valor_descuento);
        }

        return $precioBase;
    }

    public function getLimiteMaximoAttribute()
    {
        return min($this->variante->stock, 10);
    }
}