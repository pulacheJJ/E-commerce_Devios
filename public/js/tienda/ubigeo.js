// public/js/tienda/ubigeo.js

document.addEventListener('DOMContentLoaded', () => {
    // Referencias al DOM
    const selDep = document.getElementById('selDepartamento');
    const selProv = document.getElementById('selProvincia');
    const selDist = document.getElementById('selDistrito');

    // Si no existen los selects en la vista actual, detenemos el script
    if (!selDep || !selProv || !selDist) return;

    // 1. Cargar Departamentos
    async function cargarDepartamentos() {
        try {
            const response = await fetch('/api/ubigeo/departamentos');
            const data = await response.json();
            
            selDep.innerHTML = '<option value="">Selecciona Departamento</option>';
            data.forEach(d => {
                selDep.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
            });
        } catch (error) { 
            console.error("Error al cargar departamentos:", error); 
        }
    }

    // 2. Escuchar cambio en Departamento
    selDep.addEventListener('change', async function() {
        selProv.innerHTML = '<option value="">Cargando...</option>';
        selProv.disabled = true;
        selDist.innerHTML = '<option value="">Selecciona una opción</option>';
        selDist.disabled = true;

        const depId = this.value;
        if(!depId) {
            selProv.innerHTML = '<option value="">Selecciona Provincia</option>';
            return;
        }

        try {
            const response = await fetch(`/api/ubigeo/provincias/${depId}`);
            const data = await response.json();
            
            selProv.innerHTML = '<option value="">Selecciona Provincia</option>';
            data.forEach(d => {
                selProv.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
            });
            selProv.disabled = false;
        } catch (error) {
            console.error("Error al cargar provincias:", error);
        }
    });

    // 3. Escuchar cambio en Provincia
    selProv.addEventListener('change', async function() {
        selDist.innerHTML = '<option value="">Cargando...</option>';
        selDist.disabled = true;
        
        const provId = this.value;
        if(!provId) {
            selDist.innerHTML = '<option value="">Selecciona Distrito</option>';
            return;
        }

        try {
            const response = await fetch(`/api/ubigeo/distritos/${provId}`);
            const data = await response.json();
            
            selDist.innerHTML = '<option value="">Selecciona Distrito</option>';
            data.forEach(d => {
                selDist.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
            });
            selDist.disabled = false;
        } catch (error) {
            console.error("Error al cargar distritos:", error);
        }
    });

    // Inicializar
    cargarDepartamentos();
});