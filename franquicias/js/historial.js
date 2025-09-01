/**
 * JavaScript para Historial de Stock - Versión Completa Corregida
 * Archivo: franquicias/js/historial.js
 */

// Instancia del manejador de historial
let historialManager;

document.addEventListener('DOMContentLoaded', function() {
    historialManager = new HistorialStockManager();
    
    // Configurar eventos del modal personalizado
    configurarEventosModal();
});

class HistorialStockManager {
    constructor() {
        this.datosHistorial = [];
        this.datosFiltrados = [];
        this.paginaActual = 1;
        this.itemsPorPagina = 25;
        this.periodoActual = this.obtenerPeriodoActual();
        
        this.init();
    }
    
    async init() {
        try {
            await this.cargarHistorial();
            await this.cargarFiltros();
            this.configurarEventos();
        } catch (error) {
            console.error('Error en inicialización:', error);
            this.mostrarError('Error al inicializar el historial');
        }
    }
    
    configurarEventos() {
        // Filtros
        const filtroPeriodo = document.getElementById('filtro-periodo');
        const filtroRubro = document.getElementById('filtro-rubro');
        
        if (filtroPeriodo) {
            filtroPeriodo.addEventListener('change', () => this.aplicarFiltros());
        }
        
        if (filtroRubro) {
            filtroRubro.addEventListener('change', () => this.aplicarFiltros());
        }
        
        // Modo compacto
        const modoCompacto = document.getElementById('modo-compacto');
        if (modoCompacto) {
            modoCompacto.addEventListener('change', (e) => {
                this.toggleModoCompacto(e.target.checked);
            });
        }
    }
    
    async cargarHistorial() {
        try {
            this.mostrarLoading();
            
            console.log('Cargando historial desde historial_stock_controller.php...');
            
            const response = await fetch('controller/historial_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'accion=obtener_historial'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Respuesta del servidor:', data);
            
            if (data.success) {
                this.datosHistorial = data.data || [];
                this.datosFiltrados = [...this.datosHistorial];
                
                console.log('Datos historial cargados:', this.datosHistorial.length, 'registros');
                console.log('Ejemplo de registro:', this.datosHistorial[0]);
                
                this.actualizarResumen();
                
                if (this.datosHistorial.length > 0) {
                    this.mostrarTabla();
                } else {
                    this.mostrarVacio();
                }
            } else {
                this.mostrarError(data.message || 'Error al cargar el historial');
                this.mostrarVacio();
            }
        } catch (error) {
            console.error('Error en cargarHistorial:', error);
            this.mostrarError('Error de conexión al cargar historial: ' + error.message);
            this.mostrarVacio();
        } finally {
            this.ocultarLoading();
        }
    }
    
    async cargarFiltros() {
        try {
            // Cargar períodos únicos del historial actual
            const periodos = [...new Set(this.datosHistorial.map(item => item.periodo))].sort().reverse();
            this.llenarSelectPeriodos(periodos);
            
            // Cargar rubros únicos del historial actual
            const rubros = [...new Set(this.datosHistorial.map(item => item.rubro))].filter(r => r && r !== 'SIN RUBRO').sort();
            this.llenarSelectRubros(rubros);
            
            console.log('Filtros cargados - Períodos:', periodos, 'Rubros:', rubros);
            
        } catch (error) {
            console.error('Error al cargar filtros:', error);
        }
    }
    
    llenarSelectPeriodos(periodos) {
        const selectPeriodo = document.getElementById('filtro-periodo');
        if (!selectPeriodo) {
            console.warn('No se encontró el select de período');
            return;
        }
        
        // Limpiar opciones existentes excepto la primera
        while (selectPeriodo.children.length > 1) {
            selectPeriodo.removeChild(selectPeriodo.lastChild);
        }
        
        periodos.forEach(periodo => {
            const option = document.createElement('option');
            option.value = periodo;
            option.textContent = this.formatearPeriodo(periodo);
            selectPeriodo.appendChild(option);
        });
        
        console.log('Select período llenado con', periodos.length, 'opciones');
    }
    
