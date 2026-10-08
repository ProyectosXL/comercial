// App de Anulación de Pedidos e Inhabilitación de Artículos (AR / UY)
let paisSeleccionado = 'AR';
let pedidosCargados = [];
let articulosCargados = [];
let sugerenciasCargadas = [];
let edicionMasivaCargada = {
    pedidosValidos: [],
    filasData: []
};

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
        const btn = this;
        // Si hay una edición de pedido sin confirmar, pedir confirmación antes de cambiar
        confirmarDescarteEdicion().then(function(continuar) {
            if (!continuar) { return; }

            $('.country-btn').removeClass('active');
            $(btn).addClass('active');
            paisSeleccionado = $(btn).data('country');

            // Actualizar UI
            $('#pais-label-individual').text(paisSeleccionado);
            $('#pais-label-masivo').text(paisSeleccionado);
            $('#pais-label-articulo').text(paisSeleccionado);
            $('#pais-label-edicion').text(paisSeleccionado);

            // Manejar visibilidad/estado de sugerencias según el país
            actualizarVisibilidadSugerencias();
            cargarRubros();
            limpiarEdicion();
            refrescarHistorialSiVisible();
            refrescarHistorialAnulacionesSiVisible();

            toastr.info(`Base de datos cambiada a: ${paisSeleccionado === 'AR' ? 'Argentina (LAKER_SA)' : 'Uruguay (TASKY_SA)'}`);
        });
    });

    // Select/deselect all rubros
    $('#btn-select-all-rubros').on('click', function() {
        $('#select-rubros option').prop('selected', true);
    });

    $('#btn-deselect-all-rubros').on('click', function() {
        $('#select-rubros option').prop('selected', false);
    });

    // Inicializar estado de sugerencias y rubros
    actualizarVisibilidadSugerencias();
    cargarRubros();

    // ===== SUGERENCIAS DE INHABILITACIÓN =====
    $('#btn-cargar-sugerencias').on('click', function() {
        mostrarLoader('Cargando sugerencias desde la base de datos...');

        const rubrosSeleccionados = $('#select-rubros').val();

        $.ajax({
            url: 'Controller/ArticuloController.php?action=obtener_sugerencias',
            type: 'POST',
            dataType: 'json',
            data: { 
                pais: paisSeleccionado,
                rubros: rubrosSeleccionados 
            },
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
            const excelData = sugerenciasCargadas.map(s => {
                let fechaAlta = '---';
                if (s.FECHA_ALTA) {
                    fechaAlta = s.FECHA_ALTA.date ? s.FECHA_ALTA.date.substring(0, 10) : s.FECHA_ALTA;
                }
                return {
                    'COD_ARTICU': s.COD_ARTICU,
                    'DESCRIPCIO': s.DESCRIPCIO,
                    'RUBRO': s.RUBRO || '---',
                    'FECHA_ALTA': fechaAlta
                };
            });

            const worksheet = XLSX.utils.json_to_sheet(excelData);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Sugerencias');

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
                        Swal.fire({
                            title: '¿Anular Pedido?',
                            html: `<strong>${response.message}</strong><br><br>Esta acción es irreversible y liberará el stock comprometido.`,
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
    $('#articulo_fileupload').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            procesarExcelArticulos(file);
        }
    });

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

    initHistorialAnulaciones();
    initEdicionPedido();
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
            <td><span class="badge bg-light text-dark border">${s.RUBRO || '---'}</span></td>
            <td>${fechaAlta}</td>
        </tr>`);
    });

    $('#mensaje-no-sugerencias').addClass('d-none');
    $('#container-tabla-sugerencias').removeClass('d-none');
    $('#btn-descargar-sugerencias').prop('disabled', false);
    
    $('#contador-sugerencias-num').text(sugerencias.length);
    $('#card-contador-sugerencias').removeClass('d-none');
    
    toastr.success(`Se cargaron ${sugerencias.length} sugerencias de inhabilitación.`);
}

// Actualizar UI del paso de sugerencias según el país
function actualizarVisibilidadSugerencias() {
    $('#btn-cargar-sugerencias').prop('disabled', false);
    const paisNombre = (paisSeleccionado === 'AR') ? 'Argentina' : 'Uruguay';
    
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

    $('#select_all_valid').prop('checked', false);
    $('#contador_seleccionados').text('0');

    data.pedidos_data.forEach(function(p) {
        const badgeClass = p.valido ? 'badge-success-modern' : 'badge-danger-modern';
        const checkbox = p.valido 
            ? `<input type="checkbox" class="form-check-input check-pedido-valido" data-nro="${p.nro_pedido}" data-talon="${p.talon_ped}">`
            : `<input type="checkbox" class="form-check-input" disabled>`;

        const cliente = p.cod_client ? `${escapeHtml(p.cod_client)} ${p.razon_soci ? '- ' + escapeHtml(p.razon_soci) : ''}` : '---';

        const row = `<tr>
            <td class="text-center">${checkbox}</td>
            <td><strong>${escapeHtml(p.nro_pedido)}</strong></td>
            <td>${escapeHtml(p.talon_ped)}</td>
            <td><span class="text-secondary">${cliente}</span></td>
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

    const myModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPrevious'));
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
            pais: paisSeleccionado,
            usuario: $('body').attr('data-username') || '',
            origen: esIndividual ? 'INDIVIDUAL' : 'MASIVO'
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
                    refrescarHistorialAnulacionesSiVisible();
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
            <td><strong>${escapeHtml(a.cod_articu)}</strong></td>
            <td>${escapeHtml(a.descripcion)}</td>
            <td><span class="badge-modern ${badgeClass}">${a.estado}</span></td>
        </tr>`;
        tbody.append(row);
    });

    if (data.cant_disponibles > 0) {
        $('#btnInhabilitarValidos').prop('disabled', false);
    } else {
        $('#btnInhabilitarValidos').prop('disabled', true);
    }

    const myModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPreviousArticulos'));
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
                const modalEl = document.getElementById('modalPreviousArticulos');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) { modalInstance.hide(); }

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

function ocultarLoader() {
    $('#loading-overlay').css('display', 'none');
}

// Cargar Rubros para el selector
function cargarRubros() {
    mostrarLoader('Cargando lista de rubros...');
    $.ajax({
        url: 'Controller/ArticuloController.php?action=obtener_rubros',
        type: 'POST',
        dataType: 'json',
        data: { pais: paisSeleccionado },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                const select = $('#select-rubros');
                select.empty();
                response.rubros.forEach(r => {
                    select.append(`<option value="${r.RUBRO}">${r.RUBRO}</option>`);
                });
            } else {
                toastr.error(response.message || 'Error al obtener la lista de rubros.');
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al obtener rubros.');
        }
    });
}


// ==========================================
// ===== HISTORIAL DE ANULACIÓN DE PEDIDOS =====
// ==========================================
const historialAnulacion = {
    anulaciones: []
};

function initHistorialAnulaciones() {
    $('#btn-toggle-historial-anulaciones').on('click', function() {
        if ($('#anulacion-historial').hasClass('d-none')) {
            abrirHistorialAnulaciones({});
        } else {
            $('#anulacion-historial').addClass('d-none');
        }
    });

    $('#btn-cerrar-historial-anulacion').on('click', function() {
        $('#anulacion-historial').addClass('d-none');
    });

    $('#formHistorialAnulacion').on('submit', function(e) {
        e.preventDefault();
        cargarHistorialAnulaciones();
    });

    $('#btn-limpiar-historial-anul').on('click', function() {
        setFiltrosHistorialAnulacion({});
        cargarHistorialAnulaciones();
    });

    $('#historial-anulacion-busqueda').on('input', aplicarBusquedaHistorialAnulacion);

    $('#btn-descargar-historial-anulacion').on('click', descargarExcelHistorialAnulaciones);
}

function setFiltrosHistorialAnulacion(filtros) {
    $('#historial_anul_desde').val(filtros.desde || fechaISO(sumarDias(new Date(), -30)));
    $('#historial_anul_hasta').val(filtros.hasta || fechaISO(new Date()));
    $('#historial_anul_usuario').val(filtros.usuario || '');
    $('#historial_anul_talon').val(filtros.talon || '');
    $('#historial_anul_nro').val(filtros.nro || '');
    $('#historial_anul_cliente').val(filtros.cliente || '');
}

function abrirHistorialAnulaciones(filtros) {
    setFiltrosHistorialAnulacion(filtros);
    $('#historial-anulacion-busqueda').val('');
    $('#anulacion-historial').removeClass('d-none');
    cargarHistorialAnulaciones(function() {
        document.getElementById('anulacion-historial').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function refrescarHistorialAnulacionesSiVisible() {
    if (!$('#anulacion-historial').hasClass('d-none')) {
        cargarHistorialAnulaciones();
    }
}

function cargarHistorialAnulaciones(onLoad) {
    const desde = $('#historial_anul_desde').val();
    const hasta = $('#historial_anul_hasta').val();
    const talon = $('#historial_anul_talon').val().trim();

    if (!desde || !hasta) {
        toastr.warning('Indique el rango de fechas.');
        return;
    }
    if (talon !== '' && !/^\d+$/.test(talon)) {
        toastr.warning('El talón debe ser numérico.');
        return;
    }

    mostrarLoader('Consultando historial de anulaciones...');
    $.ajax({
        url: 'Controller/PedidoController.php?action=historial',
        type: 'POST',
        dataType: 'json',
        data: {
            pais: paisSeleccionado,
            desde: desde,
            hasta: hasta,
            usuario_filtro: $('#historial_anul_usuario').val().trim(),
            talon_ped: talon,
            nro_pedido: $('#historial_anul_nro').val().trim(),
            cliente: $('#historial_anul_cliente').val().trim()
        },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                historialAnulacion.anulaciones = response.anulaciones || [];
                renderHistorialAnulaciones(response.truncado);
                if (onLoad) { onLoad(); }
            } else {
                Swal.fire('No se pudo consultar el historial', response.message, 'warning');
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al consultar el historial de anulaciones.');
        }
    });
}

function renderHistorialAnulaciones(truncado) {
    const tbody = $('#tbody-historial-anulaciones').empty();

    if (historialAnulacion.anulaciones.length === 0) {
        tbody.append('<tr class="row-sin-resultados"><td colspan="7" class="text-center text-muted py-3">No hay anulaciones registradas para los filtros indicados.</td></tr>');
        $('#historial-anul-info').addClass('d-none');
        return;
    }

    historialAnulacion.anulaciones.forEach(function(a, i) {
        const usuario = a.USUARIO ? escapeHtml(a.USUARIO) : '<span class="usuario-vacio">Sin usuario</span>';
        const clienteDesc = a.COD_CLIENT ? `${escapeHtml(a.COD_CLIENT)} ${a.RAZON_SOCI ? '- ' + escapeHtml(a.RAZON_SOCI) : ''}` : '---';
        const origenBadge = a.ORIGEN === 'MASIVO' 
            ? '<span class="badge-modern badge-primary-modern">Masivo</span>' 
            : '<span class="badge-modern">Individual</span>';

        tbody.append(`<tr class="row-anulacion" data-idx="${i}">
            <td class="num text-start">${escapeHtml(a.FECHA)}</td>
            <td>${usuario}</td>
            <td>${escapeHtml(a.TALON_PED)}</td>
            <td><strong>${escapeHtml(a.NRO_PEDIDO)}</strong></td>
            <td>${clienteDesc}</td>
            <td class="num">$ ${formatearImporte(a.TOTAL_PEDI)}</td>
            <td>${origenBadge}</td>
        </tr>`);
    });

    tbody.append('<tr class="row-sin-resultados-busqueda d-none"><td colspan="7" class="text-center text-muted py-3">No hay anulaciones que coincidan con la búsqueda.</td></tr>');

    const info = $('#historial-anul-info');
    if (truncado) {
        info.text(`Se muestran las ${historialAnulacion.anulaciones.length} anulaciones más recientes. Acote los filtros para ver otras.`).removeClass('d-none');
    } else {
        info.addClass('d-none');
    }

    aplicarBusquedaHistorialAnulacion();
}

function aplicarBusquedaHistorialAnulacion() {
    const texto = normalizarBusqueda($('#historial-anulacion-busqueda').val());
    let visibles = 0;

    historialAnulacion.anulaciones.forEach(function(a, i) {
        const match = texto === ''
            || normalizarBusqueda(a.NRO_PEDIDO).indexOf(texto) !== -1
            || normalizarBusqueda(a.TALON_PED).indexOf(texto) !== -1
            || normalizarBusqueda(a.USUARIO).indexOf(texto) !== -1
            || normalizarBusqueda(a.COD_CLIENT).indexOf(texto) !== -1
            || normalizarBusqueda(a.RAZON_SOCI).indexOf(texto) !== -1
            || normalizarBusqueda(a.ORIGEN).indexOf(texto) !== -1;

        $(`#tbody-historial-anulaciones tr.row-anulacion[data-idx="${i}"]`).toggleClass('d-none', !match);
        if (match) { visibles++; }
    });

    $('#tbody-historial-anulaciones tr.row-sin-resultados-busqueda').toggleClass('d-none', visibles > 0 || historialAnulacion.anulaciones.length === 0);
}

