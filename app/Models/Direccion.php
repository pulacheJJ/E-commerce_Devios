<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Direccion extends Model
{
    protected $table = 'direcciones';

    protected $fillable = [
        'cliente_id', 
        'distrito_id', 
        'direccion_exacta', 
        'referencia', 
        'is_predeterminada'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }
}