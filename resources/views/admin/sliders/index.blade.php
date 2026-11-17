<x-layouts.admin>
    <div class="slider-admin-container">
        <h1 class="slider-admin-title">Gestión de Banners (Slider)</h1>

        @if (session('success'))
            <div class="slider-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="slider-alert-error">
                <strong class="slider-alert-error__title">¡Ups! Hubo un problema:</strong>
                <ul class="slider-alert-error__list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="slider-admin-grid">
            @can('sliders.crear')
                @include('admin.sliders.partials.create-form')
            @endcan

            @include('admin.sliders.partials.table')
        </div>
    </div>
</x-layouts.admin>