function descargarExcelHistorialAnulaciones() {
    if (!historialAnulacion.anulaciones || historialAnulacion.anulaciones.length === 0) {
        toastr.warning('No hay datos en el historial para descargar.');
        return;
    }

    try {
        const data = historialAnulacion.anulaciones.map(a => ({
            'FECHA': a.FECHA,
            'USUARIO': a.USUARIO || 'Sin usuario',
            'TALON': a.TALON_PED,
            'NRO_PEDIDO': a.NRO_PEDIDO,
            'COD_CLIENT': a.COD_CLIENT || '',
            'RAZON_SOCIAL': a.RAZON_SOCI || '',
            'TOTAL_PEDIDO': Number(a.TOTAL_PEDI) || 0,
            'ORIGEN': a.ORIGEN || 'INDIVIDUAL'
        }));

        const ws = XLSX.utils.json_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Historial_Anulaciones');

        const fechaStr = fechaISO(new Date());
        XLSX.writeFile(wb, `Historial_Anulaciones_${paisSeleccionado}_${fechaStr}.xlsx`);
        toastr.success('Historial de anulaciones exportado a Excel.');
    } catch (e) {
        console.error(e);
        toastr.error('Error al generar el archivo Excel.');
    }
}


// ==========================================
// ===== EDITAR PEDIDO (INDIVIDUAL Y MASIVO) =====
// ==========================================
const edicion = {
    pedido: null,
    renglones: [],
    cambios: {},
    cambiosSimulados: null,
    expandidos: {}
};

