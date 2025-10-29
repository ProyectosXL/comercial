<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><?php echo $panelTitle; ?></h3>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="GET">
            <div class="row align-items-end">
                <div class="col-md-3"><label for="sucursal"><strong>Filtrar por Sucursal:</strong></label><select name="sucursal" id="sucursal" class="form-control"><option value="">-- Todas --</option><?php foreach ($sucursales as $nro => $desc): ?><option value="<?php echo $nro; ?>" <?php echo (isset($_GET['sucursal']) && $_GET['sucursal'] == $nro) ? 'selected' : ''; ?>><?php echo htmlspecialchars($desc); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label for="fecha_desde"><strong>Desde:</strong></label><input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="<?php echo $_GET['fecha_desde'] ?? ''; ?>"></div>
                <div class="col-md-3"><label for="fecha_hasta"><strong>Hasta:</strong></label><input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>"></div>
                <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-primary mr-2 w-100"><i class="fas fa-filter mr-1"></i>Filtrar</button><a href="/comercial/auditoria/public/sucursal/pendientes" class="btn btn-secondary w-100 mt-2">Limpiar Filtro</a></div>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4"><div class="card text-center shadow-sm h-100"><div class="card-body"><i class="fas fa-file-invoice-dollar fa-2x text-primary mb-2"></i><h6 class="card-subtitle mb-2 text-muted">Total Pendientes</h6><p class="card-text h3"><?php echo $stats['total_pendientes'] ?? 0; ?></p></div></div></div>
    <div class="col-md-4"><div class="card text-center shadow-sm h-100"><div class="card-body"><i class="fas fa-dollar-sign fa-2x text-success mb-2"></i><h6 class="card-subtitle mb-2 text-muted">Monto Total Pendiente</h6><p class="card-text h3">$<?php echo number_format($stats['monto_total'] ?? 0, 2, ',', '.'); ?></p></div></div></div>
    <div class="col-md-4"><div class="card text-center shadow-sm h-100"><div class="card-body"><i class="fas fa-calendar-alt fa-2x text-danger mb-2"></i><h6 class="card-subtitle mb-2 text-muted">Operación más Antigua</h6><p class="card-text h3"><?php echo isset($stats['fecha_mas_antigua']) ? date('d/m/Y', strtotime($stats['fecha_mas_antigua'])) : 'N/A'; ?></p></div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="managementTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="chart-tab" data-toggle="tab" href="#chart" role="tab" aria-controls="chart" aria-selected="true"><i class="fas fa-chart-bar mr-1"></i> Resumen por Mes</a></li>
            <li class="nav-item"><a class="nav-link" id="table-tab" data-toggle="tab" href="#table" role="tab" aria-controls="table" aria-selected="false"><i class="fas fa-list mr-1"></i> Gestión de Operaciones</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="managementTabContent">
            <div class="tab-pane fade show active" id="chart" role="tabpanel" aria-labelledby="chart-tab">
                <div style="height: 400px;"><canvas id="pendientesChart"></canvas></div>
                <?php if ($selectedSucursal === null): ?>
                <hr class="my-4"><h5 class="text-center mb-3">Ranking de Sucursales con más Pendientes (Top 10)</h5>
                <div style="height: 400px;"><canvas id="rankingChart"></canvas></div>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="table" role="tabpanel" aria-labelledby="table-tab">
                <table id="operacionesTable" class="table table-hover w-100">
                    <thead class="thead-light">
                        <tr><th>Fecha</th><th>Sucursal</th><th>N° Comprobante</th><th>Importe</th><th class="text-center">Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($operaciones)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No se encontraron operaciones con los filtros aplicados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($operaciones as $op): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($op['FECHA'])); ?></td>
                                    <td><?php echo htmlspecialchars($op['DESC_SUCURSAL']); ?></td>
                                    <td><?php echo htmlspecialchars($op['N_COMP']); ?></td>
                                    <td>$<?php echo number_format($op['IMPORTE'], 2, ',', '.'); ?></td>
                                    <td class="text-center"><a href="/comercial/auditoria/public/sucursal/justificar/<?php echo htmlspecialchars($op['N_COMP']); ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit mr-1"></i> Justificar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = "
<script>
$(document).ready(function() {
    // Lógica para inicializar DataTables solo cuando su pestaña se muestra
    var table = null;
    $('a[data-toggle=\"tab\"]').on('shown.bs.tab', function (e) {
        if (e.target.hash == '#table' && !$.fn.DataTable.isDataTable('#operacionesTable')) {
            table = $('#operacionesTable').DataTable({
                'language': { 'url': '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json' },
                'order': [[ 0, 'desc' ]]
            });
        }
    });

    // Código de los gráficos (se ejecuta siempre al cargar la página)
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

    if (document.getElementById('rankingChart')) {
        var ctxRanking = document.getElementById('rankingChart').getContext('2d');
        new Chart(ctxRanking, {
            type: 'bar',
            data: {
                labels: " . ($rankingLabels ?? '[]') . ",
                datasets: [{
                    label: 'N° de Pendientes',
                    data: " . ($rankingValues ?? '[]') . ",
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
";
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>