/**
 * Script para gestión de artículos equivalentes
 * Archivo: js/equivalencias.js
 */

$(document).ready(function() {
    // Inicializar event listeners
    initEquivalenciasEvents();
    
    // Modificar la configuración de DataTable para incluir COD_EQUIVALENCIA
    modificarDataTableColumns();
});

/**
 * Inicializar eventos relacionados con equivalencias
 */
function initEquivalenciasEvents() {
    // Event listener para el botón de exportar equivalencias
    $(document).on('click', '#exportarEquivalencias', function() {
        exportarEquivalenciasExcel();
    });

    // Event listener para botones de equivalencia en la tabla principal
    $(document).on('click', '.btn-equivalencia', function() {
        const codEquivalencia = $(this).data('cod-equiv');
        mostrarEquivalencias(codEquivalencia);
    });

    // Event listener para mostrar imagen completa
    $(document).on('click', '.producto-imagen', function() {
        const articulo = $(this).data('articulo');
        const descripcion = $(this).data('descripcion');
        const rutaImagen = $(this).attr('src');
        mostrarImagenCompleta(articulo, descripcion, rutaImagen);
    });
}

/**
 * Modificar las columnas del DataTable principal para incluir COD_EQUIVALENCIA
 */
function modificarDataTableColumns() {
    // Esta función se puede usar para extender las columnas si es necesario
    // Por ahora, mantenemos la funcionalidad separada
}

/**
 * Mostrar modal con artículos equivalentes
 * @param {number} codEquivalencia - Código de equivalencia
 */
function mostrarEquivalencias(codEquivalencia) {
    if (!codEquivalencia || codEquivalencia <= 0) {
        alert('Código de equivalencia no válido');
        return;
    }

    console.log('Mostrando equivalencias para código:', codEquivalencia);

    // Mostrar el modal y spinner
    $('#modalEquivalencias').modal('show');
    $('#spinnerEquivalencias').removeClass('d-none');
    $('#alertNoEquivalencias').addClass('d-none');
    
    // Limpiar datos anteriores
    limpiarDatosModal();

    // Realizar petición AJAX
    $.ajax({
        url: 'controller/obtener_equivalencias.php',
        method: 'GET',
        data: { cod_equivalencia: codEquivalencia },
        dataType: 'json',
        timeout: 30000, // 30 segundos de timeout
        success: function(response) {
            $('#spinnerEquivalencias').addClass('d-none');
            
            if (response.success && response.data && response.data.length > 0) {
                llenarDatosModal(response);
            } else {
                mostrarAlertaNoEquivalencias();
            }
        },
        error: function(xhr, status, error) {
            $('#spinnerEquivalencias').addClass('d-none');
            console.error('Error al obtener equivalencias:', {xhr, status, error});
            
            let errorMessage = 'Error al obtener las equivalencias';
            try {
                const response = JSON.parse(xhr.responseText);
                if (response && response.message) {
                    errorMessage = response.message;
                }
            } catch(e) {
                console.error('Error al parsear respuesta:', e);
                if (status === 'timeout') {
                    errorMessage = 'Tiempo de espera agotado. Intente nuevamente.';
                } else if (status === 'error') {
                    errorMessage = 'Error de conexión con el servidor.';
                }
            }
            
            alert(errorMessage);
        }
    });
}

/**
 * Limpiar datos del modal
 */
function limpiarDatosModal() {
    $('#codigoEquivalencia').text('-');
    $('#descripcionGrupo').text('-');
    $('#totalArticulos').text('-');
    $('#stockTotalGrupo').text('-');
    $('#stockCentralGrupo').text('-');
    $('#stockLocalesGrupo').text('-');
    $('#bodyEquivalencias').empty();
}

/**
 * Llenar datos del modal con la respuesta del servidor
 * @param {object} response - Respuesta del servidor
 */
function llenarDatosModal(response) {
    try {
        // Llenar información del resumen
        const resumen = response.resumen;
        $('#codigoEquivalencia').text(resumen.codEquivalencia);
        $('#descripcionGrupo').text(resumen.descripcionGrupo || 'Sin descripción');
        $('#totalArticulos').text(resumen.totalArticulos);
        $('#stockTotalGrupo').text(Number(resumen.stockTotalGrupo || 0).toLocaleString('es-AR'));
        $('#stockCentralGrupo').text(Number(resumen.stockCentralGrupo || 0).toLocaleString('es-AR'));
        $('#stockLocalesGrupo').text(Number(resumen.stockLocalesGrupo || 0).toLocaleString('es-AR'));

        // Llenar tabla de equivalencias
        let tbody = '';
        response.data.forEach(function(item) {
            tbody += generarFilaEquivalencia(item);
        });
        
        $('#bodyEquivalencias').html(tbody);

    } catch (error) {
        console.error('Error al llenar datos del modal:', error);
        alert('Error al procesar los datos de equivalencias');
    }
}

/**
 * Generar HTML para una fila de equivalencia
 * @param {object} item - Datos del artículo
 * @returns {string} HTML de la fila
 */
