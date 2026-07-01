<!-- MODAL DE DIRECCIÓN -->
<div class="address-modal-overlay" id="modalDireccion">
    <div class="address-modal-content">
        <div class="address-modal-header">
            <h3><i class="fas fa-map-marker-alt"></i> Agregar dirección</h3>
            <button type="button" class="btn-close-modal" id="btnCerrarModalDir"><i class="fas fa-times"></i></button>
        </div>
        <div class="address-modal-body">
            <div class="form-group">
                <label>Departamento</label>
                <select id="selDepartamento" class="form-control select-icon">
                    <option value="">Cargando...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Provincia</label>
                <select id="selProvincia" class="form-control select-icon" disabled>
                    <option value="">Selecciona una opción</option>
                </select>
            </div>
            <div class="form-group">
                <label>Distrito</label>
                <select id="selDistrito" class="form-control select-icon" disabled>
                    <option value="">Selecciona una opción</option>
                </select>
            </div>
            <div class="form-group">
                <label>Dirección exacta</label>
                <input type="text" id="txtDireccion" class="form-control" placeholder="Ingresa calle y número / Mz / Lote">
            </div>
            <div class="form-group">
                <label>Referencia, Depto, Casa (Opcional)</label>
                <input type="text" id="txtReferencia" class="form-control" placeholder="Ejemplo: Depto 101, casa 3">
            </div>
        </div>
        <div class="address-modal-footer">
            <button type="button" class="btn btn--primary" id="btnGuardarDireccion" style="width: 100%; padding: 15px;">Seleccionar</button>
        </div>
    </div>
</div>