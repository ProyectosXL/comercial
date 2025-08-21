
/**
 * presupuestos-ui.js
 * Maneja los estados de la interfaz de usuario y elementos visuales
 * Afecta: #statusIndicator, botones, estados de campos, alertas
 */

const PresupuestosUI = {
    
    /**
     * Inicializa el módulo de UI
     */
    init() {
        console.log('Inicializando módulo UI...');
        this.configurarTooltips();
        this.configurarAnimaciones();
        this.ajustarClasesTabla();
        
        // Verificar que todas las funciones estén disponibles
        console.log('Funciones disponibles en PresupuestosUI:', Object.getOwnPropertyNames(this));
        console.log('Módulo UI inicializado');
    },
    
    /**
     * Configura tooltips de Bootstrap
     * @private
     */
    configurarTooltips() {
        // Inicializar tooltips si Bootstrap está disponible
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipElements.forEach(element => {
                new bootstrap.Tooltip(element);
            });
        }
    },
    
    /**
     * Configura animaciones y transiciones
     * @private
     */
    configurarAnimaciones() {
        // Agregar clase para transiciones suaves
        document.body.classList.add('transitions-enabled');
    },
    
    /**
     * Actualiza el estado del período y la interfaz
     */
    actualizarEstadoPeriodo() {
        const periodo = document.getElementById('periodoPresupuesto').value;
        if (!periodo) {
            this._limpiarEstadoPeriodo();
            return;
        }
        
        const estadoPeriodo = this._determinarEstadoPeriodo(periodo);
        
        // Actualizar indicador de estado
        this._actualizarIndicadorEstado(estadoPeriodo);
        
        // Actualizar estado de campos y botones
        this._actualizarEstadoCampos(estadoPeriodo);
        
        // Mostrar mensaje si es necesario
        this._mostrarMensajeEstado(estadoPeriodo);
    },
    
    /**
     * Determina el estado del período seleccionado
     * @private
     */
    _determinarEstadoPeriodo(periodo) {
        const fechaActual = new Date();
        const mesActual = `${fechaActual.getFullYear()}-${(fechaActual.getMonth() + 1).toString().padStart(2, '0')}`;
        
        if (periodo < mesActual) {
            return {
                tipo: 'pasado',
                texto: 'Período pasado',
                clase: 'bg-secondary',
                editable: false
            };
        } else if (periodo === mesActual) {
            return {
                tipo: 'actual',
                texto: 'Período actual',
                clase: 'bg-primary',
                editable: true
            };
        } else {
            return {
                tipo: 'futuro',
                texto: 'Período futuro',
                clase: 'bg-success',
                editable: true
            };
        }
    },
    
    /**
     * Actualiza el indicador de estado
     * @private
     */
    _actualizarIndicadorEstado(estado) {
        const indicador = document.getElementById('statusIndicator');
        if (indicador) {
            indicador.textContent = estado.texto;
            indicador.className = `badge ${estado.clase}`;
        }
    },
    
    /**
     * Actualiza el estado de campos y botones
     * @private
     */
    _actualizarEstadoCampos(estado) {
        const inputs = document.querySelectorAll('.presupuesto-input');
        const botones = document.querySelectorAll('#guardarPresupuestosBtn, #limpiarFormBtn, #copiarPeriodoBtn');
        
        inputs.forEach(input => {
            input.disabled = !estado.editable;
            
            if (!estado.editable) {
                input.classList.add('bg-light');
            } else {
                input.classList.remove('bg-light');
            }
        });
        
        botones.forEach(boton => {
            boton.disabled = !estado.editable;
            
            if (!estado.editable) {
                boton.classList.add('opacity-50');
                boton.style.cursor = 'not-allowed';
            } else {
                boton.classList.remove('opacity-50');
                boton.style.cursor = '';
            }
        });
    },
        
    /**
     * Muestra mensaje según el estado del período
     * @private
     */
    _mostrarMensajeEstado(estado) {
        if (estado.tipo === 'pasado') {
            PresupuestosUtils.mostrarInformacion(
                'Período pasado',
                'Los presupuestos no se pueden editar, pero puede consultar las observaciones existentes',
                3000
            );
        }
    },
    
    /**
     * Limpia el estado del período
     * @private
     */
    _limpiarEstadoPeriodo() {
        const indicador = document.getElementById('statusIndicator');
        if (indicador) {
            indicador.textContent = '';
            indicador.className = 'badge';
        }
    },
    
    /**
     * Anima un elemento con bounce
     */
    animarBounce(selector) {
        const elemento = document.querySelector(selector);
        if (!elemento) return;
        
        elemento.style.animation = 'bounce 0.6s ease-in-out';
        
        setTimeout(() => {
            elemento.style.animation = '';
        }, 600);
    },
    
    /**
     * Muestra/oculta elementos con animación
     */
    toggleElemento(selector, mostrar = null) {
        const elemento = document.querySelector(selector);
        if (!elemento) return;
        
        const estaVisible = !elemento.classList.contains('d-none');
        const deberiaVerse = mostrar !== null ? mostrar : !estaVisible;
        
        if (deberiaVerse && !estaVisible) {
            elemento.classList.remove('d-none');
            elemento.style.opacity = '0';
            elemento.style.transform = 'translateY(-10px)';
            
            setTimeout(() => {
                elemento.style.transition = 'all 0.3s ease';
                elemento.style.opacity = '1';
                elemento.style.transform = 'translateY(0)';
            }, 10);
            
        } else if (!deberiaVerse && estaVisible) {
            elemento.style.transition = 'all 0.3s ease';
            elemento.style.opacity = '0';
            elemento.style.transform = 'translateY(-10px)';
            
            setTimeout(() => {
                elemento.classList.add('d-none');
                elemento.style.transition = '';
            }, 300);
        }
    },
    
    /**
     * Configura el estado de carga de un botón
     */
    configurarBotonCarga(selector, cargando = false, textoOriginal = null) {
        const boton = document.querySelector(selector);
        if (!boton) return;
        
        if (cargando) {
            // Guardar texto original si no se proporcionó
            if (!textoOriginal) {
                boton.dataset.textoOriginal = boton.innerHTML;
            }
            
            boton.disabled = true;
            boton.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Cargando...
            `;
            
        } else {
            boton.disabled = false;
            const texto = textoOriginal || boton.dataset.textoOriginal || boton.innerHTML;
            boton.innerHTML = texto;
            delete boton.dataset.textoOriginal;
        }
    },
    
    /**
     * Actualiza el progreso de una barra de progreso
     */
    actualizarProgreso(selector, porcentaje, texto = '') {
        const contenedor = document.querySelector(selector);
        if (!contenedor) return;
        
        let barra = contenedor.querySelector('.progress-bar');
        if (!barra) {
            // Crear barra de progreso si no existe
            contenedor.innerHTML = `
                <div class="progress">
                    <div class="progress-bar" role="progressbar"></div>
                </div>
                <small class="text-muted mt-1 d-block"></small>
            `;
            barra = contenedor.querySelector('.progress-bar');
        }
        
        // Actualizar barra
        barra.style.width = `${Math.max(0, Math.min(100, porcentaje))}%`;
        barra.setAttribute('aria-valuenow', porcentaje);
        
        // Actualizar texto
        const textoElemento = contenedor.querySelector('small');
        if (textoElemento && texto) {
            textoElemento.textContent = texto;
        }
        
        // Color según porcentaje
        barra.className = 'progress-bar';
        if (porcentaje >= 90) {
            barra.classList.add('bg-success');
        } else if (porcentaje >= 70) {
            barra.classList.add('bg-warning');
        } else if (porcentaje >= 50) {
            barra.classList.add('bg-info');
        } else {
            barra.classList.add('bg-danger');
        }
    },
    
    /**
     * Aplica tema claro/oscuro
     */
    aplicarTema(tema = 'claro') {
        document.documentElement.setAttribute('data-bs-theme', tema === 'oscuro' ? 'dark' : 'light');
        
        // Guardar preferencia
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem('presupuestos-tema', tema);
        }
    },
    
    /**
     * Ajusta las clases de la tabla según el número de conceptos
     */
    ajustarClasesTabla() {
        const tabla = document.querySelector('.table');
        if (!tabla) return;
        
        // Contar columnas de conceptos (total - supervisora - total - observaciones)
        const totalColumnas = tabla.querySelectorAll('thead th').length;
        const numConceptos = totalColumnas - 3;
        
        // Remover clases existentes
        tabla.classList.remove('conceptos-pocos', 'conceptos-medios', 'conceptos-muchos');
        
        // Aplicar clase según número de conceptos
        if (numConceptos <= 4) {
            tabla.classList.add('conceptos-pocos');
        } else if (numConceptos <= 6) {
            tabla.classList.add('conceptos-medios');
        } else {
            tabla.classList.add('conceptos-muchos');
        }
        
        if (PresupuestosConfig.debug) {
            console.log(`Tabla configurada para ${numConceptos} conceptos`);
        }
    },
    
    /**
     * Optimiza el formato de números para espacios reducidos
     */
    optimizarFormatoNumeros() {
        const inputs = document.querySelectorAll('.presupuesto-input');
        const totales = document.querySelectorAll('.fila-total, .columna-total, #totalGeneral');
        
        // Agregar clase para formato compacto si hay muchos conceptos
        const tabla = document.querySelector('.table');
        if (tabla && tabla.classList.contains('conceptos-muchos')) {
            inputs.forEach(input => {
                input.classList.add('numero-formato-compacto');
            });
            
            totales.forEach(total => {
                total.classList.add('numero-formato-compacto');
            });
        } else {
            inputs.forEach(input => {
                input.classList.remove('numero-formato-compacto');
            });
            
            totales.forEach(total => {
                total.classList.remove('numero-formato-compacto');
            });
        }
    },
};