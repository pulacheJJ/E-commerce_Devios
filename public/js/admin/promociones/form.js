document.addEventListener('DOMContentLoaded', function() {
    
    if (typeof jQuery !== 'undefined' && $.fn.select2) {
        $('.select2-multiple').select2({
            placeholder: "Haz clic para buscar...",
            allowClear: true,
            width: '100%'
        });

        // Filtrado dinámico: Categorías -> Productos
        const $categorias = $('#categorias');
        const $productos = $('#productos');
        
        const opcionesOriginales = $productos.find('option').clone();

        $categorias.on('change', function() {
            const categoriasSeleccionadas = $(this).val(); 
            const productosSeleccionados = $productos.val() || [];

            $productos.empty();

            if (!categoriasSeleccionadas || categoriasSeleccionadas.length === 0) {
                $productos.append(opcionesOriginales.clone());
            } else {
                opcionesOriginales.each(function() {
                    const idCategoria = String($(this).data('categoria'));
                    if (categoriasSeleccionadas.includes(idCategoria)) {
                        $productos.append($(this).clone());
                    }
                });
            }

            $productos.val(productosSeleccionados);
            $productos.trigger('change.select2');
        });
    }

    // Previsualización de la imagen
    const inputBanner = document.getElementById('ruta_banner');
    const previewContainer = document.getElementById('banner-preview');

    if (inputBanner && previewContainer) {
        inputBanner.addEventListener('change', function() {
            previewContainer.innerHTML = ''; 
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxHeight = '120px';
                    img.style.width = '100%';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '6px';
                    img.style.border = '1px solid var(--teal)';
                    previewContainer.appendChild(img);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});