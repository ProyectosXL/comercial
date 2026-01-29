/* ========================================
   JavaScript específico para index.php
   Gestión de Pedidos - XL Extra
   ======================================== */

/**
 * Inicialización cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar funcionalidades
    initTotales();
    initBusquedaRapida();
    initEdicionTracking();
    initModalCliente();
    checkEditedRow();
});

/* ========================================
   Cálculo de totales
   ======================================== */

/**
 * Inicializa el cálculo de totales de la tabla
 */
function initTotales() {
    calcularTotalArticulos();
    calcularTotalClientes();
    calcularTotalImporte();
}

/**
 * Calcula el total de artículos pedidos
 */
function calcularTotalArticulos() {
    const celdas = document.querySelectorAll('td.sumTotal');
    let total = 0;
    
    celdas.forEach(celda => {
        const valor = parseFloat(celda.textContent) || 0;
        total += valor;
    });
    
    const elementoTotal = document.getElementById('total');
    if (elementoTotal) {
        elementoTotal.textContent = new Intl.NumberFormat('de-DE').format(total);
    }
}

/**
 * Calcula el total de clientes
 */
function calcularTotalClientes() {
    const filas = document.querySelectorAll('.table-pedidos tbody tr');
    const elementoTotal = document.getElementById('totalClient');
    
    if (elementoTotal) {
        elementoTotal.textContent = filas.length;
    }
}

/**
 * Calcula el total de importe y formatea importes en la tabla
 */
function calcularTotalImporte() {
    const celdas = document.querySelectorAll('td.sumImporte');
    let total = 0;
    
    const formatter = new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0
    });
    
    celdas.forEach(celda => {
        const valor = parseFloat(celda.textContent) || 0;
        total += valor;
        // Formatear cada celda
        celda.textContent = formatter.format(valor);
        celda.classList.add('text-currency');
    });
    
    const elementoTotal = document.getElementById('totalImp');
    if (elementoTotal) {
        elementoTotal.textContent = formatter.format(total);
    }
}

/* ========================================
   Búsqueda rápida en tabla
   ======================================== */

/**
 * Inicializa la funcionalidad de búsqueda rápida
 */
function initBusquedaRapida() {
    const inputBusqueda = document.getElementById('textbusq');
    
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', filtrarTabla);
    }
}

/**
 * Filtra las filas de la tabla según el texto de búsqueda
 */
function filtrarTabla() {
    const input = document.getElementById('textbusq');
    const filtro = input.value.toUpperCase();
    const tabla = document.querySelector('.table-pedidos tbody');
    
    if (!tabla) return;
    
    const filas = tabla.getElementsByTagName('tr');
    let filasVisibles = 0;
    
    for (let i = 0; i < filas.length; i++) {
        const fila = filas[i];
        const celdas = fila.getElementsByTagName('td');
        let textoFila = '';
        
        // Concatenar texto de todas las celdas
        for (let j = 0; j < celdas.length; j++) {
            textoFila += celdas[j].textContent || celdas[j].innerText;
        }
        
        // Mostrar u ocultar fila según el filtro
        if (textoFila.toUpperCase().indexOf(filtro) > -1) {
            fila.style.display = '';
            filasVisibles++;
        } else {
            fila.style.display = 'none';
        }
    }
    
    // Actualizar contador de clientes visibles
    const elementoTotal = document.getElementById('totalClient');
    if (elementoTotal) {
        elementoTotal.textContent = filasVisibles;
    }
    
    // Mostrar mensaje si no hay resultados
    mostrarMensajeSinResultados(filasVisibles);
}

/**
 * Muestra mensaje cuando no hay resultados de búsqueda
 */
