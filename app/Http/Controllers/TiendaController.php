<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogoFilterRequest;
use App\Models\Promocion;
use App\Services\CatalogoService;

class TiendaController extends Controller
{
    private $catalogoService;

    // Inyectamos tu nuevo servicio
    public function __construct(CatalogoService $catalogoService)
    {
        $this->catalogoService = $catalogoService;
    }

    public function index(CatalogoFilterRequest $request)
    {
        $filtros = $request->validated();

        $productos = $this->catalogoService->obtenerCatalogoPaginado($filtros);
        $categoriasPadre = $this->catalogoService->obtenerCategoriasPadreConConteo($filtros);
        $marcas = $this->catalogoService->obtenerMarcasActivas($filtros);
        $slides = $this->catalogoService->obtenerSlidesHero();

        return view('tienda.inicio', compact('productos', 'categoriasPadre', 'marcas', 'slides'));
    }

    // Añade este método debajo de tu método index()

    public function promocion(CatalogoFilterRequest $request, $id)
    {
        $promocion = Promocion::where('esta_activa', true)->findOrFail($id);
        abort_unless($promocion->es_vigente, 404);

        // CORRECCIÓN: Si $request->all() es null, lo forzamos a ser un array vacío
        $filtros = $request->validated();

        // Forzamos el filtro de la promoción
        $filtros['promocion_id'] = $promocion->id;

        // Ahora al pasar $filtros, garantizamos que siempre es un array
        $productos = $this->catalogoService->obtenerCatalogoPaginado($filtros);
        $categoriasPadre = $this->catalogoService->obtenerCategoriasPadreConConteo($filtros);
        $marcas = $this->catalogoService->obtenerMarcasActivas($filtros);

        $slides = collect();

        return view('tienda.inicio', compact('productos', 'categoriasPadre', 'marcas', 'slides', 'promocion'));
    }

    public function show($slug)
    {
        $producto = $this->catalogoService->obtenerProductoPorSlug($slug);
        $relacionados = $this->catalogoService->obtenerCategoriasRelacionados($producto, 4);

        return view('tienda.show', compact('producto', 'relacionados'));
    }
}
