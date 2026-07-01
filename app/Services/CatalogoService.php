<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Slider;
use App\Models\Promocion;
use Illuminate\Database\Eloquent\Builder;


class CatalogoService
{
    public function obtenerCatalogoPaginado(array $filtros = [], int $paginacion = 12)
    {
        // 1. Iniciamos la consulta base (Eager Loading y solo activos)
        $query = Producto::with(['imagenes', 'variantes', 'categoria'])
            ->where('esta_activo', true);

        $this->aplicarFiltros($query, $filtros);
        return $query->latest()->paginate($paginacion);
    }
    public function obtenerCategoriasPadreConConteo(array $filtros = [])
    {
        $promocionId = $filtros['promocion_id'] ?? null;

        return Categoria::where('esta_activa', true)
            ->withCount([
                'productos' => function ($query) use ($promocionId) {
                    $query->where('esta_activo', true);

                    // Si estamos en una promoción, solo cuenta los productos de esa promo
                    if ($promocionId) {
                        $this->filtrarPorPromocion($query, $promocionId);
                    }
                }
            ])
            // Solo trae las categorías que tienen > 0 productos (después de aplicar el filtro)
            ->having('productos_count', '>', 0)
            ->orderBy('nombre')
            ->get();
    }
    public function obtenerProductoPorSlug(string $slug)
    {
        return Producto::with(['imagenes', 'variantes', 'categoria'])
            ->where('slug', $slug)
            ->where('esta_activo', true)
            ->firstOrFail(); // Lanza error 404 si alguien intenta entrar a un producto apagado
    }

    public function obtenerMarcasActivas(array $filtros = [])
    {
        $categoriaSlug = $filtros['categoria'] ?? null;
        $promocionId = $filtros['promocion_id'] ?? null;

        $query = Producto::where('esta_activo', true)
            ->whereNotNull('marca')
            ->where('marca', '!=', '');

        if ($categoriaSlug) {
            $query->whereHas('categoria', function ($q) use ($categoriaSlug) {
                $q->where('slug', $categoriaSlug);
            });
        }

        // Si estamos en una promoción, solo trae las marcas de esos productos
        if ($promocionId) {
            $this->filtrarPorPromocion($query, $promocionId);
        }

        return $query->select('marca')->distinct()->orderBy('marca')->pluck('marca');
    }

    public function obtenerCategoriasRelacionados(Producto $producto, int $limite = 4)
    {
        return Producto::with(['imagenes', 'variantes', 'categoria'])
            ->where('categoria_id', $producto->categoria_id)
            ->where('id', '!=', $producto->id)
            ->where('esta_activo', true)
            ->inRandomOrder()
            ->limit($limite)
            ->get();
    }

    public function obtenerSlidesHero()
    {
        // 1. Sliders manuales
        $sliders = Slider::where('activo', true)->orderBy('orden', 'asc')->get();

        // 2. Promociones destacadas (usando tu helper es_vigente)
        $promociones = Promocion::where('esta_activa', true)
            ->where('destacada_en_carrusel', true)
            ->get()
            ->filter(fn($p) => $p->es_vigente);

        // 3. Unimos todo
        return $sliders->concat($promociones);
    }

    private function aplicarFiltros(Builder $query, array $filtros): void
    {
        // Responsabilidad: Orquestar todos los filtros
        $this->filtrarPorBusqueda($query, $filtros['buscar'] ?? null);
        $this->filtrarPorCategoria($query, $filtros['categoria'] ?? null);
        $this->filtrarPorMarcas($query, $filtros['marcas'] ?? []);
        $this->filtrarPorRangoPrecio($query, $filtros['precio'] ?? null);
        $this->filtrarPorPromocion($query, $filtros['promocion_id'] ?? null);
    }

    private function filtrarPorBusqueda(Builder $query, ?string $buscar): void
    {
        // 1. Early Return: Si el usuario no buscó nada, no hacemos nada.
        if (empty($buscar)) {
            return;
        }

        // 2. Preparamos la variable una sola vez
        $termino = "%{$buscar}%";

        // 3. Consulta plana y limpia usando Arrow Functions (PHP 7.4+)
        $query->where(function (Builder $q) use ($termino) {

            $q->where('nombre', 'LIKE', $termino)
                ->orWhere('marca', 'LIKE', $termino)
                ->orWhereHas(
                    'categoria',
                    fn($categoria) =>
                    $categoria->where('nombre', 'LIKE', $termino)
                        ->orWhereHas(
                            'padre',
                            fn($padre) =>
                            $padre->where('nombre', 'LIKE', $termino)
                        )
                );
        });
    }

    private function filtrarPorCategoria(Builder $query, ?string $categoriaSlug): void
    {
        $query->when($categoriaSlug, function ($q, $slug) {
            // Usamos whereHas para buscar el slug dentro de la tabla relacionada 'categorias'
            $q->whereHas('categoria', function ($queryCategoria) use ($slug) {
                $queryCategoria->where('slug', $slug);
            });
        });
    }

    private function filtrarPorRangoPrecio(Builder $query, ?string $rango): void
    {
        if (empty($rango))
            return;

        $partes = explode('-', $rango);

        $query->whereHas('variantes', function ($q) use ($partes, $rango) {

            // Creamos la lógica SQL: Si precio_oferta > 0 toma ese valor, sino toma el precio normal.
            $sqlPrecioReal = '(CASE WHEN precio_oferta > 0 THEN precio_oferta ELSE precio END)';

            if (count($partes) === 2) {
                // Filtramos el rango usando la lógica del precio real
                $q->whereRaw("$sqlPrecioReal BETWEEN ? AND ?", [(float) $partes[0], (float) $partes[1]]);
            } elseif ($rango === '1000+') {
                // Filtramos si es mayor a 1000
                $q->whereRaw("$sqlPrecioReal >= ?", [1000]);
            }
        });
    }

    private function filtrarPorMarcas(Builder $query, $marcas): void
    {
        // Responsabilidad: Aplicar el filtro 'WHERE IN' para marcas
        if (!empty($marcas) && is_array($marcas)) {
            $query->whereIn('marca', $marcas);
        }
    }
    private function filtrarPorPromocion(Builder $query, ?int $promocionId): void
    {
        if (!$promocionId) {
            return;
        }

        $query->where(function ($q) use ($promocionId) {
            // Condición 1: El producto tiene la promoción asignada directamente
            $q->whereHas('promociones', function ($qPromo) use ($promocionId) {
                $qPromo->where('promociones.id', $promocionId);
            })
                // Condición 2: La categoría del producto tiene la promoción asignada
                ->orWhereHas('categoria.promociones', function ($qCatPromo) use ($promocionId) {
                    $qCatPromo->where('promociones.id', $promocionId);
                });
        });
    }
}