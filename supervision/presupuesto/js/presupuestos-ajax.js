
/**
 * presupuestos-ajax.js
 * Maneja todas las comunicaciones AJAX con el servidor
 * Afecta: Comunicación con todos los endpoints PHP
 */

const PresupuestosAjax = {
    
    /**
     * Inicializa el módulo AJAX
     */
    init() {
        console.log('Módulo AJAX inicializado');
        this.configurarTimeouts();
    },
    
    /**
     * Configura timeouts globales
     * @private
     */
    configurarTimeouts() {
        // Configurar timeout por defecto para fetch
        this.timeoutDefault = PresupuestosConfig.timeout || 30000;
    },
    
    /**
     * Carga los presupuestos para el período seleccionado
     */
    async cargarPresupuestos() {
        const periodo = document.getElementById('periodoPresupuesto').value;
        if (!periodo) {
            console.warn('No hay período seleccionado para cargar presupuestos');
            return;
        }
        
        try {
            PresupuestosUtils.mostrarCargando('Cargando presupuestos...');
            
            const url = `${PresupuestosConfig.endpoints.obtenerPresupuestos}?periodo=${periodo}`;
            const data = await this.obtenerDatos(url);
            
            if (data.success && data.data) {
                PresupuestosFormulario.llenarFormulario(data.data);
                
                if (PresupuestosConfig.debug) {
                    console.log('Presupuestos cargados exitosamente:', data.data);
                }
            } else {
                console.warn('No se encontraron presupuestos para el período:', periodo);
                // Limpiar formulario si no hay datos
                PresupuestosFormulario.llenarFormulario({});
            }
            
        } catch (error) {
            console.error('Error cargando presupuestos:', error);
            PresupuestosUtils.mostrarError('No se pudieron cargar los presupuestos: ' + error.message);
        } finally {
            PresupuestosUtils.ocultarCargando();
            
            // Actualizar estado de la UI
            PresupuestosUI.actualizarEstadoPeriodo();
            PresupuestosObservaciones.actualizarBotonesObservaciones();
        }
    },
    
    /**
     * Obtiene datos de un endpoint GET
     * @param {string} url - URL del endpoint
     * @param {Object} options - Opciones adicionales
     */
    async obtenerDatos(url, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeoutDefault);
        
        try {
            const response = await fetch(url, {
                method: 'GET',
                signal: controller.signal,
                ...options
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            const data = await response.json();
            
            if (PresupuestosConfig.debug) {
                console.log('Respuesta recibida de:', url, data);
            }
            
            return data;
            
        } catch (error) {
            clearTimeout(timeoutId);
            
            if (error.name === 'AbortError') {
                throw new Error('La solicitud tardó demasiado tiempo');
            }
            
            throw error;
        }
    },
    
    /**
     * Envía datos a un endpoint POST
     * @param {string} url - URL del endpoint
     * @param {FormData|Object} data - Datos a enviar
     * @param {Object} options - Opciones adicionales
     */
    async enviarDatos(url, data, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeoutDefault);
        
        try {
            const fetchOptions = {
                method: 'POST',
                signal: controller.signal,
                ...options
            };
            
            // Configurar body según el tipo de datos
            if (data instanceof FormData) {
                fetchOptions.body = data;
            } else if (typeof data === 'object') {
                fetchOptions.headers = {
                    'Content-Type': 'application/json',
                    ...options.headers
                };
                fetchOptions.body = JSON.stringify(data);
            } else {
                fetchOptions.body = data;
            }
            
            if (PresupuestosConfig.debug) {
                console.log('Enviando datos a:', url, data);
            }
            
            const response = await fetch(url, fetchOptions);
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            const responseData = await response.json();
            
            if (PresupuestosConfig.debug) {
                console.log('Respuesta recibida de:', url, responseData);
            }
            
            return responseData;
            
        } catch (error) {
            clearTimeout(timeoutId);
            
            if (error.name === 'AbortError') {
                throw new Error('La solicitud tardó demasiado tiempo');
            }
            
            throw error;
        }
    },
    
    /**
     * Maneja errores de red de forma centralizada
     * @private
     */
    _manejarErrorRed(error, contexto = '') {
        let mensajeError = 'Error de comunicación con el servidor';
        
        if (error.message.includes('Failed to fetch')) {
            mensajeError = 'No se pudo conectar al servidor. Verifique su conexión a internet.';
        } else if (error.message.includes('timeout') || error.message.includes('demasiado tiempo')) {
            mensajeError = 'La solicitud tardó demasiado tiempo. Intente nuevamente.';
        } else if (error.message.includes('HTTP 404')) {
            mensajeError = 'El archivo del servidor no fue encontrado.';
        } else if (error.message.includes('HTTP 500')) {
            mensajeError = 'Error interno del servidor.';
        } else if (error.message) {
            mensajeError += ': ' + error.message;
        }
        
        if (contexto) {
            mensajeError = `${contexto}: ${mensajeError}`;
        }
        
        console.error('Error de red:', error);
        return mensajeError;
    },
    
    /**
     * Método genérico para hacer peticiones con retry
     * @param {Function} peticion - Función que ejecuta la petición
     * @param {number} intentos - Número de intentos
     * @param {string} contexto - Contexto para logging
     */
    async ejecutarConReintentos(peticion, intentos = 3, contexto = '') {
        let ultimoError;
        
        for (let i = 0; i < intentos; i++) {
            try {
                return await peticion();
            } catch (error) {
                ultimoError = error;
                
                if (i < intentos - 1) {
                    const tiempoEspera = Math.pow(2, i) * 1000; // Backoff exponencial
                    console.warn(`Intento ${i + 1} falló, reintentando en ${tiempoEspera}ms...`);
                    await new Promise(resolve => setTimeout(resolve, tiempoEspera));
                }
            }
        }
        
        // Si llegamos aquí, todos los intentos fallaron
        const mensajeError = this._manejarErrorRed(ultimoError, contexto);
        throw new Error(mensajeError);
    }
};