    llenarSelectRubros(rubros) {
        const selectRubro = document.getElementById('filtro-rubro');
        if (!selectRubro) {
            console.warn('No se encontró el select de rubro');
            return;
        }
        
        // Limpiar opciones existentes excepto la primera
        while (selectRubro.children.length > 1) {
            selectRubro.removeChild(selectRubro.lastChild);
        }
        
        rubros.forEach(rubro => {
            if (rubro && rubro !== 'SIN RUBRO') {
                const option = document.createElement('option');
                option.value = rubro;
                option.textContent = rubro;
                selectRubro.appendChild(option);
            }
        });
        
        console.log('Select rubro llenado con', rubros.length, 'opciones');
    }
    
    aplicarFiltros() {
        const filtroPeriodo = document.getElementById('filtro-periodo')?.value || '';
        const filtroRubro = document.getElementById('filtro-rubro')?.value || '';
        
        console.log('Aplicando filtros - Período:', filtroPeriodo, 'Rubro:', filtroRubro);
        
        this.datosFiltrados = this.datosHistorial.filter(item => {
            const cumplePeriodo = !filtroPeriodo || item.periodo === filtroPeriodo;
            const cumpleRubro = !filtroRubro || (item.rubro && item.rubro.toLowerCase().includes(filtroRubro.toLowerCase()));
            return cumplePeriodo && cumpleRubro;
        });
        
        console.log('Resultados filtrados:', this.datosFiltrados.length, 'de', this.datosHistorial.length);
        
        this.paginaActual = 1;
        this.actualizarResumen();
        
        if (this.datosFiltrados.length > 0) {
            this.mostrarTabla();
        } else {
            this.mostrarVacio();
        }
    }
    
    limpiarFiltros() {
        const filtroPeriodo = document.getElementById('filtro-periodo');
        const filtroRubro = document.getElementById('filtro-rubro');
        
        if (filtroPeriodo) filtroPeriodo.value = '';
        if (filtroRubro) filtroRubro.value = '';
        
        this.aplicarFiltros();
    }
    
    actualizarResumen() {
        const periodos = new Set(this.datosFiltrados.map(item => item.periodo));
        const rubros = new Set(this.datosFiltrados.map(item => item.rubro).filter(r => r && r !== 'SIN RUBRO'));
        const categorias = new Set(this.datosFiltrados.map(item => item.categoria));
        const stockTotal = this.datosFiltrados.reduce((sum, item) => sum + parseInt(item.cantidad || 0), 0);
        
        this.actualizarElemento('total-periodos', periodos.size);
        this.actualizarElemento('total-rubros', rubros.size);
        this.actualizarElemento('total-categorias', categorias.size);
        this.actualizarElemento('stock-total', stockTotal.toLocaleString());
        
        console.log('Resumen actualizado - Períodos:', periodos.size, 'Rubros:', rubros.size, 'Categorías:', categorias.size, 'Stock Total:', stockTotal);
    }
    
