
/**
 * Script para el generador de etiquetas de precios
 */
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const generarBtn = document.getElementById('generarBtn');
    const imprimirBtn = document.getElementById('imprimirBtn');
    const priceTagContainer = document.getElementById('priceTagContainer');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const priceForm = document.getElementById('priceForm');
    
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
     * Muestra un mensaje de estado al usuario
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo de mensaje ('success', 'error', 'warning')
     */
    function showMessage(message, type = 'info') {
        // Crear o reutilizar el contenedor de mensajes
        let messageContainer = document.getElementById('messageContainer');
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.id = 'messageContainer';
            messageContainer.className = 'message-container';
            priceForm.parentNode.insertBefore(messageContainer, priceForm.nextSibling);
        }
        
        // Crear el mensaje
        const messageElement = document.createElement('div');
        messageElement.className = `message message-${type}`;
        messageElement.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button type="button" class="close-message"><i class="fas fa-times"></i></button>
        `;
        
        // Añadir el mensaje al contenedor
        messageContainer.innerHTML = '';
        messageContainer.appendChild(messageElement);
        
        // Configurar el botón de cierre
        const closeButton = messageElement.querySelector('.close-message');
        closeButton.addEventListener('click', function() {
            messageElement.remove();
        });
        
        // Auto-cerrar después de 5 segundos para mensajes de éxito
        if (type === 'success') {
            setTimeout(() => {
                if (messageElement.parentNode) {
                    messageElement.remove();
                }
            }, 5000);
        }
    }
    
    /**
     * Genera la etiqueta de precios con los datos obtenidos
     */
    function generarEtiqueta() {
        showLoading();
        
        // Obtener todos los códigos de artículos
        const codigos = [];
        for (let i = 1; i <= 7; i++) {
            const codigo = document.getElementById(`codigo${i}`).value.trim();
            if (codigo) {
                codigos.push(codigo);
            }
        }
        
        // Mostrar en consola para depuración
        console.log("Códigos enviados:", codigos);
        
        // Verificar si hay al menos un código
        if (codigos.length === 0) {
            hideLoading();
            showMessage('Por favor, ingrese al menos un código de artículo.', 'error');
            return;
        }
        
        // Enviar solicitud al servidor
        fetch('Controller/obtener_precios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ codigos: codigos })
        })
        .then(response => {
                if (response.ok) {
                    return response.text().then(text => {
                        // Verificar si la respuesta contiene HTML (posible error de PHP)
                        if (text.includes('<br />') || text.includes('<b>Error') || text.includes('<b>Fatal error')) {
                            console.error('Respuesta no válida:', text);
                            throw new Error('Error en el servidor PHP. Consulte la consola para más detalles.');
                        }
                        
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Respuesta no válida:', text);
                            throw new Error('La respuesta del servidor no es JSON válido');
                        }
                    });
                } else {
                    throw new Error(`Error en la solicitud: ${response.status}`);
                }
        })
        .then(data => {
            // Limpiar el contenedor
            priceTagContainer.innerHTML = '';
            
            // Verificar el estado de la respuesta
            if (data.invalidCodes && data.invalidCodes.length > 0) {
                const invalidCodesMessage = `Código${data.invalidCodes.length > 1 ? 's' : ''} incorrecto${data.invalidCodes.length > 1 ? 's' : ''}: ${data.invalidCodes.join(', ')}`;
                showMessage(invalidCodesMessage, 'error');
                hideLoading();
                return;
            }
            
            if (data.products && data.products.length === 0) {
                showMessage('No se encontraron productos con los códigos proporcionados.', 'warning');
                hideLoading();
                return;
            }

            // Mostrar mensaje de éxito con el recuento de códigos correctos
            const products = data.products || data;
            showMessage(`Se ${products.length === 1 ? 'ha generado' : 'han generado'} ${products.length} etiqueta${products.length === 1 ? '' : 's'} correctamente.`, 'success');
            
            // Generar la etiqueta de precios
            const priceTag = document.createElement('div');
            priceTag.className = 'price-tag';
            
            // Contenedor para productos con flex-grow para ajuste dinámico
            const productsContainer = document.createElement('div');
            productsContainer.className = 'price-tag-products';
            priceTag.appendChild(productsContainer);
            
            // Agregar los productos
            products.forEach((producto) => {
                if (producto) {
                    // Obtener datos del producto
                    const nombreProducto = producto.PRODUCTO || '';
                    const precioSinIva = formatCurrency(producto.PRECIO_S_IVA);
                    const precioFinal = formatCurrency(producto.PRECIO_FINAL, 0);
                    
                    // Crear el elemento del producto
                    const productoDiv = document.createElement('div');
                    productoDiv.className = 'price-item';
                    productoDiv.innerHTML = `
                        <div class="product-name">
                            <input type="text" value="${nombreProducto}" class="product-name-input">
                        </div>
                        <div class="price-without-tax">Precio sin Impuestos Nacionales $${precioSinIva}</div>
                        <div class="final-price">$${precioFinal}</div>
                    `;
                    productsContainer.appendChild(productoDiv);
                }
            });
            
            // Agregar logo XL en el pie de página
            const brandFooter = document.createElement('div');
            brandFooter.className = 'brand-footer';
            brandFooter.innerHTML = '<img src="../image/logo.jpg" alt="Logo XL">';
            priceTag.appendChild(brandFooter);
            
            // Añadir al contenedor
            priceTagContainer.appendChild(priceTag);
            
            // Habilitar el botón de imprimir
            imprimirBtn.disabled = false;
            
            // Ocultar el indicador de carga
            hideLoading();
        })
        .catch(error => {
            console.error('Error al generar la etiqueta:', error);
            showMessage(`Error al generar la etiqueta: ${error.message}`, 'error');
            hideLoading();
        });
    }
    
    // Evento para generar la etiqueta
    generarBtn.addEventListener('click', generarEtiqueta);
    
    // Evento para imprimir
    imprimirBtn.addEventListener('click', function() {
        if (priceTagContainer.innerHTML.trim() === '') {
            showMessage('Primero debe generar una etiqueta para imprimir.', 'warning');
            return;
        }
        
        // Pequeña pausa para asegurar que todo esté renderizado correctamente
        setTimeout(function() {
            window.print();
        }, 100);
    });
    
    // Desactivar el botón de imprimir al inicio
    imprimirBtn.disabled = true;
    
    // Enviar formulario con Enter
    priceForm.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            generarBtn.click();
        }
    });
    
    // Evento para limpiar
    const limpiarBtn = document.getElementById('limpiarBtn');
    limpiarBtn.addEventListener('click', function() {
        // Limpiar el contenedor de etiquetas
        priceTagContainer.innerHTML = '';
        
        // Desactivar el botón de imprimir
        imprimirBtn.disabled = true;
        
        // Limpiar cualquier mensaje
        const messageContainer = document.getElementById('messageContainer');
        if (messageContainer) {
            messageContainer.innerHTML = '';
        }
    });
});