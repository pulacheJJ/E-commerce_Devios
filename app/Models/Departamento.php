<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamentos';
    
    // Configuración clave para IDs de tipo string (Ubigeo)
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = ['id', 'nombre'];

    public function provincias()
    {
        return $this->hasMany(Provincia::class);
    }
}