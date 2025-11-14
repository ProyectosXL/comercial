<?php
// Solo accesible para usuarios de supervisión
if ($rol !== 'supervision') {
    echo '<div class="alert alert-danger">';
    echo '<i class="bi bi-exclamation-triangle me-2"></i>';
    echo '<strong>Acceso Denegado:</strong> Esta vista solo está disponible para usuarios de supervisión.';
    echo '</div>';
    return;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-cash-coin me-2"></i>
        Reportes de Cumplimiento
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-success" onclick="exportarReporteVentas()">
                <i class="bi bi-download me-1"></i>
                Exportar Grilla
            </button>
            <button type="button" class="btn btn-sm btn-primary" onclick="exportarDetalleVentas()" id="btn-exportar-detalle-ventas" style="display: none;">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                Exportar Detalle
            </button>
        </div>
    </div>
</div>

<!-- Selector de Rango de Períodos -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-calendar-range me-2"></i>
                    Configuración del Reporte
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="periodo-desde-ventas" class="form-label">Período Desde</label>
                    <select class="form-select" id="periodo-desde-ventas">
                        <option value="">Seleccionar...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="periodo-hasta-ventas" class="form-label">Período Hasta</label>
                    <select class="form-select" id="periodo-hasta-ventas">
                        <option value="">Seleccionar...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-primary w-100" onclick="generarReporteVentas()">
                        <i class="bi bi-play-circle me-1"></i>
                        Generar Reporte
                    </button>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    El reporte muestra qué franquicias han cargado ventas por cada período en el rango seleccionado.
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="bi bi-bar-chart me-2"></i>
                    Resumen del Rango
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center" id="resumen-rango-ventas">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-primary mb-1" id="total-franquicias-ventas">-</h3>
                            <small class="text-muted">Total Franquicias</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-success mb-1" id="franquicias-cargaron-ventas">-</h3>
                            <small class="text-muted">Cargaron Ventas</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-danger mb-1" id="franquicias-no-cargaron-ventas">-</h3>
                            <small class="text-muted">No Cargaron</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-warning mb-1" id="periodos-totales-ventas">-</h3>
                            <small class="text-muted">Períodos Analizados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Cumplimiento por Período -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-grid me-2"></i>
            Grilla de Cumplimiento: Períodos vs Franquicias
        </h5>
    </div>
    <div class="card-body p-0">
        <!-- Loading State -->
        <div id="loading-reporte-ventas" class="text-center p-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Generando reporte...</span>
            </div>
            <p class="mt-2 text-muted">Analizando datos de ventas...</p>
        </div>
        
        <!-- Empty State -->
        <div id="reporte-ventas-vacio" class="text-center p-5">
            <i class="bi bi-table display-1 text-muted"></i>
            <h5 class="mt-3 text-muted">Seleccione un rango de períodos y genere el reporte</h5>
            <p class="text-muted">La matriz de cumplimiento de ventas aparecerá aquí una vez generado el reporte.</p>
        </div>
        
        <!-- Tabla de Reporte -->
        <div id="contenedor-tabla-reporte-ventas" style="display: none;">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tabla-cumplimiento-ventas">
                    <thead class="table-dark">
                        <tr id="header-periodos-ventas">
                            <th class="text-center">Franquicia</th>
                            <!-- Los períodos se agregarán dinámicamente -->
                        </tr>
                    </thead>
                    <tbody id="tbody-cumplimiento-ventas">
                        <!-- Los datos se cargan dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Leyenda -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card border-light">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Leyenda del Reporte
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-success me-2">✓</span>
                            <span>Franquicia cargó ventas en este período</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-danger me-2">✗</span>
                            <span>Franquicia NO cargó ventas en este período</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <i class="bi bi-lightbulb me-2"></i>
                            <strong>Nota:</strong> La tabla muestra TODAS las franquicias sin Tango y los períodos del rango seleccionado,
                            permitiendo una visión completa del cumplimiento de carga de ventas.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript específico para reportes de ventas -->
<script>
// Variables globales para reportes de ventas
let datosReporteVentas = null;
let rangoSeleccionado = { desde: null, hasta: null };

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando Reporte de Ventas...');
    inicializarSelectorPeriodos();
});

