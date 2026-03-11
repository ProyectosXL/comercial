$(document).ready(function () {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiVistaURL = 'api/vista_local_handler.php';

    // 1. INICIALIZAR SELECT2
    $('#vendedorSelector').select2({
        theme: 'bootstrap-5',
        placeholder: "Escriba nombre o legajo...",
        allowClear: true,
        width: '100%'
    });

    // 2. Cargar Sucursales
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function (data) {
        $.each(data, (key, suc) => {
            $('#sucursalSelector').append(`<option value="${suc.NRO_SUCURSAL}">${suc.DESC_SUCURSAL}</option>`);
        });
    });

    // 3. Cargar Vendedores
    $.getJSON(`${apiDatosURL}?accion=todos_vendedores`, function (data) {
        const datosFormateados = data.map(item => ({
            id: item.id,
            text: item.text
        }));

        $('#vendedorSelector').select2({
            theme: 'bootstrap-5',
            placeholder: "Escriba nombre o legajo...",
            allowClear: true,
            data: datosFormateados
        });

        $('#vendedorSelector').val(null).trigger('change');
    });

    // Limpiar selecciones cruzadas
    $('#vendedorSelector').on('select2:select', function (e) {
        $('#sucursalSelector').val('');
    });

    $('#sucursalSelector').on('change', function () {
        if ($(this).val()) {
            $('#vendedorSelector').val(null).trigger('change');
        }
    });

    // Función para obtener el primer día de la semana
    function getPrimerDiaDeSemana(year, week) {
        const d = new Date(year, 0, 4);
        const day = d.getDay() || 7;
        d.setDate(d.getDate() - day + 1);
        d.setDate(d.getDate() + ((week - 1) * 7));
        return d;
    }

    // 4. VER AGENDA
    $('#btnVerAgenda').on('click', function () {
        const sucursal = $('#sucursalSelector').val();
        const vendedorId = $('#vendedorSelector').val();
        const semana = $('#semanaSelector').val();

        if (!semana) {
            mostrarModalConTitulo("Advertencia", "Por favor, seleccione una semana.", "warning");
            return;
        }

        if (!sucursal && !vendedorId) {
            mostrarModalConTitulo("Advertencia", "Seleccione una Sucursal O busque un Vendedor.", "warning");
            return;
        }

        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);
        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);
        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        $('#tablaVistaLocal').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                <p class="mt-3 fs-5 text-muted">Cargando horarios...</p>
            </div>
        `);

        let url = `${apiVistaURL}?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`;
        if (vendedorId) {
            url += `&vendedor=${vendedorId}`;
        } else {
            url += `&sucursal=${sucursal}`;
        }

        $.getJSON(url, function (data) {
            renderTablaVistaLocal(data, primerDia);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error('Error al cargar datos:', textStatus, errorThrown);
            mostrarModalConTitulo("Error", "No se pudieron cargar los datos. Intente nuevamente.", "error");
        });
    });

    // --- FUNCIÓN DE CÁLCULO DE TURNO (RÉPLICA DE GESTIÓN) ---
    function calcularTurnoTexto(horas) {
        if (!horas || horas.length === 0) return '-';
        horas.sort((a, b) => a - b);
        const min = horas[0];
        const max = horas[horas.length - 1];

        if (min >= 7 && max <= 15) return 'TM';
        if (min >= 14 && (max <= 23 || max === 24)) return 'TT';
        if (min >= 10 && (max <= 23 || max === 24)) return 'FULL';

        const tieneTM = horas.some(h => h >= 7 && h <= 15);
        const tieneTT = horas.some(h => (h >= 16 && h <= 23) || h === 24);
        if (tieneTM && tieneTT) return 'FULL';

        return 'MIXTO';
    }

    // --- FUNCIÓN DE RENDERIZADO MEJORADA (RÉPLICA DE GESTIÓN) ---
    function renderTablaVistaLocal(data, primerDia) {
        const container = $('#tablaVistaLocal');
        container.empty();

        const agenda = data.agenda;
        const nombres = data.nombres;
        const promociones = data.promociones || {};
        const saldosAnteriores = data.saldosAnteriores || {};

        if (!agenda || Object.keys(agenda).length === 0) {
            container.html('<div class="alert alert-info text-center m-4">No hay horarios cargados para esta selección.</div>');
            return;
        }

        let fechasSemana = [];
        const diasSemanaNombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        const vendedoresOrdenados = Object.keys(agenda).sort((a, b) => {
            const nomA = (nombres[a]?.nombre || a).toLowerCase();
            const nomB = (nombres[b]?.nombre || b).toLowerCase();
            return nomA.localeCompare(nomB);
        });

        // Inicializar sumatorias para el footer (7 días, 25 rangos para soportar index 24)
        let sumasHoras = Array.from({ length: 7 }, () => Array(25).fill(0));

        // --- 1. CONSTRUCCIÓN DEL HEADER ---
        let html = '<table class="table table-bordered table-sm mb-0 table-horarios">';
        html += '<thead>';

        // FILA 1: Días
        html += '<tr class="align-middle sticky-top-0">';
        html += '<th class="sticky-left-0 bg-white col-vendedor">Vendedor</th>';
        html += '<th class="sticky-left-1 bg-white col-totales">Total Hs</th>';

        for (let i = 0; i < 7; i++) {
            let fechaActual = new Date(primerDia);
            fechaActual.setDate(primerDia.getDate() + i);
            const fechaISO = fechaActual.toISOString().split('T')[0];
            fechasSemana.push(fechaISO);

            const diaNum = String(fechaActual.getDate()).padStart(2, '0');
            const mesNum = String(fechaActual.getMonth() + 1).padStart(2, '0');

            // Colspan a 21 (18 horas + F + T + N)
            html += `<th colspan="21" class="dia-header text-center fw-bold bg-light" style="min-width: 600px;">${diasSemanaNombres[i]} ${diaNum}/${mesNum}</th>`;
        }
        html += '</tr>';

        // FILA 2: Horas
        html += '<tr class="align-middle sticky-top-1">';
        html += '<th class="sticky-left-0 bg-white col-vendedor"></th>';
        html += '<th class="sticky-left-1 bg-white col-totales"></th>';
        for (let d = 0; d < 7; d++) {
            for (let h = 7; h <= 24; h++) {
                const label = (h === 24) ? '00' : h;
                html += `<th class="sticky-top-1 text-center" style="width: 30px; min-width: 30px;">${label}</th>`;
            }
            html += '<th class="sticky-top-1 text-center" style="width:30px; background-color:#e9ecef;" title="Franco">F</th>';
            html += '<th class="sticky-top-1 text-center" style="width:40px; background-color:#f8f9fa;" title="Turno">T</th>';
            html += '<th class="sticky-top-1 header-dia-fin text-center" style="width:30px; background-color:#e9ecef;" title="Nota"><i class="bi bi-chat-square-text"></i></th>';
        }
        html += '</tr></thead>';

        // --- 2. BODY ---
        html += '<tbody>';
        vendedoresOrdenados.forEach(id => {
            const datos = agenda[id] || {};
            const infoVend = nombres[id] || nombres[parseInt(id)] || { nombre: `Vendedor ${id}`, tarea: '' };
            const nombre = infoVend.nombre;
            const tarea = infoVend.tarea;
            const saldoBase = saldosAnteriores[id] || saldosAnteriores[parseInt(id)] || 0;

            let tH = 0, tE = 0, tD = 0, tP = 0;

            // Pre-calcular sumatorias para el vendedor
            fechasSemana.forEach((fecha, diaIndex) => {
                const esFranco = datos.francos && datos.francos[fecha];
                if (!esFranco && datos.horarios && datos.horarios[fecha]) {
                    for (let h = 7; h <= 24; h++) {
                        const hBusqueda = (h === 24) ? 0 : h;
                        let tipo = datos.horarios[fecha][hBusqueda];
                        let valor = 1;
                        if (tipo && tipo.endsWith('_media')) {
                            valor = 0.5;
                            tipo = tipo.replace('_media', '');
                        }

                        if (tipo) {
                            if (tipo === 'vacaciones') continue;

                            if (tipo === 'normal' || tipo === 'caja') tH += valor;
                            else if (tipo === 'extra') tE += valor;
                            else if (tipo === 'devuelve') tD += valor;
                            else if (tipo === 'permiso') tP += valor;

                            sumasHoras[diaIndex][h] += valor;
                        }
                    }
                }
            });

            const saldoFinal = saldoBase + (tP - tD);
            html += '<tr>';

            // Celda Vendedor
            const feedbackTexto = datos.feedback || '';
            const tieneFeedback = feedbackTexto.trim() !== '';
            html += `<td class="sticky-left-0 bg-white text-start p-2 col-vendedor">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-truncate" style="max-width: 160px;">
                                <div class="fw-bold mb-0 vendedor-nombre">${nombre}</div>
                                <small class="text-muted vendedor-tarea">${tarea}</small>
                            </div>
                            <button class="btn-feedback ${tieneFeedback ? 'tiene-feedback' : ''}" 
                                    data-id="${id}" data-nombre="${nombre}" data-texto="${feedbackTexto}">
                                <i class="bi bi-chat-dots-fill"></i>
                            </button>
                        </div>
                     </td>`;

            // Celda Totales
            let colorSaldo = 'bg-light text-secondary';
            if (saldoFinal > 0) colorSaldo = 'bg-danger text-white';
            else if (saldoFinal < 0) colorSaldo = 'bg-success text-white';

            html += `<td class="sticky-left-1 bg-white align-middle p-0 col-totales">
                        <div class="d-flex flex-column align-items-center summary-container">
                            <span class="text-success fw-bold">H: ${tH % 1 === 0 ? tH : tH.toFixed(1)}</span>
                            <span class="text-primary fw-bold">E: ${tE % 1 === 0 ? tE : tE.toFixed(1)}</span>
                            <span class="text-warning fw-bold">D: ${tD % 1 === 0 ? tD : tD.toFixed(1)}</span>
                            <span class="fw-bold text-pink">P: ${tP % 1 === 0 ? tP : tP.toFixed(1)}</span>
                            <span class="badge border ${colorSaldo} mt-1 badge-saldo">
                                S: ${saldoFinal % 1 === 0 ? saldoFinal : saldoFinal.toFixed(1)}
                            </span>
                        </div>
                     </td>`;

            // Celdas de días
            fechasSemana.forEach((fecha, diaIndex) => {
                const esFranco = datos.francos && datos.francos[fecha];
                const nota = datos.notas && datos.notas[fecha];

                for (let h = 7; h <= 24; h++) {
                    let bgColor = '';
                    let texto = '';
                    let color = '';

                    const hBusqueda = (h === 24) ? 0 : h;
                    let tipo = (!esFranco && datos.horarios && datos.horarios[fecha]) ? (datos.horarios[fecha][hBusqueda] || '') : null;
                    let esMedia = false;

                    if (tipo && tipo.endsWith('_media')) {
                        esMedia = true;
                        tipo = tipo.replace('_media', '');
                    }

                    if (esFranco) {
                        bgColor = '#e9ecef';
                    } else if (tipo) {
                        color = 'white';
                        if (tipo === 'extra') { bgColor = '#0d6efd'; texto = 'E'; }
                        else if (tipo === 'devuelve') { bgColor = '#fd7e14'; texto = 'D'; }
                        else if (tipo === 'permiso') { bgColor = '#d63384'; texto = 'P'; }
                        else if (tipo === 'caja') { bgColor = '#0dcaf0'; texto = 'C'; color = '#000'; }
                        else if (tipo === 'vacaciones') { bgColor = '#f3e5f5'; texto = 'V'; color = '#6a1b9a'; }
                        else { bgColor = '#198754'; texto = 'X'; }
                        if (esMedia) texto += ' <small>½</small>';
                    }
                    html += `<td style="background-color: ${bgColor}; color: ${color}; font-weight: bold; text-align: center; vertical-align: middle; min-width: 30px; font-size: 0.8rem;">${texto}</td>`;
                }

                // Franco (F)
                html += `<td class="text-center align-middle p-0" style="min-width: 30px; background-color: ${esFranco ? '#f3e5f5' : 'transparent'};">
                                ${esFranco ? '<i class="bi bi-check-circle-fill" style="color: #6f42c1; font-size: 1.1rem;"></i>' : ''}
                             </td>`;

                // Turno (T)
                let turno = '-';
                let turnoColor = 'bg-light text-dark border';

                if (esFranco) {
                    turno = 'F';
                    turnoColor = 'text-white';
                    html += `<td class="text-center align-middle p-0" style="min-width: 45px;">
                                    <span class="badge" style="background-color: #6f42c1; font-size: 0.75rem;">${turno}</span>
                                 </td>`;
                } else {
                    if (datos.horarios && datos.horarios[fecha]) {
                        const horasDia = Object.keys(datos.horarios[fecha]).map(h => parseInt(h) === 0 ? 24 : parseInt(h));
                        turno = calcularTurnoTexto(horasDia);
                        switch (turno) {
                            case 'TM': turnoColor = 'bg-primary text-white'; break;
                            case 'TT': turnoColor = 'bg-success text-white'; break;
                            case 'FULL': turnoColor = 'bg-warning text-dark'; break;
                            case 'MIXTO': turnoColor = 'bg-info text-white'; break;
                        }
                    }
                    html += `<td class="text-center align-middle p-0" style="min-width: 45px;">
                                    <span class="badge ${turnoColor}" style="font-size: 0.7rem;">${turno}</span>
                                 </td>`;
                }

                // Nota
                const tieneNota = nota && nota.trim() !== '';
                html += `<td class="text-center align-middle p-0 celda-dia-fin" style="min-width: 30px;">
                                <button class="btn-nota-local ${tieneNota ? 'tiene-nota' : ''}" 
                                        data-nombre="${nombre}" data-fecha="${fecha}" data-nota="${nota || ''}"
                                        style="border: none; background: transparent; cursor: pointer; font-size: 1.1rem; color: ${tieneNota ? '#fd7e14' : '#ced4da'};">
                                    <i class="bi bi-chat-square-text${tieneNota ? '-fill' : ''}"></i>
                                </button>
                             </td>`;
            });

            html += '</tr>';
        });
        html += '</tbody>';

        // --- 3. FOOTER ---
        html += '<tfoot class="sticky-bottom bg-white" style="z-index: 25;">';

        // Totales por Hora
        html += '<tr class="total-por-hora-row">';
        html += '<td class="sticky-left-0 bg-light fw-bold text-start p-2 col-vendedor">Total Hs</td>';
        html += '<td class="sticky-left-1 bg-light col-totales"></td>';
        for (let d = 0; d < 7; d++) {
            for (let h = 7; h <= 24; h++) {
                const suma = sumasHoras[d][h] || 0;
                const fmtSuma = (suma % 1 === 0 ? suma : suma.toFixed(1));
                const colorClass = suma > 0 ? 'text-success' : 'text-muted';
                html += `<td class="text-center fw-bold bg-light ${colorClass}" style="font-size: 0.8rem; min-width: 30px;">${suma > 0 ? fmtSuma : '0'}</td>`;
            }
            html += '<td class="bg-light"></td><td class="bg-light"></td><td class="bg-light footer-dia-fin"></td>';
        }
        html += '</tr>';

        // Promociones
        html += '<tr>';
        html += '<td class="sticky-left-0 bg-light fw-bold text-start p-2 col-vendedor">Promociones</td>';
        html += '<td class="sticky-left-1 bg-light col-totales"></td>';
        fechasSemana.forEach(fecha => {
            const list = promociones[fecha] || [];
            let promoHTML = '<span class="text-muted">-</span>';
            if (list.length > 0) {
                promoHTML = `<div class="d-flex flex-wrap gap-1">
                    ${list.map(p => `<span class="badge bg-primary px-1" style="font-size: 0.7rem; white-space: normal; text-align: left;">${p}</span>`).join('')}
                </div>`;
            }
            html += `<td colspan="21" class="footer-dia-fin bg-light text-start p-1 align-top" style="max-width: 300px;">${promoHTML}</td>`;
        });
        html += '</tr>';

        html += '</tfoot></table>';
        container.html(html);
    }

    // --- EVENTOS ---
    $(document).on('click', '.btn-feedback', function () {
        $('#feedVendedor').val($(this).data('id'));
        $('#feedNombre').text($(this).data('nombre'));
        $('#feedTexto').val($(this).data('texto') || '');
        new bootstrap.Modal(document.getElementById('modalFeedback')).show();
    });

    $('#btnGuardarFeed').on('click', function () {
        const payload = {
            accion: 'guardar_feedback',
            sucursal: $('#sucursalSelector').val() || 0,
            vendedor: $('#feedVendedor').val(),
            fechaSemana: getPrimerDiaDeSemana(...$('#semanaSelector').val().split('-W')).toISOString().split('T')[0],
            mensaje: $('#feedTexto').val()
        };

        $.ajax({
            url: apiVistaURL,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function (res) {
                if (res.status === 'ok') {
                    mostrarModalConTitulo("Éxito", "Feedback guardado correctamente.", "success");
                    $('#btnVerAgenda').trigger('click');
                    bootstrap.Modal.getInstance(document.getElementById('modalFeedback')).hide();
                } else {
                    mostrarModalConTitulo("Error", "Error al guardar.", "error");
                }
            }
        });
    });

    $(document).on('click', '.btn-nota-local.tiene-nota', function () {
        mostrarModalConTitulo(`Nota para ${$(this).data('nombre')} (${$(this).data('fecha')})`, $(this).data('nota'), 'warning');
    });

    function mostrarModalConTitulo(titulo, mensaje, tipo = 'info') {
        const header = $('#modalMensajeHeader');
        header.removeClass('bg-success bg-danger bg-warning bg-info bg-primary');
        let icon = 'bi-info-circle-fill text-info';

        if (tipo === 'success') { header.addClass('bg-success'); icon = 'bi-check-circle-fill text-success'; }
        else if (tipo === 'error') { header.addClass('bg-danger'); icon = 'bi-x-circle-fill text-danger'; }
        else if (tipo === 'warning') { header.addClass('bg-warning text-dark'); icon = 'bi-exclamation-triangle-fill text-warning'; }
        else { header.addClass('bg-info'); }

        $('#modalMensajeTitulo').text(titulo);
        $('#modalMensajeIcono').html(`<i class="${icon}"></i>`);
        $('#modalMensajeTexto').html(mensaje.replace(/\n/g, '<br>'));
        new bootstrap.Modal(document.getElementById('modalMensaje')).show();
    }

    function obtenerSemanaActual() {
        const d = new Date();
        const start = new Date(d.getFullYear(), 0, 1);
        const week = Math.ceil((((d - start) / 86400000) + start.getDay() + 1) / 7);
        return `${d.getFullYear()}-W${String(week).padStart(2, '0')}`;
    }

    $('#semanaSelector').val(obtenerSemanaActual());
});
