<?php

namespace App\Services;

use App\Models\TarifaEnvio;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ShippingRateService
{
    public function create(array $validated): TarifaEnvio
    {
        return DB::transaction(fn () => TarifaEnvio::create($this->attributes($validated)), 3);
    }

    public function update(TarifaEnvio $rate, array $validated): TarifaEnvio
    {
        return DB::transaction(function () use ($rate, $validated): TarifaEnvio {
            $locked = TarifaEnvio::whereKey($rate->id)->lockForUpdate()->firstOrFail();
            $locked->update($this->attributes($validated));

            return $locked->fresh('provincia.departamento');
        }, 3);
    }

    public function toggle(TarifaEnvio $rate): TarifaEnvio
    {
        return DB::transaction(function () use ($rate): TarifaEnvio {
            $locked = TarifaEnvio::whereKey($rate->id)->lockForUpdate()->firstOrFail();
            if (! $locked->esta_activa && $locked->es_predeterminada) {
                $otherDefault = TarifaEnvio::whereKeyNot($locked->id)
                    ->where('es_predeterminada', true)->where('esta_activa', true)
                    ->lockForUpdate()->exists();
                if ($otherDefault) {
                    throw new DomainException('Ya existe otra tarifa predeterminada activa.');
                }
            }
            $locked->update(['esta_activa' => ! $locked->esta_activa]);

            return $locked;
        }, 3);
    }

    private function attributes(array $validated): array
    {
        $type = $validated['tipo'];

        return [
            'provincia_id' => $type === 'provincia' ? $validated['provincia_id'] : null,
            'costo' => Arr::get($validated, 'costo'),
            'es_predeterminada' => $type === 'predeterminada',
            'esta_activa' => (bool) $validated['esta_activa'],
        ];
    }
}
