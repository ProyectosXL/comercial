$(document).ready(function() {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiIndicadoresURL = 'api/indicadores_handler.php';
    const apiExportarURL = 'api/exportar_indicadores.php';
    let myChart = null;

    // Inicializar cuando se carga la pestaña de Indicadores
    $('#tab-indicadores').on('shown.bs.tab', function() {
        inicializarIndicadores();
    });
    
    // También inicializar si ya está activa la pestaña al cargar la página
    if ($('#tab-indicadores').hasClass('active')) {
        inicializarIndicadores();
    }
    
    function inicializarIndicadores() {
        // Cargar sucursales desde el sistema
        cargarSucursales();
        
        // Establecer semana actual
        const semanaActual = obtenerSemanaActual();
        $('#indicadoresSemanaSelector').val(semanaActual);
        
        // Mostrar botones habilitados
        $('#indicadoresBtnExportarDetalle').removeClass('disabled');
    }
    
    function cargarSucursales() {
        $.getJSON(`${apiDatosURL}?accion=sucursales`, function(data) {
            $('#indicadoresSucursalSelector').empty().append('<option value="">-- Todas las Sucursales --</option>');
            
            $.each(data, (key, suc) => {
                $('#indicadoresSucursalSelector').append(`<option value="${suc.NRO_SUCURSAL}">${suc.DESC_SUCURSAL}</option>`);
            });
            
            // Seleccionar ABASTO por defecto si existe
            const abastoOption = $('#indicadoresSucursalSelector option[value*="ABASTO"]').first();
            if (abastoOption.length) {
                abastoOption.prop('selected', true);
            }
        });
    }
    
    function obtenerSemanaActual() {
        const hoy = new Date();
        const año = hoy.getFullYear();
        const semanaNum = getWeekNumber(hoy);
        return `${año}-W${semanaNum.toString().padStart(2, '0')}`;
    }
    
    function getWeekNumber(date) {
        const firstDayOfYear = new Date(date.getFullYear(), 0, 1);
        const pastDaysOfYear = (date - firstDayOfYear) / 86400000;
        return Math.ceil((pastDaysOfYear + firstDayOfYear.getDay() + 1) / 7);
    }
    
    function getPrimerDiaDeSemana(year, week) {
        const d = new Date(year, 0, 4);
        const day = d.getDay() || 7; 
        d.setDate(d.getDate() - day + 1);
        d.setDate(d.getDate() + ((week - 1) * 7));
        return d;
    }

    // --- EVENTO FILTRAR ---
    $('#indicadoresBtnFiltrar').on('click', function() {
        const sucursal = $('#indicadoresSucursalSelector').val();
        const semana = $('#indicadoresSemanaSelector').val();
        const tipoFiltro = $('#indicadoresTipoHoraFiltro').val();

        if (!semana) {
            mostrarMensaje('error', 'Seleccione una semana.');
            return;
        }

        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);
        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);

        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        $('#indicadoresBtnFiltrar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Cargando...');

        $.getJSON(`${apiIndicadoresURL}?sucursal=${sucursal}&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`, function(data) {
            renderDashboard(data, tipoFiltro, sucursal);
        })
        .fail(function() { 
            mostrarMensaje('error', 'Error al obtener datos.');
        })
        .always(function() { 
            $('#indicadoresBtnFiltrar').prop('disabled', false).html('<i class="bi bi-graph-up-arrow"></i> Ver Indicadores'); 
        });
    });

    function renderDashboard(data, filtroTipo, sucursalSeleccionada) {
        $('#indicadoresKpiContainer, #indicadoresChartsContainer').fadeIn();

        let tH = 0, tC = 0, tP = 0, tE = 0, tD = 0, tTotal = 0;
        let tableHTML = '';
        let dataGrafico = {}; 
        const esVistaGlobal = (sucursalSeleccionada === "");

        // HEADER DE TABLA
        let thead = `<tr>`;
        if (esVistaGlobal) thead += `<th class="text-start">Sucursal</th>`;
        thead += `<th class="text-start">Vendedor</th><th class="text-success">H</th><th class="text-info">C</th><th style="color:#d63384">P</th><th class="text-primary">E</th><th class="text-warning">D</th><th>Total</th></tr>`;
        $('#indicadoresTablaDetalleBody').parent().find('thead').html(thead);

        data.forEach(item => {
            if (filtroTipo !== 'todos' && item[filtroTipo] == 0) return;

            tH += item.normal; tC += item.caja; tP += item.permiso;
            tE += item.extra; tD += item.devuelve; tTotal += item.total;

            tableHTML += `<tr>`;
            if (esVistaGlobal) tableHTML += `<td class="text-start small text-muted">${item.sucursal_nombre}</td>`;
            tableHTML += `<td class="text-start small fw-bold text-truncate" style="max-width: 150px;" title="${item.nombre}">${item.nombre}</td>
                <td class="text-success">${item.normal}</td><td class="text-info fw-bold">${item.caja}</td>
                <td class="fw-bold" style="color:#d63384">${item.permiso}</td><td class="text-primary fw-bold">${item.extra}</td>
                <td class="text-warning fw-bold">${item.devuelve}</td><td class="fw-bold">${item.total}</td></tr>`;

            let keyGrafico = esVistaGlobal ? item.sucursal_nombre : item.nombre;
            if (!dataGrafico[keyGrafico]) dataGrafico[keyGrafico] = { h:0, c:0, p:0, e:0, d:0 };
            dataGrafico[keyGrafico].h += item.normal;
            dataGrafico[keyGrafico].c += item.caja;
            dataGrafico[keyGrafico].p += item.permiso;
            dataGrafico[keyGrafico].e += item.extra;
            dataGrafico[keyGrafico].d += item.devuelve;
        });

        // Actualizar KPIs
        $('#indicadoresKpiNormal').text(tH); 
        $('#indicadoresKpiCaja').text(tC); 
        $('#indicadoresKpiPermiso').text(tP);
        $('#indicadoresKpiExtra').text(tE); 
        $('#indicadoresKpiDevuelve').text(tD); 
        $('#indicadoresKpiTotal').text(tTotal);
        
        $('#indicadoresTablaDetalleBody').html(tableHTML || '<tr><td colspan="8" class="text-center py-3">No hay datos.</td></tr>');

        // Botones
        if (tTotal > 0) {
            $('#indicadoresBtnExportarDetalle').removeClass('disabled');
        } else {
            $('#indicadoresBtnExportarDetalle').addClass('disabled');
        }

        // Gráfico
        let labels = Object.keys(dataGrafico);
        let datasetH=[], datasetC=[], datasetP=[], datasetE=[], datasetD=[];

        // Filtro visual gráfico
        if (filtroTipo==='todos' || filtroTipo==='normal') datasetH = labels.map(k => dataGrafico[k].h);
        if (filtroTipo==='todos' || filtroTipo==='caja') datasetC = labels.map(k => dataGrafico[k].c);
        if (filtroTipo==='todos' || filtroTipo==='permiso') datasetP = labels.map(k => dataGrafico[k].p);
        if (filtroTipo==='todos' || filtroTipo==='extra') datasetE = labels.map(k => dataGrafico[k].e);
        if (filtroTipo==='todos' || filtroTipo==='devuelve') datasetD = labels.map(k => dataGrafico[k].d);

        const ctx = document.getElementById('indicadoresGraficoVendedores').getContext('2d');
        if (myChart) myChart.destroy();

        const tituloGrafico = esVistaGlobal ? 'Horas Totales por Sucursal' : 'Horas por Vendedor';
        $('#indicadoresGraficoTitulo').text(tituloGrafico);

        myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'H (normal)', data: datasetH, backgroundColor: '#198754' },
                    { label: 'C (caja)', data: datasetC, backgroundColor: '#0dcaf0' },
                    { label: 'P (permiso)', data: datasetP, backgroundColor: '#d63384' },
                    { label: 'E (extra)', data: datasetE, backgroundColor: '#0d6efd' },
                    { label: 'D (devuelve)', data: datasetD, backgroundColor: '#ffc107' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: tituloGrafico },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            title: function() { return ''; },
                            label: function(tooltipItem) {
                                return `  ${tooltipItem.dataset.label}: ${tooltipItem.raw}`;
                            },
                            footer: function(tooltipItems) {
                                let total = 0;
                                tooltipItems.forEach(function(item) {
                                    total += item.raw;
                                });
                                return 'Total: ' + total;
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });
    }

    // Exportar Excel
    $('#indicadoresBtnExportarDetalle').on('click', function() {
        if ($(this).hasClass('disabled')) return;
        const sucursal = $('#indicadoresSucursalSelector').val();
        const semana = $('#indicadoresSemanaSelector').val();
        const tipoFiltro = $('#indicadoresTipoHoraFiltro').val();
        
        if (!semana) {
            mostrarMensaje('error', 'Seleccione una semana para exportar.');
            return;
        }

        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);
        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);
        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        let url = `${apiExportarURL}?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}&tipo=${tipoFiltro}`;
        if (sucursal) url += `&sucursal=${sucursal}`;
        window.location.href = url;
    });

    // Función para mostrar mensajes (reutilizable)
    function mostrarMensaje(tipo, texto) {
        let icono = '', color = '';
        switch(tipo) {
            case 'success': icono = '<i class="bi bi-check-circle-fill text-success"></i>'; color = 'bg-success'; break;
            case 'error': icono = '<i class="bi bi-exclamation-circle-fill text-danger"></i>'; color = 'bg-danger'; break;
            case 'warning': icono = '<i class="bi bi-exclamation-triangle-fill text-warning"></i>'; color = 'bg-warning'; break;
            default: icono = '<i class="bi bi-info-circle-fill text-info"></i>'; color = 'bg-info'; break;
        }
        
        $('#modalMensajeHeader').removeClass().addClass('modal-header text-white ' + color);
        $('#modalMensajeTitulo').text(tipo === 'error' ? 'Error' : tipo === 'success' ? 'Éxito' : 'Información');
        $('#modalMensajeIcono').html(icono);
        $('#modalMensajeTexto').text(texto);
        $('#modalMensaje').modal('show');
    }
});