function mostrarMensajeSinResultados(filasVisibles) {
    const tabla = document.querySelector('.table-pedidos tbody');
    let mensajeExistente = document.getElementById('no-results-message');
    
    if (filasVisibles === 0 && !mensajeExistente) {
        const fila = tabla.insertRow(0);
        fila.id = 'no-results-message';
        const celda = fila.insertCell(0);
        celda.colSpan = tabla.parentElement.querySelectorAll('thead th').length;
        celda.className = 'no-data-message';
        celda.innerHTML = `
            <i class="bi bi-search"></i>
            <p>No se encontraron resultados para la búsqueda</p>
        `;
    } else if (filasVisibles > 0 && mensajeExistente) {
        mensajeExistente.remove();
    }
}

/* ========================================
   Tracking de edición de clientes
   ======================================== */

/**
 * Inicializa el tracking cuando se hace clic en editar
 */
function initEdicionTracking() {
    const botonesEditar = document.querySelectorAll('.btn-edit');
    
    botonesEditar.forEach(boton => {
        boton.addEventListener('click', function() {
            const fila = this.closest('tr');
            const codigoCliente = fila.querySelector('#cod_client');
            
            if (codigoCliente) {
                // Guardar el código del cliente y marcar que viene de edición
                const clienteId = codigoCliente.textContent.trim();
                const url = new URL(this.href);
                url.searchParams.append('edited', clienteId);
                this.href = url.toString();
            }
        });
    });
}

/**
 * Verifica si hay un cliente editado en la URL y lo marca visualmente
 */
function checkEditedRow() {
    // Buscar parámetro 'edited' en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const clienteEditado = urlParams.get('edited');
    
    // Solo marcar si viene el parámetro en la URL (recién editado)
    if (clienteEditado) {
        marcarFilaEditada(clienteEditado);
        
        // Limpiar la URL sin recargar la página
        const newUrl = new URL(window.location);
        newUrl.searchParams.delete('edited');
        window.history.replaceState({}, '', newUrl);
    }
}

/**
 * Marca visualmente la fila del cliente editado
 */
function marcarFilaEditada(codigoCliente) {
    const tabla = document.querySelector('.table-pedidos tbody');
    if (!tabla) return;
    
    const filas = tabla.getElementsByTagName('tr');
    
    for (let i = 0; i < filas.length; i++) {
        const celda = filas[i].querySelector('#cod_client');
        
        if (celda && celda.textContent.trim() === codigoCliente) {
            // Agregar clase de highlight
            filas[i].classList.add('edited-row');
            
            // Scroll suave hacia la fila
            setTimeout(() => {
                filas[i].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 300);
            
            break;
        }
    }
}

/**
 * Limpia la marca de edición
 */
function limpiarMarcaEdicion() {
    const filaEditada = document.querySelector('.edited-row');
    
    if (filaEditada) {
        filaEditada.classList.remove('edited-row');
    }
}

/* ========================================
   Modal de información del cliente
   ======================================== */

/**
 * Inicializa los eventos del modal de información
 */
function initModalCliente() {
    const botonesInfo = document.querySelectorAll('.btn-info-modal');
    
    botonesInfo.forEach(boton => {
        boton.addEventListener('click', function() {
            const fila = this.closest('tr');
            const codigoCliente = fila.querySelector('#cod_client');
            
            if (codigoCliente) {
                buscarCliente(codigoCliente.textContent.trim());
            }
        });
    });
}

/**
 * Busca información del cliente vía AJAX
 */
function buscarCliente(codigoCliente) {
    // Limpiar estilos anteriores
    limpiarEstilosModal();
    
    // Realizar petición AJAX
    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            procesarDatosCliente(xhr.responseText);
        }
    };
    
    xhr.open('GET', 'Class/ppp.php?cliente=' + encodeURIComponent(codigoCliente), true);
    xhr.send();
}

/**
 * Procesa los datos del cliente recibidos
 */
