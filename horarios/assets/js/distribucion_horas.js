$(document).ready(function () {

    // --- Inicializar fechas: primer y último día del mes actual ---
    const hoy = new Date();
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);

    function formatDate(d) {
        return d.toISOString().split('T')[0];
    }

    $('#dist_fechaDesde').val(formatDate(primerDia));
    $('#dist_fechaHasta').val(formatDate(ultimoDia));

    let datosActuales = [];

    // --- Botón Buscar ---
    $('#dist_btnFiltrar').on('click', function () {
        const desde = $('#dist_fechaDesde').val();
        const hasta = $('#dist_fechaHasta').val();

        if (!desde || !hasta) {
            alert('Seleccione ambas fechas.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Consultando...');
        $('#dist_tablaBody').html('<tr><td colspan="5" class="text-center py-4"><span class="spinner-border text-success"></span></td></tr>');
        $('#dist_resultContainer').show();

        $.getJSON('api/distribucion_horas_handler.php', {
            fechaInicio: desde,
            fechaFin: hasta
        })
        .done(function (data) {
            datosActuales = data;
            renderTabla(data);
            $('#dist_btnExportar').prop('disabled', data.length === 0).removeClass('disabled');
        })
        .fail(function () {
            $('#dist_tablaBody').html('<tr><td colspan="5" class="text-center py-4 text-danger">Error al consultar los datos.</td></tr>');
        })
        .always(function () {
            $btn.prop('disabled', false).html('<i class="bi bi-search"></i> Buscar');
        });
    });

    // --- Renderizar tabla ---
    function renderTabla(data) {
        const $body = $('#dist_tablaBody');
        $body.empty();

        if (!data || data.length === 0) {
            $body.html('<tr><td colspan="5" class="text-center py-4 text-muted">No hay datos para el período seleccionado.</td></tr>');
            return;
        }

        // Agrupar por nombre para alternar colores por persona, aplicar una línea separadora y fila de total
        let lastNombre = '';
        let lastTotal = 0;
        let colorIdx = 0;

        data.forEach(function (item) {
            let extraStyle = '';
            if (item.nombre !== lastNombre) {
                if (lastNombre !== '') {
                    // Fila de total para el vendedor anterior (ahora 6 columnas)
                    $body.append(`
                        <tr class="fw-bold">
                            <td style="background-color: #e9ecef;"></td>
                            <td class="text-start text-uppercase text-muted" style="font-size: 0.85rem; background-color: #e9ecef;">Total ${lastNombre}</td>
                            <td style="background-color: #e9ecef;"></td>
                            <td style="background-color: #e9ecef;"></td>
                            <td style="background-color: #e9ecef;">${lastTotal}</td>
                            <td style="background-color: #e9ecef;"><span class="badge bg-dark rounded-pill px-3">100%</span></td>
                        </tr>
                    `);
                    extraStyle = 'style="border-top: 2px solid #495057;"';
                }
                colorIdx++;
                lastNombre = item.nombre;
                lastTotal = item.total_vend;
            }
            const bgClass = (colorIdx % 2 === 0) ? 'bg-light' : '';

            // Color del badge de porcentaje
            let badgeClass = 'bg-secondary';
            if (item.porcentaje >= 75) badgeClass = 'bg-success';
            else if (item.porcentaje >= 40) badgeClass = 'bg-primary';
            else if (item.porcentaje >= 15) badgeClass = 'bg-warning text-dark';

            $body.append(`
                <tr class="${bgClass}">
                    <td class="fw-semibold" ${extraStyle}>${item.legajo}</td>
                    <td class="text-start" ${extraStyle}>${item.nombre}</td>
                    <td ${extraStyle}>${item.sucursal}</td>
                    <td ${extraStyle}>${item.cod_auxiliar || ''}</td>
                    <td class="fw-bold" ${extraStyle}>${item.horas}</td>
                    <td ${extraStyle}><span class="badge ${badgeClass} rounded-pill px-3">${item.porcentaje}%</span></td>
                </tr>
            `);
        });

        // Fila de total para el último vendedor (ahora 6 columnas)
        if (lastNombre !== '') {
            $body.append(`
                <tr class="fw-bold">
                    <td style="background-color: #e9ecef;"></td>
                    <td class="text-start text-uppercase text-muted" style="font-size: 0.85rem; background-color: #e9ecef;">Total ${lastNombre}</td>
                    <td style="background-color: #e9ecef;"></td>
                    <td style="background-color: #e9ecef;"></td>
                    <td style="background-color: #e9ecef;">${lastTotal}</td>
                    <td style="background-color: #e9ecef;"><span class="badge bg-dark rounded-pill px-3">100%</span></td>
                </tr>
            `);
        }
    }

    // --- Botón Exportar ---
    $('#dist_btnExportar').on('click', function () {
        const desde = $('#dist_fechaDesde').val();
        const hasta = $('#dist_fechaHasta').val();
        if (!desde || !hasta) return;

        window.location.href = 'api/exportar_distribucion_horas.php?fechaInicio=' + desde + '&fechaFin=' + hasta;
    });

});
