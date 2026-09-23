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

$desde = trim($_GET['desde'] ?? date('Y-m-01'));
$hasta = trim($_GET['hasta'] ?? date('Y-m-d'));
$deposito = trim($_GET['deposito'] ?? '');
$articulo = trim($_GET['articulo'] ?? '');
$usuario = trim($_GET['usuario'] ?? '');

$filtros = [
    'desde'    => $desde,
    'hasta'    => $hasta,
    'deposito' => $deposito,
    'articulo' => $articulo,
    'usuario'  => $usuario
];

$totalesPorDep = $service->historialTotalesPorDeposito($filtros);
$movimientosRes = $service->historialMovimientos($filtros, 1, 500);
$movimientos = $movimientosRes['filas'];
$totalRegistros = $movimientosRes['total'];

$totalSalidas = 0;
$totalEntradas = 0;
$totalMovs = 0;
foreach ($totalesPorDep as $t) {
    $totalSalidas += $t['salidas'];
    $totalEntradas += $t['entradas'];
    $totalMovs += $t['movimientos'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Transferencias entre Depósitos · XL Extra Large</title>
    <link rel="shortcut icon" href="/sistemas/assets/css/icono.jpg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- DataTables Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <style>
        :root {
            --primary: #0066cc;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
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

        .metric-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .badge-deposito {
            font-weight: 700;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }
        .badge-dep-04 { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-dep-06 { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-dep-10 { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-dep-20 { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="/comer/" class="text-decoration-none text-muted"><i class="bi bi-grid-fill"></i> COMER</a></li>
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Transferencias entre Depósitos</a></li>
                        <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Historial</li>
                    </ol>
                </nav>
                <h1 class="page-title">
                    <i class="bi bi-clock-history text-primary"></i> Historial de Movimientos de Stock
                </h1>
            </div>

            <div>
                <a href="index.php" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i> Volver a Transferencias
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid py-4 px-4">

        <!-- Métricas Rápidas -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Bajas / Salidas</div>
                        <h4 class="fw-bold m-0"><?= number_format($totalSalidas, 0, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-box-arrow-in-down-left"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Altas / Entradas</div>
                        <h4 class="fw-bold m-0"><?= number_format($totalEntradas, 0, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-layers-half"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Impacto Neto</div>
                        <h4 class="fw-bold m-0"><?= number_format($totalEntradas - $totalSalidas, 0, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Movimientos Registrados</div>
                        <h4 class="fw-bold m-0"><?= number_format($totalRegistros, 0, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="app-card">
            <h6 class="fw-bold mb-3"><i class="bi bi-funnel-fill text-primary"></i> Filtros de Búsqueda</h6>
            <form method="GET" action="historial.php" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-muted">Desde</label>
                    <input type="date" name="desde" class="form-control form-control-sm" value="<?= htmlspecialchars($desde) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Hasta</label>
                    <input type="date" name="hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($hasta) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Depósito</label>
                    <select name="deposito" class="form-select form-select-sm">
                        <option value="">Todos los depósitos</option>
                        <?php foreach ($depositos as $cod => $nom): ?>
                            <option value="<?= $cod ?>" <?= ($deposito === $cod) ? 'selected' : '' ?>><?= $cod ?> · <?= htmlspecialchars($nom) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Artículo</label>
                    <input type="text" name="articulo" class="form-control form-control-sm" placeholder="Código..." value="<?= htmlspecialchars($articulo) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Usuario</label>
                    <input type="text" name="usuario" class="form-control form-control-sm" placeholder="Usuario..." value="<?= htmlspecialchars($usuario) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="historial.php" class="btn btn-outline-secondary btn-sm" title="Limpiar filtros">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabla Historial -->
        <div class="app-card">
            <h5 class="fw-bold mb-3"><i class="bi bi-table text-primary"></i> Detalle de Movimientos Aplicados</h5>
            <div class="table-responsive">
                <table id="tablaHistorial" class="table table-striped table-hover align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Comprobante</th>
                            <th>Tarea WMS</th>
                            <th>Dep. Origen</th>
                            <th>Ubic. Origen</th>
                            <th>Art. Origen</th>
                            <th>Cant. Baja</th>
                            <th>Dep. Destino</th>
                            <th>Ubic. Destino</th>
                            <th>Art. Destino</th>
                            <th>Cant. Alta</th>
                            <th>Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['fecha']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($m['usuario']) ?></span></td>
                                <td class="font-monospace fw-bold"><?= htmlspecialchars($m['comprobante']) ?></td>
                                <td><?= htmlspecialchars((string)$m['tarea']) ?></td>
                                <td><span class="badge-deposito badge-dep-<?= $m['dep_origen'] ?>"><?= $m['dep_origen'] ?></span></td>
                                <td class="font-monospace"><?= htmlspecialchars($m['ubic_origen']) ?></td>
                                <td><strong><?= htmlspecialchars($m['art_origen']) ?></strong></td>
                                <td class="text-danger fw-bold text-center">-<?= $m['cant_baja'] ?></td>
                                <td><span class="badge-deposito badge-dep-<?= $m['dep_destino'] ?>"><?= $m['dep_destino'] ?></span></td>
                                <td class="font-monospace"><?= htmlspecialchars($m['ubic_destino']) ?></td>
                                <td><strong><?= htmlspecialchars($m['art_destino']) ?></strong></td>
                                <td class="text-success fw-bold text-center">+<?= $m['cant_alta'] ?></td>
                                <td>
                                    <?php if ($m['es_recodificacion']): ?>
                                        <span class="badge bg-warning text-dark">Recodificación</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Transferencia</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Scripts DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#tablaHistorial').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                order: [[0, 'desc']],
                dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"lip>',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel"></i> Exportar a Excel',
                        className: 'btn btn-success btn-sm'
                    }
                ]
            });
        });
    </script>
</body>
</html>
