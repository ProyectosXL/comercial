/* ========================================
   JavaScript específico para index.php
   Gestión de Pedidos - XL Extra
   ======================================== */

/**
 * Inicialización cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('index.js cargado correctamente');
    
    // Inicializar funcionalidades
    initTotales();
    initBusquedaRapida();
    initEdicionTracking();
    initModalCliente();
    initBotonLimpiarMarcas();
    checkEditedRow();
});

/**
 * Detectar cuando se regresa a la página desde el historial
 * para actualizar las marcas de edición
 */
window.addEventListener('pageshow', function(event) {
    // Si la página se cargó desde el caché del navegador (botón atrás)
    if (event.persisted) {
        console.log('Página cargada desde caché, actualizando marcas de edición');
        checkEditedRow();
    }
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
   Tracking de edición de clientes (CORREGIDO)
   ======================================== */

/**
 * Inicializa el tracking cuando se hace clic en editar
 */
function initEdicionTracking() {
    const botonesEditar = document.querySelectorAll('.btn-edit');
    
    botonesEditar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            const fila = this.closest('tr');
            const codigoCliente = fila.querySelector('#cod_client');
            
            if (codigoCliente) {
                const clienteId = codigoCliente.textContent.trim();
                
                // Guardar en sessionStorage que este cliente fue editado
                let clientesEditados = getClientesEditados();
                if (!clientesEditados.includes(clienteId)) {
                    clientesEditados.push(clienteId);
                    sessionStorage.setItem('clientesEditados', JSON.stringify(clientesEditados));
                }
            }
        });
    });
}

/**
 * Obtiene la lista de clientes editados de sessionStorage
 */
function getClientesEditados() {
    const stored = sessionStorage.getItem('clientesEditados');
    return stored ? JSON.parse(stored) : [];
}

/**
 * Verifica y marca las filas de clientes editados
 */
function checkEditedRow() {
    const clientesEditados = getClientesEditados();
    
    if (clientesEditados.length === 0) return;
    
    const tabla = document.querySelector('.table-pedidos tbody');
    if (!tabla) return;
    
    const filas = tabla.getElementsByTagName('tr');
    
    for (let i = 0; i < filas.length; i++) {
        const celda = filas[i].querySelector('#cod_client');
        
        if (celda) {
            const codigoCliente = celda.textContent.trim();
            
            if (clientesEditados.includes(codigoCliente)) {
                // Marcar la fila como editada con estilo visual
                filas[i].classList.add('edited-row');
                
                // Agregar indicador visual al botón de editar sin eliminarlo
                const btnEditar = filas[i].querySelector('.btn-edit');
                if (btnEditar && !btnEditar.querySelector('.edited-indicator')) {
                    // Cambiar el ícono para indicar que ya fue editado
                    btnEditar.classList.add('btn-edited');
                    btnEditar.title = 'Volver a editar (ya editado)';
                    
                    // Agregar un pequeño indicador visual
                    const indicator = document.createElement('span');
                    indicator.className = 'edited-indicator';
                    indicator.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
                    indicator.style.position = 'absolute';
                    indicator.style.top = '-4px';
                    indicator.style.right = '-4px';
                    indicator.style.fontSize = '0.7rem';
                    indicator.style.color = '#ffffff';
                    indicator.style.backgroundColor = '#198754';
                    indicator.style.borderRadius = '50%';
                    indicator.style.padding = '2px';
                    indicator.style.lineHeight = '1';
                    indicator.style.display = 'flex';
                    indicator.style.alignItems = 'center';
                    indicator.style.justifyContent = 'center';
                    
                    btnEditar.style.position = 'relative';
                    btnEditar.appendChild(indicator);
                }
            }
        }
    }
}

/**
 * Limpia todas las marcas de edición (para debugging)
 */
function limpiarMarcasEdicion() {
    sessionStorage.removeItem('clientesEditados');
    location.reload();
}

/**
 * Inicializa el botón de limpiar marcas
 */
function initBotonLimpiarMarcas() {
    const btnLimpiar = document.getElementById('btnLimpiarMarcas');
    
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            Swal.fire({
                title: '¿Limpiar marcas de edición?',
                text: 'Se eliminarán todas las marcas visuales de los pedidos editados',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-check-circle me-2"></i>Sí, limpiar',
                cancelButtonText: '<i class="bi bi-x-circle me-2"></i>Cancelar',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-secondary',
                    actions: 'swal-actions-spacing'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    limpiarMarcasEdicion();
                }
            });
        });
    }
}
function limpiarMarcasEdicion() {
    sessionStorage.removeItem('clientesEditados');
    location.reload();
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
