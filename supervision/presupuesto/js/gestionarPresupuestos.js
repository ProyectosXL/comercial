
/**
 * Script para manejar la pantalla de gestión de presupuestos
 */
document.addEventListener('DOMContentLoaded', function() {
    // Establecer el mes actual como valor predeterminado para el período
    const date = new Date();
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    document.getElementById('periodoPresupuesto').value = `${year}-${month}`;
    
    // Actualizar el estado al cargar la página
    actualizarEstadoPeriodo();
    
    // Formatear campos numéricos y calcular totales
    inicializarCamposNumericos();
    
    // Asignar eventos a botones
    asignarEventos();
    
    // Cargar datos iniciales
    cargarPresupuestos();
    
    // Verificar data attributes
    verificarDataAttributes();
});

/**
 * Verifica que los data attributes estén correctamente configurados
 */
function verificarDataAttributes() {
    const inputs = document.querySelectorAll('.presupuesto-input');
    console.log(`Encontrados ${inputs.length} inputs de presupuesto`);
    
    inputs.forEach((input, index) => {
        const supervisorId = input.dataset.supervisor;
        const conceptoId = input.dataset.concepto;
        
        if (!supervisorId || !conceptoId) {
            console.error(`Input #${index} tiene atributos data incompletos:`, input);
        }
    });
}

/**
 * Formatea un número con separador de miles y sin decimales
 * 
 * @param {number} numero - El número a formatear
 * @return {string} Número formateado con separador de miles y sin decimales
 */
function formatearNumero(numero) {
    // Redondear al entero más cercano
    const entero = Math.round(numero);
    // Formatear con separador de miles
    return entero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

/**
 * Convierte un número formateado a valor numérico
 * 
 * @param {string} textoFormateado - El texto formateado a convertir
 * @return {number} Valor numérico
 */
function parsearNumero(textoFormateado) {
    if (!textoFormateado) return 0;
    // Quitar los separadores de miles
    return parseFloat(textoFormateado.replace(/\./g, '')) || 0;
}

/**
 * Inicializa los campos numéricos y su comportamiento
 */
function inicializarCamposNumericos() {
    const presupuestoInputs = document.querySelectorAll('.presupuesto-input');
    presupuestoInputs.forEach(input => {
        // Evento al escribir
        input.addEventListener('input', function(e) {
            // Permitir solo números
            this.value = this.value.replace(/[^\d]/g, '');
            
            // Calcular totales
            calcularTotales();
        });
        
        // Formatear al perder foco
        input.addEventListener('blur', function() {
            if (this.value) {
                const valor = parseInt(this.value, 10) || 0;
                this.value = formatearNumero(valor);
            }
        });
        
        // Al obtener foco, quitar formato para edición
        input.addEventListener('focus', function() {
            this.value = this.value.replace(/\./g, '');
        });
    });
}

/**
 * Calcula los totales de filas y columnas
 */
function calcularTotales() {
    // Calcular totales por fila (supervisora)
    const filas = document.querySelectorAll('tbody tr:not(:last-child)');
    let totalGeneral = 0;

    filas.forEach(fila => {
        const inputs = fila.querySelectorAll('.presupuesto-input');
        let totalFila = 0;
        
        inputs.forEach(input => {
            const valor = parsearNumero(input.value);
            totalFila += valor;
        });
        
        fila.querySelector('.fila-total').textContent = formatearNumero(totalFila);
        totalGeneral += totalFila;
    });

    // Calcular totales por columna (concepto)
    const conceptoIds = Array.from(document.querySelectorAll('.presupuesto-input')).map(
        input => input.dataset.concepto
    ).filter((value, index, self) => self.indexOf(value) === index);

    conceptoIds.forEach(conceptoId => {
        const inputsConcepto = document.querySelectorAll(`.presupuesto-input[data-concepto="${conceptoId}"]`);
        let totalColumna = 0;
        
        inputsConcepto.forEach(input => {
            totalColumna += parsearNumero(input.value);
        });
        
        document.querySelector(`.columna-total[data-concepto="${conceptoId}"]`).textContent = formatearNumero(totalColumna);
    });

    // Actualizar total general
    document.getElementById('totalGeneral').textContent = formatearNumero(totalGeneral);
}

/**
 * Carga los presupuestos para el período seleccionado
 */
function cargarPresupuestos() {
    const periodo = document.getElementById('periodoPresupuesto').value;
    if (!periodo) return;

    Swal.fire({
        title: 'Cargando...',
        text: 'Obteniendo presupuestos',
        didOpen: () => {
            Swal.showLoading();
        },
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    console.log('Solicitando presupuestos para período:', periodo);

    // Llamada AJAX para obtener presupuestos
    fetch(`Controller/obtenerPresupuestos.php?periodo=${periodo}`)
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Error HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta recibida:', data);
            Swal.close();
            
            // Limpiar los campos
            document.querySelectorAll('.presupuesto-input').forEach(input => {
                input.value = '';
            });
            
            // Si hay datos, llenar los campos
            if (data.success && data.data) {
                let presupuestosCargados = false;
                
                // Depuración para verificar estructura de datos
                console.log('Estructura de datos recibida:');
                Object.keys(data.data).forEach(supervisoraId => {
                    console.log(`Supervisora ID: ${supervisoraId}, Conceptos: ${Object.keys(data.data[supervisoraId]).join(',')}`);
                });
                
                Object.keys(data.data).forEach(supervisoraId => {
                    Object.keys(data.data[supervisoraId]).forEach(conceptoId => {
                        const input = document.querySelector(`.presupuesto-input[data-supervisor="${supervisoraId}"][data-concepto="${conceptoId}"]`);
                        if (input) {
                            // Formatear el valor con separador de miles y sin decimales
                            const valor = Math.round(parseFloat(data.data[supervisoraId][conceptoId]));
                            input.value = formatearNumero(valor);
                            presupuestosCargados = true;
                        } else {
                            console.warn(`No se encontró input para supervisora=${supervisoraId}, concepto=${conceptoId}`);
                        }
                    });
                });
                
                if (!presupuestosCargados) {
                    console.warn('No se cargaron presupuestos aunque la respuesta fue exitosa');
                }
            } else if (data.success === false) {
                console.warn('Error en la respuesta:', data.message);
            } else {
                console.warn('Estructura de respuesta inesperada:', data);
            }
            
            calcularTotales();
            
            // Verificar si es un período pasado para deshabilitar la edición
            actualizarEstadoPeriodo();
        })
        .catch(error => {
            console.error('Error completo:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los presupuestos: ' + error.message
            });
        });
}

