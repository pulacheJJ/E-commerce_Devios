<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Cliente extends Model
{
    protected $table = 'clientes';
    protected $fillable = [
        'nombres',
        'apellidos',
        'email',
        'telefono',
        'tipo_documento',
        'numero_documento',
        'direccion_predeterminada',
    ];

    // Relación: Un cliente puede tener muchos pedidos históricos
    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'cliente_id', 'id');
    }
}