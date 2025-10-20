/**
 * ============================================
 * EFICIENCIA PEDIDOS - JAVASCRIPT
 * ============================================
 * Este archivo maneja la inicialización y funcionalidad
 * de la página de consulta de pedidos por canal
 * ============================================
 */

$(document).ready(function() {
    // Inicializar componentes
    inicializarComponentes();
    
    // Solo inicializar DataTable si hay datos
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('buscar') === '1') {
        inicializarDataTable();
    }
    
    // Ocultar spinner inicial
    ocultarSpinner();
});

/**
 * Inicializa todos los componentes de la página
 */
function inicializarComponentes() {
    // Inicializar Select2 para el selector de clientes
    $('#selectCliente').select2({
        placeholder: 'Seleccione un cliente',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "No se encontraron resultados";
            },
            searching: function() {
                return "Buscando...";
            }
        }
    });
    
    // Inicializar Bootstrap Toggle para el selector de entorno
    $('#checkEntorno').bootstrapToggle();
    
    // Event listener para cambio de entorno
    $('#checkEntorno').change(function() {
        cambiarEntorno($(this).prop('checked'));
    });
    
    // Event listener para el formulario
    $('#formFiltros').submit(function(e) {
        // Validar que se hayan seleccionado las fechas
        const desde = $('#desde').val();
        const hasta = $('#hasta').val();
        
        if (!desde || !hasta) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Por favor, seleccione las fechas desde y hasta.'
            });
            return false;
        }
        
        // Mostrar spinner y mantenerlo visible
        mostrarSpinner();
        
        // Deshabilitar el botón de buscar para evitar múltiples envíos
        $(this).find('button[type="submit"]').prop('disabled', true);
        
        return true;
    });
    
    // Event listener para exportar
    $('#btnExportar').click(function() {
        exportarAExcel();
    });
    
    // Inicializar tooltips de Bootstrap
    $('[data-toggle="tooltip"]').tooltip();
}

/**
 * Inicializa DataTable con configuración personalizada
 */
function inicializarDataTable() {
    // Verificar que la tabla tenga datos antes de inicializar
    if ($('#tablePedidos tbody tr').length === 0) {
        console.log('No hay datos para inicializar DataTable');
        return;
    }
    
    // Destruir DataTable existente si existe
    if ($.fn.DataTable.isDataTable('#tablePedidos')) {
        $('#tablePedidos').DataTable().destroy();
    }
    
    // Mostrar la tabla
    $('#tablePedidos').show();
    
    // Configurar e inicializar DataTable
    const table = $('#tablePedidos').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
            emptyTable: "No hay datos disponibles",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            lengthMenu: "Mostrar _MENU_ registros",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscar:",
            zeroRecords: "No se encontraron registros coincidentes"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        pageLength: 100,
        lengthMenu: [[25, 50, 100, 200, -1], [25, 50, 100, 200, "Todos"]],
        order: [[0, 'desc']], // Ordenar por fecha descendente
        columnDefs: [
            {
                targets: [10, 11], // Columnas Pedido y Remitido
                className: 'text-end'
            },
            {
                targets: 13, // Columna Estado
                className: 'text-center'
            },
            {
                targets: [0, 1, 2, 3, 4, 7, 8], // Columnas que no deben ajustar el tamaño
                className: 'nowrap'
            }
        ],
        initComplete: function() {
            console.log('DataTable inicializado correctamente');
            
            // Ocultar spinner cuando termine de cargar
            ocultarSpinner();
        },
        drawCallback: function() {
            // Callback después de cada redibujado
        }
    });

    // Agregar funcionalidad de búsqueda avanzada (opcional)
    agregarBusquedaPorColumna(table);
}

/**
 * Agrega funcionalidad de búsqueda por columna
 * @param {DataTable} table - Instancia de DataTable
 */
function agregarBusquedaPorColumna(table) {
    // Puedes agregar filtros adicionales por columna aquí si lo necesitas
    // Por ejemplo, búsqueda por rango de fechas, etc.
}

/**
 * Cambia el entorno entre ARG y UY
 * @param {boolean} checked - Estado del checkbox (true = ARG, false = UY)
 */
