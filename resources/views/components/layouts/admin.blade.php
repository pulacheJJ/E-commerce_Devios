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
                <h2>DEVIOZ<span>.</span></h2>
            </div>
            <nav class="sidebar__nav">
                <a href="{{ route('admin.dashboard') }}"
                    class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="{{ route('admin.categorias.index') }}"
                    class="{{ request()->routeIs('admin.categorias.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i> Categorías
                </a>
                <a href="{{ route('admin.productos.index') }}"
                    class="{{ request()->routeIs('admin.productos.*') ? 'active' : '' }}">
                    <i class="fas fa-box"></i> Productos
                </a>
                <hr>
                <a href="{{ route('tienda.catalogo') }}" target="_blank" class="nav-link">
                    <i class="fas fa-store"></i>
                    Ver Tienda
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <div class="top-bar__search"></div>
                <div class="top-bar__user">
                    <i class="fas fa-user-shield" style="color: var(--teal); margin-right: 8px;"></i>
                    <span>Administrador</span>
                </div>
            </header>

            <div class="content-wrapper">
                {{ $slot }}
            </div>
        </main>
    </div>

    {{ $js ?? '' }}
</body>

</html>
