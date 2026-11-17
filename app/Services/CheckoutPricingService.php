<?php

namespace App\Services;

use App\Models\VarianteProducto;
use App\Support\Money;

class CheckoutPricingService
{
    public function snapshot(VarianteProducto $variant): array
    {
        $baseCents = Money::cents($variant->precio);
        $offerBaseCents = $variant->precio_oferta > 0
            ? min($baseCents, Money::cents($variant->precio_oferta))
            : $baseCents;
        $promotionDiscountCents = 0;
        $promotion = $variant->producto?->promocion_activa;

        if ($promotion) {
            $promotionDiscountCents = $promotion->tipo_descuento === 'porcentaje'
                ? Money::percentageDiscount($offerBaseCents, $promotion->valor_descuento)
                : Money::cents($promotion->valor_descuento);
        }

        $promotionDiscountCents = min(max(0, $promotionDiscountCents), $offerBaseCents);
        $unitCents = $offerBaseCents - $promotionDiscountCents;
        $discountCents = $baseCents - $unitCents;

        return [
            'base_cents' => $baseCents,
            'discount_cents' => $discountCents,
            'unit_cents' => $unitCents,
        ];
    }
}
