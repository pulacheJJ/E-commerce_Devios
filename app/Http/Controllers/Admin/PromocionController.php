<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promocion;
use App\Models\Categoria;
use App\Models\Producto;
use App\Http\Requests\StorePromocionRequest;
use App\Http\Requests\UpdatePromocionRequest;
use App\Services\PromocionService;
use Illuminate\Http\Request;

class PromocionController extends Controller
{
    protected $promocionService;

    public function __construct(PromocionService $promocionService)
    {
        $this->promocionService = $promocionService;
    }

    public function index()
    {
        $promociones = Promocion::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.promociones.index', compact('promociones'));
    }

    public function create()
    {
        $categorias = Categoria::where('esta_activa', true)->get();
        $productos = Producto::with('variantes')->where('esta_activo', true)->get();
        
        return view('admin.promociones.create', compact('categorias', 'productos'));
    }

    public function store(StorePromocionRequest $request)
    {
        $this->promocionService->crearPromocion(
            $request->validated(), 
            $request->file('ruta_banner')
        );

        return redirect()->route('admin.promociones.index')
                         ->with('success', '¡Promoción creada y vinculada con éxito!');
    }

    public function show(Promocion $promocione)
    {
        $promocione->load(['categorias', 'productos']);
        return view('admin.promociones.show', compact('promocione'));
    }

    public function edit(Promocion $promocione)
    {
        $promocion = $promocione; 
        $categorias = Categoria::where('esta_activa', true)->get();
        $productos = Producto::with('variantes')->where('esta_activo', true)->get();

        return view('admin.promociones.edit', compact('promocion', 'categorias', 'productos'));
    }

    public function update(UpdatePromocionRequest $request, Promocion $promocione)
    {
        $this->promocionService->actualizarPromocion(
            $promocione,
            $request->validated(), 
            $request->file('ruta_banner')
        );

        return redirect()->route('admin.promociones.index')
                         ->with('success', '¡Promoción actualizada perfectamente!');
    }

    public function toggle(Promocion $promocione)
    {
        $estado = $this->promocionService->alternarEstado($promocione);
        $mensaje = $estado ? 'activada' : 'desactivada';

        return redirect()->back()
                         ->with('success', "¡Promoción $mensaje correctamente!");
    }

    public function destroy(Promocion $promocione)
    {
        $this->promocionService->eliminarPromocion($promocione);

        return redirect()->route('admin.promociones.index')
                         ->with('success', '¡Promoción eliminada permanentemente!');
    }
}