function initEdicionPedido() {
    $('#formBuscarEdicion').on('submit', function(e) {
        e.preventDefault();
        buscarPedidoEdicion();
    });

    $(document).on('click', '#tbody-edicion .btn-expand', function() {
        const nRenglon = $(this).data('renglon');
        edicion.expandidos[nRenglon] = !edicion.expandidos[nRenglon];
        $(this).toggleClass('expanded', edicion.expandidos[nRenglon]);
        aplicarBusquedaEdicion();
    });

    $(document).on('input change', '#tbody-edicion .input-cantidad', function() {
        registrarCantidadEdicion($(this));
    });

    $(document).on('click', '#tbody-edicion .btn-quitar', function() {
        const nRenglon = Number($(this).data('renglon'));
        const r = buscarRenglon(nRenglon);
        if (!r) { return; }

        const cambio = edicion.cambios[nRenglon];
        if (cambio !== undefined && cambio.cantidad === 0) {
            delete edicion.cambios[nRenglon];
        } else {
            edicion.cambios[nRenglon] = { n_renglon: nRenglon, cod_articu: r.COD_ARTICU, cantidad: 0 };
        }
        actualizarFilaEdicion(nRenglon);
    });

    $('#btn-descartar-edicion').on('click', function() {
        edicion.cambios = {};
        renderGrillaEdicion();
    });

    $('#edicion-busqueda').on('input', aplicarBusquedaEdicion);

    $('#btn-revisar-edicion').on('click', simularEdicion);
    $('#btn-confirmar-edicion').on('click', aplicarEdicion);

    // Edición masiva por Excel
    $('#edicion_fileupload').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            procesarExcelEdicionMasiva(file);
        }
    });

    $('#btnAplicarEdicionMasiva').on('click', aplicarEdicionMasivaConfirmada);

    initHistorialEdiciones();
}

function registrarCantidadEdicion($input) {
    const nRenglon = Number($input.data('renglon'));
    const r = buscarRenglon(nRenglon);
    if (!r) { return; }

    const actual = Number(r.CANT_PEDID);
    const texto = String($input.val()).trim();
    const el = $input.get(0);

    if (texto === '' && !(el.validity && el.validity.badInput)) {
        $input.removeClass('is-invalid');
        delete edicion.cambios[nRenglon];
    } else {
        const cantidad = Number(texto);
        const valida = /^\d+$/.test(texto) && cantidad >= 1 && cantidad <= actual - 1;
        $input.toggleClass('is-invalid', !valida);
        if (valida) {
            edicion.cambios[nRenglon] = { n_renglon: nRenglon, cod_articu: r.COD_ARTICU, cantidad: cantidad };
        } else {
            delete edicion.cambios[nRenglon];
        }
    }
    actualizarFilaEdicion(nRenglon, true);
}

function actualizarFilaEdicion(nRenglon, desdeInput) {
    const r = buscarRenglon(nRenglon);
    const cambio = edicion.cambios[nRenglon];
    const quitado = cambio !== undefined && cambio.cantidad === 0;
    const modificado = cambio !== undefined && cambio.cantidad > 0;
    const $fila = $(`#tbody-edicion tr.row-principal[data-renglon="${nRenglon}"]`);

    $fila.toggleClass('row-quitado', quitado).toggleClass('row-modificado', modificado);

    if (!desdeInput) {
        const $input = $fila.find('.input-cantidad');
        if (quitado) {
            $input.val('').removeClass('is-invalid').prop('disabled', true);
        } else {
            $input.prop('disabled', Number(r.CANT_PEDID) <= 1);
        }
        $fila.find('.btn-quitar').replaceWith(botonAccionEdicion(nRenglon, quitado));
    }

    actualizarBarraEdicion();
}

function actualizarBarraEdicion() {
    const cantidad = Object.keys(edicion.cambios).length;
    $('#edicion-pendientes')
        .text(cantidad > 0 ? `${cantidad} cambio(s) pendiente(s)` : 'Sin cambios pendientes')
        .toggleClass('has-cambios', cantidad > 0);
    $('#btn-descartar-edicion, #btn-revisar-edicion').prop('disabled', cantidad === 0);
}

function listaCambiosEdicion() {
    return Object.values(edicion.cambios).sort((a, b) => a.n_renglon - b.n_renglon);
}

function simularEdicion() {
    const cambios = listaCambiosEdicion();
    if (cambios.length === 0) { return; }

    const principales = renglonesPrincipales();
    const quitados = principales.filter(r => {
        const c = edicion.cambios[r.N_RENGLON];
        return c !== undefined && c.cantidad === 0;
    });
    if (principales.length > 0 && quitados.length === principales.length) {
        Swal.fire('Atención', 'No se pueden quitar todos los renglones. Para eso usá Anular Pedidos.', 'warning');
        return;
    }

    edicion.cambiosSimulados = JSON.stringify(cambios);
    llamarEdicion('simular', edicion.cambiosSimulados, 'Simulando cambios...', function(response) {
        renderVistaPreviaEdicion(response);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarEdicion')).show();
    });
}

function aplicarEdicion() {
    if (!edicion.cambiosSimulados) { return; }

    llamarEdicion('aplicar', edicion.cambiosSimulados, 'Aplicando cambios...', function(response) {
        cerrarModalEdicion();
        toastr.success(`Pedido actualizado. Total: $ ${formatearImporte(response.resumen.TOTAL_PEDI_NUEVO)}`);
        edicion.cambios = {};
        edicion.cambiosSimulados = null;
        consultarPedidoEdicion(edicion.pedido.TALON_PED, edicion.pedido.NRO_PEDIDO);
        refrescarHistorialSiVisible();
    });
}

