<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Dashboard de Auditorías</h3>
</div>

<!-- Panel de Filtros -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="GET">
            <div class="row align-items-end">
                <div class="col-md-3"><label for="sucursal"><strong>Sucursal:</strong></label><select name="sucursal" id="sucursal" class="form-control"><option value="">-- Todas --</option><?php foreach ($sucursales as $nro => $desc): ?><option value="<?php echo $nro; ?>" <?php echo (isset($_GET['sucursal']) && $_GET['sucursal'] == $nro) ? 'selected' : ''; ?>><?php echo htmlspecialchars($desc); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label for="estado"><strong>Estado:</strong></label><select name="estado" id="estado" class="form-control"><option value="">-- Todos --</option><?php foreach ($estadosPosibles as $estado): ?><option value="<?php echo $estado; ?>" <?php echo (isset($_GET['estado']) && $_GET['estado'] == $estado) ? 'selected' : ''; ?>><?php echo $estado; ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label for="fecha_desde"><strong>Desde:</strong></label><input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="<?php echo $_GET['fecha_desde'] ?? ''; ?>"></div>
                <div class="col-md-2"><label for="fecha_hasta"><strong>Hasta:</strong></label><input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>"></div>
                <div class="col-md-3">
    <?php
    // Mantenemos la lógica PHP aquí para tenerla lista
    $filters = $_GET;
    unset($filters['url']);
    $queryParams = http_build_query($filters);
    ?>

    <!-- Botón de Filtrar, ocupa todo el ancho -->
    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    
    <!-- Contenedor Flex para los botones de abajo -->
    <div class="d-flex mt-2">
        <!-- Botón Limpiar, ocupa la mitad del espacio -->
        <a href="/comercial/auditoria/public/dashboard" class="btn btn-secondary flex-fill mr-1">Limpiar</a>
        
        <!-- Botón Descargar, ocupa la otra mitad -->
        <a href="/comercial/auditoria/public/dashboard/exportarExcel?<?php echo $queryParams; ?>" class="btn btn-success flex-fill ml-1">
            <i class="fas fa-file-excel"></i> Descargar
        </a>
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

<!-- Tabla de Datos -->
<div class="card shadow-sm">
    <div class="card-body">
        <table id="auditsTable" class="table table-hover">
            <thead class="thead-light">
                <tr><th>Fecha Solicitud</th><th>Sucursal</th><th>N° Comprobante</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($auditorias)): ?><tr><td colspan="6" class="text-center text-muted">No se encontraron resultados para los filtros aplicados.</td></tr><?php else: ?>
                    <?php foreach ($auditorias as $audit): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($audit['fecha_creacion'])); ?></td>
                            <td><?php echo htmlspecialchars($audit['DESC_SUCURSAL']); ?></td>
                            <td><?php echo htmlspecialchars($audit['n_comp']); ?></td>
                            <td><?php echo htmlspecialchars($audit['motivo']); ?></td>
                            <td>
                                <?php $badgeClass = ['Pendiente' => 'badge-info', 'Autorizado N1' => 'badge-success', 'Autorizado N2' => 'badge-primary', 'Rechazado' => 'badge-danger'][$audit['estado']] ?? 'badge-secondary'; ?>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($audit['estado']); ?></span>
                            </td>
                            <td><a href="/comercial/auditoria/public/dashboard/detalle/<?php echo $audit['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Ver Detalle</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$pageScripts = "
<script>
$(document).ready(function() {
    $('#auditsTable').DataTable({
        'language': { 'url': '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json' },
        'order': [[ 0, 'desc' ]], // Ordenar por fecha descendente
        'searching': false,       // Usamos tus filtros PHP, no el buscador de JS
        
        // --- CAMBIO 1: PERMITIR SELECCIONAR CANTIDAD DE REGISTROS ---
        'lengthChange': true,     // Habilitamos el selector
        'lengthMenu': [ [15, 25, 50, 100, -1], [15, 25, 50, 100, 'Todos'] ], // Opciones
        'pageLength': 15,         // Valor por defecto inicial

        // --- CAMBIO 2: RECORDAR LA PÁGINA AL VOLVER ---
        'stateSave': true         // Esto guarda la página (ej: pág 2) en la memoria del navegador
    });
});
</script>
";
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>