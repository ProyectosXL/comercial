<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
/* Estilos modernos premium para el formulario */
.form-section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 10px;
}
.form-section-title i {
    color: var(--primary);
}

/* Modificadores form-control modernos */
.form-group-premium {
    margin-bottom: 24px;
}
.form-label-premium {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-600);
    margin-bottom: 8px;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.input-premium {
    width: 100%;
    padding: 12px 16px;
    font-size: 14px;
    color: var(--gray-800);
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    transition: all 0.3s ease;
}
.input-premium:focus {
    background-color: #fff;
    border-color: #93c5fd;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    outline: none;
}
.input-premium:read-only, .input-premium:disabled {
    background-color: #f1f5f9;
    color: #64748b;
    cursor: not-allowed;
    border-color: transparent;
}
select.input-premium {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 40px;
}

/* Grilla Moderna de Articulos */
.articles-container {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.articles-header {
    display: grid;
    grid-template-columns: 2fr 3fr 2fr 1fr 50px;
    background: #f8fafc;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 600;
    font-size: 13px;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-radius: 12px 12px 0 0;
}
.article-row {
    display: grid;
    grid-template-columns: 2fr 3fr 2fr 1fr 50px;
    padding: 12px 20px;
    border-bottom: 1px solid #f1f5f9;
    align-items: center;
    transition: background-color 0.2s;
    gap: 16px;
}
.article-row:last-child {
    border-bottom: none;
}
.article-row:hover {
    background-color: #f8fafc;
}
.article-row:last-child {
    border-radius: 0 0 12px 12px;
}
.article-row.active-row {
    background-color: #eff6ff;
}
.article-input {
    width: 100%;
    border: 1px solid transparent;
    background: transparent;
    padding: 8px 12px;
    font-size: 14px;
    border-radius: 6px;
    transition: all 0.2s;
    color: var(--gray-800);
}
.article-input.codigo-input {
    background: #fff;
    border-color: #e2e8f0;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
}
.article-input:focus {
    background: #fff;
    border-color: #93c5fd;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
}
.article-input[readonly] {
    color: var(--gray-500);
    font-weight: 500;
}
.article-input[type="number"] {
    text-align: center;
    background: #fff;
    border-color: #e2e8f0;
}
.article-input[type="number"]:disabled {
    background: transparent;
    border-color: transparent;
    opacity: 0.5;
}
.btn-remove-row {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: #fee2e2;
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    opacity: 0;
}
.article-row:hover .btn-remove-row {
    opacity: 1;
}
.btn-remove-row:hover {
    background: #fecaca;
    color: #dc2626;
    transform: scale(1.05);
}

/* Autocomplete Moderno */
.relative-wrapper {
    position: relative;
    width: 100%;
}
.autocomplete-suggestions {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    z-index: 1050;
    max-height: 250px;
    overflow-y: auto;
    width: 100%;
    min-width: 350px;
    display: none;
}
.autocomplete-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    gap: 4px;
    transition: background 0.2s;
}
.autocomplete-item:last-child {
    border-bottom: none;
}
.autocomplete-item:hover, .autocomplete-item.active {
    background: #f8fafc;
}
.auto-title {
    font-weight: 700;
    color: var(--primary);
    font-size: 13px;
}
.auto-desc {
    color: var(--gray-700);
    font-size: 13px;
}
.auto-rubro {
    color: var(--gray-500);
    font-size: 11px;
    text-transform: uppercase;
}

/* Action Area */
.form-actions {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 16px;
}

.btn-add-row {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #f1f5f9;
    color: var(--gray-700);
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 16px;
    width: 100%;
    justify-content: center;
}
.btn-add-row:hover {
    background: #e2e8f0;
    color: var(--primary);
    border-color: var(--primary);
}
</style>

<div class="page-header">
    <div>
        <div class="breadcrumb-modern">
            <a href="/comercial/SolicitudesTransferencias/"><i class="fas fa-home"></i></a>
            <i class="fas fa-chevron-right" style="font-size:10px;"></i>
            <a href="/comercial/SolicitudesTransferencias/public/solicitud">Solicitudes</a>
            <i class="fas fa-chevron-right" style="font-size:10px;"></i>
            <span>Nueva Solicitud</span>
        </div>
        <h1 style="margin-top:8px;">Crear Transferencia</h1>
        <p style="color:var(--gray-500); margin-top: 4px;">Complete los datos para generar una nueva solicitud de logística</p>
    </div>
</div>

