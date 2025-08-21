$(document).ready(function() {
    if (typeof jQuery == 'undefined') {
        console.error('jQuery no está cargado!');
        return; // Detiene la ejecución del script
    }
    console.log($.fn.jquery);

    $('#submit-button').click(function() {
        $('#stock-form').submit();
    });

    $('#stock-form').submit(function(event) {
        event.preventDefault();
        var formData = $(this).serialize();

        $.ajax({
            url: 'index.php',
            method: 'POST',
            data: formData,
            success: function(data) {
                 window.location.reload();
            },
             error: function(xhr, status, error) {
                console.error("Error en la solicitud AJAX:", error);
                 $('.alert').remove();
                 $('.container').prepend('<div class="alert alert-danger mt-3">Error en la solicitud AJAX: ' + error + '</div>');
             }
        });
       $('#stock-form')[0].reset();
     });

    // Evento para el botón de editar
    $('.edit-btn').click(function() {
        var codigoSucursal = $(this).data('codigo-sucursal');
        var idRubro = $(this).data('id-rubro');
        var stockBase = $(this).data('stock-base');

        $('#codigo_sucursal').val(codigoSucursal);
        $('#id_rubro').val(idRubro);
        $('#stock_base').val(stockBase);
        $('#form-action').val('edit');
        $('#submit-button').text('Editar');
    });
    
    // Evento para el botón de eliminar
     $('.delete-btn').click(function() {
         var codigoSucursal = $(this).data('codigo-sucursal');
        var idRubro = $(this).data('id-rubro');
       if (confirm('¿Estás seguro de que quieres eliminar este registro?')) {
           $('#codigo_sucursal').val(codigoSucursal);
            $('#id_rubro').val(idRubro);
            $('#stock_base').val('');
            $('#form-action').val('delete');
             $('#stock-form').submit();
        }
    });
});