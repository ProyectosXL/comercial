<?php

session_start();

// Cambio de entorno via GET (sin AJAX, más confiable)
if (isset($_GET['setEntorno'])) {
    $_SESSION['entorno'] = ($_GET['setEntorno'] === 'central') ? 'central' : 'uy';
    // Redirigir limpiando el parámetro de la URL
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}


require_once '../Class/Conexion.php';
// ----------------------------------------------


// ----------------------------------------------

require 'Class/Articulo.php';
require '../Class/Rubro.php';
require '../Class/Destino.php';
require '../Class/Temporada.php';

$articuloObj = new Articulo(); // Instanciamos la clase
$ultimaActualizacionDWH = $articuloObj->obtenerFechaUltimaActualizacion(); // Llamamos al nuevo método

$rubroObj = new Rubro();
$todosLosRubros = $rubroObj->traerRubrosDestinos();

$destinoObj = new Destino();
$todosLosDestinos = $destinoObj->traerDestinos();

$temporadaObj = new Temporada();
$todasLasTemporadas = $temporadaObj->traerTemporadas();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");


if (!isset($_SESSION['entorno'])) {
    $_SESSION['entorno'] = 'central';
}

if ($_SESSION['entorno'] == 'central') {
    $checkedValue = 'checked';
    $dataOnValue = 'ARG';
    $dataOffValue = 'UY';
    $imageOn = '../css/bandera_con_sol__55757_std.jpg';
    $imageOff = '../css/UY.png';
} else {
    $checkedValue = '';
    $dataOnValue = 'ARG';
    $dataOffValue = 'UY';
    $imageOn = '../css/UY.png';
    $imageOff = '../css/bandera_con_sol__55757_std.jpg';
}


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis de Producto</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">


    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css" class="rel">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />


    <style>
        /* Adaptar ancho de selectores Temporada y Destino según contenido */
        #temporada+.select2-container,
        #destino+.select2-container {
            min-width: 120px !important;
        }

        #temporada+.select2-container .select2-selection--multiple,
        #destino+.select2-container .select2-selection--multiple {
            min-height: 38px;
        }

        /* Hacer que los botones sean más compactos */
        .btn-compact {
            white-space: nowrap;
            padding: 0.375rem 0.75rem;
        }

        /* Switch de entorno */
        .entorno-switch {
            display: inline-flex;
            background: #f1f3f5;
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 3px;
            gap: 2px;
        }
        .entorno-option {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 5px 16px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 700;
            color: #868e96;
            transition: background 0.22s, color 0.22s, box-shadow 0.22s;
            user-select: none;
        }
        .entorno-option.active {
            background: #6f42c1;
            color: #fff;
            box-shadow: 0 2px 8px rgba(111,66,193,0.18);
        }
        .entorno-option .flag-icon {
            width: 22px;
            height: 22px;
            object-fit: cover;
            border-radius: 50%;
            border: 1.5px solid rgba(255,255,255,0.4);
        }
    </style>

</head>

