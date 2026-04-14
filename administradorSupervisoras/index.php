<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Supervisoras</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --color-primary:      #4a5fc1;
            --color-primary-dark: #2d3a6b;
            --header-h:           60px;
            --bg-body:            #f0f2f5;
            --radius:             10px;
            --shadow:             0 2px 8px rgba(0,0,0,.08);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg-body);
            color: #333;
            margin: 0;
            min-height: 100vh;
        }

        /* ── Topbar ── */
        .topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--header-h);
            background: linear-gradient(135deg, #1a1f36 0%, #2d3a6b 100%);
            color: #fff;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,.3);
            gap: .75rem;
        }
        .topbar-brand {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: .5px;
            display: flex;
            align-items: center;
            gap: .5rem;
            flex: 1;
        }
        .topbar-brand .badge-tool {
            font-size: .65rem;
            background: rgba(255,255,255,.15);
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 400;
        }

        /* ── Main ── */
        .main-content {
            margin-top: var(--header-h);
            padding: 1.5rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ── Filtro bar ── */
        .filters-bar {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }
        .filters-bar .form-label {
            font-size: .8rem;
            font-weight: 600;
            color: #555;
            margin-bottom: .25rem;
            display: block;
        }
        .filters-bar select,
        .filters-bar input { min-width: 180px; font-size: .875rem; }

        /* ── Tabla card ── */
        .table-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .table-card-header {
            padding: .85rem 1.25rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }
        .table-card-header h6 {
            margin: 0;
            font-weight: 700;
            color: var(--color-primary-dark);
            font-size: .95rem;
        }
        .badge-count {
            background: var(--color-primary);
            color: #fff;
            font-size: .7rem;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* ── Tabla sucursales ── */
        .table-supervisoras { margin: 0; font-size: .875rem; }
        .table-supervisoras thead th {
            background: #f8f9fc;
            font-weight: 700;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #6c757d;
            border-bottom: 2px solid #dee2e6;
            padding: .65rem 1rem;
            white-space: nowrap;
        }
        .table-supervisoras tbody td {
            padding: .55rem 1rem;
            vertical-align: middle;
            border-color: #f0f2f5;
        }
        .table-supervisoras tbody tr:hover { background: #f8f9fc; }

        .suc-nro   { font-weight: 700; color: var(--color-primary); font-size: .82rem; }
        .suc-cod   { font-size: .78rem; color: #888; font-family: monospace; }
        .suc-nombre { font-weight: 500; }

        .select-supervisora {
            min-width: 200px; font-size: .82rem;
            border-radius: 6px; border-color: #dee2e6; padding: .3rem .5rem;
        }
        .select-supervisora:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(74,95,193,.15);
            outline: none;
        }

        .btn-save-row {
            padding: .3rem .65rem; font-size: .78rem; border-radius: 6px;
            border: none; background: var(--color-primary); color: #fff;
            cursor: pointer; transition: background .15s; white-space: nowrap;
        }
        .btn-save-row:hover:not(:disabled) { background: var(--color-primary-dark); }
        .btn-save-row:disabled { opacity: .6; cursor: default; }
        .btn-save-row.saved { background: #28a745; }
        .btn-save-row.error { background: #dc3545; }

        .badge-sin-asignar {
            background: #f0f2f5; color: #999;
            border: 1px dashed #ccc; font-size: .75rem;
            padding: 2px 8px; border-radius: 20px;
        }

        /* ── Loading ── */
        #loading-overlay {
            position: fixed; inset: 0;
            background: rgba(255,255,255,.7);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999;
        }
        #loading-overlay.hidden { display: none; }
        .loader-box {
            background: #fff; border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.15);
            padding: 1.5rem 2rem;
            display: flex; align-items: center; gap: 1rem;
            font-weight: 500; color: #444;
        }
        .loader-spinner {
            width: 28px; height: 28px;
            border: 3px solid #e9ecef; border-top-color: var(--color-primary);
            border-radius: 50%; animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Toast ── */
        #toast-container {
            position: fixed; bottom: 1.5rem; right: 1.5rem;
            z-index: 10000; display: flex; flex-direction: column; gap: .5rem;
        }
        .toast-item {
            padding: .65rem 1rem; border-radius: 8px;
            font-size: .85rem; font-weight: 500; color: #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,.18);
            animation: fadeInUp .2s ease; max-width: 320px;
        }
        .toast-item.success { background: #28a745; }
        .toast-item.error   { background: #dc3545; }
        .toast-item.warning { background: #fd7e14; }
        @keyframes fadeInUp {
            from { opacity:0; transform: translateY(12px); }
            to   { opacity:1; transform: translateY(0); }
        }

        /* ── Chips resumen ── */
        .resumen-bar { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
        .resumen-chip {
            background: #fff; border: 1.5px solid #dee2e6; border-radius: 20px;
            padding: .3rem .85rem; font-size: .78rem; cursor: pointer;
            transition: border-color .15s, background .15s;
            display: flex; align-items: center; gap: .35rem;
            font-weight: 500; color: #555;
        }
        .resumen-chip:hover,
        .resumen-chip.active {
            border-color: var(--color-primary);
            background: #eef0fb; color: var(--color-primary);
        }
        .resumen-chip .chip-count {
            background: var(--color-primary); color: #fff;
            border-radius: 10px; padding: 0 6px;
            font-size: .7rem; font-weight: 700;
        }
        .resumen-chip.active .chip-count { background: var(--color-primary-dark); }

        /* ── Offcanvas gestión supervisoras ── */
        .offcanvas-supervisoras { width: 480px !important; }
        .offcanvas-header {
            background: linear-gradient(135deg, #1a1f36 0%, #2d3a6b 100%);
            color: #fff;
        }
        .offcanvas-header .btn-close { filter: invert(1); }

        /* Tabla de gestión */
        .table-gestion { font-size: .85rem; margin: 0; }
        .table-gestion thead th {
            background: #f8f9fc; font-weight: 700;
            font-size: .75rem; text-transform: uppercase;
            letter-spacing: .4px; color: #6c757d;
            border-bottom: 2px solid #dee2e6; padding: .5rem .75rem;
        }
        .table-gestion tbody td {
            padding: .45rem .75rem; vertical-align: middle;
            border-color: #f0f2f5;
        }
        .table-gestion tbody tr:hover { background: #fafbfc; }

        .nombre-editable {
            border: none; background: transparent;
            font-weight: 500; width: 100%; padding: 2px 4px;
            border-radius: 4px; transition: background .15s;
        }
        .nombre-editable:focus {
            outline: none; background: #eef0fb;
            border-bottom: 2px solid var(--color-primary);
        }

        .btn-icon {
            border: none; background: transparent; cursor: pointer;
            border-radius: 6px; padding: 3px 7px; font-size: .85rem;
            transition: background .15s;
        }
        .btn-icon:hover { background: #e9ecef; }
        .btn-icon.text-success:hover { background: #d1f0dc; }
        .btn-icon.text-danger:hover  { background: #fde8e8; }
        .btn-icon.text-warning:hover { background: #fff3cd; }

        .badge-activa    { font-size: .7rem; padding: 2px 8px; border-radius: 10px; background: #d1f0dc; color: #155724; font-weight: 600; }
        .badge-inactiva  { font-size: .7rem; padding: 2px 8px; border-radius: 10px; background: #f0f2f5; color: #888; font-weight: 600; }
        .badge-suc-count { font-size: .7rem; padding: 2px 6px; border-radius: 10px; background: #eef0fb; color: var(--color-primary); font-weight: 600; }

        /* Form nueva supervisora */
        .form-nueva {
            background: #f8f9fc; border-radius: 8px;
            padding: .85rem 1rem; margin-bottom: 1rem;
            border: 1px solid #e9ecef;
        }
        .form-nueva h6 { font-size: .82rem; font-weight: 700; color: #555; margin-bottom: .6rem; }
        .form-nueva .input-group { gap: .5rem; }
    </style>
</head>
<body>

<!-- ═══════ Topbar ═══════════════════════════════════════════ -->
<header class="topbar">
    <div class="topbar-brand">
        <i class="bi bi-people-fill" style="font-size:1.2rem;"></i>
        Administrador de Supervisoras
        <span class="badge-tool">Sucursales Propias</span>
    </div>
    <button class="btn btn-sm btn-outline-light" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSupervisoras" title="Gestionar supervisoras">
        <i class="bi bi-person-gear me-1"></i> Gestionar supervisoras
    </button>
    <button class="btn btn-sm btn-outline-light" onclick="recargar()" title="Recargar datos">
        <i class="bi bi-arrow-repeat" id="ico-refresh"></i>
    </button>
</header>

<!-- ═══════ Offcanvas: Gestión de supervisoras ═══════════════ -->
<div class="offcanvas offcanvas-end offcanvas-supervisoras" tabindex="-1" id="offcanvasSupervisoras">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <i class="bi bi-person-gear me-2"></i>Gestión de supervisoras
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">

        <!-- Form agregar nueva -->
        <div class="form-nueva">
            <h6><i class="bi bi-person-plus-fill me-1"></i> Agregar nueva supervisora</h6>
            <div class="d-flex gap-2">
                <input type="text" id="input-nueva-sup" class="form-control form-control-sm"
                       placeholder="Nombre completo…" maxlength="100"
                       onkeydown="if(event.key==='Enter') agregarSupervisora()">
                <button class="btn btn-primary btn-sm px-3" onclick="agregarSupervisora()" id="btn-agregar">
                    <i class="bi bi-plus-lg"></i> Agregar
                </button>
            </div>
            <div id="nueva-sup-error" class="text-danger mt-1" style="font-size:.78rem;display:none;"></div>
        </div>

        <!-- Tabla gestión -->
        <div class="table-responsive" style="border-radius:8px;border:1px solid #e9ecef;overflow:hidden;">
            <table class="table table-gestion table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Sucs.</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-gestion">
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            <div class="loader-spinner mx-auto" style="width:20px;height:20px;border-width:2px;"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- ═══════ Loading overlay ═══════════════════════════════════ -->
<div id="loading-overlay">
    <div class="loader-box">
        <div class="loader-spinner"></div>
        Cargando datos…
    </div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- ═══════ Main ═════════════════════════════════════════════ -->
<main class="main-content">

    <!-- Filtros -->
    <div class="filters-bar">
        <div>
            <label class="form-label"><i class="bi bi-search"></i> Buscar sucursal</label>
            <input type="text" id="f-buscar" class="form-control" placeholder="Nombre o código…" oninput="filtrarTabla()">
        </div>
        <div>
            <label class="form-label"><i class="bi bi-person-badge"></i> Filtrar por supervisora</label>
            <select id="f-supervisora" class="form-select" onchange="filtrarTabla()">
                <option value="">Todas</option>
                <option value="__sin__">Sin asignar</option>
            </select>
        </div>
        <div class="ms-auto">
            <label class="form-label">&nbsp;</label>
            <button class="btn btn-outline-secondary btn-sm d-block" onclick="limpiarFiltros()">
                <i class="bi bi-x-circle"></i> Limpiar filtros
            </button>
        </div>
    </div>

    <!-- Chips resumen -->
    <div class="resumen-bar" id="resumen-bar"></div>

    <!-- Tabla sucursales -->
    <div class="table-card">
        <div class="table-card-header">
            <h6><i class="bi bi-table me-1"></i> Sucursales propias</h6>
            <span class="badge-count" id="badge-count">—</span>
        </div>
        <div class="table-responsive">
            <table class="table table-supervisoras table-hover" id="tabla-sucursales">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Código</th>
                        <th>Sucursal</th>
                        <th>Supervisora asignada</th>
                        <th style="width:220px;">Cambiar supervisora</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tbody-sucursales">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <div class="loader-spinner mx-auto mb-2" style="width:20px;height:20px;border-width:2px;"></div>
                            Cargando…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Modal confirmación eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-danger">
                    <i class="bi bi-trash3-fill me-1"></i> Eliminar supervisora
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Confirmar eliminación de <strong id="modal-nombre-sup"></strong>?
                <div class="text-muted mt-1" style="font-size:.82rem;">Esta acción no se puede deshacer.</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-danger" id="btn-confirmar-eliminar">
                    <i class="bi bi-trash3-fill me-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let sucursales   = [];
let supervisoras = [];   // solo activas (para dropdowns de sucursales)
let todasSuper   = [];   // todas (para panel gestión)
let filtroActivo = '';

// ═══════════════════════════════════════════════════════════════
// CARGA INICIAL
// ═══════════════════════════════════════════════════════════════
async function init() {
    showLoading(true);
    try {
        const [resSuc, resSup] = await Promise.all([
            fetch('api/getSucursales.php'),
            fetch('api/getSupervisoras.php')
        ]);
        if (!resSuc.ok || !resSup.ok) throw new Error('Error en la respuesta del servidor.');
        sucursales   = await resSuc.json();
        supervisoras = await resSup.json();
        if (sucursales.error)   throw new Error(sucursales.message);
        if (supervisoras.error) throw new Error(supervisoras.message);

        poblarFiltroSupervisoras();
        renderTabla(sucursales);
        renderResumen();
    } catch (err) {
        showToast('Error al cargar: ' + err.message, 'error');
        document.getElementById('tbody-sucursales').innerHTML =
            `<tr><td colspan="6" class="text-center text-danger py-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>${escHtml(err.message)}
             </td></tr>`;
    } finally {
        showLoading(false);
    }
}

// ═══════════════════════════════════════════════════════════════
// TABLA SUCURSALES
// ═══════════════════════════════════════════════════════════════
function poblarFiltroSupervisoras() {
    const sel = document.getElementById('f-supervisora');
    // Limpiar opciones anteriores (salvo las 2 fijas)
    while (sel.options.length > 2) sel.remove(2);
    supervisoras.forEach(s => {
        const opt = new Option(s.NOMBRE, s.ID);
        sel.appendChild(opt);
    });
}

function renderTabla(filas) {
    const tbody = document.getElementById('tbody-sucursales');
    if (!filas.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">
            <i class="bi bi-inbox me-1"></i> No hay sucursales que coincidan.</td></tr>`;
        document.getElementById('badge-count').textContent = '0';
        return;
    }
    document.getElementById('badge-count').textContent = filas.length;

    const opciones = supervisoras.map(s =>
        `<option value="${s.ID}">${escHtml(s.NOMBRE)}</option>`
    ).join('');

    tbody.innerHTML = filas.map(row => {
        const idSup  = row.ID_SUPERVISORA ?? '';
        const nomSup = row.NOMBRE_SUPERVISORA
            ? `<span class="fw-semibold text-primary">${escHtml(row.NOMBRE_SUPERVISORA)}</span>`
            : `<span class="badge-sin-asignar">Sin asignar</span>`;

        return `<tr>
            <td class="suc-nro">${row.NRO_SUCURSAL}</td>
            <td class="suc-cod">${escHtml(row.COD_CLIENT ?? '')}</td>
            <td class="suc-nombre">${escHtml(row.DESC_SUCURSAL ?? '')}</td>
            <td id="sup-actual-${row.NRO_SUCURSAL}">${nomSup}</td>
            <td>
                <select class="form-select select-supervisora"
                        id="sel-${row.NRO_SUCURSAL}"
                        data-original="${idSup}"
                        onchange="marcarCambio(this, ${row.NRO_SUCURSAL})">
                    <option value="">— Sin asignar —</option>
                    ${opciones}
                </select>
            </td>
            <td>
                <button class="btn-save-row" id="btn-${row.NRO_SUCURSAL}"
                        onclick="guardarCambio(${row.NRO_SUCURSAL})" disabled>
                    <i class="bi bi-floppy-fill me-1"></i>Guardar
                </button>
            </td>
        </tr>`;
    }).join('');

    filas.forEach(row => {
        const sel = document.getElementById(`sel-${row.NRO_SUCURSAL}`);
        if (sel && row.ID_SUPERVISORA) sel.value = row.ID_SUPERVISORA;
    });
}

function marcarCambio(selectEl, nro) {
    const btn = document.getElementById(`btn-${nro}`);
    btn.disabled = selectEl.value === (selectEl.dataset.original ?? '');
    btn.classList.remove('saved', 'error');
    btn.innerHTML = '<i class="bi bi-floppy-fill me-1"></i>Guardar';
}

async function guardarCambio(nro) {
    const sel = document.getElementById(`sel-${nro}`);
    const btn = document.getElementById(`btn-${nro}`);
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    try {
        const res  = await fetch('api/updateSupervisora.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nro_sucursal:   nro,
                id_supervisora: sel.value === '' ? null : parseInt(sel.value, 10)
            })
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.message || 'Error al guardar.');

        const suc    = sucursales.find(s => s.NRO_SUCURSAL == nro);
        const supObj = supervisoras.find(s => s.ID == sel.value);
        if (suc) {
            suc.ID_SUPERVISORA    = sel.value ? parseInt(sel.value, 10) : null;
            suc.NOMBRE_SUPERVISORA = supObj?.NOMBRE ?? null;
            document.getElementById(`sup-actual-${nro}`).innerHTML = supObj
                ? `<span class="fw-semibold text-primary">${escHtml(supObj.NOMBRE)}</span>`
                : `<span class="badge-sin-asignar">Sin asignar</span>`;
        }
        sel.dataset.original = sel.value;
        btn.classList.add('saved');
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Guardado';
        renderResumen();
        showToast('Supervisora actualizada correctamente.');
    } catch (err) {
        btn.disabled = false;
        btn.classList.add('error');
        btn.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Error';
        showToast(err.message, 'error');
    }
}

// ═══════════════════════════════════════════════════════════════
// CHIPS RESUMEN
// ═══════════════════════════════════════════════════════════════
function renderResumen() {
    const bar = document.getElementById('resumen-bar');
    const conteo = {};
    let sinAsignar = 0;
    sucursales.forEach(s => {
        if (!s.ID_SUPERVISORA) sinAsignar++;
        else conteo[s.ID_SUPERVISORA] = (conteo[s.ID_SUPERVISORA] || 0) + 1;
    });

    let html = '';
    supervisoras.forEach(sup => {
        const cant = conteo[sup.ID] || 0;
        if (!cant) return;
        const active = filtroActivo == sup.ID ? 'active' : '';
        html += `<span class="resumen-chip ${active}" onclick="toggleChip('${sup.ID}')">
            <i class="bi bi-person-fill"></i>${escHtml(sup.NOMBRE)}
            <span class="chip-count">${cant}</span>
        </span>`;
    });
    if (sinAsignar) {
        const active = filtroActivo === '__sin__' ? 'active' : '';
        html += `<span class="resumen-chip ${active}" onclick="toggleChip('__sin__')" style="border-style:dashed;">
            <i class="bi bi-person-dash"></i>Sin asignar
            <span class="chip-count" style="background:#999;">${sinAsignar}</span>
        </span>`;
    }
    bar.innerHTML = html;
}

function toggleChip(id) {
    filtroActivo = filtroActivo === id ? '' : id;
    document.getElementById('f-supervisora').value = filtroActivo;
    filtrarTabla();
    renderResumen();
}

// ═══════════════════════════════════════════════════════════════
// FILTROS
// ═══════════════════════════════════════════════════════════════
function filtrarTabla() {
    const buscar = document.getElementById('f-buscar').value.toLowerCase().trim();
    const supSel = document.getElementById('f-supervisora').value;

    const filtradas = sucursales.filter(s => {
        const matchBuscar = !buscar ||
            (s.DESC_SUCURSAL ?? '').toLowerCase().includes(buscar) ||
            (s.COD_CLIENT ?? '').toLowerCase().includes(buscar);

        let matchSup = true;
        if (supSel === '__sin__')      matchSup = !s.ID_SUPERVISORA;
        else if (supSel !== '')        matchSup = String(s.ID_SUPERVISORA) === String(supSel);

        return matchBuscar && matchSup;
    });
    renderTabla(filtradas);
}

function limpiarFiltros() {
    document.getElementById('f-buscar').value = '';
    document.getElementById('f-supervisora').value = '';
    filtroActivo = '';
    renderResumen();
    renderTabla(sucursales);
}

// ═══════════════════════════════════════════════════════════════
// PANEL GESTIÓN DE SUPERVISORAS
// ═══════════════════════════════════════════════════════════════
async function cargarGestion() {
    document.getElementById('tbody-gestion').innerHTML =
        `<tr><td colspan="4" class="text-center text-muted py-3">
            <div class="loader-spinner mx-auto" style="width:20px;height:20px;border-width:2px;"></div>
         </td></tr>`;
    try {
        const res  = await fetch('api/getAllSupervisoras.php');
        const data = await res.json();
        if (data.error) throw new Error(data.message);
        todasSuper = data;
        renderGestion();
    } catch (err) {
        document.getElementById('tbody-gestion').innerHTML =
            `<tr><td colspan="4" class="text-center text-danger py-2">${escHtml(err.message)}</td></tr>`;
    }
}

function renderGestion() {
    const tbody = document.getElementById('tbody-gestion');
    if (!todasSuper.length) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No hay supervisoras.</td></tr>`;
        return;
    }
    tbody.innerHTML = todasSuper.map(s => {
        const badgeEstado = s.ACTIVA
            ? `<span class="badge-activa">Activa</span>`
            : `<span class="badge-inactiva">Inactiva</span>`;
        const badgeSucs = s.CANT_SUCURSALES > 0
            ? `<span class="badge-suc-count">${s.CANT_SUCURSALES}</span>`
            : `<span style="color:#ccc;font-size:.75rem;">0</span>`;
        const toggleIcon  = s.ACTIVA ? 'bi-toggle-on text-success' : 'bi-toggle-off text-secondary';
        const toggleTitle = s.ACTIVA ? 'Deshabilitar' : 'Habilitar';

        return `<tr id="gestion-row-${s.ID}">
            <td>
                <input type="text" class="nombre-editable"
                       id="nombre-input-${s.ID}"
                       value="${escHtml(s.NOMBRE)}"
                       maxlength="100"
                       onkeydown="if(event.key==='Enter'){guardarNombre(${s.ID});this.blur();}">
            </td>
            <td class="text-center" id="estado-cell-${s.ID}">${badgeEstado}</td>
            <td class="text-center">${badgeSucs}</td>
            <td class="text-center" style="white-space:nowrap;">
                <button class="btn-icon" title="Guardar nombre" onclick="guardarNombre(${s.ID})">
                    <i class="bi bi-floppy-fill text-primary"></i>
                </button>
                <button class="btn-icon" title="${toggleTitle}" id="toggle-btn-${s.ID}" onclick="toggleSupervisora(${s.ID})">
                    <i class="bi ${toggleIcon}" style="font-size:1.1rem;"></i>
                </button>
                <button class="btn-icon" title="Eliminar" onclick="confirmarEliminar(${s.ID}, '${escHtml(s.NOMBRE)}')">
                    <i class="bi bi-trash3-fill text-danger"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

// ── Agregar supervisora ───────────────────────────────────────
async function agregarSupervisora() {
    const input  = document.getElementById('input-nueva-sup');
    const errDiv = document.getElementById('nueva-sup-error');
    const btn    = document.getElementById('btn-agregar');
    const nombre = input.value.trim();

    errDiv.style.display = 'none';
    if (!nombre) { errDiv.textContent = 'Ingrese un nombre.'; errDiv.style.display = 'block'; return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    try {
        const res  = await fetch('api/createSupervisora.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre })
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.message);

        input.value = '';
        showToast('Supervisora creada correctamente.');
        await cargarGestion();
        await refreshSupervisoras();
    } catch (err) {
        errDiv.textContent = err.message;
        errDiv.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus-lg"></i> Agregar';
    }
}

// ── Guardar nombre ────────────────────────────────────────────
async function guardarNombre(id) {
    const input  = document.getElementById(`nombre-input-${id}`);
    const nombre = input.value.trim();
    if (!nombre) { showToast('El nombre no puede estar vacío.', 'error'); return; }

    try {
        const res  = await fetch('api/editSupervisora.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nombre })
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.message);

        // Actualizar local
        const sup = todasSuper.find(s => s.ID == id);
        if (sup) sup.NOMBRE = nombre.toUpperCase();
        await refreshSupervisoras();
        showToast('Nombre actualizado.');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// ── Toggle activa ─────────────────────────────────────────────
async function toggleSupervisora(id) {
    const btn = document.getElementById(`toggle-btn-${id}`);
    btn.disabled = true;

    try {
        const res  = await fetch('api/toggleSupervisora.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.message);

        const sup = todasSuper.find(s => s.ID == id);
        if (sup) sup.ACTIVA = data.nuevo_estado;

        // Actualizar celda estado y botón toggle sin rerenderizar toda la tabla
        const estadoCell = document.getElementById(`estado-cell-${id}`);
        estadoCell.innerHTML = data.nuevo_estado
            ? `<span class="badge-activa">Activa</span>`
            : `<span class="badge-inactiva">Inactiva</span>`;

        const icon = btn.querySelector('i');
        icon.className = data.nuevo_estado
            ? 'bi bi-toggle-on text-success'
            : 'bi bi-toggle-off text-secondary';
        icon.style.fontSize = '1.1rem';
        btn.title = data.nuevo_estado ? 'Deshabilitar' : 'Habilitar';

        const tipo = data.nuevo_estado ? 'success' : 'warning';
        showToast(data.message, tipo);

        await refreshSupervisoras();
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        btn.disabled = false;
    }
}

// ── Eliminar ──────────────────────────────────────────────────
let _eliminarId = null;
const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));

function confirmarEliminar(id, nombre) {
    _eliminarId = id;
    document.getElementById('modal-nombre-sup').textContent = nombre;
    modalEliminar.show();
}

document.getElementById('btn-confirmar-eliminar').addEventListener('click', async () => {
    if (!_eliminarId) return;
    const btn = document.getElementById('btn-confirmar-eliminar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando…';

    try {
        const res  = await fetch('api/deleteSupervisora.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: _eliminarId })
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.message);

        modalEliminar.hide();
        showToast('Supervisora eliminada.');
        await cargarGestion();
        await refreshSupervisoras();
    } catch (err) {
        modalEliminar.hide();
        showToast(err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash3-fill me-1"></i> Eliminar';
        _eliminarId = null;
    }
});

// ── Refresh supervisoras activas (dropdowns + filtro) ─────────
async function refreshSupervisoras() {
    const res  = await fetch('api/getSupervisoras.php');
    const data = await res.json();
    if (!data.error) {
        supervisoras = data;
        poblarFiltroSupervisoras();
        renderResumen();
        // Actualizar dropdowns de la tabla (nombres pueden haber cambiado)
        renderTabla(sucursales);
    }
}

// ═══════════════════════════════════════════════════════════════
// RECARGAR GENERAL
// ═══════════════════════════════════════════════════════════════
async function recargar() {
    sucursales = []; supervisoras = [];
    document.getElementById('f-supervisora').innerHTML =
        '<option value="">Todas</option><option value="__sin__">Sin asignar</option>';
    await init();
    // Si el offcanvas está abierto, refrescar también la gestión
    if (document.getElementById('offcanvasSupervisoras').classList.contains('show')) {
        await cargarGestion();
    }
}

// ═══════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════
function showLoading(show) {
    document.getElementById('loading-overlay').classList.toggle('hidden', !show);
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function showToast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `toast-item ${type}`;
    const icon = type === 'success' ? 'check-circle-fill'
               : type === 'warning' ? 'exclamation-circle-fill'
               : 'exclamation-triangle-fill';
    el.innerHTML = `<i class="bi bi-${icon} me-1"></i>${escHtml(msg)}`;
    document.getElementById('toast-container').appendChild(el);
    setTimeout(() => el.remove(), 4000);
}

// ─── Abrir offcanvas → cargar gestión ────────────────────────
document.getElementById('offcanvasSupervisoras')
    .addEventListener('show.bs.offcanvas', cargarGestion);

// ─── Inicio ──────────────────────────────────────────────────
init();
</script>
</body>
</html>
