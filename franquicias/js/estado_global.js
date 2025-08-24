/**
 * Funciones globales para sincronizar estado entre vistas
 */

// Función global para verificar y actualizar el estado del período
async function verificarEstadoPeriodoGlobal() {
    try {
        const periodoActual = obtenerPeriodoActual();
        
        const response = await fetch('controller/franquicia_stock_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=obtener_stock&periodo=${periodoActual}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            const tieneStock = data.data.length > 0;
            actualizarEstadoSidebar(tieneStock);
            return tieneStock;
        }
    } catch (error) {
        console.error('Error al verificar estado del período:', error);
    }
    
    return false;
}

// Función para actualizar el estado en el sidebar
function actualizarEstadoSidebar(tieneStock) {
    const badge = document.getElementById('badge-estado');
    
    if (badge) {
        if (tieneStock) {
            badge.className = 'estado-badge informo';
            badge.innerHTML = '<i class="bi bi-check-circle me-1"></i>Informó';
        } else {
            badge.className = 'estado-badge no-informo';
            badge.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Pendiente';
        }
    }
}

// Función para obtener período actual
function obtenerPeriodoActual() {
    const fecha = new Date();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const año = fecha.getFullYear();
    return `${mes}-${año}`;
}

// Verificar estado al cargar cualquier página
document.addEventListener('DOMContentLoaded', function() {
    // Esperar un poco para que se carguen otros scripts
    setTimeout(() => {
        verificarEstadoPeriodoGlobal();
    }, 500);
});
