<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Dashboard de Auditorías</h3>
</div>

<!-- Panel de Filtros -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="GET">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="sucursal"><strong>Sucursal:</strong></label>
                    <select name="sucursal" id="sucursal" class="form-control">
                        <option value="">-- Todas --</option>
                        <?php foreach ($sucursales as $nro => $desc): ?>
                            <option value="<?php echo $nro; ?>" <?php echo (isset($_GET['sucursal']) && $_GET['sucursal'] == $nro) ? 'selected' : ''; ?>><?php echo htmlspecialchars($desc); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="estado"><strong>Estado:</strong></label>
                    <select name="estado" id="estado" class="form-control">
                        <option value="">-- Todos --</option>
                        <?php foreach ($estadosPosibles as $estado): ?>
                            <option value="<?php echo $estado; ?>" <?php echo (isset($_GET['estado']) && $_GET['estado'] == $estado) ? 'selected' : ''; ?>><?php echo $estado; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fecha_desde"><strong>Desde:</strong></label>
                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="fecha_hasta"><strong>Hasta:</strong></label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <?php
                    $filters = $_GET;
                    unset($filters['url']);
                    $queryParams = http_build_query($filters);
                    ?>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i>Filtrar</button>
                    <div class="d-flex mt-2">
                        <a href="/comercial/auditoria/public/dashboard" class="btn btn-secondary flex-fill mr-1">Limpiar</a>
                        <a href="/comercial/auditoria/public/dashboard/exportarExcel?<?php echo $queryParams; ?>" class="btn btn-success flex-fill ml-1"><i class="fas fa-file-excel"></i> Descargar</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- KPIs -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3"><div class="card text-white bg-primary h-100"><div class="card-body"><h5 class="card-title">Pendientes (Nuevos)</h5><p class="card-text display-4"><?php echo $stats['abiertos'] ?? 0; ?></p></div></div></div>
    <div class="col-lg-3 col-md-6 mb-3"><div class="card text-white bg-warning h-100"><div class="card-body"><h5 class="card-title">Derivados (a Gerencia)</h5><p class="card-text display-4"><?php echo $stats['derivados'] ?? 0; ?></p></div></div></div>
    <div class="col-lg-3 col-md-6 mb-3"><div class="card text-white bg-success h-100"><div class="card-body"><h5 class="card-title">Aprobados</h5><p class="card-text display-4"><?php echo $stats['aprobados'] ?? 0; ?></p></div></div></div>
    <div class="col-lg-3 col-md-6 mb-3"><div class="card text-white bg-danger h-100"><div class="card-body"><h5 class="card-title">Rechazados</h5><p class="card-text display-4"><?php echo $stats['rechazados'] ?? 0; ?></p></div></div></div>
</div>

<!-- Formulario para Acciones Masivas -->
<form action="/comercial/auditoria/public/dashboard/aprobarMasivo" method="POST" id="formMasivo" onsubmit="return confirm('¿Estás seguro de APROBAR todas las auditorías seleccionadas?');">
    
    <!-- Botonera de Acciones Masivas -->
    <div class="mb-2 d-flex justify-content-between">
        <!-- El botón aparecerá aquí -->
        <button type="submit" class="btn btn-success btn-sm shadow-sm" id="btnAprobarMasivo" style="display: none;">
            <i class="fas fa-check-double"></i> Aprobar Seleccionados
        </button>
    </div>

    <!-- Tabla de Datos -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table id="auditsTable" class="table table-hover table-bordered">
                <thead class="thead-light">
                    <tr>
                        <!-- Checkbox Maestro -->
                        <th style="width: 30px;" class="text-center">
                            <input type="checkbox" id="checkAll">
                        </th>
                        <th>Fecha Solicitud</th>
                        <th>Sucursal</th>
                        <th>N° Comprobante</th>
                        <th>Motivo</th>
                        <th style="min-width: 200px;">Observaciones</th> <!-- Ancho mínimo para leer mejor -->
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($auditorias)): ?>
                        <tr><td colspan="8" class="text-center text-muted">No se encontraron resultados para los filtros aplicados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($auditorias as $audit): ?>
                            <tr>
                                <!-- Checkbox Individual -->
                                <td class="text-center align-middle">
                                    <?php if ($audit['estado'] == 'Pendiente'): ?>
                                        <input type="checkbox" name="ids[]" value="<?php echo $audit['id']; ?>" class="check-item">
                                    <?php else: ?>
                                        <input type="checkbox" disabled class="text-muted">
                                    <?php endif; ?>
                                </td>
                                
                                <td class="align-middle"><?php echo date('d/m/Y H:i', strtotime($audit['fecha_creacion'])); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars($audit['DESC_SUCURSAL']); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars($audit['n_comp']); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars($audit['motivo']); ?></td>
                                
                                <!-- CAMBIO: Observación Completa (sin recorte) -->
                                <td class="align-middle">
                                    <?php echo nl2br(htmlspecialchars($audit['observaciones'])); ?>
                                </td>

                                <td class="align-middle">
                                    <?php $badgeClass = ['Pendiente' => 'badge-info', 'Autorizado N1' => 'badge-success', 'Autorizado N2' => 'badge-primary', 'Rechazado' => 'badge-danger'][$audit['estado']] ?? 'badge-secondary'; ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($audit['estado']); ?></span>
                                </td>
                                <td class="align-middle"><a href="/comercial/auditoria/public/dashboard/detalle/<?php echo $audit['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Ver Detalle</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<?php
$pageScripts = "
<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#auditsTable').DataTable({
        'language': { 
            'url': '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json',
            'search': 'Buscar en listado:', // Etiqueta personalizada para el buscador
            'lengthMenu': 'Mostrar _MENU_ registros'
        },
        'order': [[ 1, 'desc' ]], // Ordenar por fecha (columna 1)
        
        // --- CAMBIO: BUSCADOR ACTIVADO ---
        'searching': true,       
        
        'lengthChange': true,     
        'lengthMenu': [ [15, 25, 50, 100, -1], [15, 25, 50, 100, 'Todos'] ], 
        'pageLength': 15,         
        'stateSave': true,
        'columnDefs': [
            { 'orderable': false, 'targets': 0 } // Desactivar ordenamiento en checkboxes
        ]
    });

    // Lógica para el 'Seleccionar Todo'
    $('#checkAll').click(function() {
        var isChecked = $(this).prop('checked');
        $('.check-item').prop('checked', isChecked);
        toggleMasivoButton();
    });

    // Lógica para mostrar/ocultar el botón verde
    $(document).on('change', '.check-item', function() {
        toggleMasivoButton();
        if(false == $(this).prop('checked')){ 
            $('#checkAll').prop('checked', false); 
        }
    });

    function toggleMasivoButton() {
        if ($('.check-item:checked').length > 0) {
            $('#btnAprobarMasivo').fadeIn();
        } else {
            $('#btnAprobarMasivo').fadeOut();
        }
    }
});
</script>
";
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>