function llamarEdicion(action, cambiosJson, mensajeLoader, onSuccess) {
    mostrarLoader(mensajeLoader);
    $.ajax({
        url: `Controller/EdicionPedidoController.php?action=${action}`,
        type: 'POST',
        dataType: 'json',
        data: {
            pais: paisSeleccionado,
            talon_ped: edicion.pedido.TALON_PED,
            nro_pedido: edicion.pedido.NRO_PEDIDO,
            cambios: cambiosJson,
            usuario: $('body').attr('data-username') || ''
        },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                onSuccess(response);
            } else {
                cerrarModalEdicion();
                mostrarErrorEdicion(response);
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al procesar la edición del pedido.');
        }
    });
}

function cerrarModalEdicion() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEdicion'));
    if (modal) { modal.hide(); }
}

function mostrarErrorEdicion(response) {
    const pedidoCambio = Number(response.codigo) === 51004;
    Swal.fire({
        title: 'No se pudo editar el pedido',
        text: response.message,
        icon: 'error',
        confirmButtonColor: '#3366ff',
        confirmButtonText: pedidoCambio ? 'Recargar pedido' : 'Entendido',
        showCancelButton: pedidoCambio,
        cancelButtonText: 'Cerrar'
    }).then(result => {
        if (pedidoCambio && result.isConfirmed) {
            edicion.cambios = {};
            edicion.cambiosSimulados = null;
            consultarPedidoEdicion(edicion.pedido.TALON_PED, edicion.pedido.NRO_PEDIDO);
        }
    });
}

function ordenarConComponentes(lista) {
    const renglones = (lista || []).slice().sort((a, b) => Number(a.N_RENGLON) - Number(b.N_RENGLON));
    const ordenados = [];
    renglones.filter(r => Number(r.RENGL_PADR) === 0).forEach(p => {
        ordenados.push(p);
        renglones.filter(c => Number(c.RENGL_PADR) === Number(p.N_RENGLON)).forEach(c => ordenados.push(c));
    });
    renglones.forEach(r => { if (ordenados.indexOf(r) === -1) { ordenados.push(r); } });
    return ordenados;
}

function renderVistaPreviaEdicion(data) {
    const resumen = data.resumen;
    $('#prev-eliminados').text(resumen.ELIMINADOS);
    $('#prev-modificados').text(resumen.MODIFICADOS);
    $('#prev-total-ant').text('$ ' + formatearImporte(resumen.TOTAL_PEDI_ANT));
    $('#prev-total-nuevo').text('$ ' + formatearImporte(resumen.TOTAL_PEDI_NUEVO));

    const tbodyRenglones = $('#prev-tbody-renglones').empty();
    ordenarConComponentes(data.renglones).forEach(r => {
        const esComponente = Number(r.RENGL_PADR) !== 0;
        const eliminado = r.ESTADO_RENGLON === 'ELIMINADO';
        const badge = eliminado
            ? '<span class="badge-modern badge-danger-modern">Eliminado</span>'
            : '<span class="badge-modern badge-warning-modern">Modificado</span>';
        tbodyRenglones.append(`<tr class="${esComponente ? 'row-componente' : ''}">
            <td class="col-articulo">${esComponente ? '' : '<strong>'}${escapeHtml(r.COD_ARTICU)}${esComponente ? '' : '</strong>'}</td>
            <td class="num">${formatearCantidad(r.CANT_PEDID_ANT)}</td>
            <td class="num">${formatearCantidad(r.CANT_NUEVA)}</td>
            <td>${badge}</td>
        </tr>`);
    });

    const tbodyStock = $('#prev-tbody-stock').empty();
    (data.stock || []).forEach(s => {
        const delta = Number(s.DELTA) || 0;
        tbodyStock.append(`<tr>
            <td><strong>${escapeHtml(s.COD_ARTICU)}</strong></td>
            <td>${escapeHtml(s.COD_DEPOSI)}</td>
            <td class="num">${formatearCantidad(s.COMP_ANTES)}</td>
            <td class="num">${delta > 0 ? '+' : ''}${formatearCantidad(delta)}</td>
            <td class="num">${formatearCantidad(s.COMP_DESPUES)}</td>
        </tr>`);
    });
    if (!data.stock || data.stock.length === 0) {
        tbodyStock.append('<tr><td colspan="5" class="text-muted text-center">Sin variaciones de stock</td></tr>');
    }
}

function hayCambiosEdicion() {
    return Object.keys(edicion.cambios).length > 0;
}

function confirmarDescarteEdicion() {
    if (!hayCambiosEdicion()) {
        return Promise.resolve(true);
    }
    return Swal.fire({
        title: 'Hay cambios sin confirmar. ¿Descartarlos?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#8c93a8',
        confirmButtonText: 'Descartar cambios',
        cancelButtonText: 'Volver'
    }).then(result => result.isConfirmed);
}

function buscarPedidoEdicion() {
    const talon = $('#edicion_talon').val().trim();
    const nroPedido = $('#edicion_nro_pedido').val().trim();

    if (!talon || !nroPedido) {
        toastr.warning('Complete el talón y el número de pedido.');
        return;
    }
    if (!/^\d+$/.test(talon)) {
        toastr.warning('El talón debe ser numérico.');
        return;
    }

    confirmarDescarteEdicion().then(function(continuar) {
        if (continuar) {
            consultarPedidoEdicion(talon, nroPedido);
        }
    });
}

function consultarPedidoEdicion(talon, nroPedido) {
    mostrarLoader('Consultando pedido...');
    $.ajax({
        url: 'Controller/EdicionPedidoController.php?action=consultar',
        type: 'POST',
        dataType: 'json',
        data: {
            pais: paisSeleccionado,
            talon_ped: talon,
            nro_pedido: nroPedido
        },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                const mismoPedido = edicion.pedido !== null
                    && Number(edicion.pedido.TALON_PED) === Number(response.pedido.TALON_PED)
                    && edicion.pedido.NRO_PEDIDO === response.pedido.NRO_PEDIDO;
                if (!mismoPedido) { $('#edicion-busqueda').val(''); }

                edicion.pedido = response.pedido;
                edicion.renglones = response.renglones || [];
                edicion.cambios = {};
                edicion.cambiosSimulados = null;
                edicion.expandidos = {};
                renderEncabezadoEdicion();
                renderGrillaEdicion();
            } else {
                limpiarEdicion();
                Swal.fire('No se pudo consultar el pedido', response.message, 'warning');
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al consultar el pedido.');
        }
    });
}

function limpiarEdicion() {
    edicion.pedido = null;
    edicion.renglones = [];
    edicion.cambios = {};
    edicion.cambiosSimulados = null;
    edicion.expandidos = {};
    $('#tbody-edicion').empty();
    $('#edicion-busqueda').val('');
    $('#edicion-encabezado, #edicion-grilla').addClass('d-none');
    $('#edicion-vacio').removeClass('d-none');
}

function pedidoEditable() {
    return edicion.pedido !== null && Number(edicion.pedido.EDITABLE) === 1;
}

function renglonEditable(r) {
    return pedidoEditable() && Number(r.EDITABLE) === 1;
}

