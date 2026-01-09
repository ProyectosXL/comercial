$(document).ready(function() {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiCompURL = 'api/comparacion_handler.php';

    // Cargar Sucursales
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function(data) {
        $('#sucursalSelector').append('<option value="">Seleccione...</option>');
        $.each(data, (key, suc) => $('#sucursalSelector').append(`<option value="${suc.NRO_SUCURSAL}">${suc.DESC_SUCURSAL}</option>`));
    });

    // Función fecha (4 de Enero)
    function getPrimerDiaDeSemana(year, week) {
        const d = new Date(year, 0, 4);
        const day = d.getDay() || 7; 
        d.setDate(d.getDate() - day + 1);
        d.setDate(d.getDate() + ((week - 1) * 7));
        return d;
    }

    $('#btnComparar').on('click', function() {
        const sucursal = $('#sucursalSelector').val();
        const semana = $('#semanaSelector').val();
        
        if (!sucursal || !semana) return alert("Faltan datos");

        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);
        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);
        
        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        const btn = $(this);
        btn.prop('disabled', true).text('Consultando API...');
        $('#tablaComparacion').html('<tr><td colspan="7">Cargando datos...</td></tr>');

        $.getJSON(`${apiCompURL}?sucursal=${sucursal}&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`, function(data) {

            // 2. Renderizar Tabla
            let html = '';
            if (data.cruce && data.cruce.length > 0) {
                data.cruce.forEach(row => {
                    html += `
                        <tr class="${row.color}">
                            <td class="text-start fw-bold">${row.vendedor_visual}</td> <!-- CAMBIO AQUÍ -->
                            <td>${row.fecha}</td>
                            <td class="fw-bold">${row.plan_entrada}</td>
                            <td>${row.plan_salida}</td>
                            <td class="fw-bold">${row.real_entrada}</td>
                            <td>${row.real_salida}</td>
                            <td>${row.estado}</td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="7">No hay datos planificados para esta semana.</td></tr>';
            }
            $('#tablaComparacion').html(html);

        }).fail(function(jqXHR) {
            console.error(jqXHR);
            $('#tablaComparacion').html('<tr><td colspan="7" class="text-danger">Error al consultar API. Ver consola.</td></tr>');
        }).always(function() {
            btn.prop('disabled', false).text('Comparar vs QuickPass');
        });
    });
});