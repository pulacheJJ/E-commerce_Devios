<?php

namespace App\Http\Controllers;

use App\Services\CatalogoService;
use App\Models\Promocion;
use Illuminate\Http\Request;

class TiendaController extends Controller
{
    private $catalogoService;

    // Inyectamos tu nuevo servicio
    public function __construct(CatalogoService $catalogoService)
    {
        $this->catalogoService = $catalogoService;
    }

    public function index(Request $request)
    {
        $filtros = $request->all();

        $productos = $this->catalogoService->obtenerCatalogoPaginado($filtros);
        $categoriasPadre = $this->catalogoService->obtenerCategoriasPadreConConteo($filtros);
        $marcas = $this->catalogoService->obtenerMarcasActivas($filtros);
        $slides = $this->catalogoService->obtenerSlidesHero();
        return view('tienda.catalogo', compact('productos', 'categoriasPadre', 'marcas', 'slides'));
    }

    // Añade este método debajo de tu método index()

    public function promocion(Request $request, $id)
    {
        $promocion = Promocion::findOrFail($id);

        // CORRECCIÓN: Si $request->all() es null, lo forzamos a ser un array vacío
        $filtros = $request->all() ?? [];

        // Forzamos el filtro de la promoción
        $filtros['promocion_id'] = $promocion->id;

        // Ahora al pasar $filtros, garantizamos que siempre es un array
        $productos = $this->catalogoService->obtenerCatalogoPaginado($filtros);
        $categoriasPadre = $this->catalogoService->obtenerCategoriasPadreConConteo($filtros);
        $marcas = $this->catalogoService->obtenerMarcasActivas($filtros);

        $slides = collect();

        return view('tienda.catalogo', compact('productos', 'categoriasPadre', 'marcas', 'slides', 'promocion'));
    }
    public function show($slug)
    {
        $producto = $this->catalogoService->obtenerProductoPorSlug($slug);
        $relacionados = $this->catalogoService->obtenerCategoriasRelacionados($producto, 4);
        return view('tienda.show', compact('producto', 'relacionados'));
    }
}
