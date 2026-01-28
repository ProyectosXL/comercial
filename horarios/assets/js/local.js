$(document).ready(function() {
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
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function(data) {
        $.each(data, (key, suc) => $('#sucursalSelector').append(`<option value="${suc.NRO_SUCURSAL}">${suc.DESC_SUCURSAL}</option>`));
    });

    // 3. Cargar Vendedores
    $.getJSON(`${apiDatosURL}?accion=todos_vendedores`, function(data) {
        const datosFormateados = data.map(item => ({ id: item.id, text: item.text }));
        $('#vendedorSelector').select2({
            theme: 'bootstrap-5',
            placeholder: "Escriba nombre o legajo...",
            allowClear: true,
            data: datosFormateados
        });
        $('#vendedorSelector').val(null).trigger('change');
    });

    $('#vendedorSelector').on('select2:select', function(e) { $('#sucursalSelector').val(''); });
    $('#sucursalSelector').on('change', function() { if ($(this).val()) $('#vendedorSelector').val(null).trigger('change'); });

    function getPrimerDiaDeSemana(year, week) {
        const d = new Date(year, 0, 4);
        const day = d.getDay() || 7; 
        d.setDate(d.getDate() - day + 1);
        d.setDate(d.getDate() + ((week - 1) * 7));
        return d;
    }

    // 4. VER AGENDA
    $('#btnVerAgenda').on('click', function() {
        const sucursal = $('#sucursalSelector').val();
        const vendedorId = $('#vendedorSelector').val();
        const semana = $('#semanaSelector').val();

        if (!semana) return alert("Seleccione una semana.");
        if (!sucursal && !vendedorId) return alert("Seleccione una Sucursal O busque un Vendedor.");

        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);
        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);
        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        $('#tablaVistaLocal').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando...</p></div>');

        let url = `${apiVistaURL}?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`;
        if (vendedorId) url += `&vendedor=${vendedorId}`;
        else url += `&sucursal=${sucursal}`;

        $.getJSON(url, function(data) {
            renderTablaIdentica(data, primerDia);
        });
    });

