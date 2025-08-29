

<div class="modal fade modal-modern" id="altaModal" tabindex="-1" role="dialog" aria-labelledby="altaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="altaModalLabel">
          <i class="bi bi-pencil-square"></i>
          Editar Artículo Excluido
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formularioAlta">
          <div class="mb-4">
            <label class="form-label form-label-modern">Artículo</label>
            <select class="form-control form-control-modern" id="codArticulo" name="articulo" required>
              <option selected disabled></option>
              <?php
              if (isset($todosLosArticulosArray)) {
                foreach ($todosLosArticulosArray as $valor => $value) {
              ?>
                <option value="<?= $value->COD_ARTICU; ?>">
                  <?= $value->COD_ARTICU.' - '.$value->DESCRIPCION.' - '.$value->CANT.' Unid.'; ?>
                </option>
              <?php
                }
              }
              ?>
            </select>
          </div>
          
          <div class="mb-4">
            <label class="form-label form-label-modern">Cantidad</label>
            <input type="number" 
                   class="form-control form-control-modern" 
                   id="inputCant" 
                   name="cantidad" 
                   min="1"
                   placeholder="Ingrese la cantidad">
          </div>
          
          <div class="mb-4">
            <label class="form-label form-label-modern">Observaciones</label>
            <textarea class="form-control form-control-modern" 
                      id="inputObs" 
                      name="observaciones" 
                      rows="3" 
                      placeholder="Ingrese observaciones..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i>
          Cancelar
        </button>
        <button type="button" class="btn btn-custom btn-edit" onclick="insertarArticulo()">
          <i class="bi bi-check-lg"></i>
          Guardar Cambios
        </button>
      </div>
    </div>
  </div>
</div>