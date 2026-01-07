<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><?php echo $panelTitle; ?></h3>
    <!-- Botón de Ayuda -->
    <button type="button" class="btn btn-info shadow-sm" data-toggle="modal" data-target="#modalAyuda">
        <i class="fas fa-question-circle mr-1"></i> Guía de Uso
    </button>
</div>

<!-- Filtros de Fecha -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="GET">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="fecha_desde"><strong>Filtrar desde:</strong></label>
                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="fecha_hasta"><strong>Filtrar hasta:</strong></label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2 w-100"><i class="fas fa-filter mr-1"></i>Filtrar</button>
                    <a href="/comercial/auditoria/public/sucursal/pendientes" class="btn btn-secondary w-100 mt-2">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- KPIs -->
<div class="row mb-4">
    <div class="col-md-4"><div class="card text-center shadow-sm h-100"><div class="card-body"><i class="fas fa-file-invoice-dollar fa-2x text-primary mb-2"></i><h6 class="card-subtitle mb-2 text-muted">Total Pendientes</h6><p class="card-text h3"><?php echo $stats['total_pendientes'] ?? 0; ?></p></div></div></div>
    <div class="col-md-4"><div class="card text-center shadow-sm h-100"><div class="card-body"><i class="fas fa-dollar-sign fa-2x text-success mb-2"></i><h6 class="card-subtitle mb-2 text-muted">Monto Total Pendiente</h6><p class="card-text h3">$<?php echo number_format($stats['monto_total'] ?? 0, 2, ',', '.'); ?></p></div></div></div>
    <div class="col-md-4"><div class="card text-center shadow-sm h-100"><div class="card-body"><i class="fas fa-calendar-alt fa-2x text-danger mb-2"></i><h6 class="card-subtitle mb-2 text-muted">Operación más Antigua</h6><p class="card-text h3"><?php echo isset($stats['fecha_mas_antigua']) ? date('d/m/Y', strtotime($stats['fecha_mas_antigua'])) : 'N/A'; ?></p></div></div></div>
</div>

<!-- Pestañas -->
<div class="card shadow-sm">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="managementTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="chart-tab" data-toggle="tab" href="#chart" role="tab" aria-controls="chart" aria-selected="true"><i class="fas fa-chart-bar mr-1"></i> Resumen por Mes</a></li>
            <li class="nav-item"><a class="nav-link" id="table-tab" data-toggle="tab" href="#table" role="tab" aria-controls="table" aria-selected="false"><i class="fas fa-list mr-1"></i> Gestión de Operaciones</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="managementTabContent">
            
            <!-- Gráfico -->
            <div class="tab-pane fade show active" id="chart" role="tabpanel" aria-labelledby="chart-tab" style="height: 400px;">
                <canvas id="pendientesChart"></canvas>
            </div>
            
            <!-- Tabla -->
            <div class="tab-pane fade" id="table" role="tabpanel" aria-labelledby="table-tab">
                
                <!-- AGREGADO: Filtro visual de Estados -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="filtroEstado"><i class="fas fa-filter"></i> <strong>Filtrar por Estado:</strong></label>
                        <select id="filtroEstado" class="form-control">
                            <option value="">Mostrar Todos</option>
                            <option value="Sin Justificar">Sin Justificar (Nuevos)</option>
                            <option value="Rechazado">Rechazados (A Corregir)</option>
                        </select>
                    </div>
                </div>

                <table id="operacionesTable" class="table table-hover w-100">
                    <thead class="thead-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Sucursal</th>
                            <th>N° Comprobante</th>
                            <th>Importe</th>
                            <th>Estado</th> <!-- AGREGADO: Columna Estado -->
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($operaciones)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No se encontraron operaciones con los filtros aplicados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($operaciones as $op): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($op['FECHA'])); ?></td>
                                    <td><?php echo htmlspecialchars($op['DESC_SUCURSAL']); ?></td>
                                    <td><?php echo htmlspecialchars($op['N_COMP']); ?></td>
                                    <td>$<?php echo number_format($op['IMPORTE'], 2, ',', '.'); ?></td>
                                    
                                    <!-- AGREGADO: Lógica visual del Estado -->
                                    <td>
                                        <?php if ($op['ESTADO_VISUAL'] === 'Rechazado'): ?>
                                            <span class="badge badge-danger">Rechazado</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Sin Justificar</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <a href="/comercial/auditoria/public/sucursal/justificar/<?php echo htmlspecialchars($op['N_COMP']); ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit mr-1"></i> 
                                            <!-- Cambia el texto del botón según el estado -->
                                            <?php echo ($op['ESTADO_VISUAL'] === 'Rechazado') ? 'Corregir' : 'Justificar'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Ayuda / Instructivo -->
