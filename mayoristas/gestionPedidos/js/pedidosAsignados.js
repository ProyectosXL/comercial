/* ========================================
   JavaScript específico para pedidosAsignados.php
   Gestión de Pedidos - XL Extra
   ======================================== */

/**
 * Inicialización cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('pedidosAsignados.js cargado correctamente');
    
    // Inicializar funcionalidades
    initTotales();
    initExportExcel();
});

/* ========================================
   Cálculo de totales
   ======================================== */

/**
 * Inicializa el cálculo de totales
 */
function initTotales() {
    calcularTotalUnidades();
    calcularTotalImporte();
}

/**
 * Calcula el total de unidades
 */
function calcularTotalUnidades() {
    const celdas = document.querySelectorAll('td.sumTotal');
    let total = 0;
    
    celdas.forEach(celda => {
        const valor = parseFloat(celda.textContent) || 0;
        total += valor;
    });
    
    const elementoTotal = document.getElementById('total');
    if (elementoTotal) {
        elementoTotal.textContent = new Intl.NumberFormat('de-DE').format(total);
    }
}

/**
 * Calcula el total de importe y formatea importes en la tabla
 */
function calcularTotalImporte() {
    const celdas = document.querySelectorAll('td.sumImporte');
    let total = 0;
    
    const formatter = new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0
    });
    
    celdas.forEach(celda => {
        const valor = parseFloat(celda.textContent) || 0;
        total += valor;
        // Formatear cada celda
        celda.textContent = formatter.format(valor);
        celda.classList.add('text-currency');
    });
    
    const elementoTotal = document.getElementById('totalImp');
    if (elementoTotal) {
        elementoTotal.textContent = formatter.format(total);
    }
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
            name: "Pedidos Asignados",
            filename: `Pedidos_Asignados_${obtenerFechaActual()}`,
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

/**
 * Formatea un número con separadores de miles
 */
function formatearNumero(valor) {
    return new Intl.NumberFormat('de-DE').format(valor);
}