    actualizarElemento(id, valor) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = valor;
        } else {
            console.warn('Elemento no encontrado:', id);
        }
    }
    
    mostrarTabla() {
        if (this.datosFiltrados.length === 0) {
            this.mostrarVacio();
            return;
        }
        
        console.log('Mostrando tabla con', this.datosFiltrados.length, 'registros');
        
        // Calcular paginación
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
        const fin = inicio + this.itemsPorPagina;
        const datosPagina = this.datosFiltrados.slice(inicio, fin);
        
        console.log('Página actual:', this.paginaActual, 'Mostrando registros', inicio + 1, 'a', Math.min(fin, this.datosFiltrados.length));
        
        // Generar filas
        const tbody = document.getElementById('tbody-historial');
        if (!tbody) {
            console.error('No se encontró tbody-historial');
            return;
        }
        
        tbody.innerHTML = '';
        
        datosPagina.forEach((item, index) => {
            const fila = this.crearFilaHistorial(item);
            tbody.appendChild(fila);
        });
        
        // Mostrar tabla y ocultar estados vacíos
        this.mostrarElemento('contenedor-tabla-historial', true);
        this.mostrarElemento('historial-vacio', false);
        
        // Actualizar paginación
        this.actualizarPaginacion();
    }
    
    crearFilaHistorial(item) {
        const fila = document.createElement('tr');
        const esEditable = item.periodo === this.periodoActual;
        
        fila.innerHTML = `
            <td>
                <strong>${this.formatearPeriodo(item.periodo)}</strong>
            </td>
            <td>${item.rubro || 'N/A'}</td>
            <td>${item.categoria || 'N/A'}</td>
            <td class="text-end">
                <strong>${parseInt(item.cantidad || 0).toLocaleString()}</strong>
            </td>
            <td>
                <small class="text-muted">${this.formatearFecha(item.fecha_carga)}</small>
            </td>
            <td class="text-center">
                <span class="badge ${esEditable ? 'bg-success' : 'bg-secondary'}">
                    ${esEditable ? 'Editable' : 'Histórico'}
                </span>
            </td>
            <td class="text-center">
                ${esEditable ? 
                    `<button class="btn btn-sm btn-outline-primary" onclick="editarRegistroPersonalizado('${item.id}')" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>` : 
                    '<span class="text-muted">-</span>'
                }
            </td>
        `;
        
        return fila;
    }
    
    actualizarPaginacion() {
        const totalPaginas = Math.ceil(this.datosFiltrados.length / this.itemsPorPagina);
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina + 1;
        const fin = Math.min(this.paginaActual * this.itemsPorPagina, this.datosFiltrados.length);
        
        // Actualizar información
        this.actualizarElemento('items-mostrando', `${inicio}-${fin}`);
        this.actualizarElemento('total-items', this.datosFiltrados.length);
        
        // Generar controles de paginación
        const paginationControls = document.getElementById('pagination-controls');
        if (!paginationControls) return;
        
        paginationControls.innerHTML = '';
        
        if (totalPaginas > 1) {
            this.crearBotonPaginacion(paginationControls, this.paginaActual - 1, 'Anterior', this.paginaActual === 1);
            
            // Números de página
            for (let i = 1; i <= totalPaginas; i++) {
                if (i === 1 || i === totalPaginas || (i >= this.paginaActual - 2 && i <= this.paginaActual + 2)) {
                    this.crearBotonPaginacion(paginationControls, i, i.toString(), false, i === this.paginaActual);
                } else if (i === this.paginaActual - 3 || i === this.paginaActual + 3) {
                    const li = document.createElement('li');
                    li.className = 'page-item disabled';
                    li.innerHTML = '<span class="page-link">...</span>';
                    paginationControls.appendChild(li);
                }
            }
            
            this.crearBotonPaginacion(paginationControls, this.paginaActual + 1, 'Siguiente', this.paginaActual === totalPaginas);
        }
        
        // Mostrar paginación
        this.mostrarElemento('paginacion-historial', totalPaginas > 1, 'flex');
    }
    
    crearBotonPaginacion(container, pagina, texto, disabled, active = false) {
        const li = document.createElement('li');
        li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
        
        const link = document.createElement('a');
        link.className = 'page-link';
        link.href = '#';
        link.textContent = texto;
        
        if (!disabled) {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.cambiarPagina(pagina);
            });
        }
        
        li.appendChild(link);
        container.appendChild(li);
    }
    
    cambiarPagina(pagina) {
        const totalPaginas = Math.ceil(this.datosFiltrados.length / this.itemsPorPagina);
        
        if (pagina >= 1 && pagina <= totalPaginas) {
            this.paginaActual = pagina;
            this.mostrarTabla();
        }
    }
    
    toggleModoCompacto(compacto) {
        const tabla = document.getElementById('tabla-historial');
        if (tabla) {
            if (compacto) {
                tabla.classList.add('table-sm');
            } else {
                tabla.classList.remove('table-sm');
            }
        }
    }
    
    mostrarLoading() {
        this.mostrarElemento('loading-historial', true);
        this.mostrarElemento('contenedor-tabla-historial', false);
        this.mostrarElemento('historial-vacio', false);
    }
    
    ocultarLoading() {
        this.mostrarElemento('loading-historial', false);
    }
    
    mostrarVacio() {
        this.mostrarElemento('historial-vacio', true);
        this.mostrarElemento('contenedor-tabla-historial', false);
        this.mostrarElemento('paginacion-historial', false);
    }
    
    mostrarElemento(id, mostrar, display = 'block') {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.style.display = mostrar ? display : 'none';
        } else {
            console.warn('Elemento no encontrado para mostrar/ocultar:', id);
        }
    }
    
    mostrarError(mensaje) {
        this.mostrarAlerta(mensaje, 'danger');
        console.error('Error:', mensaje);
    }
    
    mostrarAlerta(mensaje, tipo = 'info') {
        let container = document.getElementById('alertas-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'alertas-container';
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            container.style.maxWidth = '400px';
            document.body.appendChild(container);
        }
        
        const alertId = `alert-${Date.now()}`;
        const alertHTML = `
            <div class="alert alert-${tipo} alert-dismissible fade show" role="alert" id="${alertId}" style="margin-bottom: 10px;">
                <strong>${this.getTituloAlerta(tipo)}</strong> ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        `;
        
        container.insertAdjacentHTML('afterbegin', alertHTML);
        
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
    
    getTituloAlerta(tipo) {
        const titulos = {
            'success': 'Éxito:',
            'danger': 'Error:',
            'warning': 'Advertencia:',
            'info': 'Información:'
        };
        return titulos[tipo] || 'Aviso:';
    }
    
    formatearPeriodo(periodo) {
        if (!periodo) return 'N/A';
        
        const [mes, año] = periodo.split('-');
        const meses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        
        const mesNum = parseInt(mes);
        if (mesNum >= 1 && mesNum <= 12) {
            return `${meses[mesNum - 1]} ${año}`;
        }
        
        return periodo;
    }
    
    formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        try {
            const date = new Date(fecha);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return 'N/A';
        }
    }
    
    obtenerPeriodoActual() {
        const fecha = new Date();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const año = fecha.getFullYear();
        return `${mes}-${año}`;
    }
}

