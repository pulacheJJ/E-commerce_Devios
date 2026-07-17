<?php

namespace Tests\Feature;

use App\Services\CarritoService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class StageZeroSecurityTest extends TestCase
{
    public function test_public_payment_confirmation_endpoint_is_not_exposed(): void
    {
        $this->postJson('/checkout/pago/confirmar', [
            'numero_pedido' => 'PED-FAKE',
            'metodo_pago' => 'tarjeta',
        ])->assertStatus(405);
    }

    public function test_admin_login_is_rate_limited_before_authentication(): void
    {
        $ip = '198.51.100.77';
        $email = 'stage-zero@example.test';
        $credentialKey = hash('sha256', $email.'|'.$ip);
        $ipKey = 'admin-login-ip:'.$ip;

        RateLimiter::clear($credentialKey);
        RateLimiter::clear($ipKey);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson('/login', ['email' => $email, 'password' => 'x'])
                ->assertUnprocessable();
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/login', ['email' => $email, 'password' => 'x'])
            ->assertStatus(429);

        RateLimiter::clear($credentialKey);
        RateLimiter::clear($ipKey);
    }

    public function test_checkout_owner_hash_is_private_and_stable_in_the_session(): void
    {
        $service = $this->app->make(CarritoService::class);

        $firstHash = $service->obtenerHashPropietarioActual(true);
        $secondHash = $service->obtenerHashPropietarioActual();

        $this->assertSame($firstHash, $secondHash);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $firstHash);
        $this->assertNotSame(session('checkout_owner_token'), $firstHash);
    }

    public function test_checkout_does_not_expose_internal_exceptions(): void
    {
        Log::spy();

        $checkout = Mockery::mock(CheckoutService::class);
        $checkout->shouldReceive('procesarCompra')
            ->once()
            ->andThrow(new RuntimeException('SQLSTATE secret-host.internal password=secret'));
        $this->app->instance(CheckoutService::class, $checkout);

        $response = $this->postJson('/checkout/procesar', [
            'nombres' => 'Cliente',
            'apellidos' => 'Prueba',
            'email' => 'cliente@example.test',
            'telefono' => '999999999',
            'documento' => '12345678',
            'direccion_envio' => 'RETIRO EN TIENDA',
            'referencia' => null,
            'metodo_entrega' => 'retiro',
            'distrito' => null,
        ]);

        $response->assertStatus(500)
            ->assertJsonPath('message', 'Ocurrió un error al procesar la compra.')
            ->assertJsonStructure(['incident_id'])
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('secret-host.internal');

        Log::shouldHaveReceived('error')->once();
    }

    public function test_cart_does_not_expose_internal_exceptions(): void
    {
        Log::spy();

        $cart = Mockery::mock(CarritoService::class);
        $cart->shouldReceive('actualizarCantidad')
            ->once()
            ->andThrow(new RuntimeException('SQLSTATE db.internal password=secret'));
        $this->app->instance(CarritoService::class, $cart);

        $response = $this->putJson('/carrito/actualizar/123', ['cantidad' => 1]);

        $response->assertStatus(500)
            ->assertJsonPath('message', 'No fue posible procesar el carrito.')
            ->assertJsonStructure(['incident_id'])
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('db.internal');

        Log::shouldHaveReceived('error')->once();
    }
}
