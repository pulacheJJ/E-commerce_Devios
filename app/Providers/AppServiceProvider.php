<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // Fachada para interceptar vistas
use App\Models\Carrito; // Tu modelo de carrito

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // View Composer: Envía la cantidad de productos en tiempo real SOLO al navbar
        View::composer('components.tienda.navbar', function ($view) {
            $cantidadCarrito = 0;

            if (session()->has('carrito_token')) {
                $carrito = Carrito::where('token_sesion', session('carrito_token'))->first();
                if ($carrito) {
                    // sum('cantidad') cuenta todas las unidades físicas del carrito
                    $cantidadCarrito = $carrito->elementos()->sum('cantidad'); 
                }
            }

            $view->with('cantidadCarrito', $cantidadCarrito);
        });
    }
}