/**
 * Inicializar selectores de período
 */
function inicializarSelectorPeriodos() {
    const selectDesde = document.getElementById('periodo-desde-ventas');
    const selectHasta = document.getElementById('periodo-hasta-ventas');
    
    // Generar opciones desde Enero 2023 hasta mes anterior al actual
    const opciones = [];
    const fechaActual = new Date();
    
    // Mes anterior al actual
    const mesAnterior = new Date(fechaActual.getFullYear(), fechaActual.getMonth(), 1);
    
    // Fecha de inicio: Enero 2023
    const fechaInicio = new Date(2023, 0, 1); // Enero 2023
    
    // Generar desde mesAnterior hacia atrás hasta Enero 2023
    const fecha = new Date(mesAnterior);
    while (fecha >= fechaInicio) {
        const año = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const mesNombre = fecha.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
        
        const valor = `${año}${mes}`;
        const texto = mesNombre.charAt(0).toUpperCase() + mesNombre.slice(1);
        
        opciones.push({ valor, texto });
        
        // Retroceder un mes
        fecha.setMonth(fecha.getMonth() - 1);
    }
    
    // Llenar ambos selectores
    opciones.forEach(opcion => {
        const optionDesde = new Option(opcion.texto, opcion.valor);
        const optionHasta = new Option(opcion.texto, opcion.valor);
        selectDesde.add(optionDesde);
        selectHasta.add(optionHasta.cloneNode(true));
    });
    
    // Establecer valores por defecto (mes anterior para ambos)
    if (opciones.length > 0) {
        selectDesde.value = opciones[0].valor;
        selectHasta.value = opciones[0].valor;
    }
    
    // Event listeners para validación
    selectDesde.addEventListener('change', validarRangoSeleccionado);
    selectHasta.addEventListener('change', validarRangoSeleccionado);
}

/**
 * Validar que el rango seleccionado sea válido
 */
function validarRangoSeleccionado() {
    const desde = document.getElementById('periodo-desde-ventas').value;
    const hasta = document.getElementById('periodo-hasta-ventas').value;
    const btnGenerar = document.querySelector('button[onclick="generarReporteVentas()"]');
    
    if (!desde || !hasta) {
        btnGenerar.disabled = true;
        return false;
    }
    
    if (desde > hasta) {
        btnGenerar.disabled = true;
        alert('El período "Desde" debe ser anterior o igual al período "Hasta"');
        return false;
    }
    
    btnGenerar.disabled = false;
    rangoSeleccionado = { desde, hasta };
    return true;
}

/**
 * Generar reporte de ventas
 */
