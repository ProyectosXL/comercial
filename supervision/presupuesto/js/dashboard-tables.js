
// dashboard-tables.js - Manejo de tablas de datos
const DashboardTables = {
    
    // Función para actualizar la tabla de datos principal
    actualizarTabla(datos) {
        if (!datos || !datos.length) {
            console.error('No hay datos para la tabla');
            return;
        }
        
        console.log('Datos recibidos en actualizarTabla:', datos[0]); // Debug
        
        const dataTable = window.dashboardTables.dataTable;
        dataTable.clear();
        
        // Nombres de meses en español
        const nombresMeses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        
        datos.forEach(dato => {
            const mes = dato.MES ? nombresMeses[dato.MES - 1] : '';
            const semana = dato.SEMANA || '';
            const anio = dato.ANIO || '';
            const supervisora = dato.SUPERVISORA || '';
            const concepto = dato.CONCEPTO || '';
            const efectivo = parseFloat(dato.IMPORTE || 0);
            const tarjeta = parseFloat(dato.IMPORTE_TARJETA || 0);
            const total = efectivo + tarjeta;
            
            dataTable.row.add([
                mes,
                anio,
                semana,
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