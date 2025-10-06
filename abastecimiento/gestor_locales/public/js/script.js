$(document).ready(function() {

    /**
     * Lógica del Modal (Agregar/Editar).
     * Se dispara justo ANTES de que el modal se muestre.
     */
    $('#localModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);

        if (button.hasClass('edit-btn')) {
            // --- MODO EDICIÓN ---
            var idSucursal = button.data('id-sucursal');
            var m2Venta = button.data('m2-venta');
            var m2Deposito = button.data('m2-deposito');
            var m2Baulera = button.data('m2-baulera');

            $('#id_sucursal').val(idSucursal);
            $('#m2_venta').val(m2Venta);
            $('#m2_deposito').val(m2Deposito);
            $('#m2_baulera').val(m2Baulera);
            
            // Deshabilitamos el selector para que el usuario no lo cambie.
            $('#id_sucursal').prop('disabled', true);
            
            $('#localModalLabel').html('<i class="fas fa-edit"></i> Editar Medidas del Local');

        } else {
            // --- MODO AGREGAR ---
            $('#local-form')[0].reset();
            // Nos aseguramos de que el selector de sucursal esté habilitado.
            $('#id_sucursal').prop('disabled', false);
            $('#localModalLabel').html('<i class="fas fa-plus-circle"></i> Agregar Medidas de Local');
        }
    });

    /**
     * Envío del formulario del modal.
     * Se activa al hacer clic en el botón "Guardar".
     */
    $('#submit-button').click(function() {
        
        // ===== ¡AQUÍ ESTÁ LA CORRECCIÓN CLAVE! =====
        // Habilitamos temporalmente el selector de sucursal para que su valor
        // se incluya en los datos del formulario al enviarse.
        $('#id_sucursal').prop('disabled', false);

        // Activamos la validación HTML5 del navegador.
        if ($('#local-form')[0].checkValidity()) {
            // Si el formulario es válido, lo enviamos.
            $('#local-form').submit();
        } else {
            // Si no es válido, le pedimos al navegador que muestre los mensajes de error.
            $('#local-form')[0].reportValidity();
        }
    });
    
    /**
     * Lógica del botón de eliminar.
     */
    $('.delete-btn').click(function() {
        if (confirm('¿Estás seguro de que deseas eliminar las medidas de este local? Esta acción no se puede deshacer.')) {
            var idSucursal = $(this).data('id-sucursal');
            
            var form = $('<form action="" method="post">' +
                '<input type="hidden" name="action" value="delete" />' +
                '<input type="hidden" name="id_sucursal" value="' + idSucursal + '" />' +
                '</form>');
            
            $('body').append(form);
            form.submit();
        }
    });

    /**
     * Ocultar Alertas de Mensajes.
     */
    setTimeout(function() {
        $('.main-page-alert').fadeOut('slow');
    }, 5000);

});