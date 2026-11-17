<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeAdminResourceStatusRequest;
use App\Http\Requests\StoreShippingRateRequest;
use App\Http\Requests\UpdateShippingRateRequest;
use App\Models\Provincia;
use App\Models\TarifaEnvio;
use App\Services\ShippingRateService;
use DomainException;

class ShippingRateController extends Controller
{
    public function __construct(private ShippingRateService $rates) {}

    public function index()
    {
        $tarifas = TarifaEnvio::with('provincia.departamento')
            ->orderByDesc('es_predeterminada')->orderBy('provincia_id')->paginate(20);

        return view('admin.envios.index', compact('tarifas'));
    }

    public function create()
    {
        return view('admin.envios.create', ['provincias' => $this->provinces()]);
    }

    public function store(StoreShippingRateRequest $request)
    {
        $this->rates->create($request->validated());

        return redirect()->route('admin.envios.index')->with('success', 'Tarifa de envío creada.');
    }

    public function edit(TarifaEnvio $tarifa_envio)
    {
        return view('admin.envios.edit', [
            'tarifa' => $tarifa_envio,
            'provincias' => $this->provinces(),
        ]);
    }

    public function update(UpdateShippingRateRequest $request, TarifaEnvio $tarifa_envio)
    {
        $this->rates->update($tarifa_envio, $request->validated());

        return redirect()->route('admin.envios.index')->with('success', 'Tarifa de envío actualizada.');
    }

    public function toggle(ChangeAdminResourceStatusRequest $request, TarifaEnvio $tarifa_envio)
    {
        try {
            $rate = $this->rates->toggle($tarifa_envio);

            return redirect()->route('admin.envios.index')
                ->with('success', $rate->esta_activa ? 'Tarifa activada.' : 'Tarifa desactivada.');
        } catch (DomainException $exception) {
            return redirect()->route('admin.envios.index')->with('error', $exception->getMessage());
        }
    }

    private function provinces()
    {
        return Provincia::with('departamento')->orderBy('departamento_id')->orderBy('nombre')->get();
    }
}
