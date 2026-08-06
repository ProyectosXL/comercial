// Lógica cliente del panel Matriz para Reposición UY.
// Una sola carga AJAX trae todo el dataset; filtros y solapas trabajan 100% client-side.

const REPO_BLANCO = '(En blanco)';

let dtPanel = null;
let dtPackaging = null;
let packagingInicializado = false;
let datosActuales = { data_panel: [], data_packaging: [] };

// ─── Helpers de formato ───────────────────────────────────────────────────────

function formatearNumero(valor) {
    const n = (valor === null || valor === undefined || valor === '') ? 0 : Number(valor);
    return n.toLocaleString('es-UY', { maximumFractionDigits: 0 });
}

function renderNumero(data, type) {
    if (type !== 'display') {
        return (data === null || data === undefined || data === '') ? 0 : Number(data);
    }
    return formatearNumero(data);
}

function renderRepoVacio() {
    return '';
}

// Calcula el alto del cuerpo scrolleable de una tabla contra el viewport real
// (en vez de depender de que toda la cadena de contenedores flex se estire
// perfecto), así siempre llega hasta el borde inferior de la pantalla.
function ajustarAltoScroll(dt) {
    if (!dt) {
        return;
    }

    const $wrapper = $(dt.table().container());
    const $scrollBody = $wrapper.find('.dataTables_scrollBody');

    if (!$scrollBody.length) {
        return;
    }

    const offsetTop = $scrollBody.offset().top;

    // Se busca puntualmente la fila de paginación (en vez de "la última .row" a
    // ciegas) para no correr el riesgo de reservarle 0px si la estructura no
    // coincide con lo esperado y que termine empujada fuera de la pantalla.
    const $filaInferior = $wrapper.find('.dataTables_paginate').closest('.row');
    const altoFilaInferior = $filaInferior.length ? $filaInferior.outerHeight(true) : 40;
    const margenInferior = 40; // debe acompañar el padding inferior de .app-shell + margen de seguridad

    const nuevaAltura = Math.max(120, window.innerHeight - offsetTop - altoFilaInferior - margenInferior);

    $scrollBody.css({ height: nuevaAltura + 'px', maxHeight: nuevaAltura + 'px' });
}

function timestampArchivo() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return d.getFullYear() + pad(d.getMonth() + 1) + pad(d.getDate()) + '_' + pad(d.getHours()) + pad(d.getMinutes()) + pad(d.getSeconds());
}

function botonExcel(tipo) {
    const esPanel = tipo === 'panel';
    return {
        extend: 'excelHtml5',
        title: esPanel ? 'MATRIZ PARA REPOSICION UY - SIN PACKAGING' : 'MATRIZ PARA REPOSICION UY - SOLO PACKAGING',
        filename: function () {
            return (esPanel ? 'MATRIZ_REPOSICION_UY_' : 'MATRIZ_REPOSICION_UY_PACKAGING_') + timestampArchivo();
        },
        exportOptions: {
            columns: ':visible',
            modifier: { search: 'applied', order: 'applied', page: 'all' },
            orthogonal: 'export'
        }
    };
}

// ─── Definición de columnas ───────────────────────────────────────────────────

const panelColumns = [
    { data: 'COD_ARTICU', defaultContent: '' },
    { data: 'CATEGORIA', defaultContent: '' },
    { data: 'DESCRIPCIO', defaultContent: '' },
    { data: 'RUBRO', defaultContent: '' },
    { data: 'DESTINO', defaultContent: '' },
    { data: 'TEMPORADA', defaultContent: '' },
    { data: 'PRECIO', className: 'text-end', render: renderNumero },
    { data: 'STOCK_CENTRAL', className: 'text-end', render: renderNumero },
    { data: 'STOCK_NC', className: 'text-end', render: renderNumero },
    { data: 'VENTA_NC', className: 'text-end', render: renderNumero },
    { data: 'REPO_NC', className: 'text-end col-repo', render: renderRepoVacio },
    { data: 'STOCK_3C', className: 'text-end', render: renderNumero },
    { data: 'VENTA_3C', className: 'text-end', render: renderNumero },
    { data: 'REPO_3C', className: 'text-end col-repo', render: renderRepoVacio },
    { data: 'STOCK_MV', className: 'text-end', render: renderNumero },
    { data: 'VENTA_MV', className: 'text-end', render: renderNumero },
    { data: 'REPO_MV', className: 'text-end col-repo', render: renderRepoVacio },
    { data: 'VTA_7D_NC', className: 'text-end', render: renderNumero },
    { data: 'VTA_7D_MV', className: 'text-end', render: renderNumero },
    { data: 'VTA_7D_3C', className: 'text-end', render: renderNumero }
];

