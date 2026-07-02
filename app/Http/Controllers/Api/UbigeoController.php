<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Provincia;
use App\Models\Distrito;

class UbigeoController extends Controller
{
    public function departamentos()
    {
        return response()->json(Departamento::orderBy('nombre')->get());
    }

    public function provincias($departamento_id)
    {
        return response()->json(Provincia::where('departamento_id', $departamento_id)->orderBy('nombre')->get());
    }

    public function distritos($provincia_id)
    {
        return response()->json(Distrito::where('provincia_id', $provincia_id)->orderBy('nombre')->get());
    }
}