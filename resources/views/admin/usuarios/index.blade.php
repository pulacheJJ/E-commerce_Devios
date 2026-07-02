<x-layouts.admin>
    <x-slot:title>Gestión de Usuarios | DEVIOZ</x-slot:title>

    <div class="admin-header-module">
        <h2>Gestión de Usuarios</h2>
        <a href="{{ route('admin.usuarios.create') }}" class="btn-primary-action">
            <i class="fas fa-plus"></i> Nuevo Usuario
        </a>
    </div>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo Electrónico</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($usuarios as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge badge-role">{{ $user->roles->first()->name ?? 'Sin Rol' }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $user->trashed() ? 'badge-danger' : 'badge-success' }}">
                            {{ $user->trashed() ? 'Inactivo' : 'Activo' }}
                        </span>
                    </td>
                    <td class="actions-cell">
                        <a href="{{ route('admin.usuarios.show', $user->id) }}" class="btn-icon text-info" title="Ver detalles"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('admin.usuarios.edit', $user->id) }}" class="btn-icon text-warning" title="Editar"><i class="fas fa-pen"></i></a>
                        @if($user->id !== auth()->id())
                        <button type="button" class="btn-icon toggle-status-btn {{ $user->trashed() ? 'text-success' : 'text-danger' }}" data-id="{{ $user->id }}" title="{{ $user->trashed() ? 'Restaurar' : 'Desactivar' }}">
                            <i class="fas {{ $user->trashed() ? 'fa-check-circle' : 'fa-ban' }}"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $usuarios->links() }}
    </div>

    <x-slot:js>
        <script src="{{ asset('js/admin/usuarios/index.js') }}"></script>
    </x-slot:js>
</x-layouts.admin>