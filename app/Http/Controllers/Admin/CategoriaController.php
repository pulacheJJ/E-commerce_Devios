<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Services\CategoriaService;
use App\Http\Requests\StoreCategoriaRequest;

class CategoriaController extends Controller
{
    protected $service;

    public function __construct(CategoriaService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $categorias = $this->service->obtenerTodas();
        return view('admin.categorias.index', compact('categorias'));
    }

    public function create()
    {
        $categorias_principales = $this->service->obtenerPrincipales();
        return view('admin.categorias.create', compact('categorias_principales'));
    }

    public function store(StoreCategoriaRequest $request)
    {
        $this->service->crear($request->validated());

        return redirect()->route('admin.categorias.index')
            ->with('success', 'Categoría creada con éxito.');
    }

    public function edit(Categoria $categoria)
    {
        $categorias_principales = $this->service->obtenerPrincipales();
        return view('admin.categorias.edit', compact('categoria', 'categorias_principales'));
    }

    public function update(StoreCategoriaRequest $request, Categoria $categoria)
    {
        $this->service->actualizar($categoria, $request->validated());

        return redirect()->route('admin.categorias.index')
            ->with('success', 'Categoría actualizada con éxito.');
    }

    public function toggle(Categoria $categoria)
    {
        try {
            // Delegamos la lógica de negocio al servicio
            $this->service->toggleEstado($categoria);
            
            // Mensaje dinámico según el nuevo estado
            $estado = $categoria->esta_activa ? 'reactivada' : 'desactivada';
            return back()->with('success', "Categoría {$estado} correctamente.");
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocurrió un error al cambiar el estado de la categoría.');
        }
    }
}