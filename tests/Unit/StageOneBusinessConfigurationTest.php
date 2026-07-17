<?php

namespace Tests\Unit;

use App\Models\Administrador;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tests\TestCase;

class StageOneBusinessConfigurationTest extends TestCase
{
    public function test_user_is_the_only_authentication_provider_model(): void
    {
        $this->assertSame(User::class, config('auth.providers.users.model'));
        $this->assertInstanceOf(Authenticatable::class, new User);
        $this->assertInstanceOf(Model::class, new Administrador);
        $this->assertNotInstanceOf(Authenticatable::class, new Administrador);
    }

    public function test_pending_order_expiration_configuration_is_valid(): void
    {
        $this->assertGreaterThan(0, config('pedidos.expiration_minutes'));
        $this->assertSame(['pendiente'], config('pedidos.expirable_states'));
        $this->assertGreaterThan(0, config('pedidos.cart_expiration_days'));
    }

    public function test_client_migration_uses_the_renamed_column(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_05_28_180055_actualizar_tabla_clientes.php'));

        $this->assertStringContainsString("after('nombres')", $migration);
        $this->assertStringNotContainsString("after('nombre')", $migration);
    }
}