// --- FUNCIÓN DE RENDERIZADO (IDÉNTICA A GESTIÓN) ---
    function renderTablaIdentica(data, primerDia) {
        const container = $('#tablaVistaLocal');
        container.empty();

        const agenda = data.agenda;
        const nombres = data.nombres;
        const nombresSucursales = data.nombres_sucursales || {};
        const promociones = data.promociones || {};

        if (!agenda || Object.keys(agenda).length === 0) {
            container.html('<div class="alert alert-info text-center m-4">No hay horarios cargados para esta selección.</div>');
            return;
        }

        let fechasSemana = [];
        const diasSemanaNombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        
        // --- HEADER ---
        let htmlHeader = '<table class="table table-bordered table-sm mb-0">';
        htmlHeader += '<thead class="table-light text-center align-middle"><tr><th class="ps-2 text-start">Vendedor</th><th>Total Hs</th>';
        
        // Determinar si es búsqueda por vendedor para mostrar sucursales por día
        const esBusquedaPorVendedor = $('#vendedorSelector').val();
        
// --- HEADER CON DETALLE DE SUCURSALES ---
        
        for (let i = 0; i < 7; i++) {
            let fechaActual = new Date(primerDia);
            fechaActual.setDate(primerDia.getDate() + i);
            const fechaISO = fechaActual.toISOString().split('T')[0];
            fechasSemana.push(fechaISO);
            
            const diaNum = String(fechaActual.getDate()).padStart(2, '0');
            const mesNum = String(fechaActual.getMonth() + 1).padStart(2, '0');
            
            let infoSucursalesHTML = "";

            // Solo si filtramos por vendedor, mostramos el detalle
            if ($('#vendedorSelector').val()) {
                const idVendedor = Object.keys(agenda)[0];
                const datos = agenda[idVendedor];

                if (datos && datos.sucursal_detalle && datos.sucursal_detalle[fechaISO]) {
                    // Agrupar horas por sucursal
                    // Ej: { 45: [10,11,12], 66: [15,16] }
                    let sucursalesDia = {};
                    
                    for (const [hora, sucID] of Object.entries(datos.sucursal_detalle[fechaISO])) {
                        if (!sucursalesDia[sucID]) sucursalesDia[sucID] = [];
                        sucursalesDia[sucID].push(parseInt(hora));
                    }

                    // Generar etiquetas
                    let badges = [];
                    for (const [sucID, horas] of Object.entries(sucursalesDia)) {
                        const nombreSuc = nombresSucursales[sucID] || `Suc ${sucID}`;
                        
                        // Calcular rango (min - max+1)
                        horas.sort((a,b) => a-b);
                        const min = horas[0];
                        const max = horas[horas.length - 1] + 1;
                        const rango = `${min}-${max}`;

                        badges.push(`<div class="badge bg-dark fw-normal d-block mt-1" style="font-size:0.65rem; white-space:normal;">
                                        ${nombreSuc} (${rango})
                                     </div>`);
                    }
                    infoSucursalesHTML = badges.join('');
                }
            }
            
            // Construir TH
            htmlHeader += `<th colspan="18" style="border-left: 2px solid #dee2e6; vertical-align: top;">
                                <div class="mb-1">${diasSemanaNombres[i]} ${diaNum}/${mesNum}</div>
                                ${infoSucursalesHTML}
                           </th>`;
        }
        
        htmlHeader += '</tr><tr><th></th><th></th>'; // Subheader
        for (let d = 0; d < 7; d++) {
            let borderStyle = (d > 0) ? 'border-left: 2px solid #dee2e6;' : '';
            for (let h = 7; h <= 22; h++) {
                htmlHeader += `<th style="font-size:0.7em; min-width:20px; ${h==7 ? borderStyle : ''}">${h}</th>`;
            }
            htmlHeader += '<th style="width:25px; background:#e9ecef" title="Franco">F</th>';
            htmlHeader += '<th style="width:25px; background:#e9ecef" title="Nota Diaria"><i class="bi bi-chat-square-text"></i></th>';
        }
        htmlHeader += '</tr></thead>';

        // --- BODY ---
        let htmlBody = '<tbody>';

        const vendedoresOrdenados = Object.keys(agenda).sort((a, b) => (nombres[a] || a).localeCompare(nombres[b] || b));

        vendedoresOrdenados.forEach(id => {
            const datos = agenda[id];
            const nombre = nombres[id] || id;

            let totalH = 0, totalE = 0, totalD = 0;
            fechasSemana.forEach(f => {
                if(datos.horarios && datos.horarios[f]) {
                    Object.values(datos.horarios[f]).forEach(tipo => {
                        if(tipo === 'extra') totalE++;
                        else if(tipo === 'devuelve') totalD++;
                        else totalH++;
                    });
                }
            });

            htmlBody += `<tr>`;
            
            // Columna Nombre (CON ICONO FEEDBACK)
            const tieneFeed = (datos.feedback && datos.feedback.trim().length > 0);
            const colorFeed = tieneFeed ? 'text-primary' : 'text-muted opacity-25';
            
            htmlBody += `<td class="ps-2 py-0"> <!-- Quitamos padding vertical del TD -->
                            <div class="vendedor-info-container">
                                <div class="text-truncate" style="max-width: 200px;" title="${nombre}">
                                    <div class="fw-bold">${nombre}</div>
                                    <!-- Si quieres mostrar la tarea, descomenta esto -->
                                    <!-- <small class="text-muted" style="font-size:0.75rem">${datos.tarea_habitual || ''}</small> -->
                                </div>
                                <button class="btn btn-sm p-0 me-2 btn-feedback ${colorFeed}" 
                                        style="border:none; background:transparent;"
                                        data-id="${id}" data-nombre="${nombre}" data-texto="${datos.feedback || ''}">
                                    <i class="bi bi-chat-left-quote-fill fs-5"></i>
                                </button>
                            </div>
                         </td>`;
            
            // Columna Totales
            htmlBody += `<td class="total-horas-empleado align-middle p-1">
                            <div class="d-flex flex-column align-items-center" style="font-size:0.8rem; line-height:1.1;">
                                <span class="text-success fw-bold">H: ${totalH}</span>
                                <span class="text-primary fw-bold">E: ${totalE}</span>
                                <span class="text-warning fw-bold">D: ${totalD}</span>
                            </div>
                         </td>`;

            // Celdas Días
            fechasSemana.forEach((fecha, index) => {
                const esFranco = datos.francos && datos.francos[fecha];
                const notaDiaria = datos.notas && datos.notas[fecha];
                
                let styleBorder = (index > 0) ? 'border-left: 2px solid #dee2e6;' : '';
                
// A. Horas (Con color diferente si es otra sucursal)
                for (let h = 7; h <= 22; h++) {
                    let clase = '', texto = '', extraStyle = (h==7) ? styleBorder : '';
                    let tooltip = '';

                    // Detectar si hay cambio de sucursal
                    let sucursalHoraID = 0;
                    if (datos.sucursal_detalle && datos.sucursal_detalle[fecha] && datos.sucursal_detalle[fecha][h]) {
                        sucursalHoraID = datos.sucursal_detalle[fecha][h];
                    }

                    // Si hay filtro de vendedor y la sucursal de esta hora es distinta a la "base"
                    // Le ponemos un borde rojo fuerte para destacar.
                    if ($('#vendedorSelector').val() && sucursalHoraID > 0 && sucursalHoraID !== datos.sucursal_defecto) {
                        extraStyle += ' border: 2px solid #dc3545 !important;'; 
                        
                        // Buscamos el nombre para el tooltip
                        const nomSuc = nombresSucursales[sucursalHoraID] || `Suc ${sucursalHoraID}`;
                        tooltip = ` title="En: ${nomSuc}"`;
                    }

                    // Asignar clases normales
                    if (esFranco) {
                        clase = 'dia-franco';
                    } else if (datos.horarios && datos.horarios[fecha] && datos.horarios[fecha][h]) {
                        const tipo = datos.horarios[fecha][h];
                        if (tipo === 'extra') { clase = 'hora-extra'; texto = 'E'; }
                        else if (tipo === 'devuelve') { clase = 'hora-devuelve'; texto = 'D'; }
                        else if (tipo === 'permiso') { clase = 'hora-permiso'; texto = 'P'; }
                        else if (tipo === 'caja') { clase = 'hora-caja'; texto = 'C'; }
                        else { clase = 'marcado'; texto = 'X'; }
                    }
                    
                    // Si ya había tooltip de nota, ese gana
                    const titleFinal = (notaDiaria && notaDiaria.trim() !== '') ? `Nota: ${notaDiaria}` : tooltip;

                    htmlBody += `<td class="hora-celda ${clase}" style="pointer-events:none; ${extraStyle}" ${titleFinal}>${texto}</td>`;
                }
                
// B. Franco (Celda Violeta Sólida)
                let claseFranco = esFranco ? 'bg-franco-activo' : '';
                let contenidoFranco = esFranco ? '<i class="bi bi-check-lg fw-bold"></i>' : ''; // Check blanco
                
                htmlBody += `<td class="${claseFranco} p-0">${contenidoFranco}</td>`;
                
// C. Nota Diaria (CORREGIDO PARA ABRIR MODAL)
                const tieneNota = (notaDiaria && notaDiaria.trim() !== '');
                const colorNota = tieneNota ? 'text-warning' : 'text-muted opacity-25';
                const tituloNota = tieneNota ? `Nota: ${notaDiaria}` : 'Sin nota';
                
                htmlBody += `<td class="text-center p-0 align-middle">
                                <span style="cursor: pointer;" class="btn-nota-local" data-nombre="${nombre}" data-fecha="${fecha}" data-nota="${notaDiaria || ''}">
                                    <i class="bi bi-chat-square-text-fill ${colorNota}" title="${tituloNota}"></i>
                                </span>
                             </td>`;
            });
            
            htmlBody += `</tr>`;
        });
        htmlBody += '</tbody>';

        // Footer Promociones
        let htmlFooter = '<tfoot><tr><td colspan="2" class="fw-bold ps-2 bg-light">Promociones</td>';
        fechasSemana.forEach((fecha, index) => {
            let promosHTML = '-';
            let styleBorder = (index > 0) ? 'border-left: 2px solid #dee2e6;' : '';
            if (promociones && Array.isArray(promociones[fecha]) && promociones[fecha].length > 0) {
                promosHTML = `<div class="d-flex flex-wrap gap-1">${promociones[fecha].map(p => `<span class="promo-badge">${p}</span>`).join('')}</div>`;
            }
            htmlFooter += `<td colspan="19" class="align-top p-1 bg-light" style="${styleBorder}; min-height:40px;">${promosHTML}</td>`;

        });
        htmlFooter += '</tr></tfoot></table>';

        container.html(htmlHeader + htmlBody + htmlFooter);
    }

    // --- MANEJO DEL MODAL DE FEEDBACK ---
    $(document).on('click', '.btn-feedback', function() {
        const id = $(this).data('id');
        const nom = $(this).data('nombre');
        const txt = $(this).data('texto');

        $('#feedVendedor').val(id);
        $('#feedNombre').text(nom);
        $('#feedTexto').val(txt);

        new bootstrap.Modal(document.getElementById('modalFeedback')).show();
    });

    $('#btnGuardarFeed').on('click', function() {
        const sucursal = $('#sucursalSelector').val() || 0;
        const vendedor = $('#feedVendedor').val();
        const mensaje = $('#feedTexto').val();
        const semana = $('#semanaSelector').val();
        
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
            success: function(res) {
                if (res.status === 'ok') {
                    alert('Feedback guardado.');
                    // Recargar la tabla para ver el cambio de color
                    $('#btnVerAgenda').click();
                    bootstrap.Modal.getInstance(document.getElementById('modalFeedback')).hide();
                } else {
                    alert('Error al guardar.');
                }
            },
            error: function() { alert('Error de conexión.'); }
        });
    });
    // --- MOSTRAR MODAL DE NOTA DIARIA (SOLO LECTURA) ---
    $(document).on('click', '.btn-nota-local', function() {
        const nombre = $(this).data('nombre');
        const fecha = $(this).data('fecha');
        const nota = $(this).data('nota');

        if (nota && nota.trim() !== '') {
            // Usamos el modal genérico
            mostrarModalConTitulo(`Nota para ${nombre} (${fecha})`, nota, 'warning');
        }
    });

    // --- FUNCIÓN HELPER PARA MODALES ---
    function mostrarModalConTitulo(titulo, mensaje, tipo = 'info') {
        // Asegúrate de tener el #modalMensaje en tu local.php
        const header = $('#modalMensajeHeader');
        const modalTitulo = $('#modalMensajeTitulo');
        const icono = $('#modalMensajeIcono');
        const texto = $('#modalMensajeTexto');

        header.removeClass('bg-success bg-danger bg-warning bg-info');
        icono.html('');

        let colorClase = 'bg-info';
        let iconoClase = 'bi bi-info-circle-fill text-info';
        
        if (tipo === 'success') { colorClase = 'bg-success'; iconoClase = 'bi bi-check-circle-fill text-success'; } 
        else if (tipo === 'error') { colorClase = 'bg-danger'; iconoClase = 'bi bi-x-circle-fill text-danger'; } 
        else if (tipo === 'warning') { colorClase = 'bg-warning text-dark'; iconoClase = 'bi bi-exclamation-triangle-fill text-warning'; }

        header.addClass(colorClase);
        modalTitulo.text(titulo);
        icono.html(`<i class="${iconoClase}" style="font-size:3rem;"></i>`);
        texto.html(mensaje); // .html() por si hay saltos de línea

        new bootstrap.Modal(document.getElementById('modalMensaje')).show();
    }
});