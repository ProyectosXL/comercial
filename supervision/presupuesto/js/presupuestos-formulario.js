
/**
 * presupuestos-formulario.js
 * Maneja la interacción con el formulario principal de presupuestos
 * Afecta: #presupuestosForm, #periodoPresupuesto, .presupuesto-input
 */

const PresupuestosFormulario = {
    
    /**
     * Inicializa el módulo del formulario
     */
    init() {
        this.asignarEventos();
        this.inicializarCamposNumericos();
        this.verificarDataAttributes();
    },
    
    /**
     * Establece el período actual como valor predeterminado
     */
    establecerPeriodoActual() {
        const date = new Date();
        const year = date.getFullYear();
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const periodoInput = document.getElementById('periodoPresupuesto');
        
        if (periodoInput && !periodoInput.value) {
            periodoInput.value = `${year}-${month}`;
        }
    },
    
    /**
     * Asigna eventos a los elementos del formulario
     */
    asignarEventos() {
        // Evento al cambiar el período
        const periodoInput = document.getElementById('periodoPresupuesto');
        if (periodoInput) {
            periodoInput.addEventListener('change', () => {
                PresupuestosAjax.cargarPresupuestos();
                PresupuestosUI.actualizarEstadoPeriodo();
            });
        }
        
        // Evento de envío del formulario
        const formulario = document.getElementById('presupuestosForm');
        if (formulario) {
            formulario.addEventListener('submit', (e) => {
                e.preventDefault();
                this.guardarPresupuestos();
            });
        }
        
        // Botón para limpiar formulario
        const btnLimpiar = document.getElementById('limpiarFormBtn');
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', () => this.limpiarFormulario());
        }
        
        // Botón para copiar período anterior
        const btnCopiar = document.getElementById('copiarPeriodoBtn');
        if (btnCopiar) {
            btnCopiar.addEventListener('click', () => this.mostrarModalCopiarPeriodo());
        }
        
        // Botón de confirmación en el modal de copia
        const btnConfirmarCopia = document.getElementById('confirmCopyBtn');
        if (btnConfirmarCopia) {
            btnConfirmarCopia.addEventListener('click', () => this.copiarPresupuestoAnterior());
        }
    },
    
    /**
     * Inicializa el comportamiento de los campos numéricos
     */
    inicializarCamposNumericos() {
        const presupuestoInputs = document.querySelectorAll('.presupuesto-input');
        
        presupuestoInputs.forEach(input => {
            // Evento al escribir
            input.addEventListener('input', function(e) {
                // Permitir solo números
                this.value = this.value.replace(/[^\d]/g, '');
                
                // Calcular totales
                PresupuestosCalculos.calcularTotales();
            });
            
            // Formatear al perder foco
            input.addEventListener('blur', function() {
                if (this.value) {
                    const valor = parseInt(this.value, 10) || 0;
                    this.value = PresupuestosUtils.formatearNumero(valor);
                }
            });
            
            // Al obtener foco, quitar formato para edición
            input.addEventListener('focus', function() {
                this.value = this.value.replace(/\./g, '');
            });
        });
    },
    
    /**
     * Verifica que los data attributes estén correctamente configurados
     */
    verificarDataAttributes() {
        const inputs = document.querySelectorAll('.presupuesto-input');
        let erroresEncontrados = 0;
        
        console.log(`Verificando ${inputs.length} inputs de presupuesto...`);
        
        inputs.forEach((input, index) => {
            const supervisorId = input.dataset.supervisor;
            const conceptoId = input.dataset.concepto;
            
            if (!supervisorId || !conceptoId) {
                console.error(`Input #${index} tiene atributos data incompletos:`, input);
                erroresEncontrados++;
            }
        });
        
        if (erroresEncontrados > 0) {
            console.warn(`Se encontraron ${erroresEncontrados} inputs con problemas en los data attributes`);
        } else {
            console.log('Todos los data attributes están correctamente configurados');
        }
    },
    
    /**
     * Guarda los presupuestos ingresados
     */
    guardarPresupuestos() {
        const periodo = document.getElementById('periodoPresupuesto').value;
        
        if (!PresupuestosUtils.validarPeriodoEditable(periodo)) {
            return;
        }
        
        Swal.fire({
            title: '¿Guardar presupuestos?',
            text: 'Se actualizarán los presupuestos para el período seleccionado',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this._ejecutarGuardado(periodo);
            }
        });
    },
    
    /**
     * Ejecuta el guardado de presupuestos
     * @private
     */
    _ejecutarGuardado(periodo) {
        // Preparar datos
        const presupuestos = this._prepararDatosPresupuesto();
        
        // Debug: Imprimir datos que se van a enviar
        if (PresupuestosConfig.debug) {
            console.log('Enviando datos:', { periodo, presupuestos });
        }
        
        // Mostrar loader
        PresupuestosUtils.mostrarCargando('Guardando presupuestos...');
        
        // Enviar datos
        const formData = new FormData();
        formData.append('periodo', periodo);
        formData.append('presupuesto', JSON.stringify(presupuestos));
        
        PresupuestosAjax.enviarDatos(PresupuestosConfig.endpoints.guardarPresupuestos, formData)
            .then(data => {
                if (data.success) {
                    PresupuestosUtils.mostrarExito(data.message || 'Presupuestos guardados correctamente');
                    // Recargar presupuestos para confirmar los cambios
                    PresupuestosAjax.cargarPresupuestos();
                } else {
                    PresupuestosUtils.mostrarError(data.message || 'No se pudieron guardar los presupuestos');
                }
            })
            .catch(error => {
                PresupuestosUtils.mostrarError('Error al guardar: ' + error.message);
            });
    },
    
    /**
     * Prepara los datos del presupuesto para envío
     * @private
     */
    _prepararDatosPresupuesto() {
        const presupuestos = {};
        
        document.querySelectorAll('.presupuesto-input').forEach(input => {
            const supervisorId = input.dataset.supervisor;
            const conceptoId = input.dataset.concepto;
            const valor = PresupuestosUtils.parsearNumero(input.value);
            
            if (!presupuestos[supervisorId]) {
                presupuestos[supervisorId] = {};
            }
            
            presupuestos[supervisorId][conceptoId] = valor;
        });
        
        return presupuestos;
    },
    
    /**
     * Limpia el formulario de presupuestos
     */
    limpiarFormulario() {
        const periodo = document.getElementById('periodoPresupuesto').value;
        
        if (!PresupuestosUtils.validarPeriodoEditable(periodo)) {
            return;
        }
        
        Swal.fire({
            title: '¿Limpiar formulario?',
            text: 'Se borrarán todos los valores ingresados',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Limpiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.querySelectorAll('.presupuesto-input').forEach(input => {
                    input.value = '';
                });
                PresupuestosCalculos.calcularTotales();
            }
        });
    },
    
    /**
     * Muestra el modal para copiar presupuestos del período anterior
     */
    mostrarModalCopiarPeriodo() {
        const periodo = document.getElementById('periodoPresupuesto').value;
        
        if (!PresupuestosUtils.validarPeriodoEditable(periodo)) {
            return;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('confirmCopyModal'));
        modal.show();
    },
    
    /**
     * Copia los presupuestos del período anterior
     */
    copiarPresupuestoAnterior() {
        const periodo = document.getElementById('periodoPresupuesto').value;
        
        if (!PresupuestosUtils.validarPeriodoEditable(periodo)) {
            return;
        }
        
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmCopyModal'));
        if (modal) {
            modal.hide();
        }
        
        // Mostrar loader
        PresupuestosUtils.mostrarCargando('Copiando presupuestos...');
        
        // Enviar solicitud
        const formData = new FormData();
        formData.append('periodo', periodo);
        
        PresupuestosAjax.enviarDatos(PresupuestosConfig.endpoints.copiarPeriodo, formData)
            .then(data => {
                if (data.success) {
                    // Recargar presupuestos
                    PresupuestosAjax.cargarPresupuestos();
                    PresupuestosUtils.mostrarExito(data.message || 'Presupuestos copiados correctamente');
                } else {
                    PresupuestosUtils.mostrarError(data.message || 'No se pudieron copiar los presupuestos');
                }
            })
            .catch(error => {
                PresupuestosUtils.mostrarError('Error al copiar: ' + error.message);
            });
    },
    
    /**
     * Llena el formulario con datos de presupuestos
     */
    llenarFormulario(datos) {
        if (!datos || typeof datos !== 'object') {
            console.warn('Datos de presupuesto inválidos recibidos');
            return;
        }
        
        // Limpiar campos primero
        document.querySelectorAll('.presupuesto-input').forEach(input => {
            input.value = '';
        });
        
        let presupuestosCargados = 0;
        
        // Llenar campos con datos
        Object.keys(datos).forEach(supervisoraId => {
            Object.keys(datos[supervisoraId]).forEach(conceptoId => {
                const input = document.querySelector(
                    `.presupuesto-input[data-supervisor="${supervisoraId}"][data-concepto="${conceptoId}"]`
                );
                
                if (input) {
                    const valor = Math.round(parseFloat(datos[supervisoraId][conceptoId]));
                    input.value = PresupuestosUtils.formatearNumero(valor);
                    presupuestosCargados++;
                } else {
                    console.warn(`No se encontró input para supervisora=${supervisoraId}, concepto=${conceptoId}`);
                }
            });
        });
        
        if (PresupuestosConfig.debug) {
            console.log(`Presupuestos cargados: ${presupuestosCargados}`);
        }
        
        // Recalcular totales
        PresupuestosCalculos.calcularTotales();
        
        // Optimizar formato según número de conceptos (con verificación)
        if (typeof PresupuestosUI.optimizarFormatoNumeros === 'function') {
            PresupuestosUI.optimizarFormatoNumeros();
        }
    }
};