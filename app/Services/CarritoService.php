<?php

namespace App\Services;

use App\Models\Carrito;
use App\Models\ElementoCarrito;
use App\Models\VarianteProducto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CarritoService
{
    /* =========================================================
       MÉTODOS PÚBLICOS (Orquestadores de Casos de Uso)
       ========================================================= */

    /**
     * Agrega un item al carrito desde la vista del producto.
     */
    public function agregarItem(int $varianteId, int $cantidad): array
    {
        return DB::transaction(function () use ($varianteId, $cantidad): array {
            $carrito = $this->obtenerOCrearCarrito();
            Carrito::whereKey($carrito->id)->lockForUpdate()->firstOrFail();
            $elemento = $this->obtenerElemento($carrito->id, $varianteId);

            $nuevaCantidad = ($elemento?->cantidad ?? 0) + $cantidad;

            // Delegamos la validación a la regla de negocio
            $validacion = $this->validarLimites($varianteId, $nuevaCantidad);
            if (! $validacion['valido']) {
                return ['success' => false, 'message' => $validacion['mensaje']];
            }

            $this->guardarElemento($elemento, $carrito->id, $varianteId, $nuevaCantidad);

            return ['success' => true, 'total_items' => $carrito->elementos()->count()];
        }, 3);
    }

    /**
     * Actualiza la cantidad exacta desde los botones (+) y (-) del carrito.
     */
    public function actualizarCantidad(int $elementoId, int $nuevaCantidad): array
    {
        $elemento = $this->obtenerElementoPropio($elementoId);
        if (! $elemento) {
            return ['success' => false, 'message' => 'Elemento no encontrado.'];
        }

        // Reutilizamos la misma regla de negocio para consistencia
        $validacion = $this->validarLimites($elemento->variante_id, $nuevaCantidad);
        if (! $validacion['valido']) {
            return ['success' => false, 'message' => $validacion['mensaje']];
        }

        $elemento->update(['cantidad' => $nuevaCantidad]);

        return ['success' => true];
    }

    /**
     * Elimina un item. Si el carrito queda vacío, se auto-elimina de la BD.
     */
    public function eliminarItem(int $elementoId): bool
    {
        $elemento = $this->obtenerElementoPropio($elementoId);

        if (! $elemento) {
            return false;
        }

        $carrito = $elemento->carrito;
        $elemento->delete();
        $this->limpiarSesionSiEstaVacio($carrito);

        return true;
    }

    /**
     * Obtiene el carrito actual con todos sus datos anidados para la vista.
     */
    public function obtenerCarritoActual()
    {
        $token = session()->get('carrito_token');
        if (! $token) {
            return null;
        }

        return Carrito::with(['elementos.variante.producto.imagenes'])
            ->where('token_sesion', $token)
            ->first();
    }

    public function obtenerHashPropietarioActual(bool $crear = false): ?string
    {
        if (! session()->has('checkout_owner_token') && $crear) {
            session()->put('checkout_owner_token', Str::random(64));
        }

        $token = session()->get('checkout_owner_token');

        return $token ? hash('sha256', $token) : null;
    }

    /* =========================================================
       MÉTODOS PRIVADOS (Reglas de Negocio Aisladas - SRP)
       ========================================================= */

    private function obtenerOCrearCarrito(): Carrito
    {
        $this->obtenerHashPropietarioActual(true);

        if (! session()->has('carrito_token')) {
            session()->put('carrito_token', Str::uuid()->toString());
            session()->forget('last_checkout_order_id');
        }

        $carrito = Carrito::firstOrCreate(
            ['token_sesion' => session()->get('carrito_token')],
            ['expires_at' => now()->addDays(config('pedidos.cart_expiration_days', 30))]
        );

        if (! $carrito->pedido()->exists()) {
            $carrito->update(['expires_at' => now()->addDays(config('pedidos.cart_expiration_days', 30))]);
        }

        return $carrito;
    }

    private function obtenerElementoPropio(int $elementoId): ?ElementoCarrito
    {
        $token = session()->get('carrito_token');

        if (! $token) {
            return null;
        }

        return ElementoCarrito::whereKey($elementoId)
            ->whereHas('carrito', fn ($query) => $query->where('token_sesion', $token))
            ->first();
    }

    private function obtenerElemento(int $carritoId, int $varianteId): ?ElementoCarrito
    {
        return ElementoCarrito::where('carrito_id', $carritoId)
            ->where('variante_id', $varianteId)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Regla central: La cantidad no puede superar el stock ni el tope máximo de la tienda.
     */
    private function validarLimites(int $varianteId, int $nuevaCantidad): array
    {
        if ($nuevaCantidad < 1) {
            return ['valido' => false, 'mensaje' => 'La cantidad mínima es 1.'];
        }

        $variante = VarianteProducto::with('producto')->find($varianteId);
        if (! $variante || ! $variante->producto || ! $variante->producto->esta_activo) {
            return ['valido' => false, 'mensaje' => 'Producto no disponible.'];
        }

        // Límite: El stock real disponible, pero nunca más de 10 por compra
        $limiteMaximo = min($variante->stock, 10);

        if ($nuevaCantidad > $limiteMaximo) {
            return [
                'valido' => false,
                'mensaje' => "El límite es de $limiteMaximo unidades para este producto.",
            ];
        }

        return ['valido' => true, 'mensaje' => 'OK'];
    }

    private function guardarElemento(?ElementoCarrito $elemento, int $carritoId, int $varianteId, int $nuevaCantidad): void
    {
        if ($elemento) {
            $elemento->update(['cantidad' => $nuevaCantidad]);
        } else {
            ElementoCarrito::create([
                'carrito_id' => $carritoId,
                'variante_id' => $varianteId,
                'cantidad' => $nuevaCantidad,
            ]);
        }
    }

    private function limpiarSesionSiEstaVacio(Carrito $carrito): void
    {
        if ($carrito->elementos()->count() === 0 && ! $carrito->pedido()->exists()) {
            $carrito->delete();
            session()->forget('carrito_token');
        }
    }
}
