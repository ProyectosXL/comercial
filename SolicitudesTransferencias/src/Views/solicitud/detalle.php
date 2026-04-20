<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <div>
        <div class="breadcrumb-modern">
            <?php if (isset($isLogistica) && $isLogistica): ?>
                <a href="/comercial/SolicitudesTransferencias/public/logistica">Gestión Logística</a>
            <?php else: ?>
                <a href="/comercial/SolicitudesTransferencias/"><i class="fas fa-home"></i></a>
                <i class="fas fa-chevron-right" style="font-size:10px;"></i>
                <a href="/comercial/SolicitudesTransferencias/public/solicitud">Solicitudes</a>
            <?php endif; ?>
            <i class="fas fa-chevron-right" style="font-size:10px;"></i>
            <span>Detalle #<?php echo $solicitud['ID']; ?></span>
        </div>
        <h1 style="margin-top:8px;">Solicitud #<?php echo $solicitud['ID']; ?></h1>
    </div>
    <div>
        <span class="badge-modern <?php echo $solicitud['ESTADO'] === 'PENDIENTE' ? 'badge-pendiente' : 'badge-finalizada'; ?>" style="font-size:14px;padding:8px 16px;">
            <i class="fas fa-<?php echo $solicitud['ESTADO'] === 'PENDIENTE' ? 'clock' : 'check'; ?> mr-1"></i>
            <?php echo $solicitud['ESTADO']; ?>
        </span>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card-modern mb-4">
            <div class="card-header-modern">
                <h2><i class="fas fa-info-circle mr-2" style="color:var(--primary);"></i>Información General</h2>
            </div>
            <div class="card-body-modern">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td style="color:var(--gray-500);width:40%;">Fecha:</td>
                                <td><strong><?php 
                                    $fecha = $solicitud['FECHA'];
                                    echo $fecha instanceof DateTime ? $fecha->format('d/m/Y H:i') : date('d/m/Y H:i', strtotime($fecha)); 
                                ?></strong></td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500);">Tipo:</td>
                                <td><strong><?php echo htmlspecialchars($solicitud['TIPO_TRANSFERENCIA']); ?></strong></td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500);">Usuario:</td>
                                <td><strong><?php echo htmlspecialchars($solicitud['USUARIO']); ?></strong></td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500);">Creado por:</td>
                                <td><?php echo htmlspecialchars($solicitud['CREADO_POR']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td style="color:var(--gray-500);width:40%;">Origen:</td>
                                <td><strong><?php echo htmlspecialchars($solicitud['ORIGEN']); ?></strong></td>
                            </tr>
                            <tr>
                                <td style="color:var(--gray-500);">Destino:</td>
                                <td><strong><?php echo htmlspecialchars($solicitud['DESTINO']); ?></strong></td>
                            </tr>
                            <?php if ($solicitud['OBSERVACIONES']): ?>
                            <tr>
                                <td style="color:var(--gray-500);">Observaciones:</td>
                                <td><em><?php echo nl2br(htmlspecialchars($solicitud['OBSERVACIONES'])); ?></em></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-modern">
            <div class="card-header-modern">
                <h2><i class="fas fa-box-open mr-2" style="color:var(--primary);"></i>Artículos Solicitados</h2>
            </div>
            <div class="card-body-modern" style="padding:0;">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Rubro</th>
                            <th class="text-center">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $articulos = json_decode($solicitud['ARTICULOS'], true);
                        if (is_array($articulos)):
                            foreach ($articulos as $art): 
                        ?>
                        <tr>
                            <td><strong style="color:var(--primary);"><?php echo htmlspecialchars($art['cod_articu']); ?></strong></td>
                            <td><?php echo htmlspecialchars($art['descripcion']); ?></td>
                            <td><span style="color:var(--gray-500);font-size:12px;"><?php echo htmlspecialchars($art['rubro']); ?></span></td>
                            <td class="text-center"><strong><?php echo htmlspecialchars($art['cantidad']); ?></strong></td>
                        </tr>
                        <?php 
                            endforeach; 
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card-modern" style="position:sticky;top:88px;">
            <div class="card-header-modern">
                <h2><i class="fas fa-truck mr-2" style="color:var(--primary);"></i>Logística</h2>
            </div>
            <div class="card-body-modern">
                <p style="color:var(--gray-500);font-size:13px;margin-bottom:20px;">
                    Para procesar esta solicitud, ingrese el N° de Transferencia generado en XL-TANGO y haga clic en Finalizar.
                </p>

                <form id="formFinalizar">
                    <input type="hidden" id="solicitud_id" value="<?php echo $solicitud['ID']; ?>">
                    
                    <div class="form-group-modern">
                        <label>N° Transferencia</label>
                        <input type="text" id="nro_transferencia" class="form-control-modern" 
                               value="<?php echo htmlspecialchars($solicitud['NRO_TRANSFERENCIA'] ?? ''); ?>" 
                               <?php echo $solicitud['ESTADO'] === 'FINALIZADA' ? 'readonly' : ''; ?>
                               placeholder="Ej: 0001-00001234" required>
                    </div>

                    <?php if ($solicitud['ESTADO'] === 'PENDIENTE'): ?>
                    <button type="submit" class="btn-modern btn-success-modern w-100 justify-content-center" id="btnFinalizar" style="padding:12px;">
                        <i class="fas fa-check-circle"></i> Finalizar Solicitud
                    </button>
                    <?php else: ?>
                    <div style="background:var(--success-bg);color:var(--success-dark);padding:12px;border-radius:8px;text-align:center;font-size:13px;font-weight:600;">
                        <i class="fas fa-check-circle mr-2"></i> Procesada el <?php 
                            $fin = $solicitud['FINALIZADO_FECHA'];
                            echo $fin instanceof DateTime ? $fin->format('d/m/Y H:i') : date('d/m/Y H:i', strtotime($fin)); 
                        ?>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
$pageScripts = '<script>
$(document).ready(function() {
    $("#formFinalizar").on("submit", function(e) {
        e.preventDefault();
        
        let nro = $("#nro_transferencia").val().trim();
        let id = $("#solicitud_id").val();

        if (nro === "") {
            showToast("Debe ingresar el N° de Transferencia", "error");
            return;
        }

        if (confirm("¿Está seguro de finalizar esta solicitud con el N° de transferencia: " + nro + "? Esta acción no se puede deshacer y enviará un email de confirmación.")) {
            let btn = $("#btnFinalizar");
            btn.prop("disabled", true).html(\'<i class="fas fa-spinner fa-spin"></i> Finalizando...\');

            $.ajax({
                url: "/comercial/SolicitudesTransferencias/public/solicitud/finalizar",
                type: "POST",
                contentType: "application/json",
                data: JSON.stringify({ id: id, nro_transferencia: nro }),
                success: function(resp) {
                    if (resp.success) {
                        showToast(resp.message, "success");
                        setTimeout(() => location.reload(), 1500);
                    }
                },
                error: function(xhr) {
                    btn.prop("disabled", false).html(\'<i class="fas fa-check-circle"></i> Finalizar Solicitud\');
                    let err = xhr.responseJSON;
                    showToast((err && err.error) ? err.error : "Error de conexión", "error");
                }
            });
        }
    });
});
</script>';

require_once __DIR__ . '/../layouts/footer.php'; 
?>
