<div class="modal fade modal-modern" id="altaModalImport" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel">
          <i class="bi bi-file-earmark-excel"></i>
          Importar Artículos desde Excel
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6 mb-4">
            <label class="form-label form-label-modern">
              <i class="bi bi-file-earmark-arrow-up"></i>
              Seleccionar Archivo Excel
            </label>
            <input id="fileupload" 
                   type="file" 
                   name="files[]" 
                   class="form-control form-control-modern" 
                   accept=".xlsx,.xls">
            <div class="form-text mt-2">
              <i class="bi bi-info-circle"></i>
              Formatos soportados: .xlsx, .xls
            </div>
          </div>
          <div class="col-md-6 mb-4">
            <div class="alert alert-info">
              <h6><i class="bi bi-lightbulb"></i> Formato esperado:</h6>
              <small>
                <strong>Columna A:</strong> Código de artículo<br>
                <strong>Columna B:</strong> Cantidad<br>
                <strong>Columna C:</strong> Observaciones
              </small>
            </div>
          </div>
        </div>
        
        <div class="table-container">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
              <i class="bi bi-list-check text-success"></i>
              Vista Previa de Artículos a Importar
            </h6>
            <span id="importCounter" class="badge bg-primary">0 artículos</span>
          </div>
          
          <div class="w3-responsive table-responsive2">
            <table id="tblItems" class="table table-modern">
              <thead class="thead-dark">
                <tr>
                  <th scope="col" style="width: 15%">ARTÍCULO</th>
                  <th scope="col" style="width: 10%">CANTIDAD</th>
                  <th scope="col" style="width: 25%">OBSERVACIONES</th>
                  <th scope="col" style="width: 35%">DESCRIPCIÓN</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" id="close" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i>
          Cerrar
        </button>
        <button class="btn btn-custom btn-import" id="save">
          <i class="bi bi-upload"></i>
          <span id="saveButtonText">Importar Artículos</span>
        </button>
      </div>
    </div>
  </div>
</div>