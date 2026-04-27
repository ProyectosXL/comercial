$(document).ready(function() {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiIndicadoresURL = 'api/indicadores_handler.php';
    const apiExportarURL = 'api/exportar_indicadores.php';
    let myChart = null;

// Cargar Sucursales
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function(data) {
        // Limpiamos y agregamos opción por defecto
        $('#ind_sucursalSelector').empty().append('<option value="">-- Todas las Sucursales --</option>');
        
        $.each(data, (key, suc) => {
            // Verifica que estas propiedades existan en el console.log(data)
            $('#ind_sucursalSelector').append(`<option value="${suc.NRO_SUCURSAL}">${suc.DESC_SUCURSAL}</option>`);
        });
    });

    // --- EVENTO FILTRAR (Prefijo ind_) ---
    $('#ind_btnFiltrar').on('click', function() {
        const sucursal = $('#ind_sucursalSelector').val();
        const fechaInicio = $('#ind_fechaDesde').val();
        const fechaFin = $('#ind_fechaHasta').val();
        const tipoFiltro = $('#ind_tipoHoraFiltro').val();

        if (!fechaInicio || !fechaFin) return alert("Seleccione un rango de fechas (Desde - Hasta).");

        // Link reporte >9hs
        let urlRep = `api/reporte_exceso.php?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`;
        if(sucursal) urlRep += `&sucursal=${sucursal}`;
        $('#ind_btnDescargarReporte9hs').attr('href', urlRep);

        $('#ind_btnFiltrar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.getJSON(`${apiIndicadoresURL}?sucursal=${sucursal}&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`, function(data) {
            renderDashboard(data, tipoFiltro, sucursal);
        })
        .fail(function() { alert("Error al obtener datos."); })
        .always(function() { $('#ind_btnFiltrar').prop('disabled', false).html('<i class="bi bi-graph-up-arrow"></i> Ver Indicadores'); });
    });

    function renderDashboard(data, filtroTipo, sucursalSeleccionada) {
        $('#kpiContainer, #chartsContainer').fadeIn();

        let tH = 0, tC = 0, tP = 0, tE = 0, tD = 0, tV = 0, tAU = 0, tTotal = 0;
        let tableHTML = '';
        let dataGrafico = {}; 
        const esVistaGlobal = (sucursalSeleccionada === "");

        // HEADER DE TABLA
        let thead = `<tr>`;
        if (esVistaGlobal) thead += `<th class="text-start">Sucursal</th>`;
        thead += `<th class="text-start">Legajo</th><th class="text-start">Vendedor</th><th class="text-success">H</th><th class="text-info">C</th><th style="color:#d63384">P</th><th class="text-primary">A</th><th class="text-warning">D</th><th style="color:#6a1b9a">V</th><th class="text-secondary">AU</th><th>Total</th></tr>`;
        $('#ind_tablaDetalleBody').parent().find('thead').html(thead);

        data.forEach(item => {
            if (filtroTipo !== 'todos' && item[filtroTipo] == 0) return;

            tH += item.normal; tC += item.caja; tP += item.permiso;
            tE += item.extra; tD += item.devuelve; tV += item.vacaciones; tAU += item.ausencia; tTotal += item.total;

            tableHTML += `<tr>`;
            if (esVistaGlobal) tableHTML += `<td class="text-start small text-muted">${item.sucursal_nombre}</td>`;
            tableHTML += `<td class="text-start small">${item.legajo_real}</td>
                <td class="text-start small fw-bold text-truncate" style="max-width: 150px;" title="${item.nombre}">${item.nombre}</td>
                <td class="text-success">${item.normal}</td><td class="text-info fw-bold">${item.caja}</td>
                <td class="fw-bold" style="color:#d63384">${item.permiso}</td><td class="text-primary fw-bold">${item.extra}</td>
                <td class="text-warning fw-bold">${item.devuelve}</td><td class="fw-bold" style="color:#6a1b9a">${item.vacaciones}</td>
                <td class="text-secondary fw-bold">${item.ausencia}</td><td class="fw-bold">${item.total}</td></tr>`;

            let keyGrafico = esVistaGlobal ? item.sucursal_nombre : item.nombre;
            if (!dataGrafico[keyGrafico]) dataGrafico[keyGrafico] = { h:0, c:0, p:0, e:0, d:0, v:0, au:0 };
            dataGrafico[keyGrafico].h += item.normal;
            dataGrafico[keyGrafico].c += item.caja;
            dataGrafico[keyGrafico].p += item.permiso;
            dataGrafico[keyGrafico].e += item.extra;
            dataGrafico[keyGrafico].d += item.devuelve;
            dataGrafico[keyGrafico].v += item.vacaciones;
            dataGrafico[keyGrafico].au += item.ausencia;
        });

        // Actualizar KPIs
        $('#ind_kpiNormal').text(tH); $('#ind_kpiCaja').text(tC); $('#ind_kpiPermiso').text(tP);
        $('#ind_kpiExtra').text(tE); $('#ind_kpiDevuelve').text(tD); $('#ind_kpiVacaciones').text(tV); 
        $('#ind_kpiAusencia').text(tAU); $('#ind_kpiTotal').text(tTotal);
        
        $('#ind_tablaDetalleBody').html(tableHTML || '<tr><td colspan="10" class="text-center py-3">No hay datos.</td></tr>');

        // Botones
        if (tTotal > 0) {
            $('#ind_btnDescargarReporte9hs').removeClass('disabled');
            $('#ind_btnExportarDetalle').removeClass('disabled');
            $('#ind_btnExportarCajas').removeClass('disabled');
        } else {
            $('#ind_btnDescargarReporte9hs').addClass('disabled');
            $('#ind_btnExportarDetalle').addClass('disabled');
            $('#ind_btnExportarCajas').addClass('disabled');
        }

        // Gráfico
        let labels = Object.keys(dataGrafico);
        let datasetH=[], datasetC=[], datasetP=[], datasetE=[], datasetD=[], datasetV=[], datasetAU=[];

        // Filtro visual gráfico
        if (filtroTipo==='todos' || filtroTipo==='normal') datasetH = labels.map(k => dataGrafico[k].h);
        if (filtroTipo==='todos' || filtroTipo==='caja') datasetC = labels.map(k => dataGrafico[k].c);
        if (filtroTipo==='todos' || filtroTipo==='permiso') datasetP = labels.map(k => dataGrafico[k].p);
        if (filtroTipo==='todos' || filtroTipo==='extra') datasetE = labels.map(k => dataGrafico[k].e);
        if (filtroTipo==='todos' || filtroTipo==='devuelve') datasetD = labels.map(k => dataGrafico[k].d);
        if (filtroTipo==='todos' || filtroTipo==='vacaciones') datasetV = labels.map(k => dataGrafico[k].v);
        if (filtroTipo==='todos' || filtroTipo==='ausencia') datasetAU = labels.map(k => dataGrafico[k].au);

        const ctx = document.getElementById('ind_graficoVendedores').getContext('2d');
        if (myChart) myChart.destroy();

        const tituloGrafico = esVistaGlobal ? 'Horas Totales por Sucursal' : 'Horas por Vendedor';
        $('#ind_graficoTitulo').text(tituloGrafico);

myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'H (normal)', data: datasetH, backgroundColor: '#198754' },
                    { label: 'C (caja)', data: datasetC, backgroundColor: '#0dcaf0' },
                    { label: 'P (permiso)', data: datasetP, backgroundColor: '#d63384' },
                    { label: 'A (adicional)', data: datasetE, backgroundColor: '#0d6efd' },
                    { label: 'D (devuelve)', data: datasetD, backgroundColor: '#ffc107' },
                    { label: 'V (vacaciones)', data: datasetV, backgroundColor: '#6a1b9a' },
                    { label: 'AU (ausencia)', data: datasetAU, backgroundColor: '#6c757d' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: tituloGrafico },
                    
                    // --- MAGIA DEL TOOLTIP DETALLADO ---
                    tooltip: {
                        mode: 'index', // Muestra todos los datos de la barra vertical
                        intersect: false,
                        callbacks: {
                            // Cambia el título para que no muestre la sucursal/vendedor dos veces
                            title: function() { return ''; }, 
                            
                            // Formatea cada línea del tooltip
                            label: function(tooltipItem) {
                                return `  ${tooltipItem.dataset.label}: ${tooltipItem.raw}`;
                            },
                            
                            // Agrega el TOTAL al final
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
    $('#ind_btnExportarDetalle').on('click', function() {
        if ($(this).hasClass('disabled')) return;
        const sucursal = $('#ind_sucursalSelector').val();
        const fechaInicio = $('#ind_fechaDesde').val();
        const fechaFin = $('#ind_fechaHasta').val();
        const tipoFiltro = $('#ind_tipoHoraFiltro').val();

        let url = `${apiExportarURL}?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}&tipo=${tipoFiltro}`;
        if (sucursal) url += `&sucursal=${sucursal}`;
        window.location.href = url;
    });

    // REPORTE HORAS CAJA
    $('#ind_btnExportarCajas').on('click', function() {
        if ($(this).hasClass('disabled')) return;
        const sucursal = $('#ind_sucursalSelector').val();
        const fechaInicio = $('#ind_fechaDesde').val();
        const fechaFin = $('#ind_fechaHasta').val();

        let url = `api/reporte_cajas.php?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`;
        if (sucursal) url += `&sucursal=${sucursal}`;
        window.location.href = url;
    });
});