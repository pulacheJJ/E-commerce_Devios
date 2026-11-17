@props(['producto'])

@php
    // Solo extraemos la promoción activa para usarla más fácil en la vista
    $promo = $producto->promocion_activa;
@endphp

<article class="product-card">
    <a href="{{ route('tienda.producto', $producto->slug) }}" class="product-card__image-wrapper">
        
        @if ($promo)
            <span class="product-card__promo-badge">
                @if ($promo->tipo_descuento === 'porcentaje')
                    -{{ number_format($promo->valor_descuento, 0) }}% OFF
                @else
                    - S/ {{ number_format($promo->valor_descuento, 2) }}
                @endif
            </span>
        @endif

        @if ($producto->imagen_principal)
            <img src="{{ $producto->imagen_principal_url }}" alt="{{ $producto->nombre }}" class="product-card__image">
        @else
            <div class="product-card__no-image">Sin imagen</div>
        @endif
    </a>

    <div class="product-card__info">
        <span class="product-card__category">{{ $producto->categoria->nombre ?? 'General' }}</span>

        <h3 class="product-card__title">
            <a href="{{ route('tienda.producto', $producto->slug) }}">{{ $producto->nombre }}</a>
        </h3>

        <div class="product-card__price-box">
            @if ($producto->variantes->count() > 0)
                
                @if ($promo)
                    <div class="product-card__prices">
                        <del class="product-card__price-old">S/ {{ number_format($producto->precio_original, 2) }}</del>
                        <span class="product-card__price product-card__price--discount">S/ {{ number_format($producto->precio_final, 2) }}</span>
                    </div>
                @else
                    <span class="product-card__price">Desde S/ {{ number_format($producto->precio_original, 2) }}</span>
                @endif

            @else
                <span class="product-card__out-of-stock">Agotado temporalmente</span>
            @endif
        </div>

        <a href="{{ route('tienda.producto', $producto->slug) }}" class="btn btn--primary product-card__btn">Ver Opciones</a>
    </div>
</article>
