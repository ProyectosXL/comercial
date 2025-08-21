
/**
 * presupuestos-main.js
 * Archivo principal que inicializa todos los módulos y coordina la aplicación
 * Afecta: gestionarPresupuestos.php (todo el documento)
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando aplicación de presupuestos...');
    
    try {
        // Inicializar todos los módulos
        PresupuestosFormulario.init();
        PresupuestosCalculos.init();
        PresupuestosAjax.init();
        PresupuestosObservaciones.init();
        PresupuestosUI.init();
        
        // Configuración inicial
        PresupuestosFormulario.establecerPeriodoActual();
        PresupuestosUI.actualizarEstadoPeriodo();
        
        // Verificar si las funciones existen antes de llamarlas
        if (typeof PresupuestosUI.ajustarClasesTabla === 'function') {
            PresupuestosUI.ajustarClasesTabla();
        } else {
            console.warn('PresupuestosUI.ajustarClasesTabla no está definida');
        }
        
        if (typeof PresupuestosUI.optimizarFormatoNumeros === 'function') {
            PresupuestosUI.optimizarFormatoNumeros();
        } else {
            console.warn('PresupuestosUI.optimizarFormatoNumeros no está definida');
        }
        
        // Cargar datos iniciales
        PresupuestosAjax.cargarPresupuestos();
        
        console.log('Aplicación de presupuestos inicializada correctamente');
        
    } catch (error) {
        console.error('Error inicializando la aplicación:', error);
        PresupuestosUtils.mostrarError('Error al inicializar la aplicación: ' + error.message);
    }
});

/**
 * Configuración global de la aplicación
 */
window.PresupuestosConfig = {
    debug: true,
    timeout: 30000,
    maxCaracteresObservacion: 1000,
    
    // URLs de endpoints
    endpoints: {
        obtenerPresupuestos: 'Controller/obtenerPresupuestos.php',
        guardarPresupuestos: 'Controller/guardarPresupuestos.php',
        copiarPeriodo: 'Controller/copiarPresupuestoAnterior.php',
        observaciones: 'Controller/gestionarObservaciones.php'
    },
    
    // Selectores principales
    selectores: {
        formulario: '#presupuestosForm',
        periodo: '#periodoPresupuesto',
        inputsPresupuesto: '.presupuesto-input',
        totalGeneral: '#totalGeneral',
        statusIndicator: '#statusIndicator'
    }
};