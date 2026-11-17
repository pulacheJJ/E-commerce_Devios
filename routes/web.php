<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromocionController;
use App\Http\Controllers\Admin\ShippingRateController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\SimulatedPaymentController;
use App\Http\Controllers\TiendaController;
use Illuminate\Support\Facades\Route;

$adminDomain = config('app.admin_domain');
$storeDomain = parse_url((string) config('app.url'), PHP_URL_HOST);
$storeRoutes = $adminDomain && is_string($storeDomain)
    ? Route::domain($storeDomain)
    : Route::middleware([]);

Route::get('/health', fn () => response()->json(['status' => 'ok']))
    ->name('health');

// ==========================================
// RUTAS PÚBLICAS Tienda
// ==========================================

// 1. LA TIENDA (Público) - Catálogo directo en la raíz
$storeRoutes->group(function (): void {
    Route::get('/', [TiendaController::class, 'index'])->name('tienda.inicio');

    Route::get('/producto/{slug}', [TiendaController::class, 'show'])->name('tienda.producto');

    // Carrito
    Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito.index');
    Route::post('/carrito/agregar', [CarritoController::class, 'agregar'])->middleware('throttle:carrito')->name('carrito.agregar');
    Route::delete('/carrito/eliminar/{id}', [CarritoController::class, 'eliminar'])->middleware('throttle:carrito')->name('carrito.eliminar');
    Route::put('/carrito/actualizar/{id}', [CarritoController::class, 'actualizar'])->middleware('throttle:carrito')->name('carrito.actualizar');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/procesar', [CheckoutController::class, 'procesar'])->middleware('throttle:checkout')->name('checkout.procesar');
    Route::post('/checkout/reintentar/{numeroPedido}', [CheckoutController::class, 'reintentarPago'])->middleware('throttle:checkout')->name('checkout.reintentar');
    Route::get('/checkout/pago/{numero_pedido}', [CheckoutController::class, 'pago'])->middleware('throttle:pago')->name('checkout.pago');
    Route::get('/checkout/pago/{numeroPedido}/estado', [CheckoutController::class, 'estadoPago'])
        ->middleware('throttle:pago')
        ->name('checkout.pago.estado');
    Route::get('/checkout/mercadopago/{numeroPedido}', [CheckoutController::class, 'mercadoPago'])
        ->middleware('throttle:pago')
        ->name('payments.mercadopago.show');

    // Confirmación exclusiva de proveedores registrados; nunca confirma desde la sesión del navegador.
    Route::post('/webhooks/pagos/{provider}', PaymentWebhookController::class)
        ->middleware('throttle:payment-webhook')
        ->name('payments.webhook');

    Route::prefix('demo/pagos')->name('payments.simulator.')->middleware('payment.simulator')->group(function () {
        Route::get('{pago}', [SimulatedPaymentController::class, 'show'])->name('show');
        Route::post('{pago}/simular', [SimulatedPaymentController::class, 'simulate'])
            ->middleware('throttle:pago')->name('simulate');
        Route::post('{pago}/cancelar', [SimulatedPaymentController::class, 'cancel'])
            ->middleware('throttle:pago')->name('cancel');
    });

    // Promociones
    Route::get('/promocion/{id}', [TiendaController::class, 'promocion'])->name('tienda.promocion');
});

// ==========================================
// RUTAS ADMINISTRATIVAS (Backoffice)
// ==========================================
$adminAuthRoutes = $adminDomain
    ? Route::domain($adminDomain)
    : Route::middleware([]);

$adminAuthRoutes->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login-admin')->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

if ($adminDomain) {
    Route::domain($storeDomain)->get('/admin', fn () => redirect()->route('login'));
}

// El acceso a cada módulo se decide por permisos; admin y gestor comparten este grupo autenticado.
$adminRoutes = $adminDomain
    ? Route::domain($adminDomain)
    : Route::prefix('admin');

