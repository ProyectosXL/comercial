<?php
if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}

require_once __DIR__ . '/Class/AltaMuestras.php';

Auth::exigir();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta de Muestras de Artículos · XL Extra Large</title>
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
                        <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Alta de Muestras de Artículos</li>
                    </ol>
                </nav>
                <h1 class="page-title">
                    <i class="bi bi-flask-fill text-primary"></i> Alta de Muestras de Artículos
                </h1>
            </div>

            <div>
                <button type="button" class="btn btn-outline-info d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                    <i class="bi bi-question-circle"></i> Ayuda e Instructivo
                </button>
            </div>
        </div>
    </header>

    <div class="container-fluid py-4 px-4">

        <!-- Banner Explicativo -->
        <div class="alert alert-primary border-primary border-opacity-25 bg-primary bg-opacity-10 d-flex align-items-center gap-3 mb-4 rounded-3">
            <i class="bi bi-info-circle-fill text-primary fs-4"></i>
            <div>
                <strong class="text-dark">Herramienta de Carga de Muestras:</strong>
                <span class="text-muted">Permite dar de alta y registrar el stock de muestras que ingresan directamente a una ubicación sin comprobante de depósito previo.</span>
            </div>
        </div>

        <!-- Carga de Datos -->
        <div class="row g-4">
            <!-- Columna Pegado -->
            <div class="col-lg-8">
                <div class="app-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold m-0"><i class="bi bi-clipboard-plus text-primary"></i> 1. Pegar Datos desde Excel</h5>
                        <span class="badge bg-light text-muted border">3 Columnas</span>
                    </div>
                    <p class="small text-muted mb-2">Copiá y pegá las 3 columnas sin encabezados: <code>Código de Ubicación</code>, <code>Código de Artículo</code> y <code>Cantidad</code>.</p>
                    <textarea id="datos-pegados" class="form-control font-monospace small mb-3" rows="8" placeholder="R1001A01&#9;X012345&#9;1&#10;P0401001&#9;X098765&#9;2"></textarea>
                    
                    <div class="d-flex gap-2">
                        <button type="button" id="btn-previsualizar" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="bi bi-eye-fill"></i> Previsualizar Datos
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('datos-pegados').value = '';">
                            <i class="bi bi-trash"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Columna Archivo XLSX -->
            <div class="col-lg-4">
                <div class="app-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-spreadsheet text-success"></i> 2. Subir Archivo Excel</h5>
                        <p class="small text-muted mb-3">Podés subir directamente un archivo <code>.xlsx</code> con el mismo formato de 3 columnas sin encabezados.</p>
                        <input type="file" id="archivo-xlsx" class="form-control form-control-sm mb-3" accept=".xlsx">
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button type="button" id="btn-subir-archivo" class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-upload"></i> Cargar y Previsualizar
                        </button>
                        <a href="assets/PlantillaAltaMuestrasArticulos.xlsx" download="PlantillaAltaMuestrasArticulos.xlsx" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-download"></i> Descargar Plantilla Ejemplo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista Previa -->
        <div id="seccion-preview" class="app-card mt-4" style="display:none;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="fw-bold m-0"><i class="bi bi-table text-primary"></i> Vista Previa de Muestras</h5>
                    <span id="badge-contador" class="badge bg-primary rounded-pill px-3 py-2">0 filas</span>
                    <span id="badge-unidades" class="badge bg-secondary rounded-pill px-3 py-2">0 unidades</span>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" id="btn-importar" class="btn btn-primary fw-bold d-flex align-items-center gap-2 px-3 py-2">
                        <i class="bi bi-box-arrow-in-down fs-5"></i> Confirmar e Importar
                    </button>
                    <button type="button" id="btn-ejecutar" class="btn btn-success fw-bold d-flex align-items-center gap-2 px-3 py-2" style="display:none;">
                        <i class="bi bi-play-circle fs-5"></i> Ejecutar Alta de Muestras
                    </button>
                </div>
            </div>

            <div class="table-responsive border rounded-3">
                <table class="table table-hover table-custom align-middle mb-0" id="tabla-preview">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Código de Ubicación</th>
                            <th>Código de Artículo</th>
                            <th class="text-center" style="width: 150px;">Cantidad</th>
                            <th class="text-center" style="width: 100px;">Estado</th>
                            <th class="text-center" style="width: 60px;">Quitar</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-preview">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Resultados de Ejecución -->
        <div id="seccion-resultados" class="app-card mt-4" style="display:none;">
            <h5 class="fw-bold mb-3"><i class="bi bi-check2-all text-success"></i> Resultado del Proceso</h5>
            
            <div id="alertas-resultado"></div>

            <!-- Filas Procesadas -->
            <div id="bloque-procesadas" class="mt-4" style="display:none;">
                <h6 class="fw-bold text-success"><i class="bi bi-check-circle-fill"></i> Filas Procesadas con Éxito</h6>
                <div class="table-responsive border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0" id="tabla-procesadas">
                        <thead class="table-light">
                            <tr>
                                <th>Ubicación</th>
                                <th>Artículo</th>
                                <th class="text-center">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Filas Omitidas por Falta de Stock -->
            <div id="bloque-omitidas" class="mt-4" style="display:none;">
                <div class="alert alert-warning d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div>
                        <strong id="contador-omitidas">0</strong> artículos fueron omitidos por no contar con saldo previo para ajuste inverso.
                    </div>
                </div>
                <div class="table-responsive border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0" id="tabla-omitidas">
                        <thead class="table-light">
                            <tr>
                                <th>Ubicación</th>
                                <th>Artículo</th>
                                <th class="text-center">Cantidad</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Filas con Error Bloqueante -->
            <div id="bloque-errores" class="mt-4" style="display:none;">
                <h6 class="fw-bold text-danger"><i class="bi bi-x-circle-fill"></i> Errores de Validación</h6>
                <div class="table-responsive border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0" id="tabla-errores">
                        <thead class="table-light">
                            <tr>
                                <th>Ubicación</th>
                                <th>Artículo</th>
                                <th class="text-center">Cantidad</th>
                                <th>Motivo del Error</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal de Ayuda -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-question-circle text-primary"></i> Instructivo: Alta de Muestras de Artículos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>Esta herramienta registra el <strong>alta de muestras de artículos</strong> que llegan sin depósito de origen formal (por ejemplo, muestras aportadas por directivos, compras de prueba o desarrollo): suma esa cantidad como stock en la ubicación indicada.</p>

                    <h6 class="fw-bold mt-3"><i class="bi bi-list-ol text-primary"></i> Pasos para la carga</h6>
                    <ol class="small text-muted">
                        <li>En Excel, armá una lista con 3 columnas <strong>sin encabezados</strong>: <code>Código de Ubicación</code>, <code>Código de Artículo</code> y <code>Cantidad</code>.</li>
                        <li>Copiá esas celdas desde Excel (Ctrl+C).</li>
                        <li>Pegalas en el cuadro de texto y hacé clic en <strong>Previsualizar Datos</strong>.</li>
                        <li>Revisá que no haya filas en rojo. Si todo es correcto, hacé clic en <strong>Confirmar e Importar</strong>.</li>
                        <li>Finalmente hacé clic en <strong>Ejecutar Alta de Muestras</strong> para aplicar el ajuste definitivo de stock en Tango.</li>
                    </ol>

                    <div class="alert alert-warning small mt-3">
                        <i class="bi bi-shield-exclamation"></i>
                        <strong>Importante:</strong> El paso de ejecución mueve stock real. Si algún código de artículo o ubicación no existe en el sistema, la operación no se aplicará para evitar inconsistencias.
                    </div>
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
        let filasCargadas = [];

        // Previsualizar texto pegado
        document.getElementById('btn-previsualizar').addEventListener('click', function() {
            const raw = document.getElementById('datos-pegados').value.trim();
            if (!raw) {
                Swal.fire('Atención', 'Pegá datos desde Excel antes de previsualizar.', 'warning');
                return;
            }

            const lineas = raw.split(/\r?\n/);
            const nuevas = [];

            lineas.forEach(lin => {
                const cols = lin.split('\t').map(c => c.trim());
                if (cols.length < 3) return;

                const ubic = cols[0].toUpperCase();
                const art = cols[1].toUpperCase();
                const cant = parseInt(cols[2]) || 0;

                nuevas.push({ ubic: ubic, art: art, cant: cant, error: (cant <= 0) ? 'Cantidad inválida' : null });
            });

            if (nuevas.length === 0) {
                Swal.fire('Error', 'No se pudieron detectar filas con 3 columnas.', 'error');
                return;
            }

            filasCargadas = nuevas;
            renderTabla();
        });

        // Subir y parsear XLSX
        document.getElementById('btn-subir-archivo').addEventListener('click', function() {
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
                    if (!r || r.length < 3) return;
                    if (r.every(c => !c || String(c).trim() === '')) return;

                    const ubic = String(r[0] || '').trim().toUpperCase();
                    const art = String(r[1] || '').trim().toUpperCase();
                    const cant = parseInt(r[2]) || 0;

                    nuevas.push({ ubic: ubic, art: art, cant: cant, error: (cant <= 0) ? 'Cantidad inválida' : null });
                });

                if (nuevas.length === 0) {
                    Swal.fire('Error', 'No se encontraron filas válidas en el archivo.', 'error');
                    return;
                }

                filasCargadas = nuevas;
                renderTabla();
                Swal.fire('Éxito', `Se cargaron ${nuevas.length} filas desde el archivo.`, 'success');
            };
            reader.readAsArrayBuffer(fileInput.files[0]);
        });

        function renderTabla() {
            const sec = document.getElementById('seccion-preview');
            const tbody = document.getElementById('tbody-preview');
            const badgeCount = document.getElementById('badge-contador');
            const badgeUnits = document.getElementById('badge-unidades');

            if (filasCargadas.length === 0) {
                sec.style.display = 'none';
                return;
            }

            sec.style.display = 'block';
            tbody.innerHTML = '';

            let totalUnidades = 0;

            filasCargadas.forEach((f, idx) => {
                totalUnidades += f.cant;
                const tr = document.createElement('tr');
                if (f.error) tr.className = 'row-invalid';

                tr.innerHTML = `
                    <td class="text-muted fw-bold">${idx + 1}</td>
                    <td class="font-monospace fw-bold">${f.ubic}</td>
                    <td><strong>${f.art}</strong></td>
                    <td class="text-center fw-bold text-success">+${f.cant}</td>
                    <td class="text-center">
                        ${f.error ? `<span class="badge bg-danger">${f.error}</span>` : '<span class="badge bg-success">Listo</span>'}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="quitarFila(${idx})">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>`;
                tbody.appendChild(tr);
            });

            badgeCount.textContent = `${filasCargadas.length} filas`;
            badgeUnits.textContent = `${totalUnidades} unidades`;

            document.getElementById('btn-ejecutar').style.display = 'none';
            document.getElementById('seccion-resultados').style.display = 'none';
        }

        window.quitarFila = function(idx) {
            filasCargadas.splice(idx, 1);
            renderTabla();
        };

        // Confirmar e Importar
        document.getElementById('btn-importar').addEventListener('click', async function() {
            if (filasCargadas.length === 0) return;

            const payloadFilas = filasCargadas.map(f => [f.ubic, f.art, f.cant]);

            Swal.fire({
                title: 'Importando filas...',
                text: 'Cargando datos temporales en el sistema',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const res = await fetch('controller/muestras_ajax.php?accion=importar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filas: payloadFilas })
                });
                const data = await res.json();
                Swal.close();

                if (data.ok) {
                    document.getElementById('btn-ejecutar').style.display = 'inline-flex';
                    Swal.fire('¡Datos Importados!', `Se importaron ${data.importadas} filas correctamente. Ahora podés presionar "Ejecutar Alta de Muestras".`, 'success');
                } else {
                    Swal.fire('Error al importar', data.errores ? data.errores.join('<br>') : (data.error || 'Error desconocido'), 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            }
        });

        // Ejecutar Alta de Muestras
        document.getElementById('btn-ejecutar').addEventListener('click', function() {
            Swal.fire({
                title: '¿Confirmar ejecución de alta de muestras?',
                text: 'Esta acción aplicará el ajuste real de stock en el sistema.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, aplicar ajuste',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981'
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Procesando ajuste...',
                    text: 'Ejecutando procedimiento de recodificación y ajuste',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    const res = await fetch('controller/muestras_ajax.php?accion=ejecutar', {
                        method: 'POST'
                    });
                    const data = await res.json();
                    Swal.close();

                    const secRes = document.getElementById('seccion-resultados');
                    const alertasDiv = document.getElementById('alertas-resultado');
                    secRes.style.display = 'block';

                    if (data.ok) {
                        alertasDiv.innerHTML = `
                            <div class="alert alert-success">
                                <h5 class="fw-bold mb-1"><i class="bi bi-check-circle-fill"></i> ¡Alta de Muestras Ejecutada con Éxito!</h5>
                                <p class="mb-0 small">Los movimientos fueron aplicados correctamente al inventario.</p>
                            </div>`;

                        // Mostrar tabla procesadas
                        if (data.filas_procesadas && data.filas_procesadas.length > 0) {
                            const bProc = document.getElementById('bloque-procesadas');
                            const tbodyProc = document.querySelector('#tabla-procesadas tbody');
                            tbodyProc.innerHTML = '';
                            data.filas_procesadas.forEach(p => {
                                tbodyProc.innerHTML += `
                                    <tr>
                                        <td class="font-monospace">${p.CodigoUbicacion}</td>
                                        <td><strong>${p.CodigoArticulo}</strong></td>
                                        <td class="text-center text-success fw-bold">+${p.CantidadAjuste}</td>
                                    </tr>`;
                            });
                            bProc.style.display = 'block';
                        }

                        // Mostrar tabla omitidas
                        if (data.filas_omitidas && data.filas_omitidas.length > 0) {
                            const bOmit = document.getElementById('bloque-omitidas');
                            document.getElementById('contador-omitidas').textContent = data.filas_omitidas.length;
                            const tbodyOmit = document.querySelector('#tabla-omitidas tbody');
                            tbodyOmit.innerHTML = '';
                            data.filas_omitidas.forEach(o => {
                                tbodyOmit.innerHTML += `
                                    <tr>
                                        <td class="font-monospace">${o.CodigoUbicacion}</td>
                                        <td><strong>${o.CodigoArticulo}</strong></td>
                                        <td class="text-center">${o.CantidadAjuste}</td>
                                        <td><span class="badge bg-warning text-dark">${o.Resultado}</span></td>
                                    </tr>`;
                            });
                            bOmit.style.display = 'block';
                        }

                        Swal.fire('¡Éxito!', 'Ajuste de muestras completado con éxito.', 'success');

                    } else {
                        alertasDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <h5 class="fw-bold mb-1"><i class="bi bi-x-circle-fill"></i> El lote no pudo ser aplicado</h5>
                                <p class="mb-0 small">Se encontraron errores en los datos que impiden realizar el movimiento.</p>
                            </div>`;

                        if (data.resultado_filas && data.resultado_filas.length > 0) {
                            const bErr = document.getElementById('bloque-errores');
                            const tbodyErr = document.querySelector('#tabla-errores tbody');
                            tbodyErr.innerHTML = '';
                            data.resultado_filas.forEach(e => {
                                tbodyErr.innerHTML += `
                                    <tr>
                                        <td class="font-monospace">${e.CodigoUbicacion}</td>
                                        <td><strong>${e.CodigoArticulo}</strong></td>
                                        <td class="text-center">${e.CantidadAjuste}</td>
                                        <td class="text-danger fw-bold">${e.Resultado}</td>
                                    </tr>`;
                            });
                            bErr.style.display = 'block';
                        }

                        Swal.fire('Errores detectados', 'Revisá los errores listados abajo para corregirlos.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Ocurrió un error al procesar el alta.', 'error');
                }
            });
        });
    </script>
</body>
</html>
