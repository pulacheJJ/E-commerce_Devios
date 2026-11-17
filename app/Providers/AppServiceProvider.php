<?php

namespace App\Providers;

use App\Contracts\ShippingCostCalculator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // Fachada para interceptar vistas
use Illuminate\Support\Facades\URL;
use App\Models\Carrito; // Tu modelo de carrito
use App\Services\DatabaseShippingCostCalculator;
use App\Services\Payments\PaymentProviderRegistry;
use App\Services\Payments\MercadoPagoPaymentProvider;
use App\Services\Payments\SimulatedPaymentProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ShippingCostCalculator::class, DatabaseShippingCostCalculator::class);
        $this->app->singleton(PaymentProviderRegistry::class, function (): PaymentProviderRegistry {
            $registry = new PaymentProviderRegistry;
            $secret = (string) config('checkout.simulator.secret');
            if (config('checkout.simulator.enabled') && strlen($secret) >= 32) {
                $registry->register(new SimulatedPaymentProvider($secret));
            }

            if (config('checkout.mercadopago.enabled')) {
                $registry->register(new MercadoPagoPaymentProvider(
                    (string) config('checkout.mercadopago.test.access_token'),
                    (string) config('checkout.mercadopago.mode'),
                    (bool) config('checkout.mercadopago.allow_live'),
                    config('checkout.mercadopago.test.webhook_secret'),
                ));
            }

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

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
