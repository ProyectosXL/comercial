$(document).ready(function() {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiVistaURL = 'api/vista_local_handler.php';

    // 1. INICIALIZAR SELECT2 (Estilo Bootstrap 5)
    $('#vendedorSelector').select2({
        theme: 'bootstrap-5',
        placeholder: "Escriba nombre o legajo...",
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() { return "No se encontraron resultados"; },
            searching: function() { return "Buscando..."; }
        }
    });

    // 2. Cargar Sucursales
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function(data) {
        $.each(data, (key, suc) => $('#sucursalSelector').append(`<option value="${suc.NRO_SUCURSAL}">${suc.DESC_SUCURSAL}</option>`));
    });

    // 3. Cargar TODOS los Vendedores (Para el buscador)
    $.getJSON(`${apiDatosURL}?accion=todos_vendedores`, function(data) {
        // Select2 espera un array de objetos con {id, text}
        // El PHP ya lo devuelve así, pero lo forzamos por si acaso
        $('#vendedorSelector').select2({
            theme: 'bootstrap-5',
            placeholder: "Escriba nombre o legajo...",
            allowClear: true,
            data: data // Le pasamos los datos directo
        });
        
        // Limpiamos selección inicial para que aparezca el placeholder
        $('#vendedorSelector').val(null).trigger('change');
    });

    // 4. Lógica de Exclusión (Uno u Otro)
    $('#vendedorSelector').on('select2:select', function(e) {
        $('#sucursalSelector').val(''); // Si elige persona, borra sucursal
    });

    $('#sucursalSelector').on('change', function() {
        if ($(this).val()) {
            $('#vendedorSelector').val(null).trigger('change'); // Si elige sucursal, borra persona
        }
    });

    // 5. Función Fecha
    function getPrimerDiaDeSemana(year, week) {
        const d = new Date(year, 0, 4);
        const day = d.getDay() || 7; 
        d.setDate(d.getDate() - day + 1);
        d.setDate(d.getDate() + ((week - 1) * 7));
        return d;
    }

    // 6. VER AGENDA
    $('#btnVerAgenda').on('click', function() {
        const sucursal = $('#sucursalSelector').val();
        const vendedorId = $('#vendedorSelector').val(); // Ahora sacamos el ID directo del select
        const semana = $('#semanaSelector').val();

        if (!semana) return alert("Seleccione una semana.");
        if (!sucursal && !vendedorId) return alert("Seleccione una Sucursal O busque un Vendedor.");

        const [year, weekNum] = semana.split('-W');
        const primerDia = getPrimerDiaDeSemana(year, weekNum);
        let ultimoDia = new Date(primerDia);
        ultimoDia.setDate(primerDia.getDate() + 6);
        const fechaInicio = primerDia.toISOString().split('T')[0];
        const fechaFin = ultimoDia.toISOString().split('T')[0];

        $('#agendaContainer').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando agenda...</p></div>');

        // Construir URL
        let url = `${apiVistaURL}?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`;
        if (vendedorId) {
            url += `&vendedor=${vendedorId}`;
        } else {
            url += `&sucursal=${sucursal}`;
        }

        $.getJSON(url, function(dias) {
            renderAgenda(dias);
        });
    });

    function renderAgenda(dias) {
        const container = $('#agendaContainer');
        container.empty();

        if (!dias || dias.length === 0) {
            container.html('<div class="alert alert-info text-center mt-4">No se encontraron horarios cargados para los filtros seleccionados.</div>');
            return;
        }

        let html = '';

        dias.forEach(dia => {
            let promosHTML = '';
            if (dia.promociones.length > 0) {
                promosHTML = `<div class="d-flex gap-2 flex-wrap mb-3">` + 
                    dia.promociones.map(p => `<span class="promo-tag-local shadow-sm"><i class="bi bi-tag-fill me-1"></i>${p}</span>`).join('') +
                    `</div>`;
            }

            let personalHTML = '';
            if (dia.personal.length > 0) {
                dia.personal.forEach(p => {
                    let badges = '';
                    let iconoReloj = '<i class="bi bi-clock me-2 text-secondary"></i>';
                    
                    // Solo mostramos badge si es Franco
                    if (p.es_franco) {
                        badges = '<span class="badge badge-franco ms-auto">Franco</span>';
                        iconoReloj = '<i class="bi bi-house-door-fill me-2 text-secondary"></i>'; 
                    }

                    let notaHTML = '';
                    if (p.nota) {
                        notaHTML = `<div class="staff-note shadow-sm"><i class="bi bi-chat-quote-fill me-2"></i>${p.nota}</div>`;
                    }

                    personalHTML += `
                        <div class="col-md-6 col-lg-4 col-xl-3 col-staff">
                            <div class="staff-card ${p.clase_borde} h-100 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="staff-name text-truncate" title="${p.nombre}">${p.nombre}</div>
                                </div>
                                <div class="staff-time d-flex align-items-center mb-2">
                                    ${iconoReloj} ${p.horario}
                                    ${badges}
                                </div>
                                ${notaHTML}
                            </div>
                        </div>
                    `;
                });
            } else {
                personalHTML = `<div class="col-12"><div class="text-muted small ms-3 fst-italic">Sin personal asignado este día.</div></div>`;
            }

            html += `
                <div class="timeline-day mb-5">
                    <div class="timeline-date border-bottom pb-2 mb-3">
                        <span class="text-primary"><i class="bi bi-calendar-day"></i></span> ${dia.titulo}
                    </div>
                    ${promosHTML}
                    <div class="row g-3">
                        ${personalHTML}
                    </div>
                </div>
            `;
        });

        container.html(html);
    }
});