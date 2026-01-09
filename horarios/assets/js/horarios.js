$(document).ready(function() {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiHorariosURL = 'api/horarios_handler.php';
    let isMouseDown = false;
    let modoPintado = 'marcar';
    let fechasSemana = [];
    let modoPintadoHoras = 'normal';

    // Cargar Sucursales al inicio
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function(data) {
        $('#sucursalSelector').append('<option value="">Seleccione una sucursal...</option>');
        $.each(data, (key, sucursal) => $('#sucursalSelector').append(`<option value="${sucursal.NRO_SUCURSAL}">${sucursal.DESC_SUCURSAL}</option>`));
    });

    $('#tipoHoraSelector input').on('change', function() {
        modoPintadoHoras = $(this).val();
    });

    // ==========================================
    // NUEVA LÓGICA DE CARGA SEPARADA POR PASOS
    // ==========================================
    $('#cargarHorariosBtn').on('click', function() {
        console.log("--- INICIANDO CARGA ---");
        const sucursalId = $('#sucursalSelector').val();
        const semana = $('#semanaSelector').val();

        if (!sucursalId || !semana) {
            return alert('Por favor, seleccione una sucursal y una semana.');
        }

        $('#loader').show();
        $('#tablaHorariosContainer').empty();
        $('#observacionesContainer, #guardarHorariosBtn, #agregarRefuerzoBtn').hide();
        $('#observacionesSemana').val('');
        $('#totalSemanalLocal').text('0');

        // PASO 1: Cargar SOLO los vendedores efectivos (base) y dibujar la tabla
        console.log("Paso 1: Buscando vendedores efectivos...");
        $.getJSON(`${apiDatosURL}?accion=vendedores&sucursal=${sucursalId}`)
            .done(function(vendedoresPermanentes) {
                console.log("Paso 1 OK. Vendedores efectivos encontrados:", vendedoresPermanentes.length);
                
                // Generamos la tabla con los efectivos
                generarTablaHorarios(vendedoresPermanentes, semana);

                // PASO 2: Ejecutar la función separada para buscar datos guardados y refuerzos
                verificarYCargarRefuerzos(sucursalId, semana);
            })
            .fail(function() {
                $('#tablaHorariosContainer').html('<p class="alert alert-danger">Error al cargar los vendedores de la sucursal.</p>');
                $('#loader').hide();
            });
    });

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
            .done(function(datosGuardados) {
                console.log("Datos guardados recibidos:", datosGuardados);

                // 1. Obtener IDs que tienen actividad (horarios o francos) en la BD
                // Convertimos todo a String para evitar errores de tipo (741 vs "741")
                const idsConActividad = (datosGuardados.vendedoresConActividad || []).map(id => String(id));

                // 2. Obtener IDs que YA están dibujados en la tabla
                const idsEnTabla = [];
                $('tbody tr').each(function() {
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
                        success: function(infoRefuerzos) {
                            // Agregamos las filas a la tabla existente
                            agregarFilasRefuerzos(infoRefuerzos);
                            
                            // AHORA SI: Pintamos los datos en la grilla completa
                            cargarDatosEnGrilla(datosGuardados);
                            finalizarCarga();
                        },
                        error: function(e) {
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
            .fail(function() {
                console.error("Error al cargar los datos guardados (horarios).");
                alert("Error al cargar los horarios guardados. Verifique la conexión.");
                $('#loader').hide();
            });
    }

function agregarFilasRefuerzos(listaVendedores) {
        if (!listaVendedores || listaVendedores.length === 0) return;

        let filasHTML = '';
        listaVendedores.forEach(vendedor => {
            const vendedorId = vendedor.COD_VENDED;
            // Doble chequeo para no duplicar visualmente
            if ($(`tr[data-vendedor-id="${vendedorId}"]`).length === 0) {
                filasHTML += `<tr data-vendedor-id="${vendedorId}">`;
                
                // Columna Nombre
                filasHTML += `<td class="fw-bold ps-2"><div>${vendedor.NOMBRE_VEN}</div><small class="d-block text-muted fw-normal">${vendedor.TAREA_HABITUAL} <span class="badge bg-warning text-dark">Refuerzo</span></small></td>`;
                
                // Columna Totales (CORREGIDO: N ahora es H)
                filasHTML += `<td class="total-horas-empleado align-middle p-1">`;
                filasHTML +=     `<div class="d-flex flex-column align-items-center" style="font-size: 0.85rem; line-height: 1.1;">`;
                filasHTML +=         `<span class="text-success fw-bold">H: <span class="total-n">0</span></span>`;
                filasHTML +=         `<span class="text-primary fw-bold">E: <span class="total-e">0</span></span>`;
                filasHTML +=         `<span class="text-warning fw-bold">D: <span class="total-d">0</span></span>`;
                filasHTML +=     `</div>`;
                filasHTML += `</td>`;
                
                // Columnas de Días
                fechasSemana.forEach(fechaISO => {
                    // 1. Horas (Primero)
                    for (let h = 7; h <= 22; h++) {
                        filasHTML += `<td class="hora-celda" data-fecha="${fechaISO}" data-hora="${h}"></td>`;
                    }
                    
                    // 2. Franco (Al final, estilo corregido)
                    filasHTML += `<td class="dia-franco text-center p-0"><input type="radio" class="form-check-input franco-radio mt-1" name="franco-${vendedorId}" value="${fechaISO}" style="transform: scale(0.8);"></td>`;
                    
                    // 3. Nota (Faltaba esta columna)
                    filasHTML += `<td class="text-center p-0"><button class="btn-nota" data-vendedor="${vendedorId}" data-fecha="${fechaISO}"><i class="bi bi-chat-square"></i></button></td>`;
                });
                filasHTML += '</tr>';
            }
        });
        
        // Agregamos las nuevas filas al final del tbody
        $('tbody').append(filasHTML);
    }

    function finalizarCarga() {
        $('#guardarHorariosBtn, #agregarRefuerzoBtn, #observacionesContainer').show();
        $('#loader').hide();
        console.log("--- CARGA FINALIZADA ---");
    }

// --- LÓGICA DE DESCARGA EXCEL (MODAL) ---
    
    // 1. Al tocar el botón negro "Excel QP" -> Abrimos el modal
    $('#exportarQuickPassBtn').off('click').on('click', function() {
        const semana = $('#semanaSelector').val();
        
        if (!semana) {
            mostrarModal("Por favor, seleccione una semana para exportar.", "warning");
            return;
        }

        // Abrimos el modal lindo
        new bootstrap.Modal(document.getElementById('modalConfirmarExcel')).show();
    });

    // 2. Al confirmar dentro del modal -> Ejecutamos la descarga
    $('#btnConfirmarDescarga').off('click').on('click', function() {
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
    let tablaHTML = '<table class="table table-bordered table-sm">';
    
    // --- HEADER ---
    let headerHTML = '<thead><tr class="align-middle"><th>Vendedor</th><th>Total Hs</th>';
    const diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    
    // Usamos la función de fecha corregida (Regla 4 de Enero)
    const [year, weekNum] = semana.split('-W');
    const primerDia = getPrimerDiaDeSemana(year, weekNum);
    
    for (let i = 0; i < 7; i++) {
        let fechaActual = new Date(primerDia);
        fechaActual.setDate(primerDia.getDate() + i);
        const fechaISO = fechaActual.toISOString().split('T')[0];
        fechasSemana.push(fechaISO);
        const dia = String(fechaActual.getDate()).padStart(2, '0');
        const mes = String(fechaActual.getMonth() + 1).padStart(2, '0');
        
        // Colspan aumenta a 18 (16 horas + 1 Franco + 1 Obs)
        headerHTML += `<th colspan="18">${diasSemana[i]} ${dia}/${mes}</th>`;
    }
    
    headerHTML += '</tr><tr class="align-middle"><th></th><th></th>';
    for (let d = 0; d < 7; d++) {
        // 1. Horas (7 a 22)
        for (let h = 7; h <= 22; h++) headerHTML += `<th>${h}</th>`;
        
        // 2. Franco (Al final, Punto 6)
        headerHTML += '<th style="width:30px; background-color:#e9ecef;" title="Franco">F</th>';
        
        // 3. Obs (Nueva columna, Punto 3)
        headerHTML += '<th style="width:30px; background-color:#e9ecef;" title="Observación Diaria"><i class="bi bi-chat-square-text"></i></th>';
    }
    headerHTML += '</tr></thead>';

    // --- BODY ---
    let bodyHTML = '<tbody>';
    vendedores.forEach(vendedor => {
        const vendedorId = vendedor.COD_VENDED;
        
        // Identificar si es refuerzo para mostrar badge
        let badgeRefuerzo = '';
        if (vendedor.TAREA_HABITUAL && vendedor.TAREA_HABITUAL.includes('Refuerzo')) {
             badgeRefuerzo = ' <span class="badge bg-warning text-dark" style="font-size:0.7em">Refuerzo</span>';
        }

        bodyHTML += `<tr data-vendedor-id="${vendedorId}">`;
        
        // Columna Nombre
        bodyHTML += `<td class="fw-bold text-start ps-2"><div>${vendedor.NOMBRE_VEN}</div><small class="d-block text-muted fw-normal">${vendedor.TAREA_HABITUAL}${badgeRefuerzo}</small></td>`;
        
        // Columna Totales (Punto 2: N -> H)
        bodyHTML += `<td class="total-horas-empleado align-middle p-1">`;
        bodyHTML +=     `<div class="d-flex flex-column align-items-center" style="font-size: 0.85rem; line-height: 1.1;">`;
        bodyHTML +=         `<span class="text-success fw-bold" title="Horas Normales (H)">H: <span class="total-n">0</span></span>`;
        bodyHTML +=         `<span class="text-primary fw-bold" title="Horas Extras (E)">E: <span class="total-e">0</span></span>`;
        bodyHTML +=         `<span class="text-warning fw-bold" title="Horas Devuelve (D)">D: <span class="total-d">0</span></span>`;
        bodyHTML +=     `</div>`;
        bodyHTML += `</td>`;

        // Columnas Días
        fechasSemana.forEach(fechaISO => {
            // 1. Celdas de Hora
            for (let h = 7; h <= 22; h++) {
                bodyHTML += `<td class="hora-celda" data-fecha="${fechaISO}" data-hora="${h}"></td>`;
            }
            
            // 2. Celda Franco (Al final)
            bodyHTML += `<td class="dia-franco text-center align-middle p-0">
                            <input type="radio" class="form-check-input franco-radio mt-1" name="franco-${vendedorId}" value="${fechaISO}" style="transform: scale(0.8);">
                         </td>`;
            
            // 3. Celda Nota (Nueva)
            bodyHTML += `<td class="text-center align-middle p-0">
                            <button class="btn-nota border-0 bg-transparent text-secondary p-0" data-vendedor="${vendedorId}" data-fecha="${fechaISO}" title="Agregar nota">
                                <i class="bi bi-chat-square"></i>
                            </button>
                         </td>`;
        });
        bodyHTML += '</tr>';
    });
    bodyHTML += '</tbody>';

    // --- FOOTER ---
    // (Punto 1: Eliminamos la fila de Totales por Día)
    let footerHTML = '<tfoot>';
    
    // Fila Promociones
    footerHTML += '<tr><td><strong>Promociones</strong></td><td></td>';
    fechasSemana.forEach(fechaISO => {
        // Colspan 18 para cubrir horas + franco + obs
        footerHTML += `<td colspan="18" class="promo-cell small text-start align-top p-1" data-fecha="${fechaISO}" style="white-space: normal; min-height: 50px;">-</td>`;
    });
    footerHTML += '</tr>';
    
    // (Punto 3: Eliminamos la fila de Obs. Diarias Generales antigua, ya que ahora es por vendedor)
    // Si quieres dejarla como "Obs General de Sucursal", puedes descomentar esto:
    /*
    footerHTML += '<tr><td><strong>Obs. Gral.</strong></td><td></td>';
    fechasSemana.forEach(fechaISO => {
        footerHTML += `<td colspan="18"><textarea class="form-control form-control-sm obs-diaria" data-fecha="${fechaISO}" rows="1" style="font-size:0.8rem;"></textarea></td>`;
    });
    footerHTML += '</tr>';
    */

    footerHTML += '</tfoot>';

    tablaHTML += headerHTML + bodyHTML + footerHTML + '</table>';
    container.html(tablaHTML);
    
    let ultimoDia = new Date(primerDia);
    ultimoDia.setDate(primerDia.getDate() + 6);
    return { inicio: primerDia.toISOString().split('T')[0], fin: ultimoDia.toISOString().split('T')[0] };
}
    
    function cargarDatosEnGrilla(data) {
if (data.horarios) {
    data.horarios.forEach(h => {
        const celda = $(`tr[data-vendedor-id="${h.COD_VENDED}"] td[data-fecha="${h.FECHA}"][data-hora="${h.HORA}"]`);
        
        if (h.TIPO_HORA === 'extra') {
            celda.addClass('hora-extra').text('E');
        } else if (h.TIPO_HORA === 'devuelve') {  // <--- NUEVO BLOQUE
            celda.addClass('hora-devuelve').text('D');
        } else {
            celda.addClass('marcado').text('X');
        }
    });
}
    if (data.francos) {
        data.francos.forEach(franco => {
            const vendedorId = franco.COD_VENDED;
            const fecha = franco.FECHA_FRANCO;
            
            // 1. Buscanos la fila y el radio button
            const fila = $(`tr[data-vendedor-id="${vendedorId}"]`);
            const radio = fila.find(`input.franco-radio[value="${fecha}"]`);
            
            // 2. Marcamos el tilde
            radio.prop('checked', true);
            
            // 3. Pintamos el fondo del tilde (Azulito)
            radio.closest('td').addClass('franco-activo');

            // 4. ¡EL CANDADO! Forzamos el bloqueo de las horas
            // Le ponemos la clase 'dia-franco' (que es la que bloquea el clic)
            // Y borramos cualquier color o texto que haya quedado abajo
            fila.find(`.hora-celda[data-fecha="${fecha}"]`)
                .addClass('dia-franco') 
                .removeClass('marcado hora-extra hora-devuelve')
                .text('');
        });
    }
        if (data.observaciones) {
            $('#observacionesSemana').val(data.observaciones);
        }
        if (data.observacionesDiarias) {
            data.observacionesDiarias.forEach(obs => {
                $(`.obs-diaria[data-fecha="${obs.FECHA}"]`).val(obs.OBSERVACION);
            });
        }
        if (data.promociones) {
            for (const [fecha, listaPromos] of Object.entries(data.promociones)) {
                const cell = $(`.promo-cell[data-fecha="${fecha}"]`);
                
                if (listaPromos && listaPromos.length > 0) {
                    // 1. Convertimos cada texto en una etiqueta HTML
                    const badgesHTML = listaPromos.map(textoPromo => {
                        return `<span class="promo-badge" title="${textoPromo}">${textoPromo}</span>`;
                    }).join('');
                    
                    // 2. Insertamos dentro de un contenedor flexible
                    cell.html(`<div class="promo-container">${badgesHTML}</div>`);
                } else {
                    cell.html('<span class="text-muted small">-</span>');
                }
            }
        }

    // BLOQUEOS POR CRUCE DE SUCURSAL
    if (data.bloqueosExternos) {
        for (const [vendedorId, fechas] of Object.entries(data.bloqueosExternos)) {
            for (const [fecha, horasOcupadas] of Object.entries(fechas)) {
                
                const fila = $(`tr[data-vendedor-id="${vendedorId}"]`);
                
                // Iteramos solo sobre las HORAS ocupadas (Ej: 15, 16...)
                for (const [hora, sucursalOrigen] of Object.entries(horasOcupadas)) {
                    
                    // Buscamos LA celda específica
                    const celdaHora = fila.find(`.hora-celda[data-fecha="${fecha}"][data-hora="${hora}"]`);
                    
                    // La bloqueamos visualmente
                    celdaHora
                        .removeClass('marcado hora-extra hora-devuelve dia-franco') 
                        .addClass('bloqueo-externo') 
                        .attr('title', `Ocupado en Sucursal ${sucursalOrigen}`) 
                        .text('Ø');
                }
                
        if (data.notasVendedor) {
            for (const [vid, fechas] of Object.entries(data.notasVendedor)) {
                for (const [fecha, texto] of Object.entries(fechas)) {
                    if (texto && texto.trim() !== '') {
                        const btn = $(`button.btn-nota[data-vendedor="${vid}"][data-fecha="${fecha}"]`);
                        
                        btn.data('texto', texto); // Guardamos el texto en memoria
                        btn.addClass('tiene-nota'); // Color Naranja
                        btn.html('<i class="bi bi-chat-square-text-fill"></i>'); // Icono Lleno
                    }
                }
            }
        }
            }
        }
    }

    actualizarSumatorias();
    }
    
    // Abrir Modal
    $('#tablaHorariosContainer').on('click', '.btn-nota', function() {
        const btn = $(this);
        const vid = btn.data('vendedor');
        const fecha = btn.data('fecha');
        const nombre = btn.closest('tr').find('td:first div').text();
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
    $('#btnGuardarNota').off('click').on('click', function() {
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
    $('#btnBorrarNota').off('click').on('click', function() {
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
    $('#tablaHorariosContainer').on('mousedown', '.franco-radio', function() {
        wasChecked = $(this).prop('checked');
    });
    $('#tablaHorariosContainer').on('click', '.franco-radio', function(e) {
        const fila = $(this).closest('tr');
        if (wasChecked) {
            $(this).prop('checked', false);
            fila.find('.dia-franco').removeClass('franco-activo');
            fila.find('.hora-celda').removeClass('dia-franco');
        } else {
            fila.find('.dia-franco').removeClass('franco-activo');
            fila.find('.hora-celda').removeClass('dia-franco');
            const fechaFranco = $(this).val();
            $(this).closest('td').addClass('franco-activo');
            fila.find(`.hora-celda[data-fecha="${fechaFranco}"]`).addClass('dia-franco').removeClass('marcado hora-extra').text('');
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

    texto.text(mensaje);
    new bootstrap.Modal(modalEl).show();
}

function actualizarSumatorias() {
    let granTotalSemanal = 0; // Este será solo el costo LOCAL
    let totalesDiarios = Array(7).fill(0);

    $('tbody tr').each(function() {
        const fila = $(this);
        
        // 1. Contamos las horas cargadas en ESTA sucursal
        const nLocal = fila.find('.hora-celda.marcado').length;
        const e = fila.find('.hora-celda.hora-extra').length;
        const d = fila.find('.hora-celda.hora-devuelve').length;

        // 2. Contamos las horas que trae de OTRA sucursal (Bloqueadas)
        const nExterno = fila.find('.hora-celda.bloqueo-externo').length;

        // 3. Calculamos el Total "H" (Contractual)
        // Sumamos lo que trabaja acá + lo que trabaja allá
        const totalH_Visual = nLocal + nExterno;

        // 4. Actualizamos los numeritos de la fila
        fila.find('.total-n').text(totalH_Visual); // H muestra el total real de la persona
        fila.find('.total-e').text(e);
        fila.find('.total-d').text(d);

        // 5. Calculamos el Total Semanal del Local (Badge azul arriba)
        // IMPORTANTE: Aquí SOLO sumamos lo local, para no inflar el costo de esta sucursal
        granTotalSemanal += (nLocal + e + d);
    });

    // Calcular totales verticales (por día) - Solo locales
    $('.hora-celda.marcado, .hora-celda.hora-extra, .hora-celda.hora-devuelve').each(function() {
        const fechaCelda = $(this).data('fecha');
        const diaIndex = fechasSemana.indexOf(fechaCelda);
        if (diaIndex !== -1) {
            totalesDiarios[diaIndex]++;
        }
    });

    // Actualizar badges globales
    $('#totalSemanalLocal').text(granTotalSemanal);
    $('.total-semanal-footer').text(granTotalSemanal);

    if (granTotalSemanal > 0) {
        $('#totalSemanalLocal').removeClass('bg-primary').addClass('bg-success');
    } else {
        $('#totalSemanalLocal').removeClass('bg-success').addClass('bg-primary');
    }

    // Actualizar footer de días
    $('.total-horas-dia').each(function(index) {
        $(this).text(totalesDiarios[index]);
    });
}
    
    $('#tablaHorariosContainer').on('mousedown', '.hora-celda', function(e) {
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

    $('#tablaHorariosContainer').on('mouseover', '.hora-celda', function() {
        // Lo mismo al arrastrar: Si pasamos por encima de un franco, lo ignoramos.
        if ($(this).hasClass('dia-franco')) return;

        if (isMouseDown) {
            toggleCelda($(this));
        }
    });

    $(document).on('mouseup', function() {
        isMouseDown = false;
    });

function toggleCelda(celda) {
    // Primero limpiamos todo
    if (modoPintado === 'desmarcar') {
        celda.removeClass('marcado hora-extra hora-devuelve').text('');
    } else {
        celda.removeClass('marcado hora-extra hora-devuelve'); // Limpiar antes de pintar
        
        // Pintar según el modo seleccionado
        if (modoPintadoHoras === 'extra') {
            celda.addClass('hora-extra').text('E');
        } else if (modoPintadoHoras === 'devuelve') { // <--- NUEVO BLOQUE
            celda.addClass('hora-devuelve').text('D');
        } else {
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
    
    $('#guardarHorariosBtn').off('click').on('click', function() {
        const boton = $(this);
        let errores = [];

        // =========================================================
        // 1. VALIDACIONES
        // =========================================================
        $('tbody tr').each(function() {
            const nombre = $(this).find('td:first div').text();
            
            // Leemos el total H que ya calculamos visualmente (Suma Local + Externo)
            const totalH_Semanal = parseInt($(this).find('.total-n').text()) || 0; 
            
            // Regla A: Semanal 30, 36, 42, 48
            // Validamos solo si tiene alguna hora asignada (mayor a 0)
            if (totalH_Semanal > 0 && ![30, 36, 42, 48].includes(totalH_Semanal)) {
                errores.push(`<b>${nombre}</b> suma ${totalH_Semanal} hs normales (H). Debe ser 30, 36, 42 o 48.`);
            }

            // Regla B: Diario Max 12 (Sumando todo: Local + Bloqueado Externo)
            let horasPorDia = {};
            $(this).find('.hora-celda').each(function() {
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
        $('.hora-celda.marcado, .hora-celda.hora-extra, .hora-celda.hora-devuelve').each(function() {
            const celda = $(this);
            // IMPORTANTE: NO guardamos las celdas "bloqueo-externo", esas ya están en su sucursal original
            if (celda.hasClass('bloqueo-externo')) return;

            let tipo = 'normal';
            if (celda.hasClass('hora-extra')) tipo = 'extra';
            if (celda.hasClass('hora-devuelve')) tipo = 'devuelve';

            horarios.push({
                codVended: celda.closest('tr').data('vendedor-id'),
                fecha: celda.data('fecha'),
                hora: celda.data('hora'),
                tipo: tipo
            });
        });

        // B. Recolectar Francos y Lista de Vendedores (para limpiar BD)
        $('tbody tr').each(function() {
            const vid = $(this).data('vendedor-id');
            vendedores.push(vid); 
            
            const f = $(this).find('.franco-radio:checked');
            if (f.length > 0) {
                // Solo guardamos si el radio NO está deshabilitado (es decir, es de esta sucursal)
                if (!f.prop('disabled')) {
                    francos.push({ codVended: vid, fechaFranco: f.val() });
                }
            }
        });

        // C. Recolectar Notas
        $('.btn-nota').each(function() {
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
            if(res.status === 'ok') {
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
    
    $('#agregarRefuerzoBtn').on('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('modalRefuerzos'));
        const listaContainer = $('#listaRefuerzosContainer');
        
        listaContainer.html('<p>Cargando...</p>');
        modal.show();
    
        $.getJSON(`${apiDatosURL}?accion=refuerzos`, function(refuerzos) {
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
    
    $('#buscadorRefuerzos').on('keyup', function() {
        const texto = $(this).val().toLowerCase();
        $('#listaRefuerzosContainer .list-group-item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(texto) > -1);
        });
    });
    
$('#btnConfirmarAgregarRefuerzo').on('click', function() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalRefuerzos'));
        const listaVendedoresAgregar = [];
        const listaIdsParaChequear = [];
        
        $('#listaRefuerzosContainer input:checked').each(function() {
            const vid = $(this).val();
            listaVendedoresAgregar.push({
                COD_VENDED: vid,
                NOMBRE_VEN: $(this).data('nombre'),
                TAREA_HABITUAL: $(this).data('tarea')
            });
            listaIdsParaChequear.push(vid);
        });
        
        if(listaVendedoresAgregar.length > 0) {
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
            $.getJSON(`${apiHorariosURL}?accion=verificar_disponibilidad&ids=${idsString}&sucursal=${sucursalId}&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`, function(bloqueos) {
                
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