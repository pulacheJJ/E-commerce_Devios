<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeAdminResourceStatusRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $usuarios = $this->userService->getAllWithRoles();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = Role::whereIn('name', ['admin', 'gestor'])->orderBy('name')->get();
        $permissionGroups = $this->permissionGroups();

        return view('admin.usuarios.create', compact('roles', 'permissionGroups'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->createUser($request->validated());

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function show($id)
    {
        $usuario = User::withTrashed()->with('roles')->findOrFail($id);

        return view('admin.usuarios.show', compact('usuario'));
    }

    public function edit($id)
    {
        $usuario = User::withTrashed()->with('roles', 'permissions')->findOrFail($id);
        $roles = Role::whereIn('name', ['admin', 'gestor'])->orderBy('name')->get();
        $permissionGroups = $this->permissionGroups();
        $selectedPermissions = $usuario->permissions->pluck('name')->all();

        return view('admin.usuarios.edit', compact('usuario', 'roles', 'permissionGroups', 'selectedPermissions'));
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $usuario = User::withTrashed()->findOrFail($id);
        $this->userService->updateUser($usuario, $request->validated());

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function toggle(ChangeAdminResourceStatusRequest $request, $id)
    {
        try {
            $usuario = User::withTrashed()->findOrFail($id);
            $this->authorize('toggle', $usuario);
            $status = $this->userService->toggleStatus($id);

            return response()->json(['success' => true, 'message' => "Usuario $status exitosamente.", 'status' => $status]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    private function permissionGroups(): array
    {
        $moduleLabels = [
            'usuarios' => 'Usuarios',
            'productos' => 'Productos',
            'categorias' => 'Categorías',
            'promociones' => 'Promociones',
            'sliders' => 'Sliders',
            'pedidos' => 'Pedidos',
            'envios' => 'Envíos',
        ];
        $actionLabels = [
            'ver' => 'Ver',
            'crear' => 'Crear',
            'editar' => 'Editar',
            'cambiar_estado' => 'Cambiar estado',
            'actualizar_estado' => 'Actualizar estado',
            'cancelar' => 'Cancelar',
        ];

        return collect(PermissionSeeder::PERMISSIONS)
            ->groupBy(fn (string $permission) => explode('.', $permission, 2)[0])
            ->map(fn ($permissions, string $module) => [
                'label' => $moduleLabels[$module],
                'permissions' => $permissions->map(function (string $permission) use ($actionLabels): array {
                    $action = explode('.', $permission, 2)[1];

                    return ['name' => $permission, 'label' => $actionLabels[$action]];
                })->all(),
            ])->values()->all();
    }
}
