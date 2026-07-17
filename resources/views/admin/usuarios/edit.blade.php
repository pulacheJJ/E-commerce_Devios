<x-layouts.admin>
    <x-slot:title>Editar Usuario | DEVIOZ</x-slot:title>

    <div class="admin-header-module">
        <h2>Editar Usuario: <span class="text-teal">{{ $usuario->name }}</span></h2>
        <a href="{{ route('admin.usuarios.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="admin-card form-card">
        @if ($errors->any())
            <div class="alert alert--danger">Hay campos por corregir antes de actualizar el usuario.</div>
        @endif
        <form action="{{ route('admin.usuarios.update', $usuario->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Nombre Completo</label>
                    <div class="input-container">
                        <i class="fas fa-user icon-left"></i>
                        <input type="text" id="name" name="name" value="{{ old('name', $usuario->name) }}" required>
                    </div>
                    @error('name') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <div class="input-container">
                        <i class="fas fa-envelope icon-left"></i>
                        <input type="email" id="email" name="email" value="{{ old('email', $usuario->email) }}" required>
                    </div>
                    @error('email') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <!-- Nota sobre contraseñas en edición -->
                <div class="form-group full-width alert-info">
                    <i class="fas fa-info-circle"></i> Deja los campos de contraseña en blanco si no deseas cambiarla.
                </div>

                <div class="form-group">
                    <label for="password">Nueva Contraseña (Opcional)</label>
                    <div class="input-container">
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" id="password" name="password" placeholder="••••••••">
                    </div>
                    @error('password') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirmar Nueva Contraseña</label>
                    <div class="input-container">
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="••••••••">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="role">Rol del Sistema</label>
                    <div class="input-container">
                        <i class="fas fa-user-shield icon-left"></i>
                        <select name="role" id="role" required class="form-select">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" 
                                    {{ (old('role') ?? ($usuario->roles->first()->name ?? '')) == $role->name ? 'selected' : '' }}>
                                    {{ $role->name === 'admin' ? 'Administrador' : 'Gestor' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('role') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                @include('admin.usuarios.partials.permissions', ['selectedPermissions' => $selectedPermissions])
            </div>

            <div class="form-actions-footer">
                <button type="submit" class="btn-primary-action">
                    <i class="fas fa-sync-alt"></i> Actualizar Usuario
                </button>
            </div>
        </form>
    </div>

    <x-slot:js>
        <script src="{{ asset('js/admin/usuarios/form.js') }}"></script>
    </x-slot:js>
</x-layouts.admin>
