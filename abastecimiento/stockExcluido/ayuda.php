
<div class="modal fade modal-modern" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header help-header">
        <h5 class="modal-title" id="helpModalLabel">
          <i class="bi bi-question-circle"></i>
          Centro de Ayuda - Sistema de Artículos Excluidos
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <!-- Navigation Tabs -->
        <nav>
          <div class="nav nav-tabs help-tabs" id="nav-tab" role="tablist">
            <button class="nav-link active" id="nav-general-tab" data-bs-toggle="tab" data-bs-target="#nav-general" type="button" role="tab">
              <i class="bi bi-house"></i> General
            </button>
            <button class="nav-link" id="nav-manage-tab" data-bs-toggle="tab" data-bs-target="#nav-manage" type="button" role="tab">
              <i class="bi bi-gear"></i> Gestión
            </button>
            <button class="nav-link" id="nav-import-tab" data-bs-toggle="tab" data-bs-target="#nav-import" type="button" role="tab">
              <i class="bi bi-upload"></i> Importación
            </button>
            <button class="nav-link" id="nav-export-tab" data-bs-toggle="tab" data-bs-target="#nav-export" type="button" role="tab">
              <i class="bi bi-download"></i> Exportación
            </button>
            <button class="nav-link" id="nav-faq-tab" data-bs-toggle="tab" data-bs-target="#nav-faq" type="button" role="tab">
              <i class="bi bi-question-octagon"></i> FAQ
            </button>
          </div>
        </nav>

        <!-- Tab Content -->
        <div class="tab-content help-content" id="nav-tabContent">
          
          <!-- General Tab -->
          <div class="tab-pane fade show active" id="nav-general" role="tabpanel">
            <div class="help-section">
              <h4><i class="bi bi-info-circle text-primary"></i> ¿Qué es el Sistema de Artículos Excluidos?</h4>
              <p>Este sistema permite gestionar artículos que han sido excluidos del inventario regular, proporcionando un control detallado sobre productos que requieren manejo especial.</p>
              
              <div class="accordion help-accordion" id="generalAccordion">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="heading1">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                      <i class="bi bi-list-ul me-2"></i> Funcionalidades Principales
                    </button>
                  </h2>
                  <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#generalAccordion">
                    <div class="accordion-body">
                      <ul class="help-list">
                        <li><strong>Visualización:</strong> Ver todos los artículos excluidos con detalles completos</li>
                        <li><strong>Búsqueda:</strong> Filtrar artículos por cualquier campo de manera instantánea</li>
                        <li><strong>Selección:</strong> Seleccionar uno o varios artículos para operaciones masivas</li>
                        <li><strong>Edición:</strong> Modificar cantidad y observaciones de artículos existentes</li>
                        <li><strong>Eliminación:</strong> Remover artículos del sistema con confirmación</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="heading2">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                      <i class="bi bi-ui-checks-grid me-2"></i> Interfaz de Usuario
                    </button>
                  </h2>
                  <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#generalAccordion">
                    <div class="accordion-body">
                      <div class="row">
                        <div class="col-md-6">
                          <h6><i class="bi bi-search text-info"></i> Barra de Búsqueda</h6>
                          <p>Localizada en la parte superior izquierda, permite filtrar artículos en tiempo real.</p>
                          
                          <h6><i class="bi bi-check-square text-success"></i> Selección Masiva</h6>
                          <p>Checkbox "Seleccionar todo" para operaciones en lote.</p>
                        </div>
                        <div class="col-md-6">
                          <h6><i class="bi bi-button-repeater text-warning"></i> Botones de Acción</h6>
                          <p>Editar, Importar, Exportar y Eliminar artículos seleccionados.</p>
                          
                          <h6><i class="bi bi-table text-secondary"></i> Tabla Principal</h6>
                          <p>Muestra ID, fecha, hora, código, descripción, cantidad y observaciones.</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Gestión Tab -->
          <div class="tab-pane fade" id="nav-manage" role="tabpanel">
            <div class="help-section">
              <h4><i class="bi bi-gear text-primary"></i> Gestión de Artículos</h4>
              
              <div class="accordion help-accordion" id="manageAccordion">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingEdit">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEdit">
                      <i class="bi bi-pencil-square me-2 text-warning"></i> Editar Artículos
                    </button>
                  </h2>
                  <div id="collapseEdit" class="accordion-collapse collapse show" data-bs-parent="#manageAccordion">
                    <div class="accordion-body">
                      <ol class="help-steps">
                        <li>Haga clic en el botón <span class="btn btn-sm btn-warning"><i class="bi bi-pencil-square"></i> Editar</span></li>
                        <li>Seleccione el artículo del desplegable</li>
                        <li>Modifique la cantidad (debe ser mayor a 0)</li>
                        <li>Agregue observaciones si es necesario</li>
                        <li>Haga clic en "Guardar Cambios"</li>
                      </ol>
                      <div class="alert alert-info">
                        <i class="bi bi-lightbulb"></i>
                        <strong>Consejo:</strong> Use el buscador del select para encontrar artículos rápidamente.
                      </div>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingDelete">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDelete">
                      <i class="bi bi-trash3 me-2 text-danger"></i> Eliminar Artículos
                    </button>
                  </h2>
                  <div id="collapseDelete" class="accordion-collapse collapse" data-bs-parent="#manageAccordion">
                    <div class="accordion-body">
                      <h6>Eliminación Individual:</h6>
                      <ol class="help-steps">
                        <li>Marque el checkbox del artículo a eliminar</li>
                        <li>Haga clic en <span class="btn btn-sm btn-danger"><i class="bi bi-trash3"></i> Eliminar</span></li>
                        <li>Confirme la acción en el diálogo</li>
                      </ol>
                      
                      <h6>Eliminación Masiva:</h6>
                      <ol class="help-steps">
                        <li>Use "Seleccionar todo" o marque varios checkboxes</li>
                        <li>El botón mostrará el contador de seleccionados</li>
                        <li>Confirme la eliminación masiva</li>
                      </ol>
                      
                      <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Advertencia:</strong> La eliminación es permanente y no se puede deshacer.
                      </div>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingSearch">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSearch">
                      <i class="bi bi-search me-2 text-info"></i> Búsqueda Avanzada
                    </button>
                  </h2>
                  <div id="collapseSearch" class="accordion-collapse collapse" data-bs-parent="#manageAccordion">
                    <div class="accordion-body">
                      <p>La búsqueda funciona en tiempo real sobre todos los campos visibles:</p>
                      <ul class="help-list">
                        <li><strong>Por ID:</strong> Escriba el número de ID</li>
                        <li><strong>Por Fecha:</strong> Use formato YYYY-MM-DD</li>
                        <li><strong>Por Código:</strong> Escriba parte del código del artículo</li>
                        <li><strong>Por Descripción:</strong> Cualquier palabra de la descripción</li>
                        <li><strong>Por Observaciones:</strong> Texto en observaciones</li>
                      </ul>
                      <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        La búsqueda es instantánea y no distingue entre mayúsculas y minúsculas.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Importación Tab -->
          <div class="tab-pane fade" id="nav-import" role="tabpanel">
            <div class="help-section">
              <h4><i class="bi bi-upload text-primary"></i> Importación desde Excel</h4>
              
              <div class="accordion help-accordion" id="importAccordion">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingImportPrep">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImportPrep">
                      <i class="bi bi-file-earmark-spreadsheet me-2"></i> Preparación del Archivo
                    </button>
                  </h2>
                  <div id="collapseImportPrep" class="accordion-collapse collapse show" data-bs-parent="#importAccordion">
                    <div class="accordion-body">
                      <div class="row">
                        <div class="col-md-8">
                          <h6>Formato Requerido:</h6>
                          <table class="table table-sm table-bordered">
                            <thead class="table-dark">
                              <tr>
                                <th>Columna A</th>
                                <th>Columna B</th>
                                <th>Columna C</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td>Código Artículo</td>
                                <td>Cantidad</td>
                                <td>Observaciones</td>
                              </tr>
                              <tr class="table-light">
                                <td>XC4WBA11C1601</td>
                                <td>25</td>
                                <td>Carteras cuero W25</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                        <div class="col-md-4">
                          <div class="alert alert-info">
                            <h6><i class="bi bi-check2-all"></i> Requisitos:</h6>
                            <ul class="mb-0">
                              <li>Formato .xlsx o .xls</li>
                              <li>Sin encabezados</li>
                              <li>Códigos válidos (serie X)</li>
                              <li>Cantidades numéricas</li>
                            </ul>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingImportProcess">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImportProcess">
                      <i class="bi bi-arrow-up-circle me-2"></i> Proceso de Importación
                    </button>
                  </h2>
                  <div id="collapseImportProcess" class="accordion-collapse collapse" data-bs-parent="#importAccordion">
                    <div class="accordion-body">
                      <ol class="help-steps">
                        <li>Haga clic en <span class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel"></i> Importar</span></li>
                        <li>Seleccione el archivo Excel desde su computadora</li>
                        <li>El sistema validará automáticamente los códigos</li>
                        <li>Revise la vista previa de artículos</li>
                        <li>Los artículos válidos aparecerán en verde</li>
                        <li>Los inválidos aparecerán en rojo</li>
                        <li>Haga clic en "Importar Artículos" para confirmar</li>
                      </ol>
                      
                      <div class="row mt-3">
                        <div class="col-md-6">
                          <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            <strong>Artículos Válidos:</strong> Se importarán correctamente
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Artículos Inválidos:</strong> Se omitirán automáticamente
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingImportTips">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImportTips">
                      <i class="bi bi-lightbulb me-2"></i> Consejos y Solución de Problemas
                    </button>
                  </h2>
                  <div id="collapseImportTips" class="accordion-collapse collapse" data-bs-parent="#importAccordion">
                    <div class="accordion-body">
                      <div class="row">
                        <div class="col-md-6">
                          <h6><i class="bi bi-check-circle text-success"></i> Buenas Prácticas:</h6>
                          <ul class="help-list">
                            <li>Verifique los códigos antes de importar</li>
                            <li>Use cantidades enteras positivas</li>
                            <li>Mantenga observaciones concisas</li>
                            <li>Haga respaldos antes de importaciones masivas</li>
                          </ul>
                        </div>
                        <div class="col-md-6">
                          <h6><i class="bi bi-exclamation-triangle text-warning"></i> Problemas Comunes:</h6>
                          <ul class="help-list">
                            <li><strong>"Código inexistente":</strong> El artículo no existe en el sistema</li>
                            <li><strong>"Artículo ya excluido":</strong> Ya está en la lista</li>
                            <li><strong>Archivo no reconocido:</strong> Verifique el formato</li>
                            <li><strong>Datos en blanco:</strong> Complete todos los campos</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Exportación Tab -->
          <div class="tab-pane fade" id="nav-export" role="tabpanel">
            <div class="help-section">
              <h4><i class="bi bi-download text-primary"></i> Exportación de Datos</h4>
              
              <div class="accordion help-accordion" id="exportAccordion">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingExportBasic">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExportBasic">
                      <i class="bi bi-file-earmark-excel me-2"></i> Exportación Básica
                    </button>
                  </h2>
                  <div id="collapseExportBasic" class="accordion-collapse collapse show" data-bs-parent="#exportAccordion">
                    <div class="accordion-body">
                      <ol class="help-steps">
                        <li>Haga clic en <span class="btn btn-sm btn-info"><i class="bi bi-download"></i> Exportar</span></li>
                        <li>El archivo se descargará automáticamente</li>
                        <li>El nombre incluirá la fecha actual</li>
                        <li>Se exportarán todos los artículos visibles</li>
                      </ol>
                      
                      <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Formato:</strong> El archivo se genera en formato Excel (.xls) compatible con todas las versiones.
                      </div>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingExportFiltered">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExportFiltered">
                      <i class="bi bi-funnel me-2"></i> Exportación Filtrada
                    </button>
                  </h2>
                  <div id="collapseExportFiltered" class="accordion-collapse collapse" data-bs-parent="#exportAccordion">
                    <div class="accordion-body">
                      <p>Para exportar solo ciertos artículos:</p>
                      <ol class="help-steps">
                        <li>Use la búsqueda para filtrar artículos</li>
                        <li>Solo los artículos visibles se exportarán</li>
                        <li>Haga clic en "Exportar"</li>
                        <li>El archivo contendrá únicamente los resultados filtrados</li>
                      </ol>
                      
                      <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        <strong>Ventaja:</strong> Ideal para generar reportes específicos por código, fecha o criterio.
                      </div>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingExportUse">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExportUse">
                      <i class="bi bi-graph-up me-2"></i> Usos del Archivo Exportado
                    </button>
                  </h2>
                  <div id="collapseExportUse" class="accordion-collapse collapse" data-bs-parent="#exportAccordion">
                    <div class="accordion-body">
                      <div class="row">
                        <div class="col-md-6">
                          <h6><i class="bi bi-bar-chart text-info"></i> Análisis y Reportes:</h6>
                          <ul class="help-list">
                            <li>Crear gráficos y estadísticas</li>
                            <li>Análisis de tendencias</li>
                            <li>Reportes ejecutivos</li>
                            <li>Auditorías de inventario</li>
                          </ul>
                        </div>
                        <div class="col-md-6">
                          <h6><i class="bi bi-arrow-repeat text-success"></i> Respaldos y Migraciones:</h6>
                          <ul class="help-list">
                            <li>Copias de seguridad</li>
                            <li>Migración entre sistemas</li>
                            <li>Compartir datos con terceros</li>
                            <li>Archivo histórico</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- FAQ Tab -->
          <div class="tab-pane fade" id="nav-faq" role="tabpanel">
            <div class="help-section">
              <h4><i class="bi bi-question-octagon text-primary"></i> Preguntas Frecuentes</h4>
              
              <div class="accordion help-accordion" id="faqAccordion">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingFaq1">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq1">
                      ¿Por qué no puedo editar un artículo?
                    </button>
                  </h2>
                  <div id="collapseFaq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      <p>Pueden existir varias razones:</p>
                      <ul>
                        <li>No ha seleccionado un artículo del desplegable</li>
                        <li>La cantidad ingresada no es válida (debe ser mayor a 0)</li>
                        <li>Problemas de permisos de usuario</li>
                        <li>Conexión con la base de datos</li>
                      </ul>
                      <div class="alert alert-info">
                        <strong>Solución:</strong> Verifique que todos los campos estén completos y contacte al administrador si persiste.
                      </div>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingFaq2">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq2">
                      ¿Qué significan los colores en la importación?
                    </button>
                  </h2>
                  <div id="collapseFaq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            <strong>Verde:</strong> Artículo válido, se importará correctamente
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="alert alert-danger">
                            <i class="bi bi-x-circle"></i>
                            <strong>Rojo:</strong> Error en el artículo, se omitirá en la importación
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingFaq3">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq3">
                      ¿Puedo recuperar artículos eliminados?
                    </button>
                  </h2>
                  <div id="collapseFaq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>No, las eliminaciones son permanentes.</strong>
                      </div>
                      <p><strong>Recomendaciones:</strong></p>
                      <ul>
                        <li>Siempre exporte los datos antes de eliminar</li>
                        <li>Verifique la selección antes de confirmar</li>
                        <li>Mantenga respaldos regulares</li>
                        <li>Use la búsqueda para verificar antes de eliminar</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingFaq4">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq4">
                      ¿Cómo funciona la búsqueda?
                    </button>
                  </h2>
                  <div id="collapseFaq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      <p>La búsqueda es <strong>instantánea y global:</strong></p>
                      <ul>
                        <li>Busca en todos los campos visibles simultáneamente</li>
                        <li>No distingue entre mayúsculas y minúsculas</li>
                        <li>Muestra resultados mientras escribe</li>
                        <li>Funciona con números, fechas y texto</li>
                      </ul>
                      <div class="alert alert-success">
                        <strong>Ejemplo:</strong> Si escribe "XC5S", mostrará todos los artículos que contengan "XC5S" en cualquier campo.
                      </div>
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingFaq5">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq5">
                      ¿Hay límites en la importación?
                    </button>
                  </h2>
                  <div id="collapseFaq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                      <p><strong>Límites técnicos:</strong></p>
                      <ul>
                        <li>Tamaño de archivo: Depende de la configuración del servidor</li>
                        <li>Número de filas: Sin límite específico, pero considere el rendimiento</li>
                        <li>Solo archivos .xlsx y .xls son compatibles</li>
                      </ul>
                      <div class="alert alert-info">
                        <strong>Recomendación:</strong> Para archivos muy grandes (>1000 filas), considere dividirlos en lotes más pequeños.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i>
          Cerrar
        </button>
        <button type="button" class="btn btn-primary" onclick="window.print()">
          <i class="bi bi-printer"></i>
          Imprimir Ayuda
        </button>
      </div>
    </div>
  </div>
</div>