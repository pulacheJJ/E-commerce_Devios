<x-layouts.admin>
    <div class="slider-admin-container">
        <h1 class="slider-admin-title">Gestión de Banners (Slider)</h1>

        @if (session('success'))
            <div class="slider-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                style="background-color: #7f1d1d; color: #fca5a5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong style="display:block; margin-bottom: 10px;">¡Ups! Hubo un problema:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="slider-admin-grid">
            @include('admin.sliders.partials.create-form')

            @include('admin.sliders.partials.table')
        </div>
    </div>
</x-layouts.admin>
