/* ─── Globals ─────────────────────────────────────────────────── */
let timeoutId;
let tableTimeoutId;
let currentBusqueda  = '';
let currentCanal     = '';
let currentProvincia = '';
let currentView      = 'cards'; // 'cards' | 'list'
let totalResults     = 0;

const AJAX_URL = 'controller/direccionario_ajax.php';

/* ─── Canal config ────────────────────────────────────────────── */
const CANAL_ICONS = {
    'MAYORISTA':    'bi-bag-check-fill',
    'FRANQUICIA':   'bi-shop',
    'PROPIO':       'bi-building',
    'ONLINE':       'bi-globe2',
    'DISTRIBUIDOR': 'bi-truck',
    'DEFAULT':      'bi-geo-alt-fill',
};

const CANAL_COLOR_INDEX = {};
let   canalColorCounter = 0;

function getCanalColorClass(canal) {
    const key = (canal || '').toUpperCase();
    if (CANAL_COLOR_INDEX[key] === undefined) {
        CANAL_COLOR_INDEX[key] = canalColorCounter % 6;
        canalColorCounter++;
    }
    return 'canal-color-' + CANAL_COLOR_INDEX[key];
}

function getCanalIcon(canal) {
    const key = (canal || '').toUpperCase();
    for (const [k, v] of Object.entries(CANAL_ICONS)) {
        if (key.includes(k)) return v;
    }
    return CANAL_ICONS.DEFAULT;
}

/* ─── Render helpers ──────────────────────────────────────────── */
function cleanDireccion(direccion) {
    if (!direccion) return '';
    return direccion
        .replace(/\s*\(.*?\)/g, '')            // elimina (NOMBRE SHOPPING)
        .replace(/\s*-\s+.*/g, '')             // elimina - LOCAL 1058 / - LOC 1166
        .replace(/\s+L\d+[\d\/\w]*\s*$/i, '') // elimina L25/26 al final
        .trim();
}

function mapsUrl(direccion, localidad, provincia) {
    const cleanAddr = cleanDireccion(direccion);
    const query = encodeURIComponent([cleanAddr, localidad, provincia].filter(Boolean).join(', '));
    return 'https://www.google.com/maps/search/?api=1&query=' + query;
}

