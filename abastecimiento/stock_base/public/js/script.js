$(document).ready(function() {
    // Actualizar nombre del archivo seleccionado en el modal de importación
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Elegir archivo...');
    });

    // Resetear y/o configurar el modal de agregar/editar al abrirlo
    $('#stockModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        
        // Eliminar cualquier opción temporal agregada durante una edición previa
        $('.temp-option').remove();
        
        if (button.hasClass('edit-btn')) {
            // Modo Edición
            var codigoSucursal = button.data('codigo-sucursal');
            var idRubro = button.data('id-rubro');
            var stockBase = button.data('stock-base');

            // Obtener nombres de la fila para agregarlos si la opción no existe en el select
            var row = button.closest('tr');
            var codigoCliente = row.find('td:nth-child(1) .badge').text().trim() || row.find('td:nth-child(1)').text().trim();
            var nombreSucursal = row.find('td:nth-child(2)').text().trim();
            var nombreRubro = row.find('td:nth-child(3)').text().trim();

            // Agregar sucursal temporalmente si no existe en el select
            if (codigoSucursal && $('#codigo_sucursal option[value="' + codigoSucursal + '"]').length === 0) {
                var newOpt = new Option(codigoCliente + ' - ' + nombreSucursal, codigoSucursal);
                $(newOpt).addClass('temp-option');
                $('#codigo_sucursal').append(newOpt);
            }

            // Agregar rubro temporalmente si no existe en el select
            if (idRubro && $('#id_rubro option[value="' + idRubro + '"]').length === 0) {
                var newOpt = new Option(nombreRubro, idRubro);
                $(newOpt).addClass('temp-option');
                $('#id_rubro').append(newOpt);
            }

            // Llenar el formulario
            $('#codigo_sucursal').val(codigoSucursal);
            $('#id_rubro').val(idRubro);
            $('#stock_base').val(stockBase);
            
            // Deshabilitar las claves primarias (no se pueden cambiar al editar)
            $('#codigo_sucursal').prop('disabled', true);
            $('#id_rubro').prop('disabled', true);
            
            // Configurar formulario para edición
            $('#form-action').val('edit');
            $('#submit-button').html('<i class="fas fa-save"></i> Actualizar');
            $('#stockModalLabel').html('<i class="fas fa-edit"></i> Editar Stock Base');
        } else {
            // Modo Creación (Agregar)
            $('#stock-form')[0].reset();
            $('#form-action').val('add');
            $('#codigo_sucursal').prop('disabled', false);
            $('#id_rubro').prop('disabled', false);
            $('#submit-button').html('<i class="fas fa-save"></i> Guardar');
            $('#stockModalLabel').html('<i class="fas fa-plus-circle"></i> Agregar Stock Base');
        }
    });

    // Botón para enviar el formulario del modal (Agregar/Editar)
    $('#submit-button').click(function() {
        if ($('#stock-form')[0].checkValidity()) {
            $('#stock-form').submit();
        } else {
            $('#stock-form')[0].reportValidity();
        }
    });

    // Interceptar el envío del formulario principal
    $('#stock-form').submit(function(event) {
        event.preventDefault(); // Prevenir el envío normal
        
        // Habilitar temporalmente los campos deshabilitados para que se incluyan en serialize()
        var disabledFields = $(this).find(':disabled');
        disabledFields.prop('disabled', false);
        
        var formData = $(this).serialize();
        
        // Restaurar el estado deshabilitado inmediatamente
        disabledFields.prop('disabled', true);
        
        $('#submit-button').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        $.ajax({
            url: '',
            method: 'POST',
            data: formData,
            success: function(data) {
                window.location.reload();
            },
            error: function(xhr, status, error) {
                console.error("Error en la solicitud AJAX:", error);
                $('#submit-button').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
                alert('Hubo un error al procesar la solicitud.');
            }
        });
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