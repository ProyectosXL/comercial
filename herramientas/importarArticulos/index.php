<?php
if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}

Auth::exigir();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Artículos para Etiquetas · XL Extra Large</title>
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
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --danger-color: #ef4444;
            --success-color: #10b981;
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
            color: var(--text-main);
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

        .drop-zone {
            border: 2px dashed #93c5fd;
            border-radius: 14px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            background: #eff6ff;
        }

        .drop-zone:hover, .drop-zone.drag-over {
            border-color: var(--primary);
            background: #dbeafe;
            transform: translateY(-2px);
        }

        .drop-zone i {
            font-size: 2.75rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: block;
        }

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
        }

        .row-invalid {
            background-color: #fef2f2 !important;
            border-left: 4px solid var(--danger-color);
        }

        .row-valid {
            background-color: #f0fdf4 !important;
        }
    </style>
</head>
<body>

    <!-- Header Principal -->
    <header class="main-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="/centralizadores/comer/index.php" class="text-decoration-none text-muted"><i class="bi bi-grid-fill"></i> COMER</a></li>
                        <li class="breadcrumb-item text-muted">Precios, Productos y Auditoría</li>
                        <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Importar Artículos</li>
                    </ol>
                </nav>
                <h1 class="page-title">
                    <i class="bi bi-tags-fill text-primary"></i> Importar Artículos para Impresión de Etiquetas
                </h1>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1" id="btn-ver-actuales">
                    <i class="bi bi-table"></i> Ver Artículos en Tabla de Etiquetas
                </button>
                <button type="button" class="btn btn-outline-info d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                    <i class="bi bi-question-circle"></i> Ayuda
                </button>
            </div>
        </div>
    </header>

    <div class="container-fluid py-4 px-4">

        <!-- Banner Informativo -->
        <div class="alert alert-info border-info border-opacity-25 bg-info bg-opacity-10 d-flex align-items-center gap-3 mb-4 rounded-3">
            <i class="bi bi-info-circle-fill text-info fs-4"></i>
            <div>
                <strong class="text-dark">Herramienta de Importación de Artículos:</strong>
                <span class="text-muted">Permite validar masivamente artículos contra el maestro de Tango y actualizar la tabla <code>SJ_T_ETIQUETAS_FINAL</code> utilizada para la emisión e impresión de etiquetas de productos.</span>
            </div>
        </div>

        <!-- Opciones de Carga -->
        <div class="app-card p-0">
            <div class="border-bottom p-3 bg-white rounded-top-4">
                <ul class="nav nav-pills gap-2" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-flex align-items-center gap-2" id="tab-archivo" data-bs-toggle="pill" data-bs-target="#panel-archivo" type="button" role="tab">
                            <i class="bi bi-file-earmark-excel fs-5"></i> 1. Subir Archivo Excel (.xlsx / .xls)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2" id="tab-pegado" data-bs-toggle="pill" data-bs-target="#panel-pegado" type="button" role="tab">
                            <i class="bi bi-clipboard-plus fs-5"></i> 2. Pegar Códigos de Artículos
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content p-4" id="pills-tabContent">

                <!-- TAB 1: ARCHIVO EXCEL -->
                <div class="tab-pane fade show active" id="panel-archivo" role="tabpanel">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-8">
                            <input type="file" id="archivo-excel" accept=".xlsx,.xls,.csv" style="display:none">
                            <div class="drop-zone" id="dropZone">
                                <i class="bi bi-cloud-arrow-up-fill"></i>
                                <p class="fw-bold mb-1">Arrastrá tu archivo Excel aquí o hacé clic para seleccionarlo</p>
                                <small class="text-muted d-block">Formatos compatibles: .xlsx, .xls (El sistema detectará la columna de artículos)</small>
                                <div id="archivo-seleccionado" class="mt-2 text-primary fw-bold" style="display:none;"></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 bg-white h-100 d-flex flex-column justify-content-center">
                                <h6 class="fw-bold mb-2"><i class="bi bi-check-circle text-primary"></i> Estructura requerida</h6>
                                <p class="small text-muted mb-3">El archivo puede contener un encabezado con la columna <code>ARTICULO</code> o simplemente una lista con los códigos de artículo en la primera columna.</p>
                                <button type="button" id="btn-procesar-archivo" class="btn btn-primary fw-bold d-flex align-items-center justify-content-center gap-2" disabled>
                                    <i class="bi bi-gear-wide-connected"></i> Validar Artículos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: PEGAR CODIGOS -->
                <div class="tab-pane fade" id="panel-pegado" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <label class="form-label fw-bold text-dark">Pegar lista de códigos de artículos (uno por línea):</label>
                            <textarea id="texto-articulos" class="form-control font-monospace small mb-3" rows="7" placeholder="X012345&#10;X098765&#10;G2502Z05"></textarea>
                            <div class="d-flex gap-2">
                                <button type="button" id="btn-procesar-texto" class="btn btn-primary d-flex align-items-center gap-2">
                                    <i class="bi bi-table"></i> Procesar y Validar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('texto-articulos').value = '';">
                                    <i class="bi bi-trash"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- SECCIÓN DE VISTA PREVIA Y VALIDACIÓN -->
        <div id="seccion-preview" class="app-card" style="display:none;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="fw-bold m-0"><i class="bi bi-list-check text-primary"></i> Resultado de la Validación</h5>
                    <span id="badge-total" class="badge bg-primary rounded-pill px-3 py-2">0 artículos</span>
                    <span id="badge-validos" class="badge bg-success rounded-pill px-3 py-2">0 válidos</span>
                    <span id="badge-invalidos" class="badge bg-danger rounded-pill px-3 py-2">0 no encontrados</span>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" id="btn-guardar-etiquetas" class="btn btn-success fw-bold d-flex align-items-center gap-2 px-3 py-2" disabled>
                        <i class="bi bi-printer-fill fs-5"></i> Cargar a Tabla de Etiquetas
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="vaciarLista()">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                </div>
            </div>

            <div id="alertas-validacion" class="mb-3"></div>

            <div class="table-responsive border rounded-3">
                <table class="table table-hover table-custom align-middle mb-0" id="tabla-articulos">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 220px;">Código Artículo</th>
                            <th>Descripción en Tango / Etiquetas</th>
                            <th class="text-center" style="width: 140px;">Estado</th>
                            <th class="text-center" style="width: 60px;">Quitar</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-articulos">
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Ver Artículos Actuales en SJ_T_ETIQUETAS_FINAL -->
    <div class="modal fade" id="modalActuales" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-database text-primary"></i> Artículos en Tabla de Etiquetas (SJ_T_ETIQUETAS_FINAL)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span id="modal-contador-actuales" class="badge bg-primary">0 registros</span>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-limpiar-tabla">
                            <i class="bi bi-trash"></i> Vaciar Tabla de Etiquetas
                        </button>
                    </div>
                    <div class="table-responsive border rounded-3">
                        <table class="table table-sm table-striped align-middle mb-0" id="tabla-actuales">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Código Artículo</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Ayuda -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-question-circle text-primary"></i> Instructivo: Importar Artículos para Etiquetas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>Esta herramienta permite cargar de forma masiva los códigos de artículos que serán enviados a impresión de etiquetas en la tabla temporal <code>SJ_T_ETIQUETAS_FINAL</code>.</p>

                    <h6 class="fw-bold mt-3"><i class="bi bi-list-ol text-primary"></i> Pasos para importar</h6>
                    <ol class="small text-muted">
                        <li>Subí un archivo Excel (.xlsx o .xls) con los códigos de artículo o pegá la lista directamente desde el portapapeles.</li>
                        <li>El sistema consultará cada código contra el maestro de artículos (STA11) y la tabla de etiquetas (SJ_ETIQUETAS_FINAL).</li>
                        <li>Los artículos válidos se mostrarán en verde con su descripción asociada. Aquellos que no existan se marcarán en rojo.</li>
                        <li>Hacé clic en <strong>Cargar a Tabla de Etiquetas</strong> para actualizar la tabla <code>SJ_T_ETIQUETAS_FINAL</code>.</li>
                    </ol>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let filasValidadas = [];
        let archivoExcelCargado = null;

        const dropZone = document.getElementById('dropZone');
        const inputArchivo = document.getElementById('archivo-excel');
        const txtArchivoSel = document.getElementById('archivo-seleccionado');
        const btnProcesarArch = document.getElementById('btn-procesar-archivo');

        dropZone.addEventListener('click', () => inputArchivo.click());

        inputArchivo.addEventListener('change', e => {
            if (e.target.files.length) handleFile(e.target.files[0]);
        });

        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));

        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            if (e.dataTransfer.files.length) {
                inputArchivo.files = e.dataTransfer.files;
                handleFile(e.dataTransfer.files[0]);
            }
        });

        function handleFile(file) {
            archivoExcelCargado = file;
            txtArchivoSel.textContent = `Archivo seleccionado: ${file.name}`;
            txtArchivoSel.style.display = 'block';
            btnProcesarArch.disabled = false;
        }

        btnProcesarArch.addEventListener('click', function() {
            if (!archivoExcelCargado) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    const json = XLSX.utils.sheet_to_json(sheet, { header: 1 });

                    const articulos = [];
                    json.forEach((row, idx) => {
                        if (!row || row.length === 0) return;
                        // Buscar en columna 0 o donde esté el código
                        let code = String(row[0] || '').trim();
                        if (idx === 0 && (code.toUpperCase() === 'ARTICULO' || code.toUpperCase() === 'COD_ARTICU')) return;
                        if (code !== '') articulos.push(code);
                    });

                    if (articulos.length === 0) {
                        Swal.fire('Error', 'No se encontraron códigos de artículo en el archivo.', 'error');
                        return;
                    }

                    validarListaArticulos(articulos);

                } catch (err) {
                    Swal.fire('Error', 'No se pudo leer el archivo Excel.', 'error');
                }
            };
            reader.readAsArrayBuffer(archivoExcelCargado);
        });

        // Procesar texto pegado
        document.getElementById('btn-procesar-texto').addEventListener('click', function() {
            const raw = document.getElementById('texto-articulos').value.trim();
            if (!raw) {
                Swal.fire('Atención', 'Pegá al menos un código de artículo antes de procesar.', 'warning');
                return;
            }

            const lineas = raw.split(/\r?\n/).map(l => l.trim()).filter(l => l !== '');
            if (lineas.length === 0) {
                Swal.fire('Atención', 'No se detectaron códigos válidos.', 'warning');
                return;
            }

            validarListaArticulos(lineas);
        });

        async function validarListaArticulos(articulos) {
            Swal.fire({
                title: 'Validando artículos...',
                text: 'Consultando maestro de Tango y tabla de etiquetas',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const res = await fetch('controller/importar_ajax.php?accion=validar_lote', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ articulos: articulos })
                });
                const data = await res.json();
                Swal.close();

                if (!data.ok) {
                    Swal.fire('Error', data.error || 'Ocurrió un error al validar los artículos.', 'error');
                    return;
                }

                filasValidadas = data.filas;
                renderTablaPreview(data.validos, data.invalidos);

            } catch (err) {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            }
        }

        function renderTablaPreview(validos, invalidos) {
            const sec = document.getElementById('seccion-preview');
            const tbody = document.getElementById('tbody-articulos');
            const bTotal = document.getElementById('badge-total');
            const bVal = document.getElementById('badge-validos');
            const bInv = document.getElementById('badge-invalidos');
            const btnGuardar = document.getElementById('btn-guardar-etiquetas');

            if (filasValidadas.length === 0) {
                sec.style.display = 'none';
                return;
            }

            sec.style.display = 'block';
            tbody.innerHTML = '';

            let countVal = 0;
            let countInv = 0;

            filasValidadas.forEach((f, idx) => {
                if (f.valido) countVal++; else countInv++;
                const tr = document.createElement('tr');
                tr.className = f.valido ? 'row-valid' : 'row-invalid';

                tr.innerHTML = `
                    <td class="text-muted fw-bold">${idx + 1}</td>
                    <td class="font-monospace fw-bold">${f.articulo}</td>
                    <td>${f.descripcion}</td>
                    <td class="text-center">
                        ${f.valido ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Válido</span>' : '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> No encontrado</span>'}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="quitarFila(${idx})">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>`;
                tbody.appendChild(tr);
            });

            bTotal.textContent = `${filasValidadas.length} artículos`;
            bVal.textContent = `${countVal} válidos`;
            bInv.textContent = `${countInv} no encontrados`;

            btnGuardar.disabled = (countVal === 0);
        }

        window.quitarFila = function(idx) {
            filasValidadas.splice(idx, 1);
            let v = 0, inv = 0;
            filasValidadas.forEach(f => { if (f.valido) v++; else inv++; });
            renderTablaPreview(v, inv);
        };

        window.vaciarLista = function() {
            filasValidadas = [];
            document.getElementById('seccion-preview').style.display = 'none';
        };

        // Guardar a SJ_T_ETIQUETAS_FINAL
        document.getElementById('btn-guardar-etiquetas').addEventListener('click', function() {
            const validosParaGuardar = filasValidadas.filter(f => f.valido);
            if (validosParaGuardar.length === 0) return;

            Swal.fire({
                title: '¿Cargar artículos a la tabla de etiquetas?',
                text: `Se actualizará SJ_T_ETIQUETAS_FINAL con ${validosParaGuardar.length} artículos.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cargar ahora',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981'
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Guardando datos...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    const res = await fetch('controller/importar_ajax.php?accion=guardar_lote', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ articulos: validosParaGuardar })
                    });
                    const data = await res.json();
                    Swal.close();

                    if (data.ok) {
                        Swal.fire({
                            title: '¡Carga Exitosa!',
                            text: data.mensaje,
                            icon: 'success'
                        }).then(() => {
                            vaciarLista();
                        });
                    } else {
                        Swal.fire('Error', data.error || 'No se pudieron guardar los artículos.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                }
            });
        });

        // Ver Artículos Actuales
        const modalActuales = new bootstrap.Modal(document.getElementById('modalActuales'));
        document.getElementById('btn-ver-actuales').addEventListener('click', async function() {
            try {
                const res = await fetch('controller/importar_ajax.php?accion=consultar_actuales');
                const data = await res.json();

                const tbody = document.querySelector('#tabla-actuales tbody');
                tbody.innerHTML = '';
                document.getElementById('modal-contador-actuales').textContent = `${data.total} registros`;

                if (data.filas && data.filas.length > 0) {
                    data.filas.forEach((r, idx) => {
                        tbody.innerHTML += `
                            <tr>
                                <td class="text-muted fw-bold">${idx + 1}</td>
                                <td class="font-monospace fw-bold">${r.articulo}</td>
                                <td>${r.descripcion}</td>
                            </tr>`;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">La tabla de etiquetas está vacía.</td></tr>';
                }

                modalActuales.show();

            } catch (err) {
                Swal.fire('Error', 'No se pudieron consultar los artículos actuales.', 'error');
            }
        });

        // Limpiar tabla actual
        document.getElementById('btn-limpiar-tabla').addEventListener('click', function() {
            Swal.fire({
                title: '¿Vaciar tabla de etiquetas?',
                text: 'Se eliminarán todos los registros de SJ_T_ETIQUETAS_FINAL.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, vaciar',
                cancelButtonText: 'Cancelar'
            }).then(async (r) => {
                if (!r.isConfirmed) return;

                try {
                    const res = await fetch('controller/importar_ajax.php?accion=limpiar_actuales');
                    const data = await res.json();
                    if (data.ok) {
                        Swal.fire('Éxito', data.mensaje, 'success');
                        modalActuales.hide();
                    }
                } catch (err) {
                    Swal.fire('Error', 'No se pudo vaciar la tabla.', 'error');
                }
            });
        });
    </script>
</body>
</html>
