<x-layouts.app>
    <x-slot:title>Catálogo | DEVIOZ</x-slot:title>

    <x-slot:css>
        <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    </x-slot:css>

    @if (isset($slides) && $slides->count() > 0)
        <x-tienda.slider :slides="$slides" />
    @endif

    <main class="store-container">

        <x-tienda.categorias-horizontales :categorias="$categoriasPadre" />

        <header class="store-header {{ isset($promocion) ? 'store-header--promocion' : '' }}">
            <h1 class="store-header__title">
                @if (isset($promocion))
                    <i class="fas fa-tag"></i> {{ $promocion->titulo }}
                @else
                    Catálogo de Productos
                @endif
            </h1>

            <p class="store-header__subtitle">
                @if (isset($promocion))
                    {{ $promocion->subtitulo }}
                @else
                    Encuentra la mejor tecnología y accesorios.
                @endif
            </p>
        </header>

        <div class="store-layout">

            <x-tienda.sidebar-filtros :marcas="$marcas" />

            <div class="store-main">

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
                                <a href="{{ route('tienda.inicio') }}" class="btn btn--primary"
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
        <script src="{{ asset('js/tienda/slider.js') }}"></script>
        <script src="{{ asset('js/tienda/catalogo.js') }}"></script>
    </x-slot:js>
</x-layouts.app>
