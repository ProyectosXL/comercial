
function actualizarEstadoInputs(gastos = {}) {
    let $opcionSeleccionada = $('#semanaRetail option:selected');
    let esActual = $opcionSeleccionada.data('es-actual') === 1;
    let hayGastosCargados = Object.keys(gastos).length > 0;

    $('.importe-input, textarea, #importeAdelanto').prop('disabled', !esActual || hayGastosCargados);
    $('.camara-btn').toggle(esActual && !hayGastosCargados);
    $('#guardarTodoBtn').toggle(esActual && !hayGastosCargados);

    let mensaje = esActual 
        ? (hayGastosCargados ? " Semana actual con gastos cargados. No se pueden modificar." : " Semana actual seleccionada. Puede cargar gastos.")
        : " Semana pasada seleccionada. Los gastos no se pueden modificar.";
    $('#semanaInfo').text(mensaje);
}


function traerAdelanto(){

    let semana = $('#semanaRetail').val().split('|')[0];
    let anio = $('#semanaRetail').val().split('|')[1];
    let supervisora = $('#nombreSupervisora').val();
    $.ajax({
        url: 'Controller/traerAdelanto.php',
        method: 'POST',
        data:{
            semana: semana,
            anio: anio,
            supervisora: supervisora
        },
        success: function(response) {
      
            let adelanto = formatearImporte(parseFloat(response));
            document.querySelector('#importeAdelanto').value = adelanto;

        }
    
    })
}

$(document).ready(function() {
    traerAdelanto();
    $('.importe-input').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        value = new Intl.NumberFormat('es-AR', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value / 100);
        $(this).val(value);
        actualizarTotalGastos();
    });

    // Cargar datos de la semana actual al iniciar la página
    cargarDatosSemanaActual();

    // Llamar a la función al cargar la página
    actualizarEstadoInputs();


    // Actualizar el estado cuando cambie la selección
    $('#semanaRetail').change(function() {
        let gastos = {}; // Inicialmente no hay gastos
        traerAdelanto();
        actualizarEstadoInputs(gastos);
        buscarGastos(); // Esto actualizará el estado nuevamente con los gastos reales
    });

    // Mostrar/ocultar ícono de borrar
    $('#searchInput').on('input', function() {
        let searchTerm = $(this).val();
        $('.clear-icon').toggle(searchTerm.length > 0);
        
        // Funcionalidad de búsqueda existente
        searchTerm = searchTerm.toLowerCase();
        $('.card').each(function() {
            let cardText = $(this).text().toLowerCase();
            $(this).toggle(cardText.indexOf(searchTerm) > -1);
        });
    });

    // Funcionalidad para borrar búsqueda
    $('.clear-icon').click(function() {
        $('#searchInput').val('').trigger('input');
        $(this).hide();
    });

    // Ocultar el botón de guardar si todos los gastos ya existen
    if (Object.keys(gastosExistentes).length === $('.card').length) {
        $('#guardarTodoBtn').hide();
    }

});

//Carga los datos de la semana actual al cargar la pagina//
function cargarDatosSemanaActual() {
    let semanaActual = $('#semanaRetail').val();
    if (semanaActual) {
        buscarGastos();
    } else {
        console.error("No se pudo determinar la semana actual");
    }
}

//Actualiza el total de los gastos//
function actualizarTotalGastos() {
    let total = 0;
    $('.importe-input').each(function() {
        let valor = $(this).val().replace(/\./g, '').replace(',', '.').trim();
      
        if (valor) {
            total += parseFloat(valor);
        }
    });

    console.log('Total calculado:', total);
    let adelanto = parseFloat(document.querySelector('#importeAdelanto').value.replace(".", "").replace(",", "."));
   
    if(adelanto < 1 || isNaN(adelanto)){
        adelanto = 0;
    }

     
    let saldo = total - adelanto;

    // if(saldo < 0){

    //     saldo = 0;

    // }


    console.log('Total calculado:', saldo);
    
    if(saldo < 0){
        saldo = saldo * -1;
        $('#totalGastos').text('- $' + formatearImporte(saldo));
    }else{

        $('#totalGastos').text('$' + formatearImporte(saldo));
    }
    // localStorage.setItem('totalGastos', parseFloat($('#totalGastos').text().replace(/\./g, '').replace(',', '.').replace("$","")));
}

//Formatear total de gastos//
function formatearImporte(importe) {
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(importe);
}

