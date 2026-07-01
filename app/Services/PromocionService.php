<?php

namespace App\Services;

use App\Models\Promocion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PromocionService
{
    public function crearPromocion(array $data, $bannerFile = null): Promocion
    {
        return DB::transaction(function () use ($data, $bannerFile) {
            $data = $this->prepararDatosBooleanos($data);
            
            if ($bannerFile) {
                $data['ruta_banner'] = $this->procesarBanner($bannerFile);
            }

            $promocion = Promocion::create($data);
            $this->sincronizarRelaciones($promocion, $data);
            $this->recalcularPreciosAfectados($promocion);

            return $promocion;
        });
    }

    public function actualizarPromocion(Promocion $promocion, array $data, $bannerFile = null): Promocion
    {
        return DB::transaction(function () use ($promocion, $data, $bannerFile) {
            $data = $this->prepararDatosBooleanos($data);

            if ($bannerFile) {
                $data['ruta_banner'] = $this->procesarBanner($bannerFile, $promocion->ruta_banner);
            }

            $promocion->update($data);
            $this->sincronizarRelaciones($promocion, $data);
            $this->recalcularPreciosAfectados($promocion);

            return $promocion;
        });
    }

    public function alternarEstado(Promocion $promocion): bool
    {
        return DB::transaction(function () use ($promocion) {
            $promocion->esta_activa = !$promocion->esta_activa;
            $promocion->save();
            
            $this->recalcularPreciosAfectados($promocion);

            return $promocion->esta_activa;
        });
    }

    public function eliminarPromocion(Promocion $promocion): void
    {
        DB::transaction(function () use ($promocion) {
            // Apagamos primero para limpiar precios afectados
            $promocion->esta_activa = false;
            $promocion->save();
            $this->recalcularPreciosAfectados($promocion);

            if ($promocion->ruta_banner) {
                Storage::disk('public')->delete($promocion->ruta_banner);
            }

            $promocion->delete();
        });
    }

    private function procesarBanner($file, ?string $rutaAnterior = null): string
    {
        if ($rutaAnterior) {
            Storage::disk('public')->delete($rutaAnterior);
        }
        return $file->store('promociones', 'public');
    }

    private function prepararDatosBooleanos(array $data): array
    {
        $data['esta_activa'] = isset($data['esta_activa']);
        $data['destacada_en_carrusel'] = isset($data['destacada_en_carrusel']);
        return $data;
    }

    private function sincronizarRelaciones(Promocion $promocion, array $data): void
    {
        $promocion->categorias()->sync($data['categorias'] ?? []);
        $promocion->productos()->sync($data['productos'] ?? []);
    }

    private function recalcularPreciosAfectados(Promocion $promocion): void
    {
        // La consulta SQL directa para actualizar precios se implementará aquí.
    }
}