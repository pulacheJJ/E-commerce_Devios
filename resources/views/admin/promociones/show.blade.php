<x-layouts.admin>
    <x-slot name="title">Detalles Promoción | DEVIOZ</x-slot>

    <div class="admin-header">
        <h2>Detalles: {{ $promocione->titulo }}</h2>
        <div class="header-actions">
            @can('promociones.editar')
                <a href="{{ route('admin.promociones.edit', $promocione->id) }}" class="btn-warning"><i class="fas fa-edit"></i> Editar</a>
            @endcan
            <a href="{{ route('admin.promociones.index') }}" class="btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <div class="form-layout-split">
        <div class="main-column">
            <div class="panel-card">
                <h3 class="panel-title">Información General</h3>
                <div class="info-grid">
                    <p><strong>Subtítulo:</strong> <br> {{ $promocione->subtitulo ?? 'N/A' }}</p>
                    <p><strong>Descuento:</strong> <br> <span class="badge badge-info">{{ $promocione->tipo_descuento === 'porcentaje' ? $promocione->valor_descuento . '%' : 'S/ ' . $promocione->valor_descuento }}</span></p>
                    <p><strong>Inicio:</strong> <br> <span class="text-success">{{ $promocione->fecha_inicio->format('d/m/Y h:i A') }}</span></p>
                    <p><strong>Fin:</strong> <br> <span class="text-danger">{{ $promocione->fecha_fin->format('d/m/Y h:i A') }}</span></p>
                    <p><strong>Estado:</strong> <br> {!! $promocione->esta_activa ? '<span class="text-success"><i class="fas fa-check-circle"></i> Activa</span>' : '<span class="text-danger"><i class="fas fa-times-circle"></i> Inactiva</span>' !!}</p>
                    <p><strong>Destacada:</strong> <br> {{ $promocione->destacada_en_carrusel ? 'Sí' : 'No' }}</p>
                </div>
            </div>

            <div class="panel-card mt-4">
                <h3 class="panel-title">Alcance Afectado</h3>
                <div class="relations-grid">
                    <div>
                        <h4 class="section-title">Categorías ({{ $promocione->categorias->count() }})</h4>
                        <ul class="clean-list">
                            @forelse($promocione->categorias as $cat)
                                <li><i class="fas fa-tag list-icon"></i> {{ $cat->nombre }}</li>
                            @empty
                                <li class="text-muted">No aplica a categorías.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <h4 class="section-title">Productos ({{ $promocione->productos->count() }})</h4>
                        <ul class="clean-list">
                            @forelse($promocione->productos as $prod)
                                <li><i class="fas fa-box list-icon"></i> {{ $prod->nombre }}</li>
                            @empty
                                <li class="text-muted">No aplica a productos individuales.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="side-column">
            <div class="panel-card">
                <h3 class="panel-title">Banner Publicitario</h3>
                @if($promocione->ruta_banner)
                    <img src="{{ $promocione->imagen_url }}" alt="Banner" class="show-banner-img">
                @else
                    <div class="no-image-placeholder">Sin imagen</div>
                @endif
                
                @if($promocione->url_destino)
                    <div class="mt-3 text-center">
                        <a href="{{ $promocione->url_destino }}" target="_blank" class="text-teal text-decoration-none">
                            <i class="fas fa-external-link-alt"></i> Probar URL de destino
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