async function generarReporteVentas() {
    if (!validarRangoSeleccionado()) {
        return;
    }
    
    console.log('Generando reporte de ventas para rango:', rangoSeleccionado);
    
    // Mostrar loading
    document.getElementById('loading-reporte-ventas').style.display = 'block';
    document.getElementById('reporte-ventas-vacio').style.display = 'none';
    document.getElementById('contenedor-tabla-reporte-ventas').style.display = 'none';
    
    try {
        const response = await fetch('controller/reportes_ventas_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=generar_reporte_ventas&periodo_desde=${rangoSeleccionado.desde}&periodo_hasta=${rangoSeleccionado.hasta}`
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('Respuesta del servidor:', result);
        
        if (result.success) {
            datosReporteVentas = result.data;
            mostrarReporteVentas(result.data);
            actualizarResumenVentas(result.data);
        } else {
            throw new Error(result.message || 'Error desconocido al generar el reporte');
        }
        
    } catch (error) {
        console.error('Error generando reporte de ventas:', error);
        alert('Error al generar el reporte: ' + error.message);
        
        // Mostrar estado vacío
        document.getElementById('reporte-ventas-vacio').style.display = 'block';
    } finally {
        document.getElementById('loading-reporte-ventas').style.display = 'none';
    }
}

/**
 * Mostrar el reporte en la tabla (orientación transpuesta: períodos vs franquicias)
 */
function mostrarReporteVentas(datos) {
    const tabla = document.getElementById('tabla-cumplimiento-ventas');
    const thead = tabla.querySelector('thead tr');
    const tbody = tabla.querySelector('tbody');
    
    // Limpiar tabla
    thead.innerHTML = '<th class="text-center sticky-col">Período</th>';
    tbody.innerHTML = '';
    
    // Crear headers para franquicias (columnas)
    datos.franquicias.forEach(franquicia => {
        const th = document.createElement('th');
        th.textContent = franquicia.desc_sucursal;
        th.className = 'text-center';
        th.title = `Sucursal ${franquicia.nro_sucursal}`;
        thead.appendChild(th);
    });
    
    // Crear filas para cada período
    datos.periodos.forEach(periodo => {
        const tr = document.createElement('tr');
        
        // Celda de período (primera columna)
        const tdPeriodo = document.createElement('td');
        tdPeriodo.textContent = formatearPeriodo(periodo);
        tdPeriodo.className = 'text-center fw-bold bg-light';
        tr.appendChild(tdPeriodo);
        
        // Celdas de franquicias
        datos.franquicias.forEach(franquicia => {
            const td = document.createElement('td');
            td.className = 'text-center align-middle cumplimiento-cell';
            
            const clave = `${franquicia.nro_sucursal}_${periodo}`;
            const tieneDatos = datos.cumplimiento[clave] || false;
            
            // Usar mismo estilo que reportes de cumplimiento
            if (tieneDatos) {
                td.innerHTML = '<span class="badge bg-success">✓</span>';
                td.title = `${franquicia.desc_sucursal} cargó ventas en ${formatearPeriodo(periodo)}`;
                td.classList.add('cumplimiento-si');
            } else {
                td.innerHTML = '<span class="badge bg-danger">✗</span>';
                td.title = `${franquicia.desc_sucursal} NO cargó ventas en ${formatearPeriodo(periodo)}`;
                td.classList.add('cumplimiento-no');
            }
            
            tr.appendChild(td);
        });
        
        tbody.appendChild(tr);
    });
    
    // Mostrar tabla
    document.getElementById('contenedor-tabla-reporte-ventas').style.display = 'block';
    document.getElementById('reporte-ventas-vacio').style.display = 'none';
    
    // Mostrar botón "Exportar Detalle" solo después de generar reporte
    const btnExportarDetalle = document.getElementById('btn-exportar-detalle-ventas');
    if (btnExportarDetalle) {
        btnExportarDetalle.style.display = 'inline-block';
    }
}

/**
 * Actualizar resumen estadístico
 */
function actualizarResumenVentas(datos) {
    document.getElementById('total-franquicias-ventas').textContent = datos.franquicias.length;
    document.getElementById('periodos-totales-ventas').textContent = datos.periodos.length;
    
    // Calcular franquicias que cargaron al menos en un período
    const franquiciasConDatos = new Set();
    Object.keys(datos.cumplimiento).forEach(clave => {
        if (datos.cumplimiento[clave]) {
            const [nroSucursal] = clave.split('_');
            franquiciasConDatos.add(nroSucursal);
        }
    });
    
    const totalCargaron = franquiciasConDatos.size;
    const totalNoCargaron = datos.franquicias.length - totalCargaron;
    
    document.getElementById('franquicias-cargaron-ventas').textContent = totalCargaron;
    document.getElementById('franquicias-no-cargaron-ventas').textContent = totalNoCargaron;
}

/**
 * Formatear período YYYYMM a texto legible
 */
function formatearPeriodo(periodo) {
    const año = periodo.substring(0, 4);
    const mes = periodo.substring(4, 6);
    const fecha = new Date(año, parseInt(mes) - 1, 1);
    return fecha.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' }).toUpperCase();
}

/**
 * Exportar grilla a Excel
 */
async function exportarReporteVentas() {
    if (!datosReporteVentas) {
        alert('Primero debe generar un reporte');
        return;
    }
    
    try {
        // Crear datos para Excel similar a reportes_cumplimiento
        const headers = ['Período'];
        datosReporteVentas.franquicias.forEach(franquicia => {
            headers.push(franquicia.desc_sucursal);
        });

        const datos = [];
        datos.push(headers);
        
        // Crear filas para cada período
        datosReporteVentas.periodos.forEach(periodo => {
            // Formatear período como MM/YYYY para Excel
            const año = periodo.substring(0, 4);
            const mes = periodo.substring(4, 6);
            const periodoExcel = `${mes}/${año}`;
            
            const fila = [periodoExcel];
            
            datosReporteVentas.franquicias.forEach(franquicia => {
                const clave = `${franquicia.nro_sucursal}_${periodo}`;
                const tieneDatos = datosReporteVentas.cumplimiento[clave] || false;
                fila.push(tieneDatos ? 'SÍ' : 'NO');
            });
            
            datos.push(fila);
        });
        
        // Exportar a Excel usando XLSX directamente
        exportarAExcel(datos, `reporte_ventas_${rangoSeleccionado.desde}_${rangoSeleccionado.hasta}`, 'Reporte de Ventas');
        
    } catch (error) {
        console.error('Error exportando grilla:', error);
        alert('Error al exportar la grilla: ' + error.message);
    }
}

/**
 * Exportar detalle a Excel
 */
async function exportarDetalleVentas() {
    if (!datosReporteVentas) {
        alert('Primero debe generar un reporte');
        return;
    }
    
    try {
        const response = await fetch('controller/reportes_ventas_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=exportar_detalle_ventas&periodo_desde=${rangoSeleccionado.desde}&periodo_hasta=${rangoSeleccionado.hasta}`
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'Error obteniendo datos');
        }
        
        // Convertir datos a formato para Excel como en reportes_cumplimiento
        const datosExcel = [];
        
        // Headers
        datosExcel.push(['PERIODO', 'NRO_SUCURSAL', 'DESC_SUCURSAL', 'RUBRO', 'CATEGORIA', 'STOCK_VENDIDO']);
        
        // Datos
        result.data.forEach(registro => {
            // Formatear período como MM/YYYY para Excel
            const año = registro.periodo.toString().substring(0, 4);
            const mes = registro.periodo.toString().substring(4, 6);
            const periodoExcel = `${mes}/${año}`;
            
            datosExcel.push([
                periodoExcel,
                registro.nro_sucursal,
                registro.desc_sucursal,
                registro.rubro,
                registro.categoria_padre,
                registro.cantidad
            ]);
        });
        
        // Exportar usando la función existente
        exportarAExcel(datosExcel, `detalle_ventas_${rangoSeleccionado.desde}_${rangoSeleccionado.hasta}`, 'Detalle Ventas');
        
    } catch (error) {
        console.error('Error exportando detalle:', error);
        alert('Error al exportar el detalle: ' + error.message);
    }
}

