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

        if (min >= 7 && max <= 16) return 'TM';
        if (min >= 14 && max <= 23) return 'TT';
        if (min >= 10 && max <= 23) return 'FULL';

        const tieneTM = horas.some(h => h >= 7 && h <= 15);
        const tieneTT = horas.some(h => h >= 16 && h <= 23);
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

        // Inicializar sumatorias para el footer
        let sumasHoras = Array.from({ length: 7 }, () => Array(24).fill(0));

        // --- 1. CONSTRUCCIÓN DEL TH ---
        let html = '<table class="table table-bordered table-sm mb-0 table-horarios">';
        html += '<thead>';

        // FILA 1: Días
        html += '<tr class="align-middle sticky-top-0">';
        html += '<th class="sticky-left-0 bg-white" style="z-index: 20; min-width: 250px;">Vendedor</th>';
        html += '<th class="sticky-left-1 bg-white" style="z-index: 20; min-width: 100px;">Total Hs</th>';

        for (let i = 0; i < 7; i++) {
            let fechaActual = new Date(primerDia);
            fechaActual.setDate(primerDia.getDate() + i);
            const fechaISO = fechaActual.toISOString().split('T')[0];
            fechasSemana.push(fechaISO);

            const diaNum = String(fechaActual.getDate()).padStart(2, '0');
            const mesNum = String(fechaActual.getMonth() + 1).padStart(2, '0');

            html += `<th colspan="20" class="dia-header text-center fw-bold bg-light" style="min-width: 600px;">${diasSemanaNombres[i]} ${diaNum}/${mesNum}</th>`;
        }
        html += '</tr>';

        // FILA 2: Horas
        html += '<tr class="align-middle sticky-top-1">';
        html += '<th class="sticky-left-0 bg-white" style="z-index: 19;"></th>';
        html += '<th class="sticky-left-1 bg-white" style="z-index: 19;"></th>';
        for (let d = 0; d < 7; d++) {
            for (let h = 7; h <= 23; h++) {
                html += `<th class="sticky-top-1 text-center" style="width: 30px; min-width: 30px;">${h}</th>`;
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

            // Intentar buscar el nombre de forma más robusta (por si viene como número en el objeto nombres)
            const infoVend = nombres[id] || nombres[parseInt(id)] || { nombre: `Vendedor ${id}`, tarea: '' };
            const nombre = infoVend.nombre;
            const tarea = infoVend.tarea;
            const saldoBase = saldosAnteriores[id] || saldosAnteriores[parseInt(id)] || 0;

            let tH = 0, tE = 0, tD = 0, tP = 0;

            // Pre-calcular sumatorias para el vendedor
            fechasSemana.forEach((fecha, diaIndex) => {
                const esFranco = datos.francos && datos.francos[fecha];
                if (!esFranco && datos.horarios && datos.horarios[fecha]) {
                    for (let h = 7; h <= 23; h++) {
                        const tipo = datos.horarios[fecha][h];
                        if (tipo) {
                            if (tipo === 'normal' || tipo === 'caja') tH++;
                            else if (tipo === 'extra') tE++;
                            else if (tipo === 'devuelve') tD++;
                            else if (tipo === 'permiso') tP++;

                            // Sumar para el footer global
                            sumasHoras[diaIndex][h]++;
                        }
                    }
                }
            });

            // CORRECCIÓN SALDO: El saldo es P (Permisos) - D (Devuelve)
            const saldoFinal = saldoBase + (tP - tD);

            html += '<tr>';

            // Celda Vendedor (Sticky 0)
            const feedbackTexto = datos.feedback || '';
            const tieneFeedback = feedbackTexto.trim() !== '';
            html += `<td class="sticky-left-0 bg-white text-start p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-truncate" style="max-width: 180px;">
                                <div class="fw-bold mb-0" style="font-size: 0.9rem;">${nombre}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">${tarea}</small>
                            </div>
                            <button class="btn-feedback ${tieneFeedback ? 'tiene-feedback' : ''}" 
                                    data-id="${id}" data-nombre="${nombre}" data-texto="${feedbackTexto}"
                                    style="border: none; background: transparent; cursor: pointer; font-size: 1.2rem; color: ${tieneFeedback ? '#0d6efd' : '#ced4da'};">
                                <i class="bi bi-chat-dots-fill"></i>
                            </button>
                        </div>
                     </td>`;

            // Celda Totales (Sticky 1)
            let colorSaldo = 'bg-light text-secondary';
            if (saldoFinal > 0) colorSaldo = 'bg-danger text-white';
            else if (saldoFinal < 0) colorSaldo = 'bg-success text-white';

            html += `<td class="sticky-left-1 bg-white align-middle p-0" style="width: 100px; min-width: 100px;">
                        <div class="d-flex flex-column align-items-center" style="font-size: 0.8rem; line-height: 1.1; padding: 4px;">
                            <span class="text-success fw-bold" title="Horas Normales">H: ${tH}</span>
                            <span class="text-primary fw-bold" title="Horas Extras">E: ${tE}</span>
                            <span class="text-warning fw-bold" title="Horas a Devolver">D: ${tD}</span>
                            <span class="text-pink fw-bold" style="color: #d63384;" title="Permisos">P: ${tP}</span>
                            <span class="badge border ${colorSaldo} mt-1" style="font-size: 0.7rem; padding: 2px 4px;">
                                Saldo: ${saldoFinal}
                            </span>
                        </div>
                     </td>`;

            // Celdas de días
            fechasSemana.forEach((fecha, diaIndex) => {
                const esFranco = datos.francos && datos.francos[fecha];
                const nota = datos.notas && datos.notas[fecha];

                // Horas
                for (let h = 7; h <= 23; h++) {
                    let bgColor = '';
                    let texto = '';
                    let color = '';

                    const tipo = (!esFranco && datos.horarios && datos.horarios[fecha]) ? datos.horarios[fecha][h] : null;

                    if (esFranco) {
                        bgColor = '#e9ecef';
                    } else if (tipo) {
                        color = 'white';
                        if (tipo === 'extra') { bgColor = '#0d6efd'; texto = 'E'; }
                        else if (tipo === 'devuelve') { bgColor = '#fd7e14'; texto = 'D'; }
                        else if (tipo === 'permiso') { bgColor = '#d63384'; texto = 'P'; }
                        else if (tipo === 'caja') { bgColor = '#0dcaf0'; texto = 'C'; color = '#000'; }
                        else { bgColor = '#198754'; texto = 'X'; }
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
                        const horasDia = Object.keys(datos.horarios[fecha]).map(Number);
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

        // Fila de Totales por Hora
        html += '<tr class="total-por-hora-row">';
        html += '<td class="sticky-left-0 bg-light fw-bold text-start p-2">Total Hs</td>';
        html += '<td class="sticky-left-1 bg-light"></td>';
        for (let d = 0; d < 7; d++) {
            for (let h = 7; h <= 23; h++) {
                html += `<td class="text-center fw-bold bg-light" style="font-size: 0.8rem;">${sumasHoras[d][h] || ''}</td>`;
            }
            html += '<td class="bg-light"></td><td class="bg-light"></td><td class="bg-light footer-dia-fin"></td>';
        }
        html += '</tr>';

        // Fila de Promociones
        html += '<tr>';
        html += '<td class="sticky-left-0 bg-light fw-bold text-start p-2">Promociones</td>';
        html += '<td class="sticky-left-1 bg-light"></td>';
        fechasSemana.forEach((fecha, index) => {
            let promoHTML = '<span class="text-muted">-</span>';
            if (promociones[fecha] && promociones[fecha].length > 0) {
                promoHTML = `<div class="d-flex flex-wrap gap-1">
                    ${promociones[fecha].map(p => `<span class="badge bg-primary px-1" style="font-size: 0.7rem; white-space: normal; text-align: left;">${p}</span>`).join('')}
                </div>`;
            }
            html += `<td colspan="20" class="footer-dia-fin bg-light text-start p-1 align-top" style="max-width: 300px;">${promoHTML}</td>`;
        });
        html += '</tr>';

        html += '</tfoot></table>';
        container.html(html);
    }

    // --- MANEJO DEL MODAL DE FEEDBACK ---
    $(document).on('click', '.btn-feedback', function () {
        const id = $(this).data('id');
        const nom = $(this).data('nombre');
        const txt = $(this).data('texto');

        $('#feedVendedor').val(id);
        $('#feedNombre').text(nom);
        $('#feedTexto').val(txt || '');

        const modal = new bootstrap.Modal(document.getElementById('modalFeedback'));
        modal.show();
    });

    $('#btnGuardarFeed').on('click', function () {
        const sucursal = $('#sucursalSelector').val() || 0;
        const vendedor = $('#feedVendedor').val();
        const mensaje = $('#feedTexto').val();
        const semana = $('#semanaSelector').val();

        if (!semana) {
            mostrarModalConTitulo("Error", "Debe seleccionar una semana primero.", "error");
            return;
        }

        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);
        const fechaSemana = primerDia.toISOString().split('T')[0];

        $.ajax({
            url: apiVistaURL,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                accion: 'guardar_feedback',
                sucursal: sucursal,
                vendedor: vendedor,
                fechaSemana: fechaSemana,
                mensaje: mensaje
            }),
            success: function (res) {
                if (res.status === 'ok') {
                    mostrarModalConTitulo("Éxito", "Feedback guardado correctamente.", "success");
                    $('#btnVerAgenda').trigger('click');
                    bootstrap.Modal.getInstance(document.getElementById('modalFeedback')).hide();
                } else {
                    mostrarModalConTitulo("Error", "Error al guardar el feedback.", "error");
                }
            },
            error: function () {
                mostrarModalConTitulo("Error", "Error de conexión con el servidor.", "error");
            }
        });
    });

    // --- MOSTRAR MODAL DE NOTA DIARIA ---
    $(document).on('click', '.btn-nota-local.tiene-nota', function () {
        const nombre = $(this).data('nombre');
        const fecha = $(this).data('fecha');
        const nota = $(this).data('nota');

        if (nota && nota.trim() !== '') {
            mostrarModalConTitulo(`Nota para ${nombre} (${fecha})`, nota, 'warning');
        }
    });

    // --- FUNCIÓN HELPER PARA MODALES ---
    function mostrarModalConTitulo(titulo, mensaje, tipo = 'info') {
        const header = $('#modalMensajeHeader');
        const modalTitulo = $('#modalMensajeTitulo');
        const icono = $('#modalMensajeIcono');
        const texto = $('#modalMensajeTexto');

        header.removeClass('bg-success bg-danger bg-warning bg-info bg-primary');
        icono.html('');

        let colorClase = 'bg-info';
        let iconoClase = 'bi bi-info-circle-fill text-info';

        if (tipo === 'success') {
            colorClase = 'bg-success';
            iconoClase = 'bi bi-check-circle-fill text-success';
        }
        else if (tipo === 'error') {
            colorClase = 'bg-danger';
            iconoClase = 'bi bi-x-circle-fill text-danger';
        }
        else if (tipo === 'warning') {
            colorClase = 'bg-warning text-dark';
            iconoClase = 'bi bi-exclamation-triangle-fill text-warning';
        }

        header.addClass(colorClase);
        modalTitulo.text(titulo);
        icono.html(`<i class="${iconoClase}"></i>`);
        texto.html(mensaje.replace(/\n/g, '<br>'));

        const modal = new bootstrap.Modal(document.getElementById('modalMensaje'));
        modal.show();
    }

    // --- CONFIGURAR SELECTOR DE SEMANA ---
    function obtenerSemanaActual() {
        const hoy = new Date();
        const año = hoy.getFullYear();
        const primerEnero = new Date(año, 0, 1);
        const diasPasados = Math.floor((hoy - primerEnero) / (24 * 60 * 60 * 1000));
        const semana = Math.ceil((diasPasados + primerEnero.getDay() + 1) / 7);
        return `${año}-W${String(semana).padStart(2, '0')}`;
    }

    $('#semanaSelector').val(obtenerSemanaActual());
});
