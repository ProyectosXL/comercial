
// dashboard-tables.js - Manejo de tablas de datos
const DashboardTables = {
    
    // Función para actualizar la tabla de datos principal
    actualizarTabla(datos) {
        if (!datos || !datos.length) {
            console.warn('No hay datos para la tabla, limpiando...');
            if (window.dashboardTables && window.dashboardTables.dataTable) {
                window.dashboardTables.dataTable.clear().draw();
            }
            return;
        }
        
        const dataTable = window.dashboardTables.dataTable;
        dataTable.clear();
        
        const nombresMeses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        
        datos.forEach(dato => {
            const mes = dato.MES ? nombresMeses[dato.MES - 1] : '';
            const anio = dato.ANIO || '';
            
            // CORRECCIÓN: Volvemos a usar 'semana' para el número original.
            const semana = dato.SEMANA || ''; 
            
            // Esta variable contendrá el texto final a mostrar en la tabla.
            let semanaDisplay = semana; 

            // La lógica para calcular el rango de fechas sigue siendo la misma.
            // Nos aseguramos de que 'anio' y 'semana' no estén vacíos.
            if (anio && semana) {
                try {
                    const range = DashboardUtils.getWeekDateRange(anio, semana);
                    const startDate = DashboardUtils.formatDateDDMM(range.start);
                    const endDate = DashboardUtils.formatDateDDMM(range.end);
                    // Creamos el texto formateado
                    semanaDisplay = `${semana} <span class="text-muted" style="font-size: 0.85em;">(${startDate} al ${endDate})</span>`;
                } catch (e) {
                    console.error(`Error al calcular rango para semana ${semana}/${anio}`, e);
                    // Si hay un error, se mostrará solo el número de semana.
                }
            }

            const supervisora = dato.SUPERVISORA || '';
            const concepto = dato.CONCEPTO || '';
            const efectivo = parseFloat(dato.IMPORTE || 0);
            const tarjeta = parseFloat(dato.IMPORTE_TARJETA || 0);
            const total = efectivo + tarjeta;
            
            dataTable.row.add([
                mes,
                anio,
                semanaDisplay,
                supervisora,
                concepto,
                DashboardUtils.formatearImporte(efectivo),
                DashboardUtils.formatearImporte(tarjeta),
                DashboardUtils.formatearImporte(total)
            ]);
        });
        
        dataTable.draw();
    }
};