function renglonesPrincipales() {
    return edicion.renglones.filter(r => Number(r.RENGL_PADR) === 0);
}

function componentesDe(nRenglon) {
    return edicion.renglones.filter(r => Number(r.RENGL_PADR) === Number(nRenglon));
}

function buscarRenglon(nRenglon) {
    return edicion.renglones.find(r => Number(r.N_RENGLON) === Number(nRenglon));
}

function renderEncabezadoEdicion() {
    const p = edicion.pedido;
    const estado = Number(p.ESTADO) === 2 ? '2 · Pendiente' : `Estado ${p.ESTADO}`;

    $('#ph-talon').text(p.TALON_PED);
    $('#ph-nro').text(p.NRO_PEDIDO);
    $('#ph-fecha').text(p.FECHA_PEDI || '---');
    $('#ph-cliente').text(p.COD_CLIENT || '---');
    $('#ph-estado').text(estado);
    $('#ph-total').text('$ ' + formatearImporte(p.TOTAL_PEDI));

    if (pedidoEditable()) {
        $('#edicion-no-editable').addClass('d-none');
    } else {
        $('#edicion-no-editable-motivo').text(p.MOTIVO_NO_EDITABLE || 'El pedido no se puede editar.');
        $('#edicion-no-editable').removeClass('d-none');
    }

    $('#edicion-vacio').addClass('d-none');
    $('#edicion-encabezado').removeClass('d-none');
}

function renderGrillaEdicion() {
    const tbody = $('#tbody-edicion');
    const editable = pedidoEditable();
    tbody.empty();

    $('#tabla-edicion .col-edicion').toggleClass('d-none', !editable);

    renglonesPrincipales().forEach(function(r) {
        tbody.append(filaRenglonEdicion(r, editable));

        if (Number(r.ES_KIT) === 1) {
            componentesDe(r.N_RENGLON).forEach(function(c) {
                tbody.append(filaComponenteEdicion(c, r.N_RENGLON, editable));
            });
        }
    });

    tbody.append('<tr class="row-sin-resultados d-none"><td colspan="9">No hay renglones que coincidan con la búsqueda.</td></tr>');

    $('#edicion-actions').toggleClass('d-none', !editable);
    actualizarBarraEdicion();
    aplicarBusquedaEdicion();
    $('#edicion-grilla').removeClass('d-none');
}

function normalizarBusqueda(texto) {
    return String(texto || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
}

function coincideBusqueda(renglon, texto) {
    return normalizarBusqueda(renglon.COD_ARTICU).indexOf(texto) !== -1
        || normalizarBusqueda(renglon.DESCRIPCIO).indexOf(texto) !== -1;
}

function aplicarBusquedaEdicion() {
    const texto = normalizarBusqueda($('#edicion-busqueda').val());
    let visibles = 0;

    renglonesPrincipales().forEach(function(r) {
        const n = r.N_RENGLON;
        const componentes = Number(r.ES_KIT) === 1 ? componentesDe(n) : [];
        const coincideComponente = texto !== '' && componentes.some(c => coincideBusqueda(c, texto));
        const visible = texto === '' || coincideBusqueda(r, texto) || coincideComponente;

        $(`#tbody-edicion tr.row-principal[data-renglon="${n}"]`).toggleClass('d-none', !visible);
        $(`#tbody-edicion tr.row-componente[data-padre="${n}"]`).each(function(i) {
            const mostrar = visible && (edicion.expandidos[n] || (texto !== '' && coincideBusqueda(componentes[i], texto)));
            $(this).toggleClass('d-none', !mostrar);
        });
        if (visible) { visibles++; }
    });

    $('#tbody-edicion tr.row-sin-resultados').toggleClass('d-none', visibles > 0 || renglonesPrincipales().length === 0);
}

function filaRenglonEdicion(r, editable) {
    const n = r.N_RENGLON;
    const esKit = Number(r.ES_KIT) === 1;
    const cantidad = Number(r.CANT_PEDID);
    const cambio = edicion.cambios[n];
    const quitado = cambio !== undefined && cambio.cantidad === 0;
    const modificado = cambio !== undefined && cambio.cantidad > 0;

    const expand = esKit
        ? `<button type="button" class="btn-expand ${edicion.expandidos[n] ? 'expanded' : ''}" data-renglon="${n}" title="Ver componentes"><i class="bi bi-chevron-right"></i></button>`
        : '';
    const badgeKit = esKit ? ' <span class="badge-modern badge-primary-modern ms-1">Kit</span>' : '';

    let estado = '';
    if (Number(r.TIENE_REMITO) === 1) {
        estado = `<span class="badge-modern badge-warning-modern" title="${escapeHtml(r.MOTIVO_NO_EDITABLE || '')}">Con remito</span>`;
    } else if (Number(r.EDITABLE) !== 1) {
        estado = `<span class="badge-modern badge-neutral-modern" title="${escapeHtml(r.MOTIVO_NO_EDITABLE || '')}">No editable</span>`;
    } else if (editable) {
        estado = '<span class="badge-modern">Editable</span>';
    }

    let colsEdicion = '';
    if (editable) {
        let input = '';
        let accion = '';
        if (renglonEditable(r)) {
            const deshabilitado = (cantidad <= 1 || quitado) ? 'disabled' : '';
            input = `<input type="number" class="form-control form-control-modern input-cantidad" data-renglon="${n}"
                        min="1" max="${cantidad - 1}" step="1" value="${modificado ? cambio.cantidad : ''}" ${deshabilitado}>`;
            accion = botonAccionEdicion(n, quitado);
        }
        colsEdicion = `<td class="col-edicion">${input}</td><td class="col-edicion">${accion}</td>`;
    }

    const clases = ['row-principal'];
    if (quitado) { clases.push('row-quitado'); }
    if (modificado) { clases.push('row-modificado'); }

    return `<tr class="${clases.join(' ')}" data-renglon="${n}">
        <td>${expand}</td>
        <td class="tachable"><strong>${escapeHtml(r.COD_ARTICU)}</strong>${badgeKit}</td>
        <td class="tachable">${escapeHtml(r.DESCRIPCIO || '---')}</td>
        <td class="num tachable">${formatearCantidad(r.CANT_PEDID)}</td>
        <td class="num tachable">${esKit ? '' : formatearImporte(r.PRECIO)}</td>
        <td class="num tachable">${formatearImporte(r.IMPORTE)}</td>
        <td>${estado}</td>
        ${colsEdicion}
    </tr>`;
}

function filaComponenteEdicion(c, nPadre, editable) {
    const oculto = edicion.expandidos[nPadre] ? '' : 'd-none';
    const colsEdicion = editable ? '<td class="col-edicion"></td><td class="col-edicion"></td>' : '';
    return `<tr class="row-componente ${oculto}" data-padre="${nPadre}">
        <td></td>
        <td class="col-articulo">${escapeHtml(c.COD_ARTICU)}</td>
        <td>${escapeHtml(c.DESCRIPCIO || '---')}</td>
        <td class="num">${formatearCantidad(c.CANT_PEDID)}</td>
        <td class="num">${formatearImporte(c.PRECIO)}</td>
        <td class="num">${formatearImporte(c.IMPORTE)}</td>
        <td></td>
        ${colsEdicion}
    </tr>`;
}

function botonAccionEdicion(nRenglon, quitado) {
    return quitado
        ? `<button type="button" class="btn btn-modern btn-modern-outline btn-modern-sm btn-quitar" data-renglon="${nRenglon}"><i class="bi bi-arrow-counterclockwise"></i> Deshacer</button>`
        : `<button type="button" class="btn btn-modern btn-modern-outline-danger btn-modern-sm btn-quitar" data-renglon="${nRenglon}"><i class="bi bi-trash"></i> Quitar</button>`;
}

function formatearImporte(valor) {
    return (Number(valor) || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatearCantidad(valor) {
    return (Number(valor) || 0).toLocaleString('es-AR', { maximumFractionDigits: 2 });
}

function escapeHtml(texto) {
    return String(texto === null || texto === undefined ? '' : texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}


// ==========================================
// ===== EDICIÓN MASIVA POR EXCEL =====
// ==========================================
function procesarExcelEdicionMasiva(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    if (extension !== 'xlsx' && extension !== 'xls') {
        toastr.error('El formato del archivo debe ser .xlsx o .xls.');
        $('#edicion_fileupload').val('');
        return;
    }

    mostrarLoader('Leyendo archivo Excel de edición...');

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = e.target.result;
            const workbook = XLSX.read(data, { type: 'binary' });
            const firstSheet = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheet];
            const jsonData = XLSX.utils.sheet_to_json(worksheet);

            if (jsonData.length > 0) {
                const firstRow = jsonData[0];
                const colsReq = ['TALON_PED', 'NRO_PEDIDO', 'COD_ARTICU', 'NUEVA_CANTIDAD'];
                const faltantes = colsReq.filter(c => !firstRow.hasOwnProperty(c));

                if (faltantes.length > 0) {
                    ocultarLoader();
                    Swal.fire('Estructura Incorrecta', `El archivo Excel debe contener las columnas: ${colsReq.join(', ')}. Faltan: ${faltantes.join(', ')}.`, 'error');
                    $('#edicion_fileupload').val('');
                    return;
                }
                validarLoteEdicionMasiva(jsonData);
            } else {
                ocultarLoader();
                toastr.warning('El archivo Excel está vacío.');
                $('#edicion_fileupload').val('');
            }
        } catch (err) {
            ocultarLoader();
            toastr.error('Error al leer el archivo Excel.');
        }
    };
    reader.readAsBinaryString(file);
}

function validarLoteEdicionMasiva(filas) {
    mostrarLoader('Validando cambios masivos en pedidos...');
    $.ajax({
        url: 'Controller/EdicionPedidoController.php?action=validar_lote',
        type: 'POST',
        dataType: 'json',
        data: {
            filas: JSON.stringify(filas),
            pais: paisSeleccionado
        },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                edicionMasivaCargada.pedidosValidos = response.pedidos_validos || [];
                edicionMasivaCargada.filasData = response.filas_data || [];
                mostrarPreviewTableEdicionMasiva(response);
            } else {
                Swal.fire('Error al validar lote', response.message, 'error');
                $('#edicion_fileupload').val('');
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al validar la edición masiva.');
            $('#edicion_fileupload').val('');
        }
    });
}

