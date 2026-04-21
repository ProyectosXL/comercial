<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <div>
        <div class="breadcrumb-modern">
            <span>Operaciones</span>
            <i class="fas fa-chevron-right" style="font-size:10px; margin: 0 8px;"></i>
            <span>Logística</span>
        </div>
        <h1 style="margin-top:8px;">Gestión de Transferencias</h1>
        <p style="color:var(--gray-500); margin-top: 4px;">Monitoreo y finalización de solicitudes de mercadería</p>
    </div>
</div>

<div class="card-modern shadow-sm">
    <div class="card-body-modern">
        <table id="tablaLogistica" class="table-modern stripe hover" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Usuario</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Estado</th>
                    <th>N° Transf.</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitudes as $sol): ?>
                <tr>
                    <td data-order="<?php echo $sol['ID']; ?>" style="font-weight:700; color:var(--primary);">#<?php echo $sol['ID']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($sol['FECHA'])); ?></td>
                    <td>
                        <span style="font-size: 12px; font-weight: 500; color: var(--gray-600);">
                            <?php echo htmlspecialchars($sol['TIPO_TRANSFERENCIA']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($sol['USUARIO']); ?></td>
                    <td><span class="badge badge-light"><?php echo htmlspecialchars($sol['ORIGEN']); ?></span></td>
                    <td><span class="badge badge-light"><?php echo htmlspecialchars($sol['DESTINO']); ?></span></td>
                    <td>
                        <span class="badge-modern badge-<?php echo strtolower($sol['ESTADO']); ?>">
                            <?php echo $sol['ESTADO']; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($sol['NRO_TRANSFERENCIA']): ?>
                            <span style="font-weight:700; color:var(--success-dark);"><?php echo htmlspecialchars($sol['NRO_TRANSFERENCIA']); ?></span>
                        <?php else: ?>
                            <span style="color:var(--gray-400);">Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/comercial/SolicitudesTransferencias/public/solicitud/detalle/<?php echo $sol['ID']; ?>?mode=logistics" 
                           class="btn-modern btn-primary-modern btn-sm-modern" style="padding: 6px 12px;">
                            <i class="fas fa-eye mr-1"></i> Ver y Gestionar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$pageScripts = '
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $("#tablaLogistica").DataTable({
        "order": [[0, "desc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json"
        },
        "pageLength": 25,
        "dom": "<\'row\'<\'col-sm-12 col-md-6\'l><\'col-sm-12 col-md-6\'f>>" +
               "<\'row\'<\'col-sm-12\'tr>>" +
               "<\'row\'<\'col-sm-12 col-md-5\'i><\'col-sm-12 col-md-7\'p>>",
    });
});
</script>';

require_once __DIR__ . '/../layouts/footer.php'; 
?>
