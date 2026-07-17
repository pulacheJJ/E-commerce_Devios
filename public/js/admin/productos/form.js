document.addEventListener('DOMContentLoaded', () => {
    
    // 1. SLUG DINÁMICO
    const inputNombre = document.getElementById('nombre');
    const inputSlug = document.getElementById('slug');

    if (inputNombre && inputSlug) {
        inputNombre.addEventListener('input', function() {
            let slug = this.value
                .toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
            inputSlug.value = slug;
        });
    }

    // 2. PREVISUALIZACIÓN DE NUEVAS FOTOS
    const inputImagenes = document.getElementById('imagenes');
    const previewContainer = document.getElementById('image-preview-container');

    if (inputImagenes) {
        inputImagenes.addEventListener('change', function() {
            previewContainer.innerHTML = ''; 
            if (this.files) {
                Array.from(this.files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        const badge = index === 0 ? '<span class="badge-principal">NUEVA</span>' : '';
                        div.innerHTML = `<img src="${e.target.result}" alt="Preview">${badge}`;
                        previewContainer.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            }
        });
    }

    // 3. GENERADOR DE FILAS (VARIANTES)
    const btnAddVariante = document.getElementById('btn-add-variante');
    const variantesContainer = document.getElementById('variantes-container');
    
    // Cuenta cuántas filas existen actualmente (vital para el edit)
    const indicesExistentes = Array.from(document.querySelectorAll('.variante-row'))
        .map(row => Number.parseInt(row.dataset.variantIndex, 10))
        .filter(Number.isInteger);
    let varianteIndex = indicesExistentes.length > 0 ? Math.max(...indicesExistentes) + 1 : 0;

    function agregarFilaVariante() {
        const tr = document.createElement('tr');
        tr.className = 'variante-row';
        tr.dataset.variantIndex = varianteIndex;
        tr.innerHTML = `
            <td>
                <input type="text" name="variantes[${varianteIndex}][sku]" class="form-input form-input--sm" placeholder="SKU" required>
                <small class="variant-field-error" data-error-for="variantes.${varianteIndex}.sku" style="display: none; margin-top: 5px; color: #fca5a5;"></small>
            </td>
            <td>
                <input type="text" name="variantes[${varianteIndex}][color]" class="form-input form-input--sm mb-5" placeholder="Color">
                <input type="text" name="variantes[${varianteIndex}][especificacion]" class="form-input form-input--sm" placeholder="Esp.">
            </td>
            <td><input type="number" step="0.01" name="variantes[${varianteIndex}][precio]" class="form-input form-input--sm" required></td>
            <td><input type="number" step="0.01" name="variantes[${varianteIndex}][precio_oferta]" class="form-input form-input--sm"></td>
            <td><input type="number" name="variantes[${varianteIndex}][stock]" class="form-input form-input--sm" value="0" required></td>
            <td><button type="button" class="btn btn--danger btn--sm btn-remove-var"><i class="fas fa-trash"></i></button></td>
        `;
        variantesContainer.appendChild(tr);
        varianteIndex++;
    }

    // Inicializar fila vacía SOLO si es la vista Create (cuando length == 0)
    if (variantesContainer && varianteIndex === 0) {
        agregarFilaVariante();
    }

    if (btnAddVariante) {
        btnAddVariante.addEventListener('click', agregarFilaVariante);
    }

    if (variantesContainer) {
        variantesContainer.addEventListener('input', function(e) {
            if (e.target.matches('input[name$="[sku]"]')) {
                e.target.removeAttribute('aria-invalid');
                const error = e.target.parentElement.querySelector('.variant-field-error');
                if (error) error.remove();
            }
        });

        variantesContainer.addEventListener('click', function(e) {
            // Event Delegation para eliminar
            if (e.target.closest('.btn-remove-var')) {
                if(document.querySelectorAll('.variante-row').length > 1){
                    e.target.closest('tr').remove();
                } else {
                    alert('Debe existir al menos una variante.');
                }
            }
        });
    }
});
