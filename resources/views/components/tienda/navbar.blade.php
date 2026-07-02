<header class="main-header">
    <div class="header-container">
        
        <div class="header-logo">
            <a href="{{ route('tienda.inicio') }}">
                <img src="{{ asset('img/logo-devioz.webp') }}" alt="DEVIOZ Logo" class="logo-img">
            </a>
        </div>

        <div class="header-search-bar">
            <form action="{{ route('tienda.inicio') }}" method="GET" id="formHeaderSearch">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon-left"></i>
                    <input type="text" name="buscar" id="inputHeaderSearch" 
                           placeholder="Buscar por nombre, marca o modelo..." 
                           value="{{ request('buscar') }}">
                    @if(request('buscar'))
                        <a href="{{ route('tienda.inicio') }}" class="clear-search-btn">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <nav class="header-actions">
            <a href="{{ route('carrito.index') }}" class="header-action-btn cart-icon-wrapper" title="Ver Carrito">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-badge-count" id="headerCartCount">{{ $cantidadCarrito }}</span>
            </a>
        </nav>

    </div>
</header>