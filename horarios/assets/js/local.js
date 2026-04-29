$(document).ready(function () {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiVistaURL = 'api/vista_local_handler.php';
    let lastDataLoaded = null;
    let lastPrimerDia = null;
    let sucursalesMap = {};

    // 1. INICIALIZAR SELECT2
    $('#vendedorSelector').select2({
        theme: 'bootstrap-5',
        placeholder: "Escriba nombre o legajo...",
        allowClear: true,
        width: '100%'
    });

    // 2. Cargar Sucursales
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function (data) {
        $.each(data, function(key, sucursal) {
            sucursalesMap[sucursal.NRO_SUCURSAL] = sucursal.DESC_SUCURSAL;
            $('#sucursalSelector').append(`<option value="${sucursal.NRO_SUCURSAL}">${sucursal.DESC_SUCURSAL}</option>`);
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
        const semana = $('#semanaSelector').attr('data-week-val') || $('#semanaSelector').val();

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
            lastDataLoaded = data;
            lastPrimerDia = primerDia;
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
        const nombresSucursales = data.nombresSucursales || {};
        const promociones = data.promociones || {};
        const saldosAnteriores = data.saldosAnteriores || {};

        const esFiltroUnSoloVendedor = $('#vendedorSelector').val() !== null && $('#vendedorSelector').val() !== '';

        if (!agenda || Object.keys(agenda).length === 0) {
            container.html('<div class="alert alert-info text-center m-4">No hay horarios cargados para esta selección.</div>');
            $('#btnExportarPDF').prop('disabled', true);
            return;
        }

        $('#btnExportarPDF').prop('disabled', false);

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

            let sucursalTexto = '';
            if (esFiltroUnSoloVendedor && vendedoresOrdenados.length > 0) {
                const vId = vendedoresOrdenados[0];
                const infoGral = agenda[vId] || {};
                if (infoGral.sucursal_detalle && infoGral.sucursal_detalle[fechaISO]) {
                    const horasDetalle = infoGral.sucursal_detalle[fechaISO];
                    const idsValidos = Object.values(horasDetalle).filter(sid => sid > 0);
                    if (idsValidos.length > 0) {
                        const counts = {};
                        let maxCount = 0;
                        let winner = null;
                        idsValidos.forEach(sid => {
                            counts[sid] = (counts[sid] || 0) + 1;
                            if (counts[sid] > maxCount) {
                                maxCount = counts[sid];
                                winner = sid;
                            }
                        });
                        if (winner && nombresSucursales[winner]) {
                            sucursalTexto = `<div class="text-primary fw-normal mt-1" style="font-size: 0.72rem; line-height: 1;"><i class="bi bi-geo-alt-fill"></i> ${nombresSucursales[winner]}</div>`;
                        }
                    }
                }
            }

            // Colspan a 21 (18 horas + F + T + Note)
            html += `<th colspan="21" class="dia-header text-center fw-bold bg-light" style="min-width: 500px;">
                        <div>${diasSemanaNombres[i]} ${diaNum}/${mesNum}</div>
                        ${sucursalTexto}
                    </th>`;
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

            let tH = 0, tE = 0, tD = 0, tP = 0, tC = 0;

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

                            if (tipo === 'normal') tH += valor;
                            else if (tipo === 'caja') tC += valor;
                            else if (tipo === 'permiso') tP += valor;
                            else if (tipo === 'extra') tE += valor;
                            else if (tipo === 'devuelve') tD += valor;

                            sumasHoras[diaIndex][h] += valor;
                        }
                    }
                }

                // NUEVO: Sumar bloqueos externos al total de horas (tH)
                if (lastDataLoaded.bloqueosExternos && lastDataLoaded.bloqueosExternos[id] && lastDataLoaded.bloqueosExternos[id][fecha]) {
                    Object.values(lastDataLoaded.bloqueosExternos[id][fecha]).forEach(sucId => {
                        if (sucId > 0) tH += 1; // Cada bloqueo externo cuenta como 1 hora
                    });
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
                            </div>
                            <button class="btn-feedback ${tieneFeedback ? 'tiene-feedback' : ''} ${esFiltroUnSoloVendedor ? 'd-none' : ''}" 
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
                        <div class="d-flex flex-column align-items-center summary-container" style="font-size: 0.8rem; line-height: 1.1;">
                            <span class="text-success fw-bold">H: ${tH % 1 === 0 ? tH : tH.toFixed(1)}</span>
                            <span class="text-info fw-bold">C: ${tC % 1 === 0 ? tC : tC.toFixed(1)}</span>
                            <span class="text-pink fw-bold" style="color: #d63384;">P: ${tP % 1 === 0 ? tP : tP.toFixed(1)}</span>
                            <span class="text-primary fw-bold">A: ${tE % 1 === 0 ? tE : tE.toFixed(1)}</span>
                            <span class="text-warning fw-bold">D: ${tD % 1 === 0 ? tD : tD.toFixed(1)}</span>
                            <span class="badge ${colorSaldo} border mt-1" title="Banco de Horas (Saldo)" style="font-size:0.85em; padding: 2px 4px;">
                                Saldo: ${saldoFinal % 1 === 0 ? saldoFinal : saldoFinal.toFixed(1)}
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
                    
                    const bloqueo = (data.bloqueosExternos && data.bloqueosExternos[id] && data.bloqueosExternos[id][fecha]) ? data.bloqueosExternos[id][fecha][hBusqueda] : null;

                    if (tipo && tipo.endsWith('_media')) {
                        esMedia = true;
                        tipo = tipo.replace('_media', '');
                    }

                    let extraClass = '';
                    let titleAttr = '';
                    if (esFranco) {
                        bgColor = '#e9ecef';
                    } else if (bloqueo) {
                        bgColor = '#343a40';
                        texto = 'Ø';
                        color = '#adb5bd';
                        extraClass = 'bloqueo-externo';
                        const nomSuc = sucursalesMap[bloqueo] || `Sucursal ${bloqueo}`;
                        titleAttr = `title="Ocupado en: ${nomSuc}"`;
                    } else if (tipo) {
                        color = 'white';
                        if (tipo === 'extra') { bgColor = '#0d6efd'; texto = 'A'; }
                        else if (tipo === 'devuelve') { bgColor = '#fd7e14'; texto = 'D'; }
                        else if (tipo === 'permiso') { bgColor = '#d63384'; texto = 'P'; }
                        else if (tipo === 'caja') { bgColor = '#0dcaf0'; texto = 'C'; color = '#000'; }
                        else if (tipo === 'vacaciones') { bgColor = '#f3e5f5'; texto = 'V'; color = '#6a1b9a'; }
                        else if (tipo === 'ausencia') { bgColor = '#6c757d'; texto = 'AU'; color = 'white'; }
                        else { bgColor = '#198754'; texto = 'X'; }
                        if (esMedia) texto += ' <small>½</small>';
                    }
                    html += `<td class="${extraClass}" ${titleAttr} style="background-color: ${bgColor}; color: ${color}; font-weight: bold; text-align: center; vertical-align: middle; min-width: 30px; font-size: 0.8rem;">${texto}</td>`;
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

        // FILA 1: TOTAL HS - MOVIDA AL CUERPO PARA MANTENER ORDEN Y EVITAR DUPLICADOS EN PDF
        html += '<tr class="total-por-hora-row">';
        html += '<td class="sticky-left-0 bg-light fw-bold text-start p-2 col-vendedor">Total Hs</td>';
        html += '<td class="sticky-left-1 bg-light col-totales"></td>';
        for (let d = 0; d < 7; d++) {
            for (let h = 7; h <= 24; h++) {
                const suma = sumasHoras[d][h] || 0;
                const fmtSuma = (suma % 1 === 0 ? suma : suma.toFixed(1));
                const colorClass = suma > 0 ? 'text-success' : 'text-muted';
                html += `<td class="text-center fw-bold bg-light ${colorClass}" style="font-size: 0.8rem; min-width: 30px; width: 30px;">${suma > 0 ? fmtSuma : '0'}</td>`;
            }
            html += '<td class="bg-light" style="width:30px; min-width:30px;"></td><td class="bg-light" style="width:45px; min-width:45px;"></td><td class="bg-light footer-dia-fin" style="width:30px; min-width:30px;"></td>';
        }
        html += '</tr>';

        // FILA 3: PROMOCIONES (BANCARIAS) - MOVIDA AL CUERPO PARA EVITAR DUPLICADOS EN PDF
        html += '<tr>';
        html += '<td class="sticky-left-0 bg-light text-end fw-bold p-2 align-middle col-vendedor" style="font-size: 0.8rem;">PROMOCIONES</td>';
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

        // FILA 4: PROMOCIONES INTERNAS - MOVIDA AL CUERPO PARA EVITAR DUPLICADOS EN PDF
        html += '<tr>';
        html += '<td class="sticky-left-0 bg-light text-end fw-bold p-2 align-middle col-vendedor" style="font-size: 0.8rem;">PROMOCIONES INTERNAS</td>';
        html += '<td class="sticky-left-1 bg-light col-totales"></td>';
        fechasSemana.forEach(fecha => {
            const listInt = (data.promociones_internas && data.promociones_internas[fecha]) ? data.promociones_internas[fecha] : [];
            let promoIntHTML = '<span class="text-muted">-</span>';
            if (listInt.length > 0) {
                promoIntHTML = `<div class="d-flex flex-wrap gap-1">
                    ${listInt.map(p => `<span class="badge bg-info text-dark px-1" style="font-size: 0.7rem; white-space: normal; text-align: left;">${p}</span>`).join('')}
                </div>`;
            }
            html += `<td colspan="21" class="footer-dia-fin bg-light text-start p-1 align-top" style="max-width: 300px;">${promoIntHTML}</td>`;
        });
        html += '</tr>';

        html += '</tbody>';

        // --- 3. FOOTER VACIO (Para mantener estructura) ---
        html += '<tfoot></tfoot></table>';
        container.html(html);

        // Almacenar solicitudes para acceso rápido
        window.solicitudesCache = data.solicitudes || {};

        // Ajustar sticky headers si el alto de la fila 1 cambió (por ejemplo, al mostrar sucursal)
        setTimeout(() => {
            const firstRow = container.find('thead tr').eq(0);
            if (firstRow.length) {
                const h1 = firstRow.outerHeight();
                const secondRow = container.find('thead tr').eq(1);
                // Ajustamos el 'top' de la segunda fila de horas
                secondRow.find('th').css('top', h1 + 'px');
                
                // Y las esquinas que también son sticky
                secondRow.find('.sticky-left-0, .sticky-left-1').css('top', h1 + 'px');
            }
        }, 100);
    }

    // --- EVENTOS ---
    $(document).on('click', '.btn-feedback', function () {
        $('#feedVendedor').val($(this).data('id'));
        $('#feedNombre').text($(this).data('nombre'));
        $('#feedTexto').val($(this).data('texto') || '');
        new bootstrap.Modal(document.getElementById('modalFeedback')).show();
    });

    $('#btnGuardarFeed').on('click', function () {
        const btn = $(this);
        const btnCancel = btn.siblings('.btn-secondary'); // El botón de cancelar
        const originalText = btn.html();

        // Intentar resolver la sucursal si es 0 (cuando buscan por vendedor)
        let sucursalId = $('#sucursalSelector').val();
        if (!sucursalId || sucursalId == 0) {
            // Si no hay sucursal seleccionada, intentamos sacarla de los datos cargados del vendedor
            const vId = $('#feedVendedor').val();
            if (lastDataLoaded && lastDataLoaded.agenda && lastDataLoaded.agenda[vId]) {
                const det = lastDataLoaded.agenda[vId].sucursal_detalle;
                if (det) {
                    // Buscamos la primera sucursal válida que aparezca en su agenda de la semana
                    for (let f in det) {
                        for (let h in det[f]) {
                            if (det[f][h] > 0) {
                                sucursalId = det[f][h];
                                break;
                            }
                        }
                        if (sucursalId > 0) break;
                    }
                }
            }
        }

        const semana = $('#semanaSelector').attr('data-week-val') || $('#semanaSelector').val();
        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);

        const payload = {
            accion: 'guardar_feedback',
            sucursal: sucursalId || 0,
            vendedor: $('#feedVendedor').val(),
            fechaSemana: primerDia.toISOString().split('T')[0],
            mensaje: $('#feedTexto').val()
        };

        // Estado de CARGANDO
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Enviando...');
        btnCancel.prop('disabled', true);

        $.ajax({
            url: apiVistaURL,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function (res) {
                if (res.status === 'ok') {
                    mostrarModalConTitulo("Éxito", "Feedback guardado y notificado correctamente.", "success");
                    $('#btnVerAgenda').trigger('click');
                    bootstrap.Modal.getInstance(document.getElementById('modalFeedback')).hide();
                } else {
                    mostrarModalConTitulo("Error", "Error al guardar: " + (res.message || 'Error desconocido'), "error");
                }
            },
            error: function() {
                mostrarModalConTitulo("Error", "No se pudo conectar con el servidor para enviar el feedback.", "error");
            },
            complete: function() {
                // Restauramos el botón
                btn.prop('disabled', false).html(originalText);
                btnCancel.prop('disabled', false);
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

    // --- EXPORTAR MATRIZ A PDF (TODO EN UNA PÁGINA) ---
    $('#btnExportarPDF').on('click', async function () {
        if (!lastDataLoaded) return;
        
        const btn = $(this);
        const originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Generando PDF...');
        btn.prop('disabled', true);

        try {
            const sucursalNombre = $('#sucursalSelector option:selected').text() || 'Sucursal';
            const primerDia = lastPrimerDia;
            const ultimoDia = new Date(primerDia);
            ultimoDia.setDate(primerDia.getDate() + 6);
            const rangoFechas = `${primerDia.toISOString().split('T')[0]} al ${ultimoDia.toISOString().split('T')[0]}`;

            const diasNombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            const agenda = lastDataLoaded.agenda;
            const nombres = lastDataLoaded.nombres;
            const promos = lastDataLoaded.promociones || {};
            const promosInt = lastDataLoaded.promociones_internas || {};

            const vIds = Object.keys(agenda).sort((a, b) => {
                const nA = (nombres[a]?.nombre || a).toLowerCase();
                const nB = (nombres[b]?.nombre || b).toLowerCase();
                return nA.localeCompare(nB);
            });

            // Generar TODO el HTML de la semana como un solo bloque ultra compacto
            let fullHtml = `
                <div style="font-family: Arial, Helvetica, sans-serif; background: white; padding: 8px;">
                    <div style="font-size: 13px; font-weight: bold; color: #2c3e50; margin-bottom: 6px;">
                        ${sucursalNombre} &mdash; Semana ${rangoFechas}
                    </div>
            `;

            for (let d = 0; d < 7; d++) {
                let fAct = new Date(primerDia);
                fAct.setDate(primerDia.getDate() + d);
                const fISO = fAct.toISOString().split('T')[0];
                const dd = String(fAct.getDate()).padStart(2, '0');
                const mm = String(fAct.getMonth() + 1).padStart(2, '0');

                fullHtml += `
                    <div style="margin-bottom: 4px;">
                        <div style="background: #2c3e50; color: white; padding: 2px 6px; font-weight: bold; font-size: 10px;">
                            ${diasNombres[d]} ${dd}/${mm}
                        </div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 9px; line-height: 1.15;">
                            <thead>
                                <tr style="background: #eaedf0;">
                                    <th style="border: 1px solid #aaa; padding: 1px 2px; text-align: left; width: 90px; max-width: 90px;">Vendedor</th>
                                    <th style="border: 1px solid #aaa; padding: 1px 2px; text-align: center; width: 22px;">Leg</th>
                                    ${Array.from({length: 18}, (_, i) => {
                                        const h = i + 7;
                                        const lb = (h === 24) ? '00' : h;
                                        return `<th style="border: 1px solid #aaa; padding: 0px; text-align: center; width: 10px; font-size: 8px;">${lb}</th>`;
                                    }).join('')}
                                    <th style="border: 1px solid #aaa; padding: 1px; text-align: center; width: 12px;">F</th>
                                    <th style="border: 1px solid #aaa; padding: 1px; text-align: center; width: 22px;">T</th>
                                    <th style="border: 1px solid #aaa; padding: 1px; text-align: center; width: 14px;">Hs</th>
                                    <th style="border: 1px solid #aaa; padding: 1px 2px; text-align: left; width: 70px; max-width: 70px;">Promos banco</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                vIds.forEach(vid => {
                    const inf = nombres[vid] || nombres[parseInt(vid)] || { nombre: vid, legajo: vid };
                    const dat = agenda[vid] || {};
                    const franco = dat.francos && dat.francos[fISO];
                    let tH = 0, turno = '-', hDia = [];
                    if (!franco && dat.horarios && dat.horarios[fISO]) {
                        Object.entries(dat.horarios[fISO]).forEach(([h, t]) => {
                            let v = t && t.endsWith('_media') ? 0.5 : 1;
                            if (t !== 'vacaciones') { tH += v; hDia.push(parseInt(h)); }
                        });
                    }

                    // NUEVO: Sumar bloqueos externos al total Hs del PDF
                    if (lastDataLoaded.bloqueosExternos && lastDataLoaded.bloqueosExternos[vid] && lastDataLoaded.bloqueosExternos[vid][fISO]) {
                        Object.entries(lastDataLoaded.bloqueosExternos[vid][fISO]).forEach(([h, sId]) => {
                            if (sId > 0) {
                                tH += 1;
                                hDia.push(parseInt(h));
                            }
                        });
                    }
                    turno = calcularTurnoTexto(hDia);

                    fullHtml += `<tr>
                        <td style="border: 1px solid #bbb; padding: 1px 2px; font-weight: bold; width: 90px; max-width: 90px; word-break: break-word;">${inf.nombre}</td>
                        <td style="border: 1px solid #bbb; padding: 1px; text-align: center; color: #555;">${inf.legajo || vid}</td>`;

                    for (let h = 7; h <= 24; h++) {
                        const hb = (h === 24) ? 0 : h;
                        let tx = '', bg = '';
                        
                        // 1. Prioridad: Horarios locales
                        const tipo = (!franco && dat.horarios && dat.horarios[fISO]) ? (dat.horarios[fISO][hb] || '') : null;
                        
                        // 2. Bloqueos Externos (Otras sucursales)
                        const bloqueoExt = (lastDataLoaded.bloqueosExternos && lastDataLoaded.bloqueosExternos[vid] && lastDataLoaded.bloqueosExternos[vid][fISO]) ? lastDataLoaded.bloqueosExternos[vid][fISO][hb] : null;

                        if (tipo) {
                            tx = tipo.endsWith('_media') ? '½' : 'X';
                            bg = '#198754';
                            if (tipo === 'caja') { tx = 'C'; bg = '#0dcaf0'; }
                            else if (tipo === 'extra') { tx = 'E'; bg = '#0d6efd'; }
                            else if (tipo === 'devuelve') { tx = 'D'; bg = '#ffc107'; }
                            else if (tipo === 'permiso') { tx = 'P'; bg = '#d63384'; }
                            else if (tipo === 'vacaciones') { tx = 'V'; bg = '#f3e5f5'; }
                            else if (tipo === 'ausencia') { tx = 'AU'; bg = '#6c757d'; }
                        } else if (bloqueoExt) {
                            tx = 'Ø';
                            bg = '#444'; // Color oscuro para bloqueos externos
                        }

                        let colorTexto = (bg && bg !== '#f3e5f5') ? 'white' : (bg === '#f3e5f5' ? '#6a1b9a' : 'transparent');
                        fullHtml += `<td style="border: 1px solid #bbb; padding: 0px; text-align: center; width: 10px; background: ${bg}; color: ${colorTexto}; font-weight: 700; font-size: 8px;">${tx}</td>`;
                    }

                    fullHtml += `
                        <td style="border: 1px solid #bbb; padding: 1px; text-align: center; background: ${franco ? '#6c757d' : ''}; color: white; font-weight: bold;">${franco ? 'F' : ''}</td>
                        <td style="border: 1px solid #bbb; padding: 1px; text-align: center; font-weight: bold;">${turno}</td>
                        <td style="border: 1px solid #bbb; padding: 1px; text-align: center; font-weight: bold;">${tH > 0 ? (tH % 1 === 0 ? tH : tH.toFixed(1)) : '0'}</td>
                        <td style="border: 1px solid #bbb; padding: 1px 2px; font-size: 7px; color: #333; width: 70px; max-width: 70px; word-break: break-word;">${(promos[fISO] || []).join(', ')}</td>
                    </tr>`;
                });

                fullHtml += `</tbody></table></div>`;
            }

            fullHtml += `</div>`;

            // Renderizar en un contenedor temporal fuera de pantalla
            const tempDiv = $('<div style="width: 1100px; position: absolute; left: -9999px; background: white;"></div>');
            tempDiv.html(fullHtml);
            $('body').append(tempDiv);

            // Esperar un frame para que el navegador renderice
            await new Promise(r => setTimeout(r, 300));

            const canvas = await html2canvas(tempDiv[0], {
                scale: 2.5,
                useCORS: true,
                backgroundColor: '#ffffff'
            });
            tempDiv.remove();

            const imgData = canvas.toDataURL('image/jpeg', 0.92);
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfW = pdf.internal.pageSize.getWidth();
            const pdfH = pdf.internal.pageSize.getHeight();
            const m = 5;
            const usableW = pdfW - (m * 2);
            const usableH = pdfH - (m * 2);
            
            // Escalar para que entre exacto en una página
            let imgW = usableW;
            let imgH = (canvas.height * imgW) / canvas.width;
            
            // Si la altura excede la página, re-escalar por altura
            if (imgH > usableH) {
                imgH = usableH;
                imgW = (canvas.width * imgH) / canvas.height;
            }

            // Centrar horizontalmente
            const offsetX = m + (usableW - imgW) / 2;
            pdf.addImage(imgData, 'JPEG', offsetX, m, imgW, imgH);

            const semanaVal = $('#semanaSelector').val() || 'Semana';
            pdf.save(`Horarios_${sucursalNombre}_${semanaVal}.pdf`);

        } catch (err) {
            console.error('Error al generar PDF:', err);
            mostrarModalConTitulo("Error", "No se pudo generar el PDF.", "error");
        }

        btn.html(originalText);
        btn.prop('disabled', false);
    });

    function obtenerSemanaActual() {
        const d = new Date();
        const start = new Date(d.getFullYear(), 0, 1);
        const week = Math.ceil((((d - start) / 86400000) + start.getDay() + 1) / 7);
        return `${d.getFullYear()}-W${String(week).padStart(2, '0')}`;
    }

    $('#semanaSelector').val(obtenerSemanaActual());
});
