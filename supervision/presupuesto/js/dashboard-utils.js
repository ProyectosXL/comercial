
// dashboard-utils.js - Funciones utilitarias
const DashboardUtils = {
    
    // Función para mostrar/ocultar indicador de carga
    mostrarCargando(mostrar) {
        if (mostrar) {
            $('body').addClass('wait');
        } else {
            $('body').removeClass('wait');
        }
    },
    
    // Función para mostrar mensaje de error
    mostrarError(mensaje) {
        // Si SweetAlert2 está disponible, usarlo
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: mensaje,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        } else {
            // Fallback a alert nativo
            alert('Error: ' + mensaje);
        }
    },
    
    // Función para formatear importes
    formatearImporte(importe, abreviado = false) {
        importe = parseFloat(importe);
        
        if (isNaN(importe)) {
            return '$0';
        }
        
        importe = Math.round(importe);
        
        if (abreviado && importe >= 1000) {
            return '$' + (importe / 1000).toFixed(1) + 'K';
        }
        
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(importe);
    },
    
    // Función auxiliar para convertir número de mes a nombre
    mesANombre(mes) {
        const nombresMeses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        
        // Ajustar índice (1-12 a 0-11)
        return nombresMeses[(mes - 1) % 12];
    },
    
    // Función para debouncing de eventos
    debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    },
    
    // Función para validar fechas
    validarFecha(fecha) {
        if (!fecha) return false;
        const fechaObj = new Date(fecha);
        return fechaObj instanceof Date && !isNaN(fechaObj);
    },
    
    // Función para obtener rango de fechas
    obtenerRangoFechas(tipo, cantidad) {
        const hoy = new Date();
        const fechaFin = new Date(hoy);
        const fechaInicio = new Date(hoy);
        
        switch (tipo) {
            case 'dias':
                fechaInicio.setDate(hoy.getDate() - cantidad);
                break;
            case 'semanas':
                fechaInicio.setDate(hoy.getDate() - (cantidad * 7));
                break;
            case 'meses':
                fechaInicio.setMonth(hoy.getMonth() - cantidad);
                break;
            case 'años':
                fechaInicio.setFullYear(hoy.getFullYear() - cantidad);
                break;
            default:
                return null;
        }
        
        return {
            inicio: fechaInicio.toISOString().split('T')[0],
            fin: fechaFin.toISOString().split('T')[0]
        };
    },
    
    // Función para convertir datos a CSV
    exportarCSV(datos, nombreArchivo) {
        if (!datos || !datos.length) {
            DashboardUtils.mostrarError('No hay datos para exportar');
            return;
        }
        
        // Obtener headers de las claves del primer objeto
        const headers = Object.keys(datos[0]);
        
        // Crear contenido CSV
        let csvContent = headers.join(',') + '\n';
        
        datos.forEach(row => {
            const values = headers.map(header => {
                let cell = row[header] || '';
                // Escapar comillas y agregar comillas si contiene comas
                if (cell.toString().includes(',') || cell.toString().includes('"')) {
                    cell = '"' + cell.toString().replace(/"/g, '""') + '"';
                }
                return cell;
            });
            csvContent += values.join(',') + '\n';
        });
        
        // Crear y descargar archivo
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', nombreArchivo || 'datos.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    },
    
    // Función para logging con nivel
    log(mensaje, nivel = 'info') {
        const timestamp = new Date().toISOString();
        const prefijo = `[${timestamp}] [${nivel.toUpperCase()}]`;
        
        switch (nivel) {
            case 'error':
                console.error(prefijo, mensaje);
                break;
            case 'warn':
                console.warn(prefijo, mensaje);
                break;
            case 'debug':
                console.debug(prefijo, mensaje);
                break;
            default:
                console.log(prefijo, mensaje);
        }
    },
    
    // Función para almacenamiento local (si está disponible)
    guardarEnStorage(clave, valor) {
        try {
            if (typeof Storage !== 'undefined') {
                localStorage.setItem(`dashboard_${clave}`, JSON.stringify(valor));
                return true;
            }
        } catch (e) {
            DashboardUtils.log('Error al guardar en localStorage: ' + e.message, 'warn');
        }
        return false;
    },
    
    // Función para recuperar del almacenamiento local
    obtenerDeStorage(clave, valorPorDefecto = null) {
        try {
            if (typeof Storage !== 'undefined') {
                const item = localStorage.getItem(`dashboard_${clave}`);
                return item ? JSON.parse(item) : valorPorDefecto;
            }
        } catch (e) {
            DashboardUtils.log('Error al leer de localStorage: ' + e.message, 'warn');
        }
        return valorPorDefecto;
    },
    
    // Función para limpiar almacenamiento
    limpiarStorage() {
        try {
            if (typeof Storage !== 'undefined') {
                const keys = Object.keys(localStorage);
                keys.forEach(key => {
                    if (key.startsWith('dashboard_')) {
                        localStorage.removeItem(key);
                    }
                });
                return true;
            }
        } catch (e) {
            DashboardUtils.log('Error al limpiar localStorage: ' + e.message, 'warn');
        }
        return false;
    }
};