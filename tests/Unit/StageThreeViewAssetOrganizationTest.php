<?php

namespace Tests\Unit;

use Tests\TestCase;

class StageThreeViewAssetOrganizationTest extends TestCase
{
    public function test_simulator_view_uses_external_styles_only(): void
    {
        $view = file_get_contents(resource_path('views/tienda/pagos/simulador.blade.php'));

        $this->assertStringContainsString("asset('css/tienda/pagos/simulador.css')", $view);
        $this->assertStringNotContainsString('<style', $view);
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $view);
        $this->assertStringNotContainsString('<script', $view);
        $this->assertFileExists(public_path('css/tienda/pagos/simulador.css'));
    }

    public function test_shipping_views_keep_css_and_javascript_external(): void
    {
        $views = collect([
            'views/admin/envios/index.blade.php',
            'views/admin/envios/create.blade.php',
            'views/admin/envios/edit.blade.php',
            'views/admin/envios/partials/form.blade.php',
        ])->map(fn (string $path): string => file_get_contents(resource_path($path)))->implode("\n");

        $this->assertStringNotContainsString('<style', $views);
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $views);
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/i', $views);
        $this->assertStringContainsString("asset('js/admin/envios/form.js')", $views);
        $this->assertStringContainsString("@import url('admin/envios.css')", file_get_contents(public_path('css/admin.css')));
        $this->assertFileExists(public_path('css/admin/envios.css'));
        $this->assertFileExists(public_path('js/admin/envios/form.js'));
    }

    public function test_mercado_pago_view_uses_the_official_sdk_and_external_assets(): void
    {
        $view = file_get_contents(resource_path('views/tienda/pagos/mercadopago.blade.php'));

        $this->assertStringContainsString('https://sdk.mercadopago.com/js/v2', $view);
        $this->assertStringContainsString("asset('js/tienda/pagos/mercadopago.js')", $view);
        $this->assertStringContainsString("asset('css/tienda/pagos/mercadopago.css')", $view);
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/i', $view);
        $this->assertStringNotContainsString('<style', $view);
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $view);
        $this->assertFileExists(public_path('js/tienda/pagos/mercadopago.js'));
        $this->assertFileExists(public_path('css/tienda/pagos/mercadopago.css'));
    }

    public function test_payment_status_view_uses_external_javascript(): void
    {
        $view = file_get_contents(resource_path('views/tienda/pago.blade.php'));

        $this->assertStringContainsString("asset('js/tienda/pagos/estado.js')", $view);
        $this->assertStringContainsString("asset('css/tienda/pagos/estado.css')", $view);
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/i', $view);
        $this->assertStringNotContainsString('<style', $view);
        $this->assertFileExists(public_path('js/tienda/pagos/estado.js'));
        $this->assertFileExists(public_path('css/tienda/pagos/estado.css'));

        $javascript = file_get_contents(public_path('js/tienda/pagos/estado.js'));
        $this->assertStringContainsString('showPaymentSuccess', $javascript);
        $this->assertStringContainsString('window.location.assign(redirectUrl)', $javascript);
        $this->assertStringNotContainsString('window.location.reload()', $javascript);
    }
}
