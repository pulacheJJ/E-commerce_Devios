<x-layouts.admin>
    <x-slot:title>Editar tarifa de envío | DEVIOZ</x-slot:title>
    <header class="admin-header"><h1 class="admin-header__title">Editar tarifa de envío</h1></header>
    <section class="admin-card">
        <form class="shipping-rate-form" method="POST" action="{{ route('admin.envios.update', $tarifa) }}">
            @csrf @method('PUT')
            @include('admin.envios.partials.form')
            <button class="btn btn--primary" type="submit">Actualizar tarifa</button>
        </form>
    </section>
    <x-slot:js>
        <script src="{{ asset('js/admin/envios/form.js') }}"></script>
    </x-slot:js>
</x-layouts.admin>
