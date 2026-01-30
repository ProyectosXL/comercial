/* ========================================
   JavaScript específico para resumenPorPedido.php
   Gestión de Pedidos - XL Extra
   ======================================== */

/**
 * Inicialización cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('resumenPorPedido.js cargado correctamente');
    
    // Inicializar funcionalidades
    formatearImportes();
    initExportExcel();
    initLoadingSpinner();
});

/* ========================================
   Spinner de carga
   ======================================== */

/**
 * Inicializa el spinner de carga para el formulario
 */
function initLoadingSpinner() {
    const form = document.querySelector('form');
    const spinner = document.getElementById('loadingSpinner');
    
    if (form && spinner) {
        form.addEventListener('submit', function(e) {
            // Validar que las fechas estén completas
            const desde = document.getElementById('desde').value;
            const hasta = document.getElementById('hasta').value;
            
            if (desde && hasta) {
                // Mostrar spinner
                spinner.style.display = 'flex';
            }
        });
    }
}

/* ========================================
   Formateo de importes
   ======================================== */

/**
 * Formatea los importes de la tabla
 */
function formatearImportes() {
    const celdas = document.querySelectorAll('td.importe');
    
    const formatter = new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0
    });
    
    celdas.forEach(celda => {
        const valor = parseFloat(celda.textContent) || 0;
        celda.textContent = formatter.format(valor);
        celda.classList.add('text-currency');
    });
}

/* ========================================
   Exportar a Excel
   ======================================== */

/**
 * Inicializa la funcionalidad de exportar a Excel
 */
function initExportExcel() {
    const btnExport = document.getElementById('btnExport');
    
    if (btnExport) {
        btnExport.addEventListener('click', function(e) {
            e.preventDefault();
            exportarTablaAExcel();
        });
    }
}

/**
 * Exporta la tabla a Excel
 */
function exportarTablaAExcel() {
    const tabla = document.querySelector('.table-pedidos');
    
    if (!tabla) {
        console.error('No se encontró la tabla para exportar');
        return;
    }
    
    // Usar table2excel (ya incluido en el HTML)
    if (typeof $.fn.table2excel !== 'undefined') {
        $(tabla).table2excel({
            name: "Resumen por Pedido",
            filename: `Resumen_Pedidos_${obtenerFechaActual()}`,
            fileext: ".xls"
        });
    } else {
        console.error('Plugin table2excel no disponible');
    }
}

/**
 * Obtiene la fecha actual en formato YYYY-MM-DD
 */
function obtenerFechaActual() {
    const fecha = new Date();
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    return `${año}-${mes}-${dia}`;
}

/* ========================================
   Utilidades
   ======================================== */

/**
 * Formatea un número como moneda argentina
 */
function formatearMoneda(valor) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0
    }).format(valor);
}
