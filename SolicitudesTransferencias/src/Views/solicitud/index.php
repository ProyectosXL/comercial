<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
/* Estilos adicionales para DataTables */
.table-controls-custom {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-200);
}
.table-footer-custom {
    padding: 16px 24px;
    border-top: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border-radius: 0 0 16px 16px;
}
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    padding: 6px 12px;
    outline: none;
    transition: 0.2s;
}
.dataTables_wrapper .dataTables_filter input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241, 0.1);
}
.dataTables_wrapper .dataTables_length select {
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    padding: 6px 30px 6px 12px;
    outline: none;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 4px 12px !important;
    border-radius: 6px !important;
    border: none !important;
    margin: 0 2px;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--primary) !important;
    color: white !important;
    box-shadow: 0 2px 4px rgba(99,102,241, 0.2);
}
.dataTables_wrapper .dataTables_paginate .paginate_button:not(.current):hover {
    background: var(--gray-100) !important;
    color: var(--primary) !important;
}
</style>

<div class="page-header" style="align-items: flex-end;">
    <div>
        <div class="breadcrumb-modern">
            <a href="/comercial/SolicitudesTransferencias/"><i class="fas fa-home"></i></a>
            <i class="fas fa-chevron-right" style="color:var(--gray-400); font-size:10px;"></i>
            <span>Solicitudes</span>
        </div>
        <h1 style="margin-top:12px; font-weight: 800; font-size: 1.8rem;">Todas las Solicitudes</h1>
        <p style="color:var(--gray-500); margin-top:4px;">Historial completo de movimientos de transferencias</p>
    </div>
    <a href="/comercial/SolicitudesTransferencias/public/solicitud/crear" class="btn-modern btn-primary-modern shadow-sm" style="padding: 12px 24px; font-weight: 600;">
        <i class="fas fa-plus mr-2"></i> Nueva Solicitud
    </a>
</div>

<div class="card-modern">
    <div class="card-header-modern">
        <h2><i class="fas fa-clipboard-list mr-2" style="color:var(--primary);"></i>Solicitudes de Transferencia</h2>
        <div style="display:flex;gap:8px;">
            <button class="btn-modern btn-outline-modern btn-sm-modern filter-btn active" data-filter="all">
                Todas
            </button>
            <button class="btn-modern btn-outline-modern btn-sm-modern filter-btn" data-filter="PENDIENTE" style="border-color:#f59e0b;">
                <i class="fas fa-clock" style="color:#f59e0b;"></i> Pendientes
            </button>
            <button class="btn-modern btn-outline-modern btn-sm-modern filter-btn" data-filter="FINALIZADA" style="border-color:#10b981;">
                <i class="fas fa-check" style="color:#10b981;"></i> Finalizadas
            </button>
        </div>
    </div>
    <div class="card-body-modern" style="padding:0;">
        <?php if (empty($solicitudes)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                <h3>No hay solicitudes</h3>
                <p>Crea tu primera solicitud de transferencia</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table id="tablaSolicitudes" class="table-modern" style="width:100%;">
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
                        <?php foreach ($solicitudes as $sol): 
                            $fecha = $sol['FECHA'];
                            if ($fecha instanceof DateTime) {
                                $fecha = $fecha->format('d/m/Y H:i');
                            } else {
                                $fecha = date('d/m/Y H:i', strtotime($fecha));
                            }
                        ?>
                        <tr data-estado="<?php echo $sol['ESTADO']; ?>" style="vertical-align: middle;">
                            <td><strong style="color:var(--primary); font-size:14px;">#<?php echo $sol['ID']; ?></strong></td>
                            <td class="text-muted"><i class="far fa-calendar-alt mr-1"></i><?php echo $fecha; ?></td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:8px; font-weight: 500;">
                                    <?php 
                                    $tipoIcon = 'fas fa-exchange-alt';
                                    if ($sol['TIPO_TRANSFERENCIA'] === 'Retiros y Obsequios') $tipoIcon = 'fas fa-gift';
                                    elseif ($sol['TIPO_TRANSFERENCIA'] === 'Muestras') $tipoIcon = 'fas fa-box-open';
                                    elseif ($sol['TIPO_TRANSFERENCIA'] === 'Fotos/Prensa') $tipoIcon = 'fas fa-camera';
                                    ?>
                                    <div style="background:var(--gray-100); width:28px; height:28px; border-radius:6px; display:flex; align-items:center; justify-content:center;">
                                        <i class="<?php echo $tipoIcon; ?>" style="color:var(--gray-500);font-size:12px;"></i>
                                    </div>
                                    <?php echo htmlspecialchars($sol['TIPO_TRANSFERENCIA']); ?>
                                </span>
                            </td>
                            <td style="font-weight:500; color:var(--gray-700);"><?php echo htmlspecialchars($sol['USUARIO']); ?></td>
                            <td><span style="background:var(--gray-100); padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600; color:var(--gray-600);"><?php echo htmlspecialchars($sol['ORIGEN']); ?></span></td>
                            <td><span style="background:var(--gray-100); padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600; color:var(--gray-600);"><?php echo htmlspecialchars($sol['DESTINO']); ?></span></td>
                            <td>
                                <span class="badge-modern <?php echo $sol['ESTADO'] === 'PENDIENTE' ? 'badge-pendiente' : 'badge-finalizada'; ?>" style="font-size:11px; padding: 6px 12px;">
                                    <i class="fas fa-<?php echo $sol['ESTADO'] === 'PENDIENTE' ? 'clock' : 'check'; ?> mr-1" style="font-size:10px;"></i>
                                    <?php echo $sol['ESTADO']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($sol['NRO_TRANSFERENCIA']): ?>
                                    <strong style="color:var(--success-dark); background:var(--success-bg); padding:4px 8px; border-radius:4px; font-size:12px;"><i class="fas fa-hashtag mr-1"></i><?php echo htmlspecialchars($sol['NRO_TRANSFERENCIA']); ?></strong>
                                <?php else: ?>
                                    <span style="color:var(--gray-400);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/comercial/SolicitudesTransferencias/public/solicitud/detalle/<?php echo $sol['ID']; ?>" 
                                   class="btn-modern btn-outline-modern" style="padding:6px 12px; font-size:12px;" title="Ver detalle">
                                     <i class="fas fa-eye shadow-sm"></i> Ver
                                 </a>
                                 <button type="button" class="btn-modern ml-1" 
                                         style="padding:6px 12px; font-size:12px; background: #fee2e2; color: #ef4444; border: none; border-radius: 10px;"
                                         onclick="eliminarSolicitud(<?php echo $sol['ID']; ?>)" title="Eliminar">
                                     <i class="fas fa-trash-alt"></i>
                                 </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$pageScripts = '<script>
$(document).ready(function() {
    var table = $("#tablaSolicitudes").DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json"
        },
        order: [[0, "desc"]],
        pageLength: 25,
        dom: \'<"table-controls-custom d-flex justify-content-between align-items-center"lf>rt<"table-footer-custom"ip>\',
    });

    // Filtros por estado
    $(".filter-btn").on("click", function() {
        $(".filter-btn").removeClass("active").css("background", "");
        $(this).addClass("active");

        var filter = $(this).data("filter");
        if (filter === "all") {
            table.column(6).search("").draw();
        } else {
            table.column(6).search(filter).draw();
        }
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

require_once __DIR__ . '/../layouts/footer.php'; 
?>
