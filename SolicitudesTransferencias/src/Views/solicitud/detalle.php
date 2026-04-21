<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
/* Estilos para el buscador automático en el modal */
.relative-wrapper {
    position: relative;
    width: 100%;
}
.autocomplete-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    z-index: 3000;
    max-height: 200px;
    overflow-y: auto;
    display: none;
}
.autocomplete-item {
    padding: 10px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
}
.autocomplete-item:hover {
    background: #f8fafc;
}
.auto-title {
    font-weight: 700;
    color: var(--primary);
    font-size: 13px;
}
.auto-desc {
    color: var(--gray-700);
    font-size: 12px;
}

/* Modal adjustments */
.modal-backdrop-modern.show {
    display: flex !important;
}
</style>

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
                            <tr>
                                <td style="color:var(--gray-500);">Observaciones:</td>
                                <td><em><?php echo nl2br(htmlspecialchars($solicitud['OBSERVACIONES'])); ?></em></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-modern">
            <div class="card-header-modern">
                <h2><i class="fas fa-box-open mr-2" style="color:var(--primary);"></i>Artículos Solicitados</h2>
            </div>
            <div class="card-body-modern p-0">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Rubro</th>
                            <th class="text-center">Cantidad</th>
                            <?php if (isset($isLogistica) && $isLogistica && $solicitud['ESTADO'] === 'PENDIENTE'): ?>
                                <th class="text-center">Editar</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $articulos = json_decode($solicitud['ARTICULOS'], true);
                        if (is_array($articulos)):
                            $index = 0;
                            foreach ($articulos as $art): 
                        ?>
                        <tr>
                            <td><strong style="color:var(--primary);"><?php echo htmlspecialchars($art['cod_articu']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($art['descripcion']); ?>
                                <?php if (!empty($art['obs_logistica'])): ?>
                                    <div style="margin-top:4px; font-size:11px; padding:4px 8px; background:#fff7ed; border-left:2px solid #f97316; border-radius:4px; color:#9a3412;">
                                        <i class="fas fa-edit mr-1"></i> <strong>Cambio:</strong> <?php echo htmlspecialchars($art['obs_logistica']); ?>
                                        <br><span style="color:#c2410c; opacity:0.8;">(Reemplaza a <?php echo htmlspecialchars($art['reemplaza_a']); ?> el <?php echo $art['cambiado_fecha']; ?>)</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span style="color:var(--gray-500);font-size:12px;"><?php echo htmlspecialchars($art['rubro'] ?? '-'); ?></span></td>
                            <td class="text-center"><strong><?php echo htmlspecialchars($art['cantidad']); ?></strong></td>
                            <?php if (isset($isLogistica) && $isLogistica && $solicitud['ESTADO'] === 'PENDIENTE'): ?>
                                <td class="text-center">
                                    <button class="btn-modern btn-outline-modern btn-sm-modern" 
                                            onclick="abrirModalCambio(<?php echo $index; ?>, '<?php echo $art['cod_articu']; ?>')" 
                                            title="Cambiar artículo" style="padding:4px 8px;">
                                        <i class="fas fa-pencil-alt" style="color:var(--primary);"></i>
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php 
                            $index++;
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

<!-- Modal para cambiar artículo -->
<div class="modal-backdrop-modern" id="modalArticulo" style="z-index: 2500;">
    <div class="modal-modern" style="max-width: 550px;">
        <div class="modal-header-modern">
            <h2 id="modalTitle">Cambiar Artículo</h2>
            <button type="button" class="btn-modern" onclick="cerrarModal()" style="padding:4px; background:none; border:none; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body-modern" style="padding:24px;">
            <input type="hidden" id="edit_index">
            
            <div class="form-group-modern">
                <label>Artículo Original</label>
                <input type="text" id="art_original" class="form-control-modern" readonly style="background:#f8fafc;">
            </div>

            <div class="form-group-modern">
                <label>Nuevo Artículo</label>
                <div class="relative-wrapper">
                    <input type="text" id="nuevo_cod_articu" class="form-control-modern" placeholder="Escriba código para buscar..." autocomplete="off">
                    <div id="sugerencias_modal" class="autocomplete-suggestions"></div>
                </div>
            </div>

            <div id="info_nuevo_art" style="display:none; margin-bottom:20px; padding:12px; background:var(--primary-bg); border-radius:8px;">
                <div style="font-weight:700; color:var(--primary);" id="disp_cod"></div>
                <div style="font-size:13px;" id="disp_desc"></div>
            </div>

            <div class="form-group-modern">
                <label>Observación del Cambio (Obligatorio)</label>
                <textarea id="obs_cambio" class="form-control-modern" rows="3" placeholder="Indique el motivo del cambio..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <button type="button" class="btn-modern btn-outline-modern" onclick="cerrarModal()">Cancelar</button>
                <button type="button" class="btn-modern btn-primary-modern" id="btnGuardarCambio" onclick="guardarCambioArticulo()" disabled>
                    <i class="fas fa-save mr-2"></i> Guardar Cambio
                </button>
            </div>
        </div>
    </div>
</div>

<?php 
$pageScripts = '<script>
$(document).ready(function() {
    let selectedArticle = null;

    window.abrirModalCambio = function(index, originalCod) {
        $("#edit_index").val(index);
        $("#art_original").val(originalCod);
        $("#nuevo_cod_articu").val("");
        $("#obs_cambio").val("");
        $("#info_nuevo_art").hide();
        $("#btnGuardarCambio").prop("disabled", true);
        selectedArticle = null;
        $("#modalArticulo").addClass("show");
    };

    window.cerrarModal = function() {
        $("#modalArticulo").removeClass("show");
    };

    let timeoutId;
    $("#nuevo_cod_articu").on("input", function() {
        const query = $(this).val().trim();
        const $suggestions = $("#sugerencias_modal");

        if (query.length < 3) {
            $suggestions.hide();
            return;
        }

        clearTimeout(timeoutId);
        timeoutId = setTimeout(function() {
            $.ajax({
                url: "/comercial/SolicitudesTransferencias/public/solicitud/buscarArticulos",
                data: { q: query },
                success: function(data) {
                    $suggestions.empty();
                    if (data && data.length > 0) {
                        data.forEach(function(item) {
                            let div = $(\'<div>\').addClass(\'autocomplete-item select-item-modal\')
                                .attr(\'data-cod\', item.COD_ARTICU)
                                .attr(\'data-desc\', item.DESCRIPCION)
                                .attr(\'data-rubro\', item.RUBRO)
                                .html(\'<div class="auto-title">\' + item.COD_ARTICU + \'</div><div class="auto-desc">\' + item.DESCRIPCION + \'</div>\');
                            $suggestions.append(div);
                        });
                        $suggestions.show();
                    } else {
                        $suggestions.hide();
                    }
                }
            });
        }, 300);
    });

    $(document).on("click", ".select-item-modal", function() {
        const cod = $(this).data("cod");
        const desc = $(this).data("desc");
        const rubro = $(this).data("rubro");
        
        selectedArticle = { cod_articu: cod, descripcion: desc, rubro: rubro };
        
        $("#nuevo_cod_articu").val(cod);
        $("#disp_cod").text(cod);
        $("#disp_desc").text(desc);
        $("#info_nuevo_art").fadeIn();
        $("#sugerencias_modal").hide();
        validarFormCambio();
    });

    $("#obs_cambio").on("input", function() {
        validarFormCambio();
    });

    function validarFormCambio() {
        const obs = $("#obs_cambio").val().trim();
        $("#btnGuardarCambio").prop("disabled", !(selectedArticle && obs.length > 5));
    }

    window.guardarCambioArticulo = function() {
        const data = {
            id: $("#solicitud_id").val(),
            index: $("#edit_index").val(),
            nuevoArticulo: selectedArticle,
            observacion: $("#obs_cambio").val().trim()
        };

        $("#btnGuardarCambio").prop("disabled", true).html(\'<i class="fas fa-spinner fa-spin"></i> Guardando...\');

        $.ajax({
            url: "/comercial/SolicitudesTransferencias/public/solicitud/cambiarArticulo",
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify(data),
            success: function(resp) {
                if (resp.success) {
                    showToast("Artículo actualizado correctamente", "success");
                    location.reload();
                }
            },
            error: function(xhr) {
                $("#btnGuardarCambio").prop("disabled", false).html(\'<i class="fas fa-save mr-2"></i> Guardar Cambio\');
                const err = xhr.responseJSON;
                showToast(err ? err.error : "Error al actualizar", "error");
            }
        });
    };

    $("#formFinalizar").on("submit", function(e) {
        e.preventDefault();
        let nro = $("#nro_transferencia").val().trim();
        let id = $("#solicitud_id").val();

        if (nro === "") {
            showToast("Debe ingresar el N° de Transferencia", "error");
            return;
        }

        if (confirm("¿Está seguro de finalizar esta solicitud?")) {
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
