<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Slider;
use App\Models\VarianteProducto;
use App\Support\MediaStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->publicarRecursos();

        DB::transaction(function (): void {
            $laptops = Categoria::updateOrCreate(
                ['slug' => 'demo-laptops'],
                ['nombre' => 'Laptops Demo', 'parent_id' => null, 'esta_activa' => true]
            );

            $accesorios = Categoria::updateOrCreate(
                ['slug' => 'demo-accesorios'],
                ['nombre' => 'Accesorios Demo', 'parent_id' => null, 'esta_activa' => true]
            );

            $productos = [
                $this->guardarProducto($laptops, [
                    'nombre' => 'Laptop DEVIOZ Demo 15',
                    'slug' => 'demo-laptop-devioz-15',
                    'descripcion' => 'Equipo demostrativo para validar el catalogo, carrito y checkout.',
                    'marca' => 'DEVIOZ',
                    'imagen' => 'demo/productos/laptop.svg',
                    'variantes' => [
                        ['sku' => 'DEMO-LAPTOP-NEGRO', 'precio' => 2899.90, 'precio_oferta' => 2699.90, 'stock' => 20, 'color' => 'Negro', 'especificacion' => '16 GB RAM / 512 GB SSD'],
                        ['sku' => 'DEMO-LAPTOP-GRIS', 'precio' => 2999.90, 'precio_oferta' => null, 'stock' => 15, 'color' => 'Gris', 'especificacion' => '16 GB RAM / 1 TB SSD'],
                    ],
                ]),
                $this->guardarProducto($accesorios, [
                    'nombre' => 'Audifonos DEVIOZ Demo',
                    'slug' => 'demo-audifonos-devioz',
                    'descripcion' => 'Audifonos inalambricos de demostracion.',
                    'marca' => 'DEVIOZ',
                    'imagen' => 'demo/productos/audifonos.svg',
                    'variantes' => [
                        ['sku' => 'DEMO-AUDIO-NEGRO', 'precio' => 249.90, 'precio_oferta' => 219.90, 'stock' => 30, 'color' => 'Negro', 'especificacion' => 'Bluetooth / Cancelacion de ruido'],
                    ],
                ]),
                $this->guardarProducto($accesorios, [
                    'nombre' => 'Mouse DEVIOZ Demo',
                    'slug' => 'demo-mouse-devioz',
                    'descripcion' => 'Mouse ergonomico de demostracion.',
                    'marca' => 'DEVIOZ',
                    'imagen' => 'demo/productos/mouse.svg',
                    'variantes' => [
                        ['sku' => 'DEMO-MOUSE-GRAFITO', 'precio' => 129.90, 'precio_oferta' => null, 'stock' => 40, 'color' => 'Grafito', 'especificacion' => 'Inalambrico / 6 botones'],
                    ],
                ]),
            ];

            $promocion = Promocion::firstOrNew(['titulo' => 'Promocion demostrativa DEVIOZ']);
            $promocion->fill([
                'subtitulo' => 'Descuento de prueba aplicado al catalogo demostrativo.',
                'ruta_banner' => 'demo/promociones/oferta.svg',
                'url_destino' => null,
                'fecha_inicio' => '2025-01-01 00:00:00',
                'fecha_fin' => '2035-12-31 23:59:59',
                'esta_activa' => true,
                'tipo_descuento' => 'porcentaje',
                'valor_descuento' => 5,
                'destacada_en_carrusel' => true,
            ]);
            $promocion->save();
            $promocion->productos()->sync(collect($productos)->pluck('id')->all());

            Slider::updateOrCreate(
                ['titulo' => 'Catalogo demostrativo DEVIOZ'],
                [
                    'subtitulo' => 'Productos preparados para validar el despliegue.',
                    'imagen_url' => 'demo/sliders/catalogo.svg',
                    'url_enlace' => '/',
                    'orden' => 900,
                    'activo' => true,
                ]
            );
        });
    }

    private function guardarProducto(Categoria $categoria, array $datos): Producto
    {
        $producto = Producto::withTrashed()->firstOrNew(['slug' => $datos['slug']]);
        $producto->fill([
            'categoria_id' => $categoria->id,
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'marca' => $datos['marca'],
            'esta_activo' => true,
        ]);
        $producto->deleted_at = null;
        $producto->save();

        foreach ($datos['variantes'] as $datosVariante) {
            $variante = VarianteProducto::withTrashed()->firstOrNew(['sku' => $datosVariante['sku']]);

            if (! $variante->exists || $variante->trashed()) {
                $variante->fill($datosVariante + ['producto_id' => $producto->id]);
                $variante->producto_id = $producto->id;
                $variante->deleted_at = null;
                $variante->save();
            }
        }

        ImagenProducto::updateOrCreate(
            ['producto_id' => $producto->id, 'ruta_imagen' => $datos['imagen']],
            ['es_principal' => true]
        );

        return $producto;
    }

    private function publicarRecursos(): void
    {
        $recursos = [
            'productos/laptop.svg',
            'productos/audifonos.svg',
            'productos/mouse.svg',
            'promociones/oferta.svg',
            'sliders/catalogo.svg',
        ];

        foreach ($recursos as $recurso) {
            $origen = database_path('seeders/assets/'.$recurso);

            if (! is_file($origen)) {
                throw new RuntimeException("No se encontro el recurso demostrativo: {$recurso}");
            }

            MediaStorage::disk()->put('demo/'.$recurso, file_get_contents($origen));
        }
    }
}