function cambiarEntorno(checked) {
    mostrarSpinner();
    
    const entorno = checked ? 'central' : 'uy';
    
    // Realizar petición AJAX para cambiar el entorno
    $.ajax({
        url: '../../assets/ajax/cambiarEntorno.php', // Ajusta la ruta según tu estructura
        method: 'POST',
        data: { entorno: entorno },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Recargar la página para aplicar el cambio de entorno
                location.reload();
            } else {
                console.error('Error al cambiar entorno:', response.message);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cambiar el entorno. Por favor, intente nuevamente.'
                });
                ocultarSpinner();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX al cambiar entorno:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cambiar el entorno.'
            });
            ocultarSpinner();
        }
    });
}

/**
 * Exporta la tabla actual a Excel
 */
function exportarAExcel() {
    const table = $('#tablePedidos').DataTable();
    
    // Verificar si DataTable está inicializado
    if (!$.fn.DataTable.isDataTable('#tablePedidos')) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin datos',
            text: 'Primero debe realizar una búsqueda para exportar datos.'
        });
        return;
    }
    
    // Obtener solo los datos visibles (después de aplicar filtros)
    const data = table.rows({ search: 'applied' }).data();
    
    if (data.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin datos',
            text: 'No hay datos para exportar.'
        });
        return;
    }
    
    // Crear un array con los datos a exportar
    let exportData = [];
    
    // Agregar encabezados
    exportData.push([
        'Fecha',
        'Nro. Pedido',
        'Talón',
        'Depósito',
        'Cód. Cliente',
        'Cliente',
        'Canal',
        'Tipo',
        'Cód. Artículo',
        'Descripción',
        'Pedido',
        'Remitido',
        'Rubro',
        'Estado'
    ]);
    
    // Agregar datos
    data.each(function(row) {
        const rowData = [];
        $(row).each(function(index, cell) {
            // Limpiar el contenido de badges y otros elementos HTML
            const $cell = $('<div>').html(cell);
            let cellText = $cell.text().trim();
            rowData.push(cellText);
        });
        exportData.push(rowData);
    });
    
    // Crear el archivo CSV
    let csv = '';
    exportData.forEach(function(row) {
        csv += row.map(function(cell) {
            // Escapar comillas dobles
            return '"' + (cell ? cell.toString().replace(/"/g, '""') : '') + '"';
        }).join(',') + '\n';
    });
    
    // Crear blob y descargar
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'pedidos_' + obtenerFechaActual() + '.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Mostrar mensaje de éxito
    Swal.fire({
        icon: 'success',
        title: 'Exportado',
        text: 'Los datos se han exportado correctamente.',
        timer: 2000,
        showConfirmButton: false
    });
}

/**
 * Obtiene la fecha actual en formato YYYY-MM-DD
 * @returns {string} Fecha actual
 */
function obtenerFechaActual() {
    const fecha = new Date();
    const year = fecha.getFullYear();
    const month = String(fecha.getMonth() + 1).padStart(2, '0');
    const day = String(fecha.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
}

/**
 * Muestra el spinner de carga
 */
function mostrarSpinner() {
    $('#boxLoading').addClass('show').css('display', 'flex');
}

/**
 * Oculta el spinner de carga
 */
function ocultarSpinner() {
    $('#boxLoading').removeClass('show').css('display', 'none');
}

/**
 * Función de utilidad para formatear números
 * @param {number} numero - Número a formatear
 * @returns {string} Número formateado
 */
function formatearNumero(numero) {
    return new Intl.NumberFormat('es-AR').format(numero);
}

/**
 * Función de utilidad para formatear fechas
 * @param {string} fecha - Fecha en formato ISO
 * @returns {string} Fecha formateada
 */
function formatearFecha(fecha) {
    const date = new Date(fecha);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return day + '/' + month + '/' + year;
}

/**
 * Maneja errores globales de AJAX
 */
$(document).ajaxError(function(event, jqXHR, settings, thrownError) {
    console.error('Error AJAX:', {
        url: settings.url,
        status: jqXHR.status,
        error: thrownError
    });
    
    ocultarSpinner();
    
    // Solo mostrar error si no es un abort
    if (jqXHR.statusText !== 'abort') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al procesar la solicitud.'
        });
    }
});