<body class="bg-light">

    <!-- Spinner -->
    <div class="spinner-overlay" id="spinner">
        <div class="spinner-border text-primary" role="status" style="width: 6rem; height: 6rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div class="container-fluid py-4">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2 text-primary"></i>Consulta Análisis de Producto
                    </h5>
                    <span style="font-size:0.75rem; color:#adb5bd; font-weight:400;">
                        <i class="fas fa-clock me-1"></i>Última actualización DWH: <?php echo $ultimaActualizacionDWH; ?> hs
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-size:0.8rem;color:#868e96;">Entorno:</span>
                        <div class="entorno-switch">
                            <div class="entorno-option <?= $_SESSION['entorno'] == 'central' ? 'active' : '' ?>" onclick="cambiarEntorno('central')">
                                <img src="../css/bandera_con_sol__55757_std.jpg" class="flag-icon" alt="ARG"> ARG
                            </div>
                            <div class="entorno-option <?= $_SESSION['entorno'] != 'central' ? 'active' : '' ?>" onclick="cambiarEntorno('uy')">
                                <img src="../css/UY.png" class="flag-icon" alt="UY"> UY
                            </div>
                        </div>
                    </div>
                </div>


                <form id="filtroForm" class="row g-3">
                    <!-- Modificar los selectores para incluir opción de NULL -->
                    <div class="col-md-3">
                        <label class="form-label">Rubro</label>
                        <select class="form-select" name="rubro[]" id="rubro" multiple="multiple">
                            <option value="NULL">Sin Rubro</option>
                            <?php foreach ($todosLosRubros as $rub): ?>
                                <option value="<?php echo htmlspecialchars($rub['RUBRO']); ?>">
                                    <?php echo htmlspecialchars($rub['RUBRO']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" name="categoria[]" id="categoria" multiple="multiple">
                            <option value="NULL">Sin Categoría</option>
                            <!-- Se carga dinámicamente -->
                        </select>
                    </div>

                    <div class="col-auto">
                        <label class="form-label">Temporada</label>
                        <select class="form-select" name="temporada[]" id="temporada" multiple="multiple">
                            <option value="NULL">Sin Temporada</option>
                            <?php foreach ($todasLasTemporadas as $temp): ?>
                                <option value="<?php echo htmlspecialchars($temp['TEMPORADA']); ?>">
                                    <?php echo htmlspecialchars($temp['TEMPORADA']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-auto">
                        <label class="form-label">Destino</label>
                        <select class="form-select" name="destino[]" id="destino" multiple="multiple">
                            <option value="NULL">Sin Destino</option>
                            <?php foreach ($todosLosDestinos as $dest): ?>
                                <option value="<?php echo htmlspecialchars($dest['DESTINO']); ?>">
                                    <?php echo htmlspecialchars($dest['DESTINO']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-auto d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary btn-compact">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        <button type="button" id="exportXlsx" class="btn btn-success btn-compact">
                            <i class="fas fa-file-excel me-2"></i>Excel
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-compact dropdown-toggle" type="button" id="colVisDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-columns me-1"></i>Elegir Columnas
                            </button>
                            <ul class="dropdown-menu p-2" aria-labelledby="colVisDropdown" id="colVisMenu" style="min-width:220px;max-height:400px;overflow-y:auto">
                                <li class="px-1 py-1"><small class="text-muted fw-bold">Mostrar / Ocultar columnas</small></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <!-- Generado dinámicamente por actualizarDropdownColumnas() -->
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                    <table class="table table-striped" id="productosTable">
                        <thead>
                            <tr>
                                <th>Rubro</th>
                                <th>Categoría</th>
                                <th>Temporada</th>
                                <th>Destino</th>
                                <th>Liquidacion</th>
                                <th>Fecha Ingreso</th>
                                <th>Cód. Equiv.</th>
                                <th>Artículo</th>
                                <th>Descripción</th>
                                <th>Color</th>
                                <th>Precio</th>
                                <th>Stock Locales</th>
                                <th>Stock Central</th>
                                <th>Stock Fiscal</th>
                                <th>Stock Total</th>
                                <th>Stock Ecommerce</th>
                                <th>Stock Excluido</th>
                                <th>Cant. Venta</th>
                                <th>Venta Total</th>
                                <th>Cant. Ingr. Central</th>
                                <th>Venta Franq.</th>
                                <th>Venta May.</th>
                                <th>Venta Ecomm.</th>
                                <th>Venta Propios</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/equivalencias.js"></script>

    <script>
        $(document).ready(function () {
            // Inicializar Select2 en los selectores
            $('#rubro, #categoria, #temporada, #destino').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Seleccionar...',
                allowClear: true,
                closeOnSelect: false
            });


            // Inicializar DataTable
            initDataTable();

            // Cargar todas las categorías inicialmente
            cargarCategorias();

            // Event listener para cambio en rubro
            $('#rubro').on('change', function () {
                const rubrosSeleccionados = $(this).val() || [];
                $('#categoria').val(null).trigger('change');
                cargarCategoriasPorRubro(rubrosSeleccionados);
            });

        });
        // ==============================================================================
        // NUEVA LÓGICA DE EXPORTACIÓN CENTRALIZADA
        // ==============================================================================

        // Función genérica para manejar la exportación
        function exportarDirectoExcel() {
            console.log("Iniciando exportación directa con filtros...");

            const button = $('#exportXlsx');
            const buttonOriginalHtml = button.html();
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Preparando...');

            // 1. Recolectar TODOS los filtros actuales de los selectores y la búsqueda.
            //    Usamos .val() que para los selectores múltiples devuelve un array.
            const params = {
                rubro: $('#rubro').val() || [],
                categoria: $('#categoria').val() || [],
                temporada: $('#temporada').val() || [],
                destino: $('#destino').val() || [],
                search: dataTable ? dataTable.search() : '' // Incluye la búsqueda de la tabla
            };

            // 2. Construir la cadena de consulta (query string).
            //    jQuery.param maneja correctamente los arrays para que PHP los reciba bien.
            const queryString = $.param(params);

            // 3. Construir la URL final y redirigir para iniciar la descarga.
            const url = `controller/exportar_dwh_excel.php?${queryString}`;
            console.log("URL de exportación generada:", url);

            // 4. Iniciar la descarga.
            window.location.href = url;

            // 5. Restaurar el botón después de unos segundos.
            setTimeout(function () {
                button.prop('disabled', false).html(buttonOriginalHtml);
            }, 4000); // 4 segundos para dar tiempo a que inicie la descarga.
        }

        // Event listener para el botón CSV
        $('#exportExcel').on('click', function () {
            handleExport($(this), 'controller/exportar_productos_excel.php');
        });

        $('#exportXlsx').on('click', function (e) {
            e.preventDefault();
            if (dataTable) {
                dataTable.button(0).trigger();
            }
        });

        // Variable para la instancia de DataTable, debe estar fuera de la función
        // para que otras partes del script puedan acceder a ella.
        let dataTable;

        // La función initDataTable se mantiene casi igual, es perfecta para datos del lado del cliente.
        function initDataTable(dataArray) {
            if ($.fn.DataTable.isDataTable('#productosTable')) {
                $('#productosTable').DataTable().destroy();
                $('#productosTable tbody').empty();
            }

            dataTable = $('#productosTable').DataTable({
                data: dataArray,
                processing: true,
                pageLength: 50,
                lengthMenu: [[25, 50, 100, 200, -1], [25, 50, 100, 200, "Todos"]],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json', search: "Buscar en resultados:" },
                dom: '<"d-none"B>frtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        title: 'Analisis de Producto',
                        filename: function () { return 'Analisis_Producto_' + new Date().toISOString().slice(0, 10); },
                        exportOptions: { columns: ':visible', orthogonal: 'export' }
                    }
                ],
                scrollX: true,
                order: [[0, 'asc']],
                columns: [
                    { data: 'RUBRO', defaultContent: '' },
                    { data: 'CATEGORIA', defaultContent: '' },
                    { data: 'TEMPORADA', defaultContent: '' },
                    { data: 'DESTINO', defaultContent: '' },
                    { data: 'LIQUIDACION', defaultContent: '' },
                    { data: 'FECHA_INGRESO_SUC', defaultContent: '' },
                    {
                        data: 'COD_EQUIVALENCIA',
                        render: function (data, type, row) {
                            return window.renderColumnEquivalencia ? window.renderColumnEquivalencia(data, type, row) : (data || '');
                        }
                    },
                    { data: 'ARTICULO', defaultContent: '' },
                    { data: 'DESCRIPCIO', defaultContent: '' },
                    { data: 'COLOR', defaultContent: '' },
                    { data: 'PRECIO', render: function (data, type) { if (type === 'sort' || type === 'filter') return data ? Number(data) : 0; return data ? '$ ' + Number(data).toLocaleString('es-AR') : '$ 0'; } },
                    { data: 'STOCK_LOCALES', render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'STOCK_CENTRAL', render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'STOCK_CENTRAL_FISCAL', render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'STOCK_TOTAL', render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'STOCK_RESERVA', render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'STOCK_EXCLUIDO', render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'CANT_VENTA', render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'IMP_VENTA', render: function (data, type) { if (type === 'sort' || type === 'filter') return data ? Number(data) : 0; return data ? '$ ' + Number(data).toLocaleString('es-AR') : '$ 0'; } },
                    { data: 'CANT_INGRESO_CENTRAL', defaultContent: '0', visible: false, render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'VENTA_FRANQ', defaultContent: '0', visible: false, render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'VENTA_MAY', defaultContent: '0', visible: false, render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'VENTA_ECOMM', defaultContent: '0', visible: false, render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                    { data: 'VENTA_PROPIOS', defaultContent: '0', visible: false, render: function (data, type) { if (type !== 'display') return data ? Number(data) : 0; return data ? Number(data).toLocaleString('es-AR') : '0'; } }
                ]
            });

            $('.dataTables_filter input').unbind().bind('keypress', function (e) {
                if (e.which === 13) {
                    dataTable.search(this.value).draw();
                }
            });

            actualizarDropdownColumnas();
        }

        function actualizarDropdownColumnas() {
            if (!dataTable) return;
            const menu = $('#colVisMenu');
            menu.find('.col-toggle-item').remove();
            dataTable.columns().every(function () {
                const idx = this.index();
                const title = $(this.header()).text().trim();
                const visible = this.visible();
                menu.append(`
                    <li class="col-toggle-item">
                        <label class="dropdown-item d-flex align-items-center gap-2" onclick="event.stopPropagation()">
                            <input type="checkbox" class="col-toggle" data-col-idx="${idx}" ${visible ? 'checked' : ''}> ${title}
                        </label>
                    </li>`);
            });
        }

        function cargarCategorias() {
            console.log('=== Cargando todas las categorías ===');

            $.ajax({
                url: 'controller/equivalencias_controller.php',
                method: 'GET',
                data: { accion: 'obtener_categorias' },
                dataType: 'json',
                timeout: 10000,
                success: function (response) {
                    console.log('Respuesta categorías recibida:', response);

                    if (response.success && response.data) {
                        actualizarSelectCategorias(response.data, 'Todas las categorías cargadas');
                    } else {
                        console.error('Error en respuesta de categorías:', response.message || 'Sin mensaje de error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error al cargar categorías:', { xhr, status, error });
                    mostrarErrorCategoria('Error al cargar categorías');
                }
            });
        }

        function cargarCategoriasPorRubro(rubros) {
            console.log('=== Cargando categorías por rubro ===', rubros);

            // Si no hay rubros seleccionados, cargar todas
            if (!rubros || rubros.length === 0) {
                cargarCategorias();
                return;
            }

            $.ajax({
                url: 'controller/equivalencias_controller.php',
                method: 'GET',
                data: {
                    accion: 'obtener_categorias_por_rubro',
                    rubros: rubros
                },
                dataType: 'json',
                timeout: 10000,
                success: function (response) {
                    console.log('Respuesta categorías por rubro:', response);

                    if (response.success && response.data) {
                        const mensaje = `Categorías filtradas para ${rubros.length} rubro(s)`;
                        actualizarSelectCategorias(response.data, mensaje);
                    } else {
                        console.error('Error en respuesta de categorías por rubro:', response.message);
                        mostrarErrorCategoria('Error al filtrar categorías');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error al cargar categorías por rubro:', { xhr, status, error });
                    mostrarErrorCategoria('Error al filtrar categorías por rubro');
                }
            });
        }

        // NUEVA función auxiliar para actualizar el select de categorías:
        function actualizarSelectCategorias(categorias, mensaje) {
            const selectCategoria = $('#categoria');

            // Limpiar opciones existentes (excepto la primera)
            selectCategoria.find('option:not(:first)').remove();

            console.log(mensaje + ':', categorias.length);

            // Agregar nuevas opciones
            categorias.forEach(function (categoria) {
                if (categoria.CATEGORIA) {
                    selectCategoria.append(`<option value="${categoria.CATEGORIA}">${categoria.CATEGORIA}</option>`);
                }
            });

            // Actualizar Select2
            selectCategoria.trigger('change');

            // Mostrar mensaje informativo si no hay categorías
            if (categorias.length === 0) {
                selectCategoria.append('<option disabled>No hay categorías para los rubros seleccionados</option>');
            }
        }

        // NUEVA función auxiliar para mostrar errores:
        function mostrarErrorCategoria(mensaje) {
            console.error(mensaje);
            // Opcionalmente mostrar un toast o alert
            // alert(mensaje);
        }

        let pollingInterval;

        // ==============================================================================
        // ¡NUEVA FUNCIÓN DE BÚSQUEDA SIMPLIFICADA!
        // ==============================================================================
        function buscarProductos() {
            console.log("Iniciando búsqueda directa en el DWH...");

            $('#spinner').css('display', 'flex');
            $('#filtroForm button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Buscando...');

            const postData = {
                rubro: $('#rubro').val(),
                categoria: $('#categoria').val(),
                temporada: $('#temporada').val(),
                destino: $('#destino').val()
            };

            // UNA SOLA LLAMADA AJAX. NO MÁS POLLING.
            $.ajax({
                url: 'controller/obtener_productos_dwh.php', // ¡Llamamos a nuestro nuevo script!
                type: 'POST',
                data: postData,
                dataType: 'json',
                timeout: 90000, // 90 segundos de timeout, por si acaso
                success: function (response) {
                    console.log("Datos recibidos:", response.length, "registros.");
                    // Directamente cargamos los datos en la tabla. ¡Listo!
                    initDataTable(response);
                },
                error: function (xhr) {
                    alert('Ocurrió un error al buscar los productos. Revisa la consola (F12).');
                    console.error("Error en la llamada AJAX:", xhr.responseText);
                    initDataTable([]); // Limpiamos la tabla en caso de error
                },
                complete: function () {
                    // Se ejecuta siempre al final (éxito o error), para limpiar la UI.
                    $('#spinner').hide();
                    $('#filtroForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-search me-2"></i>Buscar');
                }
            });
        }


        let pollingTimeoutId;

        function pollForResults(taskId) {
            console.log("6. Iniciando sondeo INTELIGENTE para taskId:", taskId);

            // Limpiar cualquier sondeo anterior por si acaso
            if (pollingTimeoutId) {
                clearTimeout(pollingTimeoutId);
            }

            // Poner el mensaje de espera en la tabla
            $('#productosTable tbody').html(
                '<tr><td colspan="19" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> La consulta se está procesando en el servidor, esto puede tardar varios minutos...</td></tr>'
            );

            // Función que se llamará a sí misma para verificar el resultado
            function checkStatus() {
                console.log("7. Preguntando al servidor por el resultado...");

                $.ajax({
                    url: 'controller/verificar_resultado.php',
                    type: 'POST',
                    data: { taskId: taskId },
                    dataType: 'json',
                    // El timeout aquí ahora es solo para esta petición específica
                    // Lo ponemos un poco más alto que el timeout del servidor (60s) para estar seguros
                    timeout: 70000, // 70 segundos

                    success: function (response) {
                        if (response.status === 'complete') {
                            // ¡ÉXITO! Lo encontramos.
                            console.log("¡Resultados recibidos! Cargando tabla.");
                            initDataTable(response.data);
                            resetUI();
                            // No llamamos a checkStatus() de nuevo, terminando el ciclo.
                        } else if (response.status === 'processing') {
                            // El servidor respondió que sigue trabajando. Esperamos 5 segundos y volvemos a preguntar.
                            console.log("El servidor sigue procesando. Volveremos a preguntar en 5 segundos.");
                            pollingTimeoutId = setTimeout(checkStatus, 5000);
                        } else {
                            // El servidor respondió con un error en el JSON
                            alert('Ocurrió un error en el servidor al procesar la consulta: ' + response.error);
                            resetUI();
                        }
                    },
                    error: function (xhr, status, error) {
                        // Si la petición de verificación falla (ej. timeout de 60s del servidor),
                        // no lo tratamos como un error fatal. Simplemente esperamos y lo intentamos de nuevo.
                        if (status === 'timeout' || xhr.status === 504) {
                            console.warn("La verificación del resultado falló por timeout. Reintentando en 5 segundos...");
                            pollingTimeoutId = setTimeout(checkStatus, 5000); // Volvemos a llamar a la función
                        } else {
                            // Un error real (ej. red caída, error 500)
                            alert('Se perdió la conexión al verificar el resultado. Error: ' + status);
                            console.error("Error grave en la llamada de sondeo:", status, error);
                            resetUI();
                        }
                    }
                });
            }

            // Iniciar el primer chequeo inmediatamente
            checkStatus();
        }

        let pollingExportTimeoutId;

        function pollForExport(taskId) {
            console.log("Iniciando sondeo de EXPORTACIÓN INTELIGENTE para taskId:", taskId);

            // Limpiar cualquier sondeo anterior
            if (pollingExportTimeoutId) {
                clearTimeout(pollingExportTimeoutId);
            }

            // Función que se llamará a sí misma para verificar el resultado
            function checkExportStatus() {
                console.log("Preguntando al servidor por el archivo Excel...");

                $.ajax({
                    url: 'controller/verificar_exportacion.php',
                    type: 'POST',
                    data: { taskId: taskId },
                    dataType: 'json',
                    // Timeout del cliente para esta petición específica
                    timeout: 70000, // 70 segundos

                    success: function (response) {
                        if (response.status === 'complete') {
                            // ¡ÉXITO! El archivo está listo.
                            console.log("¡Archivo listo para descargar!", response.fileUrl);

                            // --- AQUÍ VA LA SOLUCIÓN AL PUNTO 2 ---
                            // Disparamos la descarga y finalizamos la UI
                            downloadFileAndFinish(response.fileUrl);

                        } else if (response.status === 'processing') {
                            // El servidor respondió que sigue trabajando. Volvemos a preguntar en 5 segundos.
                            console.log("El servidor sigue generando el archivo. Reintentando en 5 segundos.");
                            pollingExportTimeoutId = setTimeout(checkExportStatus, 5000);
                        } else {
                            // El servidor respondió con un error en el JSON
                            alert('Ocurrió un error en el servidor al generar el archivo: ' + response.error);
                            resetExportUI();
                        }
                    },
                    error: function (xhr, status, error) {
                        // Si la petición falla por timeout del servidor (504), reintentamos.
                        if (status === 'timeout' || xhr.status === 504) {
                            console.warn("La verificación de la exportación falló por timeout. Reintentando en 5 segundos...");
                            pollingExportTimeoutId = setTimeout(checkExportStatus, 5000);
                        } else {
                            // Un error de red real
                            alert('Se perdió la conexión al verificar el estado de la exportación. Error: ' + status);
                            console.error("Error grave en el sondeo de exportación:", status, error);
                            resetExportUI();
                        }
                    }
                });
            }

            // Iniciar el primer chequeo
            checkExportStatus();
        }

        // ESTA ES LA FUNCIÓN CLAVE QUE HACE LA DESCARGA Y LUEGO LIMPIA
        function downloadFileAndFinish(fileUrl) {
            $('#spinner-msg').text('¡Archivo listo! Iniciando descarga...');
            console.log("Iniciando descarga desde la URL:", fileUrl);

            // 1. Iniciar la descarga en el navegador del usuario
            const link = document.createElement('a');
            link.href = fileUrl;
            link.setAttribute('download', '');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // 2. Esperar un tiempo prudencial (ej. 10-15 segundos) para que la descarga comience
            // y luego enviar la petición para borrar el archivo del servidor.
            const cleanupDelay = 15000; // 15 segundos
            console.log(`Esperando ${cleanupDelay / 1000} segundos antes de solicitar la limpieza del archivo.`);

            setTimeout(function () {
                // Extraemos solo el nombre del archivo de la URL
                const filename = fileUrl.split('/').pop();

                console.log("Enviando petición para limpiar el archivo del servidor:", filename);

                $.ajax({
                    url: 'controller/limpiar_exportacion.php', // El script de limpieza que ya creamos
                    type: 'POST',
                    data: { filename: filename },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            console.log("Respuesta del script de limpieza:", response.message);
                        } else {
                            console.error("El script de limpieza reportó un error:", response.error);
                        }
                    },
                    error: function () {
                        console.error("Falló la petición Ajax para limpiar el archivo del servidor.");
                    }
                });

            }, cleanupDelay);

            // 3. Limpiar la interfaz de usuario (esto se hace rápido, independientemente de la limpieza)
            setTimeout(function () {
                resetExportUI();
            }, 2000);
        }

        function resetExportUI() {
            $('#spinner').hide();
            $('#filtroForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-search me-2"></i>Buscar');
        }

        function resetUI() {
            $('#spinner').hide();
            $('#filtroForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-search me-2"></i>Buscar');
        }

        // Toggle de visibilidad de columnas adicionales
        $(document).on('change', '.col-toggle', function () {
            const colIdx = parseInt($(this).data('col-idx'));
            if (dataTable) {
                dataTable.column(colIdx).visible(this.checked);
            }
        });

        // Asegúrate de que tu formulario llame a buscarProductos()
        $('#filtroForm').on('submit', function (e) {
            e.preventDefault();
            buscarProductos();
        });

        const convertirFecha = (fechaObj) => {
            // Crear un objeto Date desde el campo `date` del objeto
            const fecha = new Date(fechaObj.date);

            // Validar si la fecha es válida
            if (isNaN(fecha)) {
                return 'Fecha inválida'; // Manejo de errores si el formato no es válido
            }

            // Formatear la fecha como "dd/MM/yyyy"
            const dia = fecha.getDate().toString().padStart(2, '0'); // Día con 2 dígitos
            const mes = (fecha.getMonth() + 1).toString().padStart(2, '0'); // Mes con 2 dígitos (0-indexed)
            const anio = fecha.getFullYear();

            return `${dia}/${mes}/${anio}`;
        };


        const cambiarEntorno = (entorno) => {
            window.location.href = '?setEntorno=' + entorno;
        }

    </script>

    <?php include 'components/modal_equivalencias.php'; ?>

</body>

</html>