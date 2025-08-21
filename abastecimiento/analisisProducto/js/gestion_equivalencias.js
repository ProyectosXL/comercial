/**
 * Script para gestión de artículos equivalentes - Versión corregida
 * Archivo: js/gestion_equivalencias.js
 */

$(document).ready(function() {
    // Variables globales
    let dataTable;
    let articulosGrupo = [];
    let accionConfirmacion = null;
    
    // Inicializar componentes
    initComponents();
    
    // Cargar datos iniciales
    cargarGrupos();
});

/**
 * Inicializar componentes de la página
 */
function initComponents() {
    // Inicializar DataTable
    initDataTable();
    
    // Inicializar Select2
    initSelect2();
    
    // Configurar eventos
    setupEventListeners();

    // Cargar rubros
    cargarRubros();
}

    /**
     * Inicializar DataTable
     */
    function initDataTable() {
        dataTable = $('#tablaGrupos').DataTable({
            pageLength: 25,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            columnDefs: [
                {
                    targets: [2], // Rubro
                    width: "200px",
                    className: "rubro-columna"
                },
                {
                    targets: [3], // Total Artículos
                    render: function(data, type, row) {
                        return data ? Number(data) : '0';
                    }
                },
                {
                    targets: [4], // Fecha Creación
                    render: function(data, type, row) {
                        if (type === 'display') {
                            return data ? formatearFecha(data) : '-';
                        }
                        return data;
                    },
                    className: 'fecha-columna'
                },
                {
                    targets: [5], // Fecha Modificación
                    render: function(data, type, row) {
                        if (type === 'display') {
                            return data ? formatearFecha(data) : '-';
                        }
                        return data;
                    },
                    className: 'fecha-columna'
                },
                {
                    targets: [6], // Estado
                    render: function(data, type, row) {
                        return data == 1 ? 
                            '<span class="badge badge-activo">Activo</span>' : 
                            '<span class="badge badge-inactivo">Inactivo</span>';
                    }
                },
                {
                    targets: [7], // Acciones
                    orderable: false,
                    render: function(data, type, row) {
                        return generarBotonesAccion(row);
                    }
                }
            ],
            columns: [
                { data: 'COD_EQUIVALENCIA', title: 'Cód. Equiv.' },
                { data: 'DESCRIPCION_GRUPO', title: 'Nombre Familia' },
                { data: 'RUBRO', title: 'Rubro' },
                { data: 'TOTAL_ARTICULOS', title: 'Cant. Art. Grupo' },
                { data: 'FECHA_CREACION', title: 'Fecha Creación' },
                { data: 'FECHA_MODIFICACION', title: 'Fecha Modificación' },
                { data: 'ACTIVO', title: 'Estado' },
                { data: null, title: 'Acciones', defaultContent: '' }
            ],
            order: [[0, 'desc']], // Ordenar por código descendente
            data: [],
            autoWidth: false,
            scrollX: true
        });
    }

/**
 * Inicializar Select2 - CORREGIDO
 */
