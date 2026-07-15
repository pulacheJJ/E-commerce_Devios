<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    protected $table = 'carritos';

    protected $fillable = [
        'token_sesion',
    ];

    // Relación: Un carrito puede tener muchos elementos (productos agregados)
    public function elementos()
    {
        return $this->hasMany(ElementoCarrito::class, 'carrito_id', 'id');
    }

    public function pedido()
    {
        return $this->hasOne(Pedido::class, 'carrito_id', 'id');
    }

    //Accesors para calcular los totales
    public function getCantidadTotalAttribute()
    {
        return $this->elementos->sum('cantidad');
    }

    public function getTotalAttribute()
    {
        return $this->elementos->sum(function ($item) {
            return $item->subtotal;
        });
    }
}