<form id="formSolicitud">
    <div class="row">
        <!-- Columna Izquierda: Información Principal -->
        <div class="col-lg-4 mb-4">
            <div class="card-modern h-100">
                <div class="card-body-modern">
                    <h3 class="form-section-title"><i class="fas fa-info-circle"></i> Datos Generales</h3>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group-premium">
                                <label class="form-label-premium">ID Solicitud</label>
                                <input type="text" class="input-premium" value="Auto-generado" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group-premium">
                                <label class="form-label-premium">Fecha</label>
                                <input type="text" class="input-premium" value="<?php echo date('d/m/Y'); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-premium">
                        <label class="form-label-premium">Tipo de Transferencia</label>
                        <select id="tipo_transferencia" class="input-premium" required>
                            <option value="" disabled selected>Seleccione un tipo...</option>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?php echo htmlspecialchars($t['VALOR']); ?>"><?php echo htmlspecialchars($t['VALOR']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-premium">
                        <label class="form-label-premium">Usuario Solicitante</label>
                        <select id="usuario" class="input-premium" required>
                            <option value="" disabled selected>Seleccionar usuario...</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?php echo htmlspecialchars($u['VALOR']); ?>"><?php echo htmlspecialchars($u['VALOR']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-premium">
                        <label class="form-label-premium">Origen</label>
                        <select id="origen" class="input-premium" required>
                            <option value="" disabled selected>Seleccionar origen...</option>
                            <?php foreach ($sucursales as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['VALOR']); ?>"><?php echo htmlspecialchars($s['VALOR']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-premium">
                        <label class="form-label-premium">Destino</label>
                        <select id="destino" class="input-premium" required>
                            <option value="" disabled selected>Seleccionar destino...</option>
                            <?php foreach ($sucursales as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['VALOR']); ?>"><?php echo htmlspecialchars($s['VALOR']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group-premium mb-0">
                        <label class="form-label-premium">Observaciones Adicionales</label>
                        <textarea id="observaciones" class="input-premium" rows="3" placeholder="Comentarios o indicaciones para logística..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Grilla de Articulos -->
        <div class="col-lg-8 mb-4">
            <div class="card-modern h-100" style="display: flex; flex-direction: column;">
                <div class="card-body-modern" style="flex-grow: 1;">
                    <h3 class="form-section-title"><i class="fas fa-box-open"></i> Detalle de Mercadería</h3>
                    <p style="color:var(--gray-500); font-size:13px; margin-bottom:16px;">
                        Ingrese el código del artículo. El sistema autocompletará la descripción y verificará disponibilidad.
                    </p>

                    <div class="articles-container">
                        <div class="articles-header">
                            <div>Código Artículo</div>
                            <div>Descripción</div>
                            <div>Rubro</div>
                            <div style="text-align:center;">Cant.</div>
                            <div></div>
                        </div>
                        <div id="articles-body">
                            <!-- Fila Inicial -->
                            <div class="article-row active-row">
                                <div class="relative-wrapper">
                                    <input type="text" class="article-input codigo-input" placeholder="Ej: XV5..." autocomplete="off">
                                    <div class="autocomplete-suggestions"></div>
                                </div>
                                <div><input type="text" class="article-input input-descripcion" placeholder="-" readonly tabindex="-1"></div>
                                <div><input type="text" class="article-input input-rubro" placeholder="-" readonly tabindex="-1"></div>
                                <div><input type="number" class="article-input input-cantidad" min="1" disabled placeholder="0"></div>
                                <div>
                                    <button type="button" class="btn-remove-row" title="Eliminar fila"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="articles-header" style="background:#f1f5f9; border-top:1px solid #e2e8f0; border-radius:0 0 12px 12px; grid-template-columns: 2fr 3fr 2fr 1fr 50px;">
                            <div style="grid-column: 1/4; text-align:right; padding-right:16px;">TOTAL ARTÍCULOS:</div>
                            <div style="text-align:center; font-weight:800; color:var(--primary); font-size:16px;" id="totalArticulos">0</div>
                            <div></div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-add-row" id="btnAddRow">
                        <i class="fas fa-plus-circle"></i> Agregar otra línea
                    </button>
                </div>
                
                <div class="card-body-modern" style="background:#f8fafc; border-top:1px solid #e2e8f0; border-radius:0 0 16px 16px;">
                    <div class="form-actions" style="margin-top:0; padding-top:0; border-top:none;">
                        <a href="/comercial/SolicitudesTransferencias/public/solicitud" class="btn-modern text-muted" style="background:transparent;">
                            Cancelar
                        </a>
                        <button type="submit" class="btn-modern btn-primary-modern" id="btnGuardar" style="padding: 12px 32px; font-size:15px;">
                            <i class="fas fa-paper-plane mr-2"></i> Enviar Solicitud
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php 
$pageScripts = '<script>
$(document).ready(function() {
    // ----------------------------------------------------
    // Lógica Destino Automático
    // ----------------------------------------------------
    $("#tipo_transferencia").on("change", function() {
        if ($(this).val() === "Retiros y Obsequios") {
            $("#destino").val("07-Retiros");
            // Efecto visual para destacar el cambio automático
            $("#destino").addClass("highlight-pulse");
            setTimeout(() => $("#destino").removeClass("highlight-pulse"), 1000);
        }
    });

    // ----------------------------------------------------
    // Lógica Dinámica de la Grilla de Artículos
    // ----------------------------------------------------
    const $body = $("#articles-body");

    // Efecto de foco en filas
    $body.on("focusin", ".article-input", function() {
        $(".article-row").removeClass("active-row");
        $(this).closest(".article-row").addClass("active-row");
    });

    // Función para crear nueva fila
    function addNewRow() {
        const resetRow = `
            <div class="article-row">
                <div class="relative-wrapper">
                    <input type="text" class="article-input codigo-input" placeholder="Buscar código..." autocomplete="off">
                    <div class="autocomplete-suggestions"></div>
                </div>
                <div><input type="text" class="article-input input-descripcion" placeholder="-" readonly tabindex="-1"></div>
                <div><input type="text" class="article-input input-rubro" placeholder="-" readonly tabindex="-1"></div>
                <div><input type="number" class="article-input input-cantidad" min="1" disabled placeholder="0"></div>
                <div>
                    <button type="button" class="btn-remove-row" title="Eliminar fila"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
        `;
        $body.append(resetRow);
        const $newInput = $body.find(".article-row").last().find(".codigo-input");
        $newInput.focus();
    }

    $("#btnAddRow").on("click", function() {
        addNewRow();
    });

    // Eliminar fila
    $body.on("click", ".btn-remove-row", function() {
        if ($(".article-row").length > 1) {
            $(this).closest(".article-row").fadeOut(200, function() { 
                $(this).remove(); 
                actualizarTotal();
            });
        } else {
            // Si es la última, solo limpiarla
            const $row = $(this).closest(".article-row");
            $row.find("input").val("");
            $row.find(".input-cantidad").prop("disabled", true);
            $row.find(".codigo-input").focus();
            actualizarTotal();
        }
    });

    // Actualizar total cuando cambia la cantidad
    $body.on("input change", ".input-cantidad", function() {
        actualizarTotal();
    });

    function actualizarTotal() {
        let total = 0;
        $(".input-cantidad").each(function() {
            let val = parseInt($(this).val()) || 0;
            total += val;
        });
        $("#totalArticulos").text(total);
    }

    // Enter en cantidad avanza a nueva linea
    $body.on("keydown", ".input-cantidad", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            const $currentRow = $(this).closest(".article-row");
            const $nextRow = $currentRow.next(".article-row");
            if ($nextRow.length) {
                $nextRow.find(".codigo-input").focus();
            } else {
                addNewRow();
            }
        }
    });

    // ----------------------------------------------------
    // Autocomplete con AJAX
    // ----------------------------------------------------
    let timeoutId;
    $body.on("input", ".codigo-input", function() {
        const $input = $(this);
        const $row = $input.closest(".article-row");
        const query = $input.val().trim();
        const $suggestions = $row.find(".autocomplete-suggestions");

        // Limpiar si el código es muy corto
        if (query.length < 3) {
            $suggestions.hide();
            $row.find(".input-descripcion").val("");
            $row.find(".input-rubro").val("");
            $row.find(".input-cantidad").val("").prop("disabled", true);
            return;
        }

        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            $.ajax({
                url: "/comercial/SolicitudesTransferencias/public/solicitud/buscarArticulos",
                data: { q: query },
                success: function(data) {
                    $suggestions.empty();
                    if (data && data.length > 0) {
                        data.forEach(item => {
                            $suggestions.append(`
                                <div class="autocomplete-item" data-cod="${item.COD_ARTICU}" data-desc="${item.DESCRIPCION}" data-rubro="${item.RUBRO}">
                                    <div class="auto-title">${item.COD_ARTICU}</div>
                                    <div class="auto-desc">${item.DESCRIPCION}</div>
                                    <div class="auto-rubro"><i class="fas fa-tag mr-1"></i>${item.RUBRO}</div>
                                </div>
                            `);
                        });
                        $suggestions.slideDown(150);
                    } else {
                        $suggestions.hide();
                    }
                }
            });
        }, 300);
    });

    // Cerrar sugerencias al hacer clic fuera
    $(document).on("click", function(e) {
        if (!$(e.target).closest(".relative-wrapper").length) {
            $(".autocomplete-suggestions").hide();
        }
    });

    // Seleccionar Item
    $body.on("click", ".autocomplete-item", function() {
        const $item = $(this);
        const $row = $item.closest(".article-row");
        
        $row.find(".codigo-input").val($item.data("cod"));
        $row.find(".input-descripcion").val($item.data("desc"));
        $row.find(".input-rubro").val($item.data("rubro"));
        
        $row.find(".autocomplete-suggestions").hide();
        
        const $inputCant = $row.find(".input-cantidad");
        $inputCant.prop("disabled", false).val(1).focus().select();
        actualizarTotal();
    });

    // ----------------------------------------------------
    // Envío del Formulario
    // ----------------------------------------------------
    $("#formSolicitud").on("submit", function(e) {
        e.preventDefault();
        
        const $btn = $("#btnGuardar");
        const prevHtml = $btn.html();
        $btn.prop("disabled", true).html(\'<i class="fas fa-circle-notch fa-spin mr-2"></i> Procesando Validación...\');

        let articulos = [];
        let hasErrors = false;

        $(".article-row").each(function() {
            const cod = $(this).find(".codigo-input").val().trim();
            const desc = $(this).find(".input-descripcion").val();
            const rubro = $(this).find(".input-rubro").val();
            const cant = $(this).find(".input-cantidad").val();

            if (cod !== "") {
                if (!cant || cant <= 0) {
                    showToast("Atención: Indique la cantidad para el artículo " + cod, "warning");
                    $(this).find(".input-cantidad").focus();
                    hasErrors = true;
                    return false;
                }
                articulos.push({
                    cod_articu: cod,
                    descripcion: desc,
                    rubro: rubro,
                    cantidad: parseInt(cant)
                });
            }
        });

        if (hasErrors) {
            $btn.prop("disabled", false).html(prevHtml);
            return;
        }

        if (articulos.length === 0) {
            showToast("La solicitud debe contener al menos un artículo válido.", "error");
            $btn.prop("disabled", false).html(prevHtml);
            return;
        }

        const data = {
            tipo_transferencia: $("#tipo_transferencia").val(),
            usuario: $("#usuario").val(),
            origen: $("#origen").val(),
            destino: $("#destino").val(),
            observaciones: $("#observaciones").val(),
            articulos: articulos
        };

        $.ajax({
            url: "/comercial/SolicitudesTransferencias/public/solicitud/guardar",
            type: "POST",
            contentType: "application/json",
            timeout: 60000, // 60 segundos por los correos
            data: JSON.stringify(data),
            success: function(resp) {
                if (resp.success) {
                    $btn.removeClass("btn-primary-modern").addClass("btn-success-modern")
                        .html(\'<i class="fas fa-check-circle mr-2"></i> Solicitud Creada\');
                    showToast(resp.message, "success");
                    
                    // Transición suave
                    setTimeout(() => {
                        window.location.href = "/comercial/SolicitudesTransferencias/public/solicitud";
                    }, 1500);
                }
            },
                error: function(xhr) {
                    $btn.prop("disabled", false).html(prevHtml);
                    const err = xhr.responseJSON;
                    if (err && err.error) {
                        showToast(err.error, "error", true); // Sticky para que vean el error
                        if (err.detalle) {
                            err.detalle.forEach(msg => showToast(msg, "warning", true)); // Sticky para ver talles/cantidades
                        }
                    } else {
                        showToast("Ups, ocurrió un error de conexión inesperado.", "error");
                    }
                }
        });
    });
});
</script>
<style>
.highlight-pulse {
    animation: flashInfo 1s ease;
}
@keyframes flashInfo {
    0% { background-color: #fff; }
    50% { background-color: #dbeafe; border-color: #3b82f6; }
    100% { background-color: #f8fafc; }
}
</style>
';

require_once __DIR__ . '/../layouts/footer.php'; 
?>