function initSelect2() {
    // Verificar si Select2 ya está inicializado antes de destruir
    if ($('#buscarArticulo').hasClass('select2-hidden-accessible')) {
        $('#buscarArticulo').select2('destroy');
    }
    if ($('#nuevoArticulo').hasClass('select2-hidden-accessible')) {
        $('#nuevoArticulo').select2('destroy');
    }
    
    // Configuración común para Select2
    const select2Config = {
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Buscar artículo por código o descripción...',
        allowClear: true,
        minimumInputLength: 2,
        language: {
            inputTooShort: function () {
                return 'Ingrese al menos 2 caracteres para buscar';
            },
            noResults: function () {
                return 'No se encontraron artículos';
            },
            searching: function () {
                return 'Buscando...';
            },
            loadingMore: function () {
                return 'Cargando más resultados...';
            }
        },
        ajax: {
            url: 'controller/equivalencias_controller.php',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    accion: 'buscar_articulos',
                    termino: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                console.log('Select2 response:', data);
                
                if (!data.success) {
                    console.error('Error en búsqueda:', data.message);
                    return {
                        results: [],
                        pagination: { more: false }
                    };
                }
                
                const results = (data.data || []).map(function(item) {
                    return {
                        id: item.id,
                        text: item.text || (item.id + ' - ' + item.descripcion),
                        descripcion: item.descripcion || ''
                    };
                });
                
                return {
                    results: results,
                    pagination: {
                        more: data.pagination ? data.pagination.more : false
                    }
                };
            },
            cache: true
        },
        templateResult: formatearResultadoArticulo,
        templateSelection: formatearSeleccionArticulo,
        escapeMarkup: function(markup) {
            return markup;
        }
    };

    // Inicializar Select2 para búsqueda de artículos (filtros)
    $('#buscarArticulo').select2(select2Config);
    
    // Inicializar Select2 para nuevo artículo en modal
    $('#nuevoArticulo').select2($.extend({}, select2Config, {
        dropdownParent: $('#modalGrupo'),
        placeholder: 'Seleccionar artículo para agregar al grupo...'
    }));
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Botón nuevo grupo
    $('#btnNuevoGrupo').on('click', function() {
        abrirModalGrupo();
    });
    
    // Botón buscar
    $('#btnBuscar').on('click', function() {
        buscarGrupos();
    });
    
    // Enter en campos de búsqueda
    $('#buscarGrupo').on('keypress', function(e) {
        if (e.which === 13) buscarGrupos();
    });
    
    // Agregar artículo al grupo
    $('#btnAgregarArticulo').on('click', function() {
        mostrarSelectorArticulo();
    });
    
    $('#btnConfirmarArticulo').on('click', function() {
        agregarArticuloAlGrupo();
    });
    
    $('#btnCancelarArticulo').on('click', function() {
        ocultarSelectorArticulo();
    });
    
    // Guardar grupo
    $('#btnGuardarGrupo').on('click', function() {
        guardarGrupo();
    });
    
    // Confirmación de acciones
    $('#btnConfirmarAccion').on('click', function() {
        if (accionConfirmacion) {
            accionConfirmacion();
            $('#modalConfirmacion').modal('hide');
        }
    });
    
    // Delegación de eventos para botones dinámicos
    $(document).on('click', '.btn-editar-grupo', function() {
        const codEquivalencia = $(this).data('cod');
        editarGrupo(codEquivalencia);
    });
    
    $(document).on('click', '.btn-eliminar-grupo', function() {
        const codEquivalencia = $(this).data('cod');
        confirmarEliminarGrupo(codEquivalencia);
    });
    
    $(document).on('click', '.btn-eliminar-articulo', function() {
        const indice = $(this).data('indice');
        eliminarArticuloDelGrupo(indice);
    });
    
    // Event listener para modal
    $(document).on('shown.bs.modal', '#modalGrupo', function() {
        console.log('Modal abierto, reinicializando Select2');
        setTimeout(function() {
            reinicializarSelect2Modal();
        }, 100);
    });
}

/**
 * Cargar grupos de equivalencias
 */
function cargarGrupos() {
    console.log('Iniciando carga de grupos...');
    mostrarSpinner();
    
    $.ajax({
        url: 'controller/equivalencias_controller.php',
        method: 'GET',
        data: { accion: 'listar_grupos' },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            console.log('Respuesta recibida:', response);
            ocultarSpinner();
            
            if (response.success) {
                dataTable.clear().rows.add(response.data || []).draw();
                console.log('Grupos cargados:', response.data ? response.data.length : 0);
                
                if (!response.data || response.data.length === 0) {
                    mostrarAdvertencia('No hay grupos de equivalencias. Crear el primer grupo.');
                }
            } else {
                mostrarError('Error al cargar grupos: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error completo:', {xhr, status, error});
            console.error('Response text:', xhr.responseText);
            ocultarSpinner();
            
            let errorMessage = 'Error de conexión al cargar grupos';
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    errorMessage = response.message;
                }
            } catch(e) {
                console.error('La respuesta no es JSON válido');
                errorMessage = 'Error del servidor. Status: ' + xhr.status;
            }
            
            mostrarError(errorMessage);
        }
    });
}

/**
 * Cargar rubros para los filtros y modal
 */
