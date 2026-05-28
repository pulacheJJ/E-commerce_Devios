<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Categoria;
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

    public function obtenerCategoriasConConteo()
    {
        return Categoria::where('esta_activa', true)
            ->withCount([
                'productos' => function ($query) {
                    $query->where('esta_activo', true);
                }
            ])
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

    public function obtenerCategoriasRelacionados(Producto $producto, int $limite = 4)
    {
        return Producto::with(['imagenes', 'variantes', 'categoria'])
        ->where('categoria_id', $producto->categoria_id)
        ->where('id','!=', $producto->id)
        ->where('esta_activo', true)
        ->inRandomOrder()
        ->limit($limite)
        ->get();
    }

    private function aplicarFiltros(Builder $query, array $filtros): void
    {
        $this->filtrarPorBusqueda($query, $filtros['buscar'] ?? null);
        $this->filtrarPorCategoria($query, $filtros['categoria'] ?? null);

        // Aquí se agregaran los futuros filtros:
        // $this->filtrarPorRangoPrecio($query, $filtros['min_precio'] ?? null, $filtros['max_precio'] ?? null);
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
}