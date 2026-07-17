<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Direccion;
use Illuminate\Support\Facades\DB;

class DireccionService
{
    public function guardarComoPredeterminada(Cliente $cliente, array $datos): Direccion
    {
        return DB::transaction(function () use ($cliente, $datos): Direccion {
            Direccion::where('cliente_id', $cliente->id)->lockForUpdate()->get();
            Direccion::where('cliente_id', $cliente->id)->where('is_predeterminada', true)
                ->update(['is_predeterminada' => false]);

            $direccion = Direccion::firstOrNew([
                'cliente_id' => $cliente->id,
                'distrito_id' => $datos['distrito'],
                'direccion_exacta' => $datos['direccion_envio'],
            ]);
            $direccion->referencia = $datos['referencia'] ?? null;
            $direccion->is_predeterminada = true;
            $direccion->save();

            return $direccion;
        }, 3);
    }
}
