<?php

namespace App\Services;

use App\Models\Categoria;
use Illuminate\Support\Str;

class CategoriaService
{
    public function obtenerTodas()
    {
        return Categoria::with('padre')->orderBy('nombre', 'asc')->get();
    }

    public function obtenerPrincipales()
    {
        return Categoria::whereNull('parent_id')->get();
    }

    public function crear(array $datos): Categoria
    {
        $datos['slug'] = Str::slug($datos['nombre']);
        $datos['esta_activa'] = isset($datos['esta_activa']);

        return Categoria::create($datos);
    }

    public function actualizar(Categoria $categoria, array $datos): bool
    {
        $datos['slug'] = Str::slug($datos['nombre']);
        $datos['esta_activa'] = isset($datos['esta_activa']);

        return $categoria->update($datos);
    }

    public function toggleEstado(Categoria $categoria): bool
    {
        // 1. Determinamos cuál será el nuevo estado
        $nuevoEstado = !$categoria->esta_activa;

        // 2. REGLA DE NEGOCIO: Proteger la activación de hijos huérfanos
        // Si intentamos ACTIVAR (true) y la categoría TIENE PADRE...
        if ($nuevoEstado === true && $categoria->parent_id !== null) {
            // Verificamos si el padre está desactivado
            if (!$categoria->padre->esta_activa) {
                // Lanzamos una excepción que el Controlador capturará y mostrará como alerta roja
                throw new \Exception("Operación denegada: No puedes activar esta subcategoría porque su categoría padre ('{$categoria->padre->nombre}') está desactivada. Activa primero al padre.");
            }
        }

        // 3. Aplicamos el cambio a la categoría actual
        $categoria->esta_activa = $nuevoEstado;
        $guardado = $categoria->save();

        // 4. REGLA DE NEGOCIO: Apagado en cascada
        // Si se desactiva un Padre, desactivamos a sus hijos
        if ($nuevoEstado === false) {
            // A. Desactivar todos los productos directos de ESTA categoría
            $categoria->productos()->update(['esta_activo' => false]);

            // B. Si es una categoría padre, también desactivar sus hijas y los productos de las hijas
            if ($categoria->hijos()->count() > 0) {
                // Primero apagamos los productos de las subcategorías
                foreach ($categoria->hijos as $hijo) {
                    $hijo->productos()->update(['esta_activo' => false]);
                }
                // Luego apagamos las subcategorías en sí
                $categoria->hijos()->update(['esta_activa' => false]);
            }
        }

        return $guardado;
    }
}