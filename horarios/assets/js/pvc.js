$(document).ready(function () {
    const apiPVC = 'api/pvc_handler.php';

    // 1. Inicializar selectores de período
    const initPeriodo = () => {
        const fechaActual = new Date();
        const mesActual = fechaActual.getMonth() + 1;
        const anioActual = fechaActual.getFullYear();

        const anioSelector = $('#pvc_anioSelector');
        for (let i = 2024; i <= anioActual + 1; i++) {
            anioSelector.append(`<option value="${i}" ${i === anioActual ? 'selected' : ''}>${i}</option>`);
        }
        $('#pvc_mesSelector').val(mesActual);
    };

    initPeriodo();

    // Formateador de moneda
    const fmtC = (val) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(val);

    // 2. Evento Filtrar / Calcular
    $('#pvc_btnFiltrar').on('click', function () {
        const mes = $('#pvc_mesSelector').val();
        const anio = $('#pvc_anioSelector').val();
        const btn = $(this);
        const body = $('#pvc_body');

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Calculando...');
        body.html('<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-danger"></div><br>Cruzando horarios con ventas de Tango...</td></tr>');

        $.getJSON(`${apiPVC}?accion=listar&mes=${mes}&anio=${anio}`, function (res) {
            btn.prop('disabled', false).html('<i class="bi bi-search"></i> Calcular PVC');

            if (res.status === 'ok') {
                renderTablaJerarquica(res.data);
                $('#pvc_btnExportar').prop('disabled', res.data.length === 0);
            } else {
                body.html(`<tr><td colspan="5" class="text-center py-5 text-danger">${res.message}</td></tr>`);
            }
        }).fail(() => {
            btn.prop('disabled', false).html('<i class="bi bi-search"></i> Calcular PVC');
            body.html('<tr><td colspan="5" class="text-center py-5 text-danger">Error de conexión al servidor.</td></tr>');
        });
    });

    // 3. Lógica de Agrupación y Renderizado
    const renderTablaJerarquica = (data) => {
        const body = $('#pvc_body');
        if (data.length === 0) {
            body.html('<tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron registros en este período.</td></tr>');
            return;
        }

        // AGRUPAR DATOS: Vendedor -> Sucursal -> Dias
        const jerarquia = {};
        data.forEach(item => {
            if (!jerarquia[item.cod_vended]) {
                jerarquia[item.cod_vended] = {
                    nombre: item.vendedor,
                    totalHoras: 0,
                    totalImporte: 0,
                    sucursales: {}
                };
            }
            jerarquia[item.cod_vended].totalHoras += item.horas;
            jerarquia[item.cod_vended].totalImporte += item.importe;

            if (!jerarquia[item.cod_vended].sucursales[item.sucursal_id]) {
                jerarquia[item.cod_vended].sucursales[item.sucursal_id] = {
                    nombre: item.sucursal,
                    totalHoras: 0,
                    totalImporte: 0,
                    dias: []
                };
            }
            jerarquia[item.cod_vended].sucursales[item.sucursal_id].totalHoras += item.horas;
            jerarquia[item.cod_vended].sucursales[item.sucursal_id].totalImporte += item.importe;
            jerarquia[item.cod_vended].sucursales[item.sucursal_id].dias.push(item);
        });

        let html = '';
        Object.keys(jerarquia).forEach((codVend, idx) => {
            const v = jerarquia[codVend];
            const vRowId = `v-collapse-${idx}`;

            // FILA NIVEL 1: VENDEDOR
            html += `
                <tr class="table-group-divider cursor-pointer bg-light" data-bs-toggle="collapse" data-bs-target=".${vRowId}" style="cursor:pointer;">
                    <td colspan="2">
                        <i class="bi bi-chevron-right me-2 text-primary"></i>
                        <span class="fw-bold text-dark">${v.nombre}</span>
                        <small class="text-muted ms-2">(Legajo: ${codVend})</small>
                    </td>
                    <td class="text-center"><span class="badge bg-danger fs-6">${v.totalHoras} hs</span></td>
                    <td class="text-end fw-bold text-success fs-5 px-3">${fmtC(v.totalImporte)}</td>
                </tr>
            `;

            // FILAS NIVEL 2: SUCURSALES (Colapsables)
            Object.keys(v.sucursales).forEach((sucId, sIdx) => {
                const s = v.sucursales[sucId];
                const sRowId = `s-collapse-${idx}-${sIdx}`;
                
                html += `
                    <tr class="collapse ${vRowId} bg-white border-start border-primary border-4">
                        <td colspan="2" class="ps-5 cursor-pointer" data-bs-toggle="collapse" data-bs-target=".${sRowId}">
                            <i class="bi bi-arrow-return-right me-2 text-secondary"></i>
                            <span class="fw-semibold text-secondary">${s.nombre}</span>
                        </td>
                        <td class="text-center"><span class="badge bg-secondary">${s.totalHoras} hs</span></td>
                        <td class="text-end fw-bold text-primary px-3">${fmtC(s.totalImporte)}</td>
                    </tr>
                `;

                // FILAS NIVEL 3: DETALLE DIARIO (Colapsables nivel 2)
                html += `<tr class="collapse ${vRowId} ${sRowId}"><td colspan="4">
            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered bg-light mb-0">
                    <thead class="table-secondary">
                        <tr style="font-size: 0.85rem;">
                            <th class="text-center">Fecha</th>
                            <th class="text-center">Horario</th>
                            <th class="text-center">Horas</th>
                            <th class="text-end">Venta Cajero</th>
                            <th class="text-end">Mejor Venta (Vendedor)</th>
                            <th class="text-end">Diferencia (PVC)</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
                s.dias.forEach(d => {
                    const diff = d.mejor_venta - d.venta_cajero;
                    const pvcDia = diff > 0 ? diff : 0;
                    
                    html += `
                        <tr style="font-size: 0.85rem;">
                            <td class="text-center">${d.fecha}</td>
                            <td class="text-center">Entrada: ${d.entrada} | Salida: ${d.salida}</td>
                            <td class="text-center">${d.horas} hs</td>
                            <td class="text-end">${fmtC(d.venta_cajero)}</td>
                            <td class="text-end">
                                ${fmtC(d.mejor_venta)} 
                                <br><small class="text-muted">${d.mejor_vendedor_nombre}</small>
                            </td>
                            <td class="text-end fw-bold text-success">
                                ${fmtC(pvcDia)}
                            </td>
                        </tr>`;
                });
        
                html += `</tbody></table></div></td></tr>`;
            });
        });

        body.html(html);

        // Cambiar icono al expandir/colapsar
        $('.collapse').on('show.bs.collapse', function() {
            $(this).prev().find('.bi-chevron-right').removeClass('bi-chevron-right').addClass('bi-chevron-down');
        }).on('hide.bs.collapse', function() {
            $(this).prev().find('.bi-chevron-down').removeClass('bi-chevron-down').addClass('bi-chevron-right');
        });
    };

    // 4. Exportar a Excel (Formato Agrupado)
    $('#pvc_btnExportar').on('click', function () {
        const mes = $('#pvc_mesSelector').val();
        const anio = $('#pvc_anioSelector').val();
        window.location.href = `api/exportar_pvc.php?mes=${mes}&anio=${anio}`;
    });
});
