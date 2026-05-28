<?php

namespace App\Http\Controllers;

use App\Services\CatalogoService;
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
        // Le pasamos todo el Request al servicio por si vienen variables por la URL (?buscar=laptop)
        $productos = $this->catalogoService->obtenerCatalogoPaginado($request->all());
        $categorias = $this->catalogoService->obtenerCategoriasConConteo();

        return view('tienda.catalogo', compact('productos', 'categorias'));
    }

    public function show($slug)
    {
        $producto = $this->catalogoService->obtenerProductoPorSlug($slug);
        $relacionados = $this->catalogoService->obtenerCategoriasRelacionados($producto, 4);
        return view('tienda.show', compact('producto', 'relacionados'));
    }
}