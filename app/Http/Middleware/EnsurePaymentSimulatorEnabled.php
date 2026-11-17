<?php

namespace App\Http\Middleware;

use App\Services\Payments\PaymentProviderRegistry;
use Closure;
use Illuminate\Http\Request;

class EnsurePaymentSimulatorEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(
            config('checkout.simulator.enabled') && app(PaymentProviderRegistry::class)->has('simulated'),
            404
        );

        return $next($request);
    }
}
