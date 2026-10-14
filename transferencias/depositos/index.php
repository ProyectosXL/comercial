<?php
if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}

require_once __DIR__ . '/Class/TransferenciasDepositos.php';

Auth::exigir();

$service = new TransferenciasDepositos();
$depositos = TransferenciasDepositos::$DEPOSITOS_HABILITADOS;
$ubicaciones = $service->ubicacionesHabilitadas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferencias entre Depósitos · XL Extra Large</title>
    <link rel="shortcut icon" href="/sistemas/assets/css/icono.jpg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- SheetJS (XLSX reader) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <style>
        :root {
            --primary: #0066cc;
            --primary-dark: #004d99;
            --primary-light: #e6f0fa;
            --secondary: #64748b;
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
            margin: 0;
            padding: 0;
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
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .breadcrumb {
            margin: 0;
            font-size: 0.85rem;
        }

        .app-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            margin-bottom: 1.5rem;
        }

        .nav-pills .nav-link {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.65rem 1.25rem;
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0,102,204,0.25);
        }

        .scanner-box {
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 1.25rem;
            transition: border-color 0.2s;
        }

        .scanner-box:focus-within {
            border-color: var(--primary);
            background: #f8fafc;
        }

        .badge-deposito {
            font-weight: 700;
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        .badge-dep-04 { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-dep-06 { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-dep-10 { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-dep-20 { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }

        .table-custom {
            font-size: 0.9rem;
        }

        .table-custom th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: none;
        }

        .row-recodificacion {
            background-color: #f0fdf4 !important;
        }

        .row-error {
            background-color: #fef2f2 !important;
            border-left: 4px solid var(--danger-color);
        }

        .sugerencia-btn {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin: 0.15rem;
        }
    </style>
</head>
<body>

    <!-- Header Principal -->
    <header class="main-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/centralizadores/comer/index.php" class="text-decoration-none text-muted"><i class="bi bi-grid-fill"></i> COMER</a></li>
                        <li class="breadcrumb-item text-muted">Supervisión y Transferencias</li>
                        <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Transferencias entre Depósitos</li>
                    </ol>
                </nav>
                <h1 class="page-title mt-1">
                    <i class="bi bi-arrow-left-right text-primary"></i> Transferencias entre Depósitos
                </h1>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-info d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                    <i class="bi bi-question-circle"></i> Ayuda e Instructivo
                </button>
                <a href="historial.php" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                    <i class="bi bi-clock-history"></i> Historial
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid py-4 px-4">

        <!-- Banner informativo depósitos habilitados -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 bg-white p-3 rounded-3 border mb-4 shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-buildings fs-5 text-primary"></i>
                <span class="fw-bold text-dark">Depósitos Habilitados:</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($depositos as $cod => $nom): ?>
                    <span class="badge-deposito badge-dep-<?= $cod ?>">
                        <?= $cod ?> · <?= htmlspecialchars($nom) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pestañas de Carga -->
        <div class="app-card p-0">
            <div class="border-bottom p-3 bg-white rounded-top-4">
                <ul class="nav nav-pills gap-2" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-flex align-items-center gap-2" id="tab-escaneo" data-bs-toggle="pill" data-bs-target="#panel-escaneo" type="button" role="tab">
                            <i class="bi bi-upc-scan fs-5"></i> 1. Modo Escaneo (Rápido)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2" id="tab-manual" data-bs-toggle="pill" data-bs-target="#panel-manual" type="button" role="tab">
                            <i class="bi bi-file-earmark-spreadsheet fs-5"></i> 2. Pegar desde Excel / Subir XLSX
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content p-4" id="pills-tabContent">

                <!-- TAB 1: MODO ESCANEO -->
                <div class="tab-pane fade show active" id="panel-escaneo" role="tabpanel">
                    <div class="row g-4">
                        <!-- Columna 1: Escaneo -->
                        <div class="col-lg-4">
                            <div class="scanner-box">
                                <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-upc-scan text-primary"></i> Escanear Artículo Origen
                                </label>
                                <div class="input-group input-group-lg mb-2">
                                    <input type="text" id="scan-articulo" class="form-control fw-bold" placeholder="Escanear o tipear..." autocomplete="off" autofocus>
                                    <button class="btn btn-primary" type="button" id="btn-scan-buscar">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <small class="text-muted d-block">
                                    El lector envía Enter automático. Se valida contra Tango y WMS.
                                </small>
                                <div id="scan-status" class="mt-2"></div>
                            </div>
                        </div>

                        <!-- Columna 2: Origen (Saldos) -->
                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 bg-white h-100">
                                <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-geo-alt-fill text-danger"></i> Ubicación Origen (con saldo)
                                </label>
                                <div id="scan-origen-container">
                                    <p class="text-muted small mb-0">Escaneá un artículo para consultar sus saldos disponibles por ubicación.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Columna 3: Destino -->
                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 bg-white h-100">
                                <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-box-arrow-in-right text-success"></i> Destino
                                </label>

                                <div class="row g-2 mb-2">
                                    <div class="col-5">
                                        <label class="form-label small text-muted mb-1">Depósito</label>
                                        <select id="scan-dep-destino" class="form-select form-select-sm">
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($depositos as $cod => $nom): ?>
                                                <option value="<?= $cod ?>"><?= $cod ?> · <?= htmlspecialchars($nom) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-7">
                                        <label class="form-label small text-muted mb-1">Ubicación</label>
                                        <select id="scan-ubic-destino" class="form-select form-select-sm" disabled>
                                            <option value="">Elegí depósito primero</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-muted mb-1">Artículo Destino</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="scan-art-destino" class="form-control" placeholder="Mismo o nuevo código..." autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" id="btn-igual-origen" title="Copiar mismo artículo">
                                            = Origen
                                        </button>
                                    </div>
                                </div>

                                <button type="button" id="btn-agregar-escaneo" class="btn btn-success w-100 fw-bold d-flex align-items-center justify-content-center gap-2" disabled>
                                    <i class="bi bi-plus-circle-fill"></i> Agregar a la Lista
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: MODO MANUAL EXCEL -->
                <div class="tab-pane fade" id="panel-manual" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <label class="form-label fw-bold text-dark">Pegar filas copiadas de Excel (8 columnas separadas por tabulación):</label>
                            <textarea id="datos-pegados" class="form-control font-monospace small" rows="7" placeholder="DepOrigen&#9;UbicOrigen&#9;ArtOrigen&#9;CantBaja&#9;DepDestino&#9;UbicDestino&#9;ArtDestino&#9;CantAlta"></textarea>
                            <div class="d-flex gap-2 mt-3">
                                <button type="button" id="btn-parsear-texto" class="btn btn-primary d-flex align-items-center gap-2">
                                    <i class="bi bi-table"></i> Procesar y Previsualizar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('datos-pegados').value = '';">
                                    <i class="bi bi-trash"></i> Limpiar
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 bg-white h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <h6 class="fw-bold mb-2"><i class="bi bi-file-earmark-excel text-success"></i> Subir Archivo Excel</h6>
                                    <p class="text-muted small mb-3">Subí un archivo <code>.xlsx</code> con las 8 columnas requeridas sin encabezados.</p>
                                    <input type="file" id="archivo-xlsx" class="form-control form-control-sm mb-3" accept=".xlsx">
                                </div>

                                <div class="d-flex flex-column gap-2">
                                    <button type="button" id="btn-cargar-archivo" class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-upload"></i> Cargar y Previsualizar
                                    </button>
                                    <a href="assets/PlantillaTransferenciasDepositos.xlsx" download="PlantillaTransferenciasDepositos.xlsx" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-download"></i> Descargar Plantilla Excel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- SECCIÓN VISTA PREVIA Y ACCIONES -->
        <div id="seccion-lista" class="app-card" style="display:none;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="fw-bold m-0"><i class="bi bi-list-check text-primary"></i> Filas para Transferencia / Recodificación</h5>
                    <span id="badge-contador-filas" class="badge bg-primary rounded-pill px-3 py-2">0 filas</span>
                    <span id="badge-total-unidades" class="badge bg-secondary rounded-pill px-3 py-2">0 unidades</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="btn-validar" class="btn btn-primary fw-bold d-flex align-items-center gap-2 px-3 py-2">
                        <i class="bi bi-check2-circle fs-5"></i> Validar Lote
                    </button>
                    <button type="button" id="btn-ejecutar" class="btn btn-success fw-bold d-flex align-items-center gap-2 px-3 py-2" style="display:none;">
                        <i class="bi bi-play-circle fs-5"></i> Ejecutar Transferencia
                    </button>
                    <button type="button" id="btn-vaciar-lista" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i> Vaciar
                    </button>
                </div>
            </div>

            <div id="contenedor-alertas" class="mb-3"></div>

            <div class="table-responsive border rounded-3">
                <table class="table table-hover table-custom align-middle mb-0" id="tabla-transferencias">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th colspan="4" class="text-center bg-light border-end">ORIGEN (BAJA)</th>
                            <th colspan="4" class="text-center bg-light border-end">DESTINO (ALTA)</th>
                            <th class="text-center" style="width: 100px;">TIPO</th>
                            <th class="text-center" style="width: 50px;">ACCIÓN</th>
                        </tr>
                        <tr>
                            <th>#</th>
                            <th>DEP</th>
                            <th>UBICACIÓN</th>
                            <th>ARTÍCULO</th>
                            <th class="border-end text-center">CANT</th>
                            <th>DEP</th>
                            <th>UBICACIÓN</th>
                            <th>ARTÍCULO</th>
                            <th class="border-end text-center">CANT</th>
                            <th class="text-center">OPERACIÓN</th>
                            <th class="text-center">QUITAR</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-transferencias">
                        <!-- Filas dinámicas -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal de Ayuda -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-question-circle text-primary"></i> Guía de Uso: Transferencias entre Depósitos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-start gap-2">
                        <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
                        <div>
                            <strong>¿Qué hace esta herramienta?</strong><br>
                            Permite realizar <strong>transferencias simples de stock</strong> (mismo código) o <strong>recodificaciones</strong> (cambio de código) entre los depósitos habilitados: <code>04 OUTLET</code>, <code>06 FALLAS</code>, <code>10 RECODIFICACIONES</code> y <code>20 ARREGLOS PRODUCCION</code>.
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4"><i class="bi bi-upc-scan text-primary"></i> 1. Modo Escaneo (Recomendado)</h6>
                    <ol class="small text-muted">
                        <li>Escaneá el código del artículo en el cuadro de búsqueda.</li>
                        <li>El sistema consultará en vivo el saldo por ubicación en el WMS.</li>
                        <li>Seleccioná la ubicación de origen si está en varias.</li>
                        <li>Elegí el depósito y ubicación de destino, e indicá el artículo de destino (o clic en <strong>= Origen</strong>).</li>
                        <li>Presioná <strong>Agregar a la Lista</strong>. Cada escaneo suma 1 unidad automáticamente.</li>
                    </ol>

                    <h6 class="fw-bold mt-4"><i class="bi bi-file-earmark-spreadsheet text-success"></i> 2. Modo Pegar desde Excel</h6>
                    <p class="small text-muted mb-2">Armá en Excel una tabla con 8 columnas <strong>sin encabezados</strong>:</p>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered text-center small">
                            <thead class="table-light">
                                <tr>
                                    <th>1. DepOrigen</th>
                                    <th>2. UbicOrigen</th>
                                    <th>3. ArtOrigen</th>
                                    <th>4. CantBaja</th>
                                    <th>5. DepDestino</th>
                                    <th>6. UbicDestino</th>
                                    <th>7. ArtDestino</th>
                                    <th>8. CantAlta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>06</td><td>R1001A01</td><td>X01234</td><td>2</td>
                                    <td>04</td><td>P0401001</td><td>X01234</td><td>2</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold mt-4"><i class="bi bi-shield-check text-primary"></i> 3. Validación y Ejecución</h6>
                    <p class="small text-muted">
                        Al presionar <strong>Validar</strong>, el sistema verifica que haya stock disponible en cada ubicación y depósito, que los artículos existan y que no haya discrepancias. Si todo es correcto, se habilitará el botón <strong>Ejecutar Transferencia</strong> para mover el stock de forma definitiva.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const ubicacionesData = <?= json_encode($ubicaciones) ?>;
        const depositosHabilitados = <?= json_encode($depositos) ?>;

        let listaFilas = [];
        let articuloEscaneadoActual = null;
        let origenSeleccionado = null;

        // Inicializar selector de ubicaciones según depósito destino
        document.getElementById('scan-dep-destino').addEventListener('change', function() {
            const dep = this.value;
            const ubicSelect = document.getElementById('scan-ubic-destino');
            ubicSelect.innerHTML = '<option value="">Seleccionar ubicación...</option>';

            if (!dep) {
                ubicSelect.disabled = true;
                actualizarBotonAgregar();
                return;
            }

            const filtradas = ubicacionesData.filter(u => u.deposito === dep);
            filtradas.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.ubicacion;
                opt.textContent = `${u.ubicacion} (${u.nombre})`;
                ubicSelect.appendChild(opt);
            });
            ubicSelect.disabled = false;
            actualizarBotonAgregar();
        });

        document.getElementById('scan-ubic-destino').addEventListener('change', actualizarBotonAgregar);
        document.getElementById('scan-art-destino').addEventListener('input', actualizarBotonAgregar);

        document.getElementById('btn-igual-origen').addEventListener('click', function() {
            if (articuloEscaneadoActual) {
                document.getElementById('scan-art-destino').value = articuloEscaneadoActual.articulo;
                actualizarBotonAgregar();
            }
        });

        // Búsqueda de artículo por escáner
        const inputScan = document.getElementById('scan-articulo');
        inputScan.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarArticuloEscaneado();
            }
        });
        document.getElementById('btn-scan-buscar').addEventListener('click', buscarArticuloEscaneado);

        async function buscarArticuloEscaneado() {
            const codigo = inputScan.value.trim();
            if (!codigo) return;

            const statusDiv = document.getElementById('scan-status');
            const origenDiv = document.getElementById('scan-origen-container');

            statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Consultando artículo...';

            try {
                const res = await fetch(`controller/transferencias_ajax.php?accion=buscar_articulo&codigo=${encodeURIComponent(codigo)}`);
                const data = await res.json();

                if (!data.encontrado) {
                    statusDiv.innerHTML = `<div class="alert alert-danger py-2 small mb-0">${data.mensaje || 'Artículo no encontrado'}</div>`;
                    origenDiv.innerHTML = '<p class="text-muted small mb-0">Sin saldo disponible.</p>';
                    articuloEscaneadoActual = null;
                    origenSeleccionado = null;
                    actualizarBotonAgregar();
                    return;
                }

                articuloEscaneadoActual = data;
                statusDiv.innerHTML = `
                    <div class="alert alert-success py-2 small mb-0">
                        <strong>${data.articulo}</strong> &mdash; ${data.descripcion}
                    </div>`;

                // Renderizar ubicaciones con saldo
                let htmlUbic = '<div class="d-flex flex-column gap-2">';
                data.ubicaciones.forEach((ub, idx) => {
                    const isChecked = (idx === 0) ? 'checked' : '';
                    if (idx === 0) origenSeleccionado = ub;
                    htmlUbic += `
                        <label class="border rounded p-2 d-flex align-items-center justify-content-between cursor-pointer">
                            <div class="d-flex align-items-center gap-2">
                                <input type="radio" name="radio-origen" value="${ub.deposito}|${ub.ubicacion}" ${isChecked} onchange="seleccionarOrigen('${ub.deposito}', '${ub.ubicacion}', ${ub.saldo})">
                                <div>
                                    <span class="badge-deposito badge-dep-${ub.deposito}">${ub.deposito}</span>
                                    <strong class="ms-1">${ub.ubicacion}</strong>
                                    <small class="text-muted d-block">${ub.nombre}</small>
                                </div>
                            </div>
                            <span class="badge bg-primary rounded-pill">Saldo: ${ub.saldo}</span>
                        </label>`;
                });
                htmlUbic += '</div>';
                origenDiv.innerHTML = htmlUbic;

                // Sugerir mismo artículo en destino si está vacío
                const inputDestino = document.getElementById('scan-art-destino');
                if (!inputDestino.value.trim()) {
                    inputDestino.value = data.articulo;
                }

                actualizarBotonAgregar();

            } catch (err) {
                statusDiv.innerHTML = `<div class="alert alert-danger py-2 small mb-0">Error de conexión al consultar artículo.</div>`;
            }
        }

        window.seleccionarOrigen = function(dep, ubic, saldo) {
            origenSeleccionado = { deposito: dep, ubicacion: ubic, saldo: saldo };
            actualizarBotonAgregar();
        };

        function actualizarBotonAgregar() {
            const depDest = document.getElementById('scan-dep-destino').value;
            const ubicDest = document.getElementById('scan-ubic-destino').value;
            const artDest = document.getElementById('scan-art-destino').value.trim();

            const isValid = (articuloEscaneadoActual && origenSeleccionado && depDest && ubicDest && artDest);
            document.getElementById('btn-agregar-escaneo').disabled = !isValid;
        }

        document.getElementById('btn-agregar-escaneo').addEventListener('click', function() {
            if (!articuloEscaneadoActual || !origenSeleccionado) return;

            const depO = origenSeleccionado.deposito;
            const ubicO = origenSeleccionado.ubicacion;
            const artO = articuloEscaneadoActual.articulo;

            const depD = document.getElementById('scan-dep-destino').value;
            const ubicD = document.getElementById('scan-ubic-destino').value;
            const artD = document.getElementById('scan-art-destino').value.trim().toUpperCase();

            // Buscar si ya existe una fila idéntica para sumar cantidad
            const existente = listaFilas.find(f => 
                f.dep_o === depO && f.ubic_o === ubicO && f.art_o === artO &&
                f.dep_d === depD && f.ubic_d === ubicD && f.art_d === artD
            );

            if (existente) {
                existente.cant_baja += 1;
                existente.cant_alta += 1;
            } else {
                listaFilas.push({
                    dep_o: depO,
                    ubic_o: ubicO,
                    art_o: artO,
                    cant_baja: 1,
                    dep_d: depD,
                    ubic_d: ubicD,
                    art_d: artD,
                    cant_alta: 1,
                    error: null,
                    sugerencias: []
                });
            }

            renderTabla();

            // Limpiar input scan para el siguiente
            inputScan.value = '';
            inputScan.focus();
            document.getElementById('scan-status').innerHTML = '<div class="text-success small fw-bold"><i class="bi bi-check-circle"></i> Fila agregada a la lista.</div>';
        });

        // Parsear texto pegado de Excel
        document.getElementById('btn-parsear-texto').addEventListener('click', function() {
            const raw = document.getElementById('datos-pegados').value.trim();
            if (!raw) {
                Swal.fire('Atención', 'Pegá filas copiadas desde Excel antes de continuar.', 'warning');
                return;
            }

            const lineas = raw.split(/\r?\n/);
            const nuevas = [];
            const erroresParse = [];

            lineas.forEach((lin, idx) => {
                const cols = lin.split('\t').map(c => c.trim());
                if (cols.length < 8) {
                    erroresParse.push(`Línea ${idx + 1}: esperadas 8 columnas, encontradas ${cols.length}`);
                    return;
                }

                const depO = cols[0].padStart(2, '0');
                const ubicO = cols[1].toUpperCase();
                const artO = cols[2].toUpperCase();
                const cantBaja = parseInt(cols[3]) || 0;

                const depD = cols[4].padStart(2, '0');
                const ubicD = cols[5].toUpperCase();
                const artD = cols[6].toUpperCase();
                const cantAlta = parseInt(cols[7]) || 0;

                nuevas.push({
                    dep_o: depO, ubic_o: ubicO, art_o: artO, cant_baja: cantBaja,
                    dep_d: depD, ubic_d: ubicD, art_d: artD, cant_alta: cantAlta,
                    error: null, sugerencias: []
                });
            });

            if (erroresParse.length > 0) {
                Swal.fire('Error en formato', erroresParse.slice(0, 5).join('<br>') + (erroresParse.length > 5 ? '<br>...' : ''), 'error');
                return;
            }

            listaFilas = nuevas;
            renderTabla();
        });

        // Cargar XLSX con SheetJS
        document.getElementById('btn-cargar-archivo').addEventListener('click', function() {
            const fileInput = document.getElementById('archivo-xlsx');
            if (!fileInput.files || fileInput.files.length === 0) {
                Swal.fire('Atención', 'Seleccioná un archivo .xlsx primero.', 'warning');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1, raw: false });

                const nuevas = [];
                rows.forEach(r => {
                    if (!r || r.length < 8) return;
                    if (r.every(c => !c || String(c).trim() === '')) return;

                    const depO = String(r[0] || '').trim().padStart(2, '0');
                    const ubicO = String(r[1] || '').trim().toUpperCase();
                    const artO = String(r[2] || '').trim().toUpperCase();
                    const cantB = parseInt(r[3]) || 0;

                    const depD = String(r[4] || '').trim().padStart(2, '0');
                    const ubicD = String(r[5] || '').trim().toUpperCase();
                    const artD = String(r[6] || '').trim().toUpperCase();
                    const cantA = parseInt(r[7]) || 0;

                    nuevas.push({
                        dep_o: depO, ubic_o: ubicO, art_o: artO, cant_baja: cantB,
                        dep_d: depD, ubic_d: ubicD, art_d: artD, cant_alta: cantA,
                        error: null, sugerencias: []
                    });
                });

                if (nuevas.length === 0) {
                    Swal.fire('Error', 'No se pudieron extraer filas válidas del archivo.', 'error');
                    return;
                }

                listaFilas = nuevas;
                renderTabla();
                Swal.fire('Éxito', `Se cargaron ${nuevas.length} filas desde el Excel.`, 'success');
            };
            reader.readAsArrayBuffer(fileInput.files[0]);
        });

        function renderTabla() {
            const sec = document.getElementById('seccion-lista');
            const tbody = document.getElementById('tbody-transferencias');
            const badgeCount = document.getElementById('badge-contador-filas');
            const badgeUnits = document.getElementById('badge-total-unidades');

            if (listaFilas.length === 0) {
                sec.style.display = 'none';
                return;
            }

            sec.style.display = 'block';
            tbody.innerHTML = '';

            let totalUnidades = 0;

            listaFilas.forEach((f, idx) => {
                totalUnidades += f.cant_baja;
                const esRecod = (f.art_o !== f.art_d);
                const trClass = f.error ? 'row-error' : (esRecod ? 'row-recodificacion' : '');

                let sugerenciasHtml = '';
                if (f.sugerencias && f.sugerencias.length > 0) {
                    sugerenciasHtml = '<div class="mt-1 small text-muted"><i class="bi bi-lightbulb text-warning"></i> Disponible en: ';
                    f.sugerencias.forEach(s => {
                        sugerenciasHtml += `
                            <button type="button" class="btn btn-outline-primary btn-sm sugerencia-btn" onclick="aplicarSugerencia(${idx}, '${s.Deposito}', '${s.Ubicacion}')">
                                ${s.Deposito} · ${s.Ubicacion} (${s.Saldo})
                            </button>`;
                    });
                    sugerenciasHtml += '</div>';
                }

                const errorHtml = f.error ? `<div class="text-danger small fw-bold mt-1"><i class="bi bi-exclamation-triangle-fill"></i> ${f.error}</div>` : '';

                const tr = document.createElement('tr');
                tr.className = trClass;
                tr.innerHTML = `
                    <td class="fw-bold text-muted">${idx + 1}</td>
                    <td><span class="badge-deposito badge-dep-${f.dep_o}">${f.dep_o}</span></td>
                    <td class="font-monospace fw-bold">${f.ubic_o}</td>
                    <td><strong>${f.art_o}</strong></td>
                    <td class="border-end text-center fw-bold text-danger">-${f.cant_baja}</td>
                    <td><span class="badge-deposito badge-dep-${f.dep_d}">${f.dep_d}</span></td>
                    <td class="font-monospace fw-bold">${f.ubic_d}</td>
                    <td><strong>${f.art_d}</strong></td>
                    <td class="border-end text-center fw-bold text-success">+${f.cant_alta}</td>
                    <td class="text-center">
                        ${esRecod ? '<span class="badge bg-warning text-dark"><i class="bi bi-arrow-repeat"></i> Recodificación</span>' : '<span class="badge bg-info"><i class="bi bi-arrow-right"></i> Transferencia</span>'}
                        ${errorHtml}
                        ${sugerenciasHtml}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="quitarFila(${idx})" title="Eliminar fila">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>`;
                tbody.appendChild(tr);
            });

            badgeCount.textContent = `${listaFilas.length} filas`;
            badgeUnits.textContent = `${totalUnidades} unidades`;

            // Reset de botones
            document.getElementById('btn-ejecutar').style.display = 'none';
        }

        window.quitarFila = function(idx) {
            listaFilas.splice(idx, 1);
            renderTabla();
        };

        window.aplicarSugerencia = function(idx, dep, ubic) {
            listaFilas[idx].dep_o = dep;
            listaFilas[idx].ubic_o = ubic;
            listaFilas[idx].error = null;
            listaFilas[idx].sugerencias = [];
            renderTabla();
            Swal.fire('Origen Actualizado', `Fila #${idx + 1} actualizada con depósito ${dep} y ubicación ${ubic}. Volvé a validar el lote.`, 'info');
        };

        document.getElementById('btn-vaciar-lista').addEventListener('click', function() {
            if (listaFilas.length === 0) return;
            Swal.fire({
                title: '¿Vaciar lista?',
                text: 'Se eliminarán todas las filas de la vista previa.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, vaciar',
                cancelButtonText: 'Cancelar'
            }).then(r => {
                if (r.isConfirmed) {
                    listaFilas = [];
                    renderTabla();
                }
            });
        });

        // VALIDAR LOTE
        document.getElementById('btn-validar').addEventListener('click', async function() {
            if (listaFilas.length === 0) return;

            const payloadFilas = listaFilas.map(f => [
                f.dep_o, f.ubic_o, f.art_o, f.cant_baja,
                f.dep_d, f.ubic_d, f.art_d, f.cant_alta
            ]);

            Swal.fire({
                title: 'Validando lote...',
                text: 'Verificando stock en Tango y WMS',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const res = await fetch('controller/transferencias_ajax.php?accion=validar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filas: payloadFilas })
                });
                const data = await res.json();
                Swal.close();

                if (data.errores && data.errores.length > 0) {
                    Swal.fire({
                        title: 'Errores de validación',
                        html: data.errores.slice(0, 10).join('<br>') + (data.errores.length > 10 ? '<br>...' : ''),
                        icon: 'error'
                    });
                    return;
                }

                // Limpiar errores previos en filas
                listaFilas.forEach(f => { f.error = null; f.sugerencias = []; });

                if (data.ok) {
                    document.getElementById('btn-ejecutar').style.display = 'inline-flex';
                    renderTabla();
                    Swal.fire('¡Lote Validado!', 'Todas las filas fueron verificadas correctamente y tienen stock disponible. Podés proceder a ejecutar la transferencia.', 'success');
                } else {
                    // Mapear errores por fila
                    if (data.filas_con_error) {
                        data.filas_con_error.forEach(err => {
                            const filaIndex = parseInt(err.Fila) - 1;
                            if (listaFilas[filaIndex]) {
                                listaFilas[filaIndex].error = err.Resultado;
                            }
                        });
                    }

                    // Mapear sugerencias
                    if (data.sugerencias) {
                        data.sugerencias.forEach(sug => {
                            const filaIndex = parseInt(sug.Fila) - 1;
                            if (listaFilas[filaIndex]) {
                                if (!listaFilas[filaIndex].sugerencias) listaFilas[filaIndex].sugerencias = [];
                                listaFilas[filaIndex].sugerencias.push(sug);
                            }
                        });
                    }

                    renderTabla();
                    Swal.fire('Errores en el lote', data.mensaje || 'Se encontraron inconsistencias en una o más filas. Revisá los detalles marcados en rojo.', 'warning');
                }

            } catch (err) {
                Swal.fire('Error', 'No se pudo comunicar con el servidor.', 'error');
            }
        });

        // EJECUTAR LOTE
        document.getElementById('btn-ejecutar').addEventListener('click', function() {
            Swal.fire({
                title: '¿Confirmar transferencia de stock?',
                text: 'Esta acción aplicará los movimientos reales en Tango y WMS.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, ejecutar ahora',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981'
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                const payloadFilas = listaFilas.map(f => [
                    f.dep_o, f.ubic_o, f.art_o, f.cant_baja,
                    f.dep_d, f.ubic_d, f.art_d, f.cant_alta
                ]);

                Swal.fire({
                    title: 'Ejecutando transferencia...',
                    text: 'Aplicando comprobantes y movimientos',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    const res = await fetch('controller/transferencias_ajax.php?accion=ejecutar', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ filas: payloadFilas })
                    });
                    const data = await res.json();
                    Swal.close();

                    if (data.ok) {
                        Swal.fire({
                            title: '¡Transferencia Exitosa!',
                            html: `
                                <div class="text-start small">
                                    <p><strong>Comprobante:</strong> ${data.nro_comprobante || 'N/A'}</p>
                                    <p><strong>Nro Tarea WMS:</strong> ${data.numero_tarea || 'N/A'}</p>
                                    <p><strong>Filas Procesadas:</strong> ${data.filas_procesadas}</p>
                                </div>`,
                            icon: 'success'
                        }).then(() => {
                            listaFilas = [];
                            renderTabla();
                        });
                    } else {
                        Swal.fire('Error al ejecutar', data.mensaje || 'No se pudo completar la transferencia.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Error crítico al procesar la solicitud.', 'error');
                }
            });
        });
    </script>
</body>
</html>