function generarFilaEquivalencia(item) {
    const esPrincipal = item.ARTICULO_PRINCIPAL == 1;
    const claseFilaPrincipal = esPrincipal ? 'table-success' : '';

    // Generar imagen del producto
    const imagenHtml = generarImagenProducto(item.ARTICULO, item.DESCRIPCIO, esPrincipal);

    return `
        <tr class="${claseFilaPrincipal}">
            <td>${imagenHtml}</td>
            <td><strong>${item.ARTICULO || ''}</strong></td>
            <td>${item.DESCRIPCIO || ''}</td>
            <td>${item.COLOR || ''}</td>
            <td>${formatearPrecio(item.PRECIO)}</td>
            <td>${formatearNumero(item.STOCK_CENTRAL)}</td>
            <td>${formatearNumero(item.STOCK_LOCALES)}</td>
            <td><strong>${formatearNumero(item.STOCK_TOTAL)}</strong></td>
            <td>${formatearNumero(item.CANT_VENTA)}</td>
            <td>${formatearPrecio(item.IMP_VENTA)}</td>
            <td>${item.TEMPORADA || ''}</td>
            <td>${item.DESTINO || ''}</td>
        </tr>
    `;
}

/**
 * Generar HTML para la imagen del producto
 * @param {string} codigoArticulo - Código del artículo
 * @param {string} descripcion - Descripción del producto
 * @param {boolean} esPrincipal - Si es el artículo principal
 * @returns {string} HTML de la imagen
 */
function generarImagenProducto(codigoArticulo, descripcion, esPrincipal = false) {
    if (!codigoArticulo) {
        return '<div class="imagen-placeholder">Sin código</div>';
    }

    const rutaImagen = `../../../../../Imagenes/${codigoArticulo}.jpg`;
    const claseAdicional = esPrincipal ? ' principal' : '';
    const titulo = esPrincipal ? 'Artículo Principal - Click para ampliar' : 'Click para ampliar';

    return `
        <img src="${rutaImagen}" 
             alt="Imagen de ${codigoArticulo}" 
             class="producto-imagen${claseAdicional}"
             data-articulo="${codigoArticulo}"
             data-descripcion="${descripcion || ''}"
             title="${titulo}"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="imagen-placeholder" style="display: none;">Sin imagen</div>
    `;
}

/**
 * Mostrar imagen completa en modal
 * @param {string} articulo - Código del artículo
 * @param {string} descripcion - Descripción del producto
 * @param {string} rutaImagen - Ruta de la imagen
 */
function mostrarImagenCompleta(articulo, descripcion, rutaImagen) {
    $('#tituloImagenCompleta').text(`Imagen del Producto - ${articulo}`);
    $('#imagenCompleta').attr('src', rutaImagen);
    $('#articuloImagen').text(articulo);
    $('#descripcionImagen').text(descripcion || 'Sin descripción');
    
    $('#modalImagenCompleta').modal('show');
}

/**
 * Mostrar alerta cuando no hay equivalencias
 */
function mostrarAlertaNoEquivalencias() {
    $('#alertNoEquivalencias').removeClass('d-none');
}

/**
 * Formatear número con separadores de miles
 * @param {number|string} numero
 * @returns {string}
 */
function formatearNumero(numero) {
    if (!numero || numero === null || numero === undefined) return '0';
    return Number(numero).toLocaleString('es-AR');
}

/**
 * Formatear precio con símbolo de moneda
 * @param {number|string} precio
 * @returns {string}
 */
function formatearPrecio(precio) {
    if (!precio || precio === null || precio === undefined) return '$ 0';
    return '$ ' + Number(precio).toLocaleString('es-AR');
}

/**
 * Exportar equivalencias a Excel
 */
function exportarEquivalenciasExcel() {
    const codEquivalencia = $('#codigoEquivalencia').text();
    if (codEquivalencia === '-') {
        alert('No hay datos para exportar');
        return;
    }

    try {
        // Verificar si XLSX está disponible
        if (typeof XLSX === 'undefined') {
            alert('Librería de Excel no disponible. No se puede exportar.');
            return;
        }

        // Obtener datos de la tabla
        const tabla = document.getElementById('tablaEquivalencias');
        if (!tabla) {
            alert('No se pudo encontrar la tabla para exportar');
            return;
        }

        // Crear el workbook
        const workbook = XLSX.utils.table_to_book(tabla, {
            sheet: "Equivalencias",
            raw: false
        });

        // Generar nombre del archivo
        const fecha = new Date().toLocaleDateString('es-AR').replace(/\//g, '-');
        const fileName = `Equivalencias_${codEquivalencia}_${fecha}.xlsx`;
        
        // Guardar archivo
        XLSX.writeFile(workbook, fileName);
        
        console.log('Archivo exportado:', fileName);

    } catch (error) {
        console.error('Error al exportar a Excel:', error);
        alert('Error al exportar el archivo de Excel: ' + error.message);
    }
}

/**
 * Generar botón de equivalencia para la tabla principal
 * @param {number} codEquivalencia - Código de equivalencia
 * @returns {string} HTML del botón
 */
function generarBotonEquivalencia(codEquivalencia) {
    if (!codEquivalencia || codEquivalencia === null) {
        return '<span class="text-muted">-</span>';
    }

    return `<button class="btn btn-outline-primary btn-sm btn-equivalencia" 
                   data-cod-equiv="${codEquivalencia}"
                   title="Ver artículos equivalentes">
                <i class="fas fa-link me-1"></i>${codEquivalencia}
            </button>`;
}

/**
 * Función para usar en el render de DataTables para la columna COD_EQUIVALENCIA
 * @param {*} data 
 * @param {*} type 
 * @param {*} row 
 * @returns {string}
 */
function renderColumnEquivalencia(data, type, row) {
    if (type === 'display') {
        return generarBotonEquivalencia(data);
    }
    return data || '';
}

// Exponer funciones globalmente para uso desde otros scripts
window.mostrarEquivalencias = mostrarEquivalencias;
window.renderColumnEquivalencia = renderColumnEquivalencia;

console.log('Script de equivalencias cargado correctamente');