<div class="modal fade" id="modalAyuda" tabindex="-1" role="dialog" aria-labelledby="modalAyudaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"> <!-- modal-lg para que sea ancho -->
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalAyudaLabel"><i class="fas fa-book-open mr-2"></i> Instructivo de Auditoría de Diferencias de Precio</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        
        <div class="alert alert-light border-left-info" role="alert">
            <strong>¿Para qué sirve esta herramienta?</strong><br>
            Este sistema permite gestionar y justificar las diferencias de precio detectadas automáticamente en las ventas de su sucursal.
        </div>

        <h5 class="text-primary mt-4"><i class="fas fa-list-ol"></i> Paso 1: Identificar Pendientes</h5>
        <p>En la pantalla principal verá dos pestañas:</p>
        <ul>
            <li><strong>Resumen por Mes:</strong> Un gráfico para ver la evolución de sus pendientes.</li>
            <li><strong>Gestión de Operaciones:</strong> La lista detallada de comprobantes que requieren su atención.</li>
        </ul>
        <p>Puede usar el filtro <strong>"Filtrar por Estado"</strong> sobre la tabla para ver:</p>
        <ul>
            <li><span class="badge badge-secondary">Sin Justificar</span>: Operaciones nuevas que ingresaron recientemente.</li>
            <li><span class="badge badge-danger">Rechazados</span>: Justificaciones que envió anteriormente pero fueron rechazadas por Auditoría (requieren corrección).</li>
        </ul>

        <hr>

        <h5 class="text-primary mt-4"><i class="fas fa-edit"></i> Paso 2: Justificar una Operación</h5>
        <p>Para procesar un pendiente, haga clic en el botón azul <strong>"Justificar"</strong> (o "Corregir"). Se abrirá un formulario donde deberá:</p>
        
        <ol>
            <li><strong>Seleccionar el Motivo:</strong> Elija la opción que mejor describa por qué ocurrió la diferencia (ej: <em>"Precio mal colocado", "No aplicó descuento"</em>).</li>
            <li><strong>Observaciones:</strong> Escriba una explicación breve pero clara. Cuantos más detalles brinde, menos probable es que se lo rechacen.</li>
            <li><strong>Aprobación de Supervisora:</strong>
                <ul>
                    <li>Si su supervisora autorizó esta diferencia, seleccione <strong>"Sí"</strong>.</li>
                    <li>Al seleccionar "Sí", aparecerá un campo para <strong>adjuntar la foto</strong> del chat, mail o comprobante de dicha autorización.</li>
                </ul>
            </li>
        </ol>

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> Si declara que tiene aprobación de supervisora, es <strong>obligatorio</strong> adjuntar la evidencia.
        </div>

        <hr>

        <h5 class="text-primary mt-4"><i class="fas fa-traffic-light"></i> Paso 3: Estados del Proceso</h5>
        <p>Una vez enviada la justificación, la operación desaparecerá de su lista de pendientes y pasará a revisión por el área de Auditoría.</p>
        <ul>
            <li><i class="fas fa-check-circle text-success"></i> <strong>Aprobado:</strong> La justificación fue aceptada. El proceso termina aquí.</li>
            <li><i class="fas fa-times-circle text-danger"></i> <strong>Rechazado:</strong> La justificación no fue suficiente o faltan datos.</li>
        </ul>

        <hr>

        <h5 class="text-primary mt-4"><i class="fas fa-redo"></i> ¿Qué hacer si me rechazan una justificación?</h5>
        <p>Si recibe un aviso de rechazo:</p>
        <ol>
            <li>La operación <strong>volverá a aparecer</strong> en su listado de pendientes con el estado <span class="badge badge-danger">Rechazado</span>.</li>
            <li>Haga clic en el botón <strong>"Corregir"</strong>.</li>
            <li>Verá una alerta indicando que es una corrección.</li>
            <li>Modifique el motivo, amplíe la observación o adjunte el archivo correcto y vuelva a enviar el formulario.</li>
        </ol>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>

<?php
$pageScripts = "
<script>
$(document).ready(function() {
    var table = null;

    // Inicializar DataTables cuando se muestra la pestaña
    $('a[data-toggle=\"tab\"]').on('shown.bs.tab', function (e) {
        if (e.target.hash == '#table') {
            if (!$.fn.DataTable.isDataTable('#operacionesTable')) {
                table = $('#operacionesTable').DataTable({
                    'language': { 'url': '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json' },
                    'order': [[ 0, 'desc' ]] // Ordenar por fecha
                });

                // AGREGADO: Lógica del filtro de Estado vinculada a DataTables
                $('#filtroEstado').on('change', function() {
                    var val = $(this).val();
                    // Columna 4 es la de 'Estado' (indices empiezan en 0)
                    table.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
                });
            }
        }
    });

    if (document.getElementById('pendientesChart')) {
        var ctx = document.getElementById('pendientesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: " . ($chartLabels ?? '[]') . ",
                datasets: [{
                    label: 'N° de Operaciones Pendientes',
                    data: " . ($chartValues ?? '[]') . ",
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                responsive: true, maintainAspectRatio: false
            }
        });
    }
});
</script>
";
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>