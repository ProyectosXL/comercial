
// dashboard-metrics.js - Manejo de métricas y KPIs
const DashboardMetrics = {
    
    // Función para actualizar métricas principales
    actualizarMetricas(resumen) {
        // Verificar que resumen existe y tiene la estructura esperada
        if (!resumen) {
            console.error('Objeto resumen no definido');
            return;
        }
        
        // Asignar valores predeterminados si las propiedades no existen
        const totalGastos = resumen.totalGastos || 0;
        const totalEfectivo = resumen.totalEfectivo || 0;
        const totalTarjeta = resumen.totalTarjeta || 0;
        const promedioSemanal = resumen.promedioSemanal || 0;
        
        $('#totalGastos').text(DashboardUtils.formatearImporte(totalGastos));
        $('#totalEfectivo').text(DashboardUtils.formatearImporte(totalEfectivo));
        $('#totalTarjeta').text(DashboardUtils.formatearImporte(totalTarjeta));
        $('#promedioSemanal').text(DashboardUtils.formatearImporte(promedioSemanal));
        
        // Variaciones respecto al período anterior
        DashboardMetrics.actualizarVariacion('varTotalGastos', resumen.varTotalGastos || 0, resumen.totalGastosAnterior);
        DashboardMetrics.actualizarVariacion('varTotalEfectivo', resumen.varTotalEfectivo || 0, resumen.totalEfectivoAnterior);
        DashboardMetrics.actualizarVariacion('varTotalTarjeta', resumen.varTotalTarjeta || 0, resumen.totalTarjetaAnterior);
    },
    
    // Función para actualizar indicador de variación
    actualizarVariacion(elementId, valor, valorAbsolutoAnterior) {
        const elemento = $(`#${elementId}`);
        
        // Formatear valor porcentual
        const valorFormateado = Math.abs(valor).toFixed(1);
        
        // Texto para mostrar
        let textoVariacion;
        
        if (valor > 0) {
            textoVariacion = `<i class="bi bi-arrow-up-circle-fill text-success"></i> ${valorFormateado}%`;
            elemento.addClass('text-success').removeClass('text-danger text-muted');
        } else if (valor < 0) {
            textoVariacion = `<i class="bi bi-arrow-down-circle-fill text-danger"></i> ${valorFormateado}%`;
            elemento.addClass('text-danger').removeClass('text-success text-muted');
        } else {
            textoVariacion = `0%`;
            elemento.addClass('text-muted').removeClass('text-success text-danger');
        }
        
        // Mostrar texto con monto en tooltip
        elemento.html(textoVariacion);
        
        // Opcional: Agregar tooltip con información más detallada
        if (valorAbsolutoAnterior !== undefined) {
            elemento.attr('title', `Período anterior: ${DashboardUtils.formatearImporte(valorAbsolutoAnterior)}`);
            // Si estás usando Bootstrap tooltips, inicialízalos
            if (typeof $().tooltip === 'function') {
                elemento.tooltip();
            }
        }
    }
};