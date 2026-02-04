document.addEventListener('DOMContentLoaded', function() {
    initTotales();
    initExportExcel();
});

/* --- UTILIDADES DE FORMATO --- */
const formateadorPesos = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
});

const formateadorEnteros = new Intl.NumberFormat('de-DE'); // Para miles sin signo

// Parsea "$ 1.500.000" -> 1500000
function parseMoneda(texto) {
    if (!texto) return 0;
    let limpio = texto.toString().replace(/[^0-9-]/g, ''); // Deja solo números y menos
    let num = parseInt(limpio, 10);
    return isNaN(num) ? 0 : num;
}

/* --- CÁLCULO DE TOTALES --- */
function initTotales() {
    calcularTotalUnidades();
    calcularTotalImporte();
}

function calcularTotalUnidades() {
    const celdas = document.querySelectorAll('td.sumTotal');
    let total = 0;
    
    celdas.forEach(celda => {
        // Limpiamos por si hay espacios o texto invisible
        let val = parseInt(celda.textContent.replace(/\D/g, '')) || 0;
        total += val;
    });
    
    const elementoTotal = document.getElementById('total');
    if (elementoTotal) {
        elementoTotal.textContent = formateadorEnteros.format(total);
    }
}

function calcularTotalImporte() {
    const celdas = document.querySelectorAll('td.sumImporte');
    let total = 0;
    
    celdas.forEach(celda => {
        // La celda ya viene formateada desde PHP ($ X.XXX.XXX)
        // Solo necesitamos convertirla a número para sumar
        total += parseMoneda(celda.textContent);
    });
    
    const elementoTotal = document.getElementById('totalImp');
    if (elementoTotal) {
        elementoTotal.textContent = formateadorPesos.format(total);
    }
}

/* --- EXPORTAR EXCEL --- */
function initExportExcel() {
    const btnExport = document.getElementById('btnExport');
    
    if (btnExport) {
        btnExport.addEventListener('click', function(e) {
            e.preventDefault();
            $(".table-pedidos").table2excel({
                name: "Pedidos Asignados",
                filename: `Pedidos_Asignados_${new Date().toISOString().slice(0,10)}`,
                fileext: ".xls"
            });
        });
    }
}