function mostrarPreviewTableEdicionMasiva(data) {
    $('#edicion_modal_pedidos_validos').text(data.cant_pedidos_validos);
    $('#edicion_modal_pedidos_invalidos').text(data.cant_pedidos_invalidos);
    $('#edicion_modal_filas_validas').text(data.cant_filas_validas);
    $('#edicion_modal_filas_invalidas').text(data.cant_filas_invalidas);
    $('#edicion_modal_btn_validos').text(data.cant_pedidos_validos);

    const tbody = $('#modal_tbody_edicion_filas').empty();

    data.filas_data.forEach(function(f) {
        const badgeClass = f.valido ? 'badge-success-modern' : 'badge-danger-modern';
        let badgeAccion = '';
        if (f.accion === 'ELIMINAR') {
            badgeAccion = '<span class="badge-modern badge-danger-modern">Quitar</span>';
        } else if (f.accion === 'MODIFICAR') {
            badgeAccion = '<span class="badge-modern badge-warning-modern">Modificar</span>';
        } else {
            badgeAccion = '<span class="badge-modern badge-neutral-modern">Error</span>';
        }

        const row = `<tr>
            <td>${escapeHtml(f.talon_ped)}</td>
            <td><strong>${escapeHtml(f.nro_pedido)}</strong></td>
            <td><strong>${escapeHtml(f.cod_articu)}</strong></td>
            <td>${escapeHtml(f.descripcio || '---')}</td>
            <td class="num">${formatearCantidad(f.cant_actual)}</td>
            <td class="num"><strong>${formatearCantidad(f.cant_nueva)}</strong></td>
            <td>${badgeAccion}</td>
            <td><span class="badge-modern ${badgeClass}">${escapeHtml(f.mensaje)}</span></td>
        </tr>`;
        tbody.append(row);
    });

    $('#btnAplicarEdicionMasiva').prop('disabled', data.cant_pedidos_validos === 0);

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPreviousEdicion'));
    modal.show();
}

function aplicarEdicionMasivaConfirmada() {
    if (!edicionMasivaCargada.pedidosValidos || edicionMasivaCargada.pedidosValidos.length === 0) {
        toastr.warning('No hay pedidos válidos para procesar.');
        return;
    }

    Swal.fire({
        title: `¿Aplicar edición a ${edicionMasivaCargada.pedidosValidos.length} pedido(s)?`,
        text: 'Se modificarán las cantidades y liberará el stock comprometido para los pedidos válidos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3366ff',
        cancelButtonColor: '#8c93a8',
        confirmButtonText: 'Sí, aplicar cambios',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            ejecutarEdicionMasivaLote();
        }
    });
}

function ejecutarEdicionMasivaLote() {
    mostrarLoader('Aplicando edición masiva en base de datos...');
    $.ajax({
        url: 'Controller/EdicionPedidoController.php?action=aplicar_lote',
        type: 'POST',
        dataType: 'json',
        data: {
            pais: paisSeleccionado,
            pedidos: JSON.stringify(edicionMasivaCargada.pedidosValidos),
            usuario: $('body').attr('data-username') || ''
        },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                const modalEl = document.getElementById('modalPreviousEdicion');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) { modalInstance.hide(); }

                Swal.fire({
                    title: 'Edición Masiva Procesada',
                    html: `
                        <div class="text-start">
                            <p><strong>Resumen de ejecución:</strong></p>
                            <ul>
                                <li>Total pedidos procesados: <span class="fw-bold">${response.total}</span></li>
                                <li>Pedidos editados con éxito: <span class="fw-bold text-success">${response.exitosos}</span></li>
                                <li>Pedidos fallidos: <span class="fw-bold text-danger">${response.fallidos}</span></li>
                            </ul>
                        </div>
                    `,
                    icon: response.fallidos === 0 ? 'success' : 'warning',
                    confirmButtonColor: '#10b981'
                }).then(() => {
                    $('#edicion_fileupload').val('');
                    edicionMasivaCargada = { pedidosValidos: [], filasData: [] };
                    refrescarHistorialSiVisible();
                });
            } else {
                Swal.fire('Error al procesar lote', response.message, 'error');
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al aplicar la edición masiva.');
        }
    });
}


