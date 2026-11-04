
/**
 * Stock Depósitos - JavaScript Module
 * Funcionalidad para la consulta de stock de artículos
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeStockApp();
});

/**
 * Inicializa la aplicación de stock
 */
function initializeStockApp() {
    setupFormValidation();
    setupLoadingIndicators();
    setupKeyboardShortcuts();
    setupTableEnhancements();
    setupAutoComplete();
    
    // Enfocar el campo de código al cargar
    const codigoInput = document.getElementById('codArticu');
    if (codigoInput) {
        codigoInput.focus();
    }
}

/**
 * Configura la validación del formulario
 */
function setupFormValidation() {
    const form = document.getElementById('stockForm');
    if (!form) return;

    form.addEventListener('submit', function(event) {
        const codigoInput = document.getElementById('codArticu');
        const codigo = codigoInput.value.trim();
        
        // Validar que el código no esté vacío
        if (!codigo) {
            event.preventDefault();
            event.stopPropagation();
            
            codigoInput.classList.add('is-invalid');
            codigoInput.focus();
            
            showNotification('Por favor ingrese un código de artículo', 'warning');
            return false;
        }
        
        // Validar formato del código
        if (!validateCodigoArticulo(codigo)) {
            event.preventDefault();
            event.stopPropagation();
            
            codigoInput.classList.add('is-invalid');
            codigoInput.focus();
            
            showNotification('El código debe tener entre 3 y 20 caracteres alfanuméricos', 'warning');
            return false;
        }
        
        // Si llegamos aquí, el formulario es válido
        codigoInput.classList.remove('is-invalid');
        codigoInput.classList.add('is-valid');
        
        // Mostrar indicador de carga
        showLoadingIndicator();
    });

    // Limpiar validación al escribir
    const codigoInput = document.getElementById('codArticu');
    if (codigoInput) {
        codigoInput.addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
        });
    }
}

/**
 * Configura los indicadores de carga
 */
function setupLoadingIndicators() {
    const buttons = document.querySelectorAll('.btn-filter');
    
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const codigo = document.getElementById('codArticu').value.trim();
            if (codigo) {
                showLoadingIndicator();
            }
        });
    });
}

/**
 * Configura atajos de teclado
 */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(event) {
        // Ctrl/Cmd + K para enfocar búsqueda
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            event.preventDefault();
            const codigoInput = document.getElementById('codArticu');
            if (codigoInput) {
                codigoInput.focus();
                codigoInput.select();
            }
        }
        
        // Enter en el campo de búsqueda
        if (event.target.id === 'codArticu' && event.key === 'Enter') {
            const form = document.getElementById('stockForm');
            if (form) {
                // Simular click en el botón "Todos" por defecto
                const todoButton = document.querySelector('[value="Todos"]');
                if (todoButton) {
                    todoButton.click();
                }
            }
        }
        
        // Escape para limpiar formulario
        if (event.key === 'Escape') {
            limpiarFormulario();
        }
    });
}

/**
 * Configura mejoras para la tabla
 */
function setupTableEnhancements() {
    const table = document.getElementById('stockTable');
    if (!table) return;
    
    // Agregar funcionalidad de ordenamiento (simple)
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        if (index < 7) { // Solo columnas numéricas
            header.style.cursor = 'pointer';
            header.setAttribute('title', 'Click para ordenar');
            
            header.addEventListener('click', function() {
                sortTable(table, index);
            });
        }
    });
    
    // Resaltar filas sin stock disponible
    highlightLowStock();
}

/**
 * Filtra las filas de la tabla por tipo de depósito
 */
function filtrarDeposito(tipo, btn) {
    // Actualizar botones activos
    document.querySelectorAll('#filtroDeposito .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const rows = document.querySelectorAll('#stockTable tbody tr');
    let visibles = 0;

    rows.forEach(row => {
        const tipoFila = row.getAttribute('data-tipo-deposito');
        const mostrar = tipo === 'todos' || tipoFila === tipo;
        row.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });

    // Actualizar contador
    const contador = document.getElementById('contadorRegistros');
    if (contador) contador.textContent = visibles + ' registros';
}

/**
 * Configura autocompletado (placeholder para futura implementación)
 */
function setupAutoComplete() {
    const codigoInput = document.getElementById('codArticu');
    if (!codigoInput) return;
    
    // Placeholder para implementar autocompletado con AJAX
    codigoInput.addEventListener('input', debounce(function() {
        const query = this.value.trim();
        if (query.length >= 3) {
            // Aquí se podría implementar búsqueda de sugerencias
            console.log('Buscando sugerencias para:', query);
        }
    }, 300));
}

/**
 * Muestra el indicador de carga
 */
function showLoadingIndicator() {
    const indicator = document.getElementById('loadingIndicator');
    if (indicator) {
        indicator.style.display = 'flex';
        
        // Ocultar después de 10 segundos como fallback
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 10000);
    }
}

/**
 * Oculta el indicador de carga
 */