/**
 * Función de exportación a Excel usando XLSX directamente (igual que reportes_cumplimiento)
 */
function exportarAExcel(datos, nombreArchivo, nombreHoja) {
    console.log('Iniciando exportación a Excel...', {datos: datos.length, archivo: nombreArchivo});
    
    if (typeof XLSX === 'undefined') {
        console.error('XLSX no está disponible');
        alert('Error: La biblioteca Excel no está cargada');
        return;
    }
    
    try {
        // Crear libro de trabajo
        const wb = XLSX.utils.book_new();
        
        // Crear hoja de trabajo con los datos
        const ws = XLSX.utils.aoa_to_sheet(datos);
        
        // Aplicar formato a las cabeceras (primera fila)
        if (datos.length > 0) {
            const headerRange = XLSX.utils.encode_range({s: {c: 0, r: 0}, e: {c: datos[0].length - 1, r: 0}});
            for (let col = 0; col < datos[0].length; col++) {
                const cellRef = XLSX.utils.encode_cell({c: col, r: 0});
                if (!ws[cellRef]) continue;
                ws[cellRef].s = {
                    font: { bold: true },
                    fill: { fgColor: { rgb: "E6F3FF" } },
                    alignment: { horizontal: "center" }
                };
            }
        }
        
        // Agregar la hoja al libro
        XLSX.utils.book_append_sheet(wb, ws, nombreHoja || 'Reporte');
        
        // Generar y descargar el archivo
        XLSX.writeFile(wb, `${nombreArchivo}.xlsx`);
        
        console.log('Excel exportado exitosamente');
        
    } catch (error) {
        console.error('Error al exportar Excel:', error);
        alert('Error al exportar Excel: ' + error.message);
        
        // Fallback a CSV en caso de error
        console.log('Intentando fallback a CSV...');
        const csvContent = datos.map(fila => 
            fila.map(celda => `"${celda}"`).join(',')
        ).join('\n');
        
        descargarArchivo(csvContent, `${nombreArchivo}.csv`, 'text/csv');
    }
}

