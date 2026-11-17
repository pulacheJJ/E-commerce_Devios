<?php

namespace App\Services;

use App\Models\Slider;
use App\Support\MediaStorage;
use Illuminate\Http\UploadedFile;

class SliderService
{
    public function listarTodos()
    {
        return Slider::orderBy('orden', 'asc')->get();
    }

    public function obtenerPorId(int $id): Slider
    {
        return Slider::findOrFail($id);
    }

    public function guardarSlider(array $data, UploadedFile $file): Slider
    {
        $rutaImagen = $file->store('sliders', MediaStorage::diskName());

        $esActivo = array_key_exists('activo', $data) ? true : false;

        return Slider::create([
            'titulo' => $data['titulo'] ?? null,
            'subtitulo' => $data['subtitulo'] ?? null,
            'url_enlace' => $data['url_enlace'] ?? null,
            'imagen_url' => $rutaImagen,
            'orden' => $data['orden'],
            'activo' => $esActivo,
        ]);
    }

    public function actualizarSlider(int $id, array $data, ?UploadedFile $file = null): Slider
    {
        $slider = Slider::findOrFail($id);
        $rutaAnterior = $slider->getRawOriginal('imagen_url');

        if ($file) {
            $slider->imagen_url = $file->store('sliders', MediaStorage::diskName());
        }

        $esActivo = array_key_exists('activo', $data) ? true : false;

        $slider->update([
            'titulo' => $data['titulo'] ?? null,
            'subtitulo' => $data['subtitulo'] ?? null,
            'url_enlace' => $data['url_enlace'] ?? null,
            'orden' => $data['orden'],
            'activo' => $esActivo,
        ]);

        if ($file && $rutaAnterior !== $slider->getRawOriginal('imagen_url')) {
            $this->eliminarArchivo($rutaAnterior);
        }

        return $slider;
    }

    public function eliminarSlider(int $id): bool
    {
        $slider = Slider::findOrFail($id);
        $rutaImagen = $slider->getRawOriginal('imagen_url');

        $eliminado = $slider->delete();

        if ($eliminado) {
            $this->eliminarArchivo($rutaImagen);
        }

        return $eliminado;
    }

    private function eliminarArchivo(?string $ruta): void
    {
        if (! $ruta) {
            return;
        }

        if (str_contains($ruta, '/')) {
            try {
                MediaStorage::disk()->delete($ruta);
            } catch (\Throwable $exception) {
                report($exception);
            }

            return;
        }

        // Compatibilidad con banners creados antes de usar el disco publico.
        $rutaAnterior = public_path('img/sliders/'.basename($ruta));
        if (is_file($rutaAnterior)) {
            unlink($rutaAnterior);
        }
    }
}
