/* ── Consulta Stock Franquicias — Frontend Logic ──────────────────────── */
'use strict';

const API = 'controller/api.php';

/* ── Formatters ─────────────────────────────────────────────────────────── */
const fmtInt      = v => Number(v).toLocaleString('es-AR', { maximumFractionDigits: 0 });
const fmtDecimal  = v => Number(v).toLocaleString('es-AR', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

/* ── Toast ──────────────────────────────────────────────────────────────── */
function mostrarToast(msg, tipo = 'danger') {
    const el = document.getElementById('toast-notif');
    el.className = `toast align-items-center text-bg-${tipo} border-0`;
    document.getElementById('toast-body').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(el, { delay: 5000 }).show();
}

/* ── Filtros ────────────────────────────────────────────────────────────── */
function obtenerFiltros() {
    return {
        sucursal:     $('#f-sucursal').val()  || '',
        temporada:    $('#f-temporada').val() || '',
        destino:      $('#f-destino').val()   || [],
        rubros:       $('#f-rubros').val()    || [],
        categorias:   $('#f-categorias').val() || [],
        grupos:       $('#f-grupos').val()    || [],
        modelo:       $('#f-modelo').val().trim(),
        articulo:     $('#f-articulo').val().trim(),
        liquidacion:  $('#f-liquidacion').val() || '',
    };
}

function hayFiltrosActivos() {
    const f = obtenerFiltros();
    return f.sucursal || f.temporada || f.destino.length ||
           f.rubros.length || f.categorias.length || f.grupos.length ||
           f.modelo || f.articulo || f.liquidacion;
}

/* ── KPIs ───────────────────────────────────────────────────────────────── */
function cargarKPIs() {
    ['kpi-stock-total', 'kpi-articulos', 'kpi-franquicias'].forEach(id => {
        document.getElementById(id).innerHTML = '<span class="kpi-spinner"><i class="bi bi-hourglass-split"></i></span>';
    });

    $.ajax({
        url: API + '?action=get_kpis',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ filtros: obtenerFiltros() }),
        success(res) {
            if (!res.success) { mostrarToast('Error al cargar KPIs'); return; }
            const d = res.data;
            document.getElementById('kpi-stock-total').textContent  = fmtInt(d.stock_total);
            document.getElementById('kpi-articulos').textContent     = fmtInt(d.articulos_unicos);
            document.getElementById('kpi-franquicias').textContent   = fmtInt(d.franquicias_con_stock);
            document.getElementById('badge-ultima-actualizacion').textContent =
                'Actualizado: ' + new Date().toLocaleTimeString('es-AR');
        },
        error() { mostrarToast('No se pudieron cargar las métricas.'); }
    });
}

/* ── Opciones de filtros ────────────────────────────────────────────────── */
function cargarFiltros() {
    $.getJSON(API + '?action=get_filtros', function (res) {
        if (!res.success) { mostrarToast('Error al cargar filtros'); return; }
        const d = res.data;

        poblarSelect('#f-sucursal',   d.sucursales, false);
        poblarSelect('#f-temporada',  d.temporadas, false);
        poblarSelect('#f-destino',    d.destinos,   true);
        poblarSelect('#f-rubros',     d.rubros,     true);
        poblarSelect('#f-categorias', d.categorias, true);
        poblarSelect('#f-grupos',     d.grupos,     true);
    }).fail(function () {
        mostrarToast('No se pudieron cargar las opciones de filtros.');
    });
}

function poblarSelect(selector, valores, multiple) {
    const $sel = $(selector);
    const current = $sel.val();
    $sel.find('option:not(:first)').remove();

    valores.forEach(v => {
        if (v !== null && v !== '') {
            $sel.append(new Option(v, v));
        }
    });

    if (current) $sel.val(current).trigger('change');
}

/* ── DataTable ──────────────────────────────────────────────────────────── */
let tabla;
let buscarPresionado = false;

