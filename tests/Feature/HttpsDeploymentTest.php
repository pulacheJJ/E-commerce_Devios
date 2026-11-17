<?php

namespace Tests\Feature;

use Tests\TestCase;

class HttpsDeploymentTest extends TestCase
{
    public function test_http_requests_redirect_to_https_when_enforcement_is_enabled(): void
    {
        config(['app.force_https' => true]);

        $response = $this->get('http://localhost/ruta-inexistente');

        $response->assertStatus(301);
        $this->assertSame('https://localhost/ruta-inexistente', $response->headers->get('Location'));
    }

    public function test_forwarded_https_is_recognized_and_receives_hsts(): void
    {
        config(['app.force_https' => true]);

        $response = $this->withHeader('X-Forwarded-Proto', 'https')->get('/health');

        $response->assertOk()->assertJson(['status' => 'ok']);
        $this->assertSame('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
    }

    public function test_health_check_remains_available_inside_the_container_over_http(): void
    {
        config(['app.force_https' => true]);

        $this->get('/health')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_configured_admin_hostname_is_trusted(): void
    {
        config([
            'app.admin_domain' => 'admin.devioz.test',
            'app.force_https' => false,
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'admin.devioz.test'])
            ->get('/health')
            ->assertOk();
    }
}
