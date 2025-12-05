
<?php

session_start();

require 'Class/Articulo.php';
require '../Class/Rubro.php';
require '../Class/Destino.php';
require '../Class/Temporada.php';

$rubroObj = new Rubro();
$todosLosRubros = $rubroObj->traerRubrosDestinos();

$destinoObj = new Destino();
$todosLosDestinos = $destinoObj->traerDestinos();

$temporadaObj = new Temporada();
$todasLasTemporadas = $temporadaObj->traerTemporadas();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");



$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$dataOnValue = ($checkedValue === 'uy') ? 'UY' : 'ARG';
$dataOffValue = ($checkedValue === 'uy') ? 'ARG' : 'UY';
$imageOn = ($checkedValue === 'central') ? '../css/bandera_con_sol__55757_std.jpg' : '../css/UY.png';
$imageOff = ($checkedValue === 'central') ? '../css/UY.png' : '../css/bandera_con_sol__55757_std.jpg';

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
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />


    <style>
    .toggle-on {
        background-image: url('<?= $imageOn ?>');
        background-size: contain;
        background-repeat: no-repeat;
        height: 60px;
        width: 60px;
    }

    .toggle-off {
        background-image: url('<?= $imageOff ?>');
        background-size: contain;
        background-repeat: no-repeat;
        height: 60px;
        width: 60px;
    }

    /* Adaptar ancho de selectores Temporada y Destino según contenido */
    #temporada + .select2-container,
    #destino + .select2-container {
        min-width: 120px !important;
    }

    #temporada + .select2-container .select2-selection--multiple,
    #destino + .select2-container .select2-selection--multiple {
        min-height: 38px;
    }

    /* Hacer que los botones sean más compactos */
    .btn-compact {
        white-space: nowrap;
        padding: 0.375rem 0.75rem;
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
                <h4 class="card-title mb-4">
                    <i class="fas fa-chart-line me-2"></i>
                    <span style="margin-right:50%">Consulta Analisis de Producto</span>
                    <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0; " onchange="cambiarEntorno(this)" id="checkEntorno" >
                </h4>

                
                <form id="filtroForm" class="row g-3">
                    <!-- Modificar los selectores para incluir opción de NULL -->
                    <div class="col-md-3">
                        <label class="form-label">Rubro</label>
                        <select class="form-select" name="rubro[]" id="rubro" multiple="multiple">
                            <option value="NULL">Sin Rubro</option>
                            <?php foreach($todosLosRubros as $rub): ?>
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
                            <?php foreach($todasLasTemporadas as $temp): ?>
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
                            <?php foreach($todosLosDestinos as $dest): ?>
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
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
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
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
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
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/equivalencias.js"></script>

<script>
$(document).ready(function() {
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
    $('#rubro').on('change', function() {
        const rubrosSeleccionados = $(this).val() || [];
        $('#categoria').val(null).trigger('change');
        cargarCategoriasPorRubro(rubrosSeleccionados);
    });

});
    // ==============================================================================
    // NUEVA LÓGICA DE EXPORTACIÓN CENTRALIZADA
    // ==============================================================================

    // Función genérica para manejar la exportación
function handleExport(button, url) {
    console.log(`Iniciando exportación ASÍNCRONA`);
    
    const buttonOriginalText = button.html();
    const paramsObj = {
        rubro: $('#rubro').val(),
        categoria: $('#categoria').val(),
        temporada: $('#temporada').val(),
        destino: $('#destino').val(),
        search: dataTable ? dataTable.search() : ''
    };

    // Mostrar spinner y deshabilitar botón
    $('#spinner').css('display', 'flex').find('.spinner-border').after('<p id="spinner-msg" style="margin-top:20px;">Iniciando generación de Excel...</p>');
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Generando...');

    // PASO A: Iniciar la generación del archivo en el servidor
    $.ajax({
        url: 'controller/iniciar_exportacion.php', // NUEVO SCRIPT
        type: 'POST',
        data: paramsObj,
        dataType: 'json',
        success: function(response) {
            if (response && response.success && response.taskId) {
                // PASO C: Iniciar el sondeo para verificar si el archivo está listo
                $('#spinner-msg').text('El servidor está procesando tu archivo Excel. Esto puede tardar varios minutos...');
                pollForExport(response.taskId);
            } else {
                alert('Error al iniciar la exportación: ' + (response.error || 'Respuesta inválida'));
                resetExportUI();
            }
        },
        error: function() {
            alert('No se pudo comunicar con el servidor para iniciar la exportación.');
            resetExportUI();
        }
    });
}

    // Event listener para el botón CSV
    $('#exportExcel').on('click', function() {
        handleExport($(this), 'controller/exportar_productos_excel.php');
    });

    // Event listener para el botón XLSX
    $('#exportXlsx').on('click', function() {
        handleExport($(this), 'controller/exportar_productos_xlsx.php');
    });

// Variable para la instancia de DataTable, debe estar fuera de la función
// para que otras partes del script puedan acceder a ella.
let dataTable; 

/**
 * Inicializa o reinicia la DataTable con un conjunto de datos ya cargado.
 * Esta versión funciona en modo "client-side".
 * @param {Array} dataArray - El array de objetos con los datos de los productos.
 */
function initDataTable(dataArray) {
    
    // 1. Destruimos la tabla si ya existe para poder reiniciarla con nuevos datos.
    if ($.fn.DataTable.isDataTable('#productosTable')) {
        $('#productosTable').DataTable().destroy();
        // Es una buena práctica vaciar el cuerpo de la tabla para evitar un "parpadeo" de datos viejos.
        $('#productosTable tbody').empty();
    }

    // 2. Inicializamos DataTables.
    dataTable = $('#productosTable').DataTable({
        // ==============================================================================
        // CAMBIOS PRINCIPALES PARA EL MODO CLIENT-SIDE
        // ==============================================================================
        
        // ¡LA LÍNEA MÁS IMPORTANTE! Le decimos a DataTables que use el array que le pasamos.
        data: dataArray,

        // Ya no necesitamos 'serverSide', 'ajax', ni callbacks relacionados con la carga.
        // serverSide: false, // Se comenta porque 'false' es el valor por defecto.
        // deferLoading: 0,  // Ya no aplica.
        // preDrawCallback: function(settings) { ... }, // Se elimina, el spinner se maneja fuera.
        // drawCallback: function(settings) { ... },  // Se elimina, el spinner se maneja fuera.
        // ajax: { ... }, // ¡SE ELIMINA COMPLETAMENTE! Los datos ya los tenemos.

        // ==============================================================================
        // CONFIGURACIONES QUE SE MANTIENEN PORQUE AFECTAN LA INTERFAZ
        // ==============================================================================

        processing: true, // Muestra "Procesando..." cuando el usuario ordena o busca en los datos ya cargados.
        scrollCollapse: true,
        pageLength: 50,
        lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
        
        // El idioma se mantiene igual.
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
            search: "Buscar:" 
        },
        
        // La configuración del DOM y los botones se mantiene.
        dom: 'Bfrtip', 
        buttons: [],   
        
        // El orden inicial se mantiene.
        order: [[0, 'asc']],

        // ¡IMPORTANTE! La definición de las columnas es exactamente la misma que tenías,
        // ya que los nombres de las propiedades en el 'dataArray' deben coincidir.
        columns: [
            { data: 'RUBRO' },
            { data: 'CATEGORIA' },
            { data: 'TEMPORADA' },
            { data: 'DESTINO' },
            { data: 'LIQUIDACION' },
            { data: 'FECHA_INGRESO_SUC', orderable: false },
            {
                data: 'COD_EQUIVALENCIA',
                render: function(data, type, row) {
                    return window.renderColumnEquivalencia(data, type, row);
                }
            },
            { data: 'ARTICULO' },
            { data: 'DESCRIPCIO' },
            { data: 'COLOR' },
            { data: 'PRECIO', render: function(data) { return data ? '$ ' + Number(data).toLocaleString('es-AR') : ''; } },
            { data: 'STOCK_LOCALES', render: function(data) { return data ? Number(data).toLocaleString('es-AR') : '0'; } },
            { data: 'STOCK_CENTRAL', render: function(data) { return data ? Number(data).toLocaleString('es-AR') : '0'; } },
            { data: 'STOCK_CENTRAL_FISCAL', render: function(data) { return data ? Number(data).toLocaleString('es-AR') : '0'; } },
            { data: 'STOCK_TOTAL', render: function(data) { return data ? Number(data).toLocaleString('es-AR') : '0'; } },
            { data: 'STOCK_RESERVA', render: function(data) { return data ? Number(data).toLocaleString('es-AR') : '0'; } }, 
            { data: 'STOCK_EXCLUIDO', render: function(data) { return data ? Number(data).toLocaleString('es-AR') : '0'; } },
            { data: 'CANT_VENTA', render: function(data) { return data ? Number(data).toLocaleString('es-AR') : '0'; } },
            { data: 'IMP_VENTA', render: function(data) { return data ? '$ ' + Number(data).toLocaleString('es-AR') : ''; } }
        ]
    });

    // 3. Re-aplicamos la lógica para que la búsqueda global se active solo con la tecla "Enter".
    // Esto es útil porque ahora la búsqueda es instantánea (en el cliente) y no queremos
    // que se ejecute con cada tecla si hay miles de filas.
    $('.dataTables_filter input')
        .unbind() // Quita el comportamiento por defecto de DataTables
        .bind('keypress', function(e) {
            if(e.which === 13) { // Si la tecla presionada es Enter
                dataTable.search(this.value).draw();
            }
        });
        
    // 4. Ocultamos el spinner, ya que la tabla está completamente renderizada.
    // Aunque la función `resetUI` también lo hace, es bueno asegurarlo aquí.
    $('#spinner').hide();
}

    function cargarCategorias() {
        console.log('=== Cargando todas las categorías ===');
        
        $.ajax({
            url: 'controller/equivalencias_controller.php',
            method: 'GET',
            data: { accion: 'obtener_categorias' },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                console.log('Respuesta categorías recibida:', response);
                
                if (response.success && response.data) {
                    actualizarSelectCategorias(response.data, 'Todas las categorías cargadas');
                } else {
                    console.error('Error en respuesta de categorías:', response.message || 'Sin mensaje de error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar categorías:', {xhr, status, error});
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
            success: function(response) {
                console.log('Respuesta categorías por rubro:', response);
                
                if (response.success && response.data) {
                    const mensaje = `Categorías filtradas para ${rubros.length} rubro(s)`;
                    actualizarSelectCategorias(response.data, mensaje);
                } else {
                    console.error('Error en respuesta de categorías por rubro:', response.message);
                    mostrarErrorCategoria('Error al filtrar categorías');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar categorías por rubro:', {xhr, status, error});
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
        categorias.forEach(function(categoria) {
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

function buscarProductos() {
    console.log("1. 'buscarProductos' ha sido llamada."); // LOG 1

    $('#spinner').css('display', 'flex');
    $('#filtroForm button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Iniciando...');

    if (pollingInterval) {
        clearInterval(pollingInterval);
    }

    const postData = {
        rubro: $('#rubro').val(),
        categoria: $('#categoria').val(),
        temporada: $('#temporada').val(),
        destino: $('#destino').val()
    };
    
    console.log("2. Enviando datos al servidor:", postData); // LOG 2

    $.ajax({
        url: 'controller/iniciar_consulta.php',
        type: 'POST',
        data: postData,
        dataType: 'json', // Esperamos un JSON estricto
        success: function(response) {
            console.log("4. ¡ÉXITO! Respuesta del servidor recibida:", response); // LOG 4
            
            if (response && response.success && response.taskId) {
                console.log("5. La respuesta es válida. Iniciando sondeo (polling)..."); // LOG 5
                $('#spinner p').remove();
                $('#spinner').append('<p style="margin-top: 20px;">Tu consulta se está procesando...</p>');
                pollForResults(response.taskId);
            } else {
                alert('La respuesta del servidor no fue la esperada. Revisa la consola.');
                console.error("Error lógico en la respuesta:", response);
                resetUI();
            }
        },
        error: function(xhr, status, error) {
            console.error("3. ¡ERROR! La llamada AJAX a iniciar_consulta.php falló."); // LOG 3
            console.error("Status:", status);
            console.error("Error:", error);
            console.error("Response Text:", xhr.responseText); // MUY IMPORTANTE
            alert('Ocurrió un error al comunicarse con el servidor. Revisa la consola (F12) para más detalles.');
            resetUI();
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

            success: function(response) {
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
            error: function(xhr, status, error) {
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

            success: function(response) {
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
            error: function(xhr, status, error) {
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
    
    setTimeout(function() {
        // Extraemos solo el nombre del archivo de la URL
        const filename = fileUrl.split('/').pop();
        
        console.log("Enviando petición para limpiar el archivo del servidor:", filename);

        $.ajax({
            url: 'controller/limpiar_exportacion.php', // El script de limpieza que ya creamos
            type: 'POST',
            data: { filename: filename },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log("Respuesta del script de limpieza:", response.message);
                } else {
                    console.error("El script de limpieza reportó un error:", response.error);
                }
            },
            error: function() {
                console.error("Falló la petición Ajax para limpiar el archivo del servidor.");
            }
        });

    }, cleanupDelay);

    // 3. Limpiar la interfaz de usuario (esto se hace rápido, independientemente de la limpieza)
    setTimeout(function() {
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

// Asegúrate de que tu formulario llame a buscarProductos()
$('#filtroForm').on('submit', function(e) {
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


const cambiarEntorno = (t) => {
  
  let entorno = 0;

  if (t.getAttribute("data-off") == "ARG") {
    entorno = 0;
  } else {
    entorno = 1;
  }

  $.ajax({
    url: "../Controller/maestroDestinosController.php?accion=cambiarEntorno",
    method: "POST",
    data: { entorno: entorno },
    success: function (data) {
      location.reload();
    }
  });
  
}

</script>

<?php include 'components/modal_equivalencias.php'; ?>

</body>
</html>