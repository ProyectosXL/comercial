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
        $.each(data, (key, suc) => {
            $('#sucursalSelector').append(`<option value="${suc.NRO_SUCURSAL}">${suc.DESC_SUCURSAL}</option>`);
        });
    });

    // 3. Cargar Vendedores
    $.getJSON(`${apiDatosURL}?accion=todos_vendedores`, function(data) {
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
    $('#vendedorSelector').on('select2:select', function(e) { 
        $('#sucursalSelector').val(''); 
    });
    
    $('#sucursalSelector').on('change', function() { 
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
    $('#btnVerAgenda').on('click', function() {
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

        $.getJSON(url, function(data) {
            renderTablaVistaLocal(data, primerDia);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Error al cargar datos:', textStatus, errorThrown);
            mostrarModalConTitulo("Error", "No se pudieron cargar los datos. Intente nuevamente.", "error");
        });
    });

    // --- FUNCIÓN DE RENDERIZADO COMPLETAMENTE CORREGIDA ---
    function renderTablaVistaLocal(data, primerDia) {
        const container = $('#tablaVistaLocal');
        container.empty();

        const agenda = data.agenda;
        const nombres = data.nombres;
        const promociones = data.promociones || {};

        if (!agenda || Object.keys(agenda).length === 0) {
            container.html('<div class="alert alert-info text-center m-4">No hay horarios cargados para esta selección.</div>');
            return;
        }

        let fechasSemana = [];
        const diasSemanaNombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        const vendedoresOrdenados = Object.keys(agenda).sort((a, b) => (nombres[a] || a).localeCompare(nombres[b] || b));
        
        // --- 1. HEADER ---
        let html = '<table class="table table-bordered table-sm mb-0">';
        html += '<thead>';
        
        // Fila 1: Días (con separador negro VISIBLE)
        html += '<tr>';
        html += '<th rowspan="2" style="vertical-align: middle;">Vendedor</th>';
        html += '<th rowspan="2" style="vertical-align: middle; min-width: 90px;">Total Hs</th>';
        
        for (let i = 0; i < 7; i++) {
            let fechaActual = new Date(primerDia);
            fechaActual.setDate(primerDia.getDate() + i);
            const fechaISO = fechaActual.toISOString().split('T')[0];
            fechasSemana.push(fechaISO);
            
            const diaNum = String(fechaActual.getDate()).padStart(2, '0');
            const mesNum = String(fechaActual.getMonth() + 1).padStart(2, '0');
            
            // CRÍTICO: Aplicar borde negro grueso al inicio de cada día
            const estiloBorde = i > 0 ? 'border-left: 4px solid #000 !important;' : '';
            
            // 20 Columnas por día: 17h (7-23) + Franco + Turno + Nota
            html += `<th colspan="20" style="${estiloBorde} text-align: center; font-weight: bold; background-color: #e9ecef;">${diasSemanaNombres[i]} ${diaNum}/${mesNum}</th>`;
        }
        html += '</tr>';
        
        // Fila 2: Horas (7-23), F, T, Nota
        html += '<tr>';
        for (let d = 0; d < 7; d++) {
            // CRÍTICO: Borde negro en la primera hora de cada día
            for (let h = 7; h <= 23; h++) {
                const estiloBorde = (h === 7 && d > 0) ? 'border-left: 4px solid #000 !important;' : '';
                html += `<th style="${estiloBorde} min-width: 32px; font-size: 0.85rem;">${h}</th>`;
            }
            html += '<th style="min-width: 32px; font-size: 0.85rem;" title="Franco">F</th>';
            html += '<th style="min-width: 35px; font-size: 0.85rem;" title="Turno">T</th>';
            html += '<th style="min-width: 35px; font-size: 0.85rem;" title="Nota"><i class="bi bi-chat-square-text"></i></th>';
        }
        html += '</tr></thead>';
        
        // --- 2. BODY ---
        html += '<tbody>';
        vendedoresOrdenados.forEach(id => {
            const datos = agenda[id] || {};
            const nombre = nombres[id] || `Legajo ${id}`;
            
            let totales = { H: 0, E: 0, D: 0 };
            
            // Calcular totales
            fechasSemana.forEach(fecha => {
                const esFranco = datos.francos && datos.francos[fecha];
                if (!esFranco && datos.horarios && datos.horarios[fecha]) {
                    for (let h = 7; h <= 23; h++) {
                        const tipo = datos.horarios[fecha][h];
                        if (tipo === 'normal') totales.H++;
                        else if (tipo === 'extra') totales.E++;
                        else if (tipo === 'devuelve') totales.D++;
                    }
                }
            });

            html += '<tr>';
            
            // Columna Vendedor con botón de feedback
            const feedbackTexto = datos.feedback || '';
            const tieneFeedback = feedbackTexto.trim() !== '';
            
            html += `<td style="text-align: left; padding: 8px; min-width: 200px; max-width: 250px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600; font-size: 0.9rem;">${nombre}</span>
                            <button class="btn-feedback ${tieneFeedback ? 'tiene-feedback' : ''}" 
                                    data-id="${id}" data-nombre="${nombre}" data-texto="${feedbackTexto}"
                                    style="border: none; background: transparent; cursor: pointer; font-size: 1.2rem; color: ${tieneFeedback ? '#0d6efd' : '#ced4da'};">
                                <i class="bi bi-chat-dots-fill"></i>
                            </button>
                        </div>
                     </td>`;
            
            // Columna Totales (MÁS ANCHA)
            html += `<td style="text-align: center; vertical-align: middle; padding: 8px; min-width: 90px; font-size: 0.9rem;">
                        <div style="line-height: 1.5;">
                            <div><strong>H:</strong> <span style="color: #198754;">${totales.H}</span></div>
                            <div><strong>E:</strong> <span style="color: #0d6efd;">${totales.E}</span></div>
                            <div><strong>D:</strong> <span style="color: #fd7e14;">${totales.D}</span></div>
                        </div>
                     </td>`;
            
            // Celdas de horarios
            fechasSemana.forEach((fecha, diaIndex) => {
                const esFranco = datos.francos && datos.francos[fecha];
                const nota = datos.notas && datos.notas[fecha];
                
                // Horas (7-23)
                for (let h = 7; h <= 23; h++) {
                    // CRÍTICO: Borde negro en la primera hora de cada día
                    const estiloBorde = (h === 7 && diaIndex > 0) ? 'border-left: 4px solid #000 !important;' : '';
                    let bgColor = '#ffffff';
                    let texto = '';
                    let fontWeight = 'normal';
                    let color = '#000';
                    
                    if (esFranco) {
                        bgColor = '#e2e3e5';
                    } else if (datos.horarios && datos.horarios[fecha] && datos.horarios[fecha][h]) {
                        const tipo = datos.horarios[fecha][h];
                        fontWeight = 'bold';
                        color = 'white';
                        
                        if (tipo === 'extra') { 
                            bgColor = '#0d6efd'; 
                            texto = 'E'; 
                        }
                        else if (tipo === 'devuelve') {
                            bgColor = '#fd7e14';
                            texto = 'D';
                        }
                        else if (tipo === 'permiso') {
                            bgColor = '#d63384';
                            texto = 'P';
                        }
                        else if (tipo === 'caja') {
                            bgColor = '#0dcaf0';
                            texto = 'C';
                            color = '#000';
                        }
                        else { 
                            bgColor = '#198754'; 
                            texto = 'X'; 
                        }
                    }
                    
                    html += `<td style="${estiloBorde} background-color: ${bgColor}; color: ${color}; font-weight: ${fontWeight}; min-width: 32px; text-align: center;">${texto}</td>`;
                }

                // Franco
                const francoStyle = esFranco ? 'background-color: #6f42c1; color: white; font-weight: bold;' : '';
                html += `<td style="${francoStyle} min-width: 32px; text-align: center;">
                            ${esFranco ? '<i class="bi bi-check-lg"></i>' : ''}
                         </td>`;
                
                // Turno
                let turno = '-';
                let turnoColor = 'bg-light text-dark border';
                if (datos.horarios && datos.horarios[fecha]) {
                    const horas = Object.keys(datos.horarios[fecha]).map(Number);
                    if (horas.length > 0) {
                        const min = Math.min(...horas);
                        const max = Math.max(...horas);
                        if (max - min >= 8) { 
                            turno = 'D'; 
                            turnoColor = 'bg-primary text-white';
                        }
                        else if (min >= 14) { 
                            turno = 'T'; 
                            turnoColor = 'bg-warning text-dark';
                        }
                        else { 
                            turno = 'M'; 
                            turnoColor = 'bg-success text-white';
                        }
                    }
                }
                html += `<td style="min-width: 35px; text-align: center; padding: 4px;">
                            <span class="badge ${turnoColor}" style="font-size: 0.8rem; padding: 3px 6px;">${turno}</span>
                         </td>`;

                // Nota
                const tieneNota = nota && nota.trim() !== '';
                const notaColor = tieneNota ? '#fd7e14' : '#ced4da';
                html += `<td style="min-width: 35px; text-align: center;">
                            <button class="btn-nota-local ${tieneNota ? 'tiene-nota' : ''}" 
                                    data-nombre="${nombre}" data-fecha="${fecha}" data-nota="${nota || ''}"
                                    style="border: none; background: transparent; cursor: pointer; font-size: 1.1rem; color: ${notaColor};">
                                <i class="bi bi-chat-square-text-fill"></i>
                            </button>
                         </td>`;
            });
            html += '</tr>';
        });
        html += '</tbody>';
        
        // --- 3. FOOTER ---
        html += '<tfoot><tr>';
        html += '<td colspan="2" style="font-weight: bold; text-align: left; padding: 8px; background-color: #e9ecef;">Promociones</td>';
        
        fechasSemana.forEach((fecha, index) => {
            // CRÍTICO: Borde negro en cada día
            const estiloBorde = index > 0 ? 'border-left: 4px solid #000 !important;' : '';
            
            let promoHTML = '<span style="color: #6c757d;">-</span>';
            if (promociones[fecha] && promociones[fecha].length > 0) {
                promoHTML = `<div style="display: flex; flex-wrap: wrap; gap: 4px;">
                    ${promociones[fecha].map(p => `<span class="badge bg-primary" style="font-size: 0.75rem;">${p}</span>`).join('')}
                </div>`;
            }
            html += `<td colspan="20" style="${estiloBorde} text-align: left; padding: 8px; vertical-align: top; background-color: #e9ecef;">${promoHTML}</td>`;
        });
        html += '</tr></tfoot>';
        
        html += '</table>';
        container.html(html);
    }

    // --- MANEJO DEL MODAL DE FEEDBACK ---
    $(document).on('click', '.btn-feedback', function() {
        const id = $(this).data('id');
        const nom = $(this).data('nombre');
        const txt = $(this).data('texto');

        $('#feedVendedor').val(id);
        $('#feedNombre').text(nom);
        $('#feedTexto').val(txt || '');

        const modal = new bootstrap.Modal(document.getElementById('modalFeedback'));
        modal.show();
    });

    $('#btnGuardarFeed').on('click', function() {
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
            success: function(res) {
                if (res.status === 'ok') {
                    mostrarModalConTitulo("Éxito", "Feedback guardado correctamente.", "success");
                    $('#btnVerAgenda').trigger('click');
                    bootstrap.Modal.getInstance(document.getElementById('modalFeedback')).hide();
                } else {
                    mostrarModalConTitulo("Error", "Error al guardar el feedback.", "error");
                }
            },
            error: function() { 
                mostrarModalConTitulo("Error", "Error de conexión con el servidor.", "error"); 
            }
        });
    });

    // --- MOSTRAR MODAL DE NOTA DIARIA ---
    $(document).on('click', '.btn-nota-local.tiene-nota', function() {
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
