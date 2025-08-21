
/**
 * presupuestos-utils.js
 * Funciones utilitarias compartidas por todos los módulos
 * Afecta: Funciones helper usadas en toda la aplicación
 */

const PresupuestosUtils = {
    
    /**
     * Formatea un número con separador de miles y sin decimales
     */
    formatearNumero(numero) {
        if (numero === null || numero === undefined || numero === '') return '0';
        
        const entero = Math.round(parseFloat(numero) || 0);
        return entero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    },
    
    /**
     * Convierte un número formateado a valor numérico
     */
    parsearNumero(textoFormateado) {
        if (!textoFormateado) return 0;
        return parseFloat(textoFormateado.toString().replace(/\./g, '')) || 0;
    },
    
    /**
     * Formatea un período para mostrar (YYYY-MM -> Mes YYYY)
     */
    formatearPeriodo(periodo) {
        if (!periodo || !periodo.includes('-')) return periodo;
        
        const [anio, mes] = periodo.split('-');
        const nombresMeses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        
        const mesNumero = parseInt(mes, 10);
        if (mesNumero < 1 || mesNumero > 12) return periodo;
        
        return `${nombresMeses[mesNumero - 1]} ${anio}`;
    },
    
    /**
     * Formatea una fecha para mostrar
     */
    formatearFecha(fechaString) {
        if (!fechaString) return 'N/A';
        
        try {
            const fecha = new Date(fechaString);
            if (isNaN(fecha.getTime())) return fechaString;
            
            return fecha.toLocaleString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            console.warn('Error formateando fecha:', e);
            return fechaString;
        }
    },
    
    /**
     * Verifica si un período es el actual
     */
    esPeriodoActual(periodo) {
        const fechaActual = new Date();
        const mesActual = `${fechaActual.getFullYear()}-${(fechaActual.getMonth() + 1).toString().padStart(2, '0')}`;
        return periodo === mesActual;
    },
    
    /**
     * Verifica si un período es editable (actual o futuro)
     */
    esPeriodoEditable(periodo) {
        const fechaActual = new Date();
        const mesActual = `${fechaActual.getFullYear()}-${(fechaActual.getMonth() + 1).toString().padStart(2, '0')}`;
        return periodo >= mesActual;
    },
    
    /**
     * Valida que un período sea editable y muestra error si no lo es
     */
    validarPeriodoEditable(periodo) {
        if (!this.esPeriodoEditable(periodo)) {
            this.mostrarError('No se pueden editar presupuestos de períodos anteriores');
            return false;
        }
        return true;
    },
    
    /**
     * Muestra mensaje de error usando SweetAlert2 o alert nativo
     */
    mostrarError(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje,
                confirmButtonText: 'Entendido'
            });
        } else {
            alert('Error: ' + mensaje);
        }
    },
    
    /**
     * Muestra mensaje de éxito
     */
    mostrarExito(mensaje, timer = 2000) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: mensaje,
                timer: timer,
                timerProgressBar: true,
                showConfirmButton: timer === 0
            });
        } else {
            alert('Éxito: ' + mensaje);
        }
    },
    
    /**
     * Muestra mensaje de advertencia
     */
    mostrarAdvertencia(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: mensaje,
                confirmButtonText: 'Entendido'
            });
        } else {
            alert('Atención: ' + mensaje);
        }
    },
    
    /**
     * Muestra mensaje informativo
     */
    mostrarInformacion(titulo, mensaje, timer = 0) {
        if (typeof Swal !== 'undefined') {
            const config = {
                icon: 'info',
                title: titulo,
                text: mensaje,
                confirmButtonText: 'Entendido'
            };
            
            if (timer > 0) {
                config.timer = timer;
                config.timerProgressBar = true;
                config.showConfirmButton = false;
            }
            
            Swal.fire(config);
        } else {
            alert(titulo + ': ' + mensaje);
        }
    },
    
    /**
     * Muestra diálogo de confirmación
     */
    async confirmar(titulo, mensaje, tipo = 'question') {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                title: titulo,
                text: mensaje,
                icon: tipo,
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: tipo === 'warning' ? '#d33' : '#3085d6'
            });
            
            return result.isConfirmed;
        } else {
            return confirm(titulo + '\n\n' + mensaje);
        }
    },
    
    /**
     * Muestra indicador de carga
     */
    mostrarCargando(mensaje = 'Cargando...') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: mensaje,
                didOpen: () => {
                    Swal.showLoading();
                },
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });
        } else {
            // Fallback con clase CSS
            document.body.classList.add('loading');
        }
    },
    
    /**
     * Oculta indicador de carga
     */
    ocultarCargando() {
        if (typeof Swal !== 'undefined') {
            Swal.close();
        } else {
            document.body.classList.remove('loading');
        }
    },
    
    /**
     * Debounce para limitar ejecución de funciones
     */
    debounce(func, wait, immediate = false) {
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
    
    /**
     * Throttle para limitar frecuencia de ejecución
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    /**
     * Valida formato de email
     */
    validarEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },
    
    /**
     * Valida que un valor sea numérico
     */
    esNumerico(valor) {
        return !isNaN(parseFloat(valor)) && isFinite(valor);
    },
    
    /**
     * Escapa HTML para prevenir XSS
     */
    escaparHTML(texto) {
        const div = document.createElement('div');
        div.textContent = texto;
        return div.innerHTML;
    },
    
    /**
     * Genera un ID único
     */
    generarID() {
        return 'id_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    },
    
    /**
     * Copia texto al portapapeles
     */
    async copiarAlPortapapeles(texto) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(texto);
                this.mostrarExito('Texto copiado al portapapeles', 1000);
                return true;
            } else {
                // Fallback para navegadores sin soporte
                const textArea = document.createElement('textarea');
                textArea.value = texto;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                const resultado = document.execCommand('copy');
                textArea.remove();
                
                if (resultado) {
                    this.mostrarExito('Texto copiado al portapapeles', 1000);
                }
                
                return resultado;
            }
        } catch (error) {
            console.error('Error copiando al portapapeles:', error);
            this.mostrarError('No se pudo copiar al portapapeles');
            return false;
        }
    },
    
    /**
     * Exporta datos como CSV
     */
    exportarCSV(datos, nombreArchivo = 'datos.csv') {
        if (!datos || !datos.length) {
            this.mostrarError('No hay datos para exportar');
            return;
        }
        
        try {
            // Obtener headers del primer objeto
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
                link.setAttribute('download', nombreArchivo);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                this.mostrarExito('Archivo CSV descargado', 1500);
            } else {
                this.mostrarError('Su navegador no soporta la descarga de archivos');
            }
            
        } catch (error) {
            console.error('Error exportando CSV:', error);
            this.mostrarError('Error al exportar CSV: ' + error.message);
        }
    },
    
    /**
     * Logging con diferentes niveles
     */
    log(mensaje, nivel = 'info', datos = null) {
        if (!PresupuestosConfig.debug && nivel === 'debug') return;
        
        const timestamp = new Date().toISOString();
        const prefijo = `[${timestamp}] [PRESUPUESTOS] [${nivel.toUpperCase()}]`;
        
        switch (nivel) {
            case 'error':
                console.error(prefijo, mensaje, datos || '');
                break;
            case 'warn':
                console.warn(prefijo, mensaje, datos || '');
                break;
            case 'debug':
                console.debug(prefijo, mensaje, datos || '');
                break;
            default:
                console.log(prefijo, mensaje, datos || '');
        }
    },
    
    /**
     * Detecta si estamos en dispositivo móvil
     */
    esMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },
    
    /**
     * Obtiene información del navegador
     */
    obtenerInfoNavegador() {
        return {
            userAgent: navigator.userAgent,
            idioma: navigator.language,
            plataforma: navigator.platform,
            esMobile: this.esMobile(),
            cookies: navigator.cookieEnabled,
            online: navigator.onLine
        };
    }
};