/**
 * Función auxiliar para descargar archivos
 */
function descargarArchivo(contenido, nombreArchivo, tipoMime) {
    const blob = new Blob([contenido], { type: tipoMime });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = nombreArchivo;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}
</script>

<!-- CSS específico para la tabla de reportes -->
<style>
/* Variables mejoradas */
:root {
    --texto-principal: #1f2937;
    --texto-secundario: #6b7280;
    --fondo-claro: #ffffff;
    --fondo-tabla: #f9fafb;
    --borde-tabla: #e5e7eb;
    --verde-exito: #10b981;
    --rojo-error: #ef4444;
}

/* Contenedor de la tabla con scroll mejorado */
.table-responsive {
    max-height: 70vh;
    overflow-x: auto;
    overflow-y: auto;
    border: 2px solid var(--borde-tabla);
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    background: var(--fondo-claro);
}

/* Tabla principal */
.table-bordered {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.75rem; /* Fuente más pequeña para que quepa más contenido */
    background: var(--fondo-claro);
}

/* Headers de la tabla */
.table-bordered th {
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%) !important;
    color: #ffffff !important;
    border: 1px solid #4b5563 !important;
    text-align: center;
    vertical-align: middle;
    padding: 8px 4px;
    font-size: 0.7rem;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Columna sticky de franquicia */
.sticky-column {
    position: sticky !important;
    left: 0 !important;
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%) !important;
    color: #ffffff !important;
    z-index: 20 !important;
    min-width: 160px;
    max-width: 180px;
    text-align: left !important;
    font-weight: 600;
    border-right: 2px solid #4b5563 !important;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

/* Celdas del cuerpo de la tabla */
.table-bordered td {
    border: 1px solid var(--borde-tabla) !important;
    text-align: center;
    vertical-align: middle;
    padding: 6px 4px;
    font-size: 0.7rem;
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
    font-weight: 500;
}

/* Celda sticky de franquicia en el cuerpo */
.table-bordered tbody .sticky-column {
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
    border-right: 2px solid var(--borde-tabla) !important;
    font-weight: 600;
    font-size: 0.75rem;
}

/* Headers de período con texto rotado */
.periodo-header {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    min-width: 50px;
    max-width: 60px;
    height: 100px;
    padding: 4px 2px !important;
    font-size: 0.65rem !important;
    font-weight: 600;
    line-height: 1.2;
    word-break: break-word;
}

/* Celdas de cumplimiento */
.cumplimiento-cell {
    width: 50px !important;
    min-width: 50px !important;
    max-width: 50px !important;
    padding: 4px 2px !important;
}

/* Estados de cumplimiento */
.cumplimiento-si {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
    color: #065f46 !important;
    border-color: var(--verde-exito) !important;
}

.cumplimiento-no {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    color: #991b1b !important;
    border-color: var(--rojo-error) !important;
}

/* Clases para indicadores de cumplimiento */
.cumple {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
    color: #065f46 !important;
    border-color: var(--verde-exito) !important;
}

.no-cumple {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    color: #991b1b !important;
    border-color: var(--rojo-error) !important;
}

/* Columna fija para períodos */
.sticky-col {
    position: sticky;
    left: 0;
    z-index: 10;
    background: var(--fondo-claro) !important;
    border-right: 2px solid var(--borde-tabla) !important;
    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
    font-weight: 600;
    min-width: 120px;
    max-width: 120px;
}

/* Headers de franquicias */
.franquicia-header {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    min-width: 50px;
    max-width: 60px;
    height: 100px;
    padding: 4px 2px !important;
    font-size: 0.65rem !important;
    font-weight: 600;
    line-height: 1.2;
    word-break: break-word;
}

