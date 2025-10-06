$(document).ready(function() {
    // Actualizar nombre del archivo seleccionado en el modal de importación
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Elegir archivo...');
    });

    // Resetear el modal de agregar/editar al abrirlo
    $('#stockModal').on('show.bs.modal', function (event) {
        // Solo resetea si el botón que lo abrió NO es un botón de editar
        if (!$(event.relatedTarget).hasClass('edit-btn')) {
            $('#stock-form')[0].reset();
            $('#form-action').val('add');
            // Habilitar selects que podrían estar deshabilitados por una edición previa
            $('#codigo_sucursal').prop('disabled', false);
            $('#id_rubro').prop('disabled', false);
            $('#submit-button').html('<i class="fas fa-save"></i> Guardar');
            $('#stockModalLabel').html('<i class="fas fa-plus-circle"></i> Agregar Stock Base');
        }
    });

    // Botón para enviar el formulario del modal (Agregar/Editar)
    $('#submit-button').click(function() {
        // Usamos la validación nativa del navegador
        if ($('#stock-form')[0].checkValidity()) {
            $('#stock-form').submit(); // Esto dispara el evento 'submit' de abajo
        } else {
            $('#stock-form')[0].reportValidity();
        }
    });

    // **CAMBIO IMPORTANTE #1: Interceptar el envío del formulario principal**
    $('#stock-form').submit(function(event) {
        event.preventDefault(); // Prevenir el envío normal
        var formData = $(this).serialize();
        
        $('#submit-button').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        $.ajax({
            // **CAMBIO CLAVE**: La URL ahora es vacía (''), lo que significa que se envía a la página actual.
            // Nuestro Front Controller en public/index.php recibirá esta petición POST.
            url: '',
            method: 'POST',
            data: formData,
            success: function(data) {
                // Simplemente recargamos la página para ver los cambios.
                window.location.reload();
            },
            error: function(xhr, status, error) {
                console.error("Error en la solicitud AJAX:", error);
                // Reactivar el botón en caso de error
                $('#submit-button').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
                alert('Hubo un error al procesar la solicitud.');
            }
        });
    });

    // Botón editar: Rellena el modal con los datos de la fila
    $('.edit-btn').click(function() {
        var codigoSucursal = $(this).data('codigo-sucursal');
        var idRubro = $(this).data('id-rubro');
        var stockBase = $(this).data('stock-base');

        // Llenar el formulario
        $('#codigo_sucursal').val(codigoSucursal);
        $('#id_rubro').val(idRubro);
        $('#stock_base').val(stockBase);
        
        // **CAMBIO IMPORTANTE #2: Deshabilitar las claves primarias**
        // No se debe permitir cambiar la sucursal o el rubro al editar, solo el stock.
        $('#codigo_sucursal').prop('disabled', true);
        $('#id_rubro').prop('disabled', true);
        
        // Establecer la acción como 'edit'
        $('#form-action').val('edit');

        // Cambiar textos del modal
        $('#submit-button').html('<i class="fas fa-save"></i> Actualizar');
        $('#stockModalLabel').html('<i class="fas fa-edit"></i> Editar Stock Base');
    });
    
    // Botón eliminar: Prepara y envía el formulario para la acción de borrado
    $('.delete-btn').click(function() {
        var codigoSucursal = $(this).data('codigo-sucursal');
        var idRubro = $(this).data('id-rubro');
        
        if (confirm('¿Estás seguro de que deseas eliminar este registro?')) {
            // Rellenar el formulario principal con los datos para eliminar
            $('#codigo_sucursal').val(codigoSucursal);
            $('#id_rubro').val(idRubro);
            $('#form-action').val('delete');
            
            // Enviar el formulario
            $('#stock-form').submit();
        }
    });

    setTimeout(function() {
        $('.main-page-alert').fadeOut('slow');
    }, 5000);
    $('#import-form').submit(function() {
        if ($('#excel_file').val()) {
            $('#loading-overlay').addClass('visible');
        }
    });
});