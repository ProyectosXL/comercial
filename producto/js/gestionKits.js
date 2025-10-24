
// ===== VARIABLES GLOBALES =====
let articulosTemporales = [];
let dataTableKits = null;
let kitActual = null; // { codKit, descripcion }

// ===== INICIALIZACIÓN =====
$(document).ready(function() {
    inicializarSelect2();
    inicializarEventos();
    
    // Si estamos en tab de listado, inicializar DataTable
    if ($('#tabListado').hasClass('active')) {
        inicializarDataTable();
    }
});

// ===== INICIALIZACIÓN DE SELECT2 =====
function inicializarSelect2() {
    // Select2 para Kits
    $('#selectKit').select2({
        placeholder: 'Buscar kit por código...',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: 'Controller/KitsController.php?accion=search-kits',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        language: {
            inputTooShort: function() {
                return 'Ingrese al menos 1 carácter';
            },
            searching: function() {
                return 'Buscando...';
            },
            noResults: function() {
                return 'No se encontraron resultados';
            }
        }
    });

    // Evento cuando se selecciona un kit
    $('#selectKit').on('select2:select', function(e) {
        const codKit = e.params.data.id;
        const descripcion = e.params.data.descripcion || '';
        
        // Mostrar descripción
        $('#descripcionKit').html(`<span class="font-medium text-gray-800">${descripcion}</span>`);
        
        // Cargar artículos del kit
        cargarArticulosDelKit(codKit, descripcion);
    });

    // Evento cuando se limpia el kit
    $('#selectKit').on('select2:clear', function() {
        limpiarKit();
    });
}

// ===== CARGAR ARTÍCULOS DE UN KIT ESPECÍFICO =====
function cargarArticulosDelKit(codKit, descripcion) {
    // Validar si ya existe este kit en la tabla
    if (kitActual && kitActual.codKit === codKit) {
        mostrarError('Este kit ya está cargado en la tabla');
        $('#selectKit').val(null).trigger('change');
        return;
    }
    
    mostrarLoading();
    
    $.ajax({
        url: 'Controller/KitsController.php?accion=get-articulos-by-kit',
        method: 'GET',
        data: { codKit: codKit },
        dataType: 'json',
        success: function(response) {
            cerrarModalLoading();
            
            if (response.results && response.results.length > 0) {
                // Guardar kit actual
                kitActual = {
                    codKit: codKit,
                    descripcion: descripcion
                };
                
                // Limpiar array temporal
                articulosTemporales = [];
                
                // Agregar todos los artículos al array temporal
                response.results.forEach(function(item) {
                    articulosTemporales.push({
                        codKit: codKit,
                        codArticu: item.COD_ARTICU,
                        descripcion: item.DESCRIP_ARTICU,
                        multiplo: item.MULTIPLO || 1
                    });
                });
                
                // Actualizar tabla
                actualizarTablaTemporal();
                
                mostrarToast(`${response.results.length} artículo(s) cargado(s) correctamente`, 'success');
            } else {
                kitActual = null;
                $('#descripcionKit').html('<span class="text-gray-400 italic">Seleccione un kit para ver su descripción</span>');
                $('#selectKit').val(null).trigger('change');
                mostrarError('Este kit no tiene artículos asociados en STA03');
            }
        },
        error: function() {
            cerrarModalLoading();
            kitActual = null;
            $('#descripcionKit').html('<span class="text-gray-400 italic">Seleccione un kit para ver su descripción</span>');
            $('#selectKit').val(null).trigger('change');
            mostrarError('Error al cargar los artículos del kit');
        }
    });
}

// ===== INICIALIZACIÓN DE EVENTOS =====
function inicializarEventos() {
    // Filtros en tab de listado
    $('#filtroKit, #filtroEstado').on('change', function() {
        if (dataTableKits) {
            dataTableKits.ajax.reload();
        }
    });

    // Event delegation para botones de toggle en DataTable
    $('#tableKits').on('click', '.btn-toggle-kit', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const inhabilitar = $(this).data('inhabilitar');
        console.log('Toggle button clicked:', { id, inhabilitar });
        toggleKit(id, inhabilitar);
    });

    // Escape para cerrar modales
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalLoading();
        }
    });
}