function cargarRubros() {
    $.ajax({
        url: 'controller/equivalencias_controller.php',
        method: 'GET',
        data: { accion: 'obtener_rubros' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Cargar rubros en filtro
                const filtroRubro = $('#filtroRubro');
                filtroRubro.empty().append('<option value="">Todos los rubros</option>');
                
                // Cargar rubros en modal
                const rubroGrupo = $('#rubroGrupo');
                rubroGrupo.empty().append('<option value="">Seleccionar rubro...</option>');
                
                response.data.forEach(function(rubro) {
                    const option = `<option value="${rubro.RUBRO}">${rubro.RUBRO}</option>`;
                    filtroRubro.append(option);
                    rubroGrupo.append(option);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar rubros:', error);
        }
    });
}

/**
 * MODIFICAR la función buscarGrupos para incluir rubro
 */
function buscarGrupos() {
    const articulo = $('#buscarArticulo').val();
    const grupo = $('#buscarGrupo').val();
    const rubro = $('#filtroRubro').val();
    const estado = $('#filtroEstado').val();
    
    mostrarSpinner();
    
    $.ajax({
        url: 'controller/equivalencias_controller.php',
        method: 'GET',
        data: {
            accion: 'buscar_grupos',
            articulo: articulo,
            grupo: grupo,
            rubro: rubro,
            estado: estado
        },
        dataType: 'json',
        success: function(response) {
            ocultarSpinner();
            if (response.success) {
                dataTable.clear().rows.add(response.data).draw();
            } else {
                mostrarError('Error en la búsqueda: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            ocultarSpinner();
            mostrarError('Error de conexión en la búsqueda');
            console.error('Error:', error);
        }
    });
}

/**
 * Abrir modal para nuevo grupo
 */
function abrirModalGrupo(codEquivalencia = null) {
    // Limpiar formulario
    $('#formGrupo')[0].reset();
    $('#codEquivalencia').val('');
    $('#accionForm').val(codEquivalencia ? 'editar' : 'crear');
    articulosGrupo = [];
    
    if (codEquivalencia) {
        $('#tituloModal').text('Editar Grupo de Equivalencias');
        cargarDatosGrupo(codEquivalencia);
    } else {
        $('#tituloModal').text('Nuevo Grupo de Equivalencias');
        actualizarTablaArticulosGrupo();
    }
    
    ocultarSelectorArticulo();
    $('#modalGrupo').modal('show');
}

/**
 * Cargar datos de un grupo para edición
 */
function cargarDatosGrupo(codEquivalencia) {
    mostrarSpinner();
    
    $.ajax({
        url: 'controller/equivalencias_controller.php',
        method: 'GET',
        data: {
            accion: 'obtener_grupo',
            cod_equivalencia: codEquivalencia
        },
        dataType: 'json',
        success: function(response) {
            ocultarSpinner();
            if (response.success) {
                const grupo = response.data;
                $('#codEquivalencia').val(grupo.COD_EQUIVALENCIA);
                $('#descripcionGrupo').val(grupo.DESCRIPCION_GRUPO);
                $('#rubroGrupo').val(grupo.RUBRO);
                $('#estadoGrupo').val(grupo.ACTIVO);
                articulosGrupo = grupo.articulos || [];
                actualizarTablaArticulosGrupo();
            } else {
                mostrarError('Error al cargar grupo: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            ocultarSpinner();
            mostrarError('Error de conexión al cargar grupo');
            console.error('Error:', error);
        }
    });
}

/**
 * Mostrar selector de artículo
 */
function mostrarSelectorArticulo() {
    $('#selectorArticulo').show();
    setTimeout(function() {
        $('#nuevoArticulo').select2('open');
    }, 100);
}

/**
 * Ocultar selector de artículo
 */
function ocultarSelectorArticulo() {
    $('#selectorArticulo').hide();
    $('#nuevoArticulo').val('').trigger('change');
}

/**
 * Agregar artículo al grupo
 */
function agregarArticuloAlGrupo() {
    const articuloSeleccionado = $('#nuevoArticulo').select2('data')[0];
    
    if (!articuloSeleccionado) {
        mostrarError('Debe seleccionar un artículo');
        return;
    }
    
    // Verificar si ya existe en el grupo
    const yaExiste = articulosGrupo.some(art => art.COD_ARTICU === articuloSeleccionado.id);
    if (yaExiste) {
        mostrarError('Este artículo ya está en el grupo');
        return;
    }
    
    // Agregar al array con RUBRO del modal
    const nuevoArticulo = {
        COD_ARTICU: articuloSeleccionado.id,
        DESCRIPCIO: articuloSeleccionado.descripcion,
        RUBRO: $('#rubroGrupo').val() // Tomar rubro del modal
    };
    
    articulosGrupo.push(nuevoArticulo);
    actualizarTablaArticulosGrupo();
    ocultarSelectorArticulo();
}

/**
 * Eliminar artículo del grupo
 */
function eliminarArticuloDelGrupo(indice) {
    if (articulosGrupo[indice]) {
        articulosGrupo.splice(indice, 1);
        actualizarTablaArticulosGrupo();
        mostrarExito('Artículo eliminado del grupo');
    }
}

/**
 * Actualizar tabla de artículos del grupo
 */
function actualizarTablaArticulosGrupo() {
    const tbody = $('#listaArticulosGrupo');
    tbody.empty();
    
    if (articulosGrupo.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                    No hay artículos en el grupo
                </td>
            </tr>
        `);
        return;
    }
    
    articulosGrupo.forEach((articulo, indice) => {
        const imagenHtml = generarImagenArticulo(articulo.COD_ARTICU, false);
        
        const fila = `
            <tr>
                <td>${imagenHtml}</td>
                <td><strong>${articulo.COD_ARTICU}</strong></td>
                <td>${articulo.DESCRIPCIO}</td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-articulo" data-indice="${indice}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        tbody.append(fila);
    });
}

/**
 * Guardar grupo
 */
function guardarGrupo() {
    if (!validarFormulario()) {
        return;
    }
    
    const datosGrupo = {
        accion: $('#accionForm').val(),
        cod_equivalencia: $('#codEquivalencia').val(),
        descripcion_grupo: $('#descripcionGrupo').val(),
        rubro: $('#rubroGrupo').val(),
        estado: $('#estadoGrupo').val(),
        articulos: articulosGrupo
    };
    
    mostrarSpinner();
    
    $.ajax({
        url: 'controller/equivalencias_controller.php',
        method: 'POST',
        data: datosGrupo,
        dataType: 'json',
        success: function(response) {
            ocultarSpinner();
            if (response.success) {
                $('#modalGrupo').modal('hide');
                cargarGrupos();
                mostrarExito(response.message);
            } else {
                mostrarError('Error al guardar: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            ocultarSpinner();
            mostrarError('Error de conexión al guardar');
            console.error('Error:', error);
        }
    });
}

/**
 * Validar formulario
 */
function validarFormulario() {
    if (!$('#descripcionGrupo').val().trim()) {
        mostrarError('La descripción del grupo es obligatoria');
        $('#descripcionGrupo').focus();
        return false;
    }
    
    if (!$('#rubroGrupo').val()) {
        mostrarError('Debe seleccionar un rubro');
        $('#rubroGrupo').focus();
        return false;
    }
    
    if (articulosGrupo.length === 0) {
        mostrarError('Debe agregar al menos un artículo al grupo');
        return false;
    }
    
    return true;
}

/**
 * Editar grupo
 */
function editarGrupo(codEquivalencia) {
    abrirModalGrupo(codEquivalencia);
}

/**
 * Confirmar eliminación de grupo
 */
function confirmarEliminarGrupo(codEquivalencia) {
    $('#mensajeConfirmacion').html(`
        <p>¿Está seguro de eliminar este grupo de equivalencias?</p>
        <p><strong>Esta acción no se puede deshacer.</strong></p>
    `);
    
    accionConfirmacion = function() {
        eliminarGrupo(codEquivalencia);
    };
    
    $('#modalConfirmacion').modal('show');
}

/**
 * Eliminar grupo
 */
function eliminarGrupo(codEquivalencia) {
    mostrarSpinner();
    
    $.ajax({
        url: 'controller/equivalencias_controller.php',
        method: 'POST',
        data: {
            accion: 'eliminar_grupo',
            cod_equivalencia: codEquivalencia
        },
        dataType: 'json',
        success: function(response) {
            ocultarSpinner();
            if (response.success) {
                cargarGrupos();
                mostrarExito('Grupo eliminado correctamente');
            } else {
                mostrarError('Error al eliminar: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            ocultarSpinner();
            mostrarError('Error de conexión al eliminar');
            console.error('Error:', error);
        }
    });
}

/**
 * Reinicializar Select2 cuando se abre el modal
 */
function reinicializarSelect2Modal() {
    // Verificar si existe antes de destruir
    if ($('#nuevoArticulo').hasClass('select2-hidden-accessible')) {
        $('#nuevoArticulo').select2('destroy');
    }
    
    $('#nuevoArticulo').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccionar artículo para agregar al grupo...',
        allowClear: true,
        minimumInputLength: 2,
        dropdownParent: $('#modalGrupo'),
        language: {
            inputTooShort: function () {
                return 'Ingrese al menos 2 caracteres para buscar';
            },
            noResults: function () {
                return 'No se encontraron artículos';
            },
            searching: function () {
                return 'Buscando...';
            }
        },
        ajax: {
            url: 'controller/equivalencias_controller.php',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    accion: 'buscar_articulos',
                    termino: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                if (!data.success) {
                    return { results: [], pagination: { more: false } };
                }
                
                const results = (data.data || []).map(function(item) {
                    return {
                        id: item.id,
                        text: item.text || (item.id + ' - ' + item.descripcion),
                        descripcion: item.descripcion || ''
                    };
                });
                
                return {
                    results: results,
                    pagination: {
                        more: data.pagination ? data.pagination.more : false
                    }
                };
            }
        },
        templateResult: formatearResultadoArticulo,
        templateSelection: formatearSeleccionArticulo,
        escapeMarkup: function(markup) { return markup; }
    });
}

// ===== FUNCIONES AUXILIARES =====

/**
 * Generar botones de acción para la tabla
 */
function generarBotonesAccion(row) {
    return `
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-primary btn-editar-grupo" data-cod="${row.COD_EQUIVALENCIA}" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-outline-danger btn-eliminar-grupo" data-cod="${row.COD_EQUIVALENCIA}" title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
}

/**
 * Generar imagen de artículo con fallback mejorado
 */
function generarImagenArticulo(codigoArticulo, esPrincipal = false) {
    const claseAdicional = esPrincipal ? ' img-principal' : '';
    
    // Sufijos de color: 01..99
    const coloresComunes = Array.from({ length: 99 }, (_, i) => String(i + 1).padStart(2, '0'));

    // Generar las rutas de imágenes a probar
    const rutasImagenes = coloresComunes.map(color => 
        `../../../Imagenes/${codigoArticulo}${color}.jpg`
    );
    
    // ID único para cada imagen
    const imageId = `img-${codigoArticulo.replace(/[^a-zA-Z0-9]/g, '')}`;
    
    return `
        <img id="${imageId}" 
             src="${rutasImagenes[0]}" 
             alt="Imagen de ${codigoArticulo}" 
             class="img-articulo${claseAdicional}"
             data-rutas='${JSON.stringify(rutasImagenes)}'
             data-indice="0"
             data-codigo="${codigoArticulo}"
             style="cursor: pointer;"
             onerror="probarSiguienteImagen('${imageId}')"
             onload="marcarImagenCargada('${imageId}')"
             onclick="mostrarImagenCompleta('${imageId}')">
        <div id="${imageId}-placeholder" class="img-placeholder" style="display: none; cursor: pointer;" 
             onclick="mostrarImagenCompleta('${imageId}')">
            Sin imagen
        </div>
    `;
}

/**
 * Formatear resultado de artículo para Select2
 */
function formatearResultadoArticulo(articulo) {
    if (articulo.loading) {
        return articulo.text;
    }
    
    if (!articulo.id) {
        return articulo.text;
    }
    
    return $(`
        <div class="d-flex align-items-center">
            <div class="me-3">
                <strong>${articulo.id}</strong>
            </div>
            <div class="flex-grow-1">
                <div>${articulo.descripcion || articulo.text}</div>
                ${articulo.color ? `<small class="text-muted">${articulo.color}</small>` : ''}
            </div>
        </div>
    `);
}

/**
 * Formatear selección de artículo para Select2
 */
function formatearSeleccionArticulo(articulo) {
    if (!articulo.id) {
        return articulo.text;
    }
    return `${articulo.id} - ${articulo.descripcion || articulo.text}`;
}

/**
 * Formatear fecha
 */
function formatearFecha(fecha) {
    if (!fecha) return '-';
    
    try {
        const date = new Date(fecha);
        
        // Verificar si la fecha es válida
        if (isNaN(date.getTime())) {
            return '-';
        }
        
        const dia = String(date.getDate()).padStart(2, '0');
        const mes = String(date.getMonth() + 1).padStart(2, '0');
        const año = date.getFullYear();
        const hora = String(date.getHours()).padStart(2, '0');
        const minuto = String(date.getMinutes()).padStart(2, '0');
        
        return `${dia}/${mes}/${año} ${hora}:${minuto}`;
        
    } catch (error) {
        console.error('Error formateando fecha:', error);
        return '-';
    }
}

/**
 * Mostrar/ocultar spinner
 */
function mostrarSpinner() {
    $('#spinner').css('display', 'flex');
}

function ocultarSpinner() {
    $('#spinner').hide();
}

/**
 * Mostrar mensajes
 */
function mostrarExito(mensaje) {
    mostrarToast(mensaje, 'success');
}

function mostrarError(mensaje) {
    mostrarToast(mensaje, 'error');
}

function mostrarAdvertencia(mensaje) {
    mostrarToast(mensaje, 'warning');
}

/**
 * NUEVA función para mostrar Toast moderno
 */
function mostrarToast(mensaje, tipo = 'info') {
    // Crear contenedor de toasts si no existe
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    // Configurar colores según tipo
    const tipos = {
        success: { bg: 'bg-success', icon: 'fas fa-check-circle' },
        error: { bg: 'bg-danger', icon: 'fas fa-exclamation-triangle' },
        warning: { bg: 'bg-warning', icon: 'fas fa-exclamation-circle' },
        info: { bg: 'bg-info', icon: 'fas fa-info-circle' }
    };
    
    const config = tipos[tipo] || tipos.info;
    const toastId = 'toast-' + Date.now();
    
    // HTML del toast
    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${config.bg} text-white">
                <i class="${config.icon} me-2"></i>
                <strong class="me-auto">Notificación</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${mensaje}
            </div>
        </div>
    `;
    
    // Agregar toast al contenedor
    document.getElementById('toast-container').insertAdjacentHTML('beforeend', toastHtml);
    
    // Mostrar toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        delay: tipo === 'error' ? 5000 : 3000 // Errores se muestran más tiempo
    });
    
    toast.show();
    
    // Eliminar del DOM después de que se oculte
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Cache para evitar repetir búsquedas
const imagenCache = new Map();

/**
 * Probar siguiente imagen cuando falla la actual
 */
function probarSiguienteImagen(imageId) {
    const img = document.getElementById(imageId);
    if (!img) return;
    
    const rutasStr = img.getAttribute('data-rutas');
    const indiceActual = parseInt(img.getAttribute('data-indice')) || 0;
    
    if (!rutasStr) {
        mostrarPlaceholder(imageId);
        return;
    }
    
    const rutas = JSON.parse(rutasStr);
    const siguienteIndice = indiceActual + 1;
    
    if (siguienteIndice < rutas.length) {
        // Probar siguiente imagen
        img.setAttribute('data-indice', siguienteIndice);
        img.src = rutas[siguienteIndice];
    } else {
        // No hay más imágenes para probar
        mostrarPlaceholder(imageId);
    }
}

/**
 * Marcar imagen como cargada exitosamente y guardar en cache
 */
function marcarImagenCargada(imageId) {
    const img = document.getElementById(imageId);
    if (!img) return;
    
    // Guardar en cache la ruta que funcionó
    const codigoBase = imageId.replace('img-', '');
    const rutaExitosa = img.src;
    imagenCache.set(codigoBase, rutaExitosa);
    
    console.log(`Imagen cargada para ${codigoBase}: ${rutaExitosa}`);
}

/**
 * Mostrar placeholder cuando no hay imagen disponible
 */
function mostrarPlaceholder(imageId) {
    const img = document.getElementById(imageId);
    const placeholder = document.getElementById(imageId + '-placeholder');
    
    if (img && placeholder) {
        img.style.display = 'none';
        placeholder.style.display = 'flex';
    }
}


/**
 * NUEVA FUNCIÓN: Mostrar imagen en modal grande
 */
function mostrarImagenCompleta(imageId) {
    const img = document.getElementById(imageId);
    const placeholder = document.getElementById(imageId + '-placeholder');
    
    let imagenSrc = '';
    let codigoArticulo = '';
    let descripcion = '';
    
    if (img && img.style.display !== 'none') {
        // Si la imagen está visible, usarla
        imagenSrc = img.src;
        codigoArticulo = img.getAttribute('data-codigo') || '';
        
        // Buscar descripción en la misma fila de la tabla
        const fila = img.closest('tr');
        if (fila) {
            const celdaDescripcion = fila.querySelector('td:nth-child(3)'); // Columna descripción
            descripcion = celdaDescripcion ? celdaDescripcion.textContent : '';
        }
    } else {
        // Si no hay imagen, mostrar placeholder en el modal
        codigoArticulo = img ? img.getAttribute('data-codigo') || '' : '';
        imagenSrc = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxOCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlbiBubyBkaXNwb25pYmxlPC90ZXh0Pjwvc3ZnPg==';
    }
    
    // Actualizar el modal
    $('#tituloImagenCompleta').text(`Imagen del Producto - ${codigoArticulo}`);
    $('#imagenCompleta').attr('src', imagenSrc);
    $('#articuloImagen').text(codigoArticulo);
    $('#descripcionImagen').text(descripcion);
    
    // Mostrar el modal
    $('#modalImagenCompleta').modal('show');
}


/**
 * MEJORAR la función para usar cache optimizada
 */
function generarImagenArticuloOptimizada(codigoArticulo, esPrincipal = false) {
    const claseAdicional = esPrincipal ? ' img-principal' : '';
    const codigoLimpio = codigoArticulo.replace(/[^a-zA-Z0-9]/g, '');
    
    // Verificar cache primero
    if (imagenCache.has(codigoLimpio)) {
        const rutaCacheada = imagenCache.get(codigoLimpio);
        const imageId = `img-${codigoLimpio}`;
        
        return `
            <img id="${imageId}" 
                 src="${rutaCacheada}" 
                 alt="Imagen de ${codigoArticulo}" 
                 class="img-articulo${claseAdicional}"
                 data-codigo="${codigoArticulo}"
                 style="cursor: pointer;"
                 onerror="mostrarPlaceholder('${imageId}')"
                 onclick="mostrarImagenCompleta('${imageId}')">
            <div id="${imageId}-placeholder" class="img-placeholder" style="display: none; cursor: pointer;" 
                 onclick="mostrarImagenCompleta('${imageId}')">
                Sin imagen
            </div>
        `;
    }
    
    // Si no está en cache, usar método normal
    return generarImagenArticulo(codigoArticulo, esPrincipal);
}

/**
 * NUEVA FUNCIÓN: Precargar imágenes de manera inteligente
 */
function precargarImagenesVisibles() {
    // Precargar imágenes que están en el viewport
    const imagenes = document.querySelectorAll('.img-articulo[data-rutas]');
    
    imagenes.forEach(img => {
        const rect = img.getBoundingClientRect();
        const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
        
        if (isVisible && !img.getAttribute('data-precargado')) {
            img.setAttribute('data-precargado', 'true');
            // La imagen ya se está cargando automáticamente
        }
    });
}

// Inicializar precargar cuando se actualiza la tabla
$(document).on('DOMNodeInserted', '#listaArticulosGrupo', function() {
    setTimeout(precargarImagenesVisibles, 100);
});

console.log('Script de gestión de equivalencias cargado correctamente');