<x-layouts.admin>
    <x-slot:title>Nuevo Usuario | DEVIOZ</x-slot:title>

    <div class="admin-header-module">
        <h2>Crear Nuevo Usuario</h2>
        <a href="{{ route('admin.usuarios.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a la lista
        </a>
    </div>

    <div class="admin-card form-card">
        @if ($errors->any())
            <div class="alert alert--danger">Hay campos por corregir antes de guardar el usuario.</div>
        @endif
        <form action="{{ route('admin.usuarios.store') }}" method="POST">
            @csrf

            <div class="form-grid">
                <!-- Nombre -->
                <div class="form-group">
                    <label for="name">Nombre Completo</label>
                    <div class="input-container">
                        <i class="fas fa-user icon-left"></i>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Ej. Juan Pérez">
                    </div>
                    @error('name') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <!-- Correo -->
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <div class="input-container">
                        <i class="fas fa-envelope icon-left"></i>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="usuario@devioz.com">
                    </div>
                    @error('email') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <!-- Contraseña -->
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-container">
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                    </div>
                    @error('password') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <!-- Confirmar Contraseña -->
                <div class="form-group">
                    <label for="password_confirmation">Confirmar Contraseña</label>
                    <div class="input-container">
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="••••••••">
                    </div>
                </div>

                <!-- Rol -->
                <div class="form-group full-width">
                    <label for="role">Rol del Sistema</label>
                    <div class="input-container">
                        <i class="fas fa-user-shield icon-left"></i>
                        <select name="role" id="role" required class="form-select">
                            <option value="">Selecciona un rol...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                    {{ $role->name === 'admin' ? 'Administrador' : 'Gestor' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('role') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                @include('admin.usuarios.partials.permissions', ['selectedPermissions' => []])
            </div>

            <div class="form-actions-footer">
                <button type="submit" class="btn-primary-action">
                    <i class="fas fa-save"></i> Guardar Usuario
                </button>
            </div>
        </form>
    </div>

    <x-slot:js>
        <script src="{{ asset('js/admin/usuarios/form.js') }}"></script>
    </x-slot:js>
</x-layouts.admin>
