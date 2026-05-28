@props(['productos'])

@if($productos->count() > 0)
    <section class="related-products-section">
        <h2 class="related-title">Quienes vieron este producto también compraron</h2>
        
        <div class="products-grid">
            @foreach($productos as $relacionado)
                <x-tarjeta-producto :producto="$relacionado" />
            @endforeach
        </div>
    </section>
@endif