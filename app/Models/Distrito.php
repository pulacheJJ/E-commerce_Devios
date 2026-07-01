<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distrito extends Model
{
    protected $table = 'distritos';
    
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = ['id', 'nombre', 'provincia_id'];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function direcciones()
    {
        return $this->hasMany(Direccion::class);
    }
}