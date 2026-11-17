<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        if (app()->environment('local')) {
            return ['.*'];
        }

        $hosts = [$this->allSubdomainsOfApplicationUrl()];
        $adminDomain = config('app.admin_domain');

        if (is_string($adminDomain) && trim($adminDomain) !== '') {
            $hosts[] = '^'.preg_quote(trim($adminDomain), '/').'$';
        }

        return array_values(array_filter($hosts));
    }
}
