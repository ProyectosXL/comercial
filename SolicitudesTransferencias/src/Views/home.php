<?php require_once __DIR__ . '/layouts/header.php'; ?>

<?php
    // Obtener conteos para las stats
    require_once __DIR__ . '/../Models/TransferenciaModel.php';
    $todas = TransferenciaModel::obtenerSolicitudes();
    $pendientes = array_filter($todas, function($s) { return $s['ESTADO'] === 'PENDIENTE'; });
    $finalizadas = array_filter($todas, function($s) { return $s['ESTADO'] === 'FINALIZADA'; });
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-exchange-alt mr-2" style="color:var(--primary);"></i>Panel de Solicitudes</h1>
        <p style="color:var(--gray-400);font-size:14px;margin:4px 0 0;">Gestión de solicitudes de transferencias de mercadería</p>
    </div>
    <a href="/comercial/SolicitudesTransferencias/public/solicitud/crear" class="btn-modern btn-primary-modern">
        <i class="fas fa-plus"></i> Nueva Solicitud
    </a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary);">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo count($todas); ?></div>
            <div class="stat-label">Total Solicitudes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--warning-bg);color:var(--warning-dark);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo count($pendientes); ?></div>
            <div class="stat-label">Pendientes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--success-bg);color:var(--success-dark);">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo count($finalizadas); ?></div>
            <div class="stat-label">Finalizadas</div>
        </div>
    </div>
</div>

<div class="card-modern">
    <div class="card-header-modern">
        <h2><i class="fas fa-list mr-2" style="color:var(--primary);"></i>Últimas Solicitudes</h2>
        <a href="/comercial/SolicitudesTransferencias/public/solicitud" class="btn-modern btn-outline-modern btn-sm-modern">
            Ver todas <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    <div class="card-body-modern" style="padding:0;">
        <?php if (empty($todas)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                <h3>No hay solicitudes aún</h3>
                <p>Crea tu primera solicitud de transferencia haciendo clic en "Nueva Solicitud"</p>
            </div>
        <?php else: ?>
            <table class="table-modern">
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
                    <?php 
                    $ultimas = array_slice($todas, 0, 10);
                    foreach ($ultimas as $sol): 
                        $fecha = $sol['FECHA'];
                        if ($fecha instanceof DateTime) {
                            $fecha = $fecha->format('d/m/Y H:i');
                        } else {
                            $fecha = date('d/m/Y H:i', strtotime($fecha));
                        }
                    ?>
                    <tr>
                        <td data-order="<?php echo $sol['ID']; ?>"><strong>#<?php echo $sol['ID']; ?></strong></td>
                        <td><?php echo $fecha; ?></td>
                        <td><?php echo htmlspecialchars($sol['TIPO_TRANSFERENCIA']); ?></td>
                        <td><?php echo htmlspecialchars($sol['USUARIO']); ?></td>
                        <td><?php echo htmlspecialchars($sol['ORIGEN']); ?></td>
                        <td><?php echo htmlspecialchars($sol['DESTINO']); ?></td>
                        <td>
                            <span class="badge-modern <?php echo $sol['ESTADO'] === 'PENDIENTE' ? 'badge-pendiente' : 'badge-finalizada'; ?>">
                                <?php echo $sol['ESTADO']; ?>
                            </span>
                        </td>
                        <td><?php echo $sol['NRO_TRANSFERENCIA'] ? htmlspecialchars($sol['NRO_TRANSFERENCIA']) : '<span style="color:var(--gray-400);">—</span>'; ?></td>
                        <td>
                             <a href="/comercial/SolicitudesTransferencias/public/solicitud/detalle/<?php echo $sol['ID']; ?>" 
                                class="btn-modern btn-outline-modern btn-sm-modern" title="Ver detalle">
                                 <i class="fas fa-eye"></i>
                             </a>
                             <button type="button" class="btn-modern btn-sm-modern ml-1" 
                                     style="background: #fee2e2; color: #ef4444; border: none;"
                                     onclick="eliminarSolicitud(<?php echo $sol['ID']; ?>)" title="Eliminar">
                                 <i class="fas fa-trash-alt"></i>
                             </button>
                         </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php 
$pageScripts = '<script>
$(document).ready(function() {
    $(".table-modern:has(tbody tr)").DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json"
        },
        order: [[0, "desc"]],
        pageLength: 10,
        dom: \'rt\',
    });
});

function eliminarSolicitud(id) {
    if (!confirm("¿Está seguro de que desea eliminar la solicitud #" + id + "? Esta acción no se puede deshacer.")) return;

    $.ajax({
        url: "/comercial/SolicitudesTransferencias/public/solicitud/eliminar",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({ id: id }),
        success: function(resp) {
            if (resp.success) {
                showToast("Solicitud eliminada correctamente", "success");
                location.reload();
            } else {
                showToast("Error al eliminar la solicitud", "error");
            }
        },
        error: function() {
            showToast("Error de conexión al intentar eliminar", "error");
        }
    });
}
</script>';

require_once __DIR__ . '/layouts/footer.php'; 
?>
