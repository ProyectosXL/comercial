$(document).ready(function() {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiIndicadoresURL = 'api/indicadores_handler.php';
    let myChart = null;

    // 1. Cargar Sucursales (Igual que en index)
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function(data) {
        $('#sucursalSelector').append('<option value="">Seleccione...</option>');
        $.each(data, (key, suc) => $('#sucursalSelector').append(`<option value="${suc.NRO_SUCURSAL}">${suc.DESC_SUCURSAL}</option>`));
    });

    // 2. Función de Fecha (ISO 8601 - Regla del 4 de Enero)
    function getPrimerDiaDeSemana(year, week) {
        const d = new Date(year, 0, 4);
        const day = d.getDay() || 7; 
        d.setDate(d.getDate() - day + 1);
        d.setDate(d.getDate() + ((week - 1) * 7));
        return d;
    }

    // 3. Evento Filtrar
    $('#btnFiltrar').on('click', function() {
        const sucursal = $('#sucursalSelector').val();
        const semana = $('#semanaSelector').val();

        if (!sucursal || !semana) return alert("Seleccione sucursal y semana.");

        // Calcular fechas
        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);
        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);

        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        // Llamar API
        $.getJSON(`${apiIndicadoresURL}?sucursal=${sucursal}&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`, function(data) {
            renderDashboard(data);
        }).fail(function() {
            alert("Error al obtener indicadores.");
        });
    });

    function renderDashboard(data) {
        $('#kpiContainer, #chartsContainer').fadeIn();

        // A. Calcular Totales KPI
        let totalN = 0, totalE = 0, totalD = 0, granTotal = 0;
        let labels = [];
        let dataN = [];
        let dataE = [];
        let dataD = [];
        let tableHTML = '';

        data.forEach(item => {
            // Sumar KPIs
            totalN += item.normal;
            totalE += item.extra;
            totalD += item.devuelve;
            granTotal += item.total;

            // Preparar Gráfico
            labels.push(item.nombre);
            dataN.push(item.normal);
            dataE.push(item.extra);
            dataD.push(item.devuelve);

            // Llenar Tabla
            tableHTML += `
                <tr>
                    <td class="text-start small fw-bold text-truncate" style="max-width: 120px;" title="${item.nombre}">${item.nombre}</td>
                    <td>${item.normal}</td>
                    <td class="fw-bold text-primary">${item.extra}</td>
                    <td class="fw-bold text-warning">${item.devuelve}</td>
                    <td class="fw-bold">${item.total}</td>
                </tr>
            `;
        });

        // Actualizar KPIs en pantalla
        $('#kpiNormal').text(totalN);
        $('#kpiExtra').text(totalE);
        $('#kpiDevuelve').text(totalD);
        $('#kpiTotal').text(granTotal);
        $('#tablaDetalleBody').html(tableHTML);

        // B. Renderizar Gráfico (Chart.js)
        const ctx = document.getElementById('graficoVendedores').getContext('2d');
        
        if (myChart) {
            myChart.destroy(); // Destruir gráfico anterior si existe
        }

        myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Normales',
                        data: dataN,
                        backgroundColor: '#198754', // Success Green
                    },
                    {
                        label: 'Extras',
                        data: dataE,
                        backgroundColor: '#0d6efd', // Primary Blue
                    },
                    {
                        label: 'Devuelve',
                        data: dataD,
                        backgroundColor: '#ffc107', // Warning Yellow
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            }
        });
    }
});