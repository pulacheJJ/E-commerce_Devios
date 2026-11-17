<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Slider;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDeploymentDataTest extends TestCase
{
    use DatabaseTransactions;

    public function test_demo_catalog_is_repeatable_and_publishes_its_images(): void
    {
        Storage::fake('public');
        config(['filesystems.media_disk' => 'public']);

        $this->seed(DemoCatalogSeeder::class);
        $this->seed(DemoCatalogSeeder::class);

        $this->assertSame(3, Producto::where('slug', 'like', 'demo-%')->count());
        $this->assertSame(1, Slider::where('titulo', 'Catalogo demostrativo DEVIOZ')->count());
        $this->assertSame(
            1,
            Producto::where('slug', 'demo-laptop-devioz-15')
                ->firstOrFail()
                ->variantes()
                ->where('sku', 'DEMO-LAPTOP-NEGRO')
                ->count()
        );

        Storage::disk('public')->assertExists([
            'demo/productos/laptop.svg',
            'demo/productos/audifonos.svg',
            'demo/productos/mouse.svg',
            'demo/promociones/oferta.svg',
            'demo/sliders/catalogo.svg',
        ]);
    }

    public function test_health_endpoint_does_not_require_authentication(): void
    {
        $this->get('/health')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }
}
