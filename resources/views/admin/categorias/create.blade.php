<x-layouts.admin>
    <x-slot:title>Crear Nueva Categoría | Panel Admin</x-slot:title>

    <div class="admin-container">
        <header class="admin-header">
            <h1 class="admin-header__title">Crear Categoría</h1>
            <a href="{{ route('admin.categorias.index') }}" class="btn btn--outline">Volver al listado</a>
        </header>

        <section class="admin-card">
            <form action="{{ route('admin.categorias.store') }}" method="POST" class="form-grid">
                @csrf

                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre de la Categoría *</label>
                    <input type="text" id="nombre" name="nombre" class="form-input @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required placeholder="Ej: Tecnología, Mouses, Teclados...">
                    @error('nombre') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="slug" class="form-label">Slug (URL amigable) *</label>
                    <input type="text" id="slug" name="slug" class="form-input @error('slug') is-invalid @enderror" value="{{ old('slug') }}" required readonly placeholder="Se genera automáticamente...">
                    @error('slug') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="parent_id" class="form-label">Categoría Padre (Opcional)</label>
                    <select id="parent_id" name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                        <option value="">Ninguna (Será una categoría principal)</option>
                        @foreach($categorias_principales as $categoria)
                            <option value="{{ $categoria->id }}" {{ old('parent_id') == $categoria->id ? 'selected' : '' }}>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group form-group--checkbox">
                    <label class="form-checkbox-label">
                        <input type="checkbox" name="esta_activa" value="1" class="form-checkbox" {{ old('esta_activa', true) ? 'checked' : '' }}>
                        <span>Categoría Activa (Visible en la tienda)</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Guardar Categoría</button>
                </div>
            </form>
        </section>
    </div>

    <x-slot:js>
        <script src="{{ asset('js/admin/categorias/create.js') }}"></script>
    </x-slot:js>
</x-layouts.admin>