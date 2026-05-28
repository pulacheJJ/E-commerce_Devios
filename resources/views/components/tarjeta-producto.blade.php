@props(['producto'])

@php
    // Lógica para imagen y precio delegada al componente
    $imagenPrincipal = $producto->imagenes->where('es_principal', true)->first() ?? $producto->imagenes->first();

    $precioMinimo = $producto->variantes->min(function ($v) {
        return $v->precio_oferta ? $v->precio_oferta : $v->precio;
    });
@endphp

<article class="product-card">
    <a href="{{ route('tienda.producto', $producto->slug) }}" class="product-card__image-wrapper">
        @if ($imagenPrincipal)
            <img src="{{ asset('storage/' . $imagenPrincipal->ruta_imagen) }}" alt="{{ $producto->nombre }}"
                class="product-card__image">
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
                <span class="product-card__price">Desde S/ {{ number_format($precioMinimo, 2) }}</span>
            @else
                <span class="product-card__out-of-stock">Agotado temporalmente</span>
            @endif
        </div>

        <a href="{{ route('tienda.producto', $producto->slug) }}" class="btn btn--primary product-card__btn">Ver
            Opciones</a>
    </div>
</article>
