$(document).ready(function () {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiVistaURL = 'api/vista_local_handler.php';
    let lastDataLoaded = null;
    let lastPrimerDia = null;
    let lastNDias = 7;
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

        // Inicializar Select2 para Sucursales (multi-select)
        $('#sucursalSelector').select2({
            theme: 'bootstrap-5',
            placeholder: "Seleccione una o más sucursales...",
            allowClear: true,
            width: '100%'
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
        $('#sucursalSelector').val(null).trigger('change');
    });

    $('#sucursalSelector').on('change', function () {
        const val = $(this).val();
        if (val && val.length > 0) {
            $('#vendedorSelector').val(null).trigger('change');
        }
    });

    // --- TOGGLE SEMANA / DÍA ---
    let modoVistaEsDia = false;
    $('#btnTogglePeriodo').on('click', function () {
        modoVistaEsDia = !modoVistaEsDia;
        if (modoVistaEsDia) {
            $('#semanaContainer').addClass('d-none');
            $('#diaContainer').removeClass('d-none');
            $('#iconoPeriodoIcon').removeClass('bi-calendar-week').addClass('bi-calendar-day');
            $('#labelPeriodo').text('DÍA');
            $(this).attr('title', 'Volver a vista Semana').addClass('btn-warning').removeClass('btn-outline-secondary');
        } else {
            $('#diaContainer').addClass('d-none');
            $('#semanaContainer').removeClass('d-none');
            $('#iconoPeriodoIcon').removeClass('bi-calendar-day').addClass('bi-calendar-week');
            $('#labelPeriodo').text('SEMANA');
            $(this).attr('title', 'Cambiar entre vista Semana y Día').removeClass('btn-warning').addClass('btn-outline-secondary');
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

    $('#btnVerAgenda').on('click', function () {
        const rawSucursales = $('#sucursalSelector').val();
        let sucursalesSeleccionadas = [];
        if (Array.isArray(rawSucursales)) {
            sucursalesSeleccionadas = rawSucursales.filter(s => s && s !== '');
        } else if (typeof rawSucursales === 'string' && rawSucursales.trim() !== '') {
            sucursalesSeleccionadas = [rawSucursales.trim()];
        }
        const vendedorId = $('#vendedorSelector').val();

        // Detectar modo: semana o día
        let primerDia, fechaInicio, fechaFin, nDias;
        if (modoVistaEsDia) {
            const diaVal = $('#diaSelector').val();
            if (!diaVal) {
                mostrarModalConTitulo("Advertencia", "Por favor, seleccione un día.", "warning");
                return;
            }
            const [dy, dm, dd] = diaVal.split('-').map(Number);
            primerDia = new Date(dy, dm - 1, dd);
            fechaInicio = diaVal;
            fechaFin = diaVal;
            nDias = 1;
        } else {
            const semana = $('#semanaSelector').attr('data-week-val') || $('#semanaSelector').val();
            if (!semana) {
                mostrarModalConTitulo("Advertencia", "Por favor, seleccione una semana.", "warning");
                return;
            }
            const [year, weekNum] = semana.split('-W');
            primerDia = getPrimerDiaDeSemana(year, weekNum);
            let ultimoDia = new Date(primerDia);
            ultimoDia.setDate(primerDia.getDate() + 6);
            fechaInicio = primerDia.toISOString().split('T')[0];
            fechaFin = ultimoDia.toISOString().split('T')[0];
            nDias = 7;
        }

        if (sucursalesSeleccionadas.length === 0 && !vendedorId) {
            mostrarModalConTitulo("Advertencia", "Seleccione una o más Sucursales O busque un Vendedor.", "warning");
            return;
        }

        $('#tablaVistaLocal').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                <p class="mt-3 fs-5 text-muted">Cargando horarios...</p>
            </div>
        `);

        let fetchPromise;
        if (vendedorId) {
            const url = `${apiVistaURL}?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}&vendedor=${vendedorId}`;
            fetchPromise = $.getJSON(url).then(data => [{
                sucursalId: '',
                sucursalNombre: 'Búsqueda por Vendedor',
                data: data
            }]);
        } else {
            const promises = sucursalesSeleccionadas.map(sucId => {
                const url = `${apiVistaURL}?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}&sucursal=${sucId}`;
                return $.getJSON(url).then(data => ({
                    sucursalId: sucId,
                    sucursalNombre: sucursalesMap[sucId] || `Sucursal ${sucId}`,
                    data: data
                }));
            });
            fetchPromise = Promise.all(promises);
        }

        fetchPromise.then(function (resultados) {
            const tieneDatos = resultados.some(res => {
                const agenda = res.data?.agenda;
                return agenda && Object.keys(agenda).length > 0;
            });

            if (!tieneDatos) {
                $('#tablaVistaLocal').html('<div class="alert alert-info text-center m-4">No hay horarios cargados para esta selección.</div>');
                $('#btnExportarPDF').prop('disabled', true);
                lastDataLoaded = null;
                return;
            }

            lastDataLoaded = resultados;
            lastPrimerDia = primerDia;
            lastNDias = nDias;
            renderTodasLasTablas(resultados, primerDia, nDias);
        }).catch(function (error) {
            console.error('Error al cargar datos:', error);
            mostrarModalConTitulo("Error", "No se pudieron cargar los datos. Intente nuevamente.", "error");
            $('#tablaVistaLocal').html('<div class="alert alert-danger text-center m-4">Error al cargar horarios.</div>');
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
    function renderTodasLasTablas(resultados, primerDia, nDias = 7) {
        const container = $('#tablaVistaLocal');
        container.empty();
        container.removeClass('table-responsive border').css({
            'overflow-x': 'visible',
            'border': 'none',
            'background': 'transparent'
        });

        $('#btnExportarPDF').prop('disabled', false);

        window.solicitudesCache = Object.assign({}, ...resultados.map(r => r.data.solicitudes || {}));

        const esFiltroUnSoloVendedor = $('#vendedorSelector').val() !== null && $('#vendedorSelector').val() !== '';

        resultados.forEach((res, index) => {
            const sucursalNombre = res.sucursalNombre;
            const data = res.data;
            const agenda = data.agenda;

            if (!agenda || Object.keys(agenda).length === 0) {
                return;
            }

            const headerHtml = `
                <div class="d-flex justify-content-between align-items-center mb-3 ${index > 0 ? 'mt-5' : 'mt-2'} px-3 py-2 bg-light rounded border-start border-dark border-4 shadow-sm">
                    <h5 class="mb-0 text-dark fw-bold text-uppercase"><i class="bi bi-grid-3x3-gap-fill me-2"></i> Grilla Horario - ${sucursalNombre}</h5>
                </div>
            `;

            const tablaHtml = obtenerHtmlTabla(data, primerDia, sucursalNombre, esFiltroUnSoloVendedor, nDias);

            const tableContainer = $('<div class="table-container bg-white rounded shadow-sm"></div>');
            tableContainer.html(tablaHtml);

            container.append(headerHtml);
            container.append(tableContainer);
        });

        // Renderizado de Observaciones (Local e Individual vs General)
        let obsLocalTexto = '';
        let obsGeneralTexto = '';

        resultados.forEach(res => {
            if (res.data && res.data.observaciones && res.data.observaciones.trim() !== '') {
                obsLocalTexto = res.data.observaciones.trim();
            }
            if (res.data && res.data.observacionesGeneral && res.data.observacionesGeneral.trim() !== '') {
                obsGeneralTexto = res.data.observacionesGeneral.trim();
            }
        });

        if (obsLocalTexto !== '') {
            $('#obsLocalTexto').text(obsLocalTexto);
            $('#wrapperObsLocal').show();
        } else {
            $('#wrapperObsLocal').hide();
        }

        if (obsGeneralTexto !== '') {
            $('#obsGeneralTexto').text(obsGeneralTexto);
            $('#wrapperObsGeneral').show();
        } else {
            $('#wrapperObsGeneral').hide();
        }

        if (obsLocalTexto !== '' || obsGeneralTexto !== '') {
            $('#observacionesVistaLocalContainer').show();
        } else {
            $('#observacionesVistaLocalContainer').hide();
        }

        setTimeout(() => {
            $('.table-container').each(function() {
                const tableEl = $(this);
                const firstRow = tableEl.find('thead tr').eq(0);
                if (firstRow.length) {
                    const h1 = firstRow.outerHeight();
                    const secondRow = tableEl.find('thead tr').eq(1);
                    secondRow.find('th').css('top', h1 + 'px');
                    secondRow.find('.sticky-left-0, .sticky-left-1').css('top', h1 + 'px');
                }
            });
        }, 100);
    }

    function obtenerHtmlTabla(data, primerDia, sucursalNombre, esFiltroUnSoloVendedor, nDias = 7) {
        const agenda = data.agenda;
        const nombres = data.nombres;
        const nombresSucursales = data.nombresSucursales || {};
        const promociones = data.promociones || {};
        const saldosAnteriores = data.saldosAnteriores || {};

        let fechasSemana = [];
        const diasSemanaNombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        const obtenerPrioridadCargo = (id) => {
            const info = nombres[id] || nombres[parseInt(id)] || {};
            const tarea = info.tarea ? info.tarea.trim().toUpperCase() : '';
            if (tarea === 'ENCARGADA') return 1;
            if (tarea === 'SUB ENCARGADA') return 2;
            if (tarea === 'VENDEDORA') return 3;
            if (tarea === 'VENDEDOR') return 4;
            return 5;
        };

        const vendedoresOrdenados = Object.keys(agenda).sort((a, b) => {
            const priorA = obtenerPrioridadCargo(a);
            const priorB = obtenerPrioridadCargo(b);
            if (priorA !== priorB) {
                return priorA - priorB;
            }
            const nomA = (nombres[a]?.nombre || a).toLowerCase();
            const nomB = (nombres[b]?.nombre || b).toLowerCase();
            return nomA.localeCompare(nomB);
        });

        let sumasHoras = Array.from({ length: nDias }, () => Array(25).fill(0));

        let html = '<table class="table table-bordered table-sm mb-0 table-horarios">';
        html += '<thead>';

        html += '<tr class="align-middle sticky-top-0">';
        html += '<th class="sticky-left-0 bg-white col-vendedor">Vendedor</th>';
        html += '<th class="sticky-left-1 bg-white col-totales">Total Hs</th>';

        for (let i = 0; i < nDias; i++) {
            let fechaActual = new Date(primerDia);
            fechaActual.setDate(primerDia.getDate() + i);
            const fechaISO = fechaActual.toISOString().split('T')[0];
            fechasSemana.push(fechaISO);

            const diaNum = String(fechaActual.getDate()).padStart(2, '0');
            const mesNum = String(fechaActual.getMonth() + 1).padStart(2, '0');

            const dayIndex = (fechaActual.getDay() + 6) % 7;

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

            html += `<th colspan="21" class="dia-header text-center fw-bold bg-light" style="min-width: 500px;">
                        <div>${diasSemanaNombres[dayIndex]} ${diaNum}/${mesNum}</div>
                        ${sucursalTexto}
                    </th>`;
        }
        html += '</tr>';

        html += '<tr class="align-middle sticky-top-1">';
        html += '<th class="sticky-left-0 bg-white col-vendedor"></th>';
        html += '<th class="sticky-left-1 bg-white col-totales"></th>';
        for (let d = 0; d < nDias; d++) {
            for (let h = 7; h <= 24; h++) {
                const label = (h === 24) ? '00' : h;
                html += `<th class="sticky-top-1 text-center" style="width: 30px; min-width: 30px;">${label}</th>`;
            }
            html += '<th class="sticky-top-1 text-center" style="width:30px; background-color:#e9ecef;" title="Franco">F</th>';
            html += '<th class="sticky-top-1 text-center" style="width:40px; background-color:#f8f9fa;" title="Turno">T</th>';
            html += '<th class="sticky-top-1 header-dia-fin text-center" style="width:30px; background-color:#e9ecef;" title="Nota"><i class="bi bi-chat-square-text"></i></th>';
        }
        html += '</tr></thead>';

        html += '<tbody>';
        vendedoresOrdenados.forEach(id => {
            const datos = agenda[id] || {};
            const infoVend = nombres[id] || nombres[parseInt(id)] || { nombre: `Vendedor ${id}`, tarea: '' };
            const nombre = infoVend.nombre;
            const tarea = infoVend.tarea;
            const saldoBase = saldosAnteriores[id] || saldosAnteriores[parseInt(id)] || 0;

            let tH = 0, tE = 0, tD = 0, tP = 0, tC = 0;

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

                if (data.bloqueosExternos && data.bloqueosExternos[id] && data.bloqueosExternos[id][fecha]) {
                    Object.values(data.bloqueosExternos[id][fecha]).forEach(sucId => {
                        if (sucId > 0) tH += 1;
                    });
                }
            });

            const saldoFinal = saldoBase + (tP - tD);
            html += '<tr>';

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

        // FILA 1: TOTAL HS
        html += '<tr class="total-por-hora-row">';
        html += '<td class="sticky-left-0 bg-light fw-bold text-start p-2 col-vendedor">Total Hs</td>';
        html += '<td class="sticky-left-1 bg-light col-totales"></td>';
        for (let d = 0; d < nDias; d++) {
            for (let h = 7; h <= 24; h++) {
                const suma = sumasHoras[d][h] || 0;
                const fmtSuma = (suma % 1 === 0 ? suma : suma.toFixed(1));
                const colorClass = suma > 0 ? 'text-success' : 'text-muted';
                html += `<td class="text-center fw-bold bg-light ${colorClass}" style="font-size: 0.8rem; min-width: 30px; width: 30px;">${suma > 0 ? fmtSuma : '0'}</td>`;
            }
            html += '<td class="bg-light" style="width:30px; min-width:30px;"></td><td class="bg-light" style="width:45px; min-width:45px;"></td><td class="bg-light footer-dia-fin" style="width:30px; min-width:30px;"></td>';
        }
        html += '</tr>';

        // FILA 3: PROMOCIONES (BANCARIAS)
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

        // FILA 4: PROMOCIONES INTERNAS
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
        html += '<tfoot></tfoot></table>';
        return html;

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
        const texto = $(this).data('texto') || '';
        $('#feedTexto').val(texto);
        
        // Actualizar contador al abrir
        updateCharCount(texto.length);
        
        new bootstrap.Modal(document.getElementById('modalFeedback')).show();
    });

    // Control de caracteres en el textarea de feedback
    $(document).on('input', '#feedTexto', function () {
        updateCharCount($(this).val().length);
    });

    function updateCharCount(len) {
        const max = 222;
        const charCountEl = $('#charCount');
        const warningEl = $('#charLimitWarning');
        
        if (charCountEl.length) {
            charCountEl.text(`${len} / ${max}`);
        }
        if (warningEl.length) {
            if (len >= max) {
                warningEl.show();
            } else {
                warningEl.hide();
            }
        }
    }

    $('#btnGuardarFeed').on('click', function () {
        const btn = $(this);
        const btnCancel = btn.siblings('.btn-secondary');
        const originalText = btn.html();

        // Resolver sucursal
        let sucursalId = $('#sucursalSelector').val();
        if (Array.isArray(sucursalId)) {
            sucursalId = sucursalId.length === 1 ? sucursalId[0] : null;
        }
        if (!sucursalId || sucursalId == 0) {
            const vId = $('#feedVendedor').val();
            if (lastDataLoaded && Array.isArray(lastDataLoaded)) {
                for (const res of lastDataLoaded) {
                    if (res.data && res.data.agenda && res.data.agenda[vId]) {
                        const det = res.data.agenda[vId].sucursal_detalle;
                        if (det) {
                            for (let f in det) {
                                for (let h in det[f]) {
                                    if (det[f][h] > 0) { sucursalId = det[f][h]; break; }
                                }
                                if (sucursalId > 0) break;
                            }
                        }
                        if (sucursalId > 0) break;
                        if (res.sucursalId > 0) { sucursalId = res.sucursalId; break; }
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

        // Cerrar el modal de feedback
        bootstrap.Modal.getInstance(document.getElementById('modalFeedback')).hide();
        btn.prop('disabled', false).html(originalText);
        btnCancel.prop('disabled', false);

        // Mostrar modal bloqueante de envío
        mostrarModalEnviando(1);

        let intento = 0;
        const maxIntentos = 3;

        function realizarEnvio() {
            intento++;
            const retraso = Math.min(2000 * Math.pow(2, intento - 1), 30000);
            actualizarModalEnviando(intento);
            $.ajax({
                url: apiVistaURL,
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                timeout: 15000,
                success: function (res) {
                    if (res.status === 'ok') {
                        cerrarModalEnviando();
                        $('#btnVerAgenda').trigger('click');
                    } else {
                        console.warn('Feedback: respuesta inesperada del servidor:', res);
                        mostrarErrorEnvio(res.error || 'El servidor rechazó la solicitud.');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.warn(`Feedback: intento ${intento} fallido. Status: ${jqXHR.status}, Text: ${textStatus}`);
                    
                    // Si el error es un error HTTP del servidor (>= 400), no reintentar
                    if (jqXHR.status >= 400) {
                        let errorMsg = 'Error del servidor.';
                        try {
                            if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
                                errorMsg = jqXHR.responseJSON.error;
                            } else if (jqXHR.responseText) {
                                const parsed = JSON.parse(jqXHR.responseText);
                                if (parsed && parsed.error) errorMsg = parsed.error;
                            }
                        } catch(e) {}
                        mostrarErrorEnvio(errorMsg);
                        return;
                    }

                    // Si es un error de timeout / conexión
                    if (intento < maxIntentos) {
                        console.warn(`Reintentando en ${retraso / 1000}s...`);
                        setTimeout(realizarEnvio, retraso);
                    } else {
                        mostrarErrorEnvio('No se pudo establecer conexión con el servidor tras varios intentos.');
                    }
                }
            });
        }
        realizarEnvio();
    });

    // ── Modal bloqueante de envío (sin botón de cerrar, backdrop estático) ──
    function mostrarModalEnviando(intento) {
        if (document.getElementById('modalEnviandoFeedback')) return;
        const el = document.createElement('div');
        el.id = 'modalEnviandoFeedback';
        el.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:10000;
                display:flex;align-items:center;justify-content:center;">
                <div id="enviandoCard" style="background:white;border-radius:12px;padding:32px 40px;text-align:center;
                    box-shadow:0 8px 32px rgba(0,0,0,0.3);min-width:320px;max-width:420px;">
                    <div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem;" role="status"></div>
                    <h5 class="mb-1 fw-bold text-dark">Guardando feedback</h5>
                    <p id="enviandoSubtexto" class="text-muted mb-0" style="font-size:0.9rem;">
                        Conectando con el servidor...
                    </p>
                </div>
            </div>`;
        document.body.appendChild(el);
    }

    function actualizarModalEnviando(intento) {
        const sub = document.getElementById('enviandoSubtexto');
        if (!sub) return;
        if (intento === 1) {
            sub.textContent = 'Enviando...';
        } else {
            sub.innerHTML = `<span class="text-warning fw-semibold">Sin conexión.</span> Reintentando automáticamente... (intento ${intento})`;
        }
    }

    function cerrarModalEnviando() {
        const el  = document.getElementById('modalEnviandoFeedback');
        const card = document.getElementById('enviandoCard');
        if (!el) return;
        // Reemplazar contenido de la tarjeta blanca con el mensaje de éxito
        if (card) {
            card.innerHTML = `
                <div style="color:#198754; font-size:3.5rem; margin-bottom:8px; line-height:1;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h5 style="margin:0 0 6px; font-weight:700; color:#1a1a1a;">¡Feedback guardado!</h5>
                <p style="margin:0; font-size:0.9rem; color:#666;">El mensaje fue enviado correctamente.</p>`;
        }
        setTimeout(() => { el.remove(); }, 2200);
    }

    function mostrarErrorEnvio(mensaje) {
        const el = document.getElementById('modalEnviandoFeedback');
        const card = document.getElementById('enviandoCard');
        if (!el) return;
        
        if (card) {
            card.innerHTML = `
                <div style="color:#dc3545; font-size:3.5rem; margin-bottom:8px; line-height:1;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <h5 style="margin:0 0 6px; font-weight:700; color:#1a1a1a;">No se pudo guardar</h5>
                <p style="margin:0 0 16px; font-size:0.9rem; color:#666; word-break:break-word;">${mensaje}</p>
                <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" id="btnCerrarErrorEnviando" style="border-radius:6px;">
                    Cerrar y corregir
                </button>`;
            
            $('#btnCerrarErrorEnviando').on('click', function() {
                el.remove();
                const modalEl = document.getElementById('modalFeedback');
                if (modalEl) {
                    new bootstrap.Modal(modalEl).show();
                }
            });
        }
    }



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

    // --- EXPORTAR MATRIZ A PDF (DOS PÁGINAS: Lun-Jue / Vie-Dom) ---
    $('#btnExportarPDF').on('click', async function () {
        if (!lastDataLoaded) return;

        const btn = $(this);
        const originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Generando PDF...');
        btn.prop('disabled', true);

        try {
            const primerDia = lastPrimerDia;
            let rangoFechas;
            if (lastNDias === 1) {
                const dd = String(primerDia.getDate()).padStart(2, '0');
                const mm = String(primerDia.getMonth() + 1).padStart(2, '0');
                const aaaa = primerDia.getFullYear();
                rangoFechas = `Día ${dd}/${mm}/${aaaa}`;
            } else {
                const ultimoDia = new Date(primerDia);
                ultimoDia.setDate(primerDia.getDate() + 6);
                rangoFechas = `Semana ${primerDia.toISOString().split('T')[0]} al ${ultimoDia.toISOString().split('T')[0]}`;
            }

            const diasNombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

            // ─── CALIBRACIÓN: renderizamos al ancho exacto del A4 landscape útil ───
            // A4 landscape = 297mm × 210mm  →  a 96dpi ≈ 1122 × 794px
            // Con 8mm de margen: área útil ≈ 1062 × 734px
            const CANVAS_W = 1062;  // px — ancho del contenedor de render
            const SCALE    = 2;     // html2canvas scale (alta calidad de impresión)
            const F_TITLE  = '11px';
            const F_DIA    = '9px';
            const F_HEADER = '8px';
            const F_CELL   = '8px';
            const F_PROMO  = '7px';
            const ROW_H    = '15px';
            // ─────────────────────────────────────────────────────────────────────────

            // Función que genera el bloque HTML para un rango de días
            function generarHtmlPagina(desdeDia, hastaDia) {
                let html = `<div style="font-family: Arial, Helvetica, sans-serif; background: white; padding: 8px; width: ${CANVAS_W}px; box-sizing: border-box;">`;

                lastDataLoaded.forEach((resultado, index) => {
                    const sucursalNombre = resultado.sucursalNombre || 'Sucursal';
                    const agenda         = resultado.data.agenda || {};
                    const nombres        = resultado.data.nombres || {};
                    const promos         = resultado.data.promociones || {};
                    const promosInt      = resultado.data.promociones_internas || {};
                    const bloqueosExt    = resultado.data.bloqueosExternos || {};

                    const prioridad = (id) => {
                        const info = nombres[id] || nombres[parseInt(id)] || {};
                        const tarea = info.tarea ? info.tarea.trim().toUpperCase() : '';
                        if (tarea === 'ENCARGADA') return 1;
                        if (tarea === 'SUB ENCARGADA') return 2;
                        if (tarea === 'VENDEDORA') return 3;
                        if (tarea === 'VENDEDOR') return 4;
                        return 5;
                    };

                    const vIds = Object.keys(agenda).sort((a, b) => {
                        const d = prioridad(a) - prioridad(b);
                        if (d !== 0) return d;
                        return (nombres[a]?.nombre || a).toLowerCase().localeCompare((nombres[b]?.nombre || b).toLowerCase());
                    });

                    // Título de sucursal
                    html += `<div style="font-size:${F_TITLE}; font-weight:bold; color:#1a252f;
                        margin-top:${index > 0 ? '10px' : '0'}; margin-bottom:4px;
                        border-bottom:2px solid #2c3e50; padding-bottom:2px;">
                        Grilla Horario — ${sucursalNombre.toUpperCase()} &nbsp;|&nbsp; ${rangoFechas}
                    </div>`;

                    for (let d = desdeDia; d <= hastaDia && d < lastNDias; d++) {
                        const fAct   = new Date(primerDia);
                        fAct.setDate(primerDia.getDate() + d);
                        const fISO   = fAct.toISOString().split('T')[0];
                        const ddStr  = String(fAct.getDate()).padStart(2, '0');
                        const mmStr  = String(fAct.getMonth() + 1).padStart(2, '0');
                        const dayIdx = (fAct.getDay() + 6) % 7;
                        const NCOLS  = 18; // horas 7..24

                        // Ancho calculado de las columnas de horas:
                        // total útil = CANVAS_W - 16px padding
                        // nombre: 140px fijo, F: 16px, Hs: 22px
                        // horas: resto / NCOLS
                        const util    = CANVAS_W - 16;
                        const fixedW  = 140 + 16 + 22;
                        const horaW   = Math.floor((util - fixedW) / NCOLS);

                        html += `
                        <div style="margin-bottom:4px;">
                            <div style="background:#2c3e50; color:white; padding:2px 6px;
                                font-size:${F_DIA}; font-weight:bold; line-height:16px;">
                                ${diasNombres[dayIdx]} ${ddStr}/${mmStr}
                            </div>
                            <table style="width:100%; border-collapse:collapse; font-size:${F_CELL};
                                table-layout:fixed; line-height:1;">
                                <colgroup>
                                    <col style="width:140px;">
                                    ${Array.from({length: NCOLS}, () => `<col style="width:${horaW}px;">`).join('')}
                                    <col style="width:16px;">
                                    <col style="width:22px;">
                                </colgroup>
                                <thead>
                                    <tr style="background:#eaedf0; height:14px;">
                                        <th style="border:1px solid #bbb; padding:1px 3px; text-align:left;
                                            font-size:${F_HEADER}; overflow:hidden; white-space:nowrap;">Vendedor</th>
                                        ${Array.from({length: NCOLS}, (_, i) => {
                                            const h  = i + 7;
                                            const lb = (h === 24) ? '00' : h;
                                            return `<th style="border:1px solid #bbb; padding:0; text-align:center;
                                                font-size:${F_HEADER};">${lb}</th>`;
                                        }).join('')}
                                        <th style="border:1px solid #bbb; padding:0; text-align:center; font-size:${F_HEADER};">F</th>
                                        <th style="border:1px solid #bbb; padding:0; text-align:center; font-size:${F_HEADER};">Hs</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                        vIds.forEach(vid => {
                            const inf   = nombres[vid] || nombres[parseInt(vid)] || { nombre: String(vid) };
                            const dat   = agenda[vid] || {};
                            const franco = dat.francos && dat.francos[fISO];
                            let tH = 0;

                            if (!franco && dat.horarios && dat.horarios[fISO]) {
                                Object.entries(dat.horarios[fISO]).forEach(([, t]) => {
                                    if (t && t !== 'vacaciones') tH += t.endsWith('_media') ? 0.5 : 1;
                                });
                            }
                            if (bloqueosExt[vid] && bloqueosExt[vid][fISO]) {
                                Object.values(bloqueosExt[vid][fISO]).forEach(s => { if (s > 0) tH += 1; });
                            }

                            const nombre = inf.nombre.length > 24 ? inf.nombre.substring(0, 23) + '…' : inf.nombre;

                            html += `<tr style="height:${ROW_H};">
                                <td style="border:1px solid #ddd; padding:1px 3px; font-weight:600;
                                    overflow:hidden; white-space:nowrap; text-overflow:ellipsis;">${nombre}</td>`;

                            for (let h = 7; h <= 24; h++) {
                                const hb        = (h === 24) ? 0 : h;
                                let tx = '', bg = '';
                                const tipo      = (!franco && dat.horarios && dat.horarios[fISO]) ? (dat.horarios[fISO][hb] || '') : null;
                                const bloqueo   = (bloqueosExt[vid] && bloqueosExt[vid][fISO]) ? bloqueosExt[vid][fISO][hb] : null;

                                if (tipo) {
                                    const base = tipo.replace('_media', '');
                                    tx = tipo.endsWith('_media') ? '½' : 'X';  bg = '#198754';
                                    if (base === 'caja')      { tx = 'C'; bg = '#0dcaf0'; }
                                    else if (base === 'extra')    { tx = 'A'; bg = '#0d6efd'; }
                                    else if (base === 'devuelve') { tx = 'D'; bg = '#ffc107'; }
                                    else if (base === 'permiso')  { tx = 'P'; bg = '#d63384'; }
                                    else if (base === 'vacaciones'){ tx = 'V'; bg = '#f3e5f5'; }
                                    else if (base === 'ausencia') { tx = 'AU'; bg = '#6c757d'; }
                                } else if (bloqueo > 0) {
                                    tx = 'Ø'; bg = '#343a40';
                                }

                                const col = (bg && bg !== '#f3e5f5') ? 'white' : (bg === '#f3e5f5' ? '#6a1b9a' : '');
                                html += `<td style="border:1px solid #ddd; padding:0; text-align:center;
                                    background:${bg}; color:${col}; font-weight:700;">${tx}</td>`;
                            }

                            const fBg = franco ? '#6f42c1' : '';
                            html += `
                                <td style="border:1px solid #ddd; padding:0; text-align:center;
                                    background:${fBg}; color:white; font-weight:bold;">${franco ? 'F' : ''}</td>
                                <td style="border:1px solid #ddd; padding:0; text-align:center; font-weight:bold;">
                                    ${tH > 0 ? (tH % 1 === 0 ? tH : tH.toFixed(1)) : '-'}</td>
                            </tr>`;
                        });

                        // Fila de promociones (pie de día)
                        const allPromos = [...(promos[fISO] || []), ...(promosInt[fISO] || [])];
                        if (allPromos.length > 0) {
                            html += `<tr style="background:#eef2ff;">
                                <td style="border:1px solid #ddd; padding:1px 4px; font-size:${F_PROMO};
                                    font-weight:bold; color:#1a237e; white-space:nowrap;">Promos:</td>
                                <td colspan="${NCOLS + 2}" style="border:1px solid #ddd; padding:1px 4px;
                                    font-size:${F_PROMO}; color:#333;">${allPromos.join(' | ')}</td>
                            </tr>`;
                        }

                        html += `</tbody></table></div>`;
                    }
                });

                html += `</div>`;
                return html;
            }

            // ─── Grupos de días ───────────────────────────────────────────────
            let gruposDias;
            if (lastNDias <= 1) {
                gruposDias = [[0, 0]];
            } else {
                gruposDias = [[0, 3], [4, 6]]; // Lun–Jue | Vie–Dom
            }

            // ─── Generar PDF ──────────────────────────────────────────────────
            const { jsPDF } = window.jspdf;
            const pdf    = new jsPDF('l', 'mm', 'a4');   // landscape A4
            const pdfW   = pdf.internal.pageSize.getWidth();   // 297 mm
            const pdfH   = pdf.internal.pageSize.getHeight();  // 210 mm
            const mMM    = 8;                                  // margen 8 mm
            const usableW = pdfW - mMM * 2;
            const usableH = pdfH - mMM * 2;

            for (let gi = 0; gi < gruposDias.length; gi++) {
                const [dDesde, dHasta] = gruposDias[gi];
                if (dDesde >= lastNDias) continue;

                const htmlPagina = generarHtmlPagina(dDesde, dHasta);

                // Div del mismo ancho que el área de render calibrada
                const tempDiv = $(`<div style="width:${CANVAS_W}px; position:absolute; left:-9999px; top:0; background:white;"></div>`);
                tempDiv.html(htmlPagina);
                $('body').append(tempDiv);

                await new Promise(r => setTimeout(r, 250));

                const canvas = await html2canvas(tempDiv[0], {
                    scale: SCALE,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    width: CANVAS_W,
                    windowWidth: CANVAS_W
                });
                tempDiv.remove();

                const imgData = canvas.toDataURL('image/jpeg', 0.93);
                if (gi > 0) pdf.addPage();

                // El canvas tiene CANVAS_W*SCALE px de ancho → mapeamos a usableW mm
                let imgW = usableW;
                let imgH = (canvas.height / canvas.width) * imgW;
                if (imgH > usableH) {
                    imgH = usableH;
                    imgW = (canvas.width / canvas.height) * imgH;
                }

                const offsetX = mMM + (usableW - imgW) / 2;
                pdf.addImage(imgData, 'JPEG', offsetX, mMM, imgW, imgH);
            }

            const sucNombres = lastDataLoaded.map(r => r.sucursalNombre.replace(/\s+/g, '_')).join('_');
            const semanaVal  = lastNDias === 1
                ? lastPrimerDia.toISOString().split('T')[0]
                : ($('#semanaSelector').val() || 'Semana');
            pdf.save(`Horarios_${sucNombres.length > 50 ? 'Multiples' : sucNombres}_${semanaVal}.pdf`);

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
