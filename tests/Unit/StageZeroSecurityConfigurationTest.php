<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StageZeroSecurityConfigurationTest extends TestCase
{
    private function projectFile(string $path): string
    {
        return file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$path);
    }

    public function test_cart_mutations_are_scoped_to_the_session_cart(): void
    {
        $service = $this->projectFile('app/Services/CarritoService.php');

        $this->assertStringContainsString("session()->get('carrito_token')", $service);
        $this->assertStringContainsString("whereHas('carrito'", $service);
        $this->assertStringNotContainsString('ElementoCarrito::find($elementoId)', $service);
    }

    public function test_checkout_has_no_public_payment_approval_route(): void
    {
        $routes = $this->projectFile('routes/web.php');

        $this->assertStringNotContainsString('/checkout/pago/confirmar', $routes);
        $this->assertStringNotContainsString('confirmarPago', $routes);
    }

    public function test_verified_payment_path_is_idempotent_and_locked(): void
    {
        $service = $this->projectFile('app/Services/CheckoutService.php');

        $this->assertStringContainsString("where('id_transaccion', \$idTransaccion)", $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString('last_checkout_order_id', $service);
        $this->assertStringNotContainsString("'TRX-'", $service);
    }

    public function test_no_predictable_admin_password_remains_in_the_seeder(): void
    {
        $seeder = $this->projectFile('database/seeders/UserSeeder.php');

        $this->assertStringContainsString('SEED_ADMIN_PASSWORD', $seeder);
        $this->assertStringNotContainsString('Admin123', $seeder);
    }

    public function test_production_container_defaults_are_hardened(): void
    {
        $compose = $this->projectFile('docker-compose.yml');
        $productionEnvironment = $this->projectFile('.env.production.example');

        $this->assertStringNotContainsString('3306:3306', $compose);
        $this->assertStringContainsString('DOCKER_APP_DEBUG:-false', $compose);
        $this->assertStringContainsString('APP_DEBUG=false', $productionEnvironment);
        $this->assertStringContainsString('MYSQL_USER', $compose);
    }
}
