<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Services\ProductoService;
use App\Services\PromocionService;
use App\Services\SliderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MediaUploadFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_products_promotions_and_sliders_use_the_configured_media_disk(): void
    {
        Storage::fake('media_test');
        config(['filesystems.media_disk' => 'media_test']);

        $categoria = Categoria::create([
            'nombre' => 'Categoria multimedia',
            'slug' => 'categoria-media-'.Str::lower(Str::random(8)),
            'esta_activa' => true,
        ]);

        $producto = app(ProductoService::class)->crear([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto multimedia '.Str::random(8),
            'descripcion' => 'Producto para verificar el disco multimedia.',
            'marca' => 'DEVIOZ',
            'esta_activo' => '1',
            'variantes' => [[
                'sku' => 'MEDIA-'.Str::upper(Str::random(12)),
                'precio' => 99.90,
                'precio_oferta' => null,
                'stock' => 5,
                'color' => 'Negro',
                'especificacion' => 'Prueba',
            ]],
            'imagenes' => [UploadedFile::fake()->create('producto.webp', 10, 'image/webp')],
        ]);

        $rutaProducto = $producto->imagenes()->firstOrFail()->ruta_imagen;
        Storage::disk('media_test')->assertExists($rutaProducto);
        $this->assertStringStartsWith('products/', $rutaProducto);

        $promocion = app(PromocionService::class)->crearPromocion([
            'titulo' => 'Promocion multimedia '.Str::random(8),
            'subtitulo' => 'Banner externo',
            'url_destino' => null,
            'fecha_inicio' => now()->subDay(),
            'fecha_fin' => now()->addDay(),
            'esta_activa' => '1',
            'tipo_descuento' => 'porcentaje',
            'valor_descuento' => 10,
            'destacada_en_carrusel' => '1',
            'categorias' => [$categoria->id],
            'productos' => [$producto->id],
        ], UploadedFile::fake()->create('promocion.webp', 10, 'image/webp'));

        Storage::disk('media_test')->assertExists($promocion->ruta_banner);
        $this->assertStringStartsWith('promociones/', $promocion->ruta_banner);

        $slider = app(SliderService::class)->guardarSlider([
            'titulo' => 'Slider multimedia '.Str::random(8),
            'subtitulo' => 'Portada externa',
            'url_enlace' => '/',
            'orden' => 1,
            'activo' => '1',
        ], UploadedFile::fake()->create('slider.webp', 10, 'image/webp'));

        $rutaSlider = $slider->getRawOriginal('imagen_url');
        Storage::disk('media_test')->assertExists($rutaSlider);
        $this->assertStringStartsWith('sliders/', $rutaSlider);
    }
}