function initTabla() {
    tabla = $('#tabla-stock').DataTable({
        serverSide:  true,
        processing:  true,
        deferRender: true,
        pageLength:  25,
        lengthMenu:  [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        language:    lenguajeES(),
        dom: "<'row align-items-center mb-2'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row align-items-center mt-2'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        ajax: function (data, callback, settings) {
            if (!buscarPresionado) {
                callback({
                    draw: data.draw,
                    recordsTotal: 0,
                    recordsFiltered: 0,
                    data: []
                });
                return;
            }
            data.action  = 'get_data';
            data.filtros = JSON.stringify(obtenerFiltros());
            $.ajax({
                url:  API,
                type: 'POST',
                data: data,
                success(res) {
                    callback(res);
                },
                error(xhr) {
                    let msg = 'Error al cargar datos.';
                    try {
                        const r = JSON.parse(xhr.responseText);
                        if (r.message) msg = r.message;
                    } catch (_) {}
                    mostrarToast(msg);
                }
            });
        },
        columns: [
            { data: 'ARTICULO',         title: 'Artículo',   width: '80px' },
            { data: 'DESCRIPCION',      title: 'Descripción' },
            { data: 'NRO_SUCURSAL',     title: 'Nro. Suc.',  width: '75px', className: 'text-center' },
            { data: 'DESC_SUCURSAL',    title: 'Franquicia' },
            { data: 'TEMPORADA',        title: 'Temporada',  width: '90px' },
            { data: 'RUBRO',            title: 'Rubro',      width: '90px' },
            { data: 'CATEGORIA',        title: 'Categoría',  width: '100px' },
            { data: 'DESTINO',          title: 'Destino',    width: '80px' },
            { data: 'GRUPO_EMPRESARIO', title: 'Grupo',      width: '90px' },
            { data: 'MODELO',           title: 'Modelo',     width: '80px' },
            {
                data: 'LIQUIDACION',
                title: 'Liquidación',
                width: '80px',
                className: 'text-center fw-semibold',
                 render: (d) => {
                    if (d === null || d === undefined || d === '' || String(d).trim().toUpperCase() === 'NO') {
                        return '<span class="badge bg-light text-muted border" style="opacity:0.75; font-weight:normal;">No</span>';
                    }
                    const s = String(d).trim().toUpperCase();
                    if (s === 'SI' || s === 'SÍ') {
                        return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Sí</span>';
                    }
                    const num = Number(d);
                    if (!isNaN(num) && num > 0) {
                        return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Sí</span>';
                    }
                    return '<span class="badge bg-light text-muted border" style="opacity:0.75; font-weight:normal;">No</span>';
                }
            },
            {
                data: 'CANT_STOCK',
                title: 'Stock',
                width: '70px',
                className: 'text-end fw-semibold',
                render: (d) => `<span class="badge badge-stock">${fmtInt(d)}</span>`
            },
        ],
        order: [[3, 'asc']],
        fixedHeader: {
            header: true,
            headerOffset: document.querySelector('.navbar')?.offsetHeight ?? 56,
        },
        drawCallback(settings) {
            const info = settings.json;
            if (info) {
                const total    = fmtInt(info.recordsFiltered);
                const sinFiltro = fmtInt(info.recordsTotal);
                const lbl = hayFiltrosActivos()
                    ? `${total} de ${sinFiltro} registros`
                    : `${sinFiltro} registros`;
                document.getElementById('lbl-conteo').textContent = lbl;

                const warnEl = document.getElementById('badge-export-warning');
                warnEl.classList.toggle('d-none', info.recordsFiltered <= 100000);
            }
        }
    });
}

/* ── Recargar tabla + KPIs al cambiar filtros ───────────────────────────── */
let debounceTimer;
function aplicarFiltros() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
        if (tabla) tabla.ajax.reload(null, true);
        cargarKPIs();
    }, 400);
}

/* ── Export ─────────────────────────────────────────────────────────────── */
function exportar(formato) {
    const btn = document.getElementById(formato === 'excel' ? 'btn-exportar-excel' : 'btn-exportar-csv');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Exportando…';
    btn.disabled = true;

    $.ajax({
        url: API + '?action=export_data',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ filtros: obtenerFiltros() }),
        success(res) {
            if (!res.success || !res.data || !res.data.length) {
                mostrarToast('No hay datos para exportar con los filtros actuales.', 'warning');
                return;
            }

            const rows     = res.data;
            const headers  = Object.keys(rows[0]);
            const data     = [headers, ...rows.map(r => headers.map(h => {
                let val = r[h];
                if (h === 'CANT_STOCK') {
                    const parsed = parseInt(val, 10);
                    val = isNaN(parsed) ? 0 : parsed;
                }
                return val;
            }))];
            const filename = `stock_franquicias_${fechaArchivo()}`;

            if (formato === 'excel') {
                exportarExcel(data, filename);
            } else {
                exportarCSV(data, filename);
            }

            mostrarToast(`Exportadas ${fmtInt(rows.length)} filas.`, 'success');
        },
        error() {
            mostrarToast('Error al exportar. Intente nuevamente.');
        },
        complete() {
            btn.innerHTML = orig;
            btn.disabled  = false;
        }
    });
}