function hideLoadingIndicator() {
    const indicator = document.getElementById('loadingIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
}

/**
 * Limpia el formulario
 */
function limpiarFormulario() {
    const codigoInput = document.getElementById('codArticu');
    if (codigoInput) {
        codigoInput.value = '';
        codigoInput.classList.remove('is-invalid', 'is-valid');
        codigoInput.focus();
    }
    
    // Limpiar botones activos
    const activeButtons = document.querySelectorAll('.btn-filter.active');
    activeButtons.forEach(btn => btn.classList.remove('active'));
    
    showNotification('Formulario limpiado', 'info');
}

/**
 * Valida el formato del código de artículo
 */
function validateCodigoArticulo(codigo) {
    // Código debe tener entre 3 y 20 caracteres alfanuméricos
    const regex = /^[A-Za-z0-9\-_]{3,20}$/;
    return regex.test(codigo);
}

/**
 * Ordena la tabla por columna
 */
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Determinar tipo de ordenamiento
    const isNumeric = columnIndex >= 4 && columnIndex <= 7;
    
    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent.trim();
        const bVal = b.cells[columnIndex].textContent.trim();
        
        if (isNumeric) {
            const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, '')) || 0;
            const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, '')) || 0;
            return bNum - aNum; // Descendente para números
        } else {
            return aVal.localeCompare(bVal); // Ascendente para texto
        }
    });
    
    // Reorganizar filas
    rows.forEach(row => tbody.appendChild(row));
    
    showNotification(`Tabla ordenada por ${table.querySelectorAll('th')[columnIndex].textContent}`, 'info');
}

/**
 * Resalta filas con stock bajo
 */
function highlightLowStock() {
    const table = document.getElementById('stockTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const disponibleCell = row.cells[6]; // Columna "Disponible"
        if (disponibleCell) {
            const disponible = parseInt(disponibleCell.textContent.replace(/[^0-9]/g, '')) || 0;
            if (disponible === 0) {
                row.classList.add('table-danger');
            } else if (disponible < 5) {
                row.classList.add('table-warning');
            }
        }
    });
}

/**
 * Exporta los datos a CSV
 */
function exportarExcel() {
    const table = document.getElementById('stockTable');
    if (!table) {
        showNotification('No hay datos para exportar', 'warning');
        return;
    }

    // Construir filas XML a partir de la tabla
    const rows = table.querySelectorAll('tr');
    let xmlRows = '';

    rows.forEach((row, rowIndex) => {
        const cells = row.querySelectorAll('th, td');
        let xmlCells = '';

        cells.forEach(cell => {
            const text = cell.textContent.trim().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const isHeader = cell.tagName === 'TH';
            const styleAttr = isHeader ? ' ss:StyleID="Header"' : '';
            // Detectar si el valor es numérico puro (sin $ ni separadores de miles)
            const numVal = text.replace(/[\s,]/g, '');
            const isNumber = numVal !== '' && !isNaN(numVal) && !/[^0-9.\-]/.test(numVal);
            if (isNumber && !isHeader) {
                xmlCells += `<Cell${styleAttr}><Data ss:Type="Number">${numVal}</Data></Cell>`;
            } else {
                xmlCells += `<Cell${styleAttr}><Data ss:Type="String">${text}</Data></Cell>`;
            }
        });

        xmlRows += `<Row>${xmlCells}</Row>\n`;
    });

    const xml = `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:x="urn:schemas-microsoft-com:office:excel">
  <Styles>
    <Style ss:ID="Header">
      <Font ss:Color="#FFFFFF" ss:Bold="1"/>
      <Interior ss:Color="#4472C4" ss:Pattern="Solid"/>
    </Style>
  </Styles>
  <Worksheet ss:Name="Stock">
    <Table>
${xmlRows}
    </Table>
  </Worksheet>
</Workbook>`;

    const blob = new Blob([xml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `stock_${new Date().toISOString().split('T')[0]}.xls`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    showNotification('Archivo Excel descargado', 'success');
}

/**
 * Muestra una notificación
 */
function showNotification(message, type = 'info') {
    // Remover notificaciones existentes
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(n => n.remove());
    
    // Crear nueva notificación
    const notification = document.createElement('div');
    notification.className = `notification-toast alert alert-${type} alert-dismissible fade show`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    
    notification.innerHTML = `
        ${getNotificationIcon(type)}
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-ocultar después de 3 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 150);
        }
    }, 3000);
}

/**
 * Retorna el ícono apropiado para la notificación
 */
function getNotificationIcon(type) {
    const icons = {
        success: '<i class="fas fa-check-circle me-2"></i>',
        warning: '<i class="fas fa-exclamation-triangle me-2"></i>',
        danger: '<i class="fas fa-times-circle me-2"></i>',
        info: '<i class="fas fa-info-circle me-2"></i>'
    };
    return icons[type] || icons.info;
}

/**
 * Función debounce para optimizar eventos
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Formatea números con separadores de miles
 */
function formatNumber(num) {
    return new Intl.NumberFormat('es-AR').format(num);
}

/**
 * Formatea precios en moneda local
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
    }).format(amount);
}

// Agregar estilos para las notificaciones
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .notification-toast {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
        border-radius: 8px;
    }
    
    .table-warning {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }
    
    .table-danger {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }
`;
document.head.appendChild(notificationStyles);

// Ocultar indicador de carga cuando la página termine de cargar
window.addEventListener('load', function() {
    hideLoadingIndicator();
});