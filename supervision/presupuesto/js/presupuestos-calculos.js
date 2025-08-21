
/**
 * presupuestos-calculos.js
 * Maneja todos los cálculos de totales y validaciones numéricas
 * Afecta: .fila-total, .columna-total, #totalGeneral
 */

const PresupuestosCalculos = {
    
    /**
     * Inicializa el módulo de cálculos
     */
    init() {
        console.log('Módulo de cálculos inicializado');
        // Los eventos se asignan desde el módulo de formulario
        // Este módulo se enfoca en los cálculos puros
    },
    
    /**
     * Calcula todos los totales de la tabla
     */
    calcularTotales() {
        this.calcularTotalesPorFila();
        this.calcularTotalesPorColumna();
        this.calcularTotalGeneral();
    },
    
    /**
     * Calcula los totales por fila (supervisora)
     */
    calcularTotalesPorFila() {
        const filas = document.querySelectorAll('tbody tr:not(:last-child)');
        
        filas.forEach(fila => {
            const inputs = fila.querySelectorAll('.presupuesto-input');
            let totalFila = 0;
            
            inputs.forEach(input => {
                const valor = PresupuestosUtils.parsearNumero(input.value);
                totalFila += valor;
            });
            
            const elementoTotal = fila.querySelector('.fila-total');
            if (elementoTotal) {
                elementoTotal.textContent = PresupuestosUtils.formatearNumero(totalFila);
                
                // Animar cambio
                this.animarCambioTotal(elementoTotal);
            }
        });
    },
    
    /**
     * Calcula los totales por columna (concepto)
     */
    calcularTotalesPorColumna() {
        const conceptoIds = this._obtenerConceptosUnicos();
        
        conceptoIds.forEach(conceptoId => {
            const inputsConcepto = document.querySelectorAll(
                `.presupuesto-input[data-concepto="${conceptoId}"]`
            );
            
            let totalColumna = 0;
            
            inputsConcepto.forEach(input => {
                totalColumna += PresupuestosUtils.parsearNumero(input.value);
            });
            
            const elementoTotal = document.querySelector(
                `.columna-total[data-concepto="${conceptoId}"]`
            );
            
            if (elementoTotal) {
                elementoTotal.textContent = PresupuestosUtils.formatearNumero(totalColumna);

            }
        });
    },
    
    /**
     * Calcula el total general
     */
    calcularTotalGeneral() {
        const inputs = document.querySelectorAll('.presupuesto-input');
        let totalGeneral = 0;
        
        inputs.forEach(input => {
            totalGeneral += PresupuestosUtils.parsearNumero(input.value);
        });
        
        const elementoTotalGeneral = document.getElementById('totalGeneral');
        if (elementoTotalGeneral) {
            elementoTotalGeneral.textContent = PresupuestosUtils.formatearNumero(totalGeneral);
            
        }
        
        if (PresupuestosConfig.debug) {
            console.log('Total general calculado:', totalGeneral);
        }
    },
    
    /**
     * Obtiene todos los IDs de conceptos únicos
     * @private
     */
    _obtenerConceptosUnicos() {
        const inputs = Array.from(document.querySelectorAll('.presupuesto-input'));
        const conceptos = inputs
            .map(input => input.dataset.concepto)
            .filter((concepto, index, array) => array.indexOf(concepto) === index)
            .filter(concepto => concepto); // Filtrar valores vacíos
        
        return conceptos;
    },
    
    /**
     * Aplica estilos según el monto
     * @private
     */
    _aplicarEstiloMonto(elemento, monto) {
        // Remover clases existentes
        elemento.classList.remove('text-success', 'text-warning', 'text-danger', 'fw-bold');
        
        if (monto === 0) {
            // Sin estilo especial para cero
            return;
        } else if (monto > 0 && monto <= 50000) {
            elemento.classList.add('text-success');
        } else if (monto > 50000 && monto <= 200000) {
            elemento.classList.add('text-warning', 'fw-bold');
        } else if (monto > 200000) {
            elemento.classList.add('text-danger', 'fw-bold');
        }
    },
    
    /**
     * Aplica estilo especial para el total general
     * @private
     */
    _aplicarEstiloTotalGeneral(elemento, total) {
        // Remover clases existentes
        elemento.classList.remove('text-primary', 'text-success', 'text-warning', 'text-danger');
        
        if (total === 0) {
            elemento.classList.add('text-muted');
        } else if (total > 0 && total <= 500000) {
            elemento.classList.add('text-success');
        } else if (total > 500000 && total <= 2000000) {
            elemento.classList.add('text-primary');
        } else if (total > 2000000 && total <= 5000000) {
            elemento.classList.add('text-warning');
        } else if (total > 5000000) {
            elemento.classList.add('text-danger');
        }
        
        // Siempre mantener negrita para el total general
        elemento.classList.add('fw-bold');
    },
    
    /**
     * Valida que todos los valores sean numéricos válidos
     */
    validarDatosNumericos() {
        const inputs = document.querySelectorAll('.presupuesto-input');
        const errores = [];
        
        inputs.forEach((input, index) => {
            const valor = input.value.trim();
            
            if (valor && isNaN(PresupuestosUtils.parsearNumero(valor))) {
                const supervisorId = input.dataset.supervisor;
                const conceptoId = input.dataset.concepto;
                
                errores.push({
                    input: input,
                    index: index,
                    supervisorId: supervisorId,
                    conceptoId: conceptoId,
                    valor: valor
                });
            }
        });
        
        if (errores.length > 0) {
            console.warn('Se encontraron errores en los datos numéricos:', errores);
            return false;
        }
        
        return true;
    },
    
    /**
     * Obtiene estadísticas de los presupuestos
     */
    obtenerEstadisticas() {
        const inputs = document.querySelectorAll('.presupuesto-input');
        const valores = [];
        
        inputs.forEach(input => {
            const valor = PresupuestosUtils.parsearNumero(input.value);
            if (valor > 0) {
                valores.push(valor);
            }
        });
        
        if (valores.length === 0) {
            return {
                total: 0,
                promedio: 0,
                maximo: 0,
                minimo: 0,
                cantidad: 0
            };
        }
        
        const total = valores.reduce((suma, valor) => suma + valor, 0);
        const promedio = total / valores.length;
        const maximo = Math.max(...valores);
        const minimo = Math.min(...valores);
        
        return {
            total: total,
            promedio: Math.round(promedio),
            maximo: maximo,
            minimo: minimo,
            cantidad: valores.length
        };
    },
    
    /**
     * Resalta campos con valores altos
     */
    resaltarValoresAltos(umbral = 100000) {
        const inputs = document.querySelectorAll('.presupuesto-input');
        
        inputs.forEach(input => {
            const valor = PresupuestosUtils.parsearNumero(input.value);
            
            // Remover clases existentes
            input.classList.remove('border-warning', 'border-danger', 'bg-warning-subtle', 'bg-danger-subtle');
            
            if (valor > umbral * 2) {
                input.classList.add('border-danger', 'bg-danger-subtle');
            } else if (valor > umbral) {
                input.classList.add('border-warning', 'bg-warning-subtle');
            }
        });
    },
    
    /**
     * Aplica estilos según los valores ingresados
     */
    aplicarEstilosValores() {
        const inputs = document.querySelectorAll('.presupuesto-input');
        
        inputs.forEach(input => {
            const valor = PresupuestosUtils.parsearNumero(input.value);
            
            // Remover clases anteriores
            input.classList.remove('valor-alto', 'valor-muy-alto');
            
            // Aplicar clases según el valor
            if (valor > 200000) {
                input.classList.add('valor-muy-alto');
            } else if (valor > 100000) {
                input.classList.add('valor-alto');
            }
        });
    },
    
    /**
     * Aplica estilos según los valores ingresados
     */
    aplicarEstilosValores() {
        const inputs = document.querySelectorAll('.presupuesto-input');
        
        inputs.forEach(input => {
            const valor = PresupuestosUtils.parsearNumero(input.value);
            
            // Remover clases anteriores
            input.classList.remove('valor-alto', 'valor-muy-alto');
            
            // Aplicar clases según el valor
            if (valor > 200000) {
                input.classList.add('valor-muy-alto');
            } else if (valor > 100000) {
                input.classList.add('valor-alto');
            }
        });
    },
    
    /**
     * Anima los totales cuando cambian
     */
    animarCambioTotal(elemento) {
        if (elemento) {
            elemento.classList.remove('total-actualizado');
            // Forzar reflow
            elemento.offsetHeight;
            elemento.classList.add('total-actualizado');
        }
    },
    
    /**
     * Limpia todos los resaltados
     */
    limpiarResaltados() {
        const inputs = document.querySelectorAll('.presupuesto-input');
        
        inputs.forEach(input => {
            input.classList.remove(
                'border-warning', 'border-danger', 
                'bg-warning-subtle', 'bg-danger-subtle'
            );
        });
    }
};