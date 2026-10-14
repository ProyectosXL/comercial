<?php
if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}
require_once __DIR__ . '/Class/StockSucursales.php';

Auth::exigir();

$stockObj   = new StockSucursales();
$sucursales = $stockObj->getSucursalesLista('AR');
$temporadas = $stockObj->getTemporadas('AR');
$rubros     = $stockObj->getRubros('AR');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Sucursales · XL Extra Large</title>
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
            --primary: #059669;
            --primary-dark: #047857;
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
            color: #34d399;
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
            border-color: #059669 !important;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15) !important;
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
            background-color: #059669 !important;
            border-color: #059669 !important;
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
                    <li class="breadcrumb-item"><a href="/centralizadores/comer/index.php#card_locales" class="text-decoration-none text-muted">Sucursales y Franquicias</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Stock Sucursales</li>
                </ol>
            </nav>
            <h1 class="page-title">
                <i class="bi bi-shop"></i> Stock Sucursales
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
                    <label for="filtro_sucursal" class="form-label text-muted fw-semibold small mb-1">Sucursal</label>
                    <select class="form-select form-select-sm" id="filtro_sucursal" name="sucursal">
                        <option value="">Todas las Sucursales</option>
                        <?php foreach ($sucursales as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
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

                <div class="col-md-3">
                    <label for="filtro_rubro" class="form-label text-muted fw-semibold small mb-1">Rubro</label>
                    <select class="form-select form-select-sm" id="filtro_rubro" name="rubro">
                        <option value="">Todos los Rubros</option>
                        <?php foreach ($rubros as $r): ?>
                            <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-center justify-content-between gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold flex-grow-1" id="btnBuscar">
                        <i class="fas fa-search me-1"></i> Buscar
                    </button>
                    <div class="badge-total text-nowrap">
                        Total Stock: <span id="total-badge">0</span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Card de Tabla -->
        <div class="table-card">
            <div class="table-responsive">
                <table id="stockSucTable" class="table table-hover table-striped w-100 align-middle">
                    <thead>
                        <tr>
                            <th>Artículo</th>
                            <th>Descripción</th>
                            <th class="text-center">Cod Suc</th>
                            <th>Sucursal</th>
                            <th class="text-center">Depósito</th>
                            <th class="text-end">Stock</th>
                            <th>Rubro</th>
                            <th>Categoría</th>
                            <th>Temporada</th>
                            <th>Destino</th>
                            <th class="text-end">Precio</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
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

        function initDataTable() {
            dataTable = $('#stockSucTable').DataTable({
                dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"lip>',
                buttons: [
                    { extend: 'copyHtml5', className: 'btn btn-dt-copy', text: '<i class="fas fa-copy me-1"></i> Copiar' },
                    { extend: 'excelHtml5', className: 'btn btn-dt-excel', text: '<i class="fas fa-file-excel me-1"></i> Excel' },
                    { extend: 'csvHtml5', className: 'btn btn-dt-csv', text: '<i class="fas fa-file-csv me-1"></i> CSV' },
                    { extend: 'pdfHtml5', className: 'btn btn-dt-pdf', text: '<i class="fas fa-file-pdf me-1"></i> PDF', orientation: 'landscape', pageSize: 'A4' },
                    { extend: 'print', className: 'btn btn-dt-print', text: '<i class="fas fa-print me-1"></i> Imprimir' },
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
                order: [[3, 'asc'], [0, 'asc']],
                columns: [
                    { data: 'articulo', className: 'fw-bold text-dark' },
                    { data: 'descripcion' },
                    { data: 'cod_client', className: 'text-center' },
                    { data: 'desc_sucursal', className: 'fw-semibold' },
                    { data: 'cod_deposi', className: 'text-center' },
                    { data: 'cant_stock', className: 'text-end fw-bold text-success', render: $.fn.dataTable.render.number('.', ',', 0) },
                    { data: 'rubro' },
                    { data: 'categoria' },
                    { data: 'temporada' },
                    { data: 'destino' },
                    { data: 'precio', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '$ ') }
                ]
            });
        }

        function loadData() {
            const formData = {
                action: 'buscar',
                pais: currentCountry,
                sucursal: $('#filtro_sucursal').val(),
                temporada: $('#filtro_temporada').val(),
                rubro: $('#filtro_rubro').val()
            };

            $('#btnBuscar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Buscando...');

            $.ajax({
                url: 'controller/stockSucursales_ajax.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(res) {
                    $('#btnBuscar').prop('disabled', false).html('<i class="fas fa-search me-1"></i> Buscar');
                    if (res.success) {
                        dataTable.clear();
                        dataTable.rows.add(res.data).draw();
                        setTimeout(() => { if (dataTable) dataTable.columns.adjust(); }, 50);

                        let totalStock = 0;
                        res.data.forEach(item => {
                            totalStock += parseFloat(item.cant_stock || 0);
                        });
                        $('#total-badge').text(totalStock.toLocaleString('es-AR'));
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudieron obtener datos.' });
                    }
                },
                error: function() {
                    $('#btnBuscar').prop('disabled', false).html('<i class="fas fa-search me-1"></i> Buscar');
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
                }
            });
        }

        function reloadFilters() {
            $.ajax({
                url: 'controller/stockSucursales_ajax.php',
                type: 'POST',
                data: { action: 'getFiltros', pais: currentCountry },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let sucHtml = '<option value="">Todas las Sucursales</option>';
                        res.sucursales.forEach(s => { sucHtml += `<option value="${s}">${s}</option>`; });
                        $('#filtro_sucursal').html(sucHtml);

                        let tempHtml = '<option value="">Todas las Temporadas</option>';
                        res.temporadas.forEach(t => { tempHtml += `<option value="${t}">${t}</option>`; });
                        $('#filtro_temporada').html(tempHtml);

                        let rubHtml = '<option value="">Todos los Rubros</option>';
                        res.rubros.forEach(r => { rubHtml += `<option value="${r}">${r}</option>`; });
                        $('#filtro_rubro').html(rubHtml);

                        loadData();
                    }
                }
            });
        }

        // Init
        initDataTable();
        loadData();

        // Eventos
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            loadData();
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
