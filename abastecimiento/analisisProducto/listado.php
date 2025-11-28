
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

                    <div class="col-md-2">
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

                    <div class="col-md-2">
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
                    
<div class="col-md-3 d-flex align-items-end gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-search me-2"></i>Buscar
    </button>
    <button type="button" id="exportXlsx" class="btn btn-success">
        <i class="fas fa-file-excel me-2"></i>Excel (XLSX)
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

    let dataTable;

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

    // Manejar envío del formulario
    $('#filtroForm').on('submit', function(e) {
        e.preventDefault();
        buscarProductos();
    });

    // ==============================================================================
    // NUEVA LÓGICA DE EXPORTACIÓN CENTRALIZADA
    // ==============================================================================

    // Función genérica para manejar la exportación
    function handleExport(button, url) {
        // 'button' es el objeto jQuery del botón presionado
        // 'url' es el endpoint del controlador PHP
        
        console.log(`Iniciando exportación a: ${url}`); // Para depuración

        const token = new Date().getTime();
        const buttonOriginalText = button.html();

        const paramsObj = {
            rubro: $('#rubro').val(),
            categoria: $('#categoria').val(),
            temporada: $('#temporada').val(),
            destino: $('#destino').val(),
            search: dataTable ? dataTable.search() : '',
            download_token: token
        };

        const params = $.param(paramsObj);

        $('#spinner').css('display', 'flex');
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Generando...');

        window.location.href = url + '?' + params;

        const checkDescarga = setInterval(function() {
            if (document.cookie.indexOf('download_token_completo=' + token) > -1) {
                finishDownload();
            }
        }, 500);

        function finishDownload() {
            clearInterval(checkDescarga);
            $('#spinner').hide();
            button.prop('disabled', false).html(buttonOriginalText);
            document.cookie = 'download_token_completo=; Max-Age=0; path=/';
        }

        setTimeout(function() {
            finishDownload();
        }, 60000);
    }

    // Event listener para el botón CSV
    $('#exportExcel').on('click', function() {
        handleExport($(this), 'controller/exportar_productos_excel.php');
    });

    // Event listener para el botón XLSX
    $('#exportXlsx').on('click', function() {
        handleExport($(this), 'controller/exportar_productos_xlsx.php');
    });

    // ==============================================================================
    // FIN DE LA LÓGICA DE EXPORTACIÓN
    // ==============================================================================

function initDataTable() {
        if ($.fn.DataTable.isDataTable('#productosTable')) {
            $('#productosTable').DataTable().destroy();
        }

        dataTable = $('#productosTable').DataTable({
            processing: false, 
            serverSide: true,
            deferLoading: 0, 
            
            // EVENTO 1: Muestra el spinner ANTES de pedir datos (cubre búsqueda rápida, paginación y orden)
            // Esta es la clave para que aparezca la ruedita al dar Enter
            "preDrawCallback": function(settings) {
                $('#spinner').css('display', 'flex'); 
            },

            // EVENTO 2: Oculta el spinner cuando terminan de llegar los datos
            drawCallback: function(settings) {
                 $('#spinner').hide();
            },

            ajax: {
                url: 'controller/obtener_productos.php',
                type: 'POST',
                data: function(d) {
                    d.rubro = $('#rubro').val();
                    d.categoria = $('#categoria').val();
                    d.temporada = $('#temporada').val();
                    d.destino = $('#destino').val();
                },
                error: function(xhr, error, thrown) {
                    console.error('Error:', xhr.responseText);
                    $('#spinner').hide();
                }
            },
            // CONFIGURACIÓN BUSCADOR: Retraso para que no busque mientras escribes (opcional, pero ayudamos con el evento Enter abajo)
            searchDelay: 1000, 
            scrollY: '70vh',
            pageLength: 100,
            lengthMenu: [[50, 100, 200], [50, 100, 200]],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                search: "Buscar:" 
            },
            dom: 'Bfrtip', // Sin botones nativos
            buttons: [],   
            order: [[0, 'asc']],
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
                
                // En tu código anterior mapeaste 'Stock Ecommerce' a la variable STOCK_RESERVA, seguimos esa lógica:
                { data: 'STOCK_RESERVA', render: function(data) { return data ? Number(data).toLocaleString('es-AR') : '0'; } }, 
                
                { data: 'STOCK_EXCLUIDO', render: function(data) { return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                { data: 'CANT_VENTA', render: function(data) { return data ? Number(data).toLocaleString('es-AR') : '0'; } },
                { data: 'IMP_VENTA', render: function(data) { return data ? '$ ' + Number(data).toLocaleString('es-AR') : ''; } }
            ]
        });

        // === MAGIA PARA EL ENTER EN LA CAJA DE BÚSQUEDA ===
        // Desvinculamos el comportamiento por defecto y agregamos el evento 'keypress' solo para Enter (código 13)
        $('.dataTables_filter input')
            .unbind() // Quita la búsqueda automática al escribir
            .bind('keypress', function(e) {
                if(e.which === 13) { // Solo si es ENTER
                    dataTable.search(this.value).draw();
                }
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

function buscarProductos() {
        $('#spinner').css('display', 'flex'); // Mostrar spinner

        // Validamos si dataTable existe.
        if ($.fn.DataTable.isDataTable('#productosTable')) {
            // .draw() fuerza a DataTables a ir al servidor (ignorando el bloqueo inicial)
            dataTable.draw(); 
        } else {
            initDataTable();
            dataTable.draw();
        }
        
        // NOTA: El spinner se debe ocultar en el evento 'drawCallback' 
        // o 'xhr' del DataTable, no aquí inmediatamente, 
        // porque draw() es asíncrono.
    }
         
    document.querySelector(".toggle").style.width="40px"
    document.querySelector(".toggle-on").style.fontSize="0"
    document.querySelector(".toggle-off").style.fontSize="0"
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
