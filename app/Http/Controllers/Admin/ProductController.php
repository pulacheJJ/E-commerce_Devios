<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Producto;
use App\Services\ProductoService;

class ProductController extends Controller
{
    protected $service;

    public function __construct(ProductoService $service)
    {
        $this->service = $service;
    }
    public function index()
    {
        $productos = $this->service->obtenerTodas();
        return view('admin.productos.index', compact('productos'));
    }

    public function create()
    {
        $categorias = $this->service->obtenerCategoria();
        return view('admin.productos.create', compact('categorias'));
    }

    public function store(StoreProductoRequest $request)
    {
        $this->service->crear($request->validated());
        return redirect()->route('admin.productos.index')
            ->with('success', 'Producto creado con Éxito');
    }

    public function edit(Producto $producto)
    {
        $categorias = $this->service->obtenerCategoria();
        return view('admin.productos.edit', compact('producto', 'categorias'));
    }

    public function update(UpdateProductoRequest $request, Producto $producto)
    {
        $this->service->actualizar($producto, $request->validated());
        return redirect()->route('admin.productos.index')->with('success', 'producto actualizado con exito');
    }

    public function toggle(Producto $producto)
    {
        $this->service->cambiarEstado($producto);
        $mensaje = $producto->esta_activo
            ? 'Producto activado y visible en la tienda con éxito.'
            : 'Producto desactivado y ocultado de la tienda con éxito.';
        return redirect()->route('admin.productos.index')->with('success', $mensaje);
    }
    public function show(Producto $producto)
    {
        $producto->load(['categoria', 'imagenes', 'variantes']);
        return view('admin.productos.show', compact('producto'));
    }
}