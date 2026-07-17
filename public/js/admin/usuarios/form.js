document.addEventListener('DOMContentLoaded', () => {
    const roleSelect = document.getElementById('role');
    const permissionsSection = document.getElementById('manager-permissions');

    if (!roleSelect || !permissionsSection || permissionsSection.dataset.initialized === 'true') {
        return;
    }

    permissionsSection.dataset.initialized = 'true';
    const permissionInputs = permissionsSection.querySelectorAll('input[name="permissions[]"]');

    const synchronizeVisibility = () => {
        const isManager = roleSelect.value === 'gestor';
        permissionsSection.hidden = !isManager;
        permissionInputs.forEach((input) => {
            input.disabled = !isManager;
        });
    };

    roleSelect.addEventListener('change', synchronizeVisibility);
    synchronizeVisibility();
});
