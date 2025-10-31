<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h3>Justificar Operación</h3>
<hr>

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
                        <option value="<?php echo htmlspecialchars($motivo); ?>"><?php echo htmlspecialchars($motivo); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="observaciones"><strong>Observaciones Adicionales</strong></label>
                <textarea class="form-control" id="observaciones" name="observaciones" rows="4" placeholder="Añada cualquier detalle relevante aquí..."></textarea>
            </div>

            <!-- Nuevos campos para la aprobación de la supervisora y el adjunto -->
            <div class="form-group">
                <label for="aprobado_supervisora"><strong>¿Aprobado por supervisora?</strong></label>
                <select class="form-control" id="aprobado_supervisora" name="aprobado_supervisora">
                    <option value="No">No</option>
                    <option value="Sí">Sí</option>
                </select>
            </div>

            <div class="form-group" id="adjunto_container" style="display: none;">
                <label for="adjunto"><strong>Adjuntar comprobante (foto, PDF, etc.)</strong></label>
                <input type="file" class="form-control-file" id="adjunto" name="adjunto">
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
            // Limpiamos el valor del input de archivo si el usuario cambia a 'No'
            $('#adjunto').val(''); 
        }
    });
});
</script>
";
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; // Ahora el footer se incluye AL FINAL y usará la variable $pageScripts. ?>