const packagingColumns = [
    { data: 'COD_ARTICU', defaultContent: '' },
    { data: 'DESCRIPCIO', defaultContent: '' },
    { data: 'STOCK_CENTRAL', className: 'text-end', render: renderNumero },
    { data: 'REPO_NC', className: 'text-end col-repo', render: renderRepoVacio },
    { data: 'REPO_3C', className: 'text-end col-repo', render: renderRepoVacio },
    { data: 'REPO_MV', className: 'text-end col-repo', render: renderRepoVacio }
];

// ─── Filtro custom de DataTables (sólo sobre la tabla Panel) ─────────────────

function coincideFiltro(valorCelda, seleccionados) {
    if (!seleccionados || seleccionados.length === 0) {
        return true;
    }
    const valor = (valorCelda === null || valorCelda === undefined) ? '' : String(valorCelda).trim();
    if (valor === '') {
        return seleccionados.includes(REPO_BLANCO);
    }
    return seleccionados.includes(valor);
}

$.fn.dataTable.ext.search.push(function (settings, searchData, index, rowData) {
    if (!rowData || !settings.nTable || settings.nTable.id !== 'tablaPanel') {
        return true;
    }

    const rubros = $('#filtroRubro').val() || [];
    const categorias = $('#filtroCategoria').val() || [];
    const destinos = $('#filtroDestino').val() || [];
    const temporadas = $('#filtroTemporada').val() || [];

    return coincideFiltro(rowData.RUBRO, rubros)
        && coincideFiltro(rowData.CATEGORIA, categorias)
        && coincideFiltro(rowData.DESTINO, destinos)
        && coincideFiltro(rowData.TEMPORADA, temporadas);
});

// ─── Spinner y errores ────────────────────────────────────────────────────────

function mostrarSpinner(mensaje) {
    $('#spinner-msg').text(mensaje || 'Cargando...');
    $('#spinner').css('display', 'flex');
}

function ocultarSpinner() {
    $('#spinner').hide();
}

function mostrarError(mensaje) {
    if (dtPanel) {
        dtPanel.destroy();
        $('#tablaPanel tbody').empty();
        dtPanel = null;
    }
    if (dtPackaging) {
        dtPackaging.destroy();
        $('#tablaPackaging tbody').empty();
        dtPackaging = null;
    }
    packagingInicializado = false;
    $('#contadorFilas').text('-');

    Swal.fire({
        icon: 'error',
        title: 'Error al cargar la matriz',
        text: mensaje,
        confirmButtonText: 'Aceptar'
    });
}

// ─── Filtros (Select2) ────────────────────────────────────────────────────────

function poblarSelect(selector, valores) {
    const $sel = $(selector);
    $sel.empty();
    (valores || []).forEach(function (v) {
        $sel.append(new Option(v, v, false, false));
    });
    $sel.trigger('change');
}

function poblarFiltros(filtros) {
    poblarSelect('#filtroRubro', filtros.rubros);
    poblarSelect('#filtroCategoria', filtros.categorias);
    poblarSelect('#filtroDestino', filtros.destinos);
    poblarSelect('#filtroTemporada', filtros.temporadas);
}

function actualizarContador() {
    if (!dtPanel) {
        $('#contadorFilas').text('-');
        return;
    }
    const visibles = dtPanel.rows({ search: 'applied' }).count();
    const total = dtPanel.rows().count();
    $('#contadorFilas').text(
        visibles.toLocaleString('es-UY') + ' de ' + total.toLocaleString('es-UY') + ' artículos'
    );
}

// ─── Inicialización de tablas ─────────────────────────────────────────────────

function inicializarTablaPanel(data) {
    if (dtPanel) {
        dtPanel.destroy();
        $('#tablaPanel tbody').empty();
    }

    dtPanel = $('#tablaPanel').DataTable({
        data: data,
        columns: panelColumns,
        order: [[0, 'asc']],
        pageLength: 250,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'Todos']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        dom: '<"d-none"B>lfrtip',
        scrollX: true,
        scrollY: '300px',
        scrollCollapse: true,
        fixedColumns: { leftColumns: 3 },
        buttons: [botonExcel('panel')],
        initComplete: function () {
            ajustarAltoScroll(this.api());
        }
    });

    // DataTables recalcula el alto del scroll en cada draw() (filtro, búsqueda,
    // cambio de página) y pisa el que le fijamos a mano: hay que reaplicarlo.
    dtPanel.on('draw', function () {
        actualizarContador();
        ajustarAltoScroll(dtPanel);
    });
}

