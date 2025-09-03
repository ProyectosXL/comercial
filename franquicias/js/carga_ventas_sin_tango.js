/**
 * JavaScript para Carga de Ventas sin Tango
 * Sistema de Franquicias - Comercial XL
 */

class CargaVentasSinTango {
    constructor() {
        this.initialized = false;
        this.currentData = {};
        this.init();
    }

    init() {
        try {
            console.log('Inicializando sistema de Carga de Ventas sin Tango...');
            
            // Esperar a que el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
            } else {
                this.setupEventListeners();
            }
            
            this.initialized = true;
            console.log('Sistema de Carga de Ventas inicializado correctamente');
            
        } catch (error) {
            console.error('Error inicializando Carga de Ventas:', error);
        }
    }

    setupEventListeners() {
        // Configurar botones principales
        this.setupMainButtons();
        
        // Configurar formularios
        this.setupForms();
        
        // Configurar tooltips
        this.setupTooltips();
        
        // Configurar animaciones
        this.setupAnimations();
    }

    setupMainButtons() {
        // Botón de carga de ventas
        const btnCargaVentas = document.getElementById('btnCargaVentas');
        if (btnCargaVentas) {
            btnCargaVentas.addEventListener('click', (e) => {
                e.preventDefault();
                this.iniciarCargaVentas();
            });
        }

        // Botón de validación sin Tango
        const btnValidarSinTango = document.getElementById('btnValidarSinTango');
        if (btnValidarSinTango) {
            btnValidarSinTango.addEventListener('click', (e) => {
                e.preventDefault();
                this.validarSinTango();
            });
        }

        // Botón de exportar datos
        const btnExportar = document.getElementById('btnExportar');
        if (btnExportar) {
            btnExportar.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportarDatos();
            });
        }
    }

    setupForms() {
        // Formulario principal de carga de ventas
        const formCargaVentas = document.getElementById('formCargaVentas');
        if (formCargaVentas) {
            formCargaVentas.addEventListener('submit', (e) => {
                e.preventDefault();
                this.procesarCargaVentas(e.target);
            });
        }

        // Validación en tiempo real
        const inputs = document.querySelectorAll('.form-ventas input, .form-ventas select');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                this.validarCampo(input);
            });
        });
    }

    setupTooltips() {
        // Inicializar tooltips de Bootstrap
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }

    setupAnimations() {
        // Añadir animaciones a las cards
        const cards = document.querySelectorAll('.carga-ventas-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('animate-fadeInUp');
            }, index * 100);
        });
    }

    // Métodos principales del sistema
    iniciarCargaVentas() {
        console.log('Iniciando proceso de carga de ventas...');
        
        this.mostrarLoader('Preparando sistema de carga...');
        
        // Simular proceso de inicialización
        setTimeout(() => {
            this.ocultarLoader();
            this.mostrarFormularioCarga();
        }, 1500);
    }

    validarSinTango() {
        console.log('Validando configuración sin Tango...');
        
        this.mostrarLoader('Validando acceso sin Tango...');
        
        // Simular validación
        setTimeout(() => {
            this.ocultarLoader();
            this.mostrarResultadoValidacion(true);
        }, 2000);
    }

    procesarCargaVentas(form) {
        console.log('Procesando carga de ventas...');
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Validar datos
        if (!this.validarDatosCarga(data)) {
            this.mostrarError('Por favor complete todos los campos requeridos');
            return;
        }

        this.mostrarLoader('Procesando carga de ventas...');
        
        // Simular procesamiento
        setTimeout(() => {
            this.ocultarLoader();
            this.mostrarExito('Carga de ventas procesada correctamente');
            this.limpiarFormulario(form);
        }, 3000);
    }

    exportarDatos() {
        console.log('Exportando datos de ventas...');
        
        this.mostrarLoader('Preparando exportación...');
        
        // Simular exportación
        setTimeout(() => {
            this.ocultarLoader();
            this.mostrarExito('Datos exportados correctamente');
        }, 2000);
    }

    // Métodos de utilidad
    validarCampo(campo) {
        const valor = campo.value.trim();
        const esRequerido = campo.hasAttribute('required');
        
        if (esRequerido && !valor) {
            this.marcarCampoInvalido(campo, 'Este campo es requerido');
            return false;
        }
        
        this.marcarCampoValido(campo);
        return true;
    }

    validarDatosCarga(data) {
        // Implementar validaciones específicas aquí
        return Object.values(data).some(value => value && value.trim());
    }

    marcarCampoInvalido(campo, mensaje) {
        campo.classList.remove('is-valid');
        campo.classList.add('is-invalid');
        
        let feedback = campo.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            campo.parentNode.appendChild(feedback);
        }
        feedback.textContent = mensaje;
    }

    marcarCampoValido(campo) {
        campo.classList.remove('is-invalid');
        campo.classList.add('is-valid');
        
        const feedback = campo.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }

    mostrarFormularioCarga() {
        // Mostrar modal o sección del formulario
        const modalCarga = document.getElementById('modalCargaVentas');
        if (modalCarga && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalCarga);
            modal.show();
        }
    }

    mostrarResultadoValidacion(exitoso) {
        const contenedor = document.getElementById('resultadoValidacion');
        if (!contenedor) return;

        const clase = exitoso ? 'alert-success' : 'alert-danger';
        const icono = exitoso ? 'bi-check-circle' : 'bi-x-circle';
        const mensaje = exitoso ? 'Validación exitosa: Sistema configurado sin Tango' : 'Error en validación';

        contenedor.innerHTML = `
            <div class="alert ${clase} d-flex align-items-center" role="alert">
                <i class="bi ${icono} me-2"></i>
                ${mensaje}
            </div>
        `;
    }

    limpiarFormulario(form) {
        form.reset();
        const campos = form.querySelectorAll('.is-valid, .is-invalid');
        campos.forEach(campo => {
            campo.classList.remove('is-valid', 'is-invalid');
        });
    }

    // Métodos de UI
    mostrarLoader(mensaje = 'Cargando...') {
        let loader = document.getElementById('loaderCargaVentas');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'loaderCargaVentas';
            loader.className = 'position-fixed top-50 start-50 translate-middle';
            loader.style.zIndex = '9999';
            document.body.appendChild(loader);
        }

        loader.innerHTML = `
            <div class="d-flex flex-column align-items-center">
                <div class="spinner-border text-success mb-3" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="bg-white px-3 py-2 rounded shadow">
                    <span class="text-muted">${mensaje}</span>
                </div>
            </div>
        `;
        loader.style.display = 'block';
    }

    ocultarLoader() {
        const loader = document.getElementById('loaderCargaVentas');
        if (loader) {
            loader.style.display = 'none';
        }
    }

    mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'danger');
    }

    mostrarExito(mensaje) {
        this.mostrarNotificacion(mensaje, 'success');
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        const contenedor = document.getElementById('notificaciones') || document.body;
        
        const notificacion = document.createElement('div');
        notificacion.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
        notificacion.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        
        notificacion.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        contenedor.appendChild(notificacion);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            if (notificacion.parentNode) {
                notificacion.remove();
            }
        }, 5000);
    }

    // Método público para obtener estado
    getEstado() {
        return {
            initialized: this.initialized,
            currentData: this.currentData
        };
    }
}

// Inicializar cuando el script se carga
let cargaVentasSystem;

document.addEventListener('DOMContentLoaded', function() {
    cargaVentasSystem = new CargaVentasSinTango();
    
    // Hacer disponible globalmente para debugging
    window.cargaVentasSystem = cargaVentasSystem;
});

// Exportar para uso en otros scripts si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CargaVentasSinTango;
}