// ==========================================
// ===== HISTORIAL DE EDICIONES =====
// ==========================================
const historial = {
    operaciones: [],
    expandidas: {}
};

function initHistorialEdiciones() {
    $('#btn-toggle-historial').on('click', function() {
        if ($('#edicion-historial').hasClass('d-none')) {
            abrirHistorial({});
        } else {
            $('#edicion-historial').addClass('d-none');
        }
    });

    $('#btn-cerrar-historial').on('click', function() {
        $('#edicion-historial').addClass('d-none');
    });

    $('#btn-historial-pedido').on('click', function() {
        if (!edicion.pedido) { return; }
        abrirHistorial({
            desde: fechaISO(sumarDias(new Date(), -365)),
            talon: edicion.pedido.TALON_PED,
            nro: edicion.pedido.NRO_PEDIDO
        });
    });

    $('#formHistorial').on('submit', function(e) {
        e.preventDefault();
        cargarHistorial();
    });

    $('#btn-limpiar-historial').on('click', function() {
        setFiltrosHistorial({});
        cargarHistorial();
    });

    $(document).on('click', '#tbody-historial tr.row-historial', function() {
        const i = $(this).data('op');
        historial.expandidas[i] = !$(this).hasClass('expanded');
        aplicarBusquedaHistorial();
    });

    $('#historial-busqueda').on('input', function() {
        historial.expandidas = {};
        aplicarBusquedaHistorial();
    });

    $('#btn-descargar-historial-edicion').on('click', descargarExcelHistorialEdiciones);
}

function sumarDias(fecha, dias) {
    const d = new Date(fecha);
    d.setDate(d.getDate() + dias);
    return d;
}

function fechaISO(fecha) {
    const mm = String(fecha.getMonth() + 1).padStart(2, '0');
    const dd = String(fecha.getDate()).padStart(2, '0');
    return `${fecha.getFullYear()}-${mm}-${dd}`;
}

function setFiltrosHistorial(filtros) {
    $('#historial_desde').val(filtros.desde || fechaISO(sumarDias(new Date(), -30)));
    $('#historial_hasta').val(filtros.hasta || fechaISO(new Date()));
    $('#historial_usuario').val(filtros.usuario || '');
    $('#historial_talon').val(filtros.talon || '');
    $('#historial_nro').val(filtros.nro || '');
    $('#historial_cliente').val(filtros.cliente || '');
}

