@php
    $checkedPermissions = old('permissions', $selectedPermissions ?? []);
@endphp

<div id="manager-permissions" class="form-group full-width" hidden>
    <div class="alert-info" style="margin-bottom: 16px;">
        <i class="fas fa-shield-alt"></i>
        Selecciona únicamente los accesos que tendrá este gestor. Debe conservar al menos uno.
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
        @foreach ($permissionGroups as $group)
            <fieldset style="border:1px solid #29455a; border-radius:8px; padding:14px;">
                <legend style="padding:0 6px; font-weight:700;">{{ $group['label'] }}</legend>
                @foreach ($group['permissions'] as $permission)
                    <label style="display:flex; align-items:center; gap:8px; margin:8px 0;">
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
