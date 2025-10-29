/**
 * Funciones globales para sincronizar estado entre vistas
 * Versión corregida que funciona en ambas vistas
 */

// Función global para verificar y actualizar el estado del período
async function verificarEstadoPeriodoGlobal() {
    try {
        // Determinar qué vista estamos usando
        const vistaActual = obtenerVistaActual();
        console.log('Verificando estado para vista:', vistaActual);
        
        // Solo verificar estado en vistas de franquicia, no en reportes de supervisión
        if (vistaActual === 'reportes' || vistaActual === 'reportes_ventas' || vistaActual === 'panel') {
            console.log('Vista de supervisión detectada, omitiendo verificación de estado de franquicia');
            return false;
        }
        
        const periodoActual = obtenerPeriodoActual();
        
        let response;
        
        if (vistaActual === 'historial') {
            // En la vista de historial, usar el controlador específico de historial
            response = await fetch('controller/historial_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'accion=obtener_estado'
            });
        } else {
            // En otras vistas, usar el controlador principal
            response = await fetch('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'accion=obtener_resumen_stock'
            });
        }
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Respuesta del servidor para estado:', data);
        
        if (data.success) {
            let tieneStock = false;
            
            // Usar directamente la respuesta del servidor que indica si tiene stock
            if (data.tiene_stock !== undefined) {
                tieneStock = data.tiene_stock;
                console.log('Estado desde servidor - Tiene stock:', tieneStock);
            } else if (vistaActual === 'historial') {
                // En historial, verificar si existe el período actual en los datos
                const datosHistorial = data.data || [];
                tieneStock = datosHistorial.some(item => item.periodo === periodoActual);
                console.log('Estado desde historial - Período actual:', periodoActual, 'Tiene stock:', tieneStock);
            } else {
                // En vista de carga, verificar que el stock sea del período actual
                // Obtener período actual en formato YYYYMM
                const fecha = new Date();
                const mes = String(fecha.getMonth() + 1).padStart(2, '0');
                const año = fecha.getFullYear();
                const periodoActualYYYYMM = `${año}${mes}`;
                
                const stockData = data.stock || data.data || [];
                
                // Verificar si algún item tiene periodo igual al actual
                if (stockData.length > 0 && stockData[0].periodo) {
                    tieneStock = stockData.some(item => item.periodo === periodoActualYYYYMM);
                    console.log('Estado desde stock - Verificando período:', periodoActualYYYYMM, 'Tiene stock:', tieneStock);
                } else {
                    // Si los datos no tienen periodo, asumir que son del período actual
                    tieneStock = stockData.length > 0;
                    console.log('Estado desde stock (sin período) - Tiene stock:', tieneStock);
                }
            }
            
            actualizarEstadoSidebar(tieneStock);
            return tieneStock;
        } else {
            console.warn('Error en respuesta del servidor:', data.message);
            actualizarEstadoSidebar(false);
        }
    } catch (error) {
        console.error('Error al verificar estado del período:', error);
        actualizarEstadoSidebar(false);
    }
    
    return false;
}

// Función para determinar la vista actual
function obtenerVistaActual() {
    // Método 1: Verificar parámetro URL
    const urlParams = new URLSearchParams(window.location.search);
    const vistaUrl = urlParams.get('vista');
    
    if (vistaUrl) {
        return vistaUrl;
    }
    
    // Método 2: Verificar elementos específicos de cada vista
    if (document.getElementById('tbody-historial')) {
        return 'historial';
    }
    
    if (document.getElementById('selector-rubro')) {
        return 'franquicia';
    }
    
    if (document.getElementById('panel-comercial-header')) {
        return 'panel';
    }
    
    // Método 3: Verificar enlaces activos en el navbar
    const linkActivo = document.querySelector('.nav-link.active');
    if (linkActivo && linkActivo.textContent.includes('Historial')) {
        return 'historial';
    }
    
    // Por defecto, asumir vista franquicia
    return 'franquicia';
}

