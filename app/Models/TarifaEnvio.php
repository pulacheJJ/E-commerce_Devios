<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifaEnvio extends Model
{
    protected $table = 'tarifas_envio';

    protected $fillable = ['provincia_id', 'costo', 'es_predeterminada', 'esta_activa'];

    protected $casts = [
        'costo' => 'decimal:2',
        'es_predeterminada' => 'boolean',
        'esta_activa' => 'boolean',
    ];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }
}
