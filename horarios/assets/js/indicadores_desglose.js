$(document).ready(function() {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiDesgloseURL = 'api/indicadores_desglose_handler.php';
    const apiExportarURL = 'api/exportar_indicadores_desglose.php';
    let dataCache = [];

    // --- Inicializar fechas: primer y último día del mes actual ---
    const hoy = new Date();
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);

    function formatDate(d) {
        return d.toISOString().split('T')[0];
    }

    $('#ind_desg_fechaDesde').val(formatDate(primerDia));
    $('#ind_desg_fechaHasta').val(formatDate(ultimoDia));

    // Cargar Sucursales en el Multi-select
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function(data) {
        $('#ind_desg_sucursalSelector').empty();
        $.each(data, (key, suc) => {
            $('#ind_desg_sucursalSelector').append(`<option value="${suc.NRO_SUCURSAL}">${suc.DESC_SUCURSAL}</option>`);
        });

        // Inicializar Select2 en modo multi-select
        $('#ind_desg_sucursalSelector').select2({
            theme: 'bootstrap-5',
            placeholder: "Todas las sucursales...",
            allowClear: true,
            width: '100%'
        });
    });

    // --- EVENTO FILTRAR ---
    $('#ind_desg_btnFiltrar').on('click', function() {
        const sucursales = $('#ind_desg_sucursalSelector').val() || [];
        const fechaInicio = $('#ind_desg_fechaDesde').val();
        const fechaFin = $('#ind_desg_fechaHasta').val();

        if (!fechaInicio || !fechaFin) {
            alert("Seleccione un rango de fechas (Desde - Hasta).");
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Buscando...');
        $('#ind_desg_tablaDetalleBody').html('<tr><td colspan="12" class="text-center py-4"><span class="spinner-border text-success"></span> Cargando datos...</td></tr>');
        $('#ind_desg_tablaContainer').show();

        // Convertir array de sucursales a string separado por comas para la query
        const sucursalesParam = sucursales.join(',');

        $.getJSON(`${apiDesgloseURL}?sucursales=${sucursalesParam}&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`, function(data) {
            dataCache = data;
            renderTabla();
        })
        .fail(function() {
            $('#ind_desg_tablaDetalleBody').html('<tr><td colspan="12" class="text-center py-3 text-danger">Error al obtener los datos.</td></tr>');
        })
        .always(function() {
            $btn.prop('disabled', false).html('<i class="bi bi-search"></i> Buscar');
        });
    });

    // --- RENDERIZAR TABLA CON FILTRADO LOCAL Y COLAPSO ---
    function renderTabla() {
        const buscarPersona = $('#ind_desg_buscarPersona').val().toLowerCase().trim();
        const buscarTabla = $('#ind_desg_buscadorTabla').val().toLowerCase().trim();
        const buscar = buscarTabla || buscarPersona; // Usa el que tenga texto
        
        let tableHTML = '';
        let tH = 0, tC = 0, tP = 0, tE = 0, tD = 0, tV = 0, tAU = 0, tTotal = 0;

        // Filtrar localmente por persona
        const filteredData = dataCache.filter(item => {
            if (!buscar) return true;
            const nombre = (item.nombre || '').toLowerCase();
            const legajo = (item.legajo_real || '').toLowerCase();
            return nombre.includes(buscar) || legajo.includes(buscar);
        });

        // Agrupar por vendedor
        const groupedByVendor = {};
        filteredData.forEach(item => {
            const key = item.legajo_real;
            if (!groupedByVendor[key]) {
                groupedByVendor[key] = {
                    legajo: item.legajo_real,
                    nombre: item.nombre,
                    sucursal: item.sucursal_nombre,
                    normal: 0, caja: 0, permiso: 0, extra: 0, devuelve: 0, vacaciones: 0, ausencia: 0, total: 0,
                    days: []
                };
            }
            groupedByVendor[key].normal += item.normal;
            groupedByVendor[key].caja += item.caja;
            groupedByVendor[key].permiso += item.permiso;
            groupedByVendor[key].extra += item.extra;
            groupedByVendor[key].devuelve += item.devuelve;
            groupedByVendor[key].vacaciones += item.vacaciones;
            groupedByVendor[key].ausencia += item.ausencia;
            groupedByVendor[key].total += item.total;
            groupedByVendor[key].days.push(item);
        });

        const vendors = Object.values(groupedByVendor);

        vendors.forEach(v => {
            tH += v.normal; 
            tC += v.caja; 
            tP += v.permiso;
            tE += v.extra; 
            tD += v.devuelve; 
            tV += v.vacaciones; 
            tAU += v.ausencia; 
            tTotal += v.total;

            // Fila resumen del vendedor (clickable)
            tableHTML += `<tr class="vendor-summary-row fw-bold" data-legajo="${v.legajo}" style="cursor: pointer;">
                <td class="text-start small">${v.sucursal}</td>
                <td class="text-start small"><i class="bi bi-chevron-right me-2 toggle-icon"></i>${v.legajo}</td>
                <td class="text-start small text-truncate" style="max-width: 150px;" title="${v.nombre}">${v.nombre}</td>
                <td class="small text-muted">Resumen</td>
                <td class="text-success">${v.normal}</td>
                <td class="text-info">${v.caja}</td>
                <td style="color:#d63384">${v.permiso}</td>
                <td class="text-primary">${v.extra}</td>
                <td class="text-warning">${v.devuelve}</td>
                <td style="color:#6a1b9a">${v.vacaciones}</td>
                <td class="text-secondary">${v.ausencia}</td>
                <td>${v.total}</td>
            </tr>`;

            // Filas de detalle (días) colapsadas por defecto
            v.days.forEach(item => {
                const fechaFmt = item.fecha ? item.fecha.split('-').reverse().join('/') : '-';
                tableHTML += `<tr class="vendor-detail-row detail-${v.legajo} table-light" style="display: none;">
                    <td class="text-start small">${item.sucursal_nombre}</td>
                    <td class="text-start small">${item.legajo_real}</td>
                    <td class="text-start small text-truncate" style="max-width: 150px;">${item.nombre}</td>
                    <td class="small">${fechaFmt}</td>
                    <td class="text-success">${item.normal}</td>
                    <td class="text-info">${item.caja}</td>
                    <td style="color:#d63384">${item.permiso}</td>
                    <td class="text-primary">${item.extra}</td>
                    <td class="text-warning">${item.devuelve}</td>
                    <td style="color:#6a1b9a">${item.vacaciones}</td>
                    <td class="text-secondary">${item.ausencia}</td>
                    <td>${item.total}</td>
                </tr>`;
            });
        });

        if (vendors.length > 0) {
            // Añadir fila de totales generales al final
            tableHTML += `<tr class="table-dark fw-bold">
                <td colspan="4" class="text-end text-uppercase" style="font-size: 0.85rem;">Totales Generales</td>
                <td>${tH}</td>
                <td>${tC}</td>
                <td style="color:#ffc2e0">${tP}</td>
                <td class="text-info">${tE}</td>
                <td class="text-warning">${tD}</td>
                <td style="color:#e1bee7">${tV}</td>
                <td class="text-secondary">${tAU}</td>
                <td>${tTotal}</td>
            </tr>`;
        }

        $('#ind_desg_tablaDetalleBody').html(tableHTML || '<tr><td colspan="12" class="text-center py-3">No se encontraron registros.</td></tr>');
    }

    // --- EVENTO TONTROL DE COLAPSO ---
    $(document).on('click', '.vendor-summary-row', function() {
        const legajo = $(this).data('legajo');
        const $details = $(`.detail-${legajo}`);
        $details.toggle();
        
        const isVisible = $details.is(':visible');
        const $icon = $(this).find('.toggle-icon');
        if (isVisible) {
            $icon.removeClass('bi-chevron-right').addClass('bi-chevron-down');
        } else {
            $icon.removeClass('bi-chevron-down').addClass('bi-chevron-right');
        }
    });

    // --- EVENTO KEYUP PARA FILTRAR EN TIEMPO REAL ---
    $('#ind_desg_buscarPersona').on('keyup', function() {
        $('#ind_desg_buscadorTabla').val($(this).val());
        renderTabla();
    });

    $('#ind_desg_buscadorTabla').on('keyup', function() {
        $('#ind_desg_buscarPersona').val($(this).val());
        renderTabla();
    });

    // --- EXPORTAR EXCEL ---
    $('#ind_desg_btnExportar').on('click', function() {
        const sucursales = $('#ind_desg_sucursalSelector').val() || [];
        const fechaInicio = $('#ind_desg_fechaDesde').val();
        const fechaFin = $('#ind_desg_fechaHasta').val();

        if (!fechaInicio || !fechaFin) return;

        const sucursalesParam = sucursales.join(',');
        const url = `${apiExportarURL}?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}&sucursales=${sucursalesParam}`;
        window.location.href = url;
    });
});
