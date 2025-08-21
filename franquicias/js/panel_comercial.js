/**
 * Panel Comercial - Funcionalidades para el área comercial
 */

class PanelComercialManager {
    constructor() {
        this.periodoActual = this.obtenerPeriodoActual();
        this.datosCache = {};
        this.filtrosActivos = {};
        
        this.init();
    }
    
    init() {
        this.configurarEventos();
        this.cargarPeriodosDisponibles();
        this.cargarDatosPeriodo(this.periodoActual);
        this.configurarFiltros();
    }
    
    /**
     * Configurar eventos del DOM
     */
    configurarEventos() {
        // Selector de período
        document.getElementById('periodo-analisis').addEventListener('change', (e) => {
            this.cambiarPeriodo(e.target.value);
        });
        
        // Tabs
        document.querySelectorAll('#panelTabs button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                this.manejarCambioTab(e.target.getAttribute('data-bs-target'));
            });
        });
        
        // Selector de franquicia para detalle
        document.getElementById('selector-franquicia-detalle').addEventListener('change', (e) => {
            this.cargarDetalleFranquicia(e.target.value);
        });
    }
    
    /**
     * Configurar filtros de búsqueda
     */
    configurarFiltros() {
        // Filtros para stock consolidado
        document.getElementById('filtro-rubro').addEventListener('input', (e) => {
            this.filtrarTablaConsolidado('rubro', e.target.value);
        });
        
        document.getElementById('filtro-categoria').addEventListener('input', (e) => {
            this.filtrarTablaConsolidado('categoria', e.target.value);
        });
    }
    
    /**
     * Cargar períodos disponibles
     */
    async cargarPeriodosDisponibles() {
        try {
            const response = await fetch('controller/panel_comercial_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=obtener_periodos'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.llenarSelectorPeriodos(data.data);
            }
        } catch (error) {
            console.error('Error al cargar períodos:', error);
        }
    }
    
    /**
     * Llenar selector de períodos
     */
    llenarSelectorPeriodos(periodos) {
        const selector = document.getElementById('periodo-analisis');
        const selectorSidebar = document.getElementById('selector-periodo');
        
        // Limpiar opciones existentes excepto la primera
        while (selector.children.length > 1) {
            selector.removeChild(selector.lastChild);
        }
        
        periodos.forEach(periodo => {
            if (periodo !== this.periodoActual) {
                const option = document.createElement('option');
                option.value = periodo;
                option.textContent = this.formatearPeriodo(periodo);
                selector.appendChild(option);
                
                // También al sidebar si existe
                if (selectorSidebar) {
                    const optionSidebar = option.cloneNode(true);
                    selectorSidebar.appendChild(optionSidebar);
                }
            }
        });
    }
    
    /**
     * Cambiar período de análisis
     */
    cambiarPeriodo(periodo) {
        this.periodoActual = periodo;
        this.cargarDatosPeriodo(periodo);
    }
    
    /**
     * Cargar todos los datos del período
     */
    async cargarDatosPeriodo(periodo) {
        this.mostrarLoading('Cargando datos del período...');
        
        try {
            await Promise.all([
                this.cargarEstadisticas(periodo),
                this.cargarResumenCumplimiento(periodo),
                this.cargarStockConsolidado(periodo)
            ]);
            
            this.actualizarStatsRapidas(periodo);
        } catch (error) {
            console.error('Error al cargar datos:', error);
            this.mostrarAlerta('Error al cargar los datos', 'danger');
        } finally {
            this.ocultarLoading();
        }
    }
    
    /**
     * Cargar estadísticas generales
     */
    async cargarEstadisticas(periodo) {
        try {
            const response = await fetch('controller/panel_comercial_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=obtener_estadisticas&periodo=${periodo}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarEstadisticas(data.data);
                this.datosCache.estadisticas = data.data;
            }
        } catch (error) {
            console.error('Error al cargar estadísticas:', error);
        }
    }
    
    /**
     * Mostrar estadísticas en el dashboard
     */
    mostrarEstadisticas(stats) {
        document.getElementById('total-franquicias').textContent = stats.total_franquicias || '0';
        document.getElementById('franquicias-informaron').textContent = stats.franquicias_informaron || '0';
        document.getElementById('franquicias-no-informaron').textContent = stats.franquicias_no_informaron || '0';
        document.getElementById('porcentaje-cumplimiento').textContent = `${stats.porcentaje_cumplimiento || '0'}%`;
    }
    
    /**
     * Cargar resumen de cumplimiento
     */
    async cargarResumenCumplimiento(periodo) {
        try {
            const response = await fetch('controller/panel_comercial_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=obtener_resumen_cumplimiento&periodo=${periodo}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarTablaCumplimiento(data.data);
                this.llenarSelectorFranquicias(data.data);
                this.datosCache.cumplimiento = data.data;
            }
        } catch (error) {
            console.error('Error al cargar cumplimiento:', error);
        }
    }
    
    /**
     * Mostrar tabla de cumplimiento
     */
    mostrarTablaCumplimiento(datos) {
        const tbody = document.querySelector('#tabla-cumplimiento tbody');
        tbody.innerHTML = '';
        
        datos.forEach(franquicia => {
            const fila = document.createElement('tr');
            
            const estadoClass = franquicia.estado === 'INFORMÓ' ? 'success' : 'danger';
            const estadoIcon = franquicia.estado === 'INFORMÓ' ? 'check-circle' : 'x-circle';
            
            fila.innerHTML = `
                <td><strong>${franquicia.franquicia_nombre}</strong></td>
                <td>${franquicia.ciudad || '-'}</td>
                <td>
                    <span class="badge bg-${estadoClass}">
                        <i class="bi bi-${estadoIcon} me-1"></i>
                        ${franquicia.estado}
                    </span>
                </td>
                <td class="text-center">${franquicia.total_registros || '0'}</td>
                <td>${franquicia.ultima_actualizacion ? this.formatearFecha(franquicia.ultima_actualizacion) : '-'}</td>
                <td>
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary" 
                            onclick="panelComercial.verDetalleFranquicia('${franquicia.franquicia_id}', '${franquicia.franquicia_nombre}')">
                        <i class="bi bi-eye me-1"></i>
                        Ver Detalle
                    </button>
                </td>
            `;
            
            tbody.appendChild(fila);
        });
    }
    
    /**
     * Cargar stock consolidado
     */
    async cargarStockConsolidado(periodo) {
        try {
            const response = await fetch('controller/panel_comercial_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=obtener_stock_consolidado&periodo=${periodo}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarTablaConsolidado(data.data);
                this.datosCache.consolidado = data.data;
            }
        } catch (error) {
            console.error('Error al cargar stock consolidado:', error);
        }
    }
    
    /**
     * Mostrar tabla de stock consolidado
     */
    mostrarTablaConsolidado(datos) {
        const tbody = document.querySelector('#tabla-consolidado tbody');
        tbody.innerHTML = '';
        
        datos.forEach(item => {
            const fila = document.createElement('tr');
            fila.setAttribute('data-rubro', item.rubro.toLowerCase());
            fila.setAttribute('data-categoria', item.categoria.toLowerCase());
            
            fila.innerHTML = `
                <td><strong>${item.rubro}</strong></td>
                <td>${item.categoria}</td>
                <td class="text-center">
                    <span class="badge bg-info">${item.franquicias_reportan}</span>
                </td>
                <td class="text-end">${parseInt(item.cantidad_total).toLocaleString()}</td>
                <td class="text-end">${parseFloat(item.promedio_cantidad).toFixed(1)}</td>
                <td class="text-end">${parseInt(item.cantidad_minima).toLocaleString()}</td>
                <td class="text-end">${parseInt(item.cantidad_maxima).toLocaleString()}</td>
            `;
            
            tbody.appendChild(fila);
        });
    }
    
    /**
     * Filtrar tabla consolidado
     */
    filtrarTablaConsolidado(tipo, valor) {
        const filas = document.querySelectorAll('#tabla-consolidado tbody tr');
        const filtro = valor.toLowerCase().trim();
        
        this.filtrosActivos[tipo] = filtro;
        
        filas.forEach(fila => {
            const cumpleRubro = !this.filtrosActivos.rubro || 
                              fila.getAttribute('data-rubro').includes(this.filtrosActivos.rubro);
            const cumpleCategoria = !this.filtrosActivos.categoria || 
                                  fila.getAttribute('data-categoria').includes(this.filtrosActivos.categoria);
            
            if (cumpleRubro && cumpleCategoria) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }
    
    /**
     * Llenar selector de franquicias para detalle
     */
    llenarSelectorFranquicias(franquicias) {
        const selector = document.getElementById('selector-franquicia-detalle');
        
        // Limpiar opciones existentes excepto la primera
        while (selector.children.length > 1) {
            selector.removeChild(selector.lastChild);
        }
        
        franquicias.forEach(franquicia => {
            const option = document.createElement('option');
            option.value = franquicia.franquicia_id;
            option.textContent = `${franquicia.franquicia_nombre} - ${franquicia.ciudad || 'Sin ciudad'}`;
            selector.appendChild(option);
        });
    }
    
    /**
     * Cargar detalle de franquicia específica
     */
    async cargarDetalleFranquicia(franquiciaId) {
        if (!franquiciaId) {
            this.ocultarDetalleFranquicia();
            return;
        }
        
        try {
            const response = await fetch('controller/panel_comercial_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=obtener_detalle_franquicia&franquicia_id=${franquiciaId}&periodo=${this.periodoActual}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarDetalleFranquicia(data.data);
            } else {
                this.ocultarDetalleFranquicia();
            }
        } catch (error) {
            console.error('Error al cargar detalle:', error);
            this.ocultarDetalleFranquicia();
        }
    }
    
    /**
     * Mostrar detalle de franquicia
     */
    mostrarDetalleFranquicia(datos) {
        const contenido = document.getElementById('contenido-detalle-franquicia');
        const mensaje = document.getElementById('mensaje-sin-detalle');
        const tbody = document.querySelector('#tabla-detalle-franquicia tbody');
        
        if (datos.length === 0) {
            this.ocultarDetalleFranquicia();
            return;
        }
        
        tbody.innerHTML = '';
        
        datos.forEach(item => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td><strong>${item.rubro}</strong></td>
                <td>${item.categoria}</td>
                <td class="text-end">${parseInt(item.cantidad).toLocaleString()}</td>
                <td>${this.formatearFecha(item.fecha_actualizacion)}</td>
            `;
            tbody.appendChild(fila);
        });
        
        contenido.style.display = 'block';
        mensaje.style.display = 'none';
    }
    
    /**
     * Ocultar detalle de franquicia
     */
    ocultarDetalleFranquicia() {
        const contenido = document.getElementById('contenido-detalle-franquicia');
        const mensaje = document.getElementById('mensaje-sin-detalle');
        
        contenido.style.display = 'none';
        mensaje.style.display = 'block';
    }
    
    /**
     * Ver detalle de franquicia en modal
     */
    async verDetalleFranquicia(franquiciaId, nombreFranquicia) {
        try {
            const response = await fetch('controller/panel_comercial_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=obtener_detalle_franquicia&franquicia_id=${franquiciaId}&periodo=${this.periodoActual}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarModalStock(nombreFranquicia, data.data);
            } else {
                this.mostrarAlerta('No hay datos de stock para esta franquicia', 'info');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta('Error al cargar el detalle', 'danger');
        }
    }
    
    /**
     * Mostrar modal con stock de franquicia
     */
    mostrarModalStock(nombreFranquicia, stockData) {
        document.getElementById('modal-franquicia-nombre').textContent = nombreFranquicia;
        document.getElementById('modal-periodo').textContent = this.formatearPeriodo(this.periodoActual);
        
        const tbody = document.getElementById('modal-tabla-stock');
        tbody.innerHTML = '';
        
        if (stockData.length === 0) {
            document.getElementById('modal-sin-datos').style.display = 'block';
            tbody.parentElement.parentElement.style.display = 'none';
        } else {
            document.getElementById('modal-sin-datos').style.display = 'none';
            tbody.parentElement.parentElement.style.display = 'block';
            
            // Calcular estadísticas
            const rubros = new Set();
            let totalStock = 0;
            
            stockData.forEach(item => {
                rubros.add(item.rubro);
                totalStock += parseInt(item.cantidad) || 0;
                
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td><strong>${item.rubro}</strong></td>
                    <td>${item.categoria}</td>
                    <td class="text-end">${parseInt(item.cantidad).toLocaleString()}</td>
                    <td>${this.formatearFecha(item.fecha_actualizacion)}</td>
                `;
                tbody.appendChild(fila);
            });
            
            // Actualizar estadísticas del modal
            document.getElementById('modal-total-rubros').textContent = rubros.size;
            document.getElementById('modal-total-categorias').textContent = stockData.length;
            document.getElementById('modal-total-stock').textContent = totalStock.toLocaleString();
            document.getElementById('modal-promedio-categoria').textContent = 
                stockData.length > 0 ? (totalStock / stockData.length).toFixed(1) : '0';
        }
        
        const modal = new bootstrap.Modal(document.getElementById('modalStock'));
        modal.show();
    }
    
    /**
     * Manejar cambio de tab
     */
    manejarCambioTab(target) {
        // Recargar datos específicos según el tab activo
        switch (target) {
            case '#consolidado':
                if (!this.datosCache.consolidado) {
                    this.cargarStockConsolidado(this.periodoActual);
                }
                break;
            case '#detalle':
                // No hacer nada, se carga cuando se selecciona la franquicia
                break;
        }
    }
    
    /**
     * Actualizar estadísticas rápidas en el sidebar
     */
    actualizarStatsRapidas(periodo) {
        if (this.datosCache.estadisticas) {
            const stats = this.datosCache.estadisticas;
            
            const statTotal = document.getElementById('stat-total');
            const statCumplimiento = document.getElementById('stat-cumplimiento');
            
            if (statTotal) statTotal.textContent = stats.total_franquicias || '0';
            if (statCumplimiento) statCumplimiento.textContent = `${stats.porcentaje_cumplimiento || '0'}%`;
        }
    }
    
    /**
     * Obtener período actual
     */
    obtenerPeriodoActual() {
        const fecha = new Date();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const año = fecha.getFullYear();
        return `${mes}-${año}`;
    }
    
    /**
     * Formatear período para mostrar
     */
    formatearPeriodo(periodo) {
        const [mes, año] = periodo.split('-');
        const meses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        return `${meses[parseInt(mes) - 1]} ${año}`;
    }
    
    /**
     * Formatear fecha
     */
    formatearFecha(fecha) {
        return new Date(fecha).toLocaleDateString('es-AR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    /**
     * Mostrar alerta
     */
    mostrarAlerta(mensaje, tipo = 'info') {
        const container = document.getElementById('alertas-container') || document.body;
        const alertId = `alert-${Date.now()}`;
        
        const alertHTML = `
            <div class="alert alert-${tipo} alert-dismissible fade show" role="alert" id="${alertId}">
                <i class="bi bi-${this.getIconoAlerta(tipo)} me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        container.insertAdjacentHTML('afterbegin', alertHTML);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
    
    /**
     * Obtener icono para alerta
     */
    getIconoAlerta(tipo) {
        const iconos = {
            'success': 'check-circle',
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return iconos[tipo] || 'info-circle';
    }
    
    /**
     * Mostrar loading
     */
    mostrarLoading(mensaje = 'Cargando...') {
        document.getElementById('mensaje-loading').textContent = mensaje;
        const modal = new bootstrap.Modal(document.getElementById('modalLoading'));
        modal.show();
    }
    
    /**
     * Ocultar loading
     */
    ocultarLoading() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalLoading'));
        if (modal) {
            modal.hide();
        }
    }
}

// Variables globales
let panelComercial;

document.addEventListener('DOMContentLoaded', function() {
    panelComercial = new PanelComercialManager();
});

/**
 * Funciones globales para uso en eventos HTML
 */

/**
 * Actualizar todos los datos
 */
async function actualizarDatos() {
    if (panelComercial) {
        await panelComercial.cargarDatosPeriodo(panelComercial.periodoActual);
        panelComercial.mostrarAlerta('Datos actualizados correctamente', 'success');
    }
}

/**
 * Exportar datos a Excel
 */
function exportarDatos(tipo) {
    const periodo = panelComercial.periodoActual;
    const url = `controller/panel_comercial_controller.php?export=1&periodo=${periodo}&tipo=${tipo}`;
    
    // Crear enlace temporal para descarga
    const link = document.createElement('a');
    link.href = url;
    link.download = `${tipo}_${periodo}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    panelComercial.mostrarAlerta('Iniciando descarga del archivo', 'success');
}

/**
 * Exportar stock de franquicia específica desde modal
 */
function exportarStockFranquicia() {
    const nombreFranquicia = document.getElementById('modal-franquicia-nombre').textContent;
    const periodo = panelComercial.periodoActual;
    
    // Obtener datos de la tabla del modal
    const filas = document.querySelectorAll('#modal-tabla-stock tr');
    const datos = [];
    
    // Headers
    datos.push(['Rubro', 'Categoría', 'Cantidad', 'Fecha Actualización']);
    
    // Datos
    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length > 0) {
            datos.push([
                celdas[0].textContent.trim(),
                celdas[1].textContent.trim(),
                celdas[2].textContent.trim(),
                celdas[3].textContent.trim()
            ]);
        }
    });
    
    // Crear CSV
    const csvContent = datos.map(fila => 
        fila.map(celda => `"${celda}"`).join(',')
    ).join('\n');
    
    // Descargar
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `stock_${nombreFranquicia.replace(/\s+/g, '_')}_${periodo}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    panelComercial.mostrarAlerta('Stock exportado correctamente', 'success');
}

/**
 * Imprimir detalle del modal
 */
function imprimirModalStock() {
    const contenido = document.getElementById('modalStock').innerHTML;
    const ventana = window.open('', '_blank');
    
    ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Detalle de Stock</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                @media print {
                    .modal-footer, .btn-close { display: none !important; }
                    .modal-content { box-shadow: none !important; border: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="container mt-3">
                ${contenido}
            </div>
            <script>window.print();</script>
        </body>
        </html>
    `);
    
    ventana.document.close();
}