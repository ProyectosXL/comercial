/**
 * Gestión de Stock - Funcionalidades para Franquicias
 */

class FranquiciaStockManager {
    constructor() {
        this.rubroActual = null;
        this.categoriasData = [];
        this.stockData = {};
        this.periodoActual = this.obtenerPeriodoActual();
        
        this.init();
    }
    
    /**
     * Función auxiliar para hacer peticiones fetch con manejo de errores
     */
    async fetchWithErrorHandling(url, options) {
        try {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Respuesta no es JSON:', text);
                throw new Error('El servidor no devolvió una respuesta JSON válida');
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error en petición fetch:', error);
            throw error;
        }
    }
    
    init() {
        this.cargarRubros();
        this.configurarEventos();
        this.verificarEstadoCarga();
        this.cargarResumenStock();
    }
    
    /**
     * Configurar eventos del DOM
     */
    configurarEventos() {
        // Selector de rubro
        document.getElementById('selector-rubro').addEventListener('change', (e) => {
            this.seleccionarRubro(e.target.value);
        });
        
        // Botón guardar stock
        document.getElementById('btn-guardar-stock').addEventListener('click', () => {
            this.guardarStock();
        });
        
        // Botón limpiar rubro
        document.getElementById('btn-limpiar-rubro').addEventListener('click', () => {
            this.limpiarRubro();
        });
    }
    
    /**
     * Cargar rubros disponibles
     */
    async cargarRubros() {
        try {
            const data = await this.fetchWithErrorHandling('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'accion=obtener_rubros'
            });
            
            if (data.success) {
                this.llenarSelectorRubros(data.rubros);
            } else {
                this.mostrarAlerta(data.message || 'Error al cargar rubros', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta('Error de conexión al cargar rubros', 'danger');
        }
    }
    
    /**
     * Llenar el selector de rubros
     */
    llenarSelectorRubros(rubros) {
        const selector = document.getElementById('selector-rubro');
        
        // Limpiar opciones existentes excepto la primera
        while (selector.children.length > 1) {
            selector.removeChild(selector.lastChild);
        }
        
        // Agregar nuevas opciones
        rubros.forEach(rubro => {
            const option = document.createElement('option');
            option.value = rubro.RUBRO;
            option.textContent = rubro.RUBRO;
            selector.appendChild(option);
        });
    }
    
    /**
     * Seleccionar rubro y cargar categorías
     */
    async seleccionarRubro(rubro) {
        if (!rubro) {
            this.ocultarCategorias();
            return;
        }
        
        this.rubroActual = rubro;
        
        try {
            const data = await this.fetchWithErrorHandling('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=obtener_categorias&rubro=${encodeURIComponent(rubro)}`
            });
            
            if (data.success) {
                this.categoriasData = data.categorias;
                this.mostrarCategorias();
                await this.cargarStockExistente(rubro);
            } else {
                this.mostrarAlerta(data.message || 'Error al cargar categorías', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta('Error de conexión', 'danger');
        }
    }
    
    /**
     * Mostrar tabla de categorías
     */
    mostrarCategorias() {
        const contenedor = document.getElementById('contenedor-categorias');
        const tabla = document.getElementById('tabla-categorias');
        const mensaje = document.getElementById('mensaje-sin-rubro');
        
        // Limpiar tabla
        tabla.innerHTML = '';
        
        // Verificar que categoriasData existe y es un array
        if (!this.categoriasData || !Array.isArray(this.categoriasData)) {
            console.warn('categoriasData no es válido:', this.categoriasData);
            this.mostrarAlerta('No se encontraron categorías', 'warning');
            return;
        }
        
        // Agregar filas de categorías
        this.categoriasData.forEach((categoria, index) => {
            const fila = this.crearFilaCategoria(categoria.nombre, index);
            tabla.appendChild(fila);
        });
        
        // Mostrar contenedor y ocultar mensaje
        contenedor.style.display = 'block';
        mensaje.style.display = 'none';
        
        // Actualizar contadores
        this.actualizarContadores();
    }
    
    /**
     * Crear fila de categoría
     */
    crearFilaCategoria(categoria, index) {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <i class="bi bi-tag-fill text-primary me-2"></i>
                    <strong>${categoria}</strong>
                </div>
            </td>
            <td>
                <div class="input-group">
                    <input type="number" 
                           id="stock-${categoria.replace(/[^a-zA-Z0-9]/g, '_')}-${index}"
                           name="stock[${categoria}]"
                           class="form-control cantidad-input" 
                           data-categoria="${categoria}"
                           data-index="${index}"
                           placeholder="0" 
                           min="0" 
                           step="1"
                           autocomplete="off"
                           aria-label="Cantidad en stock para ${categoria}"
                           value="${this.stockData[categoria] || ''}">
                    <span class="input-group-text">
                        <i class="bi bi-box me-1"></i>unidades
                    </span>
                </div>
            </td>
            <td>
                <button type="button" 
                        class="btn btn-sm btn-outline-secondary"
                        onclick="franquiciaStock.limpiarCategoria('${categoria}')"
                        title="Limpiar cantidad">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </td>
        `;
        
        // Configurar evento de input
        const input = fila.querySelector('.cantidad-input');
        input.addEventListener('input', () => {
            this.actualizarStock(categoria, input.value);
            this.actualizarContadores();
            this.habilitarBotonGuardar();
        });
        
        return fila;
    }
    
    /**
     * Cargar stock existente del período actual
     */
    async cargarStockExistente(rubro) {
        try {
            const data = await this.fetchWithErrorHandling('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=obtener_resumen_stock&periodo=${this.periodoActual}`
            });
            
            if (data.success) {
                // Filtrar stock por rubro actual
                const stockRubro = data.stock.filter(item => item.rubro === rubro);
                
                // Actualizar inputs con datos existentes
                stockRubro.forEach(item => {
                    const input = document.querySelector(`input[data-categoria="${item.categoria}"]`);
                    if (input) {
                        input.value = item.cantidad;
                        this.stockData[item.categoria] = item.cantidad;
                    }
                });
                
                this.actualizarContadores();
                this.habilitarBotonGuardar();
            }
        } catch (error) {
            console.error('Error al cargar stock existente:', error);
        }
    }
    
    /**
     * Actualizar stock en memoria
     */
    actualizarStock(categoria, cantidad) {
        if (cantidad === '' || cantidad === null) {
            delete this.stockData[categoria];
        } else {
            this.stockData[categoria] = parseInt(cantidad) || 0;
        }
    }
    
    /**
     * Limpiar categoría específica
     */
    limpiarCategoria(categoria) {
        const input = document.querySelector(`input[data-categoria="${categoria}"]`);
        if (input) {
            input.value = '';
            delete this.stockData[categoria];
            this.actualizarContadores();
            this.habilitarBotonGuardar();
        }
    }
    
    /**
     * Limpiar todo el rubro (con confirmación)
     */
    limpiarRubro() {
        if (confirm('¿Está seguro de que desea limpiar todas las cantidades del rubro actual?')) {
            this.limpiarRubroSinConfirmacion();
        }
    }
    
    /**
     * Limpiar rubro sin confirmación (para uso interno después de guardar)
     */
    limpiarRubroSinConfirmacion() {
        document.querySelectorAll('.cantidad-input').forEach(input => {
            input.value = '';
        });
        this.stockData = {};
        this.actualizarContadores();
        this.deshabilitarBotonGuardar();
    }
    
    /**
     * Actualizar contadores
     */
    actualizarContadores() {
        const totalCategorias = this.categoriasData.length;
        const completadas = Object.keys(this.stockData).length;
        
        document.getElementById('total-categorias').textContent = totalCategorias;
        document.getElementById('categorias-completadas').textContent = completadas;
    }
    
    /**
     * Habilitar botón guardar
     */
    habilitarBotonGuardar() {
        const boton = document.getElementById('btn-guardar-stock');
        const tieneStock = Object.keys(this.stockData).length > 0;
        
        boton.disabled = !tieneStock;
        
        if (tieneStock) {
            boton.classList.remove('btn-secondary');
            boton.classList.add('btn-success');
        } else {
            boton.classList.remove('btn-success');
            boton.classList.add('btn-secondary');
        }
    }
    
    /**
     * Deshabilitar botón guardar
     */
    deshabilitarBotonGuardar() {
        const boton = document.getElementById('btn-guardar-stock');
        boton.disabled = true;
        boton.classList.remove('btn-success');
        boton.classList.add('btn-secondary');
    }
    
    /**
     * Guardar stock
     */
    async guardarStock() {
        console.log('DEBUG: Iniciando guardarStock');
        
        if (!this.rubroActual || Object.keys(this.stockData).length === 0) {
            this.mostrarAlerta('Debe seleccionar un rubro y cargar al menos una cantidad', 'warning');
            return;
        }
        
        // Preparar datos para enviar
        const stockArray = Object.entries(this.stockData).map(([categoria, cantidad]) => ({
            rubro: this.rubroActual,
            categoria: categoria,
            cantidad: cantidad
        }));
        
        console.log('DEBUG: Datos a enviar:', stockArray);
        
        try {
            this.mostrarLoading('Guardando stock...');
            
            const data = await this.fetchWithErrorHandling('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=registrar_movimiento&periodo=${this.periodoActual}&stock_data=${encodeURIComponent(JSON.stringify(stockArray))}`
            });
            
            console.log('DEBUG: Respuesta del servidor:', data);
            this.ocultarLoading();
            
            if (data.success) {
                console.log('DEBUG: Guardado exitoso, limpiando formulario');
                this.mostrarAlerta('Stock guardado correctamente', 'success');
                
                // Actualizar estados
                await this.verificarEstadoCarga();
                await this.cargarResumenStock();
                
                // Limpiar formulario automáticamente después de guardar exitosamente
                this.limpiarRubroSinConfirmacion();
                document.getElementById('selector-rubro').value = '';
                this.ocultarCategorias();
                
                console.log('DEBUG: Formulario limpiado exitosamente');
            } else {
                console.error('DEBUG: Error en respuesta del servidor:', data.message);
                this.mostrarAlerta(data.message || 'Error al guardar el stock', 'danger');
            }
        } catch (error) {
            this.ocultarLoading();
            console.error('DEBUG: Error en guardarStock:', error);
            this.mostrarAlerta('Error de conexión', 'danger');
        }
    }
    
    /**
     * Verificar estado de carga del período actual
     */
    async verificarEstadoCarga() {
        try {
            const data = await this.fetchWithErrorHandling('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=obtener_resumen_stock`
            });
            
            if (data.success) {
                const tieneStock = data.stock && data.stock.length > 0;
                this.actualizarEstadoCarga(tieneStock);
            } else {
                console.warn('Error al verificar estado:', data.message);
            }
        } catch (error) {
            console.error('Error al verificar estado:', error);
        }
    }
    
    /**
     * Actualizar indicadores de estado de carga
     */
    actualizarEstadoCarga(tieneStock) {
        const badge = document.getElementById('badge-estado');
        const infoEstado = document.getElementById('info-estado-carga');
        
        if (tieneStock) {
            if (badge) {
                badge.className = 'estado-badge informo';
                badge.innerHTML = '<i class="bi bi-check-circle me-1"></i>Informó';
            }
            if (infoEstado) {
                infoEstado.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        <span class="text-success fw-bold">Stock cargado para este período</span>
                    </div>
                `;
            }
        } else {
            if (badge) {
                badge.className = 'estado-badge no-informo';
                badge.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Pendiente';
            }
            if (infoEstado) {
                infoEstado.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        <span class="text-warning fw-bold">Debe cargar el stock del período actual</span>
                    </div>
                `;
            }
        }
    }
    
    /**
     * Cargar resumen de stock cargado
     */
    async cargarResumenStock() {
        try {
            const data = await this.fetchWithErrorHandling('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=obtener_resumen_stock&periodo=${this.periodoActual}`
            });
            
            if (data.success && data.stock.length > 0) {
                this.mostrarResumenStock(data.stock);
            } else {
                this.ocultarResumenStock();
            }
        } catch (error) {
            console.error('Error al cargar resumen:', error);
        }
    }
    
    /**
     * Mostrar resumen de stock cargado
     */
    mostrarResumenStock(stockData) {
        const seccion = document.getElementById('seccion-resumen');
        const contenido = document.getElementById('contenido-resumen');
        
        // Agrupar por rubro
        const stockPorRubro = {};
        let totalItems = 0;
        let totalStock = 0;
        
        stockData.forEach(item => {
            if (!stockPorRubro[item.rubro]) {
                stockPorRubro[item.rubro] = [];
            }
            stockPorRubro[item.rubro].push(item);
            totalItems++;
            totalStock += parseInt(item.cantidad) || 0;
        });
        
        let html = `
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center py-2">
                            <h4 class="text-primary mb-1">${Object.keys(stockPorRubro).length}</h4>
                            <small class="text-muted">Rubros Cargados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body text-center py-2">
                            <h4 class="text-info mb-1">${totalItems}</h4>
                            <small class="text-muted">Categorías</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body text-center py-2">
                            <h4 class="text-success mb-1">${totalStock.toLocaleString()}</h4>
                            <small class="text-muted">Total Stock</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead class="table-secondary">
                        <tr>
                            <th>Rubro</th>
                            <th>Categoría</th>
                            <th class="text-end">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        Object.entries(stockPorRubro).forEach(([rubro, items]) => {
            items.forEach(item => {
                html += `
                    <tr>
                        <td><strong>${item.rubro}</strong></td>
                        <td>${item.categoria}</td>
                        <td class="text-end">${parseInt(item.cantidad).toLocaleString()}</td>
                    </tr>
                `;
            });
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        contenido.innerHTML = html;
        seccion.style.display = 'block';
    }
    
    /**
     * Ocultar resumen de stock
     */
    ocultarResumenStock() {
        const seccion = document.getElementById('seccion-resumen');
        seccion.style.display = 'none';
    }
    
    /**
     * Ocultar tabla de categorías
     */
    ocultarCategorias() {
        const contenedor = document.getElementById('contenedor-categorias');
        const mensaje = document.getElementById('mensaje-sin-rubro');
        
        contenedor.style.display = 'none';
        mensaje.style.display = 'block';
        
        this.rubroActual = null;
        this.categoriasData = [];
        this.stockData = {};
        this.deshabilitarBotonGuardar();
    }
    
    /**
     * Obtener período actual (mm-yyyy)
     */
    obtenerPeriodoActual() {
        const fecha = new Date();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const año = fecha.getFullYear();
        return `${mes}-${año}`;
    }
    
    /**
     * Mostrar alerta
     */
    mostrarAlerta(mensaje, tipo = 'info') {
        const container = document.getElementById('alertas-container');
        const alertId = `alert-${Date.now()}`;
        
        // Mapear tipos comunes
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
        
        const alertHTML = `
            <div class="alert alert-${tipoFinal} alert-dismissible fade show d-flex align-items-center" role="alert" id="${alertId}">
                <i class="bi bi-${this.getIconoAlerta(tipoFinal)} me-2" style="font-size: 1.2rem;"></i>
                <div class="flex-grow-1">
                    <strong class="me-2">${this.getTituloAlerta(tipoFinal)}</strong>
                    <span>${mensaje}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        `;
        
        container.insertAdjacentHTML('afterbegin', alertHTML);
        
        // Auto-remover después de 6 segundos
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 6000);
    }
    
    /**
     * Obtener título para alerta
     */
    getTituloAlerta(tipo) {
        const titulos = {
            'success': 'Éxito:',
            'danger': 'Error:',
            'warning': 'Advertencia:',
            'info': 'Información:'
        };
        return titulos[tipo] || 'Aviso:';
    }
    
    /**
     * Obtener icono para alerta
     */
    getIconoAlerta(tipo) {
        const iconos = {
            'success': 'check-circle',
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return iconos[tipo] || 'info-circle';
    }
    
    /**
     * Mostrar loading
     */
    mostrarLoading(mensaje = 'Procesando...') {
        document.getElementById('mensaje-loading').textContent = mensaje;
        const modal = new bootstrap.Modal(document.getElementById('modalLoading'));
        modal.show();
    }
    
    /**
     * Ocultar loading
     */
    ocultarLoading() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalLoading'));
        if (modal) {
            modal.hide();
        }
    }
}

// Funciones globales para uso en eventos HTML
let franquiciaStock;

document.addEventListener('DOMContentLoaded', function() {
    franquiciaStock = new FranquiciaStockManager();
});

/**
 * Cargar historial de stock
 */
async function cargarHistorial() {
    try {
        const data = await franquiciaStock.fetchWithErrorHandling('controller/franquicia_stock_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'accion=obtener_historial'
        });
        
        if (data.success) {
            // Redirigir a vista de historial con los datos
            window.location.href = '?vista=historial';
        } else {
            franquiciaStock.mostrarAlerta(data.message || 'Error al cargar el historial', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        franquiciaStock.mostrarAlerta('Error de conexión', 'danger');
    }
}