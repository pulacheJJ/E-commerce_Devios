document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('tipo');
    const provinceField = document.getElementById('province-rate-field');
    const province = document.getElementById('provincia_id');

    if (!type || !provinceField || !province) return;

    const synchronize = () => {
        const isProvinceRate = type.value === 'provincia';
        provinceField.hidden = !isProvinceRate;
        province.disabled = !isProvinceRate;
        province.required = isProvinceRate;
        if (!isProvinceRate) province.value = '';
    };

    type.addEventListener('change', synchronize);
    synchronize();
});
