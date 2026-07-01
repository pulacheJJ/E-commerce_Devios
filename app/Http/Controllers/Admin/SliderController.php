<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSliderRequest;
use App\Http\Requests\UpdateSliderRequest;
use App\Services\SliderService;
use App\Models\Slider;
use Illuminate\Http\RedirectResponse;

class SliderController extends Controller
{
    private $sliderService;

    public function __construct(SliderService $sliderService)
    {
        $this->sliderService = $sliderService;
    }

    public function index()
    {
        $sliders = $this->sliderService->listarTodos();
        return view('admin.sliders.index', compact('sliders'));
    }

    public function store(StoreSliderRequest $request): RedirectResponse
    {
        $this->sliderService->guardarSlider($request->validated(), $request->file('imagen'));
        return redirect()->route('admin.sliders.index')->with('success', 'Banner agregado exitosamente.');
    }

    public function edit($id)
    {
        $slider = $this->sliderService->obtenerPorId($id);
        // Retornamos la vista de edición (Frontend que montaremos después)
        return view('admin.sliders.edit', compact('slider')); 
    }

    public function update(UpdateSliderRequest $request, $id): RedirectResponse
    {
        $this->sliderService->actualizarSlider($id, $request->validated(), $request->file('imagen'));
        return redirect()->route('admin.sliders.index')->with('success', 'Banner actualizado correctamente.');
    }

    public function destroy($id): RedirectResponse
    {
        $this->sliderService->eliminarSlider($id);
        return redirect()->route('admin.sliders.index')->with('success', 'Banner eliminado del sistema.');
    }

    public function show(Slider $slider) {
        return view('admin.sliders.show', compact('slider'));
    }
}