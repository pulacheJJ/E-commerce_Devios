@props(['marcas'])

<aside class="store-sidebar">
    <h3 class="store-sidebar-title">Filtrar por</h3>

    <form action="{{ route('tienda.catalogo') }}" method="GET" id="storeFiltersForm">
        @if (request('categoria'))
            <input type="hidden" name="categoria" value="{{ request('categoria') }}">
        @endif
        @if (request('buscar'))
            <input type="hidden" name="buscar" value="{{ request('buscar') }}">
        @endif

        @if (request('promocion_id'))
            <input type="hidden" name="promocion_id" value="{{ request('promocion_id') }}">
        @endif
        <div class="store-filter-group">
            <h4 class="store-filter-subtitle">Precio</h4>
            <div class="store-filter-options">
                @php $precioActual = request('precio'); @endphp
                <label class="store-checkbox-label">
                    <input type="radio" name="precio" value="0-100" class="store-input-auto"
                        {{ $precioActual == '0-100' ? 'checked' : '' }}> Menos de S/ 100
                </label>
                <label class="store-checkbox-label">
                    <input type="radio" name="precio" value="100-500" class="store-input-auto"
                        {{ $precioActual == '100-500' ? 'checked' : '' }}> S/ 100 - S/ 500
                </label>
                <label class="store-checkbox-label">
                    <input type="radio" name="precio" value="500-1000" class="store-input-auto"
                        {{ $precioActual == '500-1000' ? 'checked' : '' }}> S/ 500 - S/ 1000
                </label>
                <label class="store-checkbox-label">
                    <input type="radio" name="precio" value="1000+" class="store-input-auto"
                        {{ $precioActual == '1000+' ? 'checked' : '' }}> Más de S/ 1000
                </label>
            </div>
        </div>

        @if (isset($marcas) && count($marcas) > 0)
            <div class="store-filter-group">
                <h4 class="store-filter-subtitle">Marcas</h4>
                <div class="store-filter-options">
                    @php $marcasSeleccionadas = request('marcas', []); @endphp
                    @foreach ($marcas as $marca)
                        <label class="store-checkbox-label">
                            <input type="checkbox" name="marcas[]" value="{{ $marca }}" class="store-input-auto"
                                {{ in_array($marca, $marcasSeleccionadas) ? 'checked' : '' }}>
                            {{ $marca }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <noscript>
            <button type="submit" class="store-btn-apply">Aplicar Filtros</button>
        </noscript>
    </form>
</aside>