function createSucursalCard(s, index) {
    const icon       = getCanalIcon(s.canal);
    const colorClass = getCanalColorClass(s.canal);
    const delay      = Math.min(index * 40, 400);

    const mailBtn = s.mail
        ? `<button class="btn-copy-mail" onclick="copiarAlPortapapeles('${s.mail}')" title="Copiar email">
               <i class="bi bi-clipboard"></i>
           </button>`
        : '';

    return `
    <div class="sucursal-card" style="animation-delay:${delay}ms">
        <div class="card-header-area">
            <span class="card-canal-badge ${colorClass}">${s.canal || 'Sin canal'}</span>
            <div class="card-icon-wrap"><i class="bi ${icon}"></i></div>
            <p class="card-name">${s.desc_sucursal || 'Sin nombre'}</p>
            ${s.nro_sucursal ? `<span class="card-nro">Suc. #${s.nro_sucursal}</span>` : ''}
        </div>
        <div class="card-body-area">
            <div class="card-info-row">
                <i class="bi bi-geo-alt"></i>
                <span>${s.localidad || '—'}, ${s.provincia || '—'}</span>
            </div>
            ${s.direccion ? `
            <div class="card-info-row">
                <i class="bi bi-signpost-split"></i>
                <span>${s.direccion}</span>
            </div>` : ''}
            ${s.telefono ? `
            <div class="card-info-row">
                <i class="bi bi-telephone"></i>
                <span>${s.telefono}</span>
            </div>` : ''}
            ${s.mail ? `
            <div class="card-info-row">
                <i class="bi bi-envelope"></i>
                <a href="mailto:${s.mail}">${s.mail}</a>
            </div>` : ''}
            ${s.horario ? `
            <div class="card-info-row">
                <i class="bi bi-clock"></i>
                <span>${s.horario}</span>
            </div>` : ''}
        </div>
        <div class="card-footer-area">
            <a href="${mapsUrl(s.direccion, s.localidad, s.provincia)}"
               target="_blank"
               class="btn-maps">
                <i class="bi bi-map"></i> Ver en Maps
            </a>
            ${mailBtn}
        </div>
    </div>`;
}

function createListRow(s) {
    const colorClass = getCanalColorClass(s.canal);
    return `
    <tr>
        <td>
            <div class="tbl-name">${s.desc_sucursal || '—'}</div>
            ${s.nro_sucursal ? `<div class="tbl-sub">Suc. #${s.nro_sucursal}</div>` : ''}
        </td>
        <td><span class="tbl-badge ${colorClass}">${s.canal || '—'}</span></td>
        <td>${s.localidad || '—'}</td>
        <td>${s.provincia || '—'}</td>
        <td>${s.direccion || '—'}</td>
        <td>${s.telefono || '—'}</td>
        <td>${s.mail
            ? `<a href="mailto:${s.mail}" class="tbl-link">${s.mail}</a>`
            : '—'}</td>
        <td>${s.horario || '—'}</td>
        <td>
            <a href="${mapsUrl(s.direccion, s.localidad, s.provincia)}"
               target="_blank"
               class="btn-maps" style="padding:6px 10px; font-size:.8rem;">
                <i class="bi bi-map"></i>
            </a>
        </td>
    </tr>`;
}

/* ─── Data loading ────────────────────────────────────────────── */
function cargarDatos(busqueda, canal, provincia) {
    busqueda  = busqueda  !== undefined ? busqueda  : currentBusqueda;
    canal     = canal     !== undefined ? canal     : currentCanal;
    provincia = provincia !== undefined ? provincia : currentProvincia;

    showLoading();

    $.ajax({
        url:    AJAX_URL,
        method: 'POST',
        data: {
            action:    'buscar',
            busqueda:  busqueda,
            canal:     canal,
            provincia: provincia
        },
        dataType: 'json',
        success: function (response) {
            if (!Array.isArray(response)) {
                mostrarNotificacion('Error al obtener datos', 'error');
                showEmpty('No se pudo cargar la información.');
                return;
            }

            totalResults = response.length;
            updateResultCount(totalResults);

            if (totalResults === 0) {
                showEmpty('No se encontraron sucursales con esos filtros.');
                return;
            }

            if (currentView === 'cards') {
                renderCards(response);
            } else {
                renderList(response);
            }
        },
        error: function () {
            mostrarNotificacion('Error de conexión', 'error');
            showEmpty('Error al comunicarse con el servidor.');
        }
    });
}

function renderCards(data) {
    const html = data.map((s, i) => createSucursalCard(s, i)).join('');
    $('#cards-container').html(html);
    showView('cards');
}

function renderList(data) {
    const html = data.map(createListRow).join('');
    $('#list-tbody').html(html);
    showView('list');
}

/* ─── View switching ──────────────────────────────────────────── */
function cambiarVista(vista) {
    currentView = vista;
    $('.btn-view').removeClass('active');
    $(`.btn-view[data-view="${vista}"]`).addClass('active');
    cargarDatos();
}

function showView(vista) {
    $('#state-box').hide();
    if (vista === 'cards') {
        $('#list-view').hide();
        $('#cards-view').fadeIn(220);
    } else {
        $('#cards-view').hide();
        $('#list-view').fadeIn(220);
    }
}

function showLoading() {
    $('#cards-view, #list-view').hide();
    $('#state-box').html(`
        <div class="state-box">
            <div class="spinner-ring"></div>
            <p>Cargando sucursales…</p>
        </div>`).show();
}

function showEmpty(msg) {
    $('#cards-view, #list-view').hide();
    $('#state-box').html(`
        <div class="state-box">
            <i class="bi bi-inbox"></i>
            <p>${msg}</p>
        </div>`).show();
}

function updateResultCount(n) {
    $('#result-count').text(n + (n === 1 ? ' sucursal' : ' sucursales'));
}

/* ─── Table search (client-side) ──────────────────────────────── */
function filterTable(val) {
    val = val.toLowerCase();
    $('#list-tbody tr').each(function () {
        $(this).toggle($(this).text().toLowerCase().includes(val));
    });
}

/* ─── Utility ─────────────────────────────────────────────────── */
function copiarAlPortapapeles(texto) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto)
            .then(() => mostrarNotificacion('Copiado al portapapeles', 'success'))
            .catch(() => fallbackCopy(texto));
    } else {
        fallbackCopy(texto);
    }
}

function fallbackCopy(texto) {
    const el = document.createElement('input');
    el.style.position = 'absolute';
    el.style.left = '-9999px';
    el.value = texto;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    mostrarNotificacion('Copiado al portapapeles', 'success');
}

function mostrarNotificacion(mensaje, tipo) {
    tipo = tipo || 'success';
    Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2800,
        timerProgressBar: true,
        didOpen: function (t) {
            t.addEventListener('mouseenter', Swal.stopTimer);
            t.addEventListener('mouseleave', Swal.resumeTimer);
        }
    }).fire({ icon: tipo, title: mensaje });
}

/* ─── Init ────────────────────────────────────────────────────── */
$(document).ready(function () {

    /* initial load */
    cargarDatos('', '', '');

    /* ── Sidebar search ── */
    $('#sidebar-search').on('input', function () {
        const val = $(this).val();
        $('#clear-sidebar-search').toggle(val.length > 0);
        clearTimeout(timeoutId);
        timeoutId = setTimeout(function () {
            currentBusqueda = val;
            cargarDatos();
        }, 300);
    });

    $('#clear-sidebar-search').on('click', function () {
        $('#sidebar-search').val('');
        $(this).hide();
        currentBusqueda = '';
        cargarDatos();
        $('#sidebar-search').focus();
    });

    /* ── Canal filter ── */
    $(document).on('click', '.filter-item[data-canal]', function () {
        const val = $(this).data('canal');
        $('.filter-item[data-canal]').removeClass('active');
        $(this).addClass('active');
        currentCanal = val;
        cargarDatos();
    });

    /* ── Provincia filter ── */
    $(document).on('click', '.filter-item[data-provincia]', function () {
        const val = $(this).data('provincia');
        $('.filter-item[data-provincia]').removeClass('active');
        $(this).addClass('active');
        currentProvincia = val;
        cargarDatos();
    });

    /* ── Reset all ── */
    $('#btn-reset').on('click', function () {
        currentBusqueda  = '';
        currentCanal     = '';
        currentProvincia = '';
        $('#sidebar-search').val('');
        $('#clear-sidebar-search').hide();
        $('.filter-item[data-canal]').first().addClass('active');
        $('.filter-item[data-canal]').not(':first').removeClass('active');
        $('.filter-item[data-provincia]').first().addClass('active');
        $('.filter-item[data-provincia]').not(':first').removeClass('active');
        cargarDatos();
    });

    /* ── View toggle ── */
    $(document).on('click', '.btn-view', function () {
        cambiarVista($(this).data('view'));
    });

    /* ── List-view search ── */
    $('#list-search').on('input', function () {
        const val = $(this).val();
        $('#clear-list-search').toggle(val.length > 0);
        clearTimeout(tableTimeoutId);
        tableTimeoutId = setTimeout(function () {
            filterTable(val);
        }, 200);
    });

    $('#clear-list-search').on('click', function () {
        $('#list-search').val('');
        $(this).hide();
        filterTable('');
        $('#list-search').focus();
    });

    /* ── Export Excel ── */
    $('#btn-export').on('click', function () {
        try {
            const wb   = XLSX.utils.book_new();
            const rows = [];

            rows.push(['Sucursal', 'Canal', 'Localidad', 'Provincia', 'Dirección', 'Teléfono', 'Mail', 'Horario']);

            $('#list-tbody tr:visible').each(function () {
                const tds = $(this).find('td');
                rows.push([
                    tds.eq(0).text().trim(),
                    tds.eq(1).text().trim(),
                    tds.eq(2).text().trim(),
                    tds.eq(3).text().trim(),
                    tds.eq(4).text().trim(),
                    tds.eq(5).text().trim(),
                    tds.eq(6).text().trim(),
                    tds.eq(7).text().trim(),
                ]);
            });

            const ws = XLSX.utils.aoa_to_sheet(rows);
            ws['!cols'] = [30, 15, 20, 20, 30, 15, 30, 25].map(function (w) { return { wch: w }; });
            XLSX.utils.book_append_sheet(wb, ws, 'Sucursales');

            const fecha = new Date().toISOString().slice(0, 10);
            XLSX.writeFile(wb, 'direccionario_sucursales_' + fecha + '.xlsx');
            mostrarNotificacion('Exportado correctamente', 'success');
        } catch (e) {
            console.error(e);
            mostrarNotificacion('Error al exportar', 'error');
        }
    });
});
