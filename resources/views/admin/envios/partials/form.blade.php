@php
    $currentType = old('tipo', isset($tarifa) && $tarifa->es_predeterminada ? 'predeterminada' : 'provincia');
@endphp

@if ($errors->any())
    <div class="alert alert--danger">Hay campos por corregir.</div>
@endif

<div class="form-grid">
    <div class="form-group full-width">
        <label for="tipo">Tipo de tarifa</label>
        <select id="tipo" name="tipo" class="form-select" required>
            <option value="provincia" @selected($currentType === 'provincia')>Tarifa por provincia</option>
            <option value="predeterminada" @selected($currentType === 'predeterminada')>Tarifa predeterminada</option>
        </select>
        @error('tipo') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    <div class="form-group full-width" id="province-rate-field">
        <label for="provincia_id">Provincia</label>
        <select id="provincia_id" name="provincia_id" class="form-select">
            <option value="">Selecciona una provincia</option>
            @foreach ($provincias as $provincia)
                <option value="{{ $provincia->id }}" @selected(old('provincia_id', $tarifa->provincia_id ?? null) === $provincia->id)>
                    {{ $provincia->departamento->nombre ?? '-' }} — {{ $provincia->nombre }}
                </option>
            @endforeach
        </select>
        @error('provincia_id') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="costo">Costo de envío (S/)</label>
        <input id="costo" name="costo" type="number" min="0" max="99999999.99" step="0.01"
            value="{{ old('costo', $tarifa->costo ?? '') }}" required>
        @error('costo') <span class="error-text">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label>
            <input type="hidden" name="esta_activa" value="0">
            <input type="checkbox" name="esta_activa" value="1"
                @checked((bool) old('esta_activa', $tarifa->esta_activa ?? true))>
            Tarifa activa
        </label>
        @error('esta_activa') <span class="error-text">{{ $message }}</span> @enderror
    </div>
</div>