function abrirHistorial(filtros) {
    setFiltrosHistorial(filtros);
    $('#historial-busqueda').val('');
    $('#edicion-historial').removeClass('d-none');
    cargarHistorial(function() {
        document.getElementById('edicion-historial').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function refrescarHistorialSiVisible() {
    if (!$('#edicion-historial').hasClass('d-none')) {
        cargarHistorial();
    }
}

function cargarHistorial(onLoad) {
    const desde = $('#historial_desde').val();
    const hasta = $('#historial_hasta').val();
    const talon = $('#historial_talon').val().trim();

    if (!desde || !hasta) {
        toastr.warning('Indique el rango de fechas.');
        return;
    }
    if (talon !== '' && !/^\d+$/.test(talon)) {
        toastr.warning('El talón debe ser numérico.');
        return;
    }

    mostrarLoader('Consultando historial de ediciones...');
    $.ajax({
        url: 'Controller/EdicionPedidoController.php?action=historial',
        type: 'POST',
        dataType: 'json',
        data: {
            pais: paisSeleccionado,
            desde: desde,
            hasta: hasta,
            usuario_filtro: $('#historial_usuario').val().trim(),
            talon_ped: talon,
            nro_pedido: $('#historial_nro').val().trim(),
            cliente: $('#historial_cliente').val().trim()
        },
        success: function(response) {
            ocultarLoader();
            if (response.success) {
                historial.operaciones = response.operaciones || [];
                historial.expandidas = {};
                renderHistorial(response.truncado);
                if (onLoad) { onLoad(); }
            } else {
                Swal.fire('No se pudo consultar el historial', response.message, 'warning');
            }
        },
        error: function() {
            ocultarLoader();
            toastr.error('Error de red al consultar el historial.');
        }
    });
}

function renderHistorial(truncado) {
    const tbody = $('#tbody-historial').empty();

    if (historial.operaciones.length === 0) {
        tbody.append('<tr class="row-sin-resultados"><td colspan="9" class="text-center text-muted py-3">No hay ediciones registradas para los filtros indicados.</td></tr>');
        $('#historial-info').addClass('d-none');
        return;
    }

    historial.operaciones.forEach(function(op, i) {
        const usuario = op.usuario
            ? escapeHtml(op.usuario)
            : '<span class="usuario-vacio">Sin usuario</span>';
        const cliente = op.cod_client
            ? `${escapeHtml(op.cod_client)} ${op.razon_soci ? '- ' + escapeHtml(op.razon_soci) : ''}`
            : '---';

        tbody.append(`<tr class="row-historial" data-op="${i}">
            <td><span class="btn-expand"><i class="bi bi-chevron-right"></i></span></td>
            <td class="num text-start">${escapeHtml(op.fecha)}</td>
            <td>${usuario}</td>
            <td>${escapeHtml(op.talon_ped)}</td>
            <td><strong>${escapeHtml(op.nro_pedido)}</strong></td>
            <td>${cliente}</td>
            <td class="num">${op.eliminados}</td>
            <td class="num">${op.modificados}</td>
            <td class="num total-cambio">$ ${formatearImporte(op.total_ant)}<span class="arrow">→</span>$ ${formatearImporte(op.total_nuevo)}</td>
        </tr>`);
        tbody.append(`<tr class="row-historial-detalle d-none" data-op="${i}">
            <td colspan="9">${detalleHistorialHtml(op)}</td>
        </tr>`);
    });

    tbody.append('<tr class="row-sin-resultados d-none"><td colspan="9" class="text-center text-muted py-3">No hay ediciones que coincidan con la búsqueda.</td></tr>');

    const info = $('#historial-info');
    if (truncado) {
        info.text(`Se muestran las ${historial.operaciones.length} ediciones más recientes. Acote los filtros para ver otras.`).removeClass('d-none');
    } else {
        info.addClass('d-none');
    }

    aplicarBusquedaHistorial();
}

function detalleHistorialHtml(op) {
    const filas = ordenarConComponentes(op.detalle).map(function(d) {
        const esComponente = Number(d.RENGL_PADR) !== 0;
        const eliminado = Number(d.CANT_NUEVA) === 0;
        const badge = eliminado
            ? '<span class="badge-modern badge-danger-modern">Eliminado</span>'
            : '<span class="badge-modern badge-warning-modern">Modificado</span>';
        const delta = Number(d.DELTA_COMP) || 0;
        const tieneStock = d.DELTA_COMP !== null && d.DELTA_COMP !== undefined;

        return `<tr class="${esComponente ? 'row-componente' : ''}">
            <td class="col-articulo">${esComponente ? '' : '<strong>'}${escapeHtml(d.COD_ARTICU)}${esComponente ? '' : '</strong>'}</td>
            <td>${escapeHtml(d.DESCRIPCIO || '---')}</td>
            <td>${escapeHtml(d.COD_DEPOSI || '')}</td>
            <td class="num">${formatearCantidad(d.CANT_PEDID_ANT)}</td>
            <td class="num">${formatearCantidad(d.CANT_NUEVA)}</td>
            <td>${badge}</td>
            <td class="num">${tieneStock ? formatearCantidad(d.COMP_ANTES) : ''}</td>
            <td class="num">${tieneStock ? (delta > 0 ? '+' : '') + formatearCantidad(delta) : ''}</td>
            <td class="num">${tieneStock ? formatearCantidad(d.COMP_DESPUES) : ''}</td>
        </tr>`;
    }).join('');

    return `<div class="historial-detalle">
        <div class="table-container-modern">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Artículo</th>
                        <th>Descripción</th>
                        <th>Depósito</th>
                        <th class="num">Cant. anterior</th>
                        <th class="num">Cant. nueva</th>
                        <th>Acción</th>
                        <th class="num">Comprometido antes</th>
                        <th class="num">Variación</th>
                        <th class="num">Comprometido después</th>
                    </tr>
                </thead>
                <tbody>${filas}</tbody>
            </table>
        </div>
    </div>`;
}

function aplicarBusquedaHistorial() {
    const texto = normalizarBusqueda($('#historial-busqueda').val());
    let visibles = 0;

    historial.operaciones.forEach(function(op, i) {
        const coincideArticulo = op.detalle.some(d => coincideBusqueda(d, texto));
        const coincideHeader = normalizarBusqueda(op.nro_pedido).indexOf(texto) !== -1
            || normalizarBusqueda(op.talon_ped).indexOf(texto) !== -1
            || normalizarBusqueda(op.usuario).indexOf(texto) !== -1
            || normalizarBusqueda(op.cod_client).indexOf(texto) !== -1
            || normalizarBusqueda(op.razon_soci).indexOf(texto) !== -1;

        const visible = texto === '' || coincideHeader || coincideArticulo;
        const manual = historial.expandidas[i];
        const expandida = visible && (manual !== undefined ? manual : (texto !== '' && coincideArticulo));

        $(`#tbody-historial tr.row-historial[data-op="${i}"]`)
            .toggleClass('d-none', !visible)
            .toggleClass('expanded', expandida)
            .find('.btn-expand').toggleClass('expanded', expandida);
        $(`#tbody-historial tr.row-historial-detalle[data-op="${i}"]`).toggleClass('d-none', !expandida);
        if (visible) { visibles++; }
    });

    $('#tbody-historial tr.row-sin-resultados').toggleClass('d-none', visibles > 0 || historial.operaciones.length === 0);
}

function descargarExcelHistorialEdiciones() {
    if (!historial.operaciones || historial.operaciones.length === 0) {
        toastr.warning('No hay datos en el historial para descargar.');
        return;
    }

    try {
        const resumenRows = historial.operaciones.map(op => ({
            'OPERACION': op.operacion,
            'FECHA': op.fecha,
            'USUARIO': op.usuario || 'Sin usuario',
            'TALON': op.talon_ped,
            'NRO_PEDIDO': op.nro_pedido,
            'COD_CLIENT': op.cod_client || '',
            'RAZON_SOCIAL': op.razon_soci || '',
            'RENGLONES_QUITADOS': op.eliminados,
            'RENGLONES_MODIFICADOS': op.modificados,
            'TOTAL_ANTERIOR': Number(op.total_ant) || 0,
            'TOTAL_NUEVO': Number(op.total_nuevo) || 0
        }));

        const detalleRows = [];
        historial.operaciones.forEach(op => {
            (op.detalle || []).forEach(d => {
                const eliminado = Number(d.CANT_NUEVA) === 0;
                detalleRows.push({
                    'OPERACION': op.operacion,
                    'FECHA': op.fecha,
                    'USUARIO': op.usuario || 'Sin usuario',
                    'TALON': op.talon_ped,
                    'NRO_PEDIDO': op.nro_pedido,
                    'COD_CLIENT': op.cod_client || '',
                    'RAZON_SOCIAL': op.razon_soci || '',
                    'N_RENGLON': d.N_RENGLON,
                    'ES_COMPONENTE_KIT': Number(d.RENGL_PADR) !== 0 ? 'SI' : 'NO',
                    'COD_ARTICU': d.COD_ARTICU,
                    'DESCRIPCIO': d.DESCRIPCIO || '',
                    'COD_DEPOSI': d.COD_DEPOSI || '',
                    'CANT_ANTERIOR': Number(d.CANT_PEDID_ANT) || 0,
                    'CANT_NUEVA': Number(d.CANT_NUEVA) || 0,
                    'ACCION': eliminado ? 'ELIMINADO' : 'MODIFICADO',
                    'STOCK_COMP_ANTES': d.COMP_ANTES !== null && d.COMP_ANTES !== undefined ? Number(d.COMP_ANTES) : '',
                    'DELTA_STOCK': d.DELTA_COMP !== null && d.DELTA_COMP !== undefined ? Number(d.DELTA_COMP) : '',
                    'STOCK_COMP_DESPUES': d.COMP_DESPUES !== null && d.COMP_DESPUES !== undefined ? Number(d.COMP_DESPUES) : ''
                });
            });
        });

        const wb = XLSX.utils.book_new();
        const wsResumen = XLSX.utils.json_to_sheet(resumenRows);
        const wsDetalle = XLSX.utils.json_to_sheet(detalleRows);

        XLSX.utils.book_append_sheet(wb, wsResumen, 'Ediciones_Resumen');
        XLSX.utils.book_append_sheet(wb, wsDetalle, 'Ediciones_Detalle');

        const fechaStr = fechaISO(new Date());
        XLSX.writeFile(wb, `Historial_Ediciones_${paisSeleccionado}_${fechaStr}.xlsx`);
        toastr.success('Historial de ediciones exportado a Excel exitosamente.');
    } catch (e) {
        console.error(e);
        toastr.error('Error al generar el archivo Excel.');
    }
}
