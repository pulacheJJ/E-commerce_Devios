<?php

namespace App\Services;

use App\Models\Slider;
use Illuminate\Support\Str;

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

    public function guardarSlider(array $data, $file): Slider
    {
        $nombreLimpio = time() . '_' . Str::slug($data['titulo'] ?? 'banner') . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('img/sliders'), $nombreLimpio);

        $esActivo = array_key_exists('activo', $data) ? true : false;
        
        return Slider::create([
            'titulo'     => $data['titulo'] ?? null,
            'subtitulo'  => $data['subtitulo'] ?? null,
            'url_enlace' => $data['url_enlace'] ?? null,
            'imagen_url' => $nombreLimpio,
            'orden'      => $data['orden'],
            'activo'     => $esActivo,
        ]);
    }

    public function actualizarSlider(int $id, array $data, $file = null): Slider
    {
        $slider = Slider::findOrFail($id);

        if ($file) {
            $rutaVieja = public_path('img/sliders/' . $slider->imagen_url);
            if (file_exists($rutaVieja)) {
                @unlink($rutaVieja);
            }

            $nombreLimpio = time() . '_' . Str::slug($data['titulo'] ?? 'banner') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('img/sliders'), $nombreLimpio);
            $slider->imagen_url = $nombreLimpio;
        }

        $esActivo = array_key_exists('activo', $data) ? true : false;

        $slider->update([
            'titulo'     => $data['titulo'] ?? null,
            'subtitulo'  => $data['subtitulo'] ?? null,
            'url_enlace' => $data['url_enlace'] ?? null,
            'orden'      => $data['orden'],
            'activo'     => $esActivo,
        ]);

        return $slider;
    }

    public function eliminarSlider(int $id): bool
    {
        $slider = Slider::findOrFail($id);
        
        $rutaFisica = public_path('img/sliders/' . $slider->imagen_url);
        if (file_exists($rutaFisica)) {
            @unlink($rutaFisica);
        }

        return $slider->delete();
    }
}