// Configurar eventos del modal personalizado
function configurarEventosModal() {
    const cantidadInput = document.getElementById('edit-cantidad-personalizado');
    
    if (cantidadInput) {
        cantidadInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                guardarEdicionPersonalizada();
            }
        });
    }
    
    // Event listener global para Escape
    document.addEventListener('keydown', function(event) {
        const modalVisible = document.getElementById('modal-overlay')?.classList.contains('show');
        
        if (event.key === 'Escape' && modalVisible) {
            cerrarModalPersonalizado();
        }
    });
}

// Funciones para el modal personalizado
function editarRegistroPersonalizado(id) {
    console.log('Editando registro con modal personalizado:', id);
    
    if (!historialManager || !historialManager.datosHistorial) {
        alert('Error: Sistema no inicializado correctamente');
        return;
    }
    
    const registro = historialManager.datosHistorial.find(item => item.id == id);
    
    if (!registro) {
        alert('Registro no encontrado');
        return;
    }
    
    if (registro.periodo !== historialManager.periodoActual) {
        alert('Solo puede editar registros del período actual');
        return;
    }
    
    // Llenar el formulario personalizado
    document.getElementById('edit-id-personalizado').value = registro.id;
    document.getElementById('edit-periodo-personalizado').value = historialManager.formatearPeriodo(registro.periodo);
    document.getElementById('edit-rubro-personalizado').value = registro.rubro || 'N/A';
    document.getElementById('edit-categoria-personalizado').value = registro.categoria || 'N/A';
    document.getElementById('edit-cantidad-personalizado').value = registro.cantidad;
    
    // Mostrar modal personalizado
    const overlay = document.getElementById('modal-overlay');
    const modal = document.getElementById('modal-editar-personalizado');
    
    if (overlay && modal) {
        overlay.style.display = 'block';
        modal.style.display = 'block';
        overlay.classList.add('show');
        modal.classList.add('show');
        
        // Prevenir scroll del fondo
        document.body.style.overflow = 'hidden';
        
        // Focus en el input de cantidad
        setTimeout(() => {
            const cantidadInput = document.getElementById('edit-cantidad-personalizado');
            if (cantidadInput) {
                cantidadInput.focus();
                cantidadInput.select();
            }
        }, 100);
    }
}

function cerrarModalPersonalizado() {
    const overlay = document.getElementById('modal-overlay');
    const modal = document.getElementById('modal-editar-personalizado');
    
    if (overlay && modal) {
        overlay.classList.remove('show');
        modal.classList.remove('show');
        
        // Restaurar scroll del fondo
        document.body.style.overflow = '';
        
        // Ocultar después de la animación
        setTimeout(() => {
            overlay.style.display = 'none';
            modal.style.display = 'none';
        }, 300);
        
        // Limpiar el formulario
        const form = document.getElementById('form-editar-personalizado');
        if (form) {
            form.reset();
        }
        
        const idInput = document.getElementById('edit-id-personalizado');
        if (idInput) {
            idInput.value = '';
        }
    }
}

