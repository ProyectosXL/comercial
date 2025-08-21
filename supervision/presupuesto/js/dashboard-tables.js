
// dashboard-tables.js - Manejo de tablas de datos
const DashboardTables = {
    
    // Función para actualizar la tabla de datos principal
    actualizarTabla(datos) {
        if (!datos || !datos.length) {
            console.error('No hay datos para la tabla');
            return;
        }
        
        const dataTable = window.dashboardTables.dataTable;
        dataTable.clear();
        
        datos.forEach(dato => {
            const semana = dato.SEMANA || '';
            const anio = dato.ANIO || '';
            const supervisora = dato.SUPERVISORA || '';
            const concepto = dato.CONCEPTO || '';
            const efectivo = parseFloat(dato.IMPORTE || 0);
            const tarjeta = parseFloat(dato.IMPORTE_TARJETA || 0);
            const total = efectivo + tarjeta;
            
            dataTable.row.add([
                semana,
                anio,
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