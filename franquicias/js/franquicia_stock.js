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
    }
    
    /**
     * Cargar rubros disponibles
     */
    async cargarRubros() {
        try {
            console.log('DEBUG: Iniciando carga de rubros...');
            console.log('DEBUG: URL de petición:', window.location.origin + '/franquicias/controller/franquicia_stock_controller.php');
            
            const response = await fetch('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'accion=obtener_rubros'
            });
            
            console.log('DEBUG: Status de respuesta:', response.status);
            console.log('DEBUG: Headers de respuesta:', [...response.headers.entries()]);
            
            const contentType = response.headers.get('content-type');
            console.log('DEBUG: Content-Type de respuesta:', contentType);
            
            let data;
            try {
                const textResponse = await response.text();
                console.log('DEBUG: Respuesta cruda:', textResponse);
                
                try {
                    data = JSON.parse(textResponse);
                    console.log('DEBUG: Respuesta parseada:', data);
                } catch (e) {
                    console.error('DEBUG: Error parseando JSON:', e);
                    throw new Error('Respuesta no es JSON válido: ' + textResponse);
                }
            } catch (e) {
                console.error('DEBUG: Error obteniendo texto de respuesta:', e);
                throw e;
            }
            
            if (data.success) {
                console.log('DEBUG: Datos de éxito recibidos:', {
                    rubros: data.rubros,
                    count: data?.rubros?.length,
                    source: data.source
                });
                this.llenarSelectorRubros(data.rubros);
            } else {
                console.error('DEBUG: Error en respuesta:', {
                    message: data.message,
                    error: data.error
                });
                this.mostrarAlerta(data.message || 'Error al cargar rubros', 'danger');
            }
        } catch (error) {
            console.error('DEBUG: Error en cargarRubros:', error);
            this.mostrarAlerta('Error de conexión al cargar rubros: ' + error.message, 'danger');
        }
    }
    
    /**
     * Llenar el selector de rubros
     */
    llenarSelectorRubros(rubros) {
        console.log('DEBUG: Llenando selector con rubros:', rubros);
        
        // 1. Obtener el selector
        const selector = document.getElementById('selector-rubro');
        if (!selector) {
            const error = 'No se encontró el elemento select#selector-rubro en el DOM';
            console.error('DEBUG:', error);
            this.mostrarAlerta(error, 'danger');
            return;
        }
        
        // 2. Validar los datos
        if (!rubros) {
            const error = 'No se recibieron datos de rubros';
            console.error('DEBUG:', error);
            this.mostrarAlerta(error, 'warning');
            return;
        }
        
        if (!Array.isArray(rubros)) {
            const error = `rubros no es un array: ${typeof rubros}`;
            console.error('DEBUG:', error, rubros);
            this.mostrarAlerta(error, 'danger');
            return;
        }
        
        console.log('DEBUG: Estructura de rubros:', rubros.map(r => ({
            tipo: typeof r,
            valor: r,
            esObjeto: typeof r === 'object',
            tieneRUBRO: r?.RUBRO !== undefined,
            tieneRubro: r?.rubro !== undefined
        })));
        
        // 3. Limpiar opciones existentes
        while (selector.children.length > 1) {
            selector.removeChild(selector.lastChild);
        }
        
        // 4. Crear fragmento para mejor rendimiento
        const fragment = document.createDocumentFragment();
        
        // 5. Procesar cada rubro
        rubros.forEach((rubro, index) => {
            try {
                const option = document.createElement('option');
                
                if (typeof rubro === 'object' && rubro !== null) {
                    // Si es objeto, intentar obtener el valor de RUBRO o rubro
                    if (rubro.RUBRO !== undefined) {
                        option.value = rubro.RUBRO;
                        option.textContent = rubro.RUBRO;
                    } else if (rubro.rubro !== undefined) {
                        option.value = rubro.rubro;
                        option.textContent = rubro.rubro;
                    } else {
                        console.warn('DEBUG: Objeto rubro sin propiedades esperadas:', rubro);
                        return; // Saltar este rubro
                    }
                } else if (typeof rubro === 'string') {
                    option.value = rubro;
                    option.textContent = rubro;
                } else {
                    console.warn('DEBUG: Tipo de rubro no manejado:', typeof rubro, rubro);
                    return; // Saltar este rubro
                }
                
                fragment.appendChild(option);
                
            } catch (e) {
                console.error('DEBUG: Error procesando rubro:', {
                    index,
                    rubro,
                    error: e
                });
            }
        });
        
        // 6. Agregar todas las opciones de una vez
        selector.appendChild(fragment);
        
        // 7. Verificar resultado
        console.log('DEBUG: Total opciones agregadas:', selector.options.length - 1);
        if (selector.options.length <= 1) {
            this.mostrarAlerta('No se encontraron rubros para cargar', 'warning');
        }
    }
    
    /**
     * Seleccionar rubro y cargar categorías
     */
    async seleccionarRubro(rubro) {
        try {
            console.log('DEBUG: Seleccionando rubro:', rubro);
            
            if (!rubro) {
                this.ocultarCategorias();
                return;
            }
            
            this.rubroActual = rubro;
            
            // Limpiar datos actuales
            this.categoriasData = [];
            this.stockData = {};
            
            // Obtener categorías
            const data = await this.fetchWithErrorHandling('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=obtener_categorias&rubro=${encodeURIComponent(rubro)}`
            });
            
            if (data.success) {
                console.log('DEBUG: Categorías obtenidas:', data.categorias);
                this.categoriasData = data.categorias;
                
                // Mostrar categorías antes de cargar el stock
                this.mostrarCategorias();
                
                // Cargar stock existente
                await this.cargarStockExistente(rubro);
                
                // Actualizar contadores y estado del botón
                this.actualizarContadores();
                this.habilitarBotonGuardar();
                
                console.log('DEBUG: Rubro cargado completamente:', rubro);
            } else {
                console.error('DEBUG: Error cargando categorías:', data.message);
                this.mostrarAlerta(data.message || 'Error al cargar categorías', 'danger');
            }
        } catch (error) {
            console.error('DEBUG: Error en seleccionarRubro:', error);
            this.mostrarAlerta('Error de conexión al cargar el rubro', 'danger');
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
                        class="btn btn-sm btn-outline-danger"
                        onclick="franquiciaStock.limpiarCategoria('${categoria}')"
                        title="Borrar cantidad">
                    <i class="bi bi-trash"></i>
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
            console.log('DEBUG: Cargando stock existente para rubro:', rubro);
            
            const data = await this.fetchWithErrorHandling('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=obtener_resumen_stock&periodo=${this.periodoActual}`
            });
            
            if (data.success) {
                // Limpiar datos actuales del rubro
                this.stockData = {};
                
                // Filtrar stock por rubro actual
                const stockRubro = data.stock.filter(item => item.rubro === rubro);
                console.log('DEBUG: Stock filtrado para rubro:', stockRubro);
                
                // Actualizar inputs y stockData
                stockRubro.forEach(item => {
                    const input = document.querySelector(`input[data-categoria="${item.categoria}"]`);
                    if (input) {
                        const cantidad = parseInt(item.cantidad) || 0;
                        input.value = cantidad;
                        this.stockData[item.categoria] = cantidad;
                        console.log('DEBUG: Actualizado:', item.categoria, 'con cantidad:', cantidad);
                    }
                });
                
                // Asegurarse que todas las categorías tengan un valor
                document.querySelectorAll('.cantidad-input').forEach(input => {
                    const categoria = input.getAttribute('data-categoria');
                    if (categoria && !this.stockData.hasOwnProperty(categoria)) {
                        input.value = '0';
                        this.stockData[categoria] = 0;
                        console.log('DEBUG: Inicializado:', categoria, 'con 0');
                    }
                });
                
                this.actualizarContadores();
                this.habilitarBotonGuardar();
                
                console.log('DEBUG: Stock cargado completamente:', this.stockData);
            } else {
                console.error('DEBUG: Error cargando stock:', data.message);
            }
        } catch (error) {
            console.error('DEBUG: Error al cargar stock existente:', error);
            this.mostrarAlerta('Error al cargar el stock existente', 'danger');
        }
    }
    
    /**
     * Actualizar stock en memoria
     */
    actualizarStock(categoria, cantidad) {
        // Si la cantidad es vacía, null, undefined o 0, eliminar la entrada
        if (!cantidad && cantidad !== 0) {
            delete this.stockData[categoria];
            return;
        }
        
        // Convertir a número
        const valorNumerico = parseInt(cantidad);
        if (!isNaN(valorNumerico)) {
            // Si es 0, eliminar la entrada
            if (valorNumerico === 0) {
                delete this.stockData[categoria];
            } else {
                this.stockData[categoria] = valorNumerico;
            }
        } else {
            delete this.stockData[categoria];
        }
    }
    
    /**
     * Limpiar categoría específica
     */
    limpiarCategoria(categoria) {
        console.log('DEBUG: Limpiando categoría:', categoria);
        const input = document.querySelector(`input[data-categoria="${categoria}"]`);
        if (input) {
            // Poner el valor explícitamente en 0
            input.value = '0';
            this.stockData[categoria] = 0; // Actualizar el stockData con 0
            console.log('DEBUG: Valor actualizado a 0 para categoría:', categoria);
            console.log('DEBUG: Estado actual de stockData:', this.stockData);
            this.actualizarContadores();
            this.habilitarBotonGuardar();
            
            // Forzar el cambio para que se detecte
            const event = new Event('input', {
                bubbles: true,
                cancelable: true,
            });
            input.dispatchEvent(event);
        } else {
            console.error('DEBUG: No se encontró el input para la categoría:', categoria);
        }
    }
    
    /**
     * Limpiar todo el rubro (con confirmación) - FUNCIÓN DESHABILITADA
     * Esta función se usaba con el botón "Limpiar Rubro" que fue removido
     */
    /*
    limpiarRubro() {
        if (confirm('¿Está seguro de que desea limpiar todas las cantidades del rubro actual?')) {
            this.limpiarRubroSinConfirmacion();
        }
    }
    */
    
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
        
        const totalElement = document.getElementById('total-categorias');
        if (totalElement) totalElement.textContent = totalCategorias;
        
        // Eliminar el contador de "completadas" - ya no es necesario
        const completadasElement = document.getElementById('categorias-completadas');
        if (completadasElement) {
            completadasElement.style.display = 'none';
        }
        
        // Ocultar el elemento padre del contador si existe
        const contadorCompletadas = document.querySelector('.contador-completadas');
        if (contadorCompletadas) {
            contadorCompletadas.style.display = 'none';
        }
    }
    
    /**
     * Habilitar botón guardar
     */
    habilitarBotonGuardar() {
        const boton = document.getElementById('btn-guardar-stock');
        if (!boton) return;
        
        // Verificar si hay al menos un input con valor (incluyendo 0)
        const inputs = document.querySelectorAll('.cantidad-input');
        let tieneAlgunValor = false;
        
        inputs.forEach(input => {
            const valor = input.value.trim();
            if (valor !== '' && !isNaN(valor)) {
                tieneAlgunValor = true;
            }
        });
        
        boton.disabled = !tieneAlgunValor;
        
        if (tieneAlgunValor) {
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
     * Guardar stock con confirmación
     */
    async guardarStock() {
        console.log('DEBUG: Iniciando guardarStock');
        
        // Verificar que hay un rubro seleccionado y al menos un input con valor
        if (!this.rubroActual) {
            this.mostrarAlerta('Debe seleccionar un rubro', 'warning');
            return;
        }
        
        // Verificar si hay al menos un input con valor (incluyendo 0)
        const inputs = document.querySelectorAll('.cantidad-input');
        let tieneAlgunValor = false;
        let resumenDatos = [];
        
        inputs.forEach(input => {
            const valor = input.value.trim();
            const categoria = input.getAttribute('data-categoria');
            
            if (categoria) {
                if (valor !== '' && !isNaN(valor)) {
                    // Tiene valor numérico válido
                    tieneAlgunValor = true;
                    this.actualizarStock(categoria, valor);
                    resumenDatos.push({categoria: categoria, cantidad: valor});
                } else {
                    // Campo vacío = eliminar el valor
                    this.actualizarStock(categoria, '');
                    // Solo incluir en el resumen si tenía un valor anterior
                    if (this.stockData[categoria] !== undefined) {
                        resumenDatos.push({categoria: categoria, cantidad: ''});
                    }
                }
            }
        });
        
        if (!tieneAlgunValor) {
            this.mostrarAlerta('Debe cargar al menos una cantidad', 'warning');
            return;
        }
        
        // Mostrar modal de confirmación
        this.mostrarModalConfirmacion(resumenDatos);
    }
    
    /**
     * Mostrar modal de confirmación con resumen de datos
     */
    mostrarModalConfirmacion(resumenDatos) {
        console.log('DEBUG: Iniciando mostrarModalConfirmacion');
        
        // Usar modal de Bootstrap para evitar conflictos
        this.crearModalBootstrap(resumenDatos);
    }
    
    /**
     * Crear modal usando Bootstrap para evitar conflictos con otros modales
     */
    crearModalBootstrap(resumenDatos) {
        // Eliminar modal anterior si existe
        const modalAnterior = document.getElementById('modalConfirmacionStock');
        if (modalAnterior) {
            modalAnterior.remove();
        }
        
        // Generar resumen
        let htmlResumen = '';
        resumenDatos.forEach(item => {
            if (item.cantidad !== '0') {
                htmlResumen += `
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">${item.categoria}:</span>
                        <strong>${item.cantidad}</strong>
                    </div>
                `;
            }
        });
        
        // Crear HTML del modal usando clases de Bootstrap
        const modalHTML = `
            <div class="modal fade" id="modalConfirmacionStock" tabindex="-1" aria-labelledby="modalConfirmacionStockLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header" style="background-color: #e1dfda;">
                            <h5 class="modal-title" id="modalConfirmacionStockLabel">
                                <i class="bi bi-box-seam me-2"></i>
                                Confirmar Carga de Stock
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info d-flex align-items-center mb-3">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>¿Está seguro que desea cargar el stock?</strong>
                            </div>
                            
                            <p class="text-muted mb-3">
                                Esta acción registrará los movimientos de stock en la base de datos y no se puede deshacer fácilmente.
                            </p>
                            
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="bi bi-tag me-1"></i>
                                        Rubro: ${this.rubroActual}
                                    </h6>
                                    <div class="mt-3">
                                        ${htmlResumen}
                                    </div>
                                    <hr>
                                    <small class="text-muted">Total de categorías: ${resumenDatos.length}</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-success" id="btnConfirmarStock">
                                <i class="bi bi-check-circle me-1"></i>
                                Confirmar Carga
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Insertar modal en el body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Obtener referencias
        const modalElement = document.getElementById('modalConfirmacionStock');
        const btnConfirmar = document.getElementById('btnConfirmarStock');
        
        // Configurar evento del botón confirmar
        btnConfirmar.addEventListener('click', () => {
            console.log('DEBUG: Confirmar carga clickeado');
            
            // Cerrar modal usando Bootstrap
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
            
            // Ejecutar guardado después de cerrar
            setTimeout(() => {
                this.ejecutarGuardadoStock();
            }, 200);
        });
        
        // Limpiar modal cuando se cierre
        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
        });
        
        // Mostrar modal usando Bootstrap
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        console.log('DEBUG: Modal Bootstrap creado y mostrado');
    }
    
    /**
     * Ejecutar el guardado real del stock
     */
    async ejecutarGuardadoStock() {
        console.log('DEBUG: Ejecutando guardado confirmado');
        
        // Obtener todas las categorías del formulario
        const inputs = document.querySelectorAll('.cantidad-input');
        const stockArray = [];
        
        inputs.forEach(input => {
            const categoria = input.getAttribute('data-categoria');
            if (categoria) {
                // Si el valor está vacío o no es número, usar 0
                const valor = input.value.trim();
                const cantidad = (valor !== '' && !isNaN(valor)) ? parseInt(valor) : 0;
                
                stockArray.push({
                    rubro: this.rubroActual,
                    categoria: categoria,
                    cantidad: cantidad // Siempre enviamos un número, 0 si está vacío
                });
            }
        });
        
        console.log('DEBUG: Datos a enviar:', stockArray);
        console.log('DEBUG: stockData completo:', this.stockData);
        console.log('DEBUG: Número de categorías a enviar:', stockArray.length);
        
        // TIMEOUT ABSOLUTO - garantizar que el modal se oculte después de 10 segundos máximo
        window.timeoutModal = setTimeout(() => {
            console.log('DEBUG: TIMEOUT ABSOLUTO - Forzando ocultación del modal');
            this.forzarOcultacionModal();
        }, 10000);
        
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
            console.log('🔥 ARCHIVO ACTUALIZADO - TIMESTAMP: 2025-09-03 14:40 🔥');
            
            // OCULTACIÓN SIMPLE Y DIRECTA DEL MODAL
            console.log('DEBUG: OCULTANDO MODAL DE FORMA SIMPLE');
            
            // Eliminar el modal completamente del DOM
            const modalElement = document.getElementById('modalLoading');
            if (modalElement) {
                console.log('DEBUG: Modal encontrado, eliminándolo del DOM');
                modalElement.remove();
            }
            
            // Eliminar cualquier backdrop
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            
            // Restaurar body
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
            document.body.style.overflow = '';
            
            console.log('DEBUG: Modal eliminado completamente');
            
            // Limpieza de timeouts si existen
            if (window.timeoutModal) {
                clearTimeout(window.timeoutModal);
                console.log('DEBUG: Timeout de modal limpiado');
            }
            
            if (window.timeoutModalEmergencia) {
                clearTimeout(window.timeoutModalEmergencia);
                console.log('DEBUG: Timeout de emergencia limpiado');
            }

            if (data.success) {
                console.log('DEBUG: Guardado exitoso');
                
                // DOBLE PROTECCIÓN - forzar ocultación del modal después de mostrar éxito
                setTimeout(async () => {
                    console.log('DEBUG: Protección adicional - forzando ocultación del modal');
                    this.forzarOcultacionModal();
                    
                    // Mostrar mensaje de éxito DESPUÉS de ocultar el modal
                    setTimeout(async () => {
                        console.log('DEBUG: Mostrando alerta de éxito');
                        this.mostrarAlerta(`Stock guardado exitosamente. Se registraron ${data.movimientos_registrados} movimientos en la base de datos.`, 'success');
                        
                        // Recargar datos del rubro actual
                        console.log('DEBUG: Recargando datos del rubro actual:', this.rubroActual);
                        if (this.rubroActual) {
                            await this.seleccionarRubro(this.rubroActual);
                        }
                        
                        // Actualizar el resumen de stock
                        await this.cargarResumenStock();
                        
                        // Verificar estado de carga
                        await this.verificarEstadoCarga();
                        
                        // Notificar al sistema global que el stock ha cambiado
                        if (typeof window.notificarCambioStock === 'function') {
                            console.log('DEBUG: Notificando cambio de stock a estado global...');
                            window.notificarCambioStock();
                        }
                    }, 200);
                }, 100);
                
                // NO ejecutar actualizaciones en segundo plano por ahora para evitar conflictos
                console.log('DEBUG: Saltando actualización en segundo plano para evitar conflictos');
                
            } else {
                console.error('DEBUG: Error en respuesta del servidor:', data.message);
                this.mostrarAlerta(data.message || 'Error al guardar el stock', 'danger');
            }
        } catch (error) {
            // ASEGURAR que el modal se oculte en caso de error
            console.error('DEBUG: Error en guardarStock:', error);
            
            this.ocultarLoading();
            this.forzarOcultacionModal();
            console.log('DEBUG: Modal ocultado por error');
            
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
                body: `accion=obtener_resumen_stock&periodo=${this.periodoActual}`
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
                        <span class="text-success fw-bold">Stock cargado para este período en al menos un rubro</span>
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
     * Actualizar estados en segundo plano sin bloquear la UI
     */
    async actualizarEstadosEnSegundoPlano() {
        console.log('DEBUG: Iniciando actualización de estados en segundo plano');
        
        // Usar setTimeout para asegurar que se ejecute de forma asíncrona
        setTimeout(async () => {
            try {
                // Ejecutar actualizaciones en paralelo con timeout
                const promesas = [
                    this.ejecutarConTimeout(this.verificarEstadoCarga.bind(this), 5000, 'verificarEstadoCarga'),
                    this.ejecutarConTimeout(this.cargarResumenStock.bind(this), 5000, 'cargarResumenStock')
                ];
                
                // Usar Promise.allSettled para que una falla no afecte a la otra
                const resultados = await Promise.allSettled(promesas);
                
                resultados.forEach((resultado, index) => {
                    const nombres = ['verificarEstadoCarga', 'cargarResumenStock'];
                    if (resultado.status === 'rejected') {
                        console.warn(`Error en ${nombres[index]}:`, resultado.reason);
                    } else {
                        console.log(`✓ ${nombres[index]} completado exitosamente`);
                    }
                });
                
                console.log('DEBUG: Actualización de estados completada');
            } catch (error) {
                console.error('Error en actualizarEstadosEnSegundoPlano:', error);
            }
        }, 100); // Ejecutar después de 100ms para no bloquear el hilo principal
    }
    
    /**
     * Ejecutar función con timeout
     */
    async ejecutarConTimeout(funcion, timeout, nombre) {
        return new Promise(async (resolve, reject) => {
            const timer = setTimeout(() => {
                reject(new Error(`Timeout: ${nombre} tardó más de ${timeout}ms`));
            }, timeout);
            
            try {
                const resultado = await funcion();
                clearTimeout(timer);
                resolve(resultado);
            } catch (error) {
                clearTimeout(timer);
                reject(error);
            }
        });
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
        let categoriasInventariadas = 0;
        
        stockData.forEach(item => {
            if (!stockPorRubro[item.rubro]) {
                stockPorRubro[item.rubro] = [];
            }
            stockPorRubro[item.rubro].push(item);
            totalItems++;
            const cantidad = parseInt(item.cantidad) || 0;
            totalStock += cantidad;
            if (cantidad > 0) {
                categoriasInventariadas++;
            }
        });
        
        let html = `
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body text-center py-2">
                            <h4 class="text-primary mb-1">${Object.keys(stockPorRubro).length}</h4>
                            <small class="text-muted">Rubros</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center py-2">
                            <h4 class="text-info mb-1">${totalItems}</h4>
                            <small class="text-muted">Categorías Totales</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body text-center py-2">
                            <h4 class="text-warning mb-1">${categoriasInventariadas}</h4>
                            <small class="text-muted">Categorías Inventariadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center py-2">
                            <h4 class="text-success mb-1">${totalStock.toLocaleString()}</h4>
                            <small class="text-muted">Total Stock</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <select class="form-select" id="filtro-rubro-resumen">
                        <option value="">Todos los rubros</option>
                        ${Object.keys(stockPorRubro).map(rubro => 
                            `<option value="${rubro}">${rubro}</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" id="busqueda-resumen" 
                           placeholder="Buscar por categoría..."
                           autocomplete="off">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover">
                    <thead class="table-secondary">
                        <tr>
                            <th>Rubro</th>
                            <th>Categoría</th>
                            <th class="text-end">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-resumen-stock">
        `;
        
        Object.entries(stockPorRubro).forEach(([rubro, items]) => {
            items.forEach(item => {
                const cantidad = parseInt(item.cantidad) || 0;
                html += `
                    <tr data-rubro="${item.rubro}" data-categoria="${item.categoria.toLowerCase()}" data-cantidad="${cantidad}">
                        <td><strong>${item.rubro}</strong></td>
                        <td>${item.categoria}</td>
                        <td class="text-end">${cantidad.toLocaleString()}</td>
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

        // Configurar filtros
        const busquedaInput = document.getElementById('busqueda-resumen');
        const filtroRubro = document.getElementById('filtro-rubro-resumen');
        const tablaResumen = document.getElementById('tabla-resumen-stock');

        if (busquedaInput && filtroRubro && tablaResumen) {
            const actualizarTotales = () => {
                let totalFiltrado = 0;
                let stockFiltrado = 0;
                let categoriasInventariadasFiltradas = 0;
                const filas = tablaResumen.getElementsByTagName('tr');

                Array.from(filas).forEach(fila => {
                    if (fila.style.display !== 'none') {
                        totalFiltrado++;
                        const cantidad = parseInt(fila.getAttribute('data-cantidad') || '0');
                        stockFiltrado += cantidad;
                        if (cantidad > 0) {
                            categoriasInventariadasFiltradas++;
                        }
                    }
                });

                // Actualizar las cards con los totales filtrados
                const cards = document.querySelectorAll('.card');
                cards.forEach(card => {
                    const h4 = card.querySelector('h4');
                    if (h4) {
                        if (h4.classList.contains('text-info')) {
                            // Total de categorías
                            h4.textContent = totalFiltrado;
                        } else if (h4.classList.contains('text-warning')) {
                            // Categorías inventariadas
                            h4.textContent = categoriasInventariadasFiltradas;
                        } else if (h4.classList.contains('text-success')) {
                            // Total stock
                            h4.textContent = stockFiltrado.toLocaleString();
                        }
                    }
                });
            };

            const filtrarTabla = () => {
                const textoBusqueda = busquedaInput.value.toLowerCase();
                const rubroSeleccionado = filtroRubro.value;
                const filas = tablaResumen.getElementsByTagName('tr');

                Array.from(filas).forEach(fila => {
                    const rubro = fila.getAttribute('data-rubro');
                    const categoria = fila.getAttribute('data-categoria');
                    
                    if (rubro && categoria) {
                        const coincideRubro = !rubroSeleccionado || rubro === rubroSeleccionado;
                        const coincideTexto = !textoBusqueda || 
                                            categoria.includes(textoBusqueda) || 
                                            rubro.toLowerCase().includes(textoBusqueda);

                        fila.style.display = coincideRubro && coincideTexto ? '' : 'none';
                    }
                });

                actualizarTotales();
            };

            busquedaInput.addEventListener('input', filtrarTabla);
            filtroRubro.addEventListener('change', filtrarTabla);
        }
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
            
            // TIMEOUT DE EMERGENCIA - AUTO-HIDE después de 15 segundos
            window.timeoutModalEmergencia = setTimeout(() => {
                console.log('DEBUG: TIMEOUT DE EMERGENCIA - Eliminando modal por fuerza después de 15s');
                if (modalElement) {
                    modalElement.remove();
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.paddingRight = '';
                    document.body.style.overflow = '';
                }
            }, 15000);
        }
    }
    
    /**
     * Ocultar loading
     */
    ocultarLoading() {
        console.log('DEBUG: Intentando ocultar modal de loading...');
        
        const modalElement = document.getElementById('modalLoading');
        if (modalElement) {
            console.log('DEBUG: Elemento modal encontrado');
            
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                console.log('DEBUG: Instancia de modal encontrada, ocultando...');
                modal.hide();
            } else {
                console.log('DEBUG: No hay instancia de modal, forzando ocultación...');
                // Si no hay instancia, forzar ocultación
                modalElement.style.display = 'none';
                modalElement.classList.remove('show');
                modalElement.classList.remove('fade');
                modalElement.setAttribute('aria-hidden', 'true');
                modalElement.removeAttribute('aria-modal');
                modalElement.removeAttribute('role');
                
                // Remover backdrop si existe
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    console.log('DEBUG: Removiendo backdrop...');
                    backdrop.remove();
                }
                
                // Restaurar scroll del body
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                document.body.style.overflow = '';
            }
            
            // Doble verificación - forzar ocultación después de 100ms
            setTimeout(() => {
                if (modalElement.classList.contains('show') || modalElement.style.display !== 'none') {
                    console.log('DEBUG: Modal aún visible, forzando ocultación final...');
                    modalElement.style.display = 'none';
                    modalElement.classList.remove('show', 'fade');
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                }
            }, 100);
            
        } else {
            console.log('DEBUG: Elemento modal no encontrado');
        }
        
        console.log('DEBUG: Función ocultarLoading completada');
    }
    
    /**
     * Forzar ocultación del modal sin usar Bootstrap
     */
    forzarOcultacionModal() {
        console.log('DEBUG: FORZANDO ocultación del modal de loading...');
        
        const modalElement = document.getElementById('modalLoading');
        if (modalElement) {
            // Forzar estilos CSS directamente
            modalElement.style.display = 'none';
            modalElement.style.opacity = '0';
            modalElement.style.visibility = 'hidden';
            modalElement.classList.remove('show', 'fade', 'd-block');
            modalElement.setAttribute('aria-hidden', 'true');
            modalElement.removeAttribute('aria-modal');
            modalElement.removeAttribute('role');
        }
        
        // Remover todos los backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Restaurar body completamente
        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
        document.body.style.overflow = '';
        document.body.style.paddingLeft = '';
        document.body.style.marginRight = '';
        
        console.log('DEBUG: Modal forzadamente ocultado');
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
            // Guardar los datos en sessionStorage
            sessionStorage.setItem('historial_stock', JSON.stringify(data));
            
            // Redirigir a vista de historial con un parámetro timestamp
            // para asegurar que se recarga la página
            window.location.href = `?vista=historial&t=${Date.now()}`;
        } else {
            franquiciaStock.mostrarAlerta(data.message || 'Error al cargar el historial', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        franquiciaStock.mostrarAlerta('Error de conexión', 'danger');
    }
}

/**
 * Inicializar vista de historial
 */
function inicializarHistorial() {
    try {
        // Recuperar datos del historial
        const historialData = sessionStorage.getItem('historial_stock');
        if (!historialData) {
            console.warn('No se encontraron datos del historial');
            return;
        }

        const data = JSON.parse(historialData);
        if (!data.historial || !Array.isArray(data.historial)) {
            console.warn('Datos de historial inválidos');
            return;
        }

        // Generar conjunto único de períodos y rubros
        const periodos = [...new Set(data.historial.map(item => item.periodo))].sort().reverse();
        const rubros = [...new Set(data.historial.map(item => item.rubro))].sort();

        // Llenar selectores de filtro
        const selectorPeriodo = document.getElementById('filtro-periodo');
        const selectorRubro = document.getElementById('filtro-rubro');

        if (selectorPeriodo) {
            selectorPeriodo.innerHTML = `
                <option value="">Todos los períodos</option>
                ${periodos.map(periodo => `<option value="${periodo}">${periodo}</option>`).join('')}
            `;
        }

        if (selectorRubro) {
            selectorRubro.innerHTML = `
                <option value="">Todos los rubros</option>
                ${rubros.map(rubro => `<option value="${rubro}">${rubro}</option>`).join('')}
            `;
        }

        // Configurar eventos de filtro
        const filtrarHistorial = () => {
            const periodoSeleccionado = selectorPeriodo ? selectorPeriodo.value : '';
            const rubroSeleccionado = selectorRubro ? selectorRubro.value : '';
            const busqueda = document.getElementById('busqueda-historial')?.value.toLowerCase() || '';

            const tbody = document.getElementById('tabla-historial-stock');
            if (!tbody) return;

            let totalStock = 0;
            let totalCategorias = 0;
            let categoriasInventariadas = 0;

            // Filtrar y mostrar datos
            const htmlFilas = data.historial
                .filter(item => {
                    const coincidePeriodo = !periodoSeleccionado || item.periodo === periodoSeleccionado;
                    const coincideRubro = !rubroSeleccionado || item.rubro === rubroSeleccionado;
                    const coincideBusqueda = !busqueda || 
                                           item.categoria.toLowerCase().includes(busqueda) || 
                                           item.rubro.toLowerCase().includes(busqueda);
                    
                    const pasa = coincidePeriodo && coincideRubro && coincideBusqueda;
                    if (pasa) {
                        totalStock += parseInt(item.cantidad) || 0;
                        totalCategorias++;
                        if ((parseInt(item.cantidad) || 0) > 0) {
                            categoriasInventariadas++;
                        }
                    }
                    return pasa;
                })
                .map(item => `
                    <tr>
                        <td>${item.periodo}</td>
                        <td><strong>${item.rubro}</strong></td>
                        <td>${item.categoria}</td>
                        <td class="text-end">${parseInt(item.cantidad).toLocaleString()}</td>
                    </tr>
                `)
                .join('');

            tbody.innerHTML = htmlFilas;

            // Actualizar contadores
            document.querySelectorAll('.card-body h4').forEach(card => {
                if (card.classList.contains('text-info')) {
                    card.textContent = totalCategorias;
                } else if (card.classList.contains('text-warning')) {
                    card.textContent = categoriasInventariadas;
                } else if (card.classList.contains('text-success')) {
                    card.textContent = totalStock.toLocaleString();
                }
            });
        };

        // Configurar eventos
        if (selectorPeriodo) selectorPeriodo.addEventListener('change', filtrarHistorial);
        if (selectorRubro) selectorRubro.addEventListener('change', filtrarHistorial);
        const busquedaInput = document.getElementById('busqueda-historial');
        if (busquedaInput) busquedaInput.addEventListener('input', filtrarHistorial);

        // Filtrar inicialmente
        filtrarHistorial();

    } catch (error) {
        console.error('Error al inicializar historial:', error);
        franquiciaStock.mostrarAlerta('Error al cargar el historial', 'danger');
    }
}