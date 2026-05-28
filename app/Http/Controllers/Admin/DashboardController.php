<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;

class DashboardController extends Controller
{
    public function index()
    {
        $total_categorias = Categoria::count();
        $total_productos = Producto::count();

        return view('admin.dashboard', compact('total_categorias', 'total_productos'));
    }
}