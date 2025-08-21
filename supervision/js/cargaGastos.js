
$(document).ready(function() {
    // console.log("Document ready");
 

    // Evento de clic para el botón de guardar
    $('#guardarTodoBtn').on('click', function() {
        // console.log("Botón Guardar presionado");
        guardarGastos();
    });

    $('#importeAdelanto').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        value = new Intl.NumberFormat('es-AR', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value / 100);
        $(this).val(value);

        let total = 0;
        $('.importe-input').each(function() {
            let valor = $(this).val().replace(/\./g, '').replace(',', '.').trim();
            // console.log('Valor individual:', valor);
            if (valor) {
                total += parseFloat(valor);
            }
        });


        let adelanto = parseFloat(value.replace(/\./g, '').replace(',', '.'));

        let saldo = total - adelanto;

        // if(saldo < 0){

        //     saldo = 0;

        // }

        $('#totalGastos').text(new Intl.NumberFormat('es-AR', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(saldo));

    });

    function tieneImagenesCargadas(input) {
      let tieneFoto = input.parentElement.parentElement.parentElement.childNodes[5].childNodes[0].childNodes.length
    
    if(tieneFoto == 0){
        return false
    }else{
        return true
    }

    }
    
    function guardarGastos() {
        // console.log("Función guardarGastos() llamada");
        let gastos = [];
        let conceptosIncompletos = [];
        let seleccion = $('#semanaRetail').val();
        if (!seleccion) {
            Swal.fire('Error', 'Por favor, seleccione una semana', 'error');
            return;
        }
        let [semana, anio] = seleccion.split('|');
        let importeAdelanto = $('#importeAdelanto').val().replace(/\./g, '').replace(',', '.');
        
        // console.log("Semana:", semana, "Año:", anio);
        // console.log("Iniciando procesamiento de tarjetas...");
    
        $('.card').each(function() {
            const $card = $(this);

  
            let id_conc = $card.find('.concepto-id').val();
            let concepto = $card.find('.card-title').text().trim();
            let observaciones = $card.find('textarea').val() || '';
   
            let gastosArray = [];
            $card.find('.importe-input').each(function(e,x) {
                let input = x; 
                let $importeInput = input;
                let type = input.getAttribute('attr-type');
        
                
                if (concepto.toLowerCase() === 'total de gastos') {
                    return true; 
                }
        
                let tieneImporte = $importeInput.value && $importeInput.value !== '0,00';
           
                let tieneFoto = tieneImagenesCargadas(input);
        

                // console.log(`Procesando tarjeta ${index + 1}:`, { id_conc, concepto, importe: $importeInput.val(), tieneFoto });
                if (tieneImporte && tieneFoto) {

                    let importe = $importeInput.value.replace(/\./g, '').replace(',', '.');

                    gastosArray.push({ importe , type });
                       

                }else{
              
                    if (tieneImporte &&  !tieneFoto) {
                        conceptosIncompletos.push(`${concepto} (${tieneImporte ? 'falta foto' : 'falta importe'})`);
                    } else if (tieneFoto && !tieneImporte) {
                        conceptosIncompletos.push('falta importe');             
                    }
                }
            });
            if (gastosArray.length > 0) {
                gastos.push({
                    id_conc: id_conc,
                    concepto: concepto,
                    observaciones: observaciones.trim(),
                    gastosArray: gastosArray
                });
            }
       
        });
        
        // console.log("Gastos a guardar:", gastos);
        // console.log("Conceptos incompletos:", conceptosIncompletos);
    
        if (conceptosIncompletos.length > 0) {
            Swal.fire('Error', `Los siguientes conceptos están incompletos: ${conceptosIncompletos.join(', ')}`, 'error');
            return;
        }
    
        if (gastos.length === 0) {
            // console.log("No se encontraron gastos para guardar.");
            Swal.fire('Información', 'No hay gastos nuevos para guardar.', 'info');
            return;
        }
    
        let mensaje = `Se guardarán ${gastos.length} gasto(s).`;

        Swal.fire({
            title: 'Confirmar acción',
            text: mensaje,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                enviarDatosAlServidor(gastos, semana, anio, importeAdelanto);
                }
                enviarCorreo();
        });
    }
    

    function enviarDatosAlServidor(gastos, semana, anio, importeAdelanto) {
        // console.log("Enviando datos al servidor...");
        $('#guardarTodoBtn').prop('disabled', true);
    
        let supervisora = $('#nombreSupervisora').val();
        
        $.ajax({
            url: 'Controller/guardarGastos.php',
            method: 'POST',
            data: JSON.stringify({
                semana: semana,
                anio: anio,
                gastos: gastos,
                supervisora: supervisora,
                importeAdelanto: importeAdelanto
            }),
            contentType: 'application/json',
            success: function(response) {
                // console.log("Respuesta del servidor:", response);
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    if (response.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: 'Los gastos se han guardado correctamente',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Recargar la página con los parámetros de semana y año
                                window.location.href = `?semana=${semana}&anio=${anio}`;
                            }
                        });
                    } else {
                        Swal.fire('Error', 'Error del servidor: ' + (response.message || 'Hubo un problema al guardar los gastos'), 'error');
                    }
                } catch (e) {
                    console.error("Error al procesar la respuesta:", e);
                    Swal.fire('Error', 'Hubo un problema al procesar la respuesta del servidor', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error AJAX:", status, error);
                console.error("Respuesta del servidor:", xhr.responseText);
                Swal.fire('Error', 'Hubo un problema al guardar los gastos. Detalles: ' + error, 'error');
            },
            complete: function() {
                $('#guardarTodoBtn').prop('disabled', false);
            }
        });
    }


    function enviarCorreo() {
        // Obtén los datos necesarios para el correo
        var supervisora = document.getElementById('nombreSupervisora').value;
        var semana = document.getElementById('semanaRetail').value;
        var totalGastos = document.getElementById('totalGastos').innerText;
    
        // Prepara los datos para enviar
        var datos = {
            supervisora: supervisora,
            semana: semana,
            totalGastos: totalGastos
        };
    
        // Envía la solicitud AJAX
        $.ajax({
            url: "Controller/SendEmailController.php?accion=cargaGasto",
            type: "POST",
            data: {
                supervisora: supervisora,
                semana: semana,
                totalGastos: totalGastos,
            },
            success: function(data) {
            // if (data == true) {
            //     alert('Correo enviado exitosamente');
            // } else {
            //     alert('Error al enviar el correo: ' + data.message);
            // }
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            alert('Error al enviar el correo');
        });
    }


// Añadir esta función para seleccionar la semana al cargar la página
function seleccionarSemanaAlCargar() {
    let params = new URLSearchParams(window.location.search);
    let semana = params.get('semana');
    let anio = params.get('anio');
    
    if (semana && anio) {
        let valorSelect = semana + '|' + anio;
        $('#semanaRetail').val(valorSelect);
        
        // Disparar el evento de cambio para activar cualquier listener que pueda estar asociado
        $('#semanaRetail').trigger('change');
        
        // Llamar a la función de búsqueda si existe
        if (typeof buscarGastos === 'function') {
            buscarGastos();
        }
    }
}

// Llamar a esta función cuando se carga la página
$(document).ready(function() {
    seleccionarSemanaAlCargar();
});

});