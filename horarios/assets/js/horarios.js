$(document).ready(function () {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiHorariosURL = 'api/horarios_handler.php';
    let isMouseDown = false;
    let modoPintado = 'marcar';
    let fechasSemana = [];
    let modoPintadoHoras = 'normal';

    // Cargar Sucursales al inicio
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function (data) {
        $('#sucursalSelector').append('<option value="">Seleccione una sucursal...</option>');
        $.each(data, (key, sucursal) => $('#sucursalSelector').append(`<option value="${sucursal.NRO_SUCURSAL}">${sucursal.DESC_SUCURSAL}</option>`));
    });

    $('#tipoHoraSelector input').on('change', function () {
        modoPintadoHoras = $(this).val();
    });

    // ==========================================
    // NUEVA LÓGICA DE CARGA SEPARADA POR PASOS
    // ==========================================
    $('#cargarHorariosBtn').on('click', function () {
        console.log("--- INICIANDO CARGA ---");
        const sucursalId = $('#sucursalSelector').val();
        const semana = $('#semanaSelector').val();

        if (!sucursalId || !semana) {
            return alert('Por favor, seleccione una sucursal y una semana.');
        }

        $('#loader').show();
        $('#tablaHorariosContainer').empty();
        $('#accionesContainer').hide();
        $('#observacionesContainer, #guardarHorariosBtn, #agregarRefuerzoBtn').hide();
        $('#observacionesSemana').val('');
        $('#totalSemanalLocal').text('0');

        // PASO 1: Cargar SOLO los vendedores efectivos (base) y dibujar la tabla
        console.log("Paso 1: Buscando vendedores efectivos...");
        $.getJSON(`${apiDatosURL}?accion=vendedores&sucursal=${sucursalId}`)
            .done(function (vendedoresPermanentes) {
                console.log("Paso 1 OK. Vendedores efectivos encontrados:", vendedoresPermanentes.length);

                // Generamos la tabla con los efectivos
                generarTablaHorarios(vendedoresPermanentes, semana);

                // PASO 2: Ejecutar la función separada para buscar datos guardados y refuerzos
                verificarYCargarRefuerzos(sucursalId, semana);
            })
            .fail(function () {
                $('#tablaHorariosContainer').html('<p class="alert alert-danger">Error al cargar los vendedores de la sucursal.</p>');
                $('#loader').hide();
            });
    });

    // --- MOSTRAR MODAL FEEDBACK (SOLO LECTURA) ---
    $('#tablaHorariosContainer').on('click', '.icono-feedback', function () {
        // 1. Extraer datos del icono
        const mensaje = $(this).attr('title');
        const nombre = $(this).closest('tr').find('.text-truncate > div').text();

        // 2. Usar el modal genérico para mostrar el mensaje
        // (Verificamos que no sea el tooltip por defecto)
        if (mensaje && !mensaje.toLowerCase().includes('sin feedback')) {
            // Ponemos el nombre en el título del modal para contexto
            const titulo = `Feedback de Local para ${nombre}`;
            const msgFormateado = mensaje.replace('Feedback del Local: ', '');

            // Usamos una función para mostrar el modal de Bootstrap que ya tienes
            mostrarModalConTitulo(titulo, msgFormateado, 'info');
        }
    });

    // --- FUNCIÓN PARA MOSTRAR MENSAJES LINDOS (CON TÍTULO) ---
    function mostrarModalConTitulo(titulo, mensaje, tipo = 'info') {
        const header = $('#modalMensajeHeader');
        const modalTitulo = $('#modalMensajeTitulo');
        const icono = $('#modalMensajeIcono');
        const texto = $('#modalMensajeTexto');

        header.removeClass('bg-success bg-danger bg-warning bg-info');
        icono.html('');

        let colorClase = 'bg-info';
        let iconoClase = 'bi bi-info-circle-fill text-info';

        if (tipo === 'success') {
            colorClase = 'bg-success';
            iconoClase = 'bi bi-check-circle-fill text-success';
        } else if (tipo === 'error') {
            colorClase = 'bg-danger';
            iconoClase = 'bi bi-x-circle-fill text-danger';
        } else if (tipo === 'warning') {
            colorClase = 'bg-warning text-dark';
            iconoClase = 'bi bi-exclamation-triangle-fill text-warning';
        }

        header.addClass(colorClase);
        modalTitulo.text(titulo);
        icono.html(`<i class="${iconoClase}"></i>`);
        texto.html(mensaje); // Usamos .html() por si el mensaje tiene formato

        new bootstrap.Modal(document.getElementById('modalMensaje')).show();
    }

    /**
     * FUNCIÓN NUEVA: 
     * 1. Descarga los horarios/francos guardados.
     * 2. Compara los IDs guardados contra los que ya están en la tabla.
     * 3. Si faltan IDs (son refuerzos), busca sus nombres y agrega las filas.
     * 4. Finalmente pinta las X y E.
     */
    function verificarYCargarRefuerzos(sucursalId, semana) {
        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);
        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);
        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        console.log("Paso 2: Buscando datos guardados y refuerzos...");

        $.getJSON(`${apiHorariosURL}?sucursal=${sucursalId}&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`)
            .done(function (datosGuardados) {
                console.log("Datos guardados recibidos:", datosGuardados);

                // 1. Obtener IDs que tienen actividad (horarios o francos) en la BD
                // Convertimos todo a String para evitar errores de tipo (741 vs "741")
                const idsConActividad = (datosGuardados.vendedoresConActividad || []).map(id => String(id));

                // 2. Obtener IDs que YA están dibujados en la tabla
                const idsEnTabla = [];
                $('tbody tr').each(function () {
                    idsEnTabla.push(String($(this).data('vendedor-id')));
                });

                // 3. Filtrar: ¿Quiénes tienen actividad pero NO están en la tabla? -> Refuerzos Guardados
                const idsRefuerzosFaltantes = idsConActividad.filter(id => !idsEnTabla.includes(id) && id !== '0' && id !== 'null');

                console.log("IDs en tabla:", idsEnTabla);
                console.log("IDs con actividad en BD:", idsConActividad);
                console.log("DETECTADOS REFUERZOS FALTANTES:", idsRefuerzosFaltantes);

                if (idsRefuerzosFaltantes.length > 0) {
                    // Si hay refuerzos guardados que no aparecen, consultamos sus nombres
                    console.log("Recuperando datos de los refuerzos faltantes...");
                    $.ajax({
                        url: `${apiDatosURL}?accion=vendedoresPorLista`,
                        type: 'POST',
                        data: { listaVendedores: JSON.stringify(idsRefuerzosFaltantes) },
                        dataType: 'json',
                        success: function (infoRefuerzos) {
                            // Agregamos las filas a la tabla existente
                            agregarFilasRefuerzos(infoRefuerzos);

                            // AHORA SI: Pintamos los datos en la grilla completa
                            cargarDatosEnGrilla(datosGuardados);
                            finalizarCarga();
                        },
                        error: function (e) {
                            console.error("Error al recuperar nombres de refuerzos", e);
                            // Aun si falla el nombre, intentamos cargar la grilla
                            cargarDatosEnGrilla(datosGuardados);
                            finalizarCarga();
                        }
                    });
                } else {
                    // Si no faltaba nadie, simplemente pintamos
                    console.log("No faltan refuerzos. Pintando grilla directa.");
                    cargarDatosEnGrilla(datosGuardados);
                    finalizarCarga();
                }
            })
            .fail(function () {
                console.error("Error al cargar los datos guardados (horarios).");
                alert("Error al cargar los horarios guardados. Verifique la conexión.");
                $('#loader').hide();
            });
    }

    function agregarFilasRefuerzos(listaVendedores) {
        if (!listaVendedores || listaVendedores.length === 0) return;

        let filasHTML = '';
        listaVendedores.forEach(function (vendedor) {
            const vendedorId = vendedor.COD_VENDED;
            if ($('tr[data-vendedor-id="' + vendedorId + '"]').length === 0) {
                filasHTML += '<tr data-vendedor-id="' + vendedorId + '">';

                // Columna Nombre
                filasHTML += '<td class="fw-bold text-start ps-2">';
                filasHTML += '<div class="d-flex justify-content-between align-items-center">';
                filasHTML += '<div class="text-truncate" style="max-width: 180px;" title="' + vendedor.NOMBRE_VEN + '">';
                filasHTML += '<div>' + vendedor.NOMBRE_VEN + '</div>';
                filasHTML += '<small class="d-block text-muted fw-normal">' + vendedor.TAREA_HABITUAL + ' <span class="badge bg-warning text-dark">Refuerzo</span></small>';
                filasHTML += '</div>';
                filasHTML += '<span class="icono-feedback d-none ms-auto" data-vendedor-id="' + vendedorId + '" title="Sin Feedback" style="cursor:pointer;">';
                filasHTML += '<i class="bi bi-chat-left-dots-fill text-secondary fs-5"></i>';
                filasHTML += '</span>';
                filasHTML += '</div>';
                filasHTML += '</td>';

                // Columna Totales
                filasHTML += '<td class="total-horas-empleado align-middle p-1">';
                filasHTML += '<div class="d-flex flex-column align-items-center" style="font-size: 0.85rem; line-height: 1.1;">';
                filasHTML += '<span class="text-success fw-bold">H: <span class="total-n">0</span></span>';
                filasHTML += '<span class="text-primary fw-bold">E: <span class="total-e">0</span></span>';
                filasHTML += '<span class="text-warning fw-bold">D: <span class="total-d">0</span></span>';
                filasHTML += '<span class="badge bg-light text-secondary border mt-1 total-saldo-container" title="Banco de Horas (Deuda)" style="font-size:0.9em;">';
                filasHTML += 'Saldo: <span class="total-saldo">0</span>';
                filasHTML += '</span>';
                filasHTML += '</div>';
                filasHTML += '</td>';

                // Columnas de Días (CON TURNO POR DÍA)
                fechasSemana.forEach(function (fechaISO) {
                    // 1. Horas - YA ESTÁ CORRECTO: 7 a 23 (17 horas)
                    for (let h = 7; h <= 23; h++) {
                        filasHTML += '<td class="hora-celda" data-fecha="' + fechaISO + '" data-hora="' + h + '"></td>';
                    }

                    // 2. Franco
                    filasHTML += '<td class="dia-franco text-center p-0"><input type="checkbox" class="form-check-input franco-checkbox mt-1" value="' + fechaISO + '" style="transform:scale(0.8)"></td>';

                    // 3. NUEVO: TURNO POR DÍA
                    filasHTML += '<td class="turno-dia text-center align-middle p-0">';
                    filasHTML += '<span class="turno-dia-badge badge bg-light text-dark border" data-fecha="' + fechaISO + '" title="Sin horario asignado">-</span>';
                    filasHTML += '</td>';

                    // 4. Nota (FIN DE DÍA CON SEPARADOR)
                    filasHTML += '<td class="text-center p-0 celda-dia-fin"><button class="btn-nota" data-vendedor="' + vendedorId + '" data-fecha="' + fechaISO + '"><i class="bi bi-chat-square"></i></button></td>';
                });
                filasHTML += '</tr>';
            }
        });

        $('tbody').append(filasHTML);
    }

    function finalizarCarga() {
        // Mostramos el contenedor de acciones (donde están Guardar y Excel)
        $('#accionesContainer').show();

        // Aseguramos que los botones específicos también se vean
        $('#guardarHorariosBtn').show();
        $('#exportarQuickPassBtn').show();

        // Mostramos el resto de elementos
        $('#agregarRefuerzoBtn').show();
        $('#observacionesContainer').show();
        $('#leyendaContainer').show();

        // Ocultamos el spinner de carga
        $('#loader').hide();
        console.log("--- CARGA FINALIZADA ---");
    }

    // --- LÓGICA DE DESCARGA EXCEL (MODAL) ---

    // 1. Al tocar el botón negro "Excel QP" -> Abrimos el modal
    $('#exportarQuickPassBtn').off('click').on('click', function () {
        const semana = $('#semanaSelector').val();

        if (!semana) {
            mostrarModal("Por favor, seleccione una semana para exportar.", "warning");
            return;
        }

        // Abrimos el modal lindo
        new bootstrap.Modal(document.getElementById('modalConfirmarExcel')).show();
    });

    // 2. Al confirmar dentro del modal -> Ejecutamos la descarga
    $('#btnConfirmarDescarga').off('click').on('click', function () {
        const semana = $('#semanaSelector').val();
        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);

        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);

        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        // Cerramos el modal
        bootstrap.Modal.getInstance(document.getElementById('modalConfirmarExcel')).hide();

        // Redirigimos
        window.location.href = `api/exportar_quickpass.php?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`;
    });

    function generarTablaHorarios(vendedores, semana) {
        fechasSemana = [];
        const container = $('#tablaHorariosContainer');

        // Creamos un contenedor con scroll
        let tablaHTML = '<div class="table-container" style="max-height: 70vh; overflow-y: auto; position: relative;">';
        tablaHTML += '<table class="table table-bordered table-sm table-horarios">';

        // --- HEADER CON STICKY ---
        let headerHTML = '<thead>';

        // PRIMERA FILA STICKY (Días de la semana)
        headerHTML += '<tr class="align-middle sticky-top-0">';
        headerHTML += '<th class="sticky-left-0 bg-white" style="z-index: 20;">Vendedor</th>';
        headerHTML += '<th class="sticky-left-1 bg-white" style="z-index: 20;">Total Hs</th>';

        const diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);

        for (let i = 0; i < 7; i++) {
            let fechaActual = new Date(primerDia);
            fechaActual.setDate(primerDia.getDate() + i);
            const fechaISO = fechaActual.toISOString().split('T')[0];
            fechasSemana.push(fechaISO);
            const dia = String(fechaActual.getDate()).padStart(2, '0');
            const mes = String(fechaActual.getMonth() + 1).padStart(2, '0');

            // --- CAMBIO: Añadir clase 'dia-header' para el borde derecho ---
            headerHTML += `<th colspan="20" class="sticky-top-0 bg-light dia-header">` + diasSemana[i] + ' ' + dia + '/' + mes + '</th>';
        }
        headerHTML += '</tr>';

        // SEGUNDA FILA STICKY (Horas)
        headerHTML += '<tr class="align-middle sticky-top-1">';
        headerHTML += '<th class="sticky-left-0 bg-white" style="z-index: 19;"></th>';
        headerHTML += '<th class="sticky-left-1 bg-white" style="z-index: 19;"></th>';

        for (let d = 0; d < 7; d++) {
            for (let h = 7; h <= 23; h++) {
                headerHTML += `<th class="sticky-top-1">` + h + '</th>';
            }

            headerHTML += `<th class="sticky-top-1" style="width:30px; background-color:#e9ecef;" title="Franco">F</th>`;
            headerHTML += `<th class="sticky-top-1" style="width:40px; background-color:#f8f9fa;" title="Turno asignado">T</th>`;
            headerHTML += `<th class="sticky-top-1 header-dia-fin" style="width:30px; background-color:#e9ecef;" title="Observación Diaria"><i class="bi bi-chat-square-text"></i></th>`;
        }
        headerHTML += '</tr></thead>';

        // --- BODY ---
        let bodyHTML = '<tbody>';
        vendedores.forEach(function (vendedor) {
            const vendedorId = vendedor.COD_VENDED;

            bodyHTML += '<tr data-vendedor-id="' + vendedorId + '" data-nombre="' + vendedor.NOMBRE_VEN + '" data-saldo-inicial="0">';

            // Columna Nombre
            bodyHTML += '<td class="fw-bold text-start ps-2">';
            bodyHTML += '<div class="d-flex justify-content-between align-items-center">';
            bodyHTML += '<div class="text-truncate" style="max-width: 180px;" title="' + vendedor.NOMBRE_VEN + '">';
            bodyHTML += '<div>' + vendedor.NOMBRE_VEN + '</div>';
            bodyHTML += '<small class="d-block text-muted fw-normal">' + vendedor.TAREA_HABITUAL + '</small>';
            bodyHTML += '</div>';
            bodyHTML += '<span class="icono-feedback ms-auto" data-vendedor-id="' + vendedorId + '" title="Sin Feedback" style="cursor:pointer; opacity: 0.2;">';
            bodyHTML += '<i class="bi bi-chat-left-dots-fill text-secondary fs-5"></i>';
            bodyHTML += '</span>';
            bodyHTML += '</div>';
            bodyHTML += '</td>';

            // Columna Totales
            bodyHTML += '<td class="total-horas-empleado align-middle p-1">';
            bodyHTML += '<div class="d-flex flex-column align-items-center" style="font-size: 0.85rem; line-height: 1.1;">';
            bodyHTML += '<span class="text-success fw-bold" title="Horas Normales (H)">H: <span class="total-n">0</span></span>';
            bodyHTML += '<span class="text-primary fw-bold" title="Horas Extras (E)">E: <span class="total-e">0</span></span>';
            bodyHTML += '<span class="text-warning fw-bold" title="Horas Devuelve (D)">D: <span class="total-d">0</span></span>';
            bodyHTML += '<span class="badge bg-light text-secondary border mt-3 total-saldo-container" title="Banco de Horas (Deuda)" style="font-size:0.9em;">';
            bodyHTML += 'Saldo: <span class="total-saldo">0</span>';
            bodyHTML += '</span>';
            bodyHTML += '</div>';
            bodyHTML += '</td>';

            // Columnas Días
            fechasSemana.forEach(function (fechaISO, diaIndex) { // Agregamos diaIndex aquí
                // 1. Celdas de Hora (7 a 23) - 17 celdas
                for (let h = 7; h <= 23; h++) {
                    bodyHTML += `<td class="hora-celda" data-fecha="${fechaISO}" data-hora="${h}"></td>`;
                }

                // 2. Celda Franco
                bodyHTML += `<td class="dia-franco text-center align-middle p-0">`;
                bodyHTML += '<input type="checkbox" class="form-check-input franco-checkbox mt-1" value="' + fechaISO + '" style="transform: scale(0.8);">';
                bodyHTML += '</td>';

                // 3. Celda Turno del día
                bodyHTML += `<td class="turno-dia text-center align-middle p-0">`;
                bodyHTML += '<span class="turno-dia-badge badge bg-light text-dark border" data-fecha="' + fechaISO + '" title="Sin horario asignado">-</span>';
                bodyHTML += '</td>';

                // 4. Celda Nota (FIN DE DÍA CON SEPARADOR)
                bodyHTML += `<td class="text-center align-middle p-0 celda-dia-fin">`;
                bodyHTML += '<button class="btn-nota border-0 bg-transparent text-secondary p-0" data-vendedor="' + vendedorId + '" data-fecha="' + fechaISO + '" title="Agregar nota">';
                bodyHTML += '<i class="bi bi-chat-square"></i>';
                bodyHTML += '</button>';
                bodyHTML += '</td>';
            });
            bodyHTML += '</tr>';
        });
        bodyHTML += '</tbody>';

        // --- FOOTER CON STICKY ---
        let footerHTML = '<tfoot>';

        // Fila de Total Hs STICKY BOTTOM
        footerHTML += '<tr class="total-por-hora-row sticky-bottom">';
        footerHTML += '<td class="sticky-left-0 bg-white" style="z-index: 18;"><strong>Total Hs</strong></td>';
        footerHTML += '<td class="sticky-left-1 bg-white" style="z-index: 18;"></td>';

        for (let d = 0; d < 7; d++) {
            for (let h = 7; h <= 23; h++) {
                footerHTML += `<td class="total-hora-columna text-center fw-bold" data-hora="${h}" data-dia-index="${d}" style="font-size: 0.85rem; background-color: #e9ecef;">0</td>`;
            }
            // También agregar un relleno para las columnas F, T, N con la clase de borde para consistencia
            footerHTML += `<td></td>`;
            footerHTML += `<td></td>`;
            footerHTML += `<td class="footer-dia-fin"></td>`;
        }
        footerHTML += '</tr>';

        // Fila Promociones
        footerHTML += '<tr><td><strong>Promociones</strong></td><td></td>';
        fechasSemana.forEach(function (fechaISO, diaIndex) { // Agregamos diaIndex aquí
            footerHTML += `<td colspan="20" class="promo-cell small text-start align-top p-1 footer-dia-fin" data-fecha="${fechaISO}" style="white-space: normal; min-height: 50px;">-</td>`;
        });
        footerHTML += '</tr>';

        footerHTML += '</tfoot></table></div>';

        tablaHTML += headerHTML + bodyHTML + footerHTML;
        container.html(tablaHTML);

        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);

        // Agregar esta línea para actualizar totales inmediatamente
        setTimeout(function () {
            actualizarSumatorias();
        }, 100);

        return { inicio: primerDia.toISOString().split('T')[0], fin: ultimoDia.toISOString().split('T')[0] };
    }

    function cargarDatosEnGrilla(data) {
        if (data.es_plantilla_base) {
            mostrarModal("Se cargaron los horarios de la <b>Plantilla Base</b> (Semana " + (data.tipo_semana || 'Automática') + ").<br>Recuerde guardar para confirmar.", "info");
        }

        // Carga de Horarios (N, C, P, E, D)
        if (data.horarios) {
            data.horarios.forEach(function (h) {
                const celda = $('tr[data-vendedor-id="' + h.COD_VENDED + '"] td[data-fecha="' + h.FECHA + '"][data-hora="' + h.HORA + '"]');
                celda.removeClass('marcado hora-extra hora-devuelve hora-permiso hora-caja');
                const tipo = h.TIPO_HORA.toLowerCase();

                if (tipo === 'extra') {
                    celda.addClass('hora-extra').text('E');
                } else if (tipo === 'devuelve') {
                    celda.addClass('hora-devuelve').text('D');
                } else if (tipo === 'permiso') {
                    celda.addClass('hora-permiso').text('P');
                } else if (tipo === 'caja') {
                    celda.addClass('hora-caja').text('C');
                } else {
                    celda.addClass('marcado').text('X'); // Esto es importante para el horario base
                }
            });
        }

        // Carga de Francos
        if (data.francos) {
            data.francos.forEach(franco => {
                const fila = $(`tr[data-vendedor-id="${franco.COD_VENDED}"]`);
                // Cambiamos el selector a 'franco-checkbox'
                const checkbox = fila.find(`input.franco-checkbox[value="${franco.FECHA_FRANCO}"]`);

                // Si no está tildado, lo tildamos
                if (!checkbox.prop('checked')) {
                    checkbox.click();
                }
            });
        }

        // Carga de Observaciones de Semana
        if (data.observaciones) {
            $('#observacionesSemana').val(data.observaciones);
        }

        // Carga de Promociones
        if (data.promociones) {
            for (const [fecha, listaPromos] of Object.entries(data.promociones)) {
                const cell = $(`.promo-cell[data-fecha="${fecha}"]`);
                if (listaPromos && listaPromos.length > 0) {
                    const badgesHTML = listaPromos.map(textoPromo => `<span class="promo-badge" title="${textoPromo}">${textoPromo}</span>`).join('');
                    cell.html(`<div class="promo-container">${badgesHTML}</div>`);
                } else {
                    cell.html('<span class="text-muted small">-</span>');
                }
            }
        }

        // Carga de Bloqueos Externos
        if (data.bloqueosExternos) {
            for (const [vendedorId, fechas] of Object.entries(data.bloqueosExternos)) {
                for (const [fecha, horasOcupadas] of Object.entries(fechas)) {
                    const fila = $(`tr[data-vendedor-id="${vendedorId}"]`);
                    for (const [hora, sucursalOrigen] of Object.entries(horasOcupadas)) {
                        const celdaHora = fila.find(`.hora-celda[data-fecha="${fecha}"][data-hora="${hora}"]`);
                        celdaHora
                            .removeClass('marcado hora-extra hora-devuelve dia-franco')
                            .addClass('bloqueo-externo')
                            .attr('title', `Ocupado en Sucursal ${sucursalOrigen}`)
                            .text('Ø');
                    }
                }
            }
        }

        // --- CORRECCIÓN AQUÍ: MOVER ESTE BLOQUE AFUERA ---
        // Cargar Feedback (Mostrar Ícono)
        if (data.feedback) {
            for (const [vendedorId, mensaje] of Object.entries(data.feedback)) {
                if (mensaje && mensaje.trim() !== '') {
                    // 1. Buscamos el SPAN
                    const spanIcono = $(`span.icono-feedback[data-vendedor-id="${vendedorId}"]`);

                    // 2. Buscamos el ícono <i> DENTRO del span
                    const icono = spanIcono.find('i');

                    // 3. Activamos
                    spanIcono.attr('title', `Feedback: "${mensaje}"`); // Actualizar tooltip
                    spanIcono.css('opacity', 1); // Hacerlo 100% visible
                    icono.removeClass('text-secondary').addClass('text-primary'); // Pintarlo de Azul
                }
            }
        }

        // Cargar Notas Diarias Individuales
        if (data.notasVendedor) {
            for (const [vid, fechas] of Object.entries(data.notasVendedor)) {
                for (const [fecha, texto] of Object.entries(fechas)) {
                    if (texto && texto.trim() !== '') {
                        const btn = $(`button.btn-nota[data-vendedor="${vid}"][data-fecha="${fecha}"]`);
                        btn.data('texto', texto);
                        btn.addClass('tiene-nota');
                        btn.html('<i class="bi bi-chat-square-text-fill"></i>');
                    }
                }
            }
        }

        if (data.saldosAnteriores) {
            for (const [vendedorId, saldo] of Object.entries(data.saldosAnteriores)) {
                const fila = $(`tr[data-vendedor-id="${vendedorId}"]`);
                // Guardamos el dato en la fila para usarlo en la suma en vivo
                fila.attr('data-saldo-inicial', saldo);
            }
        }

        actualizarSumatorias();
    }

    // Abrir Modal
    $('#tablaHorariosContainer').on('click', '.btn-nota', function () {
        const btn = $(this);
        const vid = btn.data('vendedor');
        const fecha = btn.data('fecha');
        const nombre = btn.closest('tr').find('td:first .text-truncate > div').text();
        const notaActual = btn.data('texto') || '';

        // Pasamos datos al modal
        $('#notaCodVended').val(vid);
        $('#notaFecha').val(fecha);
        $('#notaNombreVendedor').text(nombre);
        $('#notaFechaTexto').text(fecha);
        $('#textoNota').val(notaActual);

        new bootstrap.Modal(document.getElementById('modalNotas')).show();
    });

    // 2. Botón GUARDAR dentro del Modal
    $('#btnGuardarNota').off('click').on('click', function () {
        const vid = $('#notaCodVended').val();
        const fecha = $('#notaFecha').val();
        const texto = $('#textoNota').val().trim();

        const btnGrilla = $(`button.btn-nota[data-vendedor="${vid}"][data-fecha="${fecha}"]`);

        // Cerramos el modal de edición primero
        bootstrap.Modal.getInstance(document.getElementById('modalNotas')).hide();

        if (texto.length > 0) {
            btnGrilla.data('texto', texto);
            btnGrilla.addClass('tiene-nota');
            btnGrilla.html('<i class="bi bi-chat-square-text-fill"></i>');

            // USAMOS EL NUEVO MODAL
            mostrarModal("Nota asignada correctamente. Recuerde pulsar 'Guardar' para confirmar.", "success");
        } else {
            btnGrilla.removeData('texto');
            btnGrilla.removeClass('tiene-nota');
            btnGrilla.html('<i class="bi bi-chat-square"></i>');
        }
    });

    // 3. Botón BORRAR dentro del Modal
    $('#btnBorrarNota').off('click').on('click', function () {
        const vid = $('#notaCodVended').val();
        const fecha = $('#notaFecha').val();

        const btnGrilla = $(`button.btn-nota[data-vendedor="${vid}"][data-fecha="${fecha}"]`);

        // Limpiamos datos y estilos
        btnGrilla.removeData('texto'); // Borra la memoria
        btnGrilla.removeClass('tiene-nota'); // Quita el color
        btnGrilla.html('<i class="bi bi-chat-square"></i>'); // Vuelve al icono vacío

        // Limpiamos el textarea por si lo vuelve a abrir
        $('#textoNota').val('');

        bootstrap.Modal.getInstance(document.getElementById('modalNotas')).hide();
    });

    let wasChecked = false;
    $('#tablaHorariosContainer').on('mousedown', '.franco-radio', function () {
        wasChecked = $(this).prop('checked');
    });
    $('#tablaHorariosContainer').on('click', '.franco-checkbox', function () {
        const fila = $(this).closest('tr');
        const fecha = $(this).val();

        // Si lo tildo -> Poner franco
        if ($(this).is(':checked')) {
            $(this).parent().addClass('franco-activo');
            fila.find(`.hora-celda[data-fecha="${fecha}"]`)
                .removeClass('marcado hora-extra hora-devuelve hora-permiso hora-caja')
                .text('')
                .addClass('dia-franco');
        }
        // Si lo destildo -> Quitar franco
        else {
            $(this).parent().removeClass('franco-activo');
            fila.find(`.hora-celda[data-fecha="${fecha}"]`).removeClass('dia-franco');
        }

        actualizarSumatorias();
    });

    // --- FUNCIÓN PARA MOSTRAR MENSAJES LINDOS ---
    function mostrarModal(mensaje, tipo = 'success') {
        const modalEl = document.getElementById('modalMensaje');
        const header = $('#modalMensajeHeader');
        const titulo = $('#modalMensajeTitulo');
        const icono = $('#modalMensajeIcono');
        const texto = $('#modalMensajeTexto');

        // Resetear clases
        header.removeClass('bg-success bg-danger bg-warning');
        icono.html('');

        if (tipo === 'success') {
            header.addClass('bg-success');
            titulo.text('¡Excelente!');
            icono.html('<i class="bi bi-check-circle-fill text-success"></i>');
        } else if (tipo === 'error') {
            header.addClass('bg-danger');
            titulo.text('Error');
            icono.html('<i class="bi bi-x-circle-fill text-danger"></i>');
        } else if (tipo === 'warning') {
            header.addClass('bg-warning text-dark');
            titulo.text('Atención');
            icono.html('<i class="bi bi-exclamation-triangle-fill text-warning"></i>');
        }

        texto.html(mensaje); // Usamos .html() para que reconozca <br> y <b>
        new bootstrap.Modal(modalEl).show();
    }

    /**
     * Calcula el turno (TM/TT/FULL) basado en las horas asignadas
     * @param {Array} horasArray - Horas seleccionadas (ej: [7,8,9,10])
     * @returns {String} - 'TM', 'TT', 'FULL', 'MIXTO' o '-'
     */
    function calcularTurno(horasArray) {
        if (!horasArray || horasArray.length === 0) return '-';

        // Convertir a números y ordenar
        const horas = horasArray.map(function (h) { return parseInt(h); }).sort(function (a, b) { return a - b; });

        const primeraHora = horas[0];
        const ultimaHora = horas[horas.length - 1];

        // TM: 7 a 16 hs (inclusive)
        if (primeraHora >= 7 && ultimaHora <= 15) {
            return 'TM';
        }
        // TT: 16 a 23 hs (inclusive) - CAMBIAR: de 22 a 23
        else if (primeraHora >= 13 && ultimaHora <= 23) {
            return 'TT';
        }
        // FULL: 10 a 23 hs (o rango amplio) - CAMBIAR: de 22 a 23
        else if (primeraHora >= 10 && ultimaHora <= 23) {
            return 'FULL';
        }
        // Rango mixto o no clasificable
        else if (primeraHora >= 7 && ultimaHora <= 23) {
            // Si tiene horas tanto en TM como TT, es FULL
            const tieneTM = horas.some(function (h) { return h >= 7 && h <= 15; });
            const tieneTT = horas.some(function (h) { return h >= 16 && h <= 23; }); // CAMBIAR: de 22 a 23
            if (tieneTM && tieneTT) return 'FULL';

            // Si solo tiene parte del rango
            return 'MIXTO';
        }

        return '-';
    }

    function actualizarSumatorias() {
        let granTotalSemanal = 0;
        let totalesDiarios = Array(7).fill(0);

        // Inicializar array para contar horas por columna
        // [dia][hora] -> contador
        // CAMBIAR: De 16 horas (7-22) a 17 horas (7-23)
        let totalesPorHora = Array(7).fill().map(function () {
            return Array(17).fill(0); // 7 días, 17 horas (7-23)
        });

        // Recorremos cada fila de vendedor
        $('tbody tr').each(function () {
            const fila = $(this);
            const vendedorId = fila.data('vendedor-id');

            // 1. Contar tipos de hora visuales - AHORA TAMBIÉN CUENTA "X" (plantilla base)
            const n = fila.find('.hora-celda.marcado').length;
            const c = fila.find('.hora-celda.hora-caja').length;
            const p = fila.find('.hora-celda.hora-permiso').length;
            const e = fila.find('.hora-celda.hora-extra').length;
            const d = fila.find('.hora-celda.hora-devuelve').length;
            const nExterno = fila.find('.hora-celda.bloqueo-externo').length;

            // Contar TODAS las celdas con contenido (incluye "X" del horario base)
            const celdasConHoras = fila.find('.hora-celda.marcado, .hora-celda.hora-caja, .hora-celda.hora-permiso, .hora-celda.hora-extra, .hora-celda.hora-devuelve, .hora-celda.bloqueo-externo');
            const totalHorasTodas = celdasConHoras.length;

            // 2. Calcular por CADA DÍA el turno y contar horas por columna
            fechasSemana.forEach(function (fechaISO, diaIndex) {
                let horasDia = [];

                // Contar TODAS las horas asignadas para este día (incluye horario base con "X")
                const celdasDia = fila.find('.hora-celda.marcado[data-fecha="' + fechaISO + '"], .hora-celda.hora-caja[data-fecha="' + fechaISO + '"], .hora-celda.hora-permiso[data-fecha="' + fechaISO + '"]');

                celdasDia.each(function () {
                    const hora = $(this).data('hora');
                    horasDia.push(hora);

                    // Contar para total por columna (hora)
                    // CAMBIAR: De 7-22 (rango 16) a 7-23 (rango 17)
                    const horaIndex = hora - 7; // Convertir 7-23 a índice 0-16
                    if (horaIndex >= 0 && horaIndex < 17) {
                        totalesPorHora[diaIndex][horaIndex]++;
                    }
                });

                // Calcular turno del día
                const turnoDia = calcularTurno(horasDia);
                const badgeTurno = fila.find('.turno-dia-badge[data-fecha="' + fechaISO + '"]');

                badgeTurno.text(turnoDia);

                // Colores para los turnos por día
                badgeTurno.removeClass('bg-light bg-primary bg-success bg-warning bg-info');

                // Verificar si es franco
                const esFranco = fila.find('.franco-checkbox[value="' + fechaISO + '"]').is(':checked');

                if (esFranco) {
                    badgeTurno.addClass('bg-secondary text-white').attr('title', 'Franco');
                    badgeTurno.text('F');
                } else {
                    switch (turnoDia) {
                        case 'TM':
                            badgeTurno.addClass('bg-primary text-white').attr('title', 'Turno Mañana (7-16hs)');
                            break;
                        case 'TT':
                            badgeTurno.addClass('bg-success text-white').attr('title', 'Turno Tarde (16-22hs)');
                            break;
                        case 'FULL':
                            badgeTurno.addClass('bg-warning text-dark').attr('title', 'Full Day (10-22hs)');
                            break;
                        case 'MIXTO':
                            badgeTurno.addClass('bg-info text-white').attr('title', 'Horario Mixto');
                            break;
                        default:
                            badgeTurno.addClass('bg-light text-dark border').attr('title', 'Sin horario asignado');
                    }
                }

                // Sumar al total diario
                totalesDiarios[diaIndex] += horasDia.length;
            });

            // 3. Resto del cálculo (total H, banco de horas, etc.)
            const totalH_Visual = n + c + p + nExterno;

            fila.find('.total-n').text(totalH_Visual);
            fila.find('.total-e').text(e);
            fila.find('.total-d').text(d);

            // Cálculo banco de horas
            const saldoInicial = parseInt(fila.attr('data-saldo-inicial')) || 0;
            const saldoActual = saldoInicial + p - d;

            const spanSaldo = fila.find('.total-saldo');
            const badgeSaldo = fila.find('.total-saldo-container');

            spanSaldo.text(saldoActual);
            badgeSaldo.removeClass('bg-light text-secondary bg-danger text-white bg-success');

            if (saldoActual > 0) {
                badgeSaldo.addClass('bg-danger text-white').attr('title', 'Debe ' + saldoActual + ' horas');
            } else if (saldoActual < 0) {
                badgeSaldo.addClass('bg-success text-white').attr('title', 'Saldo a favor: ' + Math.abs(saldoActual));
            } else {
                badgeSaldo.addClass('bg-light text-secondary').attr('title', 'Al día');
            }

            // Sumar al total semanal (TODAS las horas)
            granTotalSemanal += (n + c + p + e + d);
        });

        // 4. ACTUALIZAR TOTALES POR COLUMNA DE HORA
        // CAMBIAR: De 0-15 (16 horas) a 0-16 (17 horas)
        for (let diaIndex = 0; diaIndex < 7; diaIndex++) {
            for (let horaIndex = 0; horaIndex < 17; horaIndex++) {
                const hora = horaIndex + 7; // Convertir índice 0-16 a hora 7-23
                const total = totalesPorHora[diaIndex][horaIndex];

                // Buscar la celda correspondiente en el footer
                const celdaTotal = $('.total-hora-columna[data-hora="' + hora + '"][data-dia-index="' + diaIndex + '"]');
                celdaTotal.text(total);

                // Dar color según la cantidad
                celdaTotal.removeClass('text-danger text-warning text-success');
                if (total > 0) {
                    celdaTotal.addClass('text-success');
                } else {
                    celdaTotal.addClass('text-muted');
                }
            }
        }

        // Actualizar badges globales
        $('#totalSemanalLocal').text(granTotalSemanal);
        $('.total-semanal-footer').text(granTotalSemanal);

        if (granTotalSemanal > 0) {
            $('#totalSemanalLocal').removeClass('bg-primary').addClass('bg-success');
        } else {
            $('#totalSemanalLocal').removeClass('bg-success').addClass('bg-primary');
        }

        // Actualizar totales por día en el footer
        $('.total-horas-dia').each(function (index) {
            $(this).text(totalesDiarios[index]);
        });
    }

    $('#tablaHorariosContainer').on('mousedown', '.hora-celda', function (e) {
        e.preventDefault();

        // Agregar bloqueo-externo a la lista de prohibidos
        if ($(this).hasClass('dia-franco') || $(this).hasClass('bloqueo-externo')) return;

        // REGLA DE ORO: Si es día franco, NO HACER NADA.
        // El usuario debe destildar el radio button manualmente si quiere editar.
        if ($(this).hasClass('dia-franco')) return;

        isMouseDown = true;

        // Lógica de pintar/despintar
        modoPintado = ($(this).hasClass('marcado') || $(this).hasClass('hora-extra') || $(this).hasClass('hora-devuelve')) ? 'desmarcar' : 'marcar';
        toggleCelda($(this));
    });

    $('#tablaHorariosContainer').on('mouseover', '.hora-celda', function () {
        // Lo mismo al arrastrar: Si pasamos por encima de un franco, lo ignoramos.
        if ($(this).hasClass('dia-franco')) return;

        if (isMouseDown) {
            toggleCelda($(this));
        }
    });

    $(document).on('mouseup', function () {
        isMouseDown = false;
    });

    function toggleCelda(celda) {
        // 1. Limpiamos TODAS las clases de colores y el texto
        if (modoPintado === 'desmarcar') {
            celda.removeClass('marcado hora-extra hora-devuelve hora-permiso hora-caja').text('');
        } else {
            // Primero limpiamos para evitar mezclas
            celda.removeClass('marcado hora-extra hora-devuelve hora-permiso hora-caja');

            // 2. Aplicamos la clase y letra según el selector
            if (modoPintadoHoras === 'extra') {
                celda.addClass('hora-extra').text('E');
            } else if (modoPintadoHoras === 'devuelve') {
                celda.addClass('hora-devuelve').text('D');
            } else if (modoPintadoHoras === 'permiso') {
                celda.addClass('hora-permiso').text('P'); // Rosa
            } else if (modoPintadoHoras === 'caja') {
                celda.addClass('hora-caja').text('C'); // Celeste
            } else {
                // Normal
                celda.addClass('marcado').text('X');
            }
        }
        actualizarSumatorias();
    }

    function getPrimerDiaDeSemana(year, week) {
        // 1. Creamos una fecha al 4 de enero del año elegido (siempre cae en la Semana 1)
        const d = new Date(year, 0, 4);

        // 2. Obtenemos el día de la semana (0=Domingo, 1=Lunes, ... 6=Sábado)
        // El "|| 7" convierte el 0 (Domingo) en 7 para facilitar la resta
        const day = d.getDay() || 7;

        // 3. Retrocedemos al LUNES de esa semana 1
        d.setDate(d.getDate() - day + 1);

        // 4. Sumamos las semanas correspondientes
        // (week - 1) porque ya estamos en la semana 1
        d.setDate(d.getDate() + ((week - 1) * 7));

        return d;
    }

    $('#guardarHorariosBtn').off('click').on('click', function () {
        const boton = $(this);
        let errores = [];

        // =========================================================
        // 1. VALIDACIONES
        // =========================================================
        $('tbody tr').each(function () {
            // Leemos del atributo data, si falla, intentamos leer del HTML visual
            let nombre = $(this).data('nombre');
            if (!nombre || nombre === "undefined") {
                // Fallback al texto visual
                nombre = $(this).find('td:first .text-truncate > div').first().text().trim();
            }

            // Leemos el total H que ya calculamos visualmente (Suma Local + Externo)
            const totalH_Semanal = parseInt($(this).find('.total-n').text()) || 0;

            // Regla A: Semanal 30, 36, 42, 48
            // Validamos solo si tiene alguna hora asignada (mayor a 0)
            if (totalH_Semanal > 0 && ![30, 36, 42, 48].includes(totalH_Semanal)) {
                errores.push(`<b>${nombre}</b> suma ${totalH_Semanal} hs normales (H). Debe ser 30, 36, 42 o 48.`);
            }

            // Regla B: Diario Max 12 (Sumando todo: Local + Bloqueado Externo)
            let horasPorDia = {};
            $(this).find('.hora-celda').each(function () {
                // Contamos si está marcado acá O si está bloqueado por otra sucursal
                if ($(this).hasClass('marcado') ||
                    $(this).hasClass('hora-extra') ||
                    $(this).hasClass('hora-devuelve') ||
                    $(this).hasClass('bloqueo-externo')) {

                    const f = $(this).data('fecha');
                    horasPorDia[f] = (horasPorDia[f] || 0) + 1;
                }
            });

            for (const [dia, cant] of Object.entries(horasPorDia)) {
                if (cant > 12) errores.push(`<b>${nombre}</b> excede 12hs el día ${dia} (Total acumulado).`);
            }
        });

        // Si hay errores, mostramos modal y CORTAMOS la ejecución
        if (errores.length > 0) {
            let listaHtml = errores.map(e => `<li>${e}</li>`).join('');
            $('#listaErroresValidacion').html(listaHtml);
            new bootstrap.Modal(document.getElementById('modalValidacion')).show();
            return;
        }

        // =========================================================
        // 2. PREPARACIÓN DE DATOS (Lo que faltaba)
        // =========================================================
        boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        const sucursalId = $('#sucursalSelector').val();
        const semana = $('#semanaSelector').val();

        // Calcular Fechas
        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);
        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);
        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        // Arrays para guardar
        let horarios = [], francos = [], notas = [], vendedores = [];

        // A. Recolectar Horarios
        // IMPORTANTE: Agregamos .hora-permiso y .hora-caja al selector para que las encuentre
        $('.hora-celda.marcado, .hora-celda.hora-extra, .hora-celda.hora-devuelve, .hora-celda.hora-permiso, .hora-celda.hora-caja').each(function () {
            const celda = $(this);

            // Ignoramos las celdas bloqueadas (ya están en otra sucursal)
            if (celda.hasClass('bloqueo-externo')) return;

            // DEFINICIÓN DE VARIABLE (Aquí estaba el error, faltaba el 'let')
            let tipo = 'normal';

            if (celda.hasClass('hora-extra')) tipo = 'extra';
            else if (celda.hasClass('hora-devuelve')) tipo = 'devuelve';
            else if (celda.hasClass('hora-permiso')) tipo = 'permiso'; // Nuevo
            else if (celda.hasClass('hora-caja')) tipo = 'caja';       // Nuevo

            // Obtenemos el nombre de la fila
            const nombreVendedor = celda.closest('tr').data('nombre'); // ESTO ES SEGURO


            horarios.push({
                codVended: celda.closest('tr').data('vendedor-id'),
                nombreVendedor: nombreVendedor, // <--- CAMPO NUEVO
                fecha: celda.data('fecha'),
                hora: celda.data('hora'),
                tipo: tipo
            });
        });

        // B. Recolectar Francos y Lista de Vendedores (para limpiar BD)
        $('tbody tr').each(function () {
            const fila = $(this); // Obtener la fila actual
            const vid = fila.data('vendedor-id');
            vendedores.push(vid);

            // --- CAMBIO CLAVE AQUÍ: Iterar sobre CADA checkbox marcado ---
            fila.find('.franco-checkbox:checked').each(function () {
                const checkbox = $(this); // Obtener el checkbox actual
                // Solo guardamos si el checkbox NO está deshabilitado
                if (!checkbox.prop('disabled')) {
                    francos.push({ codVended: vid, fechaFranco: checkbox.val() }); // Usar el valor del checkbox actual
                }
            });
        });

        // C. Recolectar Notas
        $('.btn-nota').each(function () {
            const t = $(this).data('texto');
            // Solo guardamos notas que no estén deshabilitadas
            if (t && t.trim() !== '' && !$(this).prop('disabled')) {
                notas.push({
                    codVended: $(this).data('vendedor'),
                    fecha: $(this).data('fecha'),
                    nota: t
                });
            }
        });

        // =========================================================
        // 3. DEFINICIÓN DEL PAYLOAD (Aquí estaba el error)
        // =========================================================
        const payload = {
            nroSucursal: sucursalId,
            fechaInicio: fechaInicio,
            fechaFin: fechaFin,
            horarios: horarios,
            francos: francos,
            notas: notas,
            vendedoresEnGrilla: vendedores,
            observaciones: $('#observacionesSemana').val()
        };

        // =========================================================
        // 4. ENVÍO AJAX
        // =========================================================
        $.ajax({
            url: apiHorariosURL,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: (res) => {
                if (res.status === 'ok') {
                    // USAMOS EL NUEVO MODAL
                    mostrarModal('¡Los horarios se guardaron correctamente en el sistema!', 'success');

                    $('#cargarHorariosBtn').click(); // Recargamos
                } else {
                    // USAMOS EL NUEVO MODAL (Error)
                    mostrarModal(res.message, 'error');
                }
            },
            error: () => mostrarModal('Error de conexión al intentar guardar.', 'error'),
            complete: () => boton.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar')
        });
    });

    function mostrarAlerta(mensaje, tipo) {
        $('.toast-container').remove();
        const alertaHTML = `<div class="toast-container position-fixed top-0 end-0 p-3"><div class="toast align-items-center text-bg-${tipo} border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">${mensaje}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div></div>`;
        $('body').append(alertaHTML);
        new bootstrap.Toast($('.toast').last()).show();
    }

    $('#agregarRefuerzoBtn').on('click', function () {
        const modal = new bootstrap.Modal(document.getElementById('modalRefuerzos'));
        const listaContainer = $('#listaRefuerzosContainer');

        listaContainer.html('<p>Cargando...</p>');
        modal.show();

        $.getJSON(`${apiDatosURL}?accion=refuerzos`, function (refuerzos) {
            if (refuerzos.length > 0) {
                let listaHTML = '<ul class="list-group">';
                refuerzos.forEach(refuerzo => {
                    if ($(`tbody tr[data-vendedor-id="${refuerzo.COD_VENDED}"]`).length === 0) {
                        listaHTML += `<li class="list-group-item"><input class="form-check-input me-2" type="checkbox" value="${refuerzo.COD_VENDED}" id="refuerzo-${refuerzo.COD_VENDED}" data-nombre="${refuerzo.NOMBRE_VEN}" data-tarea="${refuerzo.TAREA_HABITUAL}"><label class="form-check-label" for="refuerzo-${refuerzo.COD_VENDED}">${refuerzo.NOMBRE_VEN}</label></li>`;
                    }
                });
                listaHTML += '</ul>';
                listaContainer.html(listaHTML);
            } else {
                listaContainer.html('<p>No hay refuerzos disponibles.</p>');
            }
        });
    });

    $('#buscadorRefuerzos').on('keyup', function () {
        const texto = $(this).val().toLowerCase();
        $('#listaRefuerzosContainer .list-group-item').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(texto) > -1);
        });
    });

    $('#btnConfirmarAgregarRefuerzo').on('click', function () {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalRefuerzos'));
        const listaVendedoresAgregar = [];
        const listaIdsParaChequear = [];

        $('#listaRefuerzosContainer input:checked').each(function () {
            const vid = $(this).val();
            listaVendedoresAgregar.push({
                COD_VENDED: vid,
                NOMBRE_VEN: $(this).data('nombre'),
                TAREA_HABITUAL: $(this).data('tarea')
            });
            listaIdsParaChequear.push(vid);
        });

        if (listaVendedoresAgregar.length > 0) {
            // 1. Dibujar filas
            agregarFilasRefuerzos(listaVendedoresAgregar);

            // 2. VALIDACIÓN EN VIVO: Consultar si estos refuerzos ya trabajan en otro lado
            const sucursalId = $('#sucursalSelector').val();
            const semana = $('#semanaSelector').val();
            const [year, weekNum] = semana.split('-W');
            const primerDia = getPrimerDiaDeSemana(year, weekNum);
            let ultimoDia = new Date(primerDia);
            ultimoDia.setDate(primerDia.getDate() + 6);

            const fechaInicio = primerDia.toISOString().split('T')[0];
            const fechaFin = ultimoDia.toISOString().split('T')[0];
            const idsString = listaIdsParaChequear.join(',');

            // Llamada AJAX a la nueva función de PHP
            $.getJSON(`${apiHorariosURL}?accion=verificar_disponibilidad&ids=${idsString}&sucursal=${sucursalId}&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`, function (bloqueos) {

                // Recorremos los bloqueos recibidos y pintamos la grilla
                if (bloqueos) {
                    for (const [vendedorId, fechas] of Object.entries(bloqueos)) {
                        for (const [fecha, horasOcupadas] of Object.entries(fechas)) {

                            const fila = $(`tr[data-vendedor-id="${vendedorId}"]`);

                            // Iteramos sobre las horas ocupadas (Ej: 7, 8, 9, 10...)
                            for (const [hora, sucursalOrigen] of Object.entries(horasOcupadas)) {

                                // Buscamos SOLO la celda de esa hora
                                const celdaHora = fila.find(`.hora-celda[data-fecha="${fecha}"][data-hora="${hora}"]`);

                                // APLICAR BLOQUEO VISUAL
                                celdaHora
                                    .removeClass('marcado hora-extra hora-devuelve dia-franco')
                                    .addClass('bloqueo-externo')
                                    .attr('title', `Ocupado en Sucursal ${sucursalOrigen}`)
                                    .text('Ø');
                            }

                            // NOTA: Ya NO bloqueamos el radio de franco ni el botón de nota
                            // porque puede que trabaje a la mañana en un lado y tenga franco a la tarde (raro, pero posible)
                            // o que queramos ponerle una nota igual.
                        }
                    }
                }
            });
        }

        modal.hide();
    });
});