function procesarDatosCliente(respuesta) {
    console.log('Respuesta del servidor:', respuesta);
    
    try {
        const datosCliente = JSON.parse(respuesta);
        console.log('Datos parseados:', datosCliente);
        
        if (datosCliente && datosCliente.length > 0) {
            const datos = datosCliente[0];
            
            const formatter = new Intl.NumberFormat('es-AR', {
                style: 'currency',
                currency: 'ARS',
                minimumFractionDigits: 0
            });
            
            // Calcular valores numéricos
            const cupoCredito = parseFloat(datos.CUPO_CRED || 0);
            const totalDeuda = parseFloat(datos.TOTAL_DEUDA || 0);
            const totalDisponible = parseFloat(datos.TOTAL_DISPONIBLE || 0);
            
            // Actualizar elementos del modal
            actualizarElemento('codigoCliente', (datos.COD_CLIENTE || '') + ' - ' + (datos.RAZON_SOCIAL || ''));
            actualizarElemento('spanCupoCred', formatter.format(cupoCredito));
            actualizarElemento('saldo', formatter.format(parseFloat(datos.SALDO_CC || 0)));
            actualizarElemento('vencidas', formatter.format(parseFloat(datos.VENCIDAS || 0)));
            actualizarElemento('montoPedidos', formatter.format(parseFloat(datos.MONTO_PEDIDOS || 0)));
            actualizarElemento('cheque', formatter.format(parseFloat(datos.CHEQUE || 0)));
            actualizarElemento('cheques10Dias', formatter.format(parseFloat(datos.CHEQUES_10_DIAS || 0)));
            actualizarElemento('totalDeuda', formatter.format(totalDeuda));
            actualizarElemento('totalDisponible', formatter.format(totalDisponible));
            
            // Aplicar color condicional a la card de deuda
            aplicarColorCondicionalDeuda(totalDeuda, cupoCredito);
            
            // Aplicar estilos a valores negativos
            aplicarEstilosNegativos();
        } else {
            console.error('No se encontraron datos del cliente');
            actualizarElemento('codigoCliente', 'Sin datos');
        }
    } catch (error) {
        console.error('Error al procesar datos:', error);
        console.error('Respuesta recibida:', respuesta);
    }
}

/**
 * Actualiza un elemento del DOM si existe
 */
function actualizarElemento(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) {
        elemento.innerHTML = valor;
    }
}

/**
 * Aplica color condicional a la card de deuda según el porcentaje
 */
function aplicarColorCondicionalDeuda(totalDeuda, cupoCredito) {
    const cardDeuda = document.getElementById('totalDeuda');
    const cardDeudaContainer = cardDeuda ? cardDeuda.closest('.card') : null;
    
    if (!cardDeudaContainer || cupoCredito === 0) return;
    
    // Calcular porcentaje de deuda
    const porcentajeDeuda = (totalDeuda / cupoCredito) * 100;
    
    // Determinar color según porcentaje
    let color;
    if (porcentajeDeuda < 50) {
        // Verde - Situación buena
        color = '#28a745';
    } else if (porcentajeDeuda < 75) {
        // Amarillo/Naranja - Situación moderada
        color = '#ffc107';
    } else {
        // Rojo - Situación crítica
        color = '#dc3545';
    }
    
    // Aplicar color sólido
    cardDeudaContainer.style.background = color;
    
    // Actualizar porcentaje en el modal
    const porcentajeElement = document.getElementById('porcentajeDeuda');
    if (porcentajeElement) {
        porcentajeElement.textContent = porcentajeDeuda.toFixed(1) + '%';
    }
}

/**
 * Aplica estilos a valores negativos en el modal
 */
function aplicarEstilosNegativos() {
    const badges = document.querySelectorAll('.badge');
    
    badges.forEach(badge => {
        if (badge.innerText.includes('-')) {
            badge.style.background = '#dc3545';
        }
    });
}

/**
 * Limpia los estilos del modal
 */
function limpiarEstilosModal() {
    const badges = document.querySelectorAll('.badge');
    
    badges.forEach(badge => {
        badge.style.background = '';
    });
}

/* ========================================
   Utilidades
   ======================================== */

/**
 * Formatea un número como moneda
 */
function formatearMoneda(valor) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0
    }).format(valor);
}

/**
 * Formatea un número con separadores de miles
 */
function formatearNumero(valor) {
    return new Intl.NumberFormat('de-DE').format(valor);
}
