/**
 * JavaScript Principal - Funcionalidades Comunes
 */

class MainSystem {
    constructor() {
        this.init();
    }
    
    init() {
        this.configurarEventosGlobales();
        this.inicializarTooltips();
        this.configurarSidebar();
        this.configurarNotificaciones();
    }
    
    /**
     * Configurar eventos globales del sistema
     */
    configurarEventosGlobales() {
        // Prevenir envío de formularios vacíos
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.classList.contains('prevent-empty')) {
                const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
                let hasEmpty = false;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        hasEmpty = true;
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
                
                if (hasEmpty) {
                    e.preventDefault();
                    this.mostrarNotificacion('Por favor complete todos los campos requeridos', 'warning');
                }
            }
        });
        
        // Limpiar validaciones al escribir
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
            }
        });
        
        // Confirmar acciones destructivas
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('confirm-action') || 
                e.target.closest('.confirm-action')) {
                
                const mensaje = e.target.dataset.confirm || 
                               e.target.closest('.confirm-action')?.dataset.confirm ||
                               '¿Está seguro de realizar esta acción?';
                
                if (!confirm(mensaje)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        });
        
        // Auto-guardar en localStorage (solo para borrador)
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('auto-save')) {
                const key = `autosave_${e.target.name || e.target.id}`;
                localStorage.setItem(key, e.target.value);
            }
        });
        
        // Restaurar auto-guardado
        document.querySelectorAll('.auto-save').forEach(input => {
            const key = `autosave_${input.name || input.id}`;
            const saved = localStorage.getItem(key);
            if (saved && !input.value) {
                input.value = saved;
            }
        });
    }
    
    /**
     * Inicializar tooltips de Bootstrap
     */
    inicializarTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    /**
     * Configurar comportamiento del sidebar
     */
    configurarSidebar() {
        // Toggle sidebar en móvil con el nuevo botón específico
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                sidebar.classList.toggle('show');
                
                // Mostrar/ocultar overlay
                if (overlay) {
                    overlay.classList.toggle('show');
                }
            });
            
            // Cerrar sidebar al hacer clic en el overlay
            if (overlay) {
                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
            
            // Cerrar sidebar al hacer clic fuera (solo en móvil)
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 992) {
                    if (!sidebar.contains(e.target) && 
                        !toggleBtn.contains(e.target) && 
                        !e.target.closest('.dropdown-menu')) {
                        sidebar.classList.remove('show');
                        if (overlay) {
                            overlay.classList.remove('show');
                        }
                    }
                }
            });
        }
        
        // Manejar redimensionado de ventana
        window.addEventListener('resize', () => {
            if (window.innerWidth > 992 && sidebar) {
                sidebar.classList.remove('show');
                if (overlay) {
                    overlay.classList.remove('show');
                }
            }
        });
    }
    
    /**
     * Configurar sistema de notificaciones
     */
    configurarNotificaciones() {
        // Crear contenedor de notificaciones si no existe
        if (!document.getElementById('notifications-container')) {
            const container = document.createElement('div');
            container.id = 'notifications-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
    }
    
    /**
     * Mostrar notificación toast
     */
    mostrarNotificacion(mensaje, tipo = 'info', duracion = 5000) {
        const container = document.getElementById('notifications-container');
        const toastId = `toast-${Date.now()}`;
        
        // Mapear tipos comunes a los tipos de Bootstrap
        const tipoMapping = {
            'error': 'danger',
            'Error': 'danger',
            'ERROR': 'danger',
            'success': 'success',
            'warning': 'warning',
            'info': 'info',
            'danger': 'danger'
        };
        
        const tipoFinal = tipoMapping[tipo] || 'info';
        
        const iconos = {
            'success': 'check-circle-fill',
            'danger': 'exclamation-triangle-fill',
            'warning': 'exclamation-triangle-fill',
            'info': 'info-circle-fill'
        };
        
        const colores = {
            'success': 'text-success',
            'danger': 'text-danger',
            'warning': 'text-warning',
            'info': 'text-primary'
        };
        
        const fondos = {
            'success': 'bg-success',
            'danger': 'bg-danger',
            'warning': 'bg-warning',
            'info': 'bg-primary'
        };
        
        const toastHTML = `
            <div class="toast align-items-center text-white ${fondos[tipoFinal]} border-0" role="alert" id="${toastId}" data-bs-autohide="${duracion > 0 ? 'true' : 'false'}" data-bs-delay="${duracion}">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center">
                        <i class="bi bi-${iconos[tipoFinal]} me-2" style="font-size: 1.2rem;"></i>
                        <span>${mensaje}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        
        toast.show();
        
        // Remover del DOM cuando se oculte
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
        
        return toast;
    }
    
    /**
     * Formatear números con separadores de miles
     */
    formatearNumero(numero) {
        return new Intl.NumberFormat('es-AR').format(numero);
    }
    
    /**
     * Formatear fechas
     */
    formatearFecha(fecha, opciones = {}) {
        const defaults = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        };
        
        const formatOptions = { ...defaults, ...opciones };
        return new Date(fecha).toLocaleDateString('es-AR', formatOptions);
    }
    
    /**
     * Formatear fechas con hora
     */
    formatearFechaHora(fecha) {
        return new Date(fecha).toLocaleString('es-AR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    /**
     * Validar formulario
     */
    validarFormulario(form) {
        let esValido = true;
        const errores = [];
        
        // Validar campos requeridos
        const camposRequeridos = form.querySelectorAll('[required]');
        camposRequeridos.forEach(campo => {
            if (!campo.value.trim()) {
                campo.classList.add('is-invalid');
                errores.push(`El campo ${campo.name || campo.id || 'requerido'} es obligatorio`);
                esValido = false;
            } else {
                campo.classList.remove('is-invalid');
            }
        });
        
        // Validar emails
        const camposEmail = form.querySelectorAll('input[type="email"]');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        camposEmail.forEach(campo => {
            if (campo.value && !emailRegex.test(campo.value)) {
                campo.classList.add('is-invalid');
                errores.push('El formato del email no es válido');
                esValido = false;
            }
        });
        
        // Validar números
        const camposNumero = form.querySelectorAll('input[type="number"]');
        camposNumero.forEach(campo => {
            const min = campo.getAttribute('min');
            const max = campo.getAttribute('max');
            const valor = parseFloat(campo.value);
            
            if (campo.value && isNaN(valor)) {
                campo.classList.add('is-invalid');
                errores.push('Debe ingresar un número válido');
                esValido = false;
            } else if (min !== null && valor < parseFloat(min)) {
                campo.classList.add('is-invalid');
                errores.push(`El valor debe ser mayor o igual a ${min}`);
                esValido = false;
            } else if (max !== null && valor > parseFloat(max)) {
                campo.classList.add('is-invalid');
                errores.push(`El valor debe ser menor o igual a ${max}`);
                esValido = false;
            }
        });
        
        if (!esValido) {
            this.mostrarNotificacion(errores[0], 'danger');
        }
        
        return { esValido, errores };
    }
    
    /**
     * Realizar petición AJAX
     */
    async realizarPeticion(url, opciones = {}) {
        const defaults = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'same-origin'
        };
        
        const config = { ...defaults, ...opciones };
        
        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }
        } catch (error) {
            console.error('Error en petición:', error);
            this.mostrarNotificacion('Error de conexión con el servidor', 'danger');
            throw error;
        }
    }
    
    /**
     * Debounce function para optimizar búsquedas
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Throttle function para limitar eventos
     */
    throttle(func, wait) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, wait);
            }
        };
    }
    
    /**
     * Copiar texto al portapapeles
     */
    async copiarAlPortapapeles(texto) {
        try {
            await navigator.clipboard.writeText(texto);
            this.mostrarNotificacion('Texto copiado al portapapeles', 'success');
            return true;
        } catch (error) {
            console.error('Error al copiar:', error);
            this.mostrarNotificacion('No se pudo copiar el texto', 'danger');
            return false;
        }
    }
    
    /**
     * Exportar tabla a Excel (XLSX)
     */
    exportarTablaExcel(tabla, nombreArchivo = 'datos') {
        const filas = tabla.querySelectorAll('tr');
        const datos = [];
        
        filas.forEach(fila => {
            const celdas = fila.querySelectorAll('th, td');
            const filaData = [];
            
            celdas.forEach(celda => {
                let texto = celda.textContent.trim();
                filaData.push(texto);
            });
            
            if (filaData.length > 0) {
                datos.push(filaData);
            }
        });
        
        this.exportarAExcel(datos, nombreArchivo, 'Datos');
    }
    
    /**
     * Exportar datos a Excel usando SheetJS
     */
    exportarAExcel(datos, nombreArchivo, nombreHoja = 'Hoja1') {
        try {
            console.log('Intentando exportar a Excel...', {XLSX: typeof XLSX});
            
            // Verificar si SheetJS está disponible
            if (typeof XLSX === 'undefined') {
                console.warn('SheetJS no está disponible, intentando cargar dinámicamente...');
                this.cargarSheetJSYExportar(datos, nombreArchivo, nombreHoja);
                return;
            }
            
            console.log('SheetJS disponible, procediendo con exportación...');
            
            // Crear libro de trabajo
            const wb = XLSX.utils.book_new();
            
            // Crear hoja de trabajo con los datos
            const ws = XLSX.utils.aoa_to_sheet(datos);
            
            // Estilizar un poco la hoja
            if (ws['!ref']) {
                const range = XLSX.utils.decode_range(ws['!ref']);
                // Hacer la primera fila bold (header)
                for (let C = range.s.c; C <= range.e.c; ++C) {
                    const cell_address = XLSX.utils.encode_cell({r: 0, c: C});
                    if (!ws[cell_address]) continue;
                    if (!ws[cell_address].s) ws[cell_address].s = {};
                    ws[cell_address].s.font = {bold: true};
                }
            }
            
            // Agregar la hoja al libro
            XLSX.utils.book_append_sheet(wb, ws, nombreHoja);
            
            // Generar y descargar el archivo
            XLSX.writeFile(wb, `${nombreArchivo}.xlsx`);
            console.log('Archivo Excel generado exitosamente:', `${nombreArchivo}.xlsx`);
            
        } catch (error) {
            console.error('Error al exportar a Excel:', error);
            console.warn('Usando fallback a CSV...');
            this.exportarDatosCSV(datos, nombreArchivo + '.csv');
        }
    }
    
    /**
     * Cargar SheetJS dinámicamente si no está disponible
     */
    cargarSheetJSYExportar(datos, nombreArchivo, nombreHoja) {
        console.log('Cargando SheetJS dinámicamente...');
        
        // Intentar cargar SheetJS desde CDN
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        
        script.onload = () => {
            console.log('SheetJS cargado dinámicamente, reintentando exportación...');
            setTimeout(() => {
                this.exportarAExcel(datos, nombreArchivo, nombreHoja);
            }, 100);
        };
        
        script.onerror = () => {
            console.error('No se pudo cargar SheetJS, usando CSV como fallback');
            this.exportarDatosCSV(datos, nombreArchivo + '.csv');
        };
        
        document.head.appendChild(script);
    }
    
    /**
     * Exportar datos como CSV (método de respaldo)
     */
    exportarDatosCSV(datos, nombreArchivo) {
        const csvContent = datos.map(fila => 
            fila.map(celda => `"${String(celda).replace(/"/g, '""')}"`).join(',')
        ).join('\n');
        
        this.descargarArchivo(csvContent, nombreArchivo, 'text/csv');
    }
    
    /**
     * Función de test para verificar que Excel funciona
     */
    testExportarExcel() {
        const datosTest = [
            ['Nombre', 'Apellido', 'Edad'],
            ['Juan', 'Pérez', 25],
            ['María', 'García', 30],
            ['Pedro', 'López', 28]
        ];
        
        console.log('Ejecutando test de exportación Excel...');
        this.exportarAExcel(datosTest, 'test_excel', 'Datos de Test');
    }
    
    /**
     * Descargar archivo
     */
    descargarArchivo(contenido, nombreArchivo, tipoMime = 'text/plain') {
        const blob = new Blob([contenido], { type: `${tipoMime};charset=utf-8;` });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', nombreArchivo);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
        this.mostrarNotificacion(`Archivo ${nombreArchivo} descargado`, 'success');
    }
    
    /**
     * Filtrar tabla en tiempo real
     */
    configurarFiltroTabla(inputFiltro, tabla, columnas = []) {
        const filtrarTabla = this.debounce((valorFiltro) => {
            const filas = tabla.querySelectorAll('tbody tr');
            const filtro = valorFiltro.toLowerCase().trim();
            
            filas.forEach(fila => {
                if (!filtro) {
                    fila.style.display = '';
                    return;
                }
                
                let mostrarFila = false;
                const celdas = fila.querySelectorAll('td');
                
                // Si no se especifican columnas, buscar en todas
                const columnasABuscar = columnas.length > 0 ? columnas : 
                                       Array.from(celdas).map((_, index) => index);
                
                columnasABuscar.forEach(index => {
                    if (celdas[index] && 
                        celdas[index].textContent.toLowerCase().includes(filtro)) {
                        mostrarFila = true;
                    }
                });
                
                fila.style.display = mostrarFila ? '' : 'none';
            });
            
            // Actualizar contador de resultados si existe
            const contador = document.querySelector(`#contador-${tabla.id}`);
            if (contador) {
                const filasVisibles = tabla.querySelectorAll('tbody tr[style=""], tbody tr:not([style])');
                contador.textContent = `${filasVisibles.length} resultados`;
            }
        }, 300);
        
        inputFiltro.addEventListener('input', (e) => {
            filtrarTabla(e.target.value);
        });
    }
    
    /**
     * Configurar ordenamiento de tabla
     */
    configurarOrdenamientoTabla(tabla) {
        const headers = tabla.querySelectorAll('thead th[data-sort]');
        
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.classList.add('user-select-none');
            
            // Agregar icono de ordenamiento
            if (!header.querySelector('.sort-icon')) {
                header.innerHTML += ' <i class="bi bi-arrow-down-up sort-icon text-muted"></i>';
            }
            
            header.addEventListener('click', () => {
                this.ordenarTabla(tabla, header.dataset.sort, header);
            });
        });
    }
    
    /**
     * Ordenar tabla por columna
     */
    ordenarTabla(tabla, columna, header) {
        const tbody = tabla.querySelector('tbody');
        const filas = Array.from(tbody.querySelectorAll('tr'));
        const columnaIndex = parseInt(columna);
        
        // Determinar dirección de ordenamiento
        let direccion = 'asc';
        if (header.classList.contains('sort-asc')) {
            direccion = 'desc';
        }
        
        // Limpiar iconos de otros headers
        tabla.querySelectorAll('thead th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
            const icon = th.querySelector('.sort-icon');
            if (icon) {
                icon.className = 'bi bi-arrow-down-up sort-icon text-muted';
            }
        });
        
        // Actualizar header actual
        header.classList.add(`sort-${direccion}`);
        const icon = header.querySelector('.sort-icon');
        if (icon) {
            icon.className = direccion === 'asc' ? 
                            'bi bi-arrow-up sort-icon text-primary' : 
                            'bi bi-arrow-down sort-icon text-primary';
        }
        
        // Ordenar filas
        filas.sort((a, b) => {
            const celdaA = a.querySelectorAll('td')[columnaIndex];
            const celdaB = b.querySelectorAll('td')[columnaIndex];
            
            if (!celdaA || !celdaB) return 0;
            
            let valorA = celdaA.textContent.trim();
            let valorB = celdaB.textContent.trim();
            
            // Detectar si son números
            const esNumeroA = !isNaN(parseFloat(valorA.replace(/[^\d.-]/g, '')));
            const esNumeroB = !isNaN(parseFloat(valorB.replace(/[^\d.-]/g, '')));
            
            if (esNumeroA && esNumeroB) {
                valorA = parseFloat(valorA.replace(/[^\d.-]/g, ''));
                valorB = parseFloat(valorB.replace(/[^\d.-]/g, ''));
            } else {
                valorA = valorA.toLowerCase();
                valorB = valorB.toLowerCase();
            }
            
            if (direccion === 'asc') {
                return valorA > valorB ? 1 : -1;
            } else {
                return valorA < valorB ? 1 : -1;
            }
        });
        
        // Reordenar en el DOM
        filas.forEach(fila => tbody.appendChild(fila));
    }
    
    /**
     * Obtener parámetros de URL
     */
    obtenerParametrosURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const params = {};
        
        for (const [key, value] of urlParams) {
            params[key] = value;
        }
        
        return params;
    }
    
    /**
     * Actualizar URL sin recargar página
     */
    actualizarURL(parametros) {
        const url = new URL(window.location);
        
        Object.keys(parametros).forEach(key => {
            if (parametros[key] !== null && parametros[key] !== undefined) {
                url.searchParams.set(key, parametros[key]);
            } else {
                url.searchParams.delete(key);
            }
        });
        
        window.history.pushState({}, '', url);
    }
    
    /**
     * Verificar si el usuario está online
     */
    verificarConexion() {
        const actualizarEstado = () => {
            if (navigator.onLine) {
                this.mostrarNotificacion('Conexión restaurada', 'success', 3000);
            } else {
                this.mostrarNotificacion('Sin conexión a internet', 'warning', 0);
            }
        };
        
        window.addEventListener('online', actualizarEstado);
        window.addEventListener('offline', actualizarEstado);
    }
    
    /**
     * Configurar atajos de teclado
     */
    configurarAtajosTeclado() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+S para guardar
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const btnGuardar = document.querySelector('[data-action="save"], .btn-save, #btn-guardar, #btn-guardar-stock');
                if (btnGuardar && !btnGuardar.disabled) {
                    btnGuardar.click();
                }
            }
            
            // Escape para cerrar modales
            if (e.key === 'Escape') {
                const modalAbierto = document.querySelector('.modal.show');
                if (modalAbierto) {
                    const modal = bootstrap.Modal.getInstance(modalAbierto);
                    if (modal) {
                        modal.hide();
                    }
                }
            }
            
            // F1 para ayuda
            if (e.key === 'F1') {
                e.preventDefault();
                const btnAyuda = document.querySelector('[data-bs-target="#modalAyuda"]');
                if (btnAyuda) {
                    btnAyuda.click();
                }
            }
        });
    }
    
    /**
     * Método simple de test
     */
    simpleTest() {
        console.log('Método simpleTest funcionando');
        return 'OK';
    }
}