// ===== CAMBIO DE TABS =====
function cambiarTab(tab) {
    // Remover active de todos
    $('.tab-button').removeClass('active');
    $('.tab-content').removeClass('active').hide();
    
    // Activar seleccionado
    if (tab === 'alta') {
        $('#btnTabAlta').addClass('active');
        $('#tabAlta').addClass('active').show();
    } else if (tab === 'listado') {
        $('#btnTabListado').addClass('active');
        $('#tabListado').addClass('active').show();
        
        // Inicializar DataTable si no existe
        if (!dataTableKits) {
            inicializarDataTable();
        } else {
            dataTableKits.ajax.reload();
        }
    }
}

// ===== LIMPIAR KIT =====
function limpiarKit() {
    // Limpiar kit actual
    kitActual = null;
    
    // Limpiar array temporal
    articulosTemporales = [];
    
    // Actualizar tabla
    actualizarTablaTemporal();
    
    // Limpiar select kit
    $('#selectKit').val(null).trigger('change');
    
    // Restaurar descripción
    $('#descripcionKit').html('<span class="text-gray-400 italic">Seleccione un kit para ver su descripción</span>');
    
    mostrarToast('Kit limpiado correctamente', 'info');
}

// ===== ACTUALIZAR TABLA TEMPORAL =====
function actualizarTablaTemporal() {
    const tbody = $('#tbodyTemporal');
    tbody.empty();

    if (articulosTemporales.length === 0) {
        tbody.append(`
            <tr id="rowEmpty">
                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2 opacity-50"></i>
                    <p>Seleccione un kit para ver sus artículos</p>
                </td>
            </tr>
        `);
        $('#btnConfirmar').prop('disabled', true);
        $('#btnLimpiar').prop('disabled', true);
        $('#contadorArticulos').text('0 artículos');
        return;
    }

    articulosTemporales.forEach((item, index) => {
        const row = `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 text-gray-800 font-mono">${item.codKit}</td>
                <td class="px-4 py-3 text-gray-800 font-mono">${item.codArticu}</td>
                <td class="px-4 py-3 text-gray-600">${item.descripcion}</td>
                <td class="px-4 py-3 text-center">
                    <input type="number" 
                           value="${item.multiplo}" 
                           min="1" 
                           data-index="${index}"
                           onchange="actualizarMultiplo(${index}, this.value)"
                           class="w-20 px-2 py-1 text-center border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    // Habilitar botones
    $('#btnConfirmar').prop('disabled', false);
    $('#btnLimpiar').prop('disabled', false);
    
    // Actualizar contador
    $('#contadorArticulos').text(`${articulosTemporales.length} artículo${articulosTemporales.length > 1 ? 's' : ''}`);

    // Reinicializar tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
}

// ===== ACTUALIZAR MÚLTIPLO =====
function actualizarMultiplo(index, nuevoValor) {
    const valor = parseInt(nuevoValor);
    
    if (!valor || valor < 1) {
        mostrarError('El múltiplo debe ser mayor o igual a 1');
        // Restaurar valor anterior
        $(`input[data-index="${index}"]`).val(articulosTemporales[index].multiplo);
        return;
    }
    
    // Actualizar en el array
    articulosTemporales[index].multiplo = valor;
    
    mostrarToast('Múltiplo actualizado', 'success');
}

// ===== CONFIRMAR KITS =====
function confirmarKits() {
    if (articulosTemporales.length === 0) {
        mostrarError('No hay artículos para confirmar');
        return;
    }

    if (!kitActual) {
        mostrarError('No hay kit seleccionado');
        return;
    }

    // Primero verificar si el kit ya existe en la base de datos
    mostrarLoading();
    
    $.ajax({
        url: 'Controller/KitsController.php?accion=verificar-kit',
        method: 'GET',
        data: { codKit: kitActual.codKit },
        dataType: 'json',
        success: function(response) {
            cerrarModalLoading();
            
            if (response.existe) {
                // El kit ya existe, mostrar advertencia
                Swal.fire({
                    title: 'Kit ya existe',
                    html: `
                        <p>El kit <strong>${kitActual.codKit}</strong> ya tiene artículos registrados en la base de datos.</p>
                        <p>Los artículos duplicados serán omitidos.</p>
                        <p>¿Desea continuar?</p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    background: '#fff',
                    color: '#374151'
                }).then((result) => {
                    if (result.isConfirmed) {
                        guardarKits();
                    }
                });
            } else {
                // El kit no existe, proceder normalmente
                const cantidadArticulos = articulosTemporales.length;

                Swal.fire({
                    title: '¿Confirmar alta?',
                    html: `
                        <p>Se insertarán:</p>
                        <ul style="text-align: left; margin: 1rem auto; max-width: 300px;">
                            <li>Kit: <strong>${kitActual.codKit}</strong></li>
                            <li><strong>${cantidadArticulos}</strong> artículo${cantidadArticulos > 1 ? 's' : ''}</li>
                        </ul>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, confirmar',
                    cancelButtonText: 'Cancelar',
                    background: '#fff',
                    color: '#374151'
                }).then((result) => {
                    if (result.isConfirmed) {
                        guardarKits();
                    }
                });
            }
        },
        error: function() {
            cerrarModalLoading();
            mostrarError('Error al verificar el kit');
        }
    });
}

// ===== GUARDAR KITS =====
function guardarKits() {
    mostrarLoading();

    // Preparar datos para enviar
    const items = articulosTemporales.map(item => ({
        codArticu: item.codArticu,
        multiplo: item.multiplo
    }));

    const data = {
        codKit: kitActual.codKit,
        items: items
    };

    $.ajax({
        url: 'Controller/KitsController.php?accion=add',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            cerrarModalLoading();

            if (response.ok) {
                // Verificar si se insertó algún registro
                if (response.inserted === 0 && response.duplicates && response.duplicates.length > 0) {
                    // Todos eran duplicados
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kit ya existe',
                        html: `
                            <p>Todos los artículos del kit <strong>${kitActual.codKit}</strong> ya estaban registrados en la base de datos.</p>
                            <p>Se omitieron <strong>${response.duplicates.length}</strong> registro(s) duplicado(s).</p>
                            <p>No se realizaron cambios.</p>
                        `,
                        background: '#fff',
                        color: '#374151',
                        confirmButtonColor: '#f59e0b'
                    });
                } else {
                    // Se insertaron algunos o todos los registros
                    let mensaje = `Se insertaron ${response.inserted} registro(s) correctamente`;
                    if (response.duplicates && response.duplicates.length > 0) {
                        mensaje += `\nSe omitieron ${response.duplicates.length} duplicado(s)`;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: mensaje,
                        background: '#fff',
                        color: '#374151',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        // Limpiar formulario
                        limpiarKit();
                        
                        // Recargar datatable si existe
                        if (dataTableKits) {
                            dataTableKits.ajax.reload();
                        }
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    background: '#fff',
                    color: '#374151'
                });
            }
        },
        error: function(xhr, status, error) {
            cerrarModalLoading();
            console.error('Error:', error);
            mostrarError('Error al guardar los kits');
        }
    });
}

// ===== INICIALIZAR DATATABLE =====
function inicializarDataTable() {
    // Destruir DataTable si ya existe
    if ($.fn.DataTable.isDataTable('#tableKits')) {
        $('#tableKits').DataTable().destroy();
    }
    
    dataTableKits = $('#tableKits').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'Controller/KitsController.php?accion=list',
            type: 'GET',
            data: function(d) {
                d.filtroKit = $('#filtroKit').val() || '';
                d.filtroEstado = $('#filtroEstado').val() || '';
                console.log('DataTable request:', d);
            },
            dataSrc: function(json) {
                console.log('DataTable response:', json);
                console.log('Data array:', json.data);
                console.log('Data length:', json.data ? json.data.length : 0);
                
                if (json.error) {
                    console.error('Error from server:', json.error);
                    mostrarError('Error al cargar datos: ' + json.error);
                    return [];
                }
                
                // Actualizar estadísticas
                if (json.data && json.data.length > 0) {
                    actualizarEstadisticas(json.data, json.recordsTotal || 0);
                } else {
                    console.warn('No data returned from server');
                    $('#statTotal').text('0');
                    $('#statHabilitados').text('0');
                    $('#statInhabilitados').text('0');
                    $('#statKitsUnicos').text('0');
                }
                
                return json.data || [];
            },
            error: function(xhr, error, thrown) {
                console.error('DataTable AJAX error:', error, thrown);
                console.error('Response:', xhr.responseText);
                mostrarError('Error al cargar la tabla de kits');
            }
        },
        columns: [
            { 
                data: 'ID',
                className: 'text-center'
            },
            { 
                data: 'COD_KIT',
                className: 'font-mono'
            },
            { 
                data: 'COD_ARTICU',
                className: 'font-mono'
            },
            { 
                data: 'DESCRIPCIO'
            },
            { 
                data: 'MULTIPLO',
                className: 'text-center font-semibold'
            },
            { 
                data: 'INHABILITADO',
                className: 'text-center',
                render: function(data) {
                    if (data == 0) {
                        return '<span class="badge-habilitado"><i class="fas fa-check-circle mr-1"></i>Habilitado</span>';
                    } else {
                        return '<span class="badge-inhabilitado"><i class="fas fa-times-circle mr-1"></i>Inhabilitado</span>';
                    }
                }
            },
            { 
                data: 'FECHA_INHA',
                className: 'text-center',
                render: function(data) {
                    return data || '<span class="text-gray-400">—</span>';
                }
            },
            {
                data: null,
                className: 'text-center',
                orderable: false,
                render: function(data, type, row) {
                    const esHabilitado = row.INHABILITADO == 0;
                    const btnClass = esHabilitado ? 'btn-toggle' : 'bg-green-600 hover:bg-green-700';
                    const btnIcon = esHabilitado ? 'fa-toggle-off' : 'fa-toggle-on';
                    const btnTitle = esHabilitado ? 'Inhabilitar' : 'Habilitar';
                    const inhabilitar = !esHabilitado ? 0 : 1;

                    return `
                        <button class="btn-action ${btnClass} text-white btn-toggle-kit" 
                                data-id="${row.ID}" 
                                data-inhabilitar="${inhabilitar}"
                                data-bs-toggle="tooltip" title="${btnTitle}">
                            <i class="fas ${btnIcon}"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: 'Procesando...',
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros totales)',
            loadingRecords: 'Cargando...',
            zeroRecords: 'No se encontraron registros',
            emptyTable: 'No hay datos disponibles',
            paginate: {
                first: 'Primero',
                previous: 'Anterior',
                next: 'Siguiente',
                last: 'Último'
            }
        },
        drawCallback: function() {
            // Reinicializar tooltips después de cada redibujado
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
}

// ===== TOGGLE KIT (HABILITAR/INHABILITAR) =====
function toggleKit(id, inhabilitar) {
    const accion = inhabilitar ? 'inhabilitar' : 'habilitar';
    
    Swal.fire({
        title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)}?`,
        text: `¿Está seguro de ${accion} este registro?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: inhabilitar ? '#f59e0b' : '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar',
        background: '#fff',
        color: '#374151'
    }).then((result) => {
        if (result.isConfirmed) {
            mostrarLoading();
            
            console.log('toggleKit - Enviando:', { id: id, inhabilitar: inhabilitar });

            fetch('Controller/KitsController.php?accion=toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    inhabilitar: inhabilitar
                })
            })
            .then(response => {
                console.log('toggleKit - Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('toggleKit - Response data:', data);
                cerrarModalLoading();

                if (data.ok) {
                    mostrarToast(data.message, 'success');
                    if (dataTableKits) {
                        dataTableKits.ajax.reload(null, false); // Recargar sin perder página
                    }
                } else {
                    mostrarError(data.message || 'Error al actualizar');
                }
            })
            .catch(error => {
                cerrarModalLoading();
                console.error('toggleKit - Error:', error);
                mostrarError('Error al actualizar: ' + error.message);
            });
        }
    });
}

// ===== LIMPIAR FILTROS =====
function limpiarFiltros() {
    $('#filtroKit').val('');
    $('#filtroEstado').val('');
    if (dataTableKits) {
        dataTableKits.ajax.reload();
    }
}

// ===== ACTUALIZAR ESTADÍSTICAS =====
function actualizarEstadisticas(data, total) {
    let habilitados = 0;
    let inhabilitados = 0;
    const kitsUnicos = new Set();

    data.forEach(item => {
        if (item.INHABILITADO == 0) {
            habilitados++;
        } else {
            inhabilitados++;
        }
        kitsUnicos.add(item.COD_KIT);
    });

    // Actualizar estadísticas en pantalla
    $('#statTotal').text(total || data.length);
    $('#statHabilitados').text(habilitados);
    $('#statInhabilitados').text(inhabilitados);
    $('#statKitsUnicos').text(kitsUnicos.size);
}

// ===== HELPERS - LOADING =====
function mostrarLoading() {
    $('#loadingOverlay').removeClass('hidden');
}

function cerrarModalLoading() {
    $('#loadingOverlay').addClass('hidden');
}

// ===== HELPERS - TOASTS =====
function mostrarToast(mensaje, tipo = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        background: '#fff',
        color: '#374151'
    });

    Toast.fire({
        icon: tipo,
        title: mensaje
    });
}

function mostrarError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje,
        background: '#fff',
        color: '#374151',
        confirmButtonColor: '#ef4444'
    });
}