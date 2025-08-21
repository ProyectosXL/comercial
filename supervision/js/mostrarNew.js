
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
        
        // Actualizar estado del presupuesto para este concepto
        let $card = $(this).closest('.card');
        actualizarEstadoPresupuestoCard($card);
    });

    // Cargar datos de la semana actual al iniciar la página
    cargarDatosSemanaActual();
    actualizarEstadoInputs();

    // Actualizar el estado cuando cambie la selección
    $('#semanaRetail').change(function() {
        let gastos = {};
        traerAdelanto();
        actualizarEstadoInputs(gastos);
        buscarGastos();
    });

    // Mostrar/ocultar ícono de borrar
    $('#searchInput').on('input', function() {
        let searchTerm = $(this).val();
        $('.clear-icon').toggle(searchTerm.length > 0);
        
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

function cargarDatosSemanaActual() {
    let semanaActual = $('#semanaRetail').val();
    if (semanaActual) {
        buscarGastos();
    } else {
        console.error("No se pudo determinar la semana actual");
    }
}

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

    console.log('Total calculado:', saldo);
    
    if(saldo < 0){
        saldo = saldo * -1;
        $('#totalGastos').text('- $' + formatearImporte(saldo));
    }else{
        $('#totalGastos').text('$' + formatearImporte(saldo));
    }
}

function formatearImporte(importe) {
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(importe);
}

function buscarGastos() {
    let seleccion = $('#semanaRetail').val();
    if (!seleccion) {
        console.error("No se ha seleccionado ninguna semana");
        return;
    }
    
    let [semana, anio] = seleccion.split('|');

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
    });
}

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
            
            if (gastos[id_conc].OBSERVACIONES_AUT !== undefined) {
                $observacionesAutTextarea.val(gastos[id_conc].OBSERVACIONES_AUT);
                $observacionesAutTextarea.show();
            }
            
            if (gastos[id_conc].tieneImagenes) {
                $verImagenesBtn.show();
            } else {
                $verImagenesBtn.hide();
            }

            // NUEVO: Actualizar estado del presupuesto después de cargar
            setTimeout(() => {
                actualizarEstadoPresupuestoCard($card);
            }, 100);
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

function calcularTotalAutorizado(gastos) {
    let autorizado = 0;

    Object.entries(gastos).forEach(([key, value]) => {
        if(value.ESTADO == 1){
            autorizado += parseFloat(value.IMPORTE || 0);
            autorizado += parseFloat(value.IMPORTE_TARJETA || 0);
        }
    });

    let divAutorizado = document.querySelector('#divAutorizado');
    divAutorizado.hidden = autorizado <= 0;

    $('#totalAutorizado').text('$' + formatearImporte(autorizado));
}

function actualizarEstadoPresupuestoCard($card) {
    let presupuestoTotal = parseFloat($card.attr('data-presupuesto-total') || '0');
    let presupuestoGastado = parseFloat($card.attr('data-presupuesto-gastado') || '0');
    
    if (presupuestoTotal === 0) return;

    let $presupuestoInfo = $card.find('.presupuesto-info');
    let $estadoSpan = $presupuestoInfo.find('.presupuesto-estado');

    console.log(`DEBUG Card - Concepto: ${$card.find('.card-title').text()}`);
    console.log(`  Presupuesto Total: ${presupuestoTotal}`);
    console.log(`  Ya Gastado (total del mes): ${presupuestoGastado}`);

    // CORRECCIÓN: Solo comparar presupuestoGastado vs presupuestoTotal
    // presupuestoGastado ya incluye TODOS los gastos del mes
    if (presupuestoGastado > presupuestoTotal) {
        let exceso = presupuestoGastado - presupuestoTotal;
        $estadoSpan.text(`Excede el presupuesto por ${formatearImporte(exceso)}`);
        $presupuestoInfo.removeClass('dentro-presupuesto').addClass('exceso-presupuesto');
        console.log(`  ❌ EXCEDE por: ${exceso}`);
    } else {
        let disponibleRestante = presupuestoTotal - presupuestoGastado;
        $estadoSpan.text(`Dentro del presupuesto del mes`);
        $presupuestoInfo.removeClass('exceso-presupuesto').addClass('dentro-presupuesto');
        console.log(`  ✅ DENTRO - Disponible restante: ${disponibleRestante}`);
    }
}