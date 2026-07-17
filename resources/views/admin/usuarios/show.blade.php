<x-layouts.admin>
    <x-slot:title>Detalles del Usuario | DEVIOZ</x-slot:title>

    <div class="admin-header-module">
        <h2>Perfil del Usuario</h2>
        <div class="header-actions">
            @can('usuarios.editar')
            <a href="{{ route('admin.usuarios.edit', $usuario->id) }}" class="btn-primary-action">
                <i class="fas fa-pen"></i> Editar
            </a>
            @endcan
            <a href="{{ route('admin.usuarios.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="profile-container">
        <div class="admin-card profile-card">
            <div class="profile-header">
                <div class="avatar-placeholder">
                    <span>{{ substr($usuario->name, 0, 2) }}</span>
                </div>
                <div class="profile-title">
                    <h3>{{ $usuario->name }}</h3>
                    <span class="badge badge-role">{{ ucfirst($usuario->roles->first()->name ?? 'Sin Rol') }}</span>
                </div>
            </div>
            
            <div class="profile-body">
                <div class="info-group">
                    <span class="info-label">Estado de la Cuenta</span>
                    <span class="badge {{ $usuario->trashed() ? 'badge-danger' : 'badge-success' }}">
                        {{ $usuario->trashed() ? 'Inactivo (Desactivado)' : 'Activo' }}
                    </span>
                </div>

                <div class="info-group">
                    <span class="info-label">Correo Electrónico</span>
                    <span class="info-value"><i class="fas fa-envelope text-muted"></i> {{ $usuario->email }}</span>
                </div>

                <div class="info-group">
                    <span class="info-label">Fecha de Creación</span>
                    <span class="info-value"><i class="fas fa-calendar-alt text-muted"></i> {{ $usuario->created_at->format('d/m/Y H:i') }}</span>
                </div>

                <div class="info-group">
                    <span class="info-label">Última Actualización</span>
                    <span class="info-value"><i class="fas fa-clock text-muted"></i> {{ $usuario->updated_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
