<div id="modalDireccion" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Agregar dirección</h3>
            </div>
            <button type="button" id="btnCerrarModalDir" class="btn-close-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <div class="form-group-modal">
                <label for="selDepartamento">Departamento</label>
                <div class="custom-select-wrapper">
                    <select id="selDepartamento" class="form-control-modal">
                        <option value="">Cargando...</option>
                    </select>
                    <i class="fas fa-chevron-down select-icon"></i>
                </div>
            </div>

            <div class="form-group-modal">
                <label for="selProvincia">Provincia</label>
                <div class="custom-select-wrapper">
                    <select id="selProvincia" class="form-control-modal" disabled>
                        <option value="">Selecciona Provincia</option>
                    </select>
                    <i class="fas fa-chevron-down select-icon"></i>
                </div>
            </div>

            <div class="form-group-modal">
                <label for="selDistrito">Distrito</label>
                <div class="custom-select-wrapper">
                    <select id="selDistrito" class="form-control-modal" disabled>
                        <option value="">Selecciona Distrito</option>
                    </select>
                    <i class="fas fa-chevron-down select-icon"></i>
                </div>
            </div>

            <div class="form-group-modal">
                <label for="txtDireccion">Dirección exacta</label>
                <input type="text" id="txtDireccion" class="form-control-modal" placeholder="Ej: Av. Miraflores Mz X Lote 5">
            </div>

            <div class="form-group-modal">
                <label for="txtReferencia">Referencia, Depto, Casa (Opcional)</label>
                <input type="text" id="txtReferencia" class="form-control-modal" placeholder="Ej: Frente al parque principal">
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" id="btnGuardarDireccion" class="btn-modal-primary">
                Seleccionar
            </button>
        </div>
    </div>
</div>