<x-layouts.app>
    <x-slot:title>Catálogo | DEVIOZ</x-slot:title>

    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    </x-slot:css>

    <main class="store-container">
        <header class="store-header">
            <h1 class="store-header__title">Catálogo de Productos</h1>
            <p class="store-header__subtitle">Encuentra la mejor tecnología y accesorios.</p>
        </header>

        <div class="store-layout">

            <x-tienda.sidebar-filtros :categorias="$categorias" />

            <div class="store-main">
                <section class="search-section">
                    <form action="{{ route('tienda.catalogo') }}" method="GET" class="search-form" id="formBuscador">
                        @if (request('categoria'))
                            <input type="hidden" name="categoria" value="{{ request('categoria') }}">
                        @endif

                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="buscar" id="inputBuscar" class="search-input"
                                placeholder="Buscar por nombre, marca o modelo..." value="{{ request('buscar') }}">

                            @if (request('buscar'))
                                <button type="button" class="btn-clear-search" id="btnLimpiarBuscar"
                                    title="Limpiar búsqueda">&times;</button>
                            @endif
                        </div>
                        <button type="submit" class="btn btn--primary">Buscar</button>
                    </form>
                </section>

                <section class="products-grid">
                    @forelse($productos as $producto)
                        <x-tarjeta-producto :producto="$producto" />
                    @empty
                        <div class="products-empty">
                            <i class="fas fa-box-open"
                                style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                            <h3>No encontramos productos</h3>
                            <p>Intenta quitando algunos filtros o buscando otra palabra.</p>
                            @if (request()->anyFilled(['buscar', 'categoria']))
                                <a href="{{ route('tienda.catalogo') }}" class="btn btn--primary"
                                    style="margin-top: 15px; display: inline-block;">Limpiar filtros</a>
                            @endif
                        </div>
                    @endforelse
                </section>

                <div class="store-pagination">
                    {{ $productos->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </main>

    <x-slot:js>
        <script src="{{ asset('js/tienda/catalogo.js') }}"></script>
    </x-slot:js>
</x-layouts.app>
