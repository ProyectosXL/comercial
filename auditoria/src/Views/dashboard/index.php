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
                <div class="col-md-3"><button type="submit" class="btn btn-primary mr-2 w-100"><i class="fas fa-filter mr-1"></i>Filtrar</button><a href="/comercial/auditoria/public/dashboard" class="btn btn-secondary w-100 mt-2">Limpiar</a></div>
            </div>
        </form>
    </div>
</div>

<!-- KPIs -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3"><div class="card text-white bg-primary h-100"><div class="card-body"><div class="d-flex justify-content-between"><h5 class="card-title">Total Incidentes</h5><i class="fas fa-clipboard-list fa-2x"></i></div><p class="card-text display-4"><?php echo $stats['total'] ?? 0; ?></p></div></div></div>
    <div class="col-lg-3 col-md-6 mb-3"><div class="card text-white bg-info h-100"><div class="card-body"><div class="d-flex justify-content-between"><h5 class="card-title">Abiertos</h5><i class="fas fa-folder-open fa-2x"></i></div><p class="card-text display-4"><?php echo $stats['abiertos'] ?? 0; ?></p></div></div></div>
    <div class="col-lg-3 col-md-6 mb-3"><div class="card text-white bg-warning h-100"><div class="card-body"><div class="d-flex justify-content-between"><h5 class="card-title">En Curso</h5><i class="fas fa-cogs fa-2x"></i></div><p class="card-text display-4"><?php echo $stats['en_curso'] ?? 0; ?></p></div></div></div>
    <div class="col-lg-3 col-md-6 mb-3"><div class="card text-white bg-success h-100"><div class="card-body"><div class="d-flex justify-content-between"><h5 class="card-title">Tiempo Prom. Res. (días)</h5><i class="fas fa-hourglass-half fa-2x"></i></div><p class="card-text display-4"><?php echo number_format($stats['tiempo_promedio_resolucion'] ?? 0, 1, ',', '.'); ?></p></div></div></div>
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
        'order': [[ 0, 'desc' ]],
        'searching': false, // Desactivamos la búsqueda de DataTables para usar nuestros filtros
        'lengthChange': false, // Opcional: Desactivar el cambio de N° de entradas
        'pageLength': 15 // Opcional: Mostrar 15 entradas por página
    });
});
</script>
";
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>