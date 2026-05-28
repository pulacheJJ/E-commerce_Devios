<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Mi E-Commerce Pro' }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    {{ $css ?? '' }}
</head>

<body>

    <x-navbar />

    <main class="contenedor-principal">
        {{ $slot }}
    </main>

    <x-footer />

    <script src="{{ asset('js/app.js') }}"></script>
    {{ $js ?? '' }}
</body>

</html>
