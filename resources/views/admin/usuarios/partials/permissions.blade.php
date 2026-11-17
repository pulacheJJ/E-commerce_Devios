@php
    $checkedPermissions = old('permissions', $selectedPermissions ?? []);
@endphp

<div id="manager-permissions" class="form-group full-width" hidden>
    <div class="alert-info manager-permissions__notice">
        <i class="fas fa-shield-alt"></i>
        Selecciona únicamente los accesos que tendrá este gestor. Debe conservar al menos uno.
    </div>

    <div class="manager-permissions__grid">
        @foreach ($permissionGroups as $group)
            <fieldset class="manager-permissions__group">
                <legend class="manager-permissions__legend">{{ $group['label'] }}</legend>
                @foreach ($group['permissions'] as $permission)
                    <label class="manager-permissions__option">
                        <input type="checkbox" name="permissions[]" value="{{ $permission['name'] }}"
                            @checked(in_array($permission['name'], $checkedPermissions, true))>
                        <span>{{ $permission['label'] }}</span>
                    </label>
                @endforeach
            </fieldset>
        @endforeach
    </div>

    @error('permissions') <span class="error-text">{{ $message }}</span> @enderror
    @error('permissions.*') <span class="error-text">{{ $message }}</span> @enderror
</div>
