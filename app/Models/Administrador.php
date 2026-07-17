<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Modelo legado: la autenticación oficial usa User con Spatie Permission. */
class Administrador extends Model
{
    // Le decimos a Laravel la tabla exacta en español
    protected $table = 'administradores';

    protected $fillable = [
        'nombre',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
