<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\CarritoController;

// ==========================================
// RUTAS PÚBLICAS (Frontend / Tienda)
// ==========================================

// Página Inicial Pública (Tienda)
Route::get('/', function () {
    return view('welcome'); // Aquí podrías crear una landing page de DEVIOZ después
})->name('tienda.inicio');

// Módulo para el catálogo público
Route::get('/catalogo', [TiendaController::class, 'index'])->name('tienda.catalogo');
Route::get('/producto/{slug}', [TiendaController::class, 'show'])->name('tienda.producto');

//Carrito
Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito.index');
Route::post('/carrito/agregar', [CarritoController::class, 'agregar'])->name('carrito.agregar');
Route::delete('/carrito/eliminar/{id}', [CarritoController::class, 'eliminar'])->name('carrito.eliminar');
Route::put('/carrito/actualizar/{id}', [CarritoController::class, 'actualizar'])->name('carrito.actualizar');

// ==========================================
// RUTAS ADMINISTRATIVAS (Backoffice)
// ==========================================
Route::prefix('admin')->name('admin.')->group(function () {

    // Inicio del Panel (Dashboard)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Ruta específica para el eliminado lógico / reactivación
    Route::patch('categorias/{categoria}/toggle', [CategoriaController::class, 'toggle'])->name('categorias.toggle');
    // Módulo de Categorías (Completo)
    Route::resource('categorias', CategoriaController::class);

    // Ruta específica para activar/desactivar productos
    Route::patch('productos/{producto}/toggle', [ProductController::class, 'toggle'])->name('productos.toggle');
    // Módulo de Productos (Completo)
    Route::resource('productos', ProductController::class);


});