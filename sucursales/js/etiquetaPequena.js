
/**
 * Script para el generador de etiquetas pequeñas
 */
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const smallTagForm = document.getElementById('smallTagForm');
    const smallTagCode = document.getElementById('smallTagCode');
    const smallTagsContainer = document.getElementById('smallTagsContainer');
    const printSmallTagsBtn = document.getElementById('printSmallTagsBtn');
    const clearSmallTagsBtn = document.getElementById('clearSmallTagsBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    // Arreglo para almacenar los códigos de etiquetas añadidos
    let addedCodes = [];
    // Contador para IDs únicos de alertas
    let alertCounter = 0;
    
    /**
     * Muestra el indicador de carga
     */
    function showLoading() {
        loadingOverlay.classList.add('active');
    }
    
    /**
     * Oculta el indicador de carga
     */
    function hideLoading() {
        loadingOverlay.classList.remove('active');
    }
    
    /**
     * Muestra una alerta moderna
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo de alerta ('success', 'error', 'warning')
     * @param {string} title - Título de la alerta
     */
    function showAlert(message, type = 'success', title = '') {
        // Asignar títulos predeterminados según el tipo si no se proporciona uno
        if (!title) {
            switch(type) {
                case 'error':
                    title = 'Error';
                    break;
                case 'warning':
                    title = 'Advertencia';
                    break;
                case 'success':
                    title = 'Éxito';
                    break;
                default:
                    title = 'Información';
            }
        }
        
        // Iconos para cada tipo de alerta
        const icons = {
            error: 'fa-exclamation-circle',
            success: 'fa-check-circle',
            warning: 'fa-exclamation-triangle'
        };
        
        // Crear ID único para esta alerta
        const alertId = `alert-${++alertCounter}`;
        
        // Crear el elemento de alerta
        const alertElement = document.createElement('div');
        alertElement.id = alertId;
        alertElement.className = `small-tag-alert ${type}`;
        alertElement.innerHTML = `
            <div class="alert-content">
                <div class="alert-icon">
                    <i class="fas ${icons[type] || 'fa-info-circle'}"></i>
                </div>
                <div class="alert-text">
                    <div class="alert-title">${title}</div>
                    <div class="alert-message">${message}</div>
                </div>
                <button type="button" class="alert-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="alert-progress">
                <div class="alert-progress-bar"></div>
            </div>
        `;
        
        // Añadir al cuerpo del documento
        document.body.appendChild(alertElement);
        
        // Configurar el cierre de la alerta
        const closeButton = alertElement.querySelector('.alert-close');
        closeButton.addEventListener('click', function() {
            closeAlert(alertId);
        });
        
        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            closeAlert(alertId);
        }, 5000);
        
        return alertId;
    }
    
    /**
     * Cierra una alerta específica
     * @param {string} alertId - ID de la alerta a cerrar
     */
    function closeAlert(alertId) {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.classList.add('closing');
            setTimeout(() => {
                if (alertElement.parentNode) {
                    alertElement.parentNode.removeChild(alertElement);
                }
            }, 300);
        }
    }
    
    /**
     * Formatea un número como moneda en pesos argentinos
     * @param {number} value - Valor a formatear
     * @param {number} decimals - Cantidad de decimales
     * @returns {string} Valor formateado
     */
    function formatCurrency(value, decimals = 2) {
        return parseFloat(value).toLocaleString('es-AR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }
    /**
     * Busca y añade una etiqueta pequeña para el código de artículo proporcionado
     */
    function buscarYAgregarEtiqueta() {
        const codigo = smallTagCode.value.trim();
        
        if (!codigo) {
            showAlert('Por favor, ingrese un código de artículo.', 'warning');
            return;
        }
        
        // Verificar si el código ya fue añadido
        if (addedCodes.includes(codigo)) {
            showAlert('Este código ya ha sido añadido.', 'warning');
            smallTagCode.value = '';
            smallTagCode.focus();
            return;
        }
        
        showLoading();
        
        // Enviar solicitud al servidor
        fetch('Controller/obtener_precios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ codigos: [codigo] })
        })
        .then(response => response.json())
        .then(data => {
            // Verificar si hay códigos inválidos
            if (data.invalidCodes && data.invalidCodes.length > 0) {
                showAlert(`El código "${codigo}" no existe o no está disponible.`, 'error', 'Código incorrecto');
                hideLoading();
                return;
            }
            
            const products = data.products || data;
            
            // Verificar si se encontró el producto
            if (products.length === 0) {
                showAlert('No se encontró ningún producto con ese código.', 'error', 'Código no encontrado');
                hideLoading();
                return;
            }
            
            const producto = products[0];
            if (!producto) {
                showAlert('No se encontró ningún producto con ese código.', 'error', 'Código no encontrado');
                hideLoading();
                return;
            }
            
            // Añadir el código a la lista de códigos añadidos
            addedCodes.push(codigo);
            
            // Crear la etiqueta pequeña
            const smallTag = document.createElement('div');
            smallTag.className = 'small-tag';
            smallTag.dataset.codigo = codigo;
            
            // Obtener datos del producto
            const nombreProducto = producto.PRODUCTO || '';
            const precioSinIva = formatCurrency(producto.PRECIO_S_IVA);
            const precioFinal = formatCurrency(producto.PRECIO_FINAL, 0);
            
            const precioSinIvaHtml = (typeof USR_USHUAIA !== 'undefined' && USR_USHUAIA)
                ? `<div class="price-without-tax">
                    <span class="amount">$${precioSinIva}</span>
                    <span class="text">Precio sin Impuestos<br> Nacionales</span>
                </div>`
                : '';

            // Añadir contenido a la etiqueta
            smallTag.innerHTML = `
                <div class="small-tag-header no-print">
                    <span class="small-tag-code">${codigo}</span>
                    <button type="button" class="remove-tag" title="Eliminar etiqueta">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="final-price">$${precioFinal}</div>
                ${precioSinIvaHtml}
            `;
            
            // Añadir la etiqueta al contenedor
            smallTagsContainer.appendChild(smallTag);
            
            // Limpiar el campo de entrada y enfocar para el siguiente
            smallTagCode.value = '';
            smallTagCode.focus();
            
            // Configurar botón de eliminar
            const removeButton = smallTag.querySelector('.remove-tag');
            removeButton.addEventListener('click', function() {
                const codigo = smallTag.dataset.codigo;
                const index = addedCodes.indexOf(codigo);
                if (index !== -1) {
                    addedCodes.splice(index, 1);
                }
                smallTag.remove();
                
                // Actualizar estado del botón de imprimir
                updatePrintButtonState();
                
                // Mostrar alerta
                showAlert('Etiqueta eliminada correctamente.', 'success');
            });
            
            // Habilitar botón de imprimir si hay etiquetas
            updatePrintButtonState();
            
            // Mostrar alerta de éxito
            showAlert('Etiqueta añadida correctamente.', 'success');
            
            hideLoading();
        })
        .catch(error => {
            console.error('Error al buscar el artículo:', error);
            showAlert(`Error al buscar el artículo: ${error.message}`, 'error', 'Error de sistema');
            hideLoading();
        });
    }
    
    /**
     * Actualiza el estado del botón de imprimir
     */
    function updatePrintButtonState() {
        printSmallTagsBtn.disabled = smallTagsContainer.children.length === 0;
    }
    
    // Evento para el formulario de etiquetas pequeñas
    smallTagForm.addEventListener('submit', function(e) {
        e.preventDefault();
        buscarYAgregarEtiqueta();
    });
    
    // Evento para Enter en el campo de código
    smallTagCode.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarYAgregarEtiqueta();
        }
    });
    
    // Evento para imprimir etiquetas pequeñas
    printSmallTagsBtn.addEventListener('click', function() {
        if (smallTagsContainer.children.length === 0) {
            showAlert('No hay etiquetas para imprimir.', 'warning');
            return;
        }
        
        setTimeout(function() {
            window.print();
        }, 100);
    });
    
    // Evento para limpiar todas las etiquetas
    clearSmallTagsBtn.addEventListener('click', function() {
        if (smallTagsContainer.children.length === 0) {
            showAlert('No hay etiquetas para limpiar.', 'info');
            return;
        }
        
        if (confirm('¿Está seguro de que desea eliminar todas las etiquetas?')) {
            smallTagsContainer.innerHTML = '';
            addedCodes = [];
            updatePrintButtonState();
            showAlert('Todas las etiquetas han sido eliminadas.', 'success');
        }
    });
    
    // Inicializar estado del botón de imprimir
    updatePrintButtonState();
});