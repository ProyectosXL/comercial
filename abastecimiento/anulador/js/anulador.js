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
            // Preparar datos para el Excel
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
            ? `<input type="checkbox" class="form-check-input check-pedido-valido" data-nro="${p.nro_pedido}" data-talon="${p.talon_ped}">`
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


// ===== EDITAR PEDIDO =====
// Estado de la pestaña: pedido cargado, todos sus renglones y cambios pendientes (n_renglon -> cambio)
const edicion = {
    pedido: null,
    renglones: [],
    cambios: {},
    cambiosSimulados: null, // lote exacto enviado a simular, se reutiliza al aplicar
    expandidos: {}
};

function initEdicionPedido() {
    // Buscar (Enter en Talón o Nro. Pedido dispara el submit)
    $('#formBuscarEdicion').on('submit', function(e) {
        e.preventDefault();
        buscarPedidoEdicion();
    });

    // Expandir / colapsar componentes de un kit
    $(document).on('click', '#tbody-edicion .btn-expand', function() {
        const nRenglon = $(this).data('renglon');
        edicion.expandidos[nRenglon] = !edicion.expandidos[nRenglon];
        $(this).toggleClass('expanded', edicion.expandidos[nRenglon]);
        aplicarBusquedaEdicion();
    });

    // Nueva cantidad: solo se registra si es un entero entre 1 y CANT_PEDID - 1
    $(document).on('input change', '#tbody-edicion .input-cantidad', function() {
        registrarCantidadEdicion($(this));
    });

    // Quitar / Deshacer
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

    // Búsqueda rápida por artículo o descripción (solo filtra la vista, no toca los cambios)
    $('#edicion-busqueda').on('input', aplicarBusquedaEdicion);

    $('#btn-revisar-edicion').on('click', simularEdicion);
    $('#btn-confirmar-edicion').on('click', aplicarEdicion);

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
        // Vacío: sin cambio de cantidad
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

// Refresca marcas, input y botón de una fila sin re-renderizar la grilla (no pierde el foco ni lo tipeado)
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

    // No se pueden quitar todos los renglones principales
    const principales = renglonesPrincipales();
    const quitados = principales.filter(r => {
        const c = edicion.cambios[r.N_RENGLON];
        return c !== undefined && c.cantidad === 0;
    });
    if (principales.length > 0 && quitados.length === principales.length) {
        Swal.fire('Atención', 'No se pueden quitar todos los renglones. Para eso usá Anular Pedidos.', 'warning');
        return;
    }

    // Se guarda el lote exacto que se simula para aplicar los mismos cambios
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

// Los cambios pendientes se conservan; si el pedido cambió (51004) se ofrece recargarlo
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

// Ordena renglones afectados: cada kit seguido de sus componentes (RENGL_PADR = renglón del kit)
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

// Resuelve true si se puede continuar (no hay cambios o el usuario acepta descartarlos)
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
                // Al recargar el mismo pedido (después de aplicar) se conserva la búsqueda
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

    // En solo lectura no se muestran las columnas de edición
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

// Normaliza para comparar sin mayúsculas ni acentos
function normalizarBusqueda(texto) {
    return String(texto || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
}

function coincideBusqueda(renglon, texto) {
    return normalizarBusqueda(renglon.COD_ARTICU).indexOf(texto) !== -1
        || normalizarBusqueda(renglon.DESCRIPCIO).indexOf(texto) !== -1;
}

// Un renglón principal se ve si coincide él o alguno de sus componentes.
// Los componentes se ven si el kit está expandido o si ellos mismos coinciden con la búsqueda.
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

// Componente de kit: solo informativo, sin inputs ni acciones
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


// ----- Historial de ediciones (dentro de Editar Pedido) -----
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

    // Historial del pedido cargado (último año)
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

    // Expandir / colapsar el detalle de una edición
    $(document).on('click', '#tbody-historial tr.row-historial', function() {
        const i = $(this).data('op');
        historial.expandidas[i] = !$(this).hasClass('expanded');
        aplicarBusquedaHistorial();
    });

    // Al cambiar la búsqueda, las coincidencias vuelven a mostrarse expandidas
    $('#historial-busqueda').on('input', function() {
        historial.expandidas = {};
        aplicarBusquedaHistorial();
    });
}

function sumarDias(fecha, dias) {
    const d = new Date(fecha);
    d.setDate(d.getDate() + dias);
    return d;
}

// Fecha local en formato yyyy-mm-dd (el que usan los input date)
function fechaISO(fecha) {
    const mm = String(fecha.getMonth() + 1).padStart(2, '0');
    const dd = String(fecha.getDate()).padStart(2, '0');
    return `${fecha.getFullYear()}-${mm}-${dd}`;
}

// Filtros por defecto: últimos 30 días, sin usuario ni pedido
function setFiltrosHistorial(filtros) {
    $('#historial_desde').val(filtros.desde || fechaISO(sumarDias(new Date(), -30)));
    $('#historial_hasta').val(filtros.hasta || fechaISO(new Date()));
    $('#historial_usuario').val(filtros.usuario || '');
    $('#historial_talon').val(filtros.talon || '');
    $('#historial_nro').val(filtros.nro || '');
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
            nro_pedido: $('#historial_nro').val().trim()
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

    historial.operaciones.forEach(function(op, i) {
        const usuario = op.usuario
            ? escapeHtml(op.usuario)
            : '<span class="usuario-vacio">Sin usuario</span>';

        tbody.append(`<tr class="row-historial" data-op="${i}">
            <td><span class="btn-expand"><i class="bi bi-chevron-right"></i></span></td>
            <td class="num text-start">${escapeHtml(op.fecha)}</td>
            <td>${usuario}</td>
            <td>${escapeHtml(op.talon_ped)}</td>
            <td><strong>${escapeHtml(op.nro_pedido)}</strong></td>
            <td class="num">${op.eliminados}</td>
            <td class="num">${op.modificados}</td>
            <td class="num total-cambio">$ ${formatearImporte(op.total_ant)}<span class="arrow">→</span>$ ${formatearImporte(op.total_nuevo)}</td>
        </tr>`);
        tbody.append(`<tr class="row-historial-detalle d-none" data-op="${i}">
            <td colspan="8">${detalleHistorialHtml(op)}</td>
        </tr>`);
    });

    const vacio = historial.operaciones.length === 0
        ? 'No hay ediciones registradas para los filtros indicados.'
        : 'No hay ediciones que coincidan con la búsqueda.';
    tbody.append(`<tr class="row-sin-resultados d-none"><td colspan="8">${vacio}</td></tr>`);

    const info = $('#historial-info');
    if (truncado) {
        info.text(`Se muestran las ${historial.operaciones.length} ediciones más recientes. Acote los filtros para ver otras.`).removeClass('d-none');
    } else {
        info.addClass('d-none');
    }

    aplicarBusquedaHistorial();
}

// Detalle de una edición: un renglón por artículo afectado, con los componentes de kit indentados
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

// Filtra las ediciones que afectaron algún artículo que coincide; esas se muestran expandidas
function aplicarBusquedaHistorial() {
    const texto = normalizarBusqueda($('#historial-busqueda').val());
    let visibles = 0;

    historial.operaciones.forEach(function(op, i) {
        const coincide = texto !== '' && op.detalle.some(d => coincideBusqueda(d, texto));
        const visible = texto === '' || coincide;
        // Si el usuario no la abrió/cerró a mano, se expande cuando coincide con la búsqueda
        const manual = historial.expandidas[i];
        const expandida = visible && (manual !== undefined ? manual : coincide);

        $(`#tbody-historial tr.row-historial[data-op="${i}"]`)
            .toggleClass('d-none', !visible)
            .toggleClass('expanded', expandida)
            .find('.btn-expand').toggleClass('expanded', expandida);
        $(`#tbody-historial tr.row-historial-detalle[data-op="${i}"]`).toggleClass('d-none', !expandida);
        if (visible) { visibles++; }
    });

    $('#tbody-historial tr.row-sin-resultados').toggleClass('d-none', visibles > 0);
}