function guardarEdicionPersonalizada() {
    const id = document.getElementById('edit-id-personalizado').value;
    const cantidad = document.getElementById('edit-cantidad-personalizado').value;
    
    if (!cantidad || cantidad < 0) {
        alert('Debe ingresar una cantidad válida');
        document.getElementById('edit-cantidad-personalizado').focus();
        return;
    }
    
    const btnGuardar = document.getElementById('btn-guardar-personalizado');
    const btnCancelar = document.querySelector('#modal-editar-personalizado .btn-secondary');
    
    // Deshabilitar botones durante el proceso
    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Guardando...';
    }
    if (btnCancelar) {
        btnCancelar.disabled = true;
    }
    
    fetch('controller/historial_stock_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `accion=editar_stock&id=${encodeURIComponent(id)}&cantidad=${encodeURIComponent(cantidad)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            cerrarModalPersonalizado();
            
            // Recargar datos
            if (historialManager) {
                historialManager.cargarHistorial();
            }
            
            // Mostrar mensaje de éxito
            if (historialManager && typeof historialManager.mostrarAlerta === 'function') {
                historialManager.mostrarAlerta('Stock actualizado correctamente', 'success');
            } else {
                alert('Stock actualizado correctamente');
            }
        } else {
            alert(data.message || 'Error al actualizar el stock');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, intente nuevamente.');
    })
    .finally(() => {
        // Rehabilitar botones
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="bi bi-check me-1"></i>Guardar Cambios';
        }
        if (btnCancelar) {
            btnCancelar.disabled = false;
        }
    });
}

// Funciones globales
function actualizarHistorial() {
    if (historialManager) {
        historialManager.mostrarAlerta('Actualizando datos...', 'info');
        historialManager.cargarHistorial();
    }
}

function exportarHistorial() {
    if (!historialManager || !historialManager.datosFiltrados.length) {
        alert('No hay datos para exportar');
        return;
    }
    
    // Crear datos CSV
    const headers = ['Período', 'Rubro', 'Categoría', 'Cantidad', 'Fecha Carga'];
    const datos = [headers];
    
    historialManager.datosFiltrados.forEach(item => {
        datos.push([
            historialManager.formatearPeriodo(item.periodo),
            item.rubro || 'N/A',
            item.categoria || 'N/A',
            item.cantidad || '0',
            historialManager.formatearFecha(item.fecha_carga)
        ]);
    });
    
    // Exportar a Excel usando XLSX directamente
    if (typeof XLSX !== 'undefined') {
        try {
            console.log('Exportando historial a Excel...');
            
            // Crear libro de trabajo
            const wb = XLSX.utils.book_new();
            
            // Crear hoja de trabajo con los datos
            const ws = XLSX.utils.aoa_to_sheet(datos);
            
            // Aplicar formato a las cabeceras
            if (datos.length > 0) {
                for (let col = 0; col < datos[0].length; col++) {
                    const cellRef = XLSX.utils.encode_cell({c: col, r: 0});
                    if (!ws[cellRef]) continue;
                    ws[cellRef].s = {
                        font: { bold: true },
                        fill: { fgColor: { rgb: "E6F3FF" } }
                    };
                }
            }
            
            // Agregar la hoja al libro
            XLSX.utils.book_append_sheet(wb, ws, 'Historial de Stock');
            
            // Generar y descargar el archivo
            XLSX.writeFile(wb, `historial_stock_${new Date().toISOString().split('T')[0]}.xlsx`);
            
            console.log('Historial exportado a Excel exitosamente');
            
        } catch (error) {
            console.error('Error al exportar historial a Excel:', error);
            // Fallback a CSV en caso de error
            exportarCSV();
        }
    } else {
        console.warn('XLSX no disponible, exportando a CSV');
        exportarCSV();
    }
    
    function exportarCSV() {
        const csvContent = datos.map(fila => 
            fila.map(celda => `"${celda}"`).join(',')
        ).join('\n');
        
        const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', `historial_stock_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    if (historialManager) {
        historialManager.mostrarAlerta('Archivo descargado correctamente', 'success');
    }
}

function limpiarFiltros() {
    if (historialManager) {
        historialManager.limpiarFiltros();
    }
}