$adminRoutes->name('admin.')->middleware(['auth'])->group(function () {

    // Inicio del Panel (Dashboard)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Categorías
    Route::get('categorias', [CategoriaController::class, 'index'])->middleware('permission:categorias.ver')->name('categorias.index');
    Route::get('categorias/create', [CategoriaController::class, 'create'])->middleware('permission:categorias.crear')->name('categorias.create');
    Route::post('categorias', [CategoriaController::class, 'store'])->middleware('permission:categorias.crear')->name('categorias.store');
    Route::get('categorias/{categoria}/edit', [CategoriaController::class, 'edit'])->middleware('permission:categorias.editar')->name('categorias.edit');
    Route::match(['put', 'patch'], 'categorias/{categoria}', [CategoriaController::class, 'update'])->middleware('permission:categorias.editar')->name('categorias.update');
    Route::patch('categorias/{categoria}/toggle', [CategoriaController::class, 'toggle'])->middleware('permission:categorias.cambiar_estado')->name('categorias.toggle');

    // Módulo de Productos
    Route::get('productos', [ProductController::class, 'index'])->middleware('permission:productos.ver')->name('productos.index');
    Route::get('productos/create', [ProductController::class, 'create'])->middleware('permission:productos.crear')->name('productos.create');
    Route::post('productos', [ProductController::class, 'store'])->middleware('permission:productos.crear')->name('productos.store');
    Route::get('productos/{producto}/edit', [ProductController::class, 'edit'])->middleware('permission:productos.editar')->name('productos.edit');
    Route::match(['put', 'patch'], 'productos/{producto}', [ProductController::class, 'update'])->middleware('permission:productos.editar')->name('productos.update');
    Route::patch('productos/{producto}/toggle', [ProductController::class, 'toggle'])->middleware('permission:productos.cambiar_estado')->name('productos.toggle');
    Route::get('productos/{producto}', [ProductController::class, 'show'])->middleware('permission:productos.ver')->name('productos.show');

    // Módulo de Sliders
    Route::get('sliders', [SliderController::class, 'index'])->middleware('permission:sliders.ver')->name('sliders.index');
    Route::post('sliders', [SliderController::class, 'store'])->middleware('permission:sliders.crear')->name('sliders.store');
    Route::get('sliders/{slider}/edit', [SliderController::class, 'edit'])->middleware('permission:sliders.editar')->name('sliders.edit');
    Route::match(['put', 'patch'], 'sliders/{slider}', [SliderController::class, 'update'])->middleware('permission:sliders.editar')->name('sliders.update');
    Route::delete('sliders/{slider}', [SliderController::class, 'destroy'])->middleware('permission:sliders.editar')->name('sliders.destroy');
    Route::get('sliders/{slider}', [SliderController::class, 'show'])->middleware('permission:sliders.ver')->name('sliders.show');

    // Módulo de Promociones
    Route::get('promociones', [PromocionController::class, 'index'])->middleware('permission:promociones.ver')->name('promociones.index');
    Route::get('promociones/create', [PromocionController::class, 'create'])->middleware('permission:promociones.crear')->name('promociones.create');
    Route::post('promociones', [PromocionController::class, 'store'])->middleware('permission:promociones.crear')->name('promociones.store');
    Route::get('promociones/{promocione}/edit', [PromocionController::class, 'edit'])->middleware('permission:promociones.editar')->name('promociones.edit');
    Route::match(['put', 'patch'], 'promociones/{promocione}', [PromocionController::class, 'update'])->middleware('permission:promociones.editar')->name('promociones.update');
    Route::delete('promociones/{promocione}', [PromocionController::class, 'destroy'])->middleware('permission:promociones.editar')->name('promociones.destroy');
    Route::patch('promociones/{promocione}/toggle', [PromocionController::class, 'toggle'])->middleware('permission:promociones.cambiar_estado')->name('promociones.toggle');
    Route::get('promociones/{promocione}', [PromocionController::class, 'show'])->middleware('permission:promociones.ver')->name('promociones.show');

    // Módulo de Usuarios
    Route::get('usuarios', [App\Http\Controllers\Admin\UserController::class, 'index'])->middleware('permission:usuarios.ver')->name('usuarios.index');
    Route::get('usuarios/create', [App\Http\Controllers\Admin\UserController::class, 'create'])->middleware('permission:usuarios.crear')->name('usuarios.create');
    Route::post('usuarios', [App\Http\Controllers\Admin\UserController::class, 'store'])->middleware('permission:usuarios.crear')->name('usuarios.store');
    Route::get('usuarios/{usuario}/edit', [App\Http\Controllers\Admin\UserController::class, 'edit'])->middleware('permission:usuarios.editar')->name('usuarios.edit');
    Route::match(['put', 'patch'], 'usuarios/{usuario}', [App\Http\Controllers\Admin\UserController::class, 'update'])->middleware('permission:usuarios.editar')->name('usuarios.update');
    Route::patch('usuarios/{usuario}/toggle', [App\Http\Controllers\Admin\UserController::class, 'toggle'])->middleware('permission:usuarios.cambiar_estado')->name('usuarios.toggle');
    Route::get('usuarios/{usuario}', [App\Http\Controllers\Admin\UserController::class, 'show'])->middleware('permission:usuarios.ver')->name('usuarios.show');

    // Módulo de tarifas de envío
    Route::get('envios', [ShippingRateController::class, 'index'])->middleware('permission:envios.ver')->name('envios.index');
    Route::get('envios/create', [ShippingRateController::class, 'create'])->middleware('permission:envios.crear')->name('envios.create');
    Route::post('envios', [ShippingRateController::class, 'store'])->middleware('permission:envios.crear')->name('envios.store');
    Route::get('envios/{tarifa_envio}/edit', [ShippingRateController::class, 'edit'])->middleware('permission:envios.editar')->name('envios.edit');
    Route::match(['put', 'patch'], 'envios/{tarifa_envio}', [ShippingRateController::class, 'update'])->middleware('permission:envios.editar')->name('envios.update');
    Route::patch('envios/{tarifa_envio}/toggle', [ShippingRateController::class, 'toggle'])->middleware('permission:envios.cambiar_estado')->name('envios.toggle');

});
