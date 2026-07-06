let selectedCountry = 'AR';

$(document).ready(function() {
    // Country Selector logic
    $('.country-btn').on('click', function() {
        $('.country-btn').removeClass('active');
        $(this).addClass('active');
        selectedCountry = $(this).data('country');
        
        // Re-run search if there is already an article code inputted
        const currentCode = $('#selectArticulo').val().trim();
        if (currentCode) {
            buscarArticulo();
        }
    });

    // Enter keypress trigger
    $('#selectArticulo').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarArticulo();
        }
    });
});

function buscarArticulo() {
    const codArticulo = $('#selectArticulo').val().trim();
    
    if (!codArticulo) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Por favor ingrese un código de artículo',
            confirmButtonColor: '#0284c7'
        });
        return;
    }

    // Show loading spinner
    $('#loading-overlay').addClass('active');

    $.ajax({
        url: 'Controller/ArticuloController.php?accion=traerArticulo',
        type: 'POST',
        data: {
            codArticulo: codArticulo,
            pais: selectedCountry
        },
        dataType: 'json',
        success: function(data) {
            $('#loading-overlay').removeClass('active');
            
            if (data && data.length > 0) {
                const article = data[0];
                
                // Show result container, hide empty state
                $('#resultadoArticulo').removeClass('d-none');
                $('#resultadoVacio').addClass('d-none');

                // Populate fields
                $('#articulo').val(article.COD_ARTICU || '');
                $('#descripcion').val(article.DESCRIPCION || '');
                $('#rubro').val(article.RUBRO || 'N/A');
                
                // Prices
                const precioConIva = article.PRECIO !== null ? parseFloat(article.PRECIO) : 0;
                const precioSinIva = article.PRECIO_S_IVA !== null ? parseFloat(article.PRECIO_S_IVA) : 0;
                
                $('#precio_sin_iva').val(precioSinIva ? "$" + parseNumber(precioSinIva, 2) : "N/A");
                
                if (precioConIva && precioSinIva) {
                    const ivaValor = precioConIva - precioSinIva;
                    $('#iva').val("$" + parseNumber(ivaValor, 2));
                } else {
                    $('#iva').val("N/A");
                }
                
                $('#precio').val(precioConIva ? "$" + parseNumber(precioConIva, 0) : "N/A");
                
                // Metadata
                $('#destino').val(article.DESTINO || 'N/A');
                $('#temporada').val(article.TEMPORADA || 'N/A');

                // Liquidacion/SALE badge
                const badgeElement = $('.estado-badge');
                if (article.LIQUIDACION === 'SI') {
                    badgeElement.show();
                } else {
                    badgeElement.hide();
                }

                // Handle Image
                const imageName = article.COD_ARTICU.substring(0, 13);
                const imageUrl = `/Imagenes/${imageName}.jpg`;
                const imgElement = $('#imagenArticulo');
                
                fetch(imageUrl, { method: 'HEAD' })
                    .then(res => {
                        if (res.ok) {
                            imgElement.attr('src', imageUrl);
                            imgElement.show();
                        } else {
                            imgElement.hide();
                        }
                    })
                    .catch(() => {
                        imgElement.hide();
                    });

            } else {
                borrarResultados();
                Swal.fire({
                    icon: 'error',
                    title: 'No encontrado',
                    text: 'No se encontraron datos para este artículo.',
                    confirmButtonColor: '#0284c7'
                });
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            $('#loading-overlay').removeClass('active');
            console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un error al obtener los datos del artículo. Por favor, intente de nuevo.',
                confirmButtonColor: '#0284c7'
            });
            borrarResultados();
        }
    });
}

function parseNumber(number, decimals = 0) {
    number = typeof number === 'string' ? parseFloat(number) : parseFloat(number);
    return number.toLocaleString('es-AR', {
        style: 'decimal',
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function borrar() {
    $('#selectArticulo').val('');
    borrarResultados();
    $('#selectArticulo').focus();
}

function borrarResultados() {
    $('#resultadoArticulo').addClass('d-none');
    $('#resultadoVacio').removeClass('d-none');
    
    $('#articulo').val('');
    $('#descripcion').val('');
    $('#rubro').val('');
    $('#precio_sin_iva').val('');
    $('#iva').val('');
    $('#precio').val('');
    $('#destino').val('');
    $('#temporada').val('');
    
    $('.estado-badge').hide();
    $('#imagenArticulo').hide().attr('src', '');
}
