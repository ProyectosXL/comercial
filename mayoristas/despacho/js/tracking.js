// Tracking Comprobantes - JavaScript mejorado

// Variables globales para los datos
let clientesData = [];
let transportesData = [];

// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Select2 solo si está disponible la librería
    if (typeof $.fn.select2 !== 'undefined') {
        initializeSelect2();
        loadClientesData();
        loadTransportesData();
    }
    
    // Agregar loading spinner al boxLoading
    const boxLoading = document.getElementById('boxLoading');
    if (boxLoading) {
        boxLoading.innerHTML = '<div class="loading-spinner"></div>';
    }

    // Permitir búsqueda con Enter en campos de texto
    const formInputs = document.querySelectorAll('#cliente, #transp, #guia');
    formInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                traerData();
            }
        });
    });

    // Auto-focus en el primer campo al cargar
    const primerCampo = document.querySelector('#desde');
    if (primerCampo) {
        primerCampo.focus();
    }
});

// Inicializar Select2 (opcional)
function initializeSelect2() {
    if (typeof $.fn.select2 === 'undefined') return;
    
    try {
        // Configuración para Cliente
        $('#cliente').select2({
            placeholder: 'Buscar cliente...',
            allowClear: true,
            language: {
                noResults: function() {
                    return "No se encontraron clientes";
                },
                searching: function() {
                    return "Buscando...";
                }
            },
            data: clientesData
        });

        // Configuración para Transporte
        $('#transp').select2({
            placeholder: 'Buscar transporte...',
            allowClear: true,
            language: {
                noResults: function() {
                    return "No se encontraron transportes";
                },
                searching: function() {
                    return "Buscando...";
                }
            },
            data: transportesData
        });
    } catch (error) {
        console.log('Select2 no disponible, usando inputs normales');
    }
}

// Cargar datos de clientes desde API
function loadClientesData() {
    if (typeof $.fn.select2 === 'undefined') return;
    
    let token = localStorage.getItem('token');
    
    $.ajax({
        url: 'https://app.xl.com.ar/api/despacho/clientes', 
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        success: function(response) {
            console.log('Clientes cargados:', response);
            
            
            // Procesar datos según la estructura de tu API
            clientesData = response.body.map(cliente => ({
                id: cliente.RAZON_SOCI, // Ajustar según tu estructura
                text: cliente.RAZON_SOCI 
            }));
            console.log(clientesData)
            
            // Actualizar el Select2 con los nuevos datos
            updateClienteSelect2();
        },
        error: function(xhr, status, error) {
            console.warn('Error cargando clientes:', error);
            // Usar datos de ejemplo si falla la API
            clientesData = [
                { id: 'CLIENTE001', text: 'CLIENTE EJEMPLO 1 S.A.' },
                { id: 'CLIENTE002', text: 'COMERCIAL DEL SUR LTDA.' },
                { id: 'CLIENTE003', text: 'DISTRIBUIDORA NORTE S.R.L.' }
            ];
            updateClienteSelect2();
        }
    });
}

// Cargar datos de transportes desde API
function loadTransportesData() {
    if (typeof $.fn.select2 === 'undefined') return;
    
    let token = localStorage.getItem('token');
    
    $.ajax({
        url: 'https://app.xl.com.ar/api/despacho/transportes', // Ajusta esta URL según tu API
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        success: function(response) {
            console.log('Transportes cargados:', response);
            
            // Procesar datos según la estructura de tu API
            transportesData = response.body.map(transporte => ({
                id: transporte.NOMBRE_TRA,
                text: transporte.NOMBRE_TRA
            }));
            
            // Actualizar el Select2 con los nuevos datos
            updateTransporteSelect2();
        },
        error: function(xhr, status, error) {
            console.warn('Error cargando transportes:', error);
            // Usar datos de ejemplo si falla la API
            transportesData = [
                { id: 'ANDREANI', text: 'ANDREANI' },
                { id: 'OCA', text: 'OCA' },
                { id: 'CORREO_ARG', text: 'CORREO ARGENTINO' },
                { id: 'VIA_CARGO', text: 'VIA CARGO' },
                { id: 'EXPRESO_Singer', text: 'EXPRESO SINGER' }
            ];
            updateTransporteSelect2();
        }
    });
}

// Función auxiliar para actualizar Select2 de clientes
function updateClienteSelect2() {
    try {
        if ($('#cliente').hasClass('select2-hidden-accessible')) {
            $('#cliente').select2('destroy');
        }
        
        $('#cliente').select2({
            placeholder: 'Buscar cliente...',
            allowClear: true,
            data: clientesData,
            language: {
                noResults: function() {
                    return "No se encontraron clientes";
                },
                searching: function() {
                    return "Buscando...";
                }
            }
        });
    } catch (error) {
        console.log('Error inicializando Select2 para clientes:', error);
    }
}

// Función auxiliar para actualizar Select2 de transportes
function updateTransporteSelect2() {
    try {
        if ($('#transp').hasClass('select2-hidden-accessible')) {
            $('#transp').select2('destroy');
        }
        
        $('#transp').select2({
            placeholder: 'Buscar transporte...',
            allowClear: true,
            data: transportesData,
            language: {
                noResults: function() {
                    return "No se encontraron transportes";
                },
                searching: function() {
                    return "Buscando...";
                }
            }
        });
    } catch (error) {
        console.log('Error inicializando Select2 para transportes:', error);
    }
}

const traerData = () => {
    // Obtener referencias a los elementos
    let spinner = document.getElementById("boxLoading");
    spinner.className = " loading";

    let btnBuscar = document.querySelector('.btn-search');
    let btnText = btnBuscar.innerHTML;

    // Mostrar spinner en el botón
    btnBuscar.disabled = true;
    btnBuscar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Buscando...';

    // Obtener valores del formulario - usar Select2 si existe, sino input normal
    let desde = document.querySelector('#desde').value;
    let hasta = document.querySelector('#hasta').value;
    
    // Validar rango de fechas para evitar timeout del servidor
    const fechaDesde = new Date(desde);
    const fechaHasta = new Date(hasta);
    const diffTime = Math.abs(fechaHasta - fechaDesde);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    // Límite máximo de días para consulta (aumentado con timeout de 120s)
    const maxDiasConsulta = 31;
    
    if (diffDays > maxDiasConsulta) {
        // Restaurar estado del botón
        spinner.classList.remove("loading");
        btnBuscar.disabled = false;
        btnBuscar.innerHTML = btnText;
        
        alert(`El rango de fechas es demasiado amplio (${diffDays} días).\n\nPor favor, seleccione un rango máximo de ${maxDiasConsulta} días para evitar problemas de rendimiento.`);
        return;
    }
    
    let cliente = $('#cliente').length && $('#cliente').hasClass('select2-hidden-accessible') ? $('#cliente').val() : document.querySelector('#cliente').value;
    let estado = document.querySelector('#estado').value;
    let vendedor = document.querySelector('#vendedor').value;
    let transp = $('#transp').length && $('#transp').hasClass('select2-hidden-accessible') ? $('#transp').val() : document.querySelector('#transp').value;
    let guia = document.querySelector('#guia').value;
    let token = localStorage.getItem('token');

    // Guardar valores originales para filtrado en frontend
    let filtroEstadoOriginal = estado;
    let filtroClienteOriginal = cliente;
    let filtroVendedorOriginal = vendedor;
    let filtroTranspOriginal = transp;
    let filtroGuiaOriginal = guia;

    // Procesar valores vacíos (el backend maneja el LIKE internamente)
    cliente = cliente != '' ? cliente : '%';
    estado = estado != '' ? estado : '%';
    vendedor = vendedor != '' ? vendedor : '%';
    transp = transp != '' ? transp : '%';
    guia = guia != '' ? guia : '%';

    // Debug: mostrar parámetros que se enviarán
    console.log('Parámetros a enviar:', {
        desde, hasta, cliente, estado, vendedor, transp, guia, token: token ? 'presente' : 'ausente'
    });
    
    $.ajax({
        url: 'https://app.xl.com.ar/api/despacho/tracking',
        method: 'POST',
        contentType: "application/json",
        dataType: "json",
        timeout: 120000, // Timeout de 120 segundos (2 minutos) para consultas grandes
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            desde: desde,
            hasta: hasta,
            cliente: cliente,
            estado: estado,
            vendedor: vendedor,
            transp: transp,
            guia: guia
        }),
        success: function (data) {
            console.log('Respuesta recibida:', data);
            var table = document.querySelector("#tableBody");
            
            // Destruir DataTable existente correctamente
            if ($.fn.DataTable.isDataTable('#tablaTracking')) {
                $('#tablaTracking').DataTable().destroy();
            }

            if(data.body && data.body.length > 0){
                table.innerHTML = '';
                
                console.log('Datos recibidos:', data);
                console.log('Total registros del backend:', data.body.length);
                
                // Filtrar datos en frontend para corregir problema del backend (OR ESTADO IS NULL)
                let datosFiltrados = data.body.filter(function(registro) {
                    // Filtrar por estado si fue seleccionado
                    if (filtroEstadoOriginal !== '' && registro.ESTADO !== filtroEstadoOriginal) {
                        return false;
                    }
                    
                    // Filtrar por vendedor si fue seleccionado
                    if (filtroVendedorOriginal !== '' && registro.COD_VENDED !== filtroVendedorOriginal) {
                        return false;
                    }
                    
                    // Filtrar por cliente si fue ingresado (búsqueda parcial case-insensitive)
                    if (filtroClienteOriginal !== '' && registro.RAZON_SOCI) {
                        if (!registro.RAZON_SOCI.toUpperCase().includes(filtroClienteOriginal.toUpperCase())) {
                            return false;
                        }
                    }
                    
                    // Filtrar por transporte si fue ingresado (búsqueda parcial case-insensitive)
                    if (filtroTranspOriginal !== '' && registro.NOMBRE_TRA) {
                        if (!registro.NOMBRE_TRA.toUpperCase().includes(filtroTranspOriginal.toUpperCase())) {
                            return false;
                        }
                    }
                    
                    // Filtrar por guía si fue ingresado (búsqueda exacta)
                    if (filtroGuiaOriginal !== '' && registro.NRO_GUIA) {
                        if (registro.NRO_GUIA.toString() !== filtroGuiaOriginal.toString()) {
                            return false;
                        }
                    }
                    
                    return true;
                });
                
                console.log('Registros después de filtrado frontend:', datosFiltrados.length);
                
                // Verificar si hay registros después del filtrado
                if (datosFiltrados.length === 0) {
                    table.innerHTML = '<tr><td colspan="11" class="text-center py-4"><i class="bi bi-info-circle"></i> No se encontraron registros que coincidan con los filtros seleccionados</td></tr>';
                    
                    // Restaurar estado del spinner y botón
                    spinner.classList.remove("loading");
                    btnBuscar.disabled = false;
                    btnBuscar.innerHTML = btnText;
                    return;
                }
                
                datosFiltrados.forEach(function(v) {
                    var row = table.insertRow();

                    // Aplicar estilos originales
                    if (v['DIAS'] > 7 && v['ESTADO'] == 'CONTACTADO') {
                        row.style.fontWeight = "bold";
                        row.style.color = "#FE2E2E";
                    } else if (v['DIAS_GUIA'] > 2 && v['ESTADO'] != 'DESPACHADO') {
                        row.style.fontWeight = "bold";
                        row.style.color = "#FE9A2E";
                    }

                    // Crear celdas (lógica original)
                    var cell;
                    cell = row.insertCell(); cell.innerHTML = v['FECHA_COMP'] || '';
                    cell = row.insertCell(); cell.innerHTML = v['N_COMP'] || '';
                    cell = row.insertCell(); cell.innerHTML = v['COD_VENDED'] || '';
                    cell = row.insertCell(); cell.innerHTML = v['NOMBRE_TRA'] || '';
                    cell = row.insertCell(); cell.innerHTML = v['RAZON_SOCI'] || '';

                    cell = row.insertCell();
                    cell.innerHTML = (v['FECHA_GUIA'] === '1900-01-01 00:00:00' || !v['FECHA_GUIA']) ? '' : v['FECHA_GUIA'];
                
                    cell = row.insertCell();
                    cell.innerHTML = (v['NRO_GUIA'] === '0' || !v['NRO_GUIA']) ? '' : v['NRO_GUIA'];
                
                    cell = row.insertCell();
                    cell.innerHTML = (v['BULTOS'] === '0' || !v['BULTOS']) ? '' : v['BULTOS'];
                
                    cell = row.insertCell();
                    cell.innerHTML = v['IMPORTE'] ? '$ ' + parseNumber(v['IMPORTE']) : '';
                
                    cell = row.insertCell();
                    cell.innerHTML = (v['FECHA_DESPACHO'] === '1900-01-01 00:00:00' || !v['FECHA_DESPACHO']) ? '' : v['FECHA_DESPACHO'];
                
                    cell = row.insertCell();
                    if (v['ESTADO'] == 'CONTACTADO') {
                        cell.innerHTML = v['ESTADO'];
                        if (v['FECHA_CONTACTO']) {
                            cell.setAttribute("title", `Fecha Contacto: ${v['FECHA_CONTACTO']}`);
                            cell.setAttribute("data-toggle", "tooltip");
                        }
                    } else {
                        cell.innerHTML = v['ESTADO'] || '';
                    }
                });
                
                // Inicializar tooltips con manejo de errores
                try {
                    if (typeof $.fn.tooltip !== 'undefined') {
                        $('[data-toggle="tooltip"]').tooltip();
                    }
                } catch (error) {
                    console.warn('Error inicializando tooltips:', error);
                }
 
                // Inicializar DataTable con configuración original (con pequeña pausa)
                setTimeout(function() {
                    try {
                        if ($.fn.DataTable.isDataTable('#tablaTracking')) {
                            $('#tablaTracking').DataTable().destroy();
                        }
                        $('#tablaTracking').DataTable({
                            "bLengthChange": true,
                            "language": {
                                "lengthMenu": "mostrar _MENU_ registros",
                                "info": "Mostrando registros del _START_ al _END_ de un total de  _TOTAL_ registros",
                                "paginate": {
                                    "next": "Siguiente",
                                    "previous": "Anterior"
                                },
                            },
                            "bInfo": true,
                            "aaSorting": false,
                            'columnDefs': [
                                {
                                    "targets": "_all", 
                                    "className": "text-center",
                                    "sortable": false,
                                },
                            ],
                            "oLanguage": {
                                "sSearch": "Busqueda rapida:",
                                "sSearchPlaceholder": "Sobre cualquier campo"
                            },
                        });
                    } catch (error) {
                        console.warn('Error inicializando DataTable:', error);
                    }
                }, 100);

            } else {
                table.innerHTML = '';
                console.log('No se encontraron datos o estructura incorrecta:', data);
                alert('No se encontraron registros');
            }

            // Restaurar estado original del spinner y botón SIEMPRE
            spinner.classList.remove("loading");
            btnBuscar.disabled = false;
            btnBuscar.innerHTML = btnText;
        },
        error: function(xhr, status, error) {
            console.error('Error en la consulta:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            console.error('Status Code:', xhr.status);
            
            // Restaurar estado en caso de error
            spinner.classList.remove("loading");
            btnBuscar.disabled = false;
            btnBuscar.innerHTML = btnText;
            
            // Mostrar mensaje de error más detallado
            let errorMsg = 'Error al cargar los datos.';
            if (xhr.status === 0) {
                errorMsg += ' No se pudo conectar al servidor (CORS/Red).';
            } else if (xhr.status === 401) {
                errorMsg += ' Token de autorización inválido.';
            } else if (xhr.status === 404) {
                errorMsg += ' Endpoint no encontrado.';
            } else if (xhr.status >= 500) {
                errorMsg += ' Error del servidor.';
            }
            errorMsg += ` (${xhr.status})`;
            
            alert(errorMsg);
        }
    });
}

