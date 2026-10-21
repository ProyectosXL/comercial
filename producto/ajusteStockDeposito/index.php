<?php
if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}

require_once __DIR__ . '/Class/AjusteStockDeposito.php';

Auth::exigir('comer.item.precios_productos_y_auditoria_ajuste_de_stock_por_deposito');

$depositos = AjusteStockDeposito::$DEPOSITOS_HABILITADOS;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajuste de Stock por Depósito · XL Extra Large</title>
    <link rel="shortcut icon" href="/sistemas/assets/css/icono.jpg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- SheetJS (XLSX reader/writer) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <style>
        :root {
            --primary: #0066cc;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            min-height: 100vh;
        }

        .main-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.75rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .page-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .app-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            margin-bottom: 1.5rem;
        }

        .table-custom { font-size: 0.88rem; }
        .table-custom th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .row-invalid { background-color: #fef2f2 !important; border-left: 4px solid var(--danger-color); }
        .row-warning { background-color: #fffbeb !important; border-left: 4px solid var(--warning-color); }
        .row-editing { background-color: #eff6ff !important; border-left: 4px solid var(--primary); }

        .totales-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 0.75rem; }
        .total-box { border: 1px solid var(--border-color); border-radius: 10px; padding: 0.6rem 0.9rem; background: #fff; }
        .total-box .lbl { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .4px; }
        .total-box .val { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 1.05rem; }

        .tabla-scroll { max-height: 65vh; overflow: auto; }
        .resultado-txt { font-size: 0.8rem; max-width: 420px; }
        .input-mini { min-width: 70px; }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="/centralizadores/comer/index.php" class="text-decoration-none text-muted"><i class="bi bi-grid-fill"></i> COMER</a></li>
                        <li class="breadcrumb-item text-muted">Precios, Productos y Auditoría</li>
                        <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Ajuste de Stock por Depósito</li>
                    </ol>
                </nav>
                <h1 class="page-title">
                    <i class="bi bi-arrow-down-up text-primary"></i> Ajuste de Stock por Depósito
                </h1>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1" id="btn-historial">
                    <i class="bi bi-clock-history"></i> Lotes
                </button>
                <button type="button" class="btn btn-outline-info d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                    <i class="bi bi-question-circle"></i> Ayuda
                </button>
            </div>
        </div>
    </header>

    <div class="container-fluid py-4 px-4">

        <div class="alert alert-primary border-primary border-opacity-25 bg-primary bg-opacity-10 d-flex align-items-center gap-3 mb-4 rounded-3">
            <i class="bi bi-info-circle-fill text-primary fs-4"></i>
            <div>
                <strong class="text-dark">Ajuste de stock en Tango por depósito:</strong>
                <span class="text-muted">da de alta o de baja unidades en los depósitos <?= htmlspecialchars(implode(', ', array_keys($depositos))) ?> sin mover ubicaciones del WMS. Cantidad negativa = baja, positiva = alta.</span>
            </div>
        </div>

        <!-- ================= NUEVO LOTE ================= -->
        <div id="seccion-carga">
            <div class="app-card">
                <label for="leyenda" class="form-label fw-bold"><i class="bi bi-tag text-primary"></i> Motivo / Leyenda del ajuste <span class="text-danger">*</span></label>
                <input type="text" id="leyenda" class="form-control" maxlength="120" placeholder="Ej: Regularización ecommerce desde inventario 28/01 – Ajustes Negativos">
                <div class="form-text">Se guarda en el lote y en la leyenda del comprobante de Tango, para poder rastrearlo.</div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="app-card h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="fw-bold m-0"><i class="bi bi-clipboard-plus text-primary"></i> 1. Pegar Datos desde Excel</h5>
                            <span class="badge bg-light text-muted border">3 o 4 columnas</span>
                        </div>
                        <p class="small text-muted mb-2">Sin encabezados: <code>Depósito</code>, <code>Artículo</code>, <code>Cantidad</code> y opcionalmente <code>Partida</code>.</p>
                        <textarea id="datos-pegados" class="form-control font-monospace small mb-3" rows="8" placeholder="01&#9;XV6WYI18C0701&#9;-22&#10;05&#9;XV6WYI18C0701&#9;-849&#10;05&#9;XV5SYI52C1701&#9;15"></textarea>
                        <div class="d-flex gap-2">
                            <button type="button" id="btn-previsualizar" class="btn btn-primary d-flex align-items-center gap-2">
                                <i class="bi bi-eye-fill"></i> Previsualizar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('datos-pegados').value = '';">
                                <i class="bi bi-trash"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="app-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-spreadsheet text-success"></i> 2. Subir Archivo Excel</h5>
                            <p class="small text-muted mb-3">Archivo <code>.xlsx</code> con el mismo formato (primera hoja, sin encabezados).</p>
                            <input type="file" id="archivo-xlsx" class="form-control form-control-sm mb-3" accept=".xlsx,.xls">
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <button type="button" id="btn-subir-archivo" class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-upload"></i> Cargar y Previsualizar
                            </button>
                            <button type="button" id="btn-plantilla" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-download"></i> Descargar Plantilla
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vista previa -->
            <div id="seccion-preview" class="app-card mt-4" style="display:none;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="fw-bold m-0"><i class="bi bi-table text-primary"></i> Vista Previa</h5>
                        <span id="prev-badge-filas" class="badge bg-primary rounded-pill px-3 py-2">0 filas</span>
                        <span id="prev-badge-errores" class="badge bg-danger rounded-pill px-3 py-2" style="display:none;">0 con error</span>
                    </div>
                    <button type="button" id="btn-importar" class="btn btn-primary fw-bold d-flex align-items-center gap-2 px-3 py-2">
                        <i class="bi bi-shield-check fs-5"></i> Crear Lote y Validar
                    </button>
                </div>
                <div id="prev-totales" class="totales-grid mb-3"></div>
                <div class="table-responsive border rounded-3 tabla-scroll">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Depósito</th>
                                <th>Artículo</th>
                                <th class="text-end">Cantidad</th>
                                <th>Partida</th>
                                <th>Estado</th>
                                <th class="text-center" style="width: 60px;">Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-preview"></tbody>
                    </table>
                </div>
                <div id="prev-paginacion" class="d-flex justify-content-end mt-2"></div>
            </div>
        </div>

        <!-- ================= LOTE ACTUAL ================= -->
        <div id="seccion-lote" style="display:none;">
            <div class="app-card">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <div class="small text-muted">Lote <span id="lote-id" class="font-monospace"></span> · <span id="lote-usuario"></span> · <span id="lote-fecha"></span></div>
                        <h5 class="fw-bold mb-1" id="lote-leyenda"></h5>
                        <span id="lote-estado" class="badge"></span>
                        <span id="lote-comprobantes" class="small text-muted ms-2"></span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" id="btn-nuevo" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus-lg"></i> Nuevo lote</button>
                        <button type="button" id="btn-descartar" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Descartar lote</button>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap mb-3">
                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2"><i class="bi bi-check-circle"></i> <span id="cnt-ok">0</span> OK</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2"><i class="bi bi-exclamation-triangle"></i> <span id="cnt-adv">0</span> advertencias</span>
                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle px-3 py-2"><i class="bi bi-x-circle"></i> <span id="cnt-err">0</span> errores</span>
                    <span id="badge-ejec" class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2" style="display:none;"><i class="bi bi-check2-all"></i> <span id="cnt-ejec">0</span> ya aplicadas en Tango</span>
                </div>
                <div id="lote-totales" class="totales-grid mb-3"></div>

                <!-- Acciones -->
                <div id="barra-acciones" class="d-flex flex-wrap align-items-center gap-2 border-top pt-3">
                    <button type="button" id="btn-revalidar" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-repeat"></i> Revalidar</button>
                    <button type="button" id="btn-exportar-errores" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-arrow-down"></i> Exportar errores a Excel</button>
                    <button type="button" id="btn-reemplazar" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-arrow-up"></i> Reemplazar filas con error</button>
                    <input type="file" id="archivo-correcciones" accept=".xlsx,.xls" class="d-none">
                    <div class="ms-auto d-flex align-items-center gap-3">
                        <div class="form-check m-0" id="wrap-acepta" style="display:none;">
                            <input class="form-check-input" type="checkbox" id="chk-acepta">
                            <label class="form-check-label small" for="chk-acepta">Acepto advertencias</label>
                        </div>
                        <button type="button" id="btn-ejecutar" class="btn btn-success fw-bold d-flex align-items-center gap-2 px-3" disabled>
                            <i class="bi bi-play-circle fs-5"></i> <span id="txt-ejecutar">Ejecutar Ajuste en Tango</span>
                        </button>
                    </div>
                </div>
                <div id="motivo-bloqueo" class="small text-danger mt-2"></div>
            </div>

            <div class="app-card">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="btn-group btn-group-sm" role="group" id="filtros-estado">
                        <button type="button" class="btn btn-outline-secondary" data-filtro="pendientes">Errores y advertencias</button>
                        <button type="button" class="btn btn-outline-secondary" data-filtro="ERROR">Solo errores</button>
                        <button type="button" class="btn btn-outline-secondary" data-filtro="ADVERTENCIA">Solo advertencias</button>
                        <button type="button" class="btn btn-outline-secondary" data-filtro="todas">Todas</button>
                    </div>
                    <input type="search" id="buscar-articulo" class="form-control form-control-sm" style="max-width: 240px;" placeholder="Buscar artículo...">
                </div>
                <div class="table-responsive border rounded-3 tabla-scroll">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 55px;">#</th>
                                <th>Dep.</th>
                                <th>Artículo</th>
                                <th class="text-end">Cantidad</th>
                                <th>Partida</th>
                                <th class="text-end">Stock actual</th>
                                <th class="text-end">Stock result.</th>
                                <th>Estado</th>
                                <th>Detalle</th>
                                <th class="text-end" style="width: 190px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-lote"></tbody>
                    </table>
                </div>
                <div id="lote-paginacion" class="d-flex justify-content-between align-items-center mt-2 small text-muted"></div>
            </div>
        </div>
    </div>

    <!-- Modal Historial -->
    <div class="modal fade" id="modalHistorial" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-clock-history text-primary"></i> Lotes de ajuste</h5>
                    <div class="form-check ms-4 mb-0">
                        <input class="form-check-input" type="checkbox" id="chk-pendientes" checked>
                        <label class="form-check-label small" for="chk-pendientes">Solo sin ejecutar</label>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle table-custom">
                            <thead>
                                <tr>
                                    <th>Fecha</th><th>Usuario</th><th>Leyenda</th><th>Estado</th>
                                    <th class="text-end">Filas</th><th class="text-end">Bajas</th><th class="text-end">Altas</th>
                                    <th class="text-end">Errores</th><th>Comprobantes</th><th></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-historial"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ayuda -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-question-circle text-primary"></i> Instructivo: Ajuste de Stock por Depósito</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>Genera comprobantes de ajuste de stock en <strong>Tango</strong> por depósito. <strong>No mueve ubicaciones del WMS.</strong></p>

                    <h6 class="fw-bold mt-3"><i class="bi bi-list-ol text-primary"></i> Pasos</h6>
                    <ol class="small text-muted">
                        <li>Completá el <strong>motivo / leyenda</strong>.</li>
                        <li>Pegá desde Excel o subí un archivo con las columnas <code>Depósito</code>, <code>Artículo</code>, <code>Cantidad</code> y opcionalmente <code>Partida</code>, sin encabezados. Cantidad negativa = baja; positiva = alta.</li>
                        <li>Revisá los totales por depósito y hacé clic en <strong>Crear Lote y Validar</strong>.</li>
                        <li>Corregí las filas con error: editándolas en la grilla, con <strong>Ajustar a stock</strong>, <strong>Aceptar FIFO</strong> o quitándolas. También podés <strong>exportar los errores</strong>, corregirlos en Excel y volver a subirlos con <strong>Reemplazar filas con error</strong>.</li>
                        <li>Cuando no queden errores, hacé clic en <strong>Ejecutar Ajuste en Tango</strong>.</li>
                    </ol>

                    <h6 class="fw-bold mt-3"><i class="bi bi-shield-check text-primary"></i> Validaciones</h6>
                    <ul class="small text-muted">
                        <li>El artículo tiene que existir en Tango, llevar stock y no estar inhabilitado.</li>
                        <li><strong>Bajas:</strong> la suma de las bajas del artículo en el depósito no puede superar el stock actual. Si el stock resultante queda por debajo de lo comprometido o reservado, se muestra una advertencia.</li>
                        <li><strong>Partidas</strong> (artículos que las usan):
                            <ul>
                                <li>Si se informa una partida, tiene que existir, y en las bajas tiene que tener saldo en el depósito.</li>
                                <li>Un alta sin partida se suma a la última partida del depósito o, si no hay, a la última partida del artículo. Se muestra como advertencia.</li>
                                <li>Una baja sin partida se descuenta por FIFO, de la partida más antigua a la más nueva. El desglose se muestra como advertencia; con <strong>Aceptar FIFO</strong> podés convertirlo en filas.</li>
                                <li>Si el artículo no lleva partidas, la columna tiene que quedar vacía.</li>
                            </ul>
                        </li>
                        <li>No se puede repetir artículo + depósito + partida, ni tener altas y bajas del mismo artículo en el mismo depósito.</li>
                    </ul>

                    <div class="alert alert-warning small mt-3">
                        <i class="bi bi-shield-exclamation"></i>
                        <strong>Importante:</strong> la ejecución se aplica en bloques chicos de artículos, con pausas entre bloques, para no frenar a los demás procesos de Tango. Si Tango está ocupado, el bloque espera y se reintenta solo. Podés detener la ejecución y continuarla después: lo aplicado queda aplicado y no se duplica. Antes de cada bloque se vuelve a controlar el stock; si cambió, ese bloque no se aplica y sus filas quedan marcadas para corregir.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const API = 'controller/ajuste_ajax.php';
        const DEPOSITOS = <?= json_encode($depositos) ?>;
        const TAM_PAGINA = 200;

        let filasPreview = [];
        let pagPreview = 1;

        let lote = null;        // cabecera
        let detalle = [];       // filas del lote
        let filtroEstado = 'pendientes';
        let pagLote = 1;
        let filaEditando = null;

        // ---------------------------------------------------------------
        // Utilidades
        // ---------------------------------------------------------------
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const fmt = (n) => (n === null || n === undefined) ? '' : Number(n).toLocaleString('es-AR', { maximumFractionDigits: 2 });

        function normalizarDeposito(v) {
            let t = String(v ?? '').trim();
            if (t.endsWith('.0')) t = t.slice(0, -2);
            if (!/^\d{1,2}$/.test(t)) return null;
            return t.padStart(2, '0');
        }

        function parsearCantidad(v) {
            if (typeof v === 'number') return Number.isInteger(v) ? v : NaN;
            let t = String(v ?? '').replace(/\s/g, '');
            if (/^-?\d{1,3}(\.\d{3})+$/.test(t)) t = t.replace(/\./g, '');
            if (/^-?\d+\.0+$/.test(t)) t = t.split('.')[0];
            return /^-?\d+$/.test(t) ? parseInt(t, 10) : NaN;
        }

        function filaDesdeColumnas(cols) {
            const depCrudo = String(cols[0] ?? '').trim();
            const dep = normalizarDeposito(depCrudo);
            const art = String(cols[1] ?? '').trim().toUpperCase();
            const cantCruda = cols[2];
            const cant = parsearCantidad(cantCruda);
            const partida = String(cols[3] ?? '').trim().toUpperCase();

            const errores = [];
            if (!dep || !DEPOSITOS[dep]) errores.push(`Depósito "${depCrudo}" no habilitado`);
            if (!art) errores.push('Artículo vacío');
            if (Number.isNaN(cant)) errores.push(`Cantidad "${cantCruda ?? ''}" inválida`);
            else if (cant === 0) errores.push('Cantidad cero');

            return { dep: dep || depCrudo, art, cant: Number.isNaN(cant) ? String(cantCruda ?? '') : cant, partida, error: errores.join('; ') || null };
        }

        function leerTextoPegado(raw) {
            return raw.split(/\r?\n/)
                .map(l => l.split('\t'))
                .filter(c => c.some(x => String(x).trim() !== ''))
                .map(filaDesdeColumnas);
        }

        function leerArchivo(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const wb = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });
                        const ws = wb.Sheets[wb.SheetNames[0]];
                        const rows = XLSX.utils.sheet_to_json(ws, { header: 1, raw: true, defval: '' });
                        resolve(rows.filter(r => r && r.some(c => String(c).trim() !== '')).map(filaDesdeColumnas));
                    } catch (err) { reject(err); }
                };
                reader.onerror = reject;
                reader.readAsArrayBuffer(file);
            });
        }

        function totalesPorDeposito(filas, getDep, getCant) {
            const t = {};
            filas.forEach(f => {
                const dep = getDep(f), cant = getCant(f);
                if (typeof cant !== 'number' || !cant) return;
                const k = dep + (cant < 0 ? '|BAJA' : '|ALTA');
                if (!t[k]) t[k] = { dep, tipo: cant < 0 ? 'Baja' : 'Alta', filas: 0, unidades: 0 };
                t[k].filas++;
                t[k].unidades += Math.abs(cant);
            });
            return Object.values(t).sort((a, b) => (a.dep + a.tipo).localeCompare(b.dep + b.tipo));
        }

        function renderTotales(contenedor, totales) {
            contenedor.innerHTML = totales.map(t => `
                <div class="total-box">
                    <div class="lbl">${t.tipo === 'Baja' ? '<i class="bi bi-arrow-down text-danger"></i>' : '<i class="bi bi-arrow-up text-success"></i>'} ${t.tipo} dep. ${esc(t.dep)}</div>
                    <div class="val">${fmt(t.unidades)} u.</div>
                    <div class="small text-muted">${fmt(t.filas)} artículos</div>
                </div>`).join('');
        }

        function renderPaginacion(contenedor, total, pagina, onCambio) {
            const paginas = Math.max(1, Math.ceil(total / TAM_PAGINA));
            if (paginas <= 1) { contenedor.innerHTML = total ? `<span>${fmt(total)} filas</span>` : ''; return; }
            contenedor.innerHTML = `
                <span>${fmt(total)} filas · página ${pagina} de ${paginas}</span>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" ${pagina <= 1 ? 'disabled' : ''} data-p="${pagina - 1}"><i class="bi bi-chevron-left"></i></button>
                    <button class="btn btn-outline-secondary" ${pagina >= paginas ? 'disabled' : ''} data-p="${pagina + 1}"><i class="bi bi-chevron-right"></i></button>
                </div>`;
            contenedor.querySelectorAll('button[data-p]').forEach(b => b.addEventListener('click', () => onCambio(parseInt(b.dataset.p))));
        }

        async function api(accion, body = null, query = '') {
            const opts = body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) } : {};
            const res = await fetch(`${API}?accion=${accion}${query}`, opts);
            if (res.status === 504 || res.status === 502) {
                throw new Error('El servidor tardó demasiado en responder. El proceso puede haber terminado igual: revisá el lote en "Lotes" antes de reintentar.');
            }
            let data;
            try {
                data = await res.json();
            } catch (e) {
                throw new Error(`Respuesta inválida del servidor (HTTP ${res.status}).`);
            }
            if (!data.ok) {
                const msg = data.errores ? data.errores.map(esc).join('<br>') : esc(data.error || data.ejecucion?.mensaje || 'Error desconocido');
                const err = new Error(msg);
                err.data = data;
                throw err;
            }
            return data;
        }

        function cargando(titulo, texto = '') {
            Swal.fire({ title: titulo, text: texto, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        }

        function mostrarError(titulo, err) {
            Swal.fire({ title: titulo, html: `<div class="text-start small" style="max-height:50vh;overflow:auto">${err.message || 'No se pudo conectar con el servidor.'}</div>`, icon: 'error' });
        }

        // ---------------------------------------------------------------
        // Carga y vista previa
        // ---------------------------------------------------------------
        document.getElementById('btn-previsualizar').addEventListener('click', () => {
            const raw = document.getElementById('datos-pegados').value.trim();
            if (!raw) { Swal.fire('Atención', 'Pegá datos desde Excel antes de previsualizar.', 'warning'); return; }
            filasPreview = leerTextoPegado(raw);
            if (!filasPreview.length) { Swal.fire('Error', 'No se detectaron filas.', 'error'); return; }
            pagPreview = 1;
            renderPreview();
        });

        document.getElementById('btn-subir-archivo').addEventListener('click', async () => {
            const input = document.getElementById('archivo-xlsx');
            if (!input.files?.length) { Swal.fire('Atención', 'Seleccioná un archivo primero.', 'warning'); return; }
            try {
                filasPreview = await leerArchivo(input.files[0]);
                if (!filasPreview.length) { Swal.fire('Error', 'No se encontraron filas en el archivo.', 'error'); return; }
                pagPreview = 1;
                renderPreview();
            } catch (e) {
                Swal.fire('Error', 'No se pudo leer el archivo.', 'error');
            }
        });

        document.getElementById('btn-plantilla').addEventListener('click', () => {
            const ws = XLSX.utils.aoa_to_sheet([
                ['01', 'XV6WYI18C0701', -22, ''],
                ['05', 'XV6WYI18C0701', -849, ''],
                ['05', 'XV5SYI52C1701', 15, ''],
            ]);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Ajuste');
            XLSX.writeFile(wb, 'PlantillaAjusteStockDeposito.xlsx');
        });

        function renderPreview() {
            const sec = document.getElementById('seccion-preview');
            if (!filasPreview.length) { sec.style.display = 'none'; return; }
            sec.style.display = 'block';

            const errores = filasPreview.filter(f => f.error).length;
            document.getElementById('prev-badge-filas').textContent = `${fmt(filasPreview.length)} filas`;
            const bErr = document.getElementById('prev-badge-errores');
            bErr.style.display = errores ? 'inline-block' : 'none';
            bErr.textContent = `${fmt(errores)} con error`;

            renderTotales(document.getElementById('prev-totales'), totalesPorDeposito(filasPreview.filter(f => !f.error), f => f.dep, f => f.cant));

            // Errores primero, para que se vean sin paginar
            const orden = filasPreview.map((f, idx) => ({ f, idx })).sort((a, b) => (b.f.error ? 1 : 0) - (a.f.error ? 1 : 0));
            const pagina = orden.slice((pagPreview - 1) * TAM_PAGINA, pagPreview * TAM_PAGINA);

            document.getElementById('tbody-preview').innerHTML = pagina.map(({ f, idx }) => `
                <tr class="${f.error ? 'row-invalid' : ''}">
                    <td class="text-muted fw-bold">${idx + 1}</td>
                    <td class="font-monospace">${esc(f.dep)}</td>
                    <td><strong>${esc(f.art)}</strong></td>
                    <td class="text-end fw-bold ${typeof f.cant === 'number' && f.cant < 0 ? 'text-danger' : 'text-success'}">${typeof f.cant === 'number' ? (f.cant > 0 ? '+' : '') + fmt(f.cant) : esc(f.cant)}</td>
                    <td class="font-monospace">${esc(f.partida)}</td>
                    <td>${f.error ? `<span class="badge bg-danger">${esc(f.error)}</span>` : '<span class="badge bg-success">Listo</span>'}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="quitarFilaPreview(${idx})"><i class="bi bi-x-lg"></i></button>
                    </td>
                </tr>`).join('');

            renderPaginacion(document.getElementById('prev-paginacion'), filasPreview.length, pagPreview, p => { pagPreview = p; renderPreview(); });
        }

        window.quitarFilaPreview = (idx) => { filasPreview.splice(idx, 1); renderPreview(); };

        document.getElementById('btn-importar').addEventListener('click', async () => {
            const leyenda = document.getElementById('leyenda').value.trim();
            if (!leyenda) { Swal.fire('Atención', 'Completá el motivo / leyenda del ajuste.', 'warning'); document.getElementById('leyenda').focus(); return; }
            if (!filasPreview.length) return;
            if (filasPreview.some(f => f.error)) {
                Swal.fire('Hay filas con error', 'Corregí o quitá las filas marcadas en rojo antes de crear el lote.', 'warning');
                return;
            }

            cargando('Creando lote...', `Cargando ${fmt(filasPreview.length)} filas`);
            let loteId;
            try {
                const creado = await api('importar', {
                    leyenda,
                    filas: filasPreview.map(f => [f.dep, f.art, f.cant, f.partida])
                });
                loteId = creado.lote_id;
                filasPreview = [];
                document.getElementById('datos-pegados').value = '';
                document.getElementById('archivo-xlsx').value = '';
                renderPreview();
            } catch (err) {
                mostrarError('Error al crear el lote', err);
                return;
            }

            cargando('Validando lote...', 'Controlando stock y partidas en Tango');
            try {
                const data = await api('validar', { lote_id: loteId });
                Swal.close();
                aplicarLote(data, true);
                avisoValidacion(data.resumen);
            } catch (err) {
                // El lote ya existe: se abre igual para poder revalidar
                try { aplicarLote(await api('obtener', null, '&lote_id=' + encodeURIComponent(loteId)), true); } catch (e) { /* sin conexión */ }
                mostrarError('El lote se creó pero la validación falló. Probá con "Revalidar".', err);
            }
        });

        // ---------------------------------------------------------------
        // Lote
        // ---------------------------------------------------------------
        function aplicarLote(data, resetFiltro = false) {
            lote = data.lote;
            detalle = data.detalle || [];
            filaEditando = null;

            const url = new URL(window.location.href);
            url.searchParams.set('lote', lote.id);
            window.history.replaceState({}, '', url);

            if (resetFiltro) {
                const hayPendientes = detalle.some(d => d.estado === 'ERROR' || d.estado === 'ADVERTENCIA');
                filtroEstado = hayPendientes ? 'pendientes' : 'todas';
                pagLote = 1;
            }

            document.getElementById('seccion-carga').style.display = 'none';
            document.getElementById('seccion-lote').style.display = 'block';
            renderLote();
        }

        function avisoValidacion(r) {
            if (!r) return;
            if (r.errores > 0) {
                Swal.fire('Validación con errores', `${fmt(r.errores)} fila(s) con error y ${fmt(r.advertencias)} con advertencia. Corregilas para poder ejecutar.`, 'warning');
            } else if (r.advertencias > 0) {
                Swal.fire('Validado con advertencias', `${fmt(r.advertencias)} fila(s) con advertencia. Revisalas antes de ejecutar.`, 'info');
            } else {
                Swal.fire('Lote validado', `Las ${fmt(r.ok)} filas están OK. Ya podés ejecutar el ajuste.`, 'success');
            }
        }

        const BADGE_LOTE = { CARGADO: 'bg-secondary', CON_ERRORES: 'bg-danger', VALIDADO: 'bg-success', EN_PROCESO: 'bg-info text-dark', EJECUTADO: 'bg-primary' };
        const TXT_LOTE = { CARGADO: 'Cambios sin validar', CON_ERRORES: 'Con errores', VALIDADO: 'Validado', EN_PROCESO: 'Ejecución parcial', EJECUTADO: 'Ejecutado' };

        function renderLote() {
            const ejecutado = lote.estado === 'EJECUTADO';
            const ejecutadas = detalle.filter(d => d.ejecutada).length;

            document.getElementById('lote-id').textContent = lote.id;
            document.getElementById('lote-usuario').textContent = lote.usuario;
            document.getElementById('lote-fecha').textContent = lote.fecha_alta || '';
            document.getElementById('lote-leyenda').textContent = lote.leyenda;
            const bEstado = document.getElementById('lote-estado');
            bEstado.className = 'badge ' + (BADGE_LOTE[lote.estado] || 'bg-secondary');
            bEstado.textContent = TXT_LOTE[lote.estado] || lote.estado;
            document.getElementById('lote-comprobantes').textContent = lote.comprobantes ? `Comprobantes: ${lote.comprobantes}` : (lote.mensaje || '');

            // Los contadores de estado son de las filas pendientes; las aplicadas se cuentan aparte
            const cnt = { OK: 0, ADVERTENCIA: 0, ERROR: 0 };
            detalle.forEach(d => { if (!d.ejecutada && cnt[d.estado] !== undefined) cnt[d.estado]++; });
            document.getElementById('cnt-ok').textContent = fmt(cnt.OK);
            document.getElementById('cnt-adv').textContent = fmt(cnt.ADVERTENCIA);
            document.getElementById('cnt-err').textContent = fmt(cnt.ERROR);
            document.getElementById('cnt-ejec').textContent = fmt(ejecutadas);
            document.getElementById('badge-ejec').style.display = ejecutadas ? 'inline-block' : 'none';

            renderTotales(document.getElementById('lote-totales'), totalesPorDeposito(detalle, d => d.deposito, d => d.cantidad));

            // Barra de acciones
            document.getElementById('barra-acciones').style.display = ejecutado ? 'none' : 'flex';
            // Con bloques ya aplicados en Tango el lote no se puede descartar
            document.getElementById('btn-descartar').style.display = (ejecutado || ejecutadas) ? 'none' : 'inline-block';
            document.getElementById('txt-ejecutar').textContent = ejecutadas ? 'Continuar ejecución' : 'Ejecutar Ajuste en Tango';
            document.getElementById('btn-exportar-errores').disabled = cnt.ERROR === 0;
            document.getElementById('btn-reemplazar').disabled = cnt.ERROR === 0;
            document.getElementById('wrap-acepta').style.display = cnt.ADVERTENCIA > 0 ? 'block' : 'none';

            let bloqueo = '';
            if (!ejecutado) {
                if (lote.estado === 'CARGADO') bloqueo = 'Hay cambios sin validar: hacé clic en Revalidar.';
                else if (cnt.ERROR > 0) bloqueo = `Corregí las ${fmt(cnt.ERROR)} fila(s) con error para poder ejecutar.`;
                else if (cnt.ADVERTENCIA > 0 && !document.getElementById('chk-acepta').checked) bloqueo = 'Revisá las advertencias y tildá "Acepto advertencias".';
            }
            document.getElementById('motivo-bloqueo').textContent = bloqueo;
            document.getElementById('btn-ejecutar').disabled = ejecutado || bloqueo !== '';

            document.querySelectorAll('#filtros-estado button').forEach(b => {
                b.classList.toggle('active', b.dataset.filtro === filtroEstado);
            });

            renderGrilla();
        }

        function filasFiltradas() {
            const q = document.getElementById('buscar-articulo').value.trim().toUpperCase();
            return detalle.filter(d => {
                if (filtroEstado === 'pendientes' && (d.ejecutada || !(d.estado === 'ERROR' || d.estado === 'ADVERTENCIA'))) return false;
                if ((filtroEstado === 'ERROR' || filtroEstado === 'ADVERTENCIA') && (d.ejecutada || d.estado !== filtroEstado)) return false;
                if (q && !d.articulo.includes(q)) return false;
                return true;
            });
        }

        function renderGrilla() {
            const ejecutado = lote.estado === 'EJECUTADO';
            const filas = filasFiltradas();
            const paginas = Math.max(1, Math.ceil(filas.length / TAM_PAGINA));
            if (pagLote > paginas) pagLote = paginas;
            const pagina = filas.slice((pagLote - 1) * TAM_PAGINA, pagLote * TAM_PAGINA);

            const tbody = document.getElementById('tbody-lote');
            if (!pagina.length) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4">${filtroEstado === 'pendientes' ? 'No hay filas con errores ni advertencias.' : 'Sin filas para mostrar.'}</td></tr>`;
            } else {
                tbody.innerHTML = pagina.map(d => filaEditando === d.fila ? filaEdicionHtml(d) : filaHtml(d, ejecutado)).join('');
            }
            renderPaginacion(document.getElementById('lote-paginacion'), filas.length, pagLote, p => { pagLote = p; renderGrilla(); });
        }

        function filaHtml(d, ejecutado) {
            // Fila ya aplicada en Tango: solo lectura
            if (d.ejecutada) {
                return `
                    <tr class="text-muted">
                        <td class="fw-bold">${d.fila}</td>
                        <td class="font-monospace">${esc(d.deposito)}</td>
                        <td><strong>${esc(d.articulo)}</strong></td>
                        <td class="text-end fw-bold ${d.cantidad < 0 ? 'text-danger' : 'text-success'}">${d.cantidad > 0 ? '+' : ''}${fmt(d.cantidad)}</td>
                        <td class="font-monospace">${esc(d.partida)}</td>
                        <td class="text-end">${fmt(d.stock_actual)}</td>
                        <td class="text-end">${fmt(d.stock_resultante)}</td>
                        <td><span class="badge bg-primary">Aplicada</span></td>
                        <td class="resultado-txt">${d.comprobante ? 'AJU ' + esc(d.comprobante) : ''}</td>
                        <td></td>
                    </tr>`;
            }

            const clase = d.estado === 'ERROR' ? 'row-invalid' : (d.estado === 'ADVERTENCIA' ? 'row-warning' : '');
            const badge = d.estado === 'ERROR' ? '<span class="badge bg-danger">Error</span>'
                        : d.estado === 'ADVERTENCIA' ? '<span class="badge bg-warning text-dark">Advertencia</span>'
                        : d.estado === 'OK' ? '<span class="badge bg-success">OK</span>'
                        : '<span class="badge bg-secondary">Sin validar</span>';

            let sugerencia = '';
            if (d.sugerencia && d.sugerencia.length) {
                sugerencia = '<div class="mt-1">' + d.sugerencia.map(s => `<span class="badge bg-light text-dark border me-1 font-monospace">${esc(s.partida)}: ${fmt(s.cantidad)}</span>`).join('') + '</div>';
            }

            let acciones = '';
            if (!ejecutado) {
                const editar = `<button class="btn btn-outline-primary btn-sm p-1 px-2" title="Editar" onclick="editarFila(${d.fila})"><i class="bi bi-pencil"></i></button>`;
                const ajustar = (d.cantidad < 0 && d.estado === 'ERROR' && d.disponible > 0 && d.disponible < -d.cantidad)
                    ? `<button class="btn btn-outline-warning btn-sm p-1 px-2" title="Ajustar la baja al disponible (${fmt(d.disponible)})" onclick="ajustarStock(${d.fila})"><i class="bi bi-arrow-down-short"></i>${fmt(Math.floor(d.disponible))}</button>` : '';
                const fifo = (d.sugerencia && d.sugerencia.length)
                    ? `<button class="btn btn-outline-success btn-sm p-1 px-2" title="Aceptar desglose FIFO de partidas" onclick="aceptarFifo(${d.fila})"><i class="bi bi-diagram-3"></i> FIFO</button>` : '';
                const quitar = `<button class="btn btn-outline-danger btn-sm p-1 px-2" title="Quitar fila" onclick="quitarFila(${d.fila})"><i class="bi bi-x-lg"></i></button>`;
                acciones = `<div class="d-flex gap-1 justify-content-end">${fifo}${ajustar}${editar}${quitar}</div>`;
            }

            return `
                <tr class="${clase}">
                    <td class="text-muted fw-bold">${d.fila}</td>
                    <td class="font-monospace">${esc(d.deposito)}</td>
                    <td><strong>${esc(d.articulo)}</strong></td>
                    <td class="text-end fw-bold ${d.cantidad < 0 ? 'text-danger' : 'text-success'}">${d.cantidad > 0 ? '+' : ''}${fmt(d.cantidad)}</td>
                    <td class="font-monospace">${esc(d.partida)}</td>
                    <td class="text-end">${fmt(d.stock_actual)}</td>
                    <td class="text-end ${d.stock_resultante < 0 ? 'text-danger fw-bold' : ''}">${fmt(d.stock_resultante)}</td>
                    <td>${badge}</td>
                    <td class="resultado-txt">${esc(d.resultado)}${sugerencia}</td>
                    <td>${acciones}</td>
                </tr>`;
        }

        function filaEdicionHtml(d) {
            const opts = Object.keys(DEPOSITOS).map(k => `<option value="${k}" ${k === d.deposito ? 'selected' : ''}>${k}</option>`).join('');
            return `
                <tr class="row-editing">
                    <td class="text-muted fw-bold">${d.fila}</td>
                    <td><select id="ed-dep" class="form-select form-select-sm input-mini">${opts}</select></td>
                    <td><input id="ed-art" class="form-control form-control-sm font-monospace" value="${esc(d.articulo)}" maxlength="15"></td>
                    <td><input id="ed-cant" type="number" step="1" class="form-control form-control-sm text-end input-mini" value="${d.cantidad}"></td>
                    <td><input id="ed-partida" class="form-control form-control-sm font-monospace input-mini" value="${esc(d.partida)}" maxlength="25"></td>
                    <td class="text-end">${fmt(d.stock_actual)}</td>
                    <td class="text-end">${d.disponible !== null && d.cantidad < 0 ? `<span class="small text-muted">disp. ${fmt(d.disponible)}</span>` : ''}</td>
                    <td><span class="badge bg-primary">Editando</span></td>
                    <td class="resultado-txt">${esc(d.resultado)}</td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <button class="btn btn-primary btn-sm p-1 px-2" onclick="guardarFila(${d.fila})"><i class="bi bi-check-lg"></i> Guardar</button>
                            <button class="btn btn-outline-secondary btn-sm p-1 px-2" onclick="cancelarEdicion()"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </td>
                </tr>`;
        }

        window.editarFila = (fila) => { filaEditando = fila; renderGrilla(); document.getElementById('ed-cant')?.focus(); };
        window.cancelarEdicion = () => { filaEditando = null; renderGrilla(); };

        async function accionLote(accion, body, titulo, textoOk = null) {
            cargando(titulo, 'Revalidando stock y partidas');
            try {
                const data = await api(accion, Object.assign({ lote_id: lote.id }, body));
                Swal.close();
                aplicarLote(data);
                if (textoOk) {
                    Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: textoOk });
                }
                return data;
            } catch (err) {
                mostrarError('No se pudo aplicar el cambio', err);
                return null;
            }
        }

        window.guardarFila = (fila) => {
            accionLote('actualizar_fila', {
                fila,
                deposito: document.getElementById('ed-dep').value,
                articulo: document.getElementById('ed-art').value,
                cantidad: document.getElementById('ed-cant').value,
                partida: document.getElementById('ed-partida').value
            }, 'Guardando fila...', 'Fila actualizada y revalidada');
        };

        window.quitarFila = async (fila) => {
            const r = await Swal.fire({ title: `¿Quitar la fila ${fila}?`, text: 'Si es una baja sin stock, acordate de pasarla al listado de faltantes.', icon: 'question', showCancelButton: true, confirmButtonText: 'Quitar', cancelButtonText: 'Cancelar', confirmButtonColor: '#ef4444' });
            if (r.isConfirmed) accionLote('eliminar_filas', { filas: [fila] }, 'Quitando fila...', 'Fila quitada');
        };

        window.ajustarStock = async (fila) => {
            const d = detalle.find(x => x.fila === fila);
            const r = await Swal.fire({
                title: 'Ajustar a stock disponible',
                html: `La baja de <b>${esc(d.articulo)}</b> en dep. ${esc(d.deposito)} pasa de <b>${fmt(-d.cantidad)}</b> a <b>${fmt(Math.floor(d.disponible))}</b> unidades.<br><span class="small text-muted">La diferencia (${fmt(-d.cantidad - Math.floor(d.disponible))}) queda como faltante para el próximo inventario.</span>`,
                icon: 'question', showCancelButton: true, confirmButtonText: 'Ajustar', cancelButtonText: 'Cancelar'
            });
            if (r.isConfirmed) accionLote('ajustar_stock', { fila }, 'Ajustando...', 'Cantidad ajustada al disponible');
        };

        window.aceptarFifo = (fila) => accionLote('aceptar_fifo', { fila }, 'Aplicando desglose FIFO...', 'Desglose de partidas aplicado');

        document.querySelectorAll('#filtros-estado button').forEach(b => b.addEventListener('click', () => {
            filtroEstado = b.dataset.filtro; pagLote = 1; filaEditando = null; renderLote();
        }));
        document.getElementById('buscar-articulo').addEventListener('input', () => { pagLote = 1; renderGrilla(); });
        document.getElementById('chk-acepta').addEventListener('change', renderLote);

        document.getElementById('btn-revalidar').addEventListener('click', async () => {
            const data = await accionLote('validar', {}, 'Revalidando lote...');
            if (data) avisoValidacion(data.resumen);
        });

        document.getElementById('btn-exportar-errores').addEventListener('click', () => {
            const errores = detalle.filter(d => d.estado === 'ERROR');
            if (!errores.length) return;
            // Primeras 4 columnas = formato de carga; Resultado queda a la derecha como referencia
            const aoa = errores.map(d => [d.deposito, d.articulo, d.cantidad, d.partida || '', d.resultado]);
            const ws = XLSX.utils.aoa_to_sheet(aoa);
            ws['!cols'] = [{ wch: 6 }, { wch: 18 }, { wch: 10 }, { wch: 14 }, { wch: 80 }];
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Errores');
            XLSX.writeFile(wb, `Errores_Ajuste_${lote.id.substring(0, 8)}.xlsx`);
        });

        document.getElementById('btn-reemplazar').addEventListener('click', async () => {
            const r = await Swal.fire({
                title: 'Reemplazar filas con error',
                html: `Se van a <b>borrar las ${fmt(detalle.filter(d => d.estado === 'ERROR').length)} filas con error</b> del lote y cargar las del archivo corregido.<br><span class="small text-muted">Usá el Excel exportado: se toman las columnas Depósito, Artículo, Cantidad y Partida; la columna del error se ignora. Las filas que no quieras ajustar, borralas del archivo.</span>`,
                icon: 'info', showCancelButton: true, confirmButtonText: 'Elegir archivo', cancelButtonText: 'Cancelar'
            });
            if (r.isConfirmed) document.getElementById('archivo-correcciones').click();
        });

        document.getElementById('archivo-correcciones').addEventListener('change', async (e) => {
            const file = e.target.files?.[0];
            e.target.value = '';
            if (!file) return;
            let filas;
            try {
                filas = (await leerArchivo(file));
            } catch (err) {
                Swal.fire('Error', 'No se pudo leer el archivo.', 'error');
                return;
            }
            const conError = filas.filter(f => f.error);
            if (conError.length) {
                Swal.fire({ title: 'El archivo tiene filas inválidas', html: conError.slice(0, 20).map(f => `${esc(f.art)}: ${esc(f.error)}`).join('<br>'), icon: 'error' });
                return;
            }
            const data = await accionLote('reemplazar_errores', { filas: filas.map(f => [f.dep, f.art, f.cant, f.partida]) }, 'Reemplazando filas...');
            if (data) {
                Swal.fire('Filas reemplazadas', `Se quitaron ${fmt(data.cambio.borradas)} filas con error y se cargaron ${fmt(data.cambio.insertadas)}. Resultado: ${fmt(data.resumen.errores)} errores, ${fmt(data.resumen.advertencias)} advertencias.`, data.resumen.errores ? 'warning' : 'success');
            }
        });

        document.getElementById('btn-descartar').addEventListener('click', async () => {
            const r = await Swal.fire({ title: '¿Descartar el lote?', text: 'Se borra el lote completo. No se movió stock.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Descartar', cancelButtonText: 'Cancelar', confirmButtonColor: '#ef4444' });
            if (!r.isConfirmed) return;
            try {
                await api('descartar', { lote_id: lote.id });
                nuevoLote();
            } catch (err) {
                mostrarError('No se pudo descartar', err);
            }
        });

        document.getElementById('btn-nuevo').addEventListener('click', nuevoLote);

        function nuevoLote() {
            lote = null; detalle = [];
            const url = new URL(window.location.href);
            url.searchParams.delete('lote');
            window.history.replaceState({}, '', url);
            document.getElementById('chk-acepta').checked = false;
            document.getElementById('seccion-lote').style.display = 'none';
            document.getElementById('seccion-carga').style.display = 'block';
        }

        // ---------------------------------------------------------------
        // Ejecución por bloques: cada llamada aplica un bloque chico de artículos en una
        // transacción corta, con pausa entre bloques para no frenar a otros procesos de Tango.
        // ---------------------------------------------------------------
        const PAUSA_ENTRE_BLOQUES_MS = 1500;
        const ESPERA_TANGO_OCUPADO_MS = 10000;
        const MAX_REINTENTOS_OCUPADO = 10;
        let detenerEjecucion = false;

        const esperar = (ms) => new Promise(r => setTimeout(r, ms));

        function mostrarProgreso(aplicadas, total, texto) {
            const pct = total ? Math.round(aplicadas * 100 / total) : 0;
            const html = `
                <div class="progress mb-2" style="height: 22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:${pct}%">${pct}%</div>
                </div>
                <div class="small">${fmt(aplicadas)} de ${fmt(total)} filas aplicadas en Tango</div>
                <div class="small text-muted mt-1" id="txt-progreso">${esc(texto)}</div>`;

            if (detenerEjecucion) {
                cargando('Deteniendo...', 'Terminando el bloque en curso');
                return;
            }
            if (Swal.isVisible() && document.getElementById('txt-progreso')) {
                Swal.update({ html });
                return;
            }
            Swal.fire({
                title: 'Ejecutando ajuste por bloques',
                html,
                allowOutsideClick: false,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Detener después de este bloque'
            }).then(r => { if (r.dismiss === Swal.DismissReason.cancel) detenerEjecucion = true; });
        }

        async function recargarLote() {
            try { aplicarLote(await api('obtener', null, '&lote_id=' + encodeURIComponent(lote.id)), true); } catch (e) { /* sin conexión */ }
        }

        document.getElementById('btn-ejecutar').addEventListener('click', async () => {
            const pendientes = detalle.filter(d => !d.ejecutada);
            const totales = totalesPorDeposito(pendientes, d => d.deposito, d => d.cantidad);
            const r = await Swal.fire({
                title: detalle.some(d => d.ejecutada) ? '¿Continuar la ejecución en Tango?' : '¿Ejecutar el ajuste en Tango?',
                html: `<div class="text-start small mb-2">${esc(lote.leyenda)}</div>
                       <table class="table table-sm small text-start"><tbody>
                       ${totales.map(t => `<tr><td>${t.tipo} dep. ${esc(t.dep)}</td><td class="text-end">${fmt(t.filas)} art.</td><td class="text-end fw-bold">${fmt(t.unidades)} u.</td></tr>`).join('')}
                       </tbody></table>
                       <div class="small text-muted mb-1">Se aplica en bloques chicos con pausas, para no frenar a los demás procesos de Tango. Podés detenerlo y continuar después.</div>
                       <div class="small text-danger">Se generan comprobantes de ajuste reales.</div>`,
                icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, ejecutar', cancelButtonText: 'Cancelar', confirmButtonColor: '#10b981'
            });
            if (!r.isConfirmed) return;

            detenerEjecucion = false;
            const total = detalle.length;
            let aplicadas = detalle.filter(d => d.ejecutada).length;
            let reintentos = 0;
            mostrarProgreso(aplicadas, total, 'Iniciando...');

            try {
                while (!detenerEjecucion) {
                    const data = await api('ejecutar', { lote_id: lote.id, acepta_advertencias: document.getElementById('chk-acepta').checked });
                    const ej = data.ejecucion;

                    if (ej.tango_ocupado) {
                        reintentos++;
                        if (reintentos > MAX_REINTENTOS_OCUPADO) {
                            throw new Error('Tango siguió ocupado con otros procesos. La ejecución quedó pausada: lo aplicado hasta ahora queda aplicado y podés continuar más tarde con "Continuar ejecución".');
                        }
                        mostrarProgreso(aplicadas, total, `Tango está ocupado con otro proceso. Reintento ${reintentos} de ${MAX_REINTENTOS_OCUPADO} en ${ESPERA_TANGO_OCUPADO_MS / 1000} s...`);
                        await esperar(ESPERA_TANGO_OCUPADO_MS);
                        continue;
                    }
                    reintentos = 0;

                    if (!ej.ok) {
                        // El stock cambió desde la validación: ese bloque no se aplicó y sus filas quedaron marcadas
                        aplicarLote(data, true);
                        Swal.fire('Bloque no aplicado', `${esc(ej.mensaje)}<br><span class="small text-muted">Lo aplicado antes queda aplicado. Corregí las filas marcadas en rojo, revalidá y continuá la ejecución.</span>`, 'warning');
                        return;
                    }

                    aplicadas = total - ej.filas_pendientes;

                    if (ej.terminado) {
                        aplicarLote(data, true);
                        Swal.fire('Ajuste aplicado', `${fmt(total)} filas aplicadas en Tango.${ej.comprobantes ? '<br>Comprobantes: <b>' + esc(ej.comprobantes) + '</b>' : ''}`, 'success');
                        return;
                    }

                    mostrarProgreso(aplicadas, total, `Bloque aplicado (${fmt(ej.filas_procesadas)} filas). Pausa breve para no frenar a otros procesos...`);
                    await esperar(PAUSA_ENTRE_BLOQUES_MS);
                }

                await recargarLote();
                Swal.fire('Ejecución pausada', `Se aplicaron ${fmt(aplicadas)} de ${fmt(total)} filas. Podés continuar cuando quieras con "Continuar ejecución".`, 'info');
            } catch (err) {
                await recargarLote();
                mostrarError('La ejecución se detuvo', err);
            }
        });

        // ---------------------------------------------------------------
        // Historial / retomar lote
        // ---------------------------------------------------------------
        const modalHistorial = new bootstrap.Modal(document.getElementById('modalHistorial'));

        async function cargarHistorial() {
            const tbody = document.getElementById('tbody-historial');
            tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-3">Cargando...</td></tr>';
            try {
                const pend = document.getElementById('chk-pendientes').checked ? '&pendientes=1' : '';
                const data = await api('historial', null, pend);
                if (!data.lotes.length) {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-3">No hay lotes.</td></tr>';
                    return;
                }
                tbody.innerHTML = data.lotes.map(l => `
                    <tr>
                        <td class="text-nowrap">${esc(l.fecha_alta)}</td>
                        <td>${esc(l.usuario)}</td>
                        <td>${esc(l.leyenda)}</td>
                        <td><span class="badge ${BADGE_LOTE[l.estado] || 'bg-secondary'}">${esc(TXT_LOTE[l.estado] || l.estado)}</span></td>
                        <td class="text-end">${fmt(l.filas)}${l.estado !== 'EJECUTADO' && l.filas_ejecutadas ? `<div class="small text-muted">${fmt(l.filas_ejecutadas)} aplicadas</div>` : ''}</td>
                        <td class="text-end text-danger">${fmt(l.unidades_baja)}</td>
                        <td class="text-end text-success">${fmt(l.unidades_alta)}</td>
                        <td class="text-end">${l.filas_error ? `<span class="badge bg-danger">${fmt(l.filas_error)}</span>` : ''}</td>
                        <td class="small">${esc(l.comprobantes)}</td>
                        <td><button class="btn btn-outline-primary btn-sm" onclick="abrirLote('${esc(l.id)}')">${l.estado === 'EJECUTADO' ? 'Ver' : 'Retomar'}</button></td>
                    </tr>`).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-danger">${err.message}</td></tr>`;
            }
        }

        document.getElementById('btn-historial').addEventListener('click', () => { modalHistorial.show(); cargarHistorial(); });
        document.getElementById('chk-pendientes').addEventListener('change', cargarHistorial);

        window.abrirLote = async (id) => {
            modalHistorial.hide();
            cargando('Abriendo lote...');
            try {
                const data = await api('obtener', null, '&lote_id=' + encodeURIComponent(id));
                Swal.close();
                aplicarLote(data, true);
            } catch (err) {
                mostrarError('No se pudo abrir el lote', err);
            }
        };

        // Retomar el lote de la URL al recargar la página
        const loteUrl = new URLSearchParams(window.location.search).get('lote');
        if (loteUrl) abrirLote(loteUrl);
    </script>
</body>
</html>