/**
 * Actualiza el indicador de estado del período
 */
function actualizarEstadoPeriodo() {
    const periodo = document.getElementById('periodoPresupuesto').value;
    if (!periodo) {
        document.getElementById('statusIndicator').textContent = '';
        return;
    }

    const date = new Date();
    const currentMonth = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
    
    // Determinar si este es un período pasado, actual o futuro
    const esPeriodoPasado = periodo < currentMonth;
    const esPeriodoActual = periodo === currentMonth;
    const esPeriodoFuturo = periodo > currentMonth;
    
    // Actualizar el badge de estado
    if (esPeriodoActual) {
        document.getElementById('statusIndicator').textContent = 'Período actual';
        document.getElementById('statusIndicator').className = 'badge bg-primary';
    } else if (esPeriodoFuturo) {
        document.getElementById('statusIndicator').textContent = 'Período futuro';
        document.getElementById('statusIndicator').className = 'badge bg-success';
    } else {
        document.getElementById('statusIndicator').textContent = 'Período pasado';
        document.getElementById('statusIndicator').className = 'badge bg-secondary';
    }
    
    // Habilitar o deshabilitar campos según el período
    const inputs = document.querySelectorAll('.presupuesto-input');
    const botones = document.querySelectorAll('#guardarPresupuestosBtn, #limpiarFormBtn, #copiarPeriodoBtn');
    
    if (esPeriodoPasado) {
        // Deshabilitar campos y botones para períodos pasados
        inputs.forEach(input => {
            input.disabled = true;
            input.classList.add('bg-light');
        });
        
        botones.forEach(boton => {
            boton.disabled = true;
            boton.classList.add('opacity-50');
        });
        
        // Mostrar mensaje informativo
        Swal.fire({
            title: 'Período pasado',
            text: 'No se pueden editar presupuestos de períodos anteriores',
            icon: 'info',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        });
    } else {
        // Habilitar campos y botones para período actual y futuro
        inputs.forEach(input => {
            input.disabled = false;
            input.classList.remove('bg-light');
        });
        
        botones.forEach(boton => {
            boton.disabled = false;
            boton.classList.remove('opacity-50');
        });
    }
}

/**
 * Asigna eventos a los elementos de la página
 */
function asignarEventos() {
    // Evento al cambiar el período
    document.getElementById('periodoPresupuesto').addEventListener('change', function() {
        cargarPresupuestos();
        actualizarEstadoPeriodo();
    });

    // Evento de envío del formulario
    document.getElementById('presupuestosForm').addEventListener('submit', function(e) {
        e.preventDefault();
        guardarPresupuestos();
    });

    // Botón para limpiar formulario
    document.getElementById('limpiarFormBtn').addEventListener('click', function() {
        limpiarFormulario();
    });

    // Botón para copiar período anterior
    document.getElementById('copiarPeriodoBtn').addEventListener('click', function() {
        mostrarModalCopiarPeriodo();
    });

    // Botón de confirmación en el modal de copia
    document.getElementById('confirmCopyBtn').addEventListener('click', function() {
        copiarPresupuestoAnterior();
    });
}

