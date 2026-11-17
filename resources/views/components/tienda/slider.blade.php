@props(['slides' => []])

@if ($slides->count() > 0)
    <section class="hero-slider">
        <div class="slider-wrapper">

            @foreach ($slides as $index => $slide)
                <div class="slide {{ $index === 0 ? 'slide-active' : '' }}">

                    {{-- 1. Capa de oscurecimiento (solo si hay texto) --}}
                    @if ($slide->titulo || $slide->subtitulo)
                        <div class="slide-overlay"></div>
                    @endif

                    {{-- 2. Imagen Única (Enlace envuelve a la imagen) --}}
                    <a href="{{ $slide->url_destino ?? $slide->link }}" class="slide-image-link">
                        <img src="{{ $slide->imagen_url }}" alt="{{ $slide->titulo ?? 'Banner' }}" class="slide-img">
                    </a>

                    {{-- 3. Contenido (Texto) --}}
                    @if ($slide->titulo || $slide->subtitulo || $slide->url_destino || $slide->link)
                        <div class="slide-content">
                            @if ($slide->titulo)
                                <h2 class="slide-title">{{ $slide->titulo }}</h2>
                            @endif

                            @if ($slide->subtitulo)
                                <p class="slide-subtitle">{{ $slide->subtitulo }}</p>
                            @endif

                            @if (($slide->url_destino || $slide->link) && ($slide->titulo || $slide->subtitulo))
                                <a href="{{ $slide->url_destino ?? $slide->link }}" class="slide-btn">
                                    Descubrir <span class="slide-btn-arrow">&#10140;</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach

            <button class="slider-btn-nav slider-btn-prev" id="btnPrevSlide">&#10094;</button>
            <button class="slider-btn-nav slider-btn-next" id="btnNextSlide">&#10095;</button>
        </div>

        <div class="slider-dots-container">
            @foreach ($slides as $index => $slide)
                <span class="slider-dot {{ $index === 0 ? 'dot-active' : '' }}" data-slide="{{ $index + 1 }}"></span>
            @endforeach
        </div>
    </section>
@endif
