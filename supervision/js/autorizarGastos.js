
const BASE_URL = 'http://192.168.0.143:8080';

$(document).ready(function() {
    inicializarEventListeners();
    actualizarEstadoObservacionesAut();
    mostrarTotalGastos(false); // Ocultar la tarjeta de total al inicio
    mostrarPendientesDeAutorizar();
});


function inicializarEventListeners() {
    $('#buscarBtn').click(buscarGastos);
    $('#procesarGastosBtn').click(procesarGastos);
    $('#autorizarSwitch').change(toggleAutorizacion);
    // Agregar listener para cambios en los switches de autorización
    $(document).on('change', '.autorizar-switch', function() {
        let id_conc = $(this).closest('.card').find('.concepto-id').val();
        actualizarEstadoObservacionesAut(id_conc);
    });
}

function actualizarTotalGastos() {
    let total = 0;
    $('.importe-input').each(function() {
        let valor = $(this).val().replace(/\./g, '').replace(',', '.').trim();
        if (valor) {
            total += parseFloat(valor);
        }
    });

    $('.importe-input-tarjeta').each(function() {
        let valor = $(this).val().replace(/\./g, '').replace(',', '.').trim();
        if (valor) {
            total += parseFloat(valor);
        }
    });


    let semana = $('#semanaRetail').val().split('|')[0];
    let anio = $('#semanaRetail').val().split('|')[1];
    let supervisora = $('#supervisoraSelect').val();

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

            let adelantoValor = parseFloat(document.querySelector('#importeAdelanto').value.replace(".", "").replace(",", "."));

            if(adelantoValor < 1 || isNaN(adelantoValor)){
                adelanto = 0;
            }
        
             
            let saldo = total - adelantoValor;
        
            // if(saldo < 0){
        
            //     saldo = 0;
        
            // }
        
            if(saldo < 0){
                saldo = saldo * -1;
                $('#totalGastos').text('- $' + formatearImporte(saldo));
            }else{

                $('#totalGastos').text('$' + formatearImporte(saldo));
            }
        }
    
    })
  
}

function formatearImporte(importe) {
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(importe);
}

// Mostrar/ocultar ícono de borrar
$('#searchInput').on('input', function() {
    let searchTerm = $(this).val().toLowerCase();
    $('.clear-icon').toggle(searchTerm.length > 0);
    
    let gastosVisibles = 0;
    $('.card').each(function() {
        let cardText = $(this).text().toLowerCase();
        let visible = cardText.indexOf(searchTerm) > -1;
        $(this).toggle(visible);
        if (visible) gastosVisibles++;
    });

    actualizarTotalGastos();
    mostrarTotalGastos(gastosVisibles > 0);
});
    

// Funcionalidad para borrar búsqueda
$('.clear-icon').click(function() {
    $('#searchInput').val('').trigger('input');
    $(this).hide();
});

function toggleAutorizacion() {
    let isChecked = $(this).is(':checked');
    $('#autorizarBtn').prop('disabled', !isChecked);
}