// Inicializar sistema principal
let mainSystem;

// Exponer MainSystem globalmente de inmediato
window.MainSystem = MainSystem;

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded - Verificando mainSystem...');
    
    // Si ya fue inicializado por la función inmediata, usarlo
    if (window.mainSystem) {
        mainSystem = window.mainSystem;
        console.log('mainSystem ya estaba inicializado');
    } else {
        console.log('Inicializando mainSystem...');
        try {
            mainSystem = new MainSystem();
            window.mainSystem = mainSystem;
            console.log('mainSystem inicializado exitosamente');
        } catch (error) {
            console.error('Error inicializando mainSystem:', error);
            return;
        }
    }
    
    // Configurar funcionalidades adicionales
    configurarTablas();
    configurarFormularios();
    inicializarComponentes();
});

/**
 * Configurar todas las tablas del sistema
 */
function configurarTablas() {
    // Configurar filtros de tabla
    document.querySelectorAll('table[data-filterable]').forEach(tabla => {
        const filtro = document.querySelector(`#filtro-${tabla.id}`);
        if (filtro) {
            mainSystem.configurarFiltroTabla(filtro, tabla);
        }
    });
    
    // Configurar ordenamiento
    document.querySelectorAll('table[data-sortable]').forEach(tabla => {
        mainSystem.configurarOrdenamientoTabla(tabla);
    });
}

