<?php

namespace App\Services;

use App\Models\Promocion;
use App\Support\MediaStorage;
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
        $rutaAnterior = $promocion->ruta_banner;

        $promocionActualizada = DB::transaction(function () use ($promocion, $data, $bannerFile) {
            $data = $this->prepararDatosBooleanos($data);

            if ($bannerFile) {
                $data['ruta_banner'] = $this->procesarBanner($bannerFile);
            }

            $promocion->update($data);
            $this->sincronizarRelaciones($promocion, $data);
            $this->recalcularPreciosAfectados($promocion);

            return $promocion;
        });

        if ($bannerFile && $rutaAnterior !== $promocionActualizada->ruta_banner) {
            $this->eliminarBannerAnterior($rutaAnterior);
        }

        return $promocionActualizada;
    }

    public function alternarEstado(Promocion $promocion): bool
    {
        return DB::transaction(function () use ($promocion) {
            $promocion->esta_activa = ! $promocion->esta_activa;
            $promocion->save();

            $this->recalcularPreciosAfectados($promocion);

            return $promocion->esta_activa;
        });
    }

    public function eliminarPromocion(Promocion $promocion): void
    {
        $rutaBanner = $promocion->ruta_banner;

        DB::transaction(function () use ($promocion) {
            // Apagamos primero para limpiar precios afectados
            $promocion->esta_activa = false;
            $promocion->save();
            $this->recalcularPreciosAfectados($promocion);

            $promocion->delete();
        });

        $this->eliminarBannerAnterior($rutaBanner);
    }

    private function procesarBanner($file): string
    {
        return $file->store('promociones', MediaStorage::diskName());
    }

    private function eliminarBannerAnterior(?string $ruta): void
    {
        if (! $ruta) {
            return;
        }

        try {
            MediaStorage::disk()->delete($ruta);
        } catch (\Throwable $exception) {
            report($exception);
        }
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
