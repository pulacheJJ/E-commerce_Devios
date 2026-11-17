<?php

namespace Tests\Unit;

use App\Models\ImagenProducto;
use App\Models\Promocion;
use App\Models\Slider;
use App\Support\MediaStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStorageConfigurationTest extends TestCase
{
    public function test_media_urls_use_the_configured_disk(): void
    {
        Storage::fake('public');
        config(['filesystems.media_disk' => 'public']);

        $imagen = new ImagenProducto(['ruta_imagen' => 'products/producto.webp']);
        $promocion = new Promocion(['ruta_banner' => 'promociones/banner.webp']);
        $slider = new Slider(['imagen_url' => 'sliders/portada.webp']);

        $this->assertStringEndsWith('/storage/products/producto.webp', $imagen->url);
        $this->assertStringEndsWith('/storage/promociones/banner.webp', $promocion->imagen_url);
        $this->assertStringEndsWith('/storage/sliders/portada.webp', $slider->imagen_url);
    }

    public function test_views_do_not_build_local_storage_urls(): void
    {
        $views = [
            'resources/views/tienda/show.blade.php',
            'resources/views/tienda/checkout.blade.php',
            'resources/views/tienda/checkout-retry.blade.php',
            'resources/views/tienda/carrito.blade.php',
            'resources/views/components/tarjeta-producto.blade.php',
            'resources/views/admin/productos/show.blade.php',
            'resources/views/admin/productos/index.blade.php',
            'resources/views/admin/productos/edit.blade.php',
            'resources/views/admin/promociones/show.blade.php',
            'resources/views/admin/promociones/index.blade.php',
            'resources/views/admin/promociones/edit.blade.php',
        ];

        foreach ($views as $view) {
            $this->assertStringNotContainsString("asset('storage/", file_get_contents(base_path($view)), $view);
        }
    }

    public function test_s3_adapter_is_a_runtime_dependency(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('league/flysystem-aws-s3-v3', $composer['require']);
        $this->assertSame('^3.0', $composer['require']['league/flysystem-aws-s3-v3']);
    }

    public function test_cloudinary_adapter_and_disk_are_configured(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
        $disk = config('filesystems.disks.cloudinary');

        $this->assertArrayHasKey('cloudinary-labs/cloudinary-laravel', $composer['require']);
        $this->assertSame('^2.3', $composer['require']['cloudinary-labs/cloudinary-laravel']);
        $this->assertSame('cloudinary', $disk['driver']);
        $this->assertTrue($disk['throw']);
        $this->assertArrayHasKey('cloud_url', config('cloudinary'));
    }

    public function test_cloudinary_disk_builds_https_delivery_urls(): void
    {
        config([
            'filesystems.media_disk' => 'cloudinary',
            'filesystems.disks.cloudinary.url' => 'https://res.cloudinary.com/nube-demo/image/upload',
        ]);

        $this->assertSame(
            'https://res.cloudinary.com/nube-demo/image/upload/products/foto.webp',
            MediaStorage::url('products/foto.webp')
        );
    }
}