/**
 * Configurar formularios del sistema
 */
function configurarFormularios() {
    // Auto-focus en primer campo de formularios modales
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', () => {
            const primerInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
            if (primerInput) {
                primerInput.focus();
            }
        });
    });
    
    // Validación en tiempo real
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', (e) => {
            const validacion = mainSystem.validarFormulario(form);
            if (!validacion.esValido) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Inicializar componentes adicionales
 */
function inicializarComponentes() {
    // Inicializar popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Configurar atajos de teclado
    mainSystem.configurarAtajosTeclado();
    
    // Verificar conexión
    mainSystem.verificarConexion();
    
    // Auto-actualizar timestamps
    setInterval(actualizarTimestamps, 60000); // Cada minuto
}

/**
 * Actualizar timestamps relativos
 */
function actualizarTimestamps() {
    document.querySelectorAll('[data-timestamp]').forEach(elemento => {
        const timestamp = parseInt(elemento.dataset.timestamp);
        const ahora = Date.now();
        const diferencia = ahora - timestamp;
        
        const minutos = Math.floor(diferencia / 60000);
        const horas = Math.floor(minutos / 60);
        const dias = Math.floor(horas / 24);
        
        let texto = '';
        if (dias > 0) {
            texto = `hace ${dias} día${dias > 1 ? 's' : ''}`;
        } else if (horas > 0) {
            texto = `hace ${horas} hora${horas > 1 ? 's' : ''}`;
        } else if (minutos > 0) {
            texto = `hace ${minutos} minuto${minutos > 1 ? 's' : ''}`;
        } else {
            texto = 'hace un momento';
        }
        
        elemento.textContent = texto;
    });
}

/**
 * Funciones globales útiles
 */
// Inicialización inmediata para garantizar disponibilidad
try {
    if (typeof window !== 'undefined') {
        // Exponer la clase MainSystem inmediatamente
        window.MainSystem = MainSystem;
        
        // Crear una función para inicializar cuando el DOM esté listo
        window.initMainSystem = function() {
            if (!window.mainSystem) {
                console.log('Inicializando mainSystem desde initMainSystem...');
                window.mainSystem = new MainSystem();
            }
            return window.mainSystem;
        };
        
        // Si el DOM ya está listo, inicializar inmediatamente
        if (document.readyState === 'loading') {
            // DOM aún cargando, esperar al evento
            document.addEventListener('DOMContentLoaded', window.initMainSystem);
        } else {
            // DOM ya está listo, inicializar inmediatamente
            window.initMainSystem();
        }
    }
} catch (error) {
    console.error('Error en inicialización inmediata de mainSystem:', error);
}