/**
 * Guarda los presupuestos ingresados
 */
function guardarPresupuestos() {
    const periodo = document.getElementById('periodoPresupuesto').value;
    const date = new Date();
    const currentMonth = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
    
    // Verificar si es un período pasado
    if (periodo < currentMonth) {
        Swal.fire({
            title: 'Período pasado',
            text: 'No se pueden editar presupuestos de períodos anteriores',
            icon: 'error'
        });
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
            // Preparar datos
            const presupuestos = {};
            
            document.querySelectorAll('.presupuesto-input').forEach(input => {
                const supervisorId = input.dataset.supervisor;
                const conceptoId = input.dataset.concepto;
                // Convertir el valor formateado a número
                const valor = parsearNumero(input.value);
                
                if (!presupuestos[supervisorId]) {
                    presupuestos[supervisorId] = {};
                }
                
                presupuestos[supervisorId][conceptoId] = valor;
            });
            
            // Debug: Imprimir datos que se van a enviar
            console.log('Enviando datos:');
            console.log('Periodo:', periodo);
            console.log('Presupuestos:', presupuestos);
            
            // Mostrar loader
            Swal.fire({
                title: 'Guardando...',
                didOpen: () => {
                    Swal.showLoading();
                },
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            
            // Enviar datos
            const formData = new FormData();
            formData.append('periodo', periodo);
            formData.append('presupuesto', JSON.stringify(presupuestos));
            
            // Debug: verificar datos del FormData
            console.log('Datos de FormData:');
            for (let pair of formData.entries()) {
                console.log(pair[0], pair[1]);
            }
            
            fetch('Controller/guardarPresupuestos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`Error HTTP ${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Guardado!',
                        text: data.message || 'Los presupuestos han sido guardados correctamente',
                        icon: 'success'
                    }).then(() => {
                        // Recargar presupuestos para confirmar los cambios
                        cargarPresupuestos();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudieron guardar los presupuestos',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al guardar los presupuestos: ' + error.message,
                    icon: 'error'
                });
            });
        }
    });
}

/**
 * Limpia el formulario de presupuestos
 */
function limpiarFormulario() {
    const periodo = document.getElementById('periodoPresupuesto').value;
    const date = new Date();
    const currentMonth = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
    
    // Verificar si es un período pasado
    if (periodo < currentMonth) {
        Swal.fire({
            title: 'Período pasado',
            text: 'No se pueden editar presupuestos de períodos anteriores',
            icon: 'error'
        });
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
            calcularTotales();
        }
    });
}

/**
 * Muestra el modal para copiar presupuestos del período anterior
 */
function mostrarModalCopiarPeriodo() {
    const periodo = document.getElementById('periodoPresupuesto').value;
    const date = new Date();
    const currentMonth = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
    
    // Verificar si es un período pasado
    if (periodo < currentMonth) {
        Swal.fire({
            title: 'Período pasado',
            text: 'No se pueden editar presupuestos de períodos anteriores',
            icon: 'error'
        });
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('confirmCopyModal'));
    modal.show();
}

/**
 * Copia los presupuestos del período anterior
 */
function copiarPresupuestoAnterior() {
    const periodo = document.getElementById('periodoPresupuesto').value;
    if (!periodo) {
        Swal.fire({
            title: 'Error',
            text: 'Debe seleccionar un período',
            icon: 'error'
        });
        return;
    }
    
    const date = new Date();
    const currentMonth = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
    
    // Verificar si es un período pasado
    if (periodo < currentMonth) {
        Swal.fire({
            title: 'Período pasado',
            text: 'No se pueden editar presupuestos de períodos anteriores',
            icon: 'error'
        });
        return;
    }

    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmCopyModal'));
    modal.hide();

    // Mostrar loader
    Swal.fire({
        title: 'Copiando presupuestos...',
        didOpen: () => {
            Swal.showLoading();
        },
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    // Enviar solicitud
    const formData = new FormData();
    formData.append('periodo', periodo);

    fetch('Controller/copiarPresupuestoAnterior.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`Error HTTP ${response.status}: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Recargar presupuestos
            cargarPresupuestos();
            
            Swal.fire({
                title: '¡Completado!',
                text: data.message || 'Se han copiado los presupuestos del período anterior',
                icon: 'success'
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'No se pudieron copiar los presupuestos',
                icon: 'error'
            });
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        Swal.fire({
            title: 'Error',
            text: 'Ocurrió un error al copiar los presupuestos: ' + error.message,
            icon: 'error'
        });
    });
}