//Trae los datos del backend//
function buscarGastos() {
    let seleccion = $('#semanaRetail').val();
    if (!seleccion) {
        console.error("No se ha seleccionado ninguna semana");
        return;
    }
    
    let [semana, anio] = seleccion.split('|');

    // Mostrar indicador de carga
    // Swal.fire({
    //     title: 'Cargando...',
    //     text: 'Buscando gastos para la semana seleccionada',
    //     allowOutsideClick: false,
    //     didOpen: () => {
    //         Swal.showLoading();
    //     }
    // });

    $.ajax({
        url: 'Controller/buscarGastos.php',
        method: 'POST',
        data: JSON.stringify({ semana: semana, anio: anio }),
        contentType: 'application/json',
        dataType: 'json',
    })
    .done(function(response) {
        console.log("Respuesta de buscarGastos:", response);
        if (response.success) {
            actualizarInterfazConGastos(response.gastos);
            actualizarEstadoInputs(response.gastos);
            calcularTotalAutorizado(response.gastos);
        } else {
            Swal.fire('Error', response.message || 'Hubo un problema al buscar los gastos', 'error');
        }
    })
    .fail(function(xhr, status, error) {
        console.error("Error AJAX:", status, error);
        Swal.fire('Error', 'Hubo un problema al buscar los gastos: ' + error, 'error');
    })
    .always(function() {
        Swal.close(); // Cerrar el indicador de carga
    });
}


//Muestra los datos ya cargados en los inputs//
function actualizarInterfazConGastos(gastos) {
    console.log("Actualizando interfaz con gastos:", gastos);
    if (!gastos) {
        console.error("No se recibieron datos de gastos");
        return;
    }

    $('.card').each(function() {
        let $card = $(this);
        let id_conc = $card.find('.concepto-id').val();
        let $importeInput = $card.find('.importe-input[attr-type="efectivo"]');
        let $importeTarjeta = $card.find('.importe-input[attr-type="tarjeta"]');
        let $observacionesTextarea = $card.find('textarea');
        let $observacionesAutTextarea = $card.find('textarea[id^="observaciones_aut_"]');
        let $verImagenesBtn = $card.find('.ver-imagenes-btn');
        let $estadoBadge = $card.find('.estado-badge');

        if (gastos[id_conc]) {
            
            $importeInput.val(formatearImporte(gastos[id_conc].IMPORTE));
            $importeTarjeta.val(formatearImporte(gastos[id_conc].IMPORTE_TARJETA));
            $observacionesTextarea.val(gastos[id_conc].OBSERVACIONES);
            $estadoBadge.show();
            // Actualizar y mostrar las observaciones del autorizante
            if (gastos[id_conc].OBSERVACIONES_AUT !== undefined) {
                $observacionesAutTextarea.val(gastos[id_conc].OBSERVACIONES_AUT);
                // console.log(`Gasto ${id_conc}: Observaciones autorizante = ${gastos[id_conc].OBSERVACIONES_AUT}`);
                $observacionesAutTextarea.show();
            } else {
            }
            
            // console.log(`Gasto ${id_conc} tiene imágenes:`, gastos[id_conc].tieneImagenes);
            
            if (gastos[id_conc].tieneImagenes) {
                $verImagenesBtn.show();
            } else {
                $verImagenesBtn.hide();
            }
        } else {
            $importeInput.val('');
            $importeTarjeta.val('');
            $observacionesTextarea.val('');
            $verImagenesBtn.hide();
            $estadoBadge.hide();
        }

        
    });

    let allDiv = document.querySelectorAll(".observaciones-aut-div");
    allDiv.forEach((div) => {
        if(div.querySelector('textarea').value === ""){
            div.hidden = true;
        }else{
            div.hidden = false;
        }
    });

    actualizarTotalGastos();
    actualizarEstadoInputs(gastos);
}

function calcularTotalAutorizado (gastos) {
    

    let autorizado = 0;

    Object.entries(gastos).forEach(([key, value]) => {
            if(value.ESTADO == 1){

                autorizado += parseFloat(value.IMPORTE);

            }
        }
    );
    let divAutorizado = document.querySelector('#divAutorizado');
    if(autorizado > 0){
        divAutorizado.hidden = false;
    }else{
        divAutorizado.hidden = true;
    }
    
    $('#totalAutorizado').text('$' + formatearImporte(autorizado));

}