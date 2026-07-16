<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductoService
{
    public function obtenerTodas()
    {
        return Producto::with(['categoria', 'imagenes', 'variantes'])
            ->orderBy('id', 'desc') // Los más nuevos primero
            ->get();
    }

    public function obtenerCategoria()
    {
        return Categoria::orderBy('nombre', 'asc')->get();
    }

    public function crear(array $datos)
    {
        // preparamos los datos base
        $datos['slug'] = Str::slug($datos['nombre']);
        $datos['esta_activo'] = isset($datos['esta_activo']);

        $variantes = $datos['variantes'];
        $imagenes = $datos['imagenes'];

        return DB::transaction(function () use ($datos) {
            // Creamos el producto base
            $producto = Producto::create($datos);

            // crear producto variante
            $this->crearVariantes($producto, $datos['variantes']);

            // subir y guardar imagenes
            $this->subirGaleriaImagenes($producto, $datos['imagenes']);

            return $producto;
        });

    }

    public function actualizar(Producto $producto, array $datos): Producto
    {
        $datos['slug'] = Str::slug($datos['nombre']);
        $datos['esta_activo'] = isset($datos['esta_activo']);

        return DB::transaction(function () use ($producto, $datos) {
            $producto->update($datos);
            $this->sincronizarVariantes($producto, $datos['variantes']);
            if (! empty($datos['imagenes'])) {
                $this->subirNuevasImagenes($producto, $datos['imagenes']);
            }

            return $producto;
        });
    }

    private function crearVariantes(Producto $producto, array $variantes): void
    {
        foreach ($variantes as $variante) {
            $producto->variantes()->create([
                'sku' => trim($variante['sku']),
                'color' => $variante['color'] ?? null,
                'especificacion' => $variante['especificacion'] ?? null,
                'precio' => $variante['precio'],
                'precio_oferta' => $variante['precio_oferta'] ?? null,
                'stock' => $variante['stock'],
            ]);
        }
    }

    private function subirGaleriaImagenes(Producto $producto, array $imagenes): void
    {
        foreach ($imagenes as $index => $archivo) {
            $producto->imagenes()->create([
                'ruta_imagen' => $archivo->store('products', 'public'),
                'es_principal' => $index === 0 ? true : false, // La primera de la lista será la portada
            ]);
        }
    }

    private function sincronizarVariantes(Producto $producto, array $variantes): void
    {
        $variantesIds = [];

        foreach ($variantes as $varData) {
            if (! empty($varData['id'])) {
                $producto->variantes()->where('id', $varData['id'])->update([
                    'sku' => trim($varData['sku']),
                    'color' => $varData['color'] ?? null,
                    'especificacion' => $varData['especificacion'] ?? null,
                    'precio' => $varData['precio'],
                    'precio_oferta' => $varData['precio_oferta'] ?? null,
                    'stock' => $varData['stock'],
                ]);
                $variantesIds[] = $varData['id'];
            } else {
                $varData['sku'] = trim($varData['sku']);
                $nuevaVariante = $producto->variantes()->create($varData);
                $variantesIds[] = $nuevaVariante->id;
            }
        }

        // eliminamos las variantes que el usario borro
        if (! empty($variantesIds)) {
            $producto->variantes()->whereNotIn('id', $variantesIds)->delete();
        }
    }

    private function subirNuevasImagenes(Producto $producto, array $imagenes): void
    {
        foreach ($imagenes as $archivo) {
            $producto->imagenes()->create([
                'ruta_imagen' => $archivo->store('products', 'public'),
                'es_principal' => false,
            ]);
        }
    }

    public function cambiarEstado(Producto $producto): bool
    {
        return $producto->update([
            'esta_activo' => ! $producto->esta_activo,
        ]);
    }
}
