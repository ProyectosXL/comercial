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
    // Inicializar Select2 para el selector de canal
    $('#selectCanal').select2({
        placeholder: 'Todos los canales',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "No se encontraron resultados";
            }
        }
    });

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
        const isChecked = $(this).prop('checked');
        console.log('Toggle cambiado. Estado:', isChecked ? 'ARG (central)' : 'UY');
        cambiarEntorno(isChecked);
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
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
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
dom:
            // Contenedor superior con alineación flexbox
            '<"row justify-content-between align-items-center mb-3"' +
                // "Mostrar X registros" a la izquierda
                '<"col-md-auto"l>' +
                // "Buscar:" a la derecha
                '<"col-md-auto"f>' +
            '>' +
            // La tabla en sí
            't' +
            // Contenedor inferior
            '<"row align-items-center mt-3"' +
                // "Mostrando X de Y"
                '<"col-md-6"i>' +
                // Paginación a la derecha
                '<"col-md-6"p>' +
            '>',
        pageLength: 100,
        lengthMenu: [[25, 50, 100, 200, -1], [25, 50, 100, 200, "Todos"]],
        order: [[0, 'desc']], // Ordenar por fecha descendente
        columnDefs: [
            {
                // Columna Fecha - Prioridad máxima (siempre visible)
                targets: 0,
                responsivePriority: 1,
                className: 'nowrap all'
            },
            {
                // Columna Nro. Pedido - Alta prioridad
                targets: 1,
                responsivePriority: 2,
                className: 'nowrap'
            },
            {
                // Columna Cliente - Alta prioridad
                targets: 5,
                responsivePriority: 3
            },
            {
                // Columna Estado - Alta prioridad
                targets: 13,
                responsivePriority: 4,
                className: 'text-center'
            },
            {
                // Columnas Pedido y Remitido
                targets: [10, 11],
                responsivePriority: 5,
                className: 'text-end'
            },
            {
                // Columnas menos importantes (se ocultarán primero en pantallas pequeñas)
                targets: [2, 3, 4, 6, 7, 8, 9, 12],
                responsivePriority: 10
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
    
    console.log('Cambiando entorno a:', entorno);
    
    // Realizar petición AJAX para cambiar el entorno
    $.ajax({
        url: '../../assets/ajax/cambiarEntorno.php', // Ajusta la ruta según tu estructura
        method: 'POST',
        data: { entorno: entorno },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta del servidor:', response);
            if (response.success) {
                console.log('Entorno cambiado exitosamente. Recargando página...');
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
                // Revertir el toggle al estado anterior
                $('#checkEntorno').bootstrapToggle(checked ? 'off' : 'on');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX al cambiar entorno:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cambiar el entorno.'
            });
            ocultarSpinner();
            // Revertir el toggle al estado anterior
            $('#checkEntorno').bootstrapToggle(checked ? 'off' : 'on');
        }
    });
}

/**
 * Exporta la tabla actual a Excel (XLSX)
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
    
    // Mostrar spinner
    mostrarSpinner();
    
    // Preparar los datos para exportar
    const exportData = [];
    
    // Agregar encabezados
    const headers = [
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
    ];
    exportData.push(headers);
    
    // Agregar datos (limpiar HTML de las celdas)
    data.each(function(row) {
        const rowData = [];
        $(row).each(function(index, cell) {
            // Limpiar el contenido de badges y otros elementos HTML
            const $cell = $('<div>').html(cell);
            let cellText = $cell.text().trim();
            
            // Convertir números de texto a números para Excel
            if (index === 10 || index === 11) { // Columnas Pedido y Remitido
                cellText = cellText.replace(/\./g, '').replace(/,/g, '.');
                cellText = parseFloat(cellText) || 0;
            }
            
            rowData.push(cellText);
        });
        exportData.push(rowData);
    });
    
    // Crear el libro de trabajo (workbook)
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(exportData);
    
    // Configurar anchos de columnas
    const colWidths = [
        { wch: 12 },  // Fecha
        { wch: 15 },  // Nro. Pedido
        { wch: 10 },  // Talón
        { wch: 12 },  // Depósito
        { wch: 12 },  // Cód. Cliente
        { wch: 30 },  // Cliente
        { wch: 15 },  // Canal
        { wch: 12 },  // Tipo
        { wch: 15 },  // Cód. Artículo
        { wch: 35 },  // Descripción
        { wch: 10 },  // Pedido
        { wch: 10 },  // Remitido
        { wch: 20 },  // Rubro
        { wch: 12 }   // Estado
    ];
    ws['!cols'] = colWidths;
    
    // Agregar la hoja al libro
    XLSX.utils.book_append_sheet(wb, ws, 'Pedidos');
    
    // Generar el archivo y descargarlo
    const fileName = 'pedidos_' + obtenerFechaActual() + '.xlsx';
    XLSX.writeFile(wb, fileName);
    
    // Ocultar spinner
    ocultarSpinner();
    
    // Mostrar mensaje de éxito
    Swal.fire({
        icon: 'success',
        title: 'Exportado',
        text: 'Los datos se han exportado correctamente en formato Excel.',
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