function buscarGastos() {
    let semanaCompleta = $('#semanaRetail').val();
    let supervisora = $('#supervisoraSelect').val();
    if (!semanaCompleta || !supervisora) {
        Swal.fire('Error', 'Por favor, seleccione una semana y una supervisora', 'error');
        return;
    }

    $('.importe-input').each(function() {
        $(this).val(0);
    });

    let [semana, anio] = semanaCompleta.split('|');

    console.log("Buscando gastos para:", { semana, anio, supervisora });

    $.ajax({
        url: 'Controller/buscarGastosSup.php',
        method: 'POST',
        data: JSON.stringify({ 
            semana: semana, 
            anio: anio, 
            supervisora: supervisora 
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            console.log("Respuesta de búsqueda:", response);
            if (response.success) {
                localStorage.setItem("email", response.email);
                actualizarInterfazConGastos(response.gastos);
                mostrarTotalGastos(Object.keys(response.gastos).length > 0);
                calcularTotalAutorizado();
            } else {
                Swal.fire('Error', response.message || 'Hubo un problema al buscar los gastos', 'error');
                mostrarTotalGastos(false);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error AJAX:", status, error);
            console.log("Respuesta del servidor:", xhr.responseText);
            Swal.fire('Error', 'Hubo un problema al buscar los gastos: ' + error, 'error');
            mostrarTotalGastos(false);
        }
    });
}

function actualizarInterfazConGastos(gastos) {
    console.log("Actualizando interfaz con gastos:", gastos);
    let gastosVisibles = 0;
    $('.card').each(function() {
        let $card = $(this);
        let id_conc = $card.find('.concepto-id').val();
        let $importeInput = $card.find('.importe-input');
        let $importeTarjeta = $card.find('.importe-input-tarjeta');
        let $observacionesTextarea = $card.find('textarea[id^="observaciones_"]');
        let $observacionesAutTextarea = $card.find('textarea[id^="observaciones_aut_"]');
        let $autorizarSwitch = $card.find('.autorizar-switch');
        let $badgeContainer = $card.find('.badge-container');
        if (gastos[id_conc]) {
            $card.show();
            gastosVisibles++;
            $importeInput.val(formatearImporte(gastos[id_conc].IMPORTE)).prop('disabled', true);
            $importeTarjeta.val(formatearImporte(gastos[id_conc].IMPORTE_TARJETA)).prop('disabled', true);
            $observacionesTextarea.val(gastos[id_conc].OBSERVACIONES).prop('disabled', true);
            
            
            if (gastos[id_conc].OBSERVACIONES_AUT !== undefined) {
                $observacionesAutTextarea.val(gastos[id_conc].OBSERVACIONES_AUT);
                console.log(`Gasto ${id_conc}: Observaciones autorizante = ${gastos[id_conc].OBSERVACIONES_AUT}`);
            } else {
                console.log(`Gasto ${id_conc}: No hay observaciones del autorizante`);
            }
            
            if (gastos[id_conc].ESTADO === 1) {
                $autorizarSwitch.hide();
                $badgeContainer.html('<span class="badge bg-success" id="spanAutorizado">AUTORIZADO</span>').show();
                $observacionesAutTextarea.prop('disabled', true);
            } else if (gastos[id_conc].ESTADO === 0) {
                $autorizarSwitch.hide();
                $badgeContainer.html('<span class="badge bg-danger">RECHAZADO</span>').show();
                $observacionesAutTextarea.prop('disabled', true);
            } else {
                $autorizarSwitch.show().prop('checked', false);
                $badgeContainer.hide();
                $observacionesAutTextarea.prop('disabled', false);
            }
        } else {
            $card.hide();
        }
    });
    actualizarTotalGastos();
    mostrarTotalGastos(gastosVisibles > 0);
}


function mostrarTotalGastos(mostrar) {
    const $cardTotal = $('.card-total');
    const cardAdelanto = $('.card-adelanto');
    if (mostrar) {
        $cardTotal.show();
        cardAdelanto.show();
    } else {
        $cardTotal.hide();
        cardAdelanto.hide();
    }
}


function actualizarEstadoObservacionesAut(id_conc) {
    if (id_conc) {
        // Si se proporciona un ID, actualizar solo ese gasto
        let $card = $(`.card:has(.concepto-id[value="${id_conc}"])`);
        actualizarEstadoObservacionesAutCard($card);
    } else {
        // Si no se proporciona ID, actualizar todos los gastos
        $('.card').each(function() {
            actualizarEstadoObservacionesAutCard($(this));
        });
    }
}

function actualizarEstadoObservacionesAutCard($card) {
    let $badge = $card.find('.badge');
    let $observacionesAut = $card.find('textarea[id^="observaciones_aut_"]');
    let $autorizarSwitch = $card.find('.autorizar-switch');
    
    if ($badge.hasClass('bg-success') || $badge.hasClass('bg-danger')) {
        $observacionesAut.prop('disabled', true);
    } else if ($autorizarSwitch.is(':checked')) {
        $observacionesAut.prop('disabled', false);
    } else {
        $observacionesAut.prop('disabled', true);
    }
}



function mostrarImagenes(id_conc, type) {
    let semanaCompleta = $('#semanaRetail').val();
    let supervisora = $('#supervisoraSelect').val();

    if (!semanaCompleta || !supervisora) {
        Swal.fire('Error', 'Por favor, seleccione una semana y una supervisora antes de ver las imágenes', 'error');
        return;
    }

    let [semana, anio] = semanaCompleta.split('|');

    console.log("Buscando imágenes para:", { id_conc, semana, anio, supervisora });

    Swal.fire({
        title: 'Cargando imágenes...',
        text: 'Por favor, espere...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'Controller/obtenerImagenesSup.php',
        method: 'POST',
        data: JSON.stringify({
            id_conc: id_conc,
            semana: semana,
            anio: anio,
            supervisora: supervisora,
            type: type
        }),
        contentType: 'application/json',
        dataType: 'json',
    })
    .done(function(response) {
        console.log("Respuesta de obtenerImagenes:", response);
        if (response.success && response.imagenes && response.imagenes.length > 0) {
            let carouselItems = response.imagenes.map((img, index) => `
                <div class="carousel-item ${index === 0 ? 'active' : ''}">
                    <img src="${img}" class="d-block w-100 zoomable" alt="Imagen ${index + 1}">
                    <div class="carousel-caption">
                        <h5>Imagen ${index + 1} de ${response.imagenes.length}</h5>
                    </div>
                </div>
            `).join('');
            
            let carouselHtml = `
                <div id="imageCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        ${carouselItems}
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#imageCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#imageCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Siguiente</span>
                    </button>
                </div>
            `;
            
            Swal.fire({
                html: carouselHtml,
                width: '90%',
                padding: '0',
                background: '#fff',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    container: 'image-modal-container',
                    closeButton: 'image-modal-close-button'
                },
                didOpen: (modalElement) => {
                    new bootstrap.Carousel(modalElement.querySelector('#imageCarousel'));
                    
                    // Agregar botones de rotación al carrusel
                    addRotationToCarousel('imageCarousel');
                }
            });
        } else {
            console.log("Debug info:", response.debug);
            Swal.fire('Sin imágenes', 'No se encontraron imágenes. Revisa la consola para más detalles.', 'info');
        }
    })
    .fail(function(xhr, status, error) {
        console.error('Error al obtener la lista de imágenes:', error);
        console.log("Respuesta del servidor:", xhr.responseText);
        Swal.fire('Error', 'No se pudieron cargar las imágenes. Por favor, intente de nuevo.', 'error');
    });
}


function procesarGastos() {
    let semana = $('#semanaRetail').val().split('|')[0];
    let anio = $('#semanaRetail').val().split('|')[1];
    let supervisora = $('#supervisoraSelect').val();
    let totalGastos = $('#totalAutorizado').text().replace('$', '').replace(/\./g, '').replace(',', '.').trim();
    let totalEfectivo = 0;
    if (!semana || !anio || !supervisora) {
        Swal.fire('Error', 'Por favor, seleccione una semana y una supervisora', 'error');
        return;
    }

    let gastosParaProcesar = [];

    $('.card').each(function(index) {
        let $card = $(this);
        let id_conc = $card.find('.concepto-id').val();
        let $importeInput = $card.find('.importe-input');
        let $importeTarjeta = $card.find('.importe-input-tarjeta');
        let $autorizarSwitch = $card.find('.autorizar-switch');
        let $observacionesAutTextarea = $card.find('textarea[id^="observaciones_aut_"]');
  

        if ($importeInput.length === 0 && $importeTarjeta.length === 0) {
            console.error(`No se encontró el input de importe para el concepto ID ${id_conc}`);
            return; // Continúa con la siguiente iteración
        }

        let importeValue = $importeInput.val();
        let importeTarjetaValue = $importeTarjeta.val();
        
        if (((importeValue && importeValue.trim() !== '') || (importeTarjetaValue && importeTarjetaValue.trim() !== '')) && $autorizarSwitch.is(':visible') && $autorizarSwitch.is(':checked')) {
            let autorizado = $autorizarSwitch.prop('checked') ? 1 : 0;
            let observaciones_aut = $observacionesAutTextarea.val();
            gastosParaProcesar.push({
                id_conc: id_conc,
                autorizado: autorizado,
                observaciones_aut: observaciones_aut
            });
        }
        if(importeValue.replace(".", "").replace(",", ".") != 0){

            totalEfectivo += parseFloat(importeValue.replace(".", "").replace(",", "."));
        }
    });

    console.log("Gastos a procesar:", gastosParaProcesar);


    if (gastosParaProcesar.length === 0) {
        Swal.fire('Advertencia', 'No hay gastos para procesar.', 'warning');
        return;
    }
    
    Swal.fire({
        title: '¿Está seguro?',
        text: `Va a procesar ${gastosParaProcesar.length} gastos. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'Controller/procesarGastos.php',
                method: 'POST',
                data: JSON.stringify({
                    semana: semana,
                    anio: anio,
                    supervisora: supervisora,
                    gastos: gastosParaProcesar
                }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(response) {
                    console.log("Respuesta del servidor:", response);
                    if (response.success) {
                        $.ajax({
                            url: 'Controller/SendEmailController.php?accion=autorizar',
                            method: 'POST',
                            data:{
                                semana: semana,
                                anio: anio,
                                supervisora: supervisora,
                                email: localStorage.getItem("email"),
                                totalGastos: totalEfectivo
                                
                            }
                        })
                        Swal.fire('Procesados', 'Los gastos han sido procesados con éxito.', 'success');

                        location.reload();
                        actualizarInterfazConGastos(response.gastos);
                    } else {
                        console.error("Error del servidor:", response.message);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al procesar los gastos',
                            text: 'Hubo un problema al procesar los gastos. Por favor, contacte al administrador.',
                            footer: `<a href="#" onclick="mostrarDetallesError('${response.message}')">Ver detalles del error</a>`
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error en la solicitud:", error);
                    console.log("Respuesta del servidor:", xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de comunicación',
                        text: 'Hubo un problema al comunicarse con el servidor. Por favor, intente de nuevo más tarde.',
                        footer: `<a href="#" onclick="mostrarDetallesError('${error}')">Ver detalles del error</a>`
                    });
                }
            });
        }
    });
}

function mostrarDetallesError(mensaje) {
    Swal.fire({
        title: 'Detalles del error',
        html: `<pre>${mensaje}</pre>`,
        icon: 'info'
    });
}



function traerAdelanto(){

    let semana = $('#semanaRetail').val().split('|')[0];
    let anio = $('#semanaRetail').val().split('|')[1];
    let supervisora = $('#supervisoraSelect').val();
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


function calcularTotalAutorizado () {

    let allSwitch = document.querySelectorAll(".autorizar-switch")

    let totalAutorizado = 0;

    allSwitch.forEach((element) => {
        if(element.checked){
            let card = element.closest('.card');
            let importe = card.querySelector('.importe-input').value;
            let importeTarjeta = card.querySelector('.importe-input-tarjeta').value;

            totalAutorizado += parseFloat(importe.replace(".", "").replace(",", "."));
            totalAutorizado += parseFloat(importeTarjeta.replace(".", "").replace(",", "."));
        }
    })

    if(isNaN(totalAutorizado)){
        return;
    }

    let allSpan = document.querySelectorAll('#spanAutorizado');

    allSpan.forEach((element) => {
        let card = element.closest('.card');
        let importe = card.querySelector('.importe-input').value;
        let importeTarjeta = card.querySelector('.importe-input-tarjeta').value;
        totalAutorizado += parseFloat(importe.replace(".", "").replace(",", "."));
        totalAutorizado += parseFloat(importeTarjeta.replace(".", "").replace(",", "."));
    })


    
    $('#totalAutorizado').text('$' + formatearImporte(totalAutorizado));

}

const mostrarPendientesDeAutorizar = () => {
    let semana = $('#semanaRetail').val().split('|')[0];
    let anio = $('#semanaRetail').val().split('|')[1];

    if(!semana || !anio){
        return;
    }

    let pendientesSpan = document.querySelector('#pendientesSpan');

    let supervisorasSelect = document.querySelector('#supervisoraSelect');

    let arraSupervisoras = [];

    supervisorasSelect.querySelectorAll('option').forEach((element) => {
        arraSupervisoras.push(element.value);
    })


    $.ajax({
        url: 'Controller/traerPendientesDeAutorizar.php',
        method: 'POST',
        data:{
            semana: semana,
            anio: anio
        },
        success: function(response) {

            let data = JSON.parse(response);

            let supervisorasConGastosPendientes = [];

            data.forEach((element) => {
                let supervisora = element.SUPERVISORA;
                if(!supervisorasConGastosPendientes.includes(supervisora)){
                    supervisorasConGastosPendientes.push(supervisora);
                }
            })
            pendientesSpan.innerHTML = '';

            supervisorasConGastosPendientes.forEach((element) => {
                let span = document.createElement('span');
                span.classList.add('badge', 'bg-danger', 'ms-1');
                span.innerHTML = element;
                pendientesSpan.appendChild(span);
            })


        
        
         }

    })


}
