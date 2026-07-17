<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Panel Admin | DEVIOZ' }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>

<body>

    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar__logo">
                <img src="{{ asset('img/logo-devioz.webp') }}" alt="DEVIOZ Logo" class="logo-img">

            </div>
            <nav class="sidebar__nav">
                <a href="{{ route('admin.dashboard') }}"
                    class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                @can('categorias.ver')
                <a href="{{ route('admin.categorias.index') }}"
                    class="{{ request()->routeIs('admin.categorias.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i> Categorías
                </a>
                @endcan
                @can('productos.ver')
                <a href="{{ route('admin.productos.index') }}"
                    class="{{ request()->routeIs('admin.productos.*') ? 'active' : '' }}">
                    <i class="fas fa-box"></i> Productos
                </a>
                @endcan
                @can('sliders.ver')
                <a href="{{ route('admin.sliders.index') }}"
                    class="sidebar-link {{ request()->routeIs('admin.sliders.*') ? 'active' : '' }}">
                    <i class="fas fa-images"></i>
                    <span>Banners / Slider</span>
                </a>
                @endcan
                @can('promociones.ver')
                <a href="{{ route('admin.promociones.index') }}"
                    class="{{ request()->routeIs('admin.promociones.*') ? 'active' : '' }}">
                    <i class="fas fa-percent"></i> Promociones
                </a>
                @endcan
                @can('usuarios.ver')
                <a href="{{ route('admin.usuarios.index') }}"
                    class="{{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Usuarios
                </a>
                @endcan
                <hr>
                <a href="{{ route('tienda.inicio') }}" target="_blank" class="nav-link">
                    <i class="fas fa-store"></i>
                    Ver Tienda
                </a>

                <div class="logout-container">
                    <form action="{{ route('logout') }}" method="POST" id="formLogout">
                        @csrf
                        <button type="submit" class="btn-logout" title="Cerrar sesión de forma segura">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Cerrar Sesión</span>
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <div class="top-bar__search"></div>
                <div class="top-bar__user">
                    <i class="fas fa-user-shield" style="color: var(--teal); margin-right: 8px;"></i>
                    <span>{{ auth()->user()->hasRole('admin') ? 'Administrador' : 'Gestor' }}</span>
                </div>
            </header>

            <div class="content-wrapper">
                {{ $slot }}
            </div>
        </main>
    </div>

    {{ $js ?? '' }}
    <script src="{{ asset('js/admin/admin.js') }}"></script>
</body>

</html>
