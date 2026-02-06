$(document).ready(function() {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiBancoURL = 'api/banco_horas_handler.php';
    const apiExportarURL = 'api/exportar_banco_horas.php';
    const apiVendedoresURL = 'api/obtener_vendedores_banco.php';
    let bancoChart = null;
    
    // Cargar sucursales para banco de horas
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function(data) {
        $('#bancoSucursalSelector').empty().append('<option value="">-- Todas las Sucursales --</option>');
        $.each(data, (key, suc) => {
            $('#bancoSucursalSelector').append(`<option value="${suc.NRO_SUCURSAL}">${suc.DESC_SUCURSAL}</option>`);
        });
    });
    
    // Cuando cambia la sucursal, cargar vendedores con movimientos
    $('#bancoSucursalSelector').change(function() {
        const sucursalId = $(this).val();
        $('#bancoVendedorSelector').prop('disabled', !sucursalId);
        
        if (sucursalId) {
            cargarVendedoresConMovimientos(sucursalId);
        } else {
            $('#bancoVendedorSelector').empty().append('<option value="">-- Seleccione Sucursal --</option>');
        }
        
        // Limpiar datos anteriores
        limpiarDatosBanco();
    });
    
    // Evento para filtrar banco de horas
    $('#bancoBtnFiltrar').click(function() {
        const sucursal = $('#bancoSucursalSelector').val();
        const vendedor = $('#bancoVendedorSelector').val();
        const fechaInicio = $('#bancoFechaInicio').val() || obtenerFechaInicioDefault();
        const fechaFin = $('#bancoFechaFin').val() || obtenerFechaFinDefault();
        
        if (!sucursal) {
            mostrarMensaje('warning', 'Seleccione una sucursal.');
            return;
        }
        
        // Mostrar cargando
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Buscando...');
        
        // Limpiar datos anteriores
        limpiarDatosBanco();
        
        // Obtener resumen
        obtenerResumenBanco(sucursal, vendedor);
        
        // Obtener datos para gráfico
        obtenerGraficoBanco(sucursal, vendedor, fechaInicio, fechaFin);
        
        // Obtener movimientos
        obtenerMovimientosBanco(sucursal, vendedor, fechaInicio, fechaFin);
    });
    
    // Evento para exportar
    $('#bancoBtnExportar').click(function() {
        if ($(this).hasClass('disabled')) return;
        
        const sucursal = $('#bancoSucursalSelector').val();
        const vendedor = $('#bancoVendedorSelector').val();
        const fechaInicio = $('#bancoFechaInicio').val() || obtenerFechaInicioDefault();
        const fechaFin = $('#bancoFechaFin').val() || obtenerFechaFinDefault();
        
        if (!sucursal) {
            mostrarMensaje('warning', 'Seleccione una sucursal para exportar.');
            return;
        }
        
        let url = `${apiExportarURL}?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`;
        if (sucursal) url += `&sucursal=${sucursal}`;
        if (vendedor) url += `&vendedor=${vendedor}`;
        
        window.location.href = url;
    });
    
    function cargarVendedoresConMovimientos(sucursalId) {
        $('#bancoVendedorSelector').empty().append('<option value="">-- Cargando vendedores... --</option>');
        
        $.getJSON(`${apiVendedoresURL}?sucursal=${sucursalId}`, function(data) {
            $('#bancoVendedorSelector').empty().append('<option value="">-- Todos los Vendedores --</option>');
            $.each(data, function(key, vendedor) {
                $('#bancoVendedorSelector').append(`<option value="${vendedor.id}">${vendedor.text}</option>`);
            });
        }).fail(function() {
            $('#bancoVendedorSelector').empty().append('<option value="">-- Error al cargar --</option>');
        });
    }
    
    function obtenerResumenBanco(sucursal, vendedor) {
        let url = `${apiBancoURL}?tipo=resumen`;
        if (sucursal) url += `&sucursal=${sucursal}`;
        if (vendedor) url += `&vendedor=${vendedor}`;
        
        $.getJSON(url, function(data) {
            if (data.error) {
                mostrarMensaje('error', data.error);
                return;
            }
            
            $('#bancoHorasAcumuladas').text(data.resumen.acumuladas);
            $('#bancoHorasUtilizadas').text(data.resumen.utilizadas);
            $('#bancoSaldoActual').text(data.resumen.saldo);
            $('#bancoPromedioMes').text(data.resumen.promedio_mes);
            
            $('#bancoResumenContainer').fadeIn();
        }).fail(function() {
            mostrarMensaje('error', 'Error al obtener el resumen del banco de horas.');
        });
    }
    
    function obtenerGraficoBanco(sucursal, vendedor, fechaInicio, fechaFin) {
        let url = `${apiBancoURL}?tipo=grafico&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`;
        if (sucursal) url += `&sucursal=${sucursal}`;
        if (vendedor) url += `&vendedor=${vendedor}`;
        
        $.getJSON(url, function(data) {
            if (data.error) {
                mostrarMensaje('error', data.error);
                return;
            }
            
            generarGraficoBanco(data.grafico);
            $('#bancoGraficoContainer').fadeIn();
        }).fail(function() {
            mostrarMensaje('error', 'Error al obtener datos para el gráfico.');
        });
    }
    
    function obtenerMovimientosBanco(sucursal, vendedor, fechaInicio, fechaFin) {
        let url = `${apiBancoURL}?tipo=movimientos&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`;
        if (sucursal) url += `&sucursal=${sucursal}`;
        if (vendedor) url += `&vendedor=${vendedor}`;
        
        $.getJSON(url, function(data) {
            if (data.error) {
                mostrarMensaje('error', data.error);
                return;
            }
            
            generarTablaMovimientos(data.movimientos);
            $('#bancoTablaContainer').fadeIn();
            $('#bancoBtnExportar').prop('disabled', false);
            
            // Habilitar botón de filtrar
            $('#bancoBtnFiltrar').prop('disabled', false).html('<i class="bi bi-search"></i> Buscar Histórico');
        }).fail(function() {
            mostrarMensaje('error', 'Error al obtener los movimientos.');
            $('#bancoBtnFiltrar').prop('disabled', false).html('<i class="bi bi-search"></i> Buscar Histórico');
        });
    }
    
    function generarGraficoBanco(datos) {
        const ctx = document.getElementById('bancoGraficoHistorico');
        if (!ctx) return;
        
        // Destruir gráfico anterior si existe
        if (bancoChart) {
            bancoChart.destroy();
            bancoChart = null;
        }
        
        bancoChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: datos.meses,
                datasets: [
                    {
                        label: 'Horas Acumuladas (P)',
                        data: datos.acumuladas,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Horas Utilizadas (D)',
                        data: datos.utilizadas,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Saldo Actual',
                        data: datos.saldos,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Horas'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Meses'
                        }
                    }
                }
            }
        });
    }
    
