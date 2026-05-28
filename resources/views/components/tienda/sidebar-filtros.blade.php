@props(['categorias'])

<aside class="store-sidebar">
    <div class="filter-group">
        <h3 class="filter-title">Categorías</h3>
        <ul class="filter-list">
            <li>
                <a href="{{ request()->fullUrlWithQuery(['categoria' => null]) }}"
                    class="filter-link {{ !request('categoria') ? 'active' : '' }}">
                    Todas
                </a>
            </li>
            @foreach ($categorias as $cat)
                <li>
                    <a href="{{ request()->fullUrlWithQuery(['categoria' => $cat->slug]) }}"
                        class="filter-link {{ request('categoria') == $cat->slug ? 'active' : '' }}">
                        {{ $cat->nombre }}
                        <span class="filter-count">({{ $cat->productos_count }})</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

</aside>