function exportarExcel(data, filename) {
    const ws = XLSX.utils.aoa_to_sheet(data);

    // Ancho automático de columnas
    const colWidths = data[0].map((_, i) =>
        ({ wch: Math.max(...data.map(r => String(r[i] ?? '').length), String(data[0][i]).length) + 2 })
    );
    ws['!cols'] = colWidths;

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Stock Franquicias');
    XLSX.writeFile(wb, filename + '.xlsx');
}

function exportarCSV(data, filename) {
    const csv = data.map(row =>
        row.map(v => '"' + String(v ?? '').replace(/"/g, '""') + '"').join(',')
    ).join('\r\n');

    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = filename + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}

function fechaArchivo() {
    const d = new Date();
    return [
        d.getFullYear(),
        String(d.getMonth() + 1).padStart(2, '0'),
        String(d.getDate()).padStart(2, '0'),
        '_',
        String(d.getHours()).padStart(2, '0'),
        String(d.getMinutes()).padStart(2, '0')
    ].join('');
}

function mostrarKPIsVacios() {
    document.getElementById('kpi-stock-total').textContent  = '-';
    document.getElementById('kpi-articulos').textContent     = '-';
    document.getElementById('kpi-franquicias').textContent   = '-';
}

/* ── Lenguaje DataTables ────────────────────────────────────────────────── */
function lenguajeES() {
    return {
        processing:   '<div class="dt-processing"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Procesando…</div>',
        search:       '',
        searchPlaceholder: 'Buscar en resultados…',
        lengthMenu:   'Mostrar _MENU_ filas',
        info:         'Filas _START_ a _END_ de _TOTAL_',
        infoEmpty:    'Sin resultados',
        infoFiltered: '(filtrado de _MAX_ totales)',
        paginate: {
            first:    '«', last: '»', next: '›', previous: '‹'
        },
        emptyTable:   '<div class="text-center py-4 text-muted"><i class="bi bi-funnel fs-3 d-block mb-2"></i>Seleccione los filtros y presione Buscar para comenzar.</div>',
        zeroRecords:  '<div class="text-center py-4 text-muted"><i class="bi bi-search fs-3 d-block mb-2"></i>Sin resultados para la búsqueda.</div>',
    };
}

/* ── Init ───────────────────────────────────────────────────────────────── */
$(function () {

    // Tooltips Bootstrap en KPI cards
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover focus' });
    });

    // Select2 — single
    $('.select2-single').select2({
        theme:        'bootstrap-5',
        allowClear:   true,
        width:        '100%',
    });

    // Select2 — multi
    $('.select2-multi').select2({
        theme:        'bootstrap-5',
        closeOnSelect: false,
        width:        '100%',
    });

    // Carga inicial de opciones
    cargarFiltros();

    // Tabla server-side
    initTabla();

    // KPIs iniciales vacíos
    mostrarKPIsVacios();

    // ── Eventos de filtros ────────────────────────────────────────────────

    // Buscar
    $('#btn-buscar').on('click', function () {
        buscarPresionado = true;
        cargarKPIs();
        if (tabla) tabla.ajax.reload(null, true);
    });

    // Filtrar categorias en base a rubro
    $('#f-rubros').on('change', function () {
        const rubros = $(this).val() || [];
        $.ajax({
            url: API + '?action=get_categorias',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ rubros: rubros }),
            success(res) {
                if (res.success) {
                    poblarSelect('#f-categorias', res.data, true);
                }
            }
        });
    });

    // Limpiar filtros
    $('#btn-limpiar').on('click', function () {
        $('#f-sucursal, #f-temporada, #f-destino, #f-liquidacion').val(null).trigger('change');
        $('#f-rubros, #f-categorias, #f-grupos').val(null).trigger('change');
        $('#f-modelo, #f-articulo').val('');
        buscarPresionado = false;
        mostrarKPIsVacios();
        if (tabla) tabla.ajax.reload(null, true);
    });

    // Exportar
    $('#btn-exportar-excel').on('click', () => exportar('excel'));
    $('#btn-exportar-csv').on('click',   () => exportar('csv'));

    // Toggle panel filtros
    $('#btn-toggle-filtros').on('click', function () {
        const panel = $('#panel-filtros');
        const icon  = $('#icon-toggle-filtros');
        panel.slideToggle(200);
        icon.toggleClass('bi-chevron-up bi-chevron-down');
    });

    // Refresh caché
    $('#btn-refresh-cache').on('click', function () {
        const btn = this;
        btn.disabled = true;
        $.post(API + '?action=invalidar_cache', function () {
            cargarFiltros();
            mostrarToast('Caché de filtros actualizado.', 'success');
        }).fail(function () {
            mostrarToast('Error al actualizar caché.');
        }).always(function () {
            setTimeout(() => { btn.disabled = false; }, 2000);
        });
    });
});
