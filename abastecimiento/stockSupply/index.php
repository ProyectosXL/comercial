<?php
if (file_exists(__DIR__ . '/../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}
require_once __DIR__ . '/Class/StockSupply.php';

Auth::exigir();

$supplyObj  = new StockSupply();
$rubros     = $supplyObj->getRubros('AR');
$categorias = $supplyObj->getCategorias('AR');
$temporadas = $supplyObj->getTemporadas('AR');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Supply · XL Extra Large</title>
    <link rel="shortcut icon" href="/sistemas/assets/css/icono.jpg">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 & Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables 1.13 Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --border-color: #e2e8f0;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: #f8fafc;
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .main-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .page-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .page-title i {
            color: var(--primary);
        }

        .filter-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            margin-bottom: 1.5rem;
        }

        .table-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .btn-toggle-country {
            display: inline-flex;
            border-radius: 30px;
            padding: 3px;
            background: #e2e8f0;
            border: none;
            gap: 2px;
        }

        .btn-toggle-country button {
            border: none;
            background: transparent;
            padding: 0.35rem 0.9rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            transition: all 0.2s ease;
        }

        .btn-toggle-country button.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .badge-total {
            background: #0f172a;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge-total span {
            color: #38bdf8;
            font-weight: 700;
        }

        table.dataTable thead th {
            font-family: 'Sora', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f8fafc;
            color: #475569;
            border-bottom: 2px solid #cbd5e1 !important;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            font-size: 0.85rem;
            vertical-align: middle;
        }

        /* Toolbar y Botones de DataTables */
        .dt-buttons {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 0.4rem !important;
            margin-bottom: 0.75rem !important;
        }

        .dt-buttons.btn-group {
            display: flex !important;
            gap: 0.4rem !important;
        }

        .dt-buttons.btn-group > .btn,
        .dt-button {
            border-radius: 8px !important;
            margin: 0 !important;
            border: 1px solid transparent !important;
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            padding: 0.42rem 0.85rem !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.4rem !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04) !important;
            transition: all 0.15s ease-in-out !important;
            background-image: none !important;
        }

        /* Estilos específicos para cada botón */
        .btn-dt-copy { background-color: #f1f5f9 !important; color: #475569 !important; border-color: #cbd5e1 !important; }
        .btn-dt-copy:hover { background-color: #e2e8f0 !important; color: #1e293b !important; }

        .btn-dt-excel { background-color: #ecfdf5 !important; color: #065f46 !important; border-color: #a7f3d0 !important; }
        .btn-dt-excel:hover { background-color: #10b981 !important; color: #ffffff !important; border-color: #10b981 !important; }

        .btn-dt-csv { background-color: #f0fdfa !important; color: #115e59 !important; border-color: #99f6e4 !important; }
        .btn-dt-csv:hover { background-color: #14b8a6 !important; color: #ffffff !important; border-color: #14b8a6 !important; }

        .btn-dt-pdf { background-color: #fef2f2 !important; color: #991b1b !important; border-color: #fecaca !important; }
        .btn-dt-pdf:hover { background-color: #ef4444 !important; color: #ffffff !important; border-color: #ef4444 !important; }

        .btn-dt-print { background-color: #f8fafc !important; color: #334155 !important; border-color: #cbd5e1 !important; }
        .btn-dt-print:hover { background-color: #334155 !important; color: #ffffff !important; border-color: #334155 !important; }

        .btn-dt-colvis { background-color: #eff6ff !important; color: #1e40af !important; border-color: #bfdbfe !important; }
        .btn-dt-colvis:hover { background-color: #2563eb !important; color: #ffffff !important; border-color: #2563eb !important; }

        /* Buscador DataTable */
        .dataTables_filter {
            margin-bottom: 0.75rem !important;
        }

        .dataTables_filter label {
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            color: #475569 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }

        .dataTables_filter input {
            border-radius: 8px !important;
            border: 1px solid #cbd5e1 !important;
            padding: 0.38rem 0.75rem !important;
            font-size: 0.85rem !important;
            background-color: #ffffff !important;
            transition: all 0.2s ease !important;
        }

        .dataTables_filter input:focus {
            border-color: #0284c7 !important;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
            outline: none !important;
        }

        /* Selector de cantidad por página */
        .dataTables_length label {
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            color: #64748b !important;
        }

        .dataTables_length select {
            border-radius: 8px !important;
            border: 1px solid #cbd5e1 !important;
            padding: 0.3rem 0.6rem !important;
            font-size: 0.85rem !important;
            margin: 0 0.35rem !important;
        }

        /* Paginación */
        .page-item .page-link {
            border-radius: 6px !important;
            margin: 0 2px !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            border-color: #e2e8f0 !important;
            color: #475569 !important;
        }

        .page-item.active .page-link {
            background-color: #0284c7 !important;
            border-color: #0284c7 !important;
            color: #ffffff !important;
        }

        .dir-breadcrumb {
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="main-header">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb dir-breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/centralizadores/comer/index.php" class="text-decoration-none text-muted">COMER</a></li>
                    <li class="breadcrumb-item"><a href="/centralizadores/comer/index.php#card_abastecimiento" class="text-decoration-none text-muted">Abastecimiento y Stock</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Stock Supply (Pivot)</li>
                </ol>
            </nav>
            <h1 class="page-title">
                <i class="bi bi-grid-3x3-gap-fill"></i> Stock Supply
            </h1>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="btn-toggle-country" id="country-toggle">
                <button type="button" class="active" data-country="AR">AR</button>
                <button type="button" data-country="UY">UY</button>
            </div>
            <a href="/centralizadores/comer/index.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                <i class="bi bi-arrow-left"></i> Volver a COMER
            </a>
        </div>
    </header>

    <!-- Main Body -->
    <main class="container-fluid px-4 py-3">

        <!-- Card de Filtros -->
        <div class="filter-card">
            <form id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filtro_rubro" class="form-label text-muted fw-semibold small mb-1">Rubro</label>
                    <select class="form-select form-select-sm" id="filtro_rubro" name="rubro">
                        <option value="">Todos los Rubros</option>
                        <?php foreach ($rubros as $r): ?>
                            <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtro_categoria" class="form-label text-muted fw-semibold small mb-1">Categoría</label>
                    <select class="form-select form-select-sm" id="filtro_categoria" name="categoria">
                        <option value="">Todas las Categorías</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtro_temporada" class="form-label text-muted fw-semibold small mb-1">Temporada</label>
                    <select class="form-select form-select-sm" id="filtro_temporada" name="temporada">
                        <option value="">Todas las Temporadas</option>
                        <?php foreach ($temporadas as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-center justify-content-between gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold flex-grow-1" id="btnBuscar">
                        <i class="fas fa-search me-1"></i> Buscar
                    </button>
                    <div class="badge-total text-nowrap">
                        Registros: <span id="total-badge">0</span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Card de Tabla -->
        <div class="table-card">
            <div class="table-responsive" id="tableContainer">
                <table id="pivotTable" class="table table-hover table-striped w-100 align-middle">
                    <thead id="pivotHead"></thead>
                    <tbody id="pivotBody"></tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        let currentCountry = 'AR';
        let dataTable = null;

        function loadPivotData() {
            const formData = {
                action: 'buscar',
                pais: currentCountry,
                rubro: $('#filtro_rubro').val(),
                categoria: $('#filtro_categoria').val(),
                temporada: $('#filtro_temporada').val()
            };

            $('#btnBuscar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Consultando Pivot...');

            $.ajax({
                url: 'controller/stockSupply_ajax.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(res) {
                    $('#btnBuscar').prop('disabled', false).html('<i class="fas fa-search me-1"></i> Buscar');
                    if (res.success) {
                        renderPivotTable(res.columns, res.data);
                        $('#total-badge').text(res.total.toLocaleString('es-AR'));
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudieron obtener datos del pivot.' });
                    }
                },
                error: function() {
                    $('#btnBuscar').prop('disabled', false).html('<i class="fas fa-search me-1"></i> Buscar');
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
                }
            });
        }

        function renderPivotTable(columns, data) {
            if (dataTable) {
                dataTable.destroy();
                $('#pivotTable').empty();
            }

            if (!columns || !columns.length) {
                $('#pivotTable').html('<thead><tr><th>Sin columnas</th></tr></thead><tbody><tr><td>No hay registros para mostrar.</td></tr></tbody>');
                return;
            }

            let dtColumns = [];
            let headerHtml = '<tr>';
            columns.forEach(col => {
                headerHtml += `<th>${col}</th>`;
                let isNumericCol = false;
                for (let i = 0; i < Math.min(data.length, 25); i++) {
                    if (data[i] && typeof data[i][col] === 'number') {
                        isNumericCol = true;
                        break;
                    }
                }

                dtColumns.push({
                    data: col,
                    title: col,
                    type: isNumericCol ? 'num' : 'string',
                    className: isNumericCol ? 'text-end' : '',
                    defaultContent: isNumericCol ? 0 : '',
                    render: function(val, type) {
                        if (type === 'display') {
                            if (typeof val === 'number') {
                                return val.toLocaleString('es-AR');
                            }
                            return val !== null && val !== undefined && val !== '' ? val : '-';
                        }
                        if (type === 'sort' || type === 'type') {
                            return typeof val === 'number' ? val : (isNumericCol ? (parseFloat(val) || 0) : (val || ''));
                        }
                        return val;
                    }
                });
            });
            headerHtml += '</tr>';

            $('#pivotTable').html(`<thead>${headerHtml}</thead><tbody></tbody>`);

            dataTable = $('#pivotTable').DataTable({
                data: data,
                columns: dtColumns,
                dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"lip>',
                buttons: [
                    { extend: 'copyHtml5', className: 'btn btn-dt-copy', text: '<i class="fas fa-copy me-1"></i> Copiar' },
                    { extend: 'excelHtml5', className: 'btn btn-dt-excel', text: '<i class="fas fa-file-excel me-1"></i> Excel' },
                    { extend: 'csvHtml5', className: 'btn btn-dt-csv', text: '<i class="fas fa-file-csv me-1"></i> CSV' },
                    { extend: 'colvis', className: 'btn btn-dt-colvis', text: '<i class="fas fa-columns me-1"></i> Columnas' }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                scrollY: '58vh',
                scrollX: true,
                scrollCollapse: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                order: [[0, 'asc']]
            });
            setTimeout(() => { if (dataTable) dataTable.columns.adjust(); }, 50);
        }

        function reloadFilters() {
            $.ajax({
                url: 'controller/stockSupply_ajax.php',
                type: 'POST',
                data: { action: 'getFiltros', pais: currentCountry },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let rubHtml = '<option value="">Todos los Rubros</option>';
                        res.rubros.forEach(r => { rubHtml += `<option value="${r}">${r}</option>`; });
                        $('#filtro_rubro').html(rubHtml);

                        let catHtml = '<option value="">Todas las Categorías</option>';
                        res.categorias.forEach(c => { catHtml += `<option value="${c}">${c}</option>`; });
                        $('#filtro_categoria').html(catHtml);

                        let tempHtml = '<option value="">Todas las Temporadas</option>';
                        res.temporadas.forEach(t => { tempHtml += `<option value="${t}">${t}</option>`; });
                        $('#filtro_temporada').html(tempHtml);

                        loadPivotData();
                    }
                }
            });
        }

        // Init
        loadPivotData();

        // Eventos
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            loadPivotData();
        });

        $('#country-toggle button').on('click', function() {
            const country = $(this).data('country');
            if (country === currentCountry) return;
            
            $('#country-toggle button').removeClass('active');
            $(this).addClass('active');
            currentCountry = country;

            reloadFilters();
        });

        $(window).on('resize', function() {
            if (dataTable) dataTable.columns.adjust();
        });
    });
    </script>
</body>
</html>