function inicializarTablaPackaging(data) {
    if (dtPackaging) {
        dtPackaging.destroy();
        $('#tablaPackaging tbody').empty();
    }

    dtPackaging = $('#tablaPackaging').DataTable({
        data: data,
        columns: packagingColumns,
        order: [[0, 'asc']],
        pageLength: 250,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'Todos']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        dom: '<"d-none"B>lfrtip',
        scrollX: true,
        scrollY: '300px',
        scrollCollapse: true,
        fixedColumns: { leftColumns: 2 },
        buttons: [botonExcel('packaging')],
        initComplete: function () {
            ajustarAltoScroll(this.api());
        }
    });

    dtPackaging.on('draw', function () {
        ajustarAltoScroll(dtPackaging);
    });
}

// ─── Carga de datos ────────────────────────────────────────────────────────────

function cargarDatos() {
    mostrarSpinner('Cargando matriz de reposición...');

    $.ajax({
        url: REPO_AJAX_URL,
        method: 'GET',
        dataType: 'json',
        timeout: 280000,
        success: function (resp) {
            if (!resp || !resp.ok) {
                mostrarError((resp && resp.error) ? resp.error : 'Error desconocido al cargar los datos.');
                return;
            }

            datosActuales = resp;
            $('#fechaGenerado').text(resp.generado || '-');
            poblarFiltros(resp.filtros || {});
            inicializarTablaPanel(resp.data_panel || []);

            packagingInicializado = false;
            if (dtPackaging) {
                dtPackaging.destroy();
                $('#tablaPackaging tbody').empty();
                dtPackaging = null;
            }
            if ($('#tab-packaging').hasClass('active')) {
                inicializarTablaPackaging(resp.data_packaging || []);
                packagingInicializado = true;
            }

            actualizarContador();
        },
        error: function (xhr) {
            let mensaje = 'No se pudo conectar con el servidor.';
            try {
                const json = JSON.parse(xhr.responseText);
                if (json && json.error) {
                    mensaje = json.error;
                }
            } catch (e) {
                // respuesta no era JSON, se mantiene el mensaje genérico
            }
            mostrarError(mensaje);
        },
        complete: function () {
            ocultarSpinner();
        }
    });
}

// ─── Eventos ───────────────────────────────────────────────────────────────────

$(document).ready(function () {
    $('#filtroRubro, #filtroCategoria, #filtroDestino, #filtroTemporada').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Todos',
        allowClear: true,
        closeOnSelect: false
    });

    $('#filtroRubro, #filtroCategoria, #filtroDestino, #filtroTemporada').on('change', function () {
        if (dtPanel) {
            dtPanel.draw();
        }
    });

    $('#btnLimpiarFiltros').on('click', function () {
        $('#filtroRubro, #filtroCategoria, #filtroDestino, #filtroTemporada').val(null).trigger('change');
    });

    $('#btnRecargar').on('click', function () {
        cargarDatos();
    });

    $('#btnExportPanel').on('click', function () {
        if (dtPanel) {
            dtPanel.button(0).trigger();
        }
    });

    $('#btnExportPackaging').on('click', function () {
        if (dtPackaging) {
            dtPackaging.button(0).trigger();
        }
    });

    $('#tab-panel-btn').on('shown.bs.tab', function () {
        $('#cardFiltros').show();
        if (dtPanel) {
            dtPanel.columns.adjust();
            ajustarAltoScroll(dtPanel);
        }
    });

    $('#tab-packaging-btn').on('shown.bs.tab', function () {
        $('#cardFiltros').hide();
        if (!packagingInicializado) {
            inicializarTablaPackaging(datosActuales.data_packaging || []);
            packagingInicializado = true;
        } else if (dtPackaging) {
            dtPackaging.columns.adjust();
            ajustarAltoScroll(dtPackaging);
        }
    });

    let resizeTimeout;
    $(window).on('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
            if (dtPanel) {
                dtPanel.columns.adjust();
                ajustarAltoScroll(dtPanel);
            }
            if (dtPackaging) {
                dtPackaging.columns.adjust();
                ajustarAltoScroll(dtPackaging);
            }
        }, 150);
    });

    // El ícono del header carga async y puede correr unos px la maqueta recién
    // después del initComplete de la tabla: se vuelve a ajustar por las dudas.
    $(window).on('load', function () {
        if (dtPanel) {
            ajustarAltoScroll(dtPanel);
        }
        if (dtPackaging) {
            ajustarAltoScroll(dtPackaging);
        }
    });

    cargarDatos();
});
