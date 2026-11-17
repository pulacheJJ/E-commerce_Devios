<?php

namespace App\Console\Commands;

use App\Models\Slider;
use App\Support\MediaStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CopiarArchivosMultimedia extends Command
{
    protected $signature = 'media:copy
        {--from=public : Disco de origen}
        {--to= : Disco de destino; por defecto usa MEDIA_DISK}
        {--force : Reemplaza objetos que ya existen}';

    protected $description = 'Copia fotografias y banners entre discos sin borrar el origen';

    public function handle(): int
    {
        $origenNombre = (string) $this->option('from');
        $destinoNombre = (string) ($this->option('to') ?: MediaStorage::diskName());

        if ($origenNombre === $destinoNombre) {
            $this->error('El disco de origen y el de destino deben ser diferentes.');

            return self::FAILURE;
        }

        $origen = Storage::disk($origenNombre);
        $destino = Storage::disk($destinoNombre);
        $copiados = 0;
        $omitidos = 0;

        foreach ($origen->allFiles() as $ruta) {
            if (str_starts_with(basename($ruta), '.')) {
                continue;
            }

            if (! $this->option('force') && $destino->exists($ruta)) {
                $omitidos++;

                continue;
            }

            $flujo = $origen->readStream($ruta);
            if (! is_resource($flujo)) {
                throw new RuntimeException("No fue posible leer {$ruta} desde {$origenNombre}.");
            }

            try {
                if (! $destino->writeStream($ruta, $flujo)) {
                    throw new RuntimeException("No fue posible copiar {$ruta} hacia {$destinoNombre}.");
                }
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    "Fallo al copiar {$ruta}: {$exception->getMessage()}",
                    previous: $exception
                );
            } finally {
                fclose($flujo);
            }

            $copiados++;
        }

        if ($origenNombre === 'public') {
            [$slidersCopiados, $slidersOmitidos] = $this->copiarSlidersAnteriores($destinoNombre);
            $copiados += $slidersCopiados;
            $omitidos += $slidersOmitidos;
        }

        $this->info("Copia finalizada: {$copiados} archivo(s) copiado(s), {$omitidos} omitido(s).");
        $this->line('Los archivos del disco de origen se conservaron.');

        return self::SUCCESS;
    }

    /**
     * Migra banners creados cuando los sliders se almacenaban en public/img.
     * El archivo original se conserva y el registro pasa a utilizar la clave nueva.
     *
     * @return array{int, int}
     */
    private function copiarSlidersAnteriores(string $destinoNombre): array
    {
        $destino = Storage::disk($destinoNombre);
        $copiados = 0;
        $omitidos = 0;

        Slider::query()
            ->where('imagen_url', 'not like', '%/%')
            ->each(function (Slider $slider) use ($destino, &$copiados, &$omitidos): void {
                $nombreAnterior = basename((string) $slider->getRawOriginal('imagen_url'));
                $origen = public_path('img/sliders/'.$nombreAnterior);

                if (! is_file($origen)) {
                    $this->warn("No se encontro el slider anterior {$nombreAnterior}; el registro no se modifico.");
                    $omitidos++;

                    return;
                }

                $rutaNueva = 'sliders/'.$nombreAnterior;
                if ($this->option('force') || ! $destino->exists($rutaNueva)) {
                    $flujo = fopen($origen, 'rb');
                    if (! is_resource($flujo)) {
                        throw new RuntimeException("No fue posible leer el slider anterior {$nombreAnterior}.");
                    }

                    try {
                        if (! $destino->writeStream($rutaNueva, $flujo)) {
                            throw new RuntimeException("No fue posible copiar el slider {$nombreAnterior}.");
                        }
                    } finally {
                        fclose($flujo);
                    }
                }

                $slider->update(['imagen_url' => $rutaNueva]);
                $copiados++;
            });

        return [$copiados, $omitidos];
    }
}
