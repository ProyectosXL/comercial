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
                data: 'cae_tarjeta',
                render: function(data, type, row) {
                    let disabled = row.cargado ? 'disabled' : '';
                    return '<input type="text" class="form-control form-control-sm cae-tarjeta" maxlength="20" value="' + (data || '') + '" ' + disabled + '>';
                }
            },
            { 
                data: null,
                render: function(data, type, row) {
                    return '<button class="btn btn-warning btn-sm verFotos" attr-type="tarjeta" data-id="' + row.id + '"><i class="fas fa-eye"></i></button>';
                },
                className: 'text-center'
            },
            { 
                data: null,
                render: function(data, type, row) {
                    let disabled = row.cargado_tarj ? 'disabled' : '';
                    let checked = row.cargado_tarj ? 'checked' : '';
                    return '<input type="checkbox" class="form-check-input cargadoTarjetaCheck" ' + checked + ' ' + disabled + '>';
                },
                className: 'text-center cargado-tarjeta-column'
            },
            { data: 'importe' },
            {
                data: 'cae_efectivo',
                render: function(data, type, row) {
                    let disabled = row.cargado ? 'disabled' : '';
                    return '<input type="text" class="form-control form-control-sm cae-efectivo" maxlength="20" value="' + (data || '') + '" ' + disabled + '>';
                }
            },
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
                    let disabled = row.cargado ? 'disabled' : '';
                    return '<input type="checkbox" class="form-check-input cargadoCheck" ' + (row.cargado ? 'checked ' + disabled : '') + '>';
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

    $(document).on('change', '.cargadoTarjetaCheck', function() {
        let row = table.row($(this).closest('tr')).data();
        let id = row.id;
        let supervisora = row.supervisora;
        let cargado = $(this).is(':checked');
        let semanaCompleta = $('#semanaRetail').val();
        let [semana, anio] = semanaCompleta.split('|');
    
        actualizarEstadoCargadoTarjeta(id, supervisora, semana, anio, cargado);
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

    // Manejador de eventos para el checkbox de cargado de tarjeta
    $(document).on('click', '.cargadoTarjetaCheck', function(e) {
        const row = table.row($(this).closest('tr'));
        const data = row.data();
        const $checkbox = $(this);

        // Si el checkbox ya estaba marcado, no permitir desmarcarlo
        if (data.cargado_tarj) {
            e.preventDefault();
            return false;
        }

        // Proceder con el guardado sin validaciones adicionales
        if ($checkbox.prop('checked')) {
            // Deshabilitar checkbox
            $checkbox.prop('disabled', true);

            const semanaCompleta = $('#semanaRetail').val();
            const supervisora = $('#supervisoraSelect').val();

            if (!semanaCompleta) {
                Swal.fire('Error', 'Debe seleccionar una semana', 'error');
                return false;
            }

            if (!supervisora) {
                Swal.fire('Error', 'Debe seleccionar una supervisora', 'error');
                return false;
            }

            const [semana, anio] = semanaCompleta.split('|');

            if (!semana || !anio) {
                Swal.fire('Error', 'Error al procesar la semana seleccionada', 'error');
                return false;
            }

            console.log('Guardando datos:', {
                id: data.id,
                supervisora: supervisora,
                semana: semana,
                anio: anio
            });

            $.ajax({
                url: 'Controller/actualizarCAETarjeta.php',
                method: 'POST',
                data: JSON.stringify({
                    id: data.id,
                    supervisora: supervisora,
                    semana: semana,
                    anio: anio
                }),
                contentType: 'application/json',
                success: function(response) {
                    console.log('Respuesta del servidor:', response);

                    if (response.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: 'Guardado y marcado como cargado correctamente',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            // Actualizar la tabla después de cerrar el mensaje
                            buscarGastos();
                        });
                    } else {
                        console.error('Error en la respuesta:', response);
                        let errorMsg = response.message || 'Error al guardar CAE de tarjeta';
                        
                        // Mostrar error y revertir cambios en la UI
                        Swal.fire({
                            title: 'Error',
                            text: errorMsg,
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            $checkbox.prop('checked', false).prop('disabled', false);
                            // Refrescar los datos en caso de error también
                            buscarGastos();
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', {xhr, status, error});
                    console.log('Respuesta del servidor:', xhr.responseText);
                    
                    let errorMsg = 'Error al guardar CAE de tarjeta';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMsg = response.message;
                        }
                    } catch(e) {
                        errorMsg += ': ' + error;
                        if (xhr.responseText) {
                            errorMsg += '\n' + xhr.responseText;
                        }
                    }
                    
                    // Mostrar mensaje de advertencia
                    Swal.fire({
                        title: 'Advertencia',
                        html: 'El registro parece haberse actualizado pero hubo un error en la respuesta:<br><br>' + 
                             errorMsg.replace(/\n/g, '<br>'),
                        icon: 'warning',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Refrescar los datos para verificar el estado actual
                        buscarGastos();
                    });
                }
            });
        }
    });

    // Manejador de eventos para el checkbox de cargado
    $(document).on('click', '.cargadoCheck', function(e) {
        const row = table.row($(this).closest('tr'));
        const data = row.data();
        const $checkbox = $(this);
        
        // Si el checkbox ya estaba marcado, no permitir desmarcarlo
        if (data.cargado) {
            e.preventDefault();
            return false;
        }

        // Si pasa todas las validaciones, deshabilitamos los campos CAE
        if ($checkbox.prop('checked')) {
            $checkbox.prop('disabled', true);

            // Guardar los valores CAE en la base de datos
            const semanaCompleta = $('#semanaRetail').val();
            const supervisora = $('#supervisoraSelect').val();

            if (!semanaCompleta) {
                Swal.fire('Error', 'Debe seleccionar una semana', 'error');
                return false;
            }

            if (!supervisora) {
                Swal.fire('Error', 'Debe seleccionar una supervisora', 'error');
                return false;
            }

            const [semana, anio] = semanaCompleta.split('|');
            
            if (!semana || !anio) {
                Swal.fire('Error', 'Error al procesar la semana seleccionada', 'error');
                return false;
            }

            console.log('Enviando datos:', {
                id: data.id,
                supervisora: supervisora,
                semana: semana,
                anio: anio
            });

            $.ajax({
                url: 'Controller/actualizarCAE.php',
                method: 'POST',
                data: JSON.stringify({
                    id: data.id,
                    supervisora: supervisora,
                    semana: semana,
                    anio: anio
                }),
                contentType: 'application/json',
                success: function(response) {
                    console.log('Respuesta del servidor:', response);
                    
                    // Primero actualizamos la UI
                    if (response.success) {
                        // Actualizar el estado visual de los campos
                        $checkbox.prop('checked', true).prop('disabled', true);
                        
                        // Mostrar mensaje de éxito
                        Swal.fire({
                            title: 'Éxito',
                            text: 'CAE guardado correctamente',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            // Actualizar la tabla después de cerrar el mensaje
                            buscarGastos();
                        });
                    } else {
                        console.error('Error en la respuesta:', response);
                        let errorMsg = response.message || 'Error al guardar CAE';
                        if (response.sqlErrors) {
                            errorMsg += '\n' + JSON.stringify(response.sqlErrors);
                        }
                        
                        // Mostrar error y revertir cambios en la UI
                        Swal.fire({
                            title: 'Error',
                            html: errorMsg.replace(/\n/g, '<br>'),
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            $checkbox.prop('checked', false).prop('disabled', false);
                            // Refrescar los datos en caso de error también
                            buscarGastos();
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', {xhr, status, error});
                    console.log('Respuesta del servidor:', xhr.responseText);
                    
                    let errorMsg = 'Error al guardar CAE';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMsg = response.message;
                        }
                    } catch(e) {
                        errorMsg += ': ' + error;
                        if (xhr.responseText) {
                            errorMsg += '\n' + xhr.responseText;
                        }
                    }
                    
                    // Verificar si el registro se actualizó a pesar del error
                    const semanaCompleta = $('#semanaRetail').val();
                    const [semana, anio] = semanaCompleta.split('|');
                    const supervisora = $('#supervisoraSelect').val();

                    // Mostrar mensaje de advertencia
                    Swal.fire({
                        title: 'Advertencia',
                        html: 'El registro parece haberse actualizado pero hubo un error en la respuesta:<br><br>' + 
                             errorMsg.replace(/\n/g, '<br>'),
                        icon: 'warning',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Refrescar los datos para verificar el estado actual
                        buscarGastos();
                    });
                }
            });
        }
    });

    // Función para mostrar imágenes
    function mostrarImagenes(id_conc, type) {
        const semanaCompleta = $('#semanaRetail').val();
        let supervisora = $('#supervisoraSelect').val();

        if (!semanaCompleta) {
            Swal.fire('Error', 'Por favor, seleccione una semana', 'error');
            return;
        }

        // Si no hay supervisora seleccionada en el filtro, buscar la supervisora de la fila específica
        if (!supervisora) {
            // Buscar en la tabla la fila que corresponde a este id_conc
            let rowData = null;
            table.rows().every(function() {
                const data = this.data();
                if (data.id == id_conc) {
                    rowData = data;
                    return false; // break the loop
                }
            });

            if (rowData && rowData.supervisora) {
                supervisora = rowData.supervisora;
            } else {
                Swal.fire('Error', 'No se pudo determinar la supervisora para este gasto', 'error');
                return;
            }
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

    // Función para actualizar el estado de cargado de tarjeta
    function actualizarEstadoCargadoTarjeta(id, supervisora, semana, anio, cargado) {
        $.ajax({
            url: 'Controller/actualizarEstadoCargadoTarjeta.php',
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
                    console.log('Estado de cargado de tarjeta actualizado correctamente');
                    Swal.fire({
                        title: 'Éxito',
                        text: 'Estado de cargado de tarjeta actualizado correctamente',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', response.message || 'Hubo un problema al actualizar el estado de cargado de tarjeta', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error AJAX:", status, error);
                console.log("Respuesta del servidor:", xhr.responseText);
                Swal.fire({
                    title: 'Error',
                    html: 'Hubo un problema al actualizar el estado de cargado de tarjeta: ' + error + '<br><pre>' + xhr.responseText + '</pre>',
                    icon: 'error'
                });
            }
        });
    }

});