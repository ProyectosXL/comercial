/**
 * Gestión de Stock - Funcionalidades para Franquicias (VERSIÓN ACTUALIZADA)
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
        // Esperar a que el DOM esté completamente cargado
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.inicializar();
            });
        } else {
            this.inicializar();
        }
    }
    
    inicializar() {
        this.inicializarBusquedaArticulos();
        this.cargarRubros();
        this.configurarEventos();
        this.verificarEstadoCarga();
        this.cargarResumenStock();
    }
    
    /**
     * Inicializar Select2 para búsqueda de artículos
     */
    inicializarBusquedaArticulos() {
        // Verificar que jQuery y Select2 estén cargados
        if (typeof $ === 'undefined') {
            console.warn('jQuery no está cargado, esperando...');
            setTimeout(() => this.inicializarBusquedaArticulos(), 500);
            return;
        }
        
        if (!$.fn.select2) {
            console.warn('Select2 no está cargado, inicializando búsqueda simple');
            this.inicializarBusquedaSimple();
            return;
        }

        const selector = $('#buscar-articulo');
        
        // Verificar que el elemento existe
        if (selector.length === 0) {
            console.warn('Elemento buscar-articulo no encontrado');
            return;
        }
        
        selector.select2({
            theme: 'bootstrap-5',
            placeholder: 'Escriba para buscar artículos...',
            allowClear: true,
            minimumInputLength: 2,
            language: {
                inputTooShort: function() {
                    return 'Ingrese al menos 2 caracteres';
                },
                searching: function() {
                    return 'Buscando...';
                },
                noResults: function() {
                    return 'No se encontraron artículos';
                },
                errorLoading: function() {
                    return 'Error al cargar resultados';
                }
            },
            ajax: {
                url: 'controller/franquicia_stock_controller.php',
                type: 'POST',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        accion: 'buscar_articulos',
                        termino: params.term
                    };
                },
                processResults: function(data) {
                    console.log('Datos recibidos:', data);
                    
                    if (data.success && data.articulos) {
                        return {
                            results: data.articulos.map(articulo => ({
                                id: articulo.codigo,
                                text: `${articulo.codigo} - ${articulo.descripcion}`,
                                articulo: articulo
                            }))
                        };
                    } else {
                        console.warn('Sin resultados o error:', data.message);
                        return {
                            results: []
                        };
                    }
                },
                cache: true
            },
            templateResult: function(option) {
                if (option.loading) return option.text;
                if (!option.articulo) return $('<span>' + option.text + '</span>');
                
                return $(`
                    <div class="select2-articulo-result">
                        <div class="fw-bold">${option.articulo.codigo}</div>
                        <div class="text-muted small">${option.articulo.descripcion}</div>
                        <div class="small">
                            <span class="badge bg-primary">${option.articulo.rubro}</span>
                            <span class="badge bg-secondary">${option.articulo.categoria_padre}</span>
                        </div>
                    </div>
                `);
            },
            templateSelection: function(option) {
                return option.articulo ? 
                       `${option.articulo.codigo} - ${option.articulo.descripcion}` : 
                       option.text;
            }
        });

        // Evento cuando se selecciona un artículo
        selector.on('select2:select', (e) => {
            const data = e.params.data;
            if (data.articulo) {
                this.mostrarInfoArticulo(data.articulo);
            }
        });

        // Evento cuando se limpia la selección
        selector.on('select2:clear', () => {
            this.ocultarInfoArticulo();
        });
        
        console.log('Select2 inicializado correctamente');
    }

    /**
     * Inicializar búsqueda simple sin Select2 (fallback)
     */
    inicializarBusquedaSimple() {
        const selector = document.getElementById('buscar-articulo');
        if (!selector) return;
        
        // Convertir select a input
        const input = document.createElement('input');
        input.type = 'text';
        input.id = 'buscar-articulo';
        input.className = 'form-control';
        input.placeholder = 'Escriba código o descripción del artículo...';
        
        selector.parentNode.replaceChild(input, selector);
        
        // Agregar evento de búsqueda con debounce
        let timeoutId;
        input.addEventListener('input', (e) => {
            clearTimeout(timeoutId);
            const termino = e.target.value.trim();
            
            if (termino.length < 2) {
                this.ocultarInfoArticulo();
                return;
            }
            
            timeoutId = setTimeout(() => {
                this.buscarArticulosSimple(termino);
            }, 500);
        });
        
        console.log('Búsqueda simple inicializada');
    }

    /**
     * Búsqueda simple de artículos (sin Select2)
     */
    async buscarArticulosSimple(termino) {
        try {
            const data = await this.fetchWithErrorHandling('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=buscar_articulos&termino=${encodeURIComponent(termino)}`
            });
            
            if (data.success && data.articulos.length > 0) {
                // Mostrar el primer resultado
                this.mostrarInfoArticulo(data.articulos[0]);
                
                // Si hay múltiples resultados, mostrar mensaje
                if (data.articulos.length > 1) {
                    this.mostrarAlerta(`Se encontraron ${data.articulos.length} artículos. Mostrando el primero.`, 'info');
                }
            } else {
                this.mostrarSinResultados();
            }
        } catch (error) {
            console.error('Error en búsqueda simple:', error);
            this.mostrarAlerta('Error al buscar artículos', 'danger');
        }
    }

    /**
     * Mostrar información del artículo seleccionado
     */
    mostrarInfoArticulo(articulo) {
        const contenedor = document.getElementById('info-articulo');
        const resultado = document.getElementById('resultado-busqueda-articulo');
        const sinResultados = document.getElementById('sin-resultados-articulo');
        
        if (!contenedor || !resultado) {
            console.warn('Elementos de resultado no encontrados');
            return;
        }
        
        contenedor.innerHTML = `
            <div class="col-md-6">
                <div class="card border-primary info-articulo-card">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-2 text-primary">
                            <i class="bi bi-upc-scan me-1"></i>
                            Código de Artículo
                        </h6>
                        <p class="card-text mb-0">
                            <strong class="fs-5">${articulo.codigo}</strong>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-info info-articulo-card">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-2 text-info">
                            <i class="bi bi-card-text me-1"></i>
                            Descripción
                        </h6>
                        <p class="card-text mb-0">
                            <strong>${articulo.descripcion}</strong>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mt-3">
                <div class="card border-warning info-articulo-card">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-2 text-warning">
                            <i class="bi bi-tags me-1"></i>
                            Rubro
                        </h6>
                        <p class="card-text mb-0">
                            <span class="badge bg-warning text-dark fs-6">${articulo.rubro}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mt-3">
                <div class="card border-success info-articulo-card">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-2 text-success">
                            <i class="bi bi-bookmark me-1"></i>
                            Categoría
                        </h6>
                        <p class="card-text mb-0">
                            <span class="badge bg-success fs-6">${articulo.categoria_padre}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-12 mt-3">
                <div class="alert alert-success d-flex align-items-start" role="alert">
                    <i class="bi bi-lightbulb me-2 mt-1"></i>
                    <div>
                        <strong>💡 Información encontrada:</strong><br>
                        Para cargar stock de este artículo, seleccione el rubro <strong>"${articulo.rubro}"</strong> 
                        y luego la categoría <strong>"${articulo.categoria_padre}"</strong> en el formulario de carga de stock.
                    </div>
                </div>
            </div>
        `;
        
        resultado.style.display = 'block';
        if (sinResultados) {
            sinResultados.style.display = 'none';
        }
        
        // Scroll suave al resultado
        resultado.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'nearest' 
        });
    }

    /**
     * Mostrar mensaje sin resultados
     */
    mostrarSinResultados() {
        const resultado = document.getElementById('resultado-busqueda-articulo');
        const sinResultados = document.getElementById('sin-resultados-articulo');
        
        if (resultado) resultado.style.display = 'none';
        if (sinResultados) sinResultados.style.display = 'block';
    }

    /**
     * Ocultar información del artículo
     */
    ocultarInfoArticulo() {
        const resultado = document.getElementById('resultado-busqueda-articulo');
        const sinResultados = document.getElementById('sin-resultados-articulo');
        
        if (resultado) resultado.style.display = 'none';
        if (sinResultados) sinResultados.style.display = 'none';
    }
    
    /**
     * Configurar eventos del DOM
     */
    configurarEventos() {
        // Selector de rubro
        const selectorRubro = document.getElementById('selector-rubro');
        if (selectorRubro) {
            selectorRubro.addEventListener('change', (e) => {
                this.seleccionarRubro(e.target.value);
            });
        }
        
        // Botón guardar stock
        const btnGuardar = document.getElementById('btn-guardar-stock');
        if (btnGuardar) {
            btnGuardar.addEventListener('click', () => {
                this.guardarStock();
            });
        }
        
        // Botón limpiar rubro
        const btnLimpiar = document.getElementById('btn-limpiar-rubro');
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', () => {
                this.limpiarRubro();
            });
        }
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
        if (!selector) return;
        
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
        
        if (!contenedor || !tabla || !mensaje) return;
        
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
        
        const totalElement = document.getElementById('total-categorias');
        const completadasElement = document.getElementById('categorias-completadas');
        
        if (totalElement) totalElement.textContent = totalCategorias;
        if (completadasElement) completadasElement.textContent = completadas;
    }
    
    /**
     * Habilitar botón guardar
     */
    habilitarBotonGuardar() {
        const boton = document.getElementById('btn-guardar-stock');
        if (!boton) return;
        
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
        if (!boton) return;
        
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
                const selectorRubro = document.getElementById('selector-rubro');
                if (selectorRubro) selectorRubro.value = '';
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
        
        if (!seccion || !contenido) return;
        
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
        if (seccion) seccion.style.display = 'none';
    }
    
    /**
     * Ocultar tabla de categorías
     */
    ocultarCategorias() {
        const contenedor = document.getElementById('contenedor-categorias');
        const mensaje = document.getElementById('mensaje-sin-rubro');
        
        if (contenedor) contenedor.style.display = 'none';
        if (mensaje) mensaje.style.display = 'block';
        
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
        if (!container) return;
        
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
        const mensajeElement = document.getElementById('mensaje-loading');
        if (mensajeElement) mensajeElement.textContent = mensaje;
        
        const modalElement = document.getElementById('modalLoading');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }
    
    /**
     * Ocultar loading
     */
    ocultarLoading() {
        const modalElement = document.getElementById('modalLoading');
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
    }
}

// Funciones globales para uso en eventos HTML
let franquiciaStock;

document.addEventListener('DOMContentLoaded', function() {
    franquiciaStock = new FranquiciaStockManager();
});

// Funciones globales para uso en HTML
function limpiarBusquedaArticulo() {
    // Limpiar Select2 si está inicializado
    if (typeof $ !== 'undefined' && $('#buscar-articulo').length) {
        const selector = $('#buscar-articulo');
        if (selector.hasClass('select2-hidden-accessible')) {
            selector.val(null).trigger('change');
        } else {
            // Limpiar input simple
            const input = document.getElementById('buscar-articulo');
            if (input) input.value = '';
        }
    }
    
    // Ocultar resultados
    if (franquiciaStock) {
        franquiciaStock.ocultarInfoArticulo();
    }
}

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