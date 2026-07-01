<div class="store-horizontal-filters">
    <div class="store-filters-track">
        
        {{-- Botón TODOS: Limpia la categoría pero mantiene la promoción --}}
        <a href="{{ request()->fullUrlWithQuery(['categoria' => null]) }}" 
           class="store-filter-pill {{ !request('categoria') ? 'is-active' : '' }}">
            Todos
        </a>

        @foreach($categorias as $cat)
            @php $isActive = request('categoria') == $cat->slug; @endphp
            {{-- fullUrlWithQuery mantiene automáticamente la promoción si existe en la URL --}}
            <a href="{{ request()->fullUrlWithQuery(['categoria' => $cat->slug]) }}" 
               class="store-filter-pill {{ $isActive ? 'is-active' : '' }}">
                {{ $cat->nombre }} <span class="store-filter-count">({{ $cat->productos_count }})</span>
            </a>
        @endforeach
    </div>
</div>