// Función para actualizar el estado en el sidebar
function actualizarEstadoSidebar(tieneStock) {
    const badge = document.getElementById('badge-estado');
    const infoEstado = document.getElementById('info-estado-carga');
    
    console.log('Actualizando estado sidebar - Tiene stock:', tieneStock);
    
    if (badge) {
        if (tieneStock) {
            badge.className = 'estado-badge informo';
            badge.innerHTML = '<i class="bi bi-check-circle me-1"></i>Informó';
        } else {
            badge.className = 'estado-badge no-informo';
            badge.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Pendiente';
        }
        console.log('Badge actualizado:', badge.className, badge.innerHTML);
    } else {
        console.warn('Elemento badge-estado no encontrado');
    }
    
    // Actualizar también el info de estado si existe (en vista franquicia)
    if (infoEstado) {
        if (tieneStock) {
            infoEstado.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle text-success me-2"></i>
                    <span class="text-success fw-bold">Stock cargado para este período en al menos un rubro</span>
                </div>
            `;
        } else {
            infoEstado.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                    <span class="text-warning fw-bold">Debe cargar el stock del período actual</span>
                </div>
            `;
        }
        console.log('Info estado actualizado');
    }
}

// Función para verificar estado de ventas (solo para franquicias sin Tango)
async function verificarEstadoVentas() {
    const badgeVentas = document.getElementById('badge-estado-ventas');
    
    // Si no existe el badge de ventas, no hacer nada (usuario sin acceso)
    if (!badgeVentas) {
        console.log('No existe badge-estado-ventas, omitiendo verificación de ventas');
        return;
    }
    
    try {
        // Obtener período en formato YYYYMM (que es como está en la BD)
        const fecha = new Date();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const año = fecha.getFullYear();
        const periodoActual = `${año}${mes}`; // Formato: 202510
        
        console.log('Verificando ventas para período:', periodoActual);
        
        // Consultar si existen ventas para el período actual
        const response = await fetch('controller/verificar_estado_ventas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `periodo=${encodeURIComponent(periodoActual)}`
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Estado de ventas:', data);
        
        if (data.success) {
            if (data.tiene_ventas) {
                badgeVentas.className = 'estado-badge informo';
                badgeVentas.innerHTML = '<i class="bi bi-check-circle me-1"></i>Informó';
            } else {
                badgeVentas.className = 'estado-badge no-informo';
                badgeVentas.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Pendiente';
            }
        }
    } catch (error) {
        console.error('Error al verificar estado de ventas:', error);
        badgeVentas.className = 'estado-badge verificando';
        badgeVentas.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Error';
    }
}

// Función para obtener período actual
function obtenerPeriodoActual() {
    const fecha = new Date();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const año = fecha.getFullYear();
    return `${mes}-${año}`;
}

// Función para verificar estado específicamente después de guardar stock
async function verificarEstadoDespuesDeGuardar() {
    console.log('Verificando estado después de guardar...');
    
    // Esperar un poco para que la base de datos se actualice
    setTimeout(async () => {
        await verificarEstadoPeriodoGlobal();
        
        // Si estamos en la vista historial y existe el manager, recargar datos
        if (typeof historialManager !== 'undefined' && historialManager) {
            console.log('Recargando datos del historial después de actualización...');
            historialManager.cargarHistorial();
        }
    }, 1000);
}

// Verificar estado al cargar cualquier página
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, verificando estado global...');
    
    // Verificar estado pero solo si no estamos en medio de una operación de guardado
    setTimeout(async () => {
        // Solo verificar si no hay un modal de loading visible
        const modalLoading = document.getElementById('modalLoading');
        if (!modalLoading || !modalLoading.classList.contains('show')) {
            console.log('Ejecutando verificación automática del estado...');
            await verificarEstadoPeriodoGlobal();
            
            // Verificar también estado de ventas si el badge existe
            await verificarEstadoVentas();
        } else {
            console.log('Modal de loading visible, posponiendo verificación...');
            // Si hay modal, esperar a que termine y luego verificar
            setTimeout(async () => {
                if (!modalLoading.classList.contains('show')) {
                    await verificarEstadoPeriodoGlobal();
                    await verificarEstadoVentas();
                }
            }, 2000);
        }
    }, 1000);
});

// También verificar cuando cambia la visibilidad de la página (por si el usuario vuelve)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        console.log('Página visible de nuevo, verificando estado...');
        // Verificar estado después de un pequeño delay
        setTimeout(async () => {
            const modalLoading = document.getElementById('modalLoading');
            if (!modalLoading || !modalLoading.classList.contains('show')) {
                await verificarEstadoPeriodoGlobal();
                await verificarEstadoVentas();
            }
        }, 500);
    }
});

// Función para forzar actualización del estado (usar desde otras vistas)
window.actualizarEstadoGlobal = function() {
    console.log('Forzando actualización del estado global...');
    verificarEstadoPeriodoGlobal();
};

// Función para usar después de operaciones de stock
window.notificarCambioStock = function() {
    console.log('Notificando cambio de stock...');
    verificarEstadoDespuesDeGuardar();
};