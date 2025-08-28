
$(document).ready(function() {
    // Inicialización de la tabla principal
    let table = $('#gastosTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        },
        columns: [
            { data: 'supervisora' },
            { data: 'concepto' },
            { data: 'importe_tarjeta' },
            { 
                data: null,
                render: function(data, type, row) {
                    return '<button class="btn btn-warning btn-sm verFotos" attr-type="tarjeta" data-id="' + row.id + '"><i class="fas fa-eye"></i></button>';
                },
                className: 'text-center'
            },
            { data: 'importe' }, 
            { 
                data: null,
                render: function(data, type, row) {
                    return '<button class="btn btn-warning btn-sm verFotos" attr-type="efectivo" data-id="' + row.id + '"><i class="fas fa-eye"></i></button>';
                },
                className: 'text-center'
            },
            { 
                data: null,
                render: function(data, type, row) {
                    return '<input type="checkbox" class="form-check-input cargadoCheck" ' + (row.cargado ? 'checked' : '') + '>';
                },
                className: 'text-center cargado-column'
            }
        ],
        order: [[0, 'asc']],
        fixedHeader: true
    });

    // Inicialización de la tabla de gastos pendientes
    let pendientesTable;

    // Eventos de botones principales
    $('#buscarBtn').click(function() {
        buscarGastos();
    });

    $('#verPendientesBtn').click(function() {
        cargarGastosPendientes();
    });

    // Eventos delegados
    $(document).on('click', '.verFotos', function() {
        let id = $(this).data('id');
        let type = $(this).attr('attr-type');
        mostrarImagenes(id, type);
    });

    $(document).on('change', '.cargadoCheck', function() {
        let row = table.row($(this).closest('tr')).data();
        let id = row.id;
        let supervisora = row.supervisora;
        let cargado = $(this).is(':checked');
        let semanaCompleta = $('#semanaRetail').val();
        let [semana, anio] = semanaCompleta.split('|');
    
        actualizarEstadoCargado(id, supervisora, semana, anio, cargado);
    });

    // Función para buscar gastos
    function buscarGastos() {
        let semanaCompleta = $('#semanaRetail').val();
        let supervisora = $('#supervisoraSelect').val();
    
        if (!semanaCompleta) {
            Swal.fire('Error', 'Por favor, seleccione una semana', 'error');
            return;
        }
    
        let [semana, anio] = semanaCompleta.split('|');
    
        // Mostrar indicador de carga
        Swal.fire({
            title: 'Buscando gastos...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
    
        // Primero, obtenemos los gastos
        $.ajax({
            url: 'Controller/buscarGastosControlados.php',
            method: 'POST',
            data: JSON.stringify({ 
                semana: semana, 
                anio: anio, 
                supervisora: supervisora 
            }),
            contentType: 'application/json',
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
               
                if(!response.gastos[0]){
                    Swal.fire('Error', 'Hubo un problema al buscar los gastos', 'error');
                    return 1
                }
            
                actualizarTabla(response.gastos);
                
                let totalGastosTarjeta = 0;
                let totalGastosEfectivo = 0;
                
                response.gastos.forEach(element => {
                    let efectivo = element.importe.replace('.','').replace(',','.');
                    let tarjeta = element.importe_tarjeta.replace('.','').replace(',','.');
               
                    totalGastosEfectivo += parseFloat(efectivo);
                    totalGastosTarjeta += parseFloat(tarjeta);
                });
       
                $.ajax({
                    url: 'Controller/obtenerAdelanto.php',
                    method: 'POST',
                    data: { 
                        semana: semana, 
                        anio: anio, 
                        supervisora: supervisora 
                    },
                    dataType: 'json'
                })
                .done(function(adelantoResponse) {
                    if (adelantoResponse.error) {
                        console.error("Error al obtener adelanto:", adelantoResponse.error);
                        Swal.fire('Error', 'Hubo un problema al obtener el adelanto', 'error');
                    } else {
                        let totalAdelanto = parseFloat(adelantoResponse.adelanto);
                        let totalReintegro = totalGastosTarjeta + totalGastosEfectivo - totalAdelanto;
                        
                        actualizarTotales(totalGastosTarjeta, totalGastosEfectivo, totalAdelanto, totalReintegro);
                        $('#semanaInfo').text('Semana ' + semana);
                        Swal.close();
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error("Error al obtener adelanto:", status, error);
                    Swal.fire('Error', 'Hubo un problema al obtener el adelanto', 'error');
                });
            } else {
                Swal.fire('Error', response.message || 'Hubo un problema al buscar los gastos', 'error');
            }
        })
        .fail(function(xhr, status, error) {
            console.error("Error AJAX:", status, error);
            Swal.fire('Error', 'Hubo un problema al buscar los gastos: ' + error, 'error');
        });
    }
    
    // Función para cargar y mostrar los gastos pendientes
    function cargarGastosPendientes() {
        // Mostrar indicador de carga
        Swal.fire({
            title: 'Cargando gastos pendientes...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: 'Controller/obtenerGastosPendientes.php',
            method: 'GET',
            dataType: 'json'
        })
        .done(function(response) {
            Swal.close();
            
            if (response.success) {
                mostrarModalGastosPendientes(response.gastos);
            } else {
                Swal.fire('Error', response.message || 'Hubo un problema al obtener los gastos pendientes', 'error');
            }
        })
        .fail(function(xhr, status, error) {
            Swal.close();
            console.error("Error AJAX:", status, error);
            Swal.fire('Error', 'Hubo un problema al obtener los gastos pendientes: ' + error, 'error');
        });
    }

    // Función para mostrar el modal con los gastos pendientes
    function mostrarModalGastosPendientes(gastos) {
        // Si la tabla ya existe, destruirla para volver a inicializarla
        if (pendientesTable) {
            pendientesTable.destroy();
        }

        // Inicializar la tabla de gastos pendientes
        pendientesTable = $('#gastosPendientesTable').DataTable({
            data: gastos,
            columns: [
                { data: 'SEMANA' },
                { data: 'ANIO' },
                { data: 'SUPERVISORA' },
                { 
                    data: 'IMPORTE',
                    render: function(data, type, row) {
                        if (type === 'display' || type === 'filter') {
                            return formatearImporte(data);
                        }
                        return data;
                    }
                },
                { 
                    data: 'IMPORTE_TARJETA',
                    render: function(data, type, row) {
                        if (type === 'display' || type === 'filter') {
                            return formatearImporte(data);
                        }
                        return data;
                    }
                },
                { data: 'OBSERVACIONES' }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
            },
            order: [[1, 'desc'], [0, 'desc']],
            fixedHeader: true
        });

        // Mostrar el modal
        $('#gastosPendientesModal').modal('show');
    }
    
    // Función para actualizar los totales mostrados
    function actualizarTotales(gastosTarjeta, gastosEfectivo, adelanto, reintegro) {
        $('#totalGastosTarjeta').text(formatearImporte(gastosTarjeta));
        $('#totalGastosEfectivo').text(formatearImporte(gastosEfectivo));
        $('#totalAdelanto').text(formatearImporte(adelanto));
        $('#totalReintegro').text(formatearImporte(reintegro));
    }
    
    // Función para formatear importes sin decimales
    function formatearImporte(importe) {
        // Redondear al entero más cercano
        importe = Math.round(importe);
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(importe);
    }
 
    // Función para actualizar la tabla principal
    function actualizarTabla(gastos) {
        table.clear();
        table.rows.add(gastos).draw();
    }

    // Función para mostrar imágenes
    function mostrarImagenes(id_conc, type) {
        const semanaCompleta = $('#semanaRetail').val();
        const supervisora    = $('#supervisoraSelect').val();

        if (!semanaCompleta || !supervisora) {
            Swal.fire('Error', 'Por favor, seleccione una semana y una supervisora', 'error');
            return;
        }
        const [semana, anio] = semanaCompleta.split('|');

        // Usamos POST exactamente como antes, pero sin Swal
        $.ajax({
            url: 'Controller/obtenerImagenesSup.php',
            method: 'POST',
            data: JSON.stringify({ id_conc, semana, anio, supervisora, type }),
            contentType: 'application/json',
            dataType: 'json',
            beforeSend: () => {
                // Fancybox mostrará su propio indicador
            }
        })
        .done(function (response) {
            if (response.success && response.imagenes && response.imagenes.length) {
                const fancyboxImages = response.imagenes.map((src, i) => ({
                    src: src,
                    caption: `Imagen ${i + 1} de ${response.imagenes.length}`
                }));
                Fancybox.show(fancyboxImages, {
                    loop: false,
                    buttons: ['zoom', 'close'],
                    slideShow: { autoStart: false }
                });
            } else {
                Swal.fire('Sin imágenes', response.message || 'No se encontraron imágenes', 'info');
            }
        })
        .fail(function (xhr) {
            Swal.fire('Error', 'No se pudieron cargar las imágenes', 'error');
        });
    }
    
    // Función para actualizar el estado de cargado
    function actualizarEstadoCargado(id, supervisora, semana, anio, cargado) {
        $.ajax({
            url: 'Controller/actualizarEstadoCargado.php',
            method: 'POST',
            data: JSON.stringify({ 
                id: id,
                supervisora: supervisora,
                semana: semana,
                anio: anio,
                cargado: cargado 
            }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log('Estado actualizado correctamente');
                } else {
                    Swal.fire('Error', response.message || 'Hubo un problema al actualizar el estado', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error AJAX:", status, error);
                console.log("Respuesta del servidor:", xhr.responseText);
                Swal.fire({
                    title: 'Error',
                    html: 'Hubo un problema al actualizar el estado: ' + error + '<br><pre>' + xhr.responseText + '</pre>',
                    icon: 'error'
                });
            }
        });
    }
});