// Función para formatear números
const parseNumber = (number) => {
    number = parseInt(number);
    let newNumber = number.toLocaleString('de-De', {
        style: 'decimal',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });
    return newNumber;
}

// Función para mostrar mensajes elegantes
const showMessage = (message, type = 'info') => {
    // Crear elemento de mensaje si no existe
    let messageContainer = document.getElementById('messageContainer');
    if (!messageContainer) {
        messageContainer = document.createElement('div');
        messageContainer.id = 'messageContainer';
        messageContainer.style.position = 'fixed';
        messageContainer.style.top = '20px';
        messageContainer.style.right = '20px';
        messageContainer.style.zIndex = '10000';
        document.body.appendChild(messageContainer);
    }

    // Crear mensaje
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show`;
    messageDiv.style.minWidth = '300px';
    messageDiv.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;

    messageContainer.appendChild(messageDiv);

    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 5000);
}

// Función para limpiar formulario
const limpiarFiltros = () => {
    // Limpiar Select2 si existe, sino usar input normal
    if ($('#cliente').length && $('#cliente').hasClass('select2-hidden-accessible')) {
        $('#cliente').val('').trigger('change');
    } else {
        document.querySelector('#cliente').value = '';
    }
    
    if ($('#transp').length && $('#transp').hasClass('select2-hidden-accessible')) {
        $('#transp').val('').trigger('change');
    } else {
        document.querySelector('#transp').value = '';
    }
    
    // Limpiar campos regulares
    document.querySelector('#estado').value = '';
    document.querySelector('#vendedor').value = '';
    document.querySelector('#guia').value = '';
    
    // Establecer fechas al día actual
    const hoy = new Date().toISOString().split('T')[0];
    document.querySelector('#desde').value = hoy;
    document.querySelector('#hasta').value = hoy;
    
    if (typeof showMessage === 'function') {
        showMessage('Filtros limpiados correctamente', 'success');
    }
}