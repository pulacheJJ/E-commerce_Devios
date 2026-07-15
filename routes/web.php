<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\PromocionController;
use App\Http\Controllers\Admin\AuthController;

// ==========================================
// RUTAS PÚBLICAS Tienda
// ==========================================

// 1. LA TIENDA (Público) - Catálogo directo en la raíz
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
Route::get('/checkout/pago/{numero_pedido}', [CheckoutController::class, 'pago'])->middleware('throttle:pago')->name('checkout.pago');

// Promociones
Route::get('/promocion/{id}', [TiendaController::class, 'promocion'])->name('tienda.promocion');

// ==========================================
// RUTAS ADMINISTRATIVAS (Backoffice)
// ==========================================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login-admin')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Agregamos 'role:admin' para que solo los administradores puedan acceder
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    
    // Inicio del Panel (Dashboard)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Categorías
    Route::patch('categorias/{categoria}/toggle', [CategoriaController::class, 'toggle'])->name('categorias.toggle');
    Route::resource('categorias', CategoriaController::class);

    // Módulo de Productos
    Route::patch('productos/{producto}/toggle', [ProductController::class, 'toggle'])->name('productos.toggle');
    Route::resource('productos', ProductController::class);

    // Módulo de Sliders
    Route::resource('sliders', SliderController::class);

    // Módulo de Promociones
    Route::patch('promociones/{promocione}/toggle', [PromocionController::class, 'toggle'])->name('promociones.toggle');
    Route::resource('promociones', PromocionController::class);

    // Módulo de Usuarios
    Route::patch('usuarios/{usuario}/toggle', [App\Http\Controllers\Admin\UserController::class, 'toggle'])->name('usuarios.toggle');
    Route::resource('usuarios', App\Http\Controllers\Admin\UserController::class);

});