/* Estado general */
.estado-general {
    min-width: 90px;
    max-width: 100px;
    font-weight: 600 !important;
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
    font-size: 0.75rem;
}

/* Badges en la tabla */
.badge {
    font-size: 0.65rem !important;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    min-width: 30px;
}

.badge.bg-success {
    background: var(--verde-exito) !important;
    color: #ffffff !important;
}

.badge.bg-danger {
    background: var(--rojo-error) !important;
    color: #ffffff !important;
}

/* Hover effects */
.table-hover tbody tr:hover {
    background: rgba(59, 130, 246, 0.05) !important;
}

.table-hover tbody tr:hover td {
    background: rgba(59, 130, 246, 0.05) !important;
    color: var(--texto-principal) !important;
}

.table-hover tbody tr:hover .sticky-column {
    background: rgba(59, 130, 246, 0.1) !important;
    color: var(--texto-principal) !important;
}

/* Cards de información */
.card {
    background: var(--fondo-claro) !important;
    border: 1px solid var(--borde-tabla);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-body {
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
}

.card-header {
    background: linear-gradient(135deg, #f9fafb 0%, #e5e7eb 100%) !important;
    color: var(--texto-principal) !important;
    border-bottom: 1px solid var(--borde-tabla);
    font-weight: 600;
}

.card-header.bg-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
    color: #ffffff !important;
    border-bottom: none;
}

.card-header.bg-info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
    color: #ffffff !important;
    border-bottom: none;
}

/* Estadísticas */
.stat-item h3 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--texto-principal) !important;
    margin-bottom: 0.5rem;
}

.stat-item small {
    color: var(--texto-secundario) !important;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Alertas */
.alert {
    color: var(--texto-principal) !important;
    border: none;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.alert-info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
    border-left: 4px solid #3b82f6;
    color: #1e40af !important;
}

/* Leyenda */
.card.border-light {
    background: var(--fondo-tabla) !important;
    border: 1px solid var(--borde-tabla) !important;
}

.card.border-light .card-body {
    background: var(--fondo-tabla) !important;
    color: var(--texto-principal) !important;
}

.card.border-light .card-body span {
    color: var(--texto-principal) !important;
}

/* Texto en elementos secundarios */
.text-muted {
    color: var(--texto-secundario) !important;
}

.text-primary {
    color: #3b82f6 !important;
}

.text-success {
    color: var(--verde-exito) !important;
}

.text-danger {
    color: var(--rojo-error) !important;
}

.text-warning {
    color: #f59e0b !important;
}

/* Botones */
.btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border: none;
    color: #ffffff;
}

.btn-secondary {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    border: none;
    color: #ffffff;
}

/* Formularios */
.form-control, .form-select {
    border: 1px solid var(--borde-tabla);
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
}

.form-control:focus, .form-select:focus {
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
}

.form-label {
    color: var(--texto-principal) !important;
    font-weight: 600;
}

/* Loading states */
#loading-reporte-ventas {
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
}

#reporte-ventas-vacio {
    background: var(--fondo-tabla) !important;
    color: var(--texto-principal) !important;
}

#reporte-ventas-vacio h5 {
    color: var(--texto-principal) !important;
}

#reporte-ventas-vacio p {
    color: var(--texto-secundario) !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sticky-column {
        min-width: 120px !important;
        max-width: 140px !important;
        font-size: 0.7rem;
    }
    
    .periodo-header {
        min-width: 40px !important;
        max-width: 45px !important;
        font-size: 0.6rem !important;
        height: 80px;
    }
    
    .table-bordered th,
    .table-bordered td {
        font-size: 0.65rem;
        padding: 4px 2px;
    }
    
    .badge {
        font-size: 0.6rem !important;
        padding: 3px 6px;
        min-width: 50px;
    }
    
    .cumplimiento-cell {
        width: 40px !important;
        min-width: 40px !important;
        max-width: 40px !important;
    }
    
    .estado-general {
        min-width: 80px;
        max-width: 85px;
    }
}
</style>