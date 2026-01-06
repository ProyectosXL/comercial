<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h3>Justificar Operación</h3>
<hr>

<!-- ALERTA DE RECHAZO: Solo aparece si estamos corrigiendo -->
<?php if (isset($justificacionPrevia) && $justificacionPrevia['estado'] === 'Rechazado'): ?>
    <div class="alert alert-warning border-warning shadow-sm">
        <h4><i class="fas fa-exclamation-triangle"></i> Solicitud Rechazada</h4>
        <p>Esta justificación fue rechazada anteriormente. Los datos han sido precargados para que pueda corregirlos y volver a enviarla.</p>
        <?php if (!empty($justificacionPrevia['observaciones_rechazo'])): // Opcional: si guardas el motivo del rechazo en BD ?>
            <hr>
            <strong>Motivo del rechazo:</strong> <?php echo htmlspecialchars($justificacionPrevia['observaciones_rechazo']); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Tarjeta con la información de la operación -->
<div class="card mb-4">
    <div class="card-header">
        <strong>Detalles de la Operación a Justificar</strong>
    </div>
    <div class="card-body">
        <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($operacion['FECHA'])); ?></p>
        <p><strong>Sucursal:</strong> <?php echo htmlspecialchars($operacion['NRO_SUCURS'] . ' - ' . $operacion['DESC_SUCURSAL']); ?></p>
        <p><strong>N° Comprobante:</strong> <?php echo htmlspecialchars($operacion['N_COMP']); ?></p>
        <p><strong>Importe:</strong> $<?php echo number_format($operacion['IMPORTE'], 2, ',', '.'); ?></p>
    </div>
</div>

<!-- Formulario de justificación -->
<div class="card">
    <div class="card-header">
        <strong>Formulario de Justificación</strong>
    </div>
    <div class="card-body">
        <!-- ¡IMPORTANTE! Se añade enctype="multipart/form-data" para permitir la subida de archivos -->
        <form action="/comercial/auditoria/public/sucursal/guardar" method="POST" enctype="multipart/form-data">
            
            <!-- Campos ocultos con la info de la operación que necesitamos guardar -->
            <input type="hidden" name="n_comp" value="<?php echo htmlspecialchars($operacion['N_COMP']); ?>">
            <input type="hidden" name="nro_sucursal" value="<?php echo htmlspecialchars($operacion['NRO_SUCURS']); ?>">
            <input type="hidden" name="fecha_operacion" value="<?php echo $operacion['FECHA']; ?>">
            <input type="hidden" name="importe" value="<?php echo $operacion['IMPORTE']; ?>">

            <div class="form-group">
                <label for="motivo"><strong>Motivo <span class="text-danger">*</span></strong></label>
                <select class="form-control" id="motivo" name="motivo" required>
                    <option value="">-- Seleccione un motivo --</option>
                    <?php foreach ($motivos as $motivo): ?>
                        <?php 
                            // Lógica para preseleccionar si venimos de un rechazo
                            $selected = (isset($justificacionPrevia['motivo']) && $justificacionPrevia['motivo'] == $motivo) ? 'selected' : ''; 
                        ?>
                        <option value="<?php echo htmlspecialchars($motivo); ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($motivo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="observaciones"><strong>Observaciones Adicionales</strong></label>
                <!-- Lógica para rellenar el texto anterior -->
                <textarea class="form-control" id="observaciones" name="observaciones" rows="4" placeholder="Añada cualquier detalle relevante aquí..."><?php echo isset($justificacionPrevia['observaciones']) ? htmlspecialchars($justificacionPrevia['observaciones']) : ''; ?></textarea>
            </div>

            <!-- Nuevos campos para la aprobación de la supervisora y el adjunto -->
            <div class="form-group">
                <label for="aprobado_supervisora"><strong>¿Aprobado por supervisora?</strong></label>
                <select class="form-control" id="aprobado_supervisora" name="aprobado_supervisora">
                    <?php 
                        $selNo = (isset($justificacionPrevia['aprobado_supervisora']) && $justificacionPrevia['aprobado_supervisora'] == 'No') ? 'selected' : '';
                        $selSi = (isset($justificacionPrevia['aprobado_supervisora']) && $justificacionPrevia['aprobado_supervisora'] == 'Sí') ? 'selected' : '';
                    ?>
                    <option value="No" <?php echo $selNo; ?>>No</option>
                    <option value="Sí" <?php echo $selSi; ?>>Sí</option>
                </select>
            </div>

            <div class="form-group" id="adjunto_container" style="display: none;">
                <label for="adjunto"><strong>Adjuntar comprobante (foto, PDF, etc.)</strong></label>
                <!-- Nota: No se puede precargar un input file por seguridad. Si ya había uno, se debe subir de nuevo o manejar en backend que si viene vacío conserve el anterior -->
                <input type="file" class="form-control-file" id="adjunto" name="adjunto">
                <?php if (!empty($justificacionPrevia['path_adjunto'])): ?>
                    <small class="text-info"><i class="fas fa-info-circle"></i> Ya existe un archivo adjunto previo. Si no selecciona uno nuevo, se mantendrá el anterior.</small>
                <?php endif; ?>
            </div>

            <a href="/comercial/auditoria/public/sucursal/pendientes" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check"></i> Enviar Justificación
            </button>
        </form>
    </div>
</div>

<?php
// Definimos los scripts específicos para esta página ANTES de llamar al footer
$pageScripts = "
<script>
$(document).ready(function() {
    // Lógica para mostrar/ocultar el campo de adjunto
    $('#aprobado_supervisora').on('change', function() {
        if (this.value === 'Sí') {
            $('#adjunto_container').slideDown();
        } else {
            $('#adjunto_container').slideUp();
            $('#adjunto').val(''); 
        }
    });

    // --- NUEVO: Disparar el evento change al cargar la página ---
    // Esto hace que si cargamos datos previos y dice 'Sí', aparezca el campo de adjunto inmediatamente.
    $('#aprobado_supervisora').trigger('change');

    // --- Prevenir doble envío ---
    $('form').on('submit', function() {
        var btn = $(this).find('button[type=\"submit\"]');
        // Cambiar texto y deshabilitar botón
        btn.prop('disabled', true);
        btn.html('<i class=\"fas fa-spinner fa-spin\"></i> Guardando...');
    });
});
</script>
";
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>