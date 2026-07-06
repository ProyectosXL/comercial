// App de Anulación de Pedidos e Inhabilitación de Artículos (AR / UY)
let paisSeleccionado = 'AR';
let pedidosCargados = [];
let articulosCargados = [];
let sugerenciasCargadas = [];

$(document).ready(function() {
    // Configuración global de Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000"
    };

    // Toggle de País (AR / UY)
    $('.country-btn').on('click', function() {
        $('.country-btn').removeClass('active');
        $(this).addClass('active');
        paisSeleccionado = $(this).data('country');
        
        // Actualizar UI
        $('#pais-label-individual').text(paisSeleccionado);
        $('#pais-label-masivo').text(paisSeleccionado);
        $('#pais-label-articulo').text(paisSeleccionado);
        
        // Manejar visibilidad/estado de sugerencias según el país
        actualizarVisibilidadSugerencias();

        toastr.info(`Base de datos cambiada a: ${paisSeleccionado === 'AR' ? 'Argentina (LAKER_SA)' : 'Uruguay (TASKY_SA)'}`);
    });

    // Inicializar estado de sugerencias
    actualizarVisibilidadSugerencias();

    // ===== SUGERENCIAS DE INHABILITACIÓN =====
    $('#btn-cargar-sugerencias').on('click', function() {
        mostrarLoader('Cargando sugerencias desde la base de datos...');

        $.ajax({
            url: 'Controller/ArticuloController.php?action=obtener_sugerencias',
            type: 'POST',
            dataType: 'json',
            data: { pais: paisSeleccionado },
            success: function(response) {
                ocultarLoader();
                if (response.success) {
                    sugerenciasCargadas = response.sugerencias;
                    renderizarTablaSugerencias(response.sugerencias);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                ocultarLoader();
                toastr.error('Error de red al obtener las sugerencias.');
            }
        });
    });

    // Descargar sugerencias en Excel
    $('#btn-descargar-sugerencias').on('click', function() {
        if (sugerenciasCargadas.length === 0) {
            toastr.warning('No hay sugerencias cargadas para descargar.');
            return;
        }

        try {
            // Preparar datos para el Excel
            const excelData = sugerenciasCargadas.map(s => {
                let fechaAlta = '---';
                if (s.FECHA_ALTA) {
                    fechaAlta = s.FECHA_ALTA.date ? s.FECHA_ALTA.date.substring(0, 10) : s.FECHA_ALTA;
                }
                return {
                    'COD_ARTICU': s.COD_ARTICU,
                    'DESCRIPCIO': s.DESCRIPCIO,
                    'FECHA_ALTA': fechaAlta
                };
            });

            // Crear libro y hoja
            const worksheet = XLSX.utils.json_to_sheet(excelData);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Sugerencias');

            // Guardar archivo
            XLSX.writeFile(workbook, 'Sugerencias_Inhabilitacion.xlsx');
            toastr.success('Archivo Excel descargado exitosamente.');
        } catch (err) {
            toastr.error('Error al generar el archivo Excel.');
            console.error(err);
        }
    });


    // ===== ANULACIÓN INDIVIDUAL =====
    $('#formAnularIndividual').on('submit', function(e) {
        e.preventDefault();

        const nroPedido = $('#input_nro_pedido').val().trim();
        const talonPed = $('#input_talon_ped').val().trim();

        if (!nroPedido || !talonPed) {
            toastr.warning('Por favor complete ambos campos.');
            return;
        }

        mostrarLoader('Validando pedido...');

        // AJAX para validar individual
        $.ajax({
            url: 'Controller/PedidoController.php?action=validar_individual',
            type: 'POST',
            dataType: 'json',
            data: {
                nro_pedido: nroPedido,
                talon_ped: talonPed,
                pais: paisSeleccionado
            },
            success: function(response) {
                ocultarLoader();
                if (response.success) {
                    if (response.valido) {
                        // Confirmar anulación
                        Swal.fire({
                            title: '¿Anular Pedido?',
                            html: `<strong>${response.message}</strong><br><br>Esta acción es irreversible.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#94a3b8',
                            confirmButtonText: 'Sí, anular pedido',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                ejecutarAnulacion([{ nro_pedido: nroPedido, talon_ped: talonPed }], true);
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Pedido no válido',
                            text: response.message.replace('❌ ', ''),
                            icon: 'error',
                            confirmButtonColor: '#0284c7'
                        });
                    }
                } else {
                    toastr.error(response.message || 'Error al validar el pedido.');
                }
            },
            error: function() {
                ocultarLoader();
                toastr.error('Error de red al intentar validar el pedido.');
            }
        });
    });

    // ===== ANULACIÓN MASIVA (EXCEL) =====
    // Escuchar selección de archivo
    $('#fileupload').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            procesarExcel(file);
        }
    });

    // Seleccionar Todos los Válidos
    $(document).on('change', '#select_all_valid', function() {
        const isChecked = $(this).is(':checked');
        $('.check-pedido-valido').prop('checked', isChecked);
        actualizarContadorSeleccionados();
    });

    // Cambio en checkbox individual de la tabla preview
    $(document).on('change', '.check-pedido-valido', function() {
        actualizarContadorSeleccionados();
        // Si se desmarca alguno, desmarcar el select all
        if (!$(this).is(':checked')) {
            $('#select_all_valid').prop('checked', false);
        }
    });

    // Botón: Anular Seleccionados
    $('#btnAnularSeleccionados').on('click', function() {
        const seleccionados = [];
        $('.check-pedido-valido:checked').each(function() {
            seleccionados.push({
                nro_pedido: $(this).data('nro'),
                talon_ped: $(this).data('talon')
            });
        });

        if (seleccionados.length === 0) {
            toastr.warning('No has seleccionado ningún pedido válido para anular.');
            return;
        }

        Swal.fire({
            title: `¿Anular ${seleccionados.length} pedidos?`,
            text: 'Se procederá a anular todos los pedidos seleccionados. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Sí, anular seleccionados',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                ejecutarAnulacion(seleccionados, false);
            }
        });
    });

    // Botón: Anular Todos los Válidos
    $('#btnAnularTodosValidos').on('click', function() {
        const validos = pedidosCargados.filter(p => p.valido).map(p => ({
            nro_pedido: p.nro_pedido,
            talon_ped: p.talon_ped
        }));

        if (validos.length === 0) {
            toastr.warning('No hay pedidos válidos para anular.');
            return;
        }

        Swal.fire({
            title: `¿Anular TODOS los pedidos válidos (${validos.length})?`,
            text: 'Se procederá a anular la totalidad de los pedidos válidos identificados. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Sí, anular TODOS los válidos',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                ejecutarAnulacion(validos, false);
            }
        });
    });


    // ===== INHABILITACIÓN MASIVA DE ARTÍCULOS =====
    // Escuchar selección de archivo para artículos
    $('#articulo_fileupload').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            procesarExcelArticulos(file);
        }
    });

    // Confirmar Inhabilitación de Artículos Válidos
    $('#btnInhabilitarValidos').on('click', function() {
        const validos = articulosCargados.filter(a => a.valido);

        if (validos.length === 0) {
            toastr.warning('No hay artículos disponibles para inhabilitar.');
            return;
        }

        Swal.fire({
            title: `¿Inhabilitar ${validos.length} artículos?`,
            text: 'Esta acción actualizará el perfil del artículo a \'N\'. Solo se afectarán artículos válidos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Sí, inhabilitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                ejecutarInhabilitacion(articulosCargados);
            }
        });
    });
});

// Renderizar tabla de sugerencias
function renderizarTablaSugerencias(sugerencias) {
    const tbody = $('#tabla-sugerencias tbody');
    tbody.empty();

    if (sugerencias.length === 0) {
        $('#container-tabla-sugerencias').addClass('d-none');
        $('#mensaje-no-sugerencias').removeClass('d-none').html(`
            <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
            No se encontraron artículos sugeridos para inhabilitar en este momento.
        `);
        $('#btn-descargar-sugerencias').prop('disabled', true);
        $('#card-contador-sugerencias').addClass('d-none');
        return;
    }

    sugerencias.forEach(s => {
        let fechaAlta = '---';
        if (s.FECHA_ALTA) {
            fechaAlta = s.FECHA_ALTA.date ? s.FECHA_ALTA.date.substring(0, 10) : s.FECHA_ALTA;
        }
        tbody.append(`<tr>
            <td><strong>${s.COD_ARTICU}</strong></td>
            <td>${s.DESCRIPCIO || '---'}</td>
            <td>${fechaAlta}</td>
        </tr>`);
    });

    $('#mensaje-no-sugerencias').addClass('d-none');
    $('#container-tabla-sugerencias').removeClass('d-none');
    $('#btn-descargar-sugerencias').prop('disabled', false);
    
    // Mostrar contador
    $('#contador-sugerencias-num').text(sugerencias.length);
    $('#card-contador-sugerencias').removeClass('d-none');
    
    toastr.success(`Se cargaron ${sugerencias.length} sugerencias de inhabilitación.`);
}

// Actualizar UI del paso de sugerencias según el país
function actualizarVisibilidadSugerencias() {
    $('#btn-cargar-sugerencias').prop('disabled', false);
    const paisNombre = (paisSeleccionado === 'AR') ? 'Argentina' : 'Uruguay';
    
    // Al cambiar de país, reiniciar la tabla y las sugerencias cargadas
    $('#tabla-sugerencias tbody').empty();
    $('#container-tabla-sugerencias').addClass('d-none');
    $('#btn-descargar-sugerencias').prop('disabled', true);
    sugerenciasCargadas = [];
    $('#card-contador-sugerencias').addClass('d-none');

    $('#mensaje-no-sugerencias').removeClass('d-none').html(`
        <i class="bi bi-info-circle fs-4 d-block mb-1"></i>
        Presione "Cargar Sugerencias" para ver las sugerencias de la base de datos de ${paisNombre}.
    `);
}

// Procesar Excel de Pedidos
function procesarExcel(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    if (extension !== 'xlsx' && extension !== 'xls') {
        toastr.error('El formato del archivo debe ser .xlsx o .xls.');
        $('#fileupload').val('');
        return;
    }

    mostrarLoader('Leyendo archivo Excel...');

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = e.target.result;
            const workbook = XLSX.read(data, { type: 'binary' });
            const firstSheet = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheet];
            const jsonData = XLSX.utils.sheet_to_json(worksheet);

            // Validar columnas
            if (jsonData.length > 0) {
                const firstRow = jsonData[0];
                if (!firstRow.hasOwnProperty('NRO_PEDIDO') || !firstRow.hasOwnProperty('TALON_PED')) {
                    ocultarLoader();
                    Swal.fire('Estructura Incorrecta', 'El archivo Excel debe contener las columnas "NRO_PEDIDO" y "TALON_PED".', 'error');
                    $('#fileupload').val('');
                    return;
                }
                validarLotePedidos(jsonData);
            } else {
                ocultarLoader();
                toastr.warning('El archivo Excel está vacío.');
                $('#fileupload').val('');
            }
        } catch (err) {
            ocultarLoader();
            toastr.error('Error al leer el archivo Excel.');
        }
    };
    reader.readAsBinaryString(file);
}

// Validar Lote de Pedidos
function validarLotePedidos(lote) {
    mostrarLoader('Validando lista de pedidos...');
    $.ajax({
        url: 'Controller/PedidoController.php?action=validar_lote',
        type: 'POST',
        dataType: 'json',
        data: {
            pedidos: JSON.stringify(lote),
            pais: paisSeleccionado
        },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                pedidosCargados = response.pedidos_data;
                mostrarPreviewTable(response);
            } else {
                Swal.fire('Error', response.message, 'error');
                $('#fileupload').val('');
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al validar lote.');
            $('#fileupload').val('');
        }
    });
}

function mostrarPreviewTable(data) {
    const tbody = $('#modal_tbody_pedidos');
    tbody.empty();

    $('#modal_pedidos_validos').text(data.pedidos_validos);
    $('#modal_pedidos_invalidos').text(data.pedidos_invalidos);
    $('#modal_total_validos').text(data.pedidos_validos);
    $('#modal_total_validos_text').text(data.pedidos_validos);

    $('#select_all_valid').prop('checked', false);
    $('#contador_seleccionados').text('0');

    data.pedidos_data.forEach(function(p) {
        const badgeClass = p.valido ? 'badge-success-modern' : 'badge-danger-modern';
        const checkbox = p.valido 
            ? `<input type="checkbox" class="form-check-input check-pedido-valido" data-nro="${p.nro_pedido}" data-tal="${p.talon_ped}">`
            : `<input type="checkbox" class="form-check-input" disabled>`;

        const row = `<tr>
            <td class="text-center">${checkbox}</td>
            <td><strong>${p.nro_pedido}</strong></td>
            <td>${p.talon_ped}</td>
            <td><span class="badge-modern ${badgeClass}">${p.estado}</span></td>
        </tr>`;
        tbody.append(row);
    });

    if (data.pedidos_validos > 0) {
        $('#btnAnularSeleccionados').prop('disabled', false);
        $('#btnAnularTodosValidos').prop('disabled', false);
        $('#select_all_valid').prop('disabled', false);
    } else {
        $('#btnAnularSeleccionados').prop('disabled', true);
        $('#btnAnularTodosValidos').prop('disabled', true);
        $('#select_all_valid').prop('disabled', true);
    }

    const myModal = new bootstrap.Modal(document.getElementById('modalPrevious'));
    myModal.show();
}

function ejecutarAnulacion(pedidos, esIndividual) {
    mostrarLoader('Anulando pedido(s)...');
    $.ajax({
        url: 'Controller/PedidoController.php?action=anular',
        type: 'POST',
        dataType: 'json',
        data: {
            pedidos: JSON.stringify(pedidos),
            pais: paisSeleccionado
        },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                Swal.fire({
                    title: '¡Anulación Completada!',
                    text: response.message.replace('✅ ', ''),
                    icon: 'success',
                    confirmButtonColor: '#10b981'
                }).then(() => {
                    if (esIndividual) {
                        $('#input_nro_pedido').val('');
                        $('#input_talon_ped').val('');
                    } else {
                        const modalEl = document.getElementById('modalPrevious');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) { modalInstance.hide(); }
                        $('#fileupload').val('');
                    }
                });
            } else {
                Swal.fire('Ocurrió un Problema', response.message, 'warning');
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al intentar anular.');
        }
    });
}

function actualizarContadorSeleccionados() {
    $('#contador_seleccionados').text($('.check-pedido-valido:checked').length);
}


// ===== DETALLE ARTÍCULOS =====
function procesarExcelArticulos(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    if (extension !== 'xlsx' && extension !== 'xls') {
        toastr.error('El formato del archivo debe ser .xlsx o .xls.');
        $('#articulo_fileupload').val('');
        return;
    }

    mostrarLoader('Leyendo archivo Excel...');

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = e.target.result;
            const workbook = XLSX.read(data, { type: 'binary' });
            const firstSheet = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheet];
            const jsonData = XLSX.utils.sheet_to_json(worksheet);

            // Validar columna COD_ARTICU
            if (jsonData.length > 0) {
                const firstRow = jsonData[0];
                if (!firstRow.hasOwnProperty('COD_ARTICU')) {
                    ocultarLoader();
                    Swal.fire('Estructura Incorrecta', 'El archivo Excel debe contener la columna "COD_ARTICU".', 'error');
                    $('#articulo_fileupload').val('');
                    return;
                }
                validarLoteArticulos(jsonData);
            } else {
                ocultarLoader();
                toastr.warning('El archivo Excel está vacío.');
                $('#articulo_fileupload').val('');
            }
        } catch (err) {
            ocultarLoader();
            toastr.error('Error al leer el archivo Excel.');
        }
    };
    reader.readAsBinaryString(file);
}

function validarLoteArticulos(lote) {
    mostrarLoader('Validando lista de artículos...');
    $.ajax({
        url: 'Controller/ArticuloController.php?action=validar_lote',
        type: 'POST',
        dataType: 'json',
        data: {
            articulos: JSON.stringify(lote),
            pais: paisSeleccionado
        },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                articulosCargados = response.articulos_data;
                mostrarPreviewTableArticulos(response);
            } else {
                Swal.fire('Error', response.message, 'error');
                $('#articulo_fileupload').val('');
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al validar artículos.');
            $('#articulo_fileupload').val('');
        }
    });
}

function mostrarPreviewTableArticulos(data) {
    const tbody = $('#modal_tbody_articulos');
    tbody.empty();

    $('#art_disponibles').text(data.cant_disponibles);
    $('#art_ya_inhabilitados').text(data.cant_ya_inhabilitados);
    $('#art_inexistentes').text(data.cant_inexistentes);
    $('#modal_art_total_validos').text(data.cant_disponibles);

    data.articulos_data.forEach(function(a) {
        let badgeClass = 'badge-danger-modern';
        if (a.estado === 'Disponible para inhabilitar') {
            badgeClass = 'badge-success-modern';
        } else if (a.estado === 'Ya se encuentra inhabilitado') {
            badgeClass = 'badge-modern bg-secondary text-white';
        }

        const row = `<tr>
            <td><strong>${a.cod_articu}</strong></td>
            <td>${a.descripcion}</td>
            <td><span class="badge-modern ${badgeClass}">${a.estado}</span></td>
        </tr>`;
        tbody.append(row);
    });

    if (data.cant_disponibles > 0) {
        $('#btnInhabilitarValidos').prop('disabled', false);
    } else {
        $('#btnInhabilitarValidos').prop('disabled', true);
    }

    const myModal = new bootstrap.Modal(document.getElementById('modalPreviousArticulos'));
    myModal.show();
}

function ejecutarInhabilitacion(articulos) {
    mostrarLoader('Inhabilitando artículos...');
    $.ajax({
        url: 'Controller/ArticuloController.php?action=inhabilitar',
        type: 'POST',
        dataType: 'json',
        data: {
            articulos: JSON.stringify(articulos),
            pais: paisSeleccionado
        },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                // Cerrar modal
                const modalEl = document.getElementById('modalPreviousArticulos');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) { modalInstance.hide(); }

                // Mostrar SweetAlert2 con resumen del proceso
                Swal.fire({
                    title: 'Proceso de Inhabilitación Completado',
                    html: `
                        <div class="text-start">
                            <p><strong>Resumen de ejecución:</strong></p>
                            <ul>
                                <li>Artículos procesados: <span class="fw-bold">${response.total_procesados}</span></li>
                                <li>Artículos inhabilitados: <span class="fw-bold text-success">${response.inhabilitados}</span></li>
                                <li>Registros omitidos (ya inhabilitados): <span class="fw-bold text-secondary">${response.omitidos}</span></li>
                                <li>Artículos inexistentes: <span class="fw-bold text-danger">${response.inexistentes}</span></li>
                                <li>Errores presentados: <span class="fw-bold text-warning">${response.errores}</span></li>
                            </ul>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonColor: '#10b981'
                }).then(() => {
                    $('#articulo_fileupload').val('');
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al inhabilitar artículos.');
        }
    });
}


// Loader Helpers
function mostrarLoader(mensaje) {
    $('#loader-text').text(mensaje || 'Procesando...');
    $('#loading-overlay').css('display', 'flex');
}

// Ocultar Loader
function ocultarLoader() {
    $('#loading-overlay').css('display', 'none');
}
