/**
 * Panel Comercial - JavaScript Simple y Funcional
 */

// Variables globales
let panelComercial;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    panelComercial = new PanelComercialManager();
});

class PanelComercialManager {
    constructor() {
        this.periodoActual = this.obtenerPeriodoActual();
        this.init();
    }
    
    init() {
        this.configurarEventos();
        this.cargarDatos();
    }
    
    configurarEventos() {
        // Selector de período
        const selectorPeriodo = document.getElementById('periodo-analisis');
        if (selectorPeriodo) {
            selectorPeriodo.addEventListener('change', (e) => {
                this.periodoActual = e.target.value;
                this.cargarDatos();
            });
        }
    }
    
    async cargarDatos() {
        try {
            await this.cargarEstadisticas();
            await this.cargarCumplimiento();
            await this.cargarStockConsolidado();
        } catch (error) {
            console.error('Error al cargar datos:', error);
            this.mostrarAlerta('Error al cargar datos', 'danger');
        }
    }
    
    async cargarEstadisticas() {
        try {
            const response = await fetch('controller/panel_comercial_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=obtener_estadisticas&periodo=${this.periodoActual}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.actualizarEstadisticas(data.data);
            }
        } catch (error) {
            console.error('Error al cargar estadísticas:', error);
        }
    }
    
    actualizarEstadisticas(stats) {
        const elementos = {
            'total-franquicias': stats.total_franquicias || '0',
            'franquicias-informaron': stats.franquicias_informaron || '0',
            'franquicias-no-informaron': stats.franquicias_no_informaron || '0',
            'porcentaje-cumplimiento': `${stats.porcentaje_cumplimiento || '0'}%`
        };
        
        Object.entries(elementos).forEach(([id, valor]) => {
            const elemento = document.getElementById(id);
            if (elemento) elemento.textContent = valor;
        });
    }
    
    async cargarCumplimiento() {
        try {
            const response = await fetch('controller/panel_comercial_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=obtener_resumen_cumplimiento&periodo=${this.periodoActual}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarTablaCumplimiento(data.data);
            }
        } catch (error) {
            console.error('Error al cargar cumplimiento:', error);
        }
    }
    
    mostrarTablaCumplimiento(datos) {
        const tbody = document.getElementById('tbody-cumplimiento');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        datos.forEach(franquicia => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${franquicia.franquicia_nombre}</td>
                <td>${franquicia.ciudad || 'N/A'}</td>
                <td class="text-center">
                    <span class="badge ${franquicia.estado === 'INFORMÓ' ? 'bg-success' : 'bg-danger'}">
                        ${franquicia.estado}
                    </span>
                </td>
                <td class="text-center">${franquicia.total_registros || '0'}</td>
                <td>${this.formatearFecha(franquicia.ultima_actualizacion)}</td>
                <td class="text-center">
                    ${franquicia.estado === 'INFORMÓ' ? 
                        `<button class="btn btn-sm btn-outline-primary" onclick="verDetalleFranquicia('${franquicia.franquicia_id}', '${franquicia.franquicia_nombre}')">
                            <i class="bi bi-eye"></i> Ver
                        </button>` : 
                        '<span class="text-muted">-</span>'
                    }
                </td>
            `;
            tbody.appendChild(fila);
        });
    }
    
    async cargarStockConsolidado() {
        try {
            const response = await fetch('controller/panel_comercial_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=obtener_stock_consolidado&periodo=${this.periodoActual}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarTablaConsolidado(data.data);
            }
        } catch (error) {
            console.error('Error al cargar stock consolidado:', error);
        }
    }
    
    mostrarTablaConsolidado(datos) {
        const tbody = document.getElementById('tbody-consolidado');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        datos.forEach(item => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td><strong>${item.rubro}</strong></td>
                <td>${item.categoria}</td>
                <td class="text-center">${item.franquicias_reportan}</td>
                <td class="text-end">${parseInt(item.cantidad_total || 0).toLocaleString()}</td>
                <td class="text-end">${parseInt(item.promedio_cantidad || 0).toLocaleString()}</td>
                <td class="text-end">${parseInt(item.cantidad_minima || 0).toLocaleString()}</td>
                <td class="text-end">${parseInt(item.cantidad_maxima || 0).toLocaleString()}</td>
            `;
            tbody.appendChild(fila);
        });
    }
    
    formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        try {
            return new Date(fecha).toLocaleDateString('es-ES');
        } catch {
            return 'N/A';
        }
    }
    
    obtenerPeriodoActual() {
        const fecha = new Date();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const año = fecha.getFullYear();
        return `${mes}-${año}`;
    }
    
    mostrarAlerta(mensaje, tipo = 'info') {
        // Crear alerta Bootstrap
        const alertContainer = document.getElementById('alertas-container') || document.body;
        const alert = document.createElement('div');
        alert.className = `alert alert-${tipo} alert-dismissible fade show`;
        alert.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        alertContainer.appendChild(alert);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 5000);
    }
    
    exportarDatos(tipo) {
        if (!this.periodoActual) {
            this.mostrarAlerta('Debe seleccionar un período', 'warning');
            return;
        }
        
        // Crear URL para descarga
        const url = `controller/panel_comercial_controller.php?export=1&periodo=${this.periodoActual}&tipo=${tipo}`;
        
        // Crear enlace temporal
        const link = document.createElement('a');
        link.href = url;
        link.download = `${tipo}_${this.periodoActual}.csv`;
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        this.mostrarAlerta('Iniciando descarga del archivo Excel', 'success');
    }
}

// Funciones globales para uso en HTML
function actualizarDatos() {
    if (panelComercial) {
        panelComercial.cargarDatos();
    }
}

function exportarDatos(tipo) {
    if (panelComercial) {
        panelComercial.exportarDatos(tipo);
    }
}

function verDetalleFranquicia(franquiciaId, nombreFranquicia) {
    if (panelComercial) {
        // Por ahora solo mostrar alerta
        panelComercial.mostrarAlerta(`Funcionalidad de detalle en desarrollo para ${nombreFranquicia}`, 'info');
    }
}