function generarTablaMovimientos(movimientos) {
    const tbody = $('#bancoTablaDetalleBody');
    tbody.empty();
    
    if (movimientos.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <i class="bi bi-info-circle me-2"></i>
                    No hay movimientos en el período seleccionado
                </td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    movimientos.forEach((mov, index) => {
        html += `
            <tr data-vendedor="${mov.cod_vendedor}" class="cursor-pointer" data-index="${index}">
                <td class="fw-bold">${mov.sucursal}</td>
                <td>
                    <div class="fw-bold">${mov.nombre_vendedor}</div>
                    <small class="text-muted">${mov.cod_vendedor}</small>
                </td>
                <td class="text-center text-success fw-bold">
                    <i class="bi bi-plus-circle"></i> ${mov.acumulaciones}
                </td>
                <td class="text-center text-danger fw-bold">
                    <i class="bi bi-dash-circle"></i> ${mov.utilizaciones}
                </td>
                <td class="text-center">
                    <span class="badge ${mov.saldo > 0 ? 'bg-success' : mov.saldo < 0 ? 'bg-danger' : 'bg-secondary'} fs-6">
                        ${mov.saldo}h
                    </span>
                </td>
                <td class="text-center text-muted small">${mov.ultima_fecha}</td>
            </tr>
            <tr id="detalle-${index}" style="display: none;" class="table-light">
                <td colspan="6" class="p-0 border-0">
                    <div class="p-3 small bg-light">
                        <div class="row">
                            <div class="col-md-6">
                                <strong><i class="bi bi-info-circle"></i> Resumen detallado:</strong><br>
                                • <span class="text-success">Acumulaciones (P):</span> ${mov.acumulaciones} horas<br>
                                • <span class="text-danger">Utilizaciones (D):</span> ${mov.utilizaciones} horas<br>
                                • <span class="text-primary">Saldo neto:</span> ${mov.saldo} horas
                            </div>
                            <div class="col-md-6">
                                <strong><i class="bi bi-calendar"></i> Estadísticas:</strong><br>
                                • Última actividad: ${mov.ultima_fecha}<br>
                                • Estado: <span class="badge ${mov.saldo > 0 ? 'bg-success' : mov.saldo < 0 ? 'bg-danger' : 'bg-secondary'}">
                                    ${mov.saldo > 0 ? 'Crédito disponible' : mov.saldo < 0 ? 'Déficit' : 'Balanceado'}
                                </span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
    
    // Agregar evento de clic después de crear la tabla
    $('tr[data-vendedor].cursor-pointer').off('click').on('click', function() {
        const index = $(this).data('index');
        $('#detalle-' + index).toggle();
        
        // Cambiar icono del vendedor
        const icon = $(this).find('i.bi-chevron-down, i.bi-chevron-up');
        if (icon.length) {
            if (icon.hasClass('bi-chevron-down')) {
                icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
            } else {
                icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            }
        }
    });
}
    
    function limpiarDatosBanco() {
        $('#bancoResumenContainer').hide();
        $('#bancoGraficoContainer').hide();
        $('#bancoTablaContainer').hide();
        $('#bancoTablaDetalleBody').empty();
        
        $('#bancoHorasAcumuladas').text('0');
        $('#bancoHorasUtilizadas').text('0');
        $('#bancoSaldoActual').text('0');
        $('#bancoPromedioMes').text('0');
        
        if (bancoChart) {
            bancoChart.destroy();
            bancoChart = null;
        }
    }
    
    function obtenerFechaFinDefault() {
        const hoy = new Date();
        const seisMesesAtras = new Date(hoy.getFullYear(), hoy.getMonth() + 6, 1);
        return seisMesesAtras.toISOString().split('T')[0];
    }
    
    function obtenerFechaInicioDefault() {
        return new Date().toISOString().split('T')[0];
    }
    
    function mostrarMensaje(tipo, texto) {
        let icono = '', color = '';
        switch(tipo) {
            case 'success': icono = '<i class="bi bi-check-circle-fill text-success"></i>'; color = 'bg-success'; break;
            case 'error': icono = '<i class="bi bi-exclamation-circle-fill text-danger"></i>'; color = 'bg-danger'; break;
            case 'warning': icono = '<i class="bi bi-exclamation-triangle-fill text-warning"></i>'; color = 'bg-warning'; break;
            case 'info': icono = '<i class="bi bi-info-circle-fill text-info"></i>'; color = 'bg-info'; break;
        }
        
        $('#modalMensajeHeader').removeClass().addClass('modal-header text-white ' + color);
        $('#modalMensajeTitulo').text(tipo === 'error' ? 'Error' : tipo === 'success' ? 'Éxito' : 'Información');
        $('#modalMensajeIcono').html(icono);
        $('#modalMensajeTexto').text(texto);
        $('#modalMensaje').modal('show');
    }
    
    // Inicializar fechas por defecto
    $('#bancoFechaInicio').val(obtenerFechaInicioDefault());
    $('#bancoFechaFin').val(obtenerFechaFinDefault());
});