$(document).ready(function () {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiPlantillasURL = 'api/plantillas_handler.php';
    let isMouseDown = false;
    let modoPintado = 'pintar';
    let tipoHoraSeleccionada = 'normal'; // Valor por defecto

    // 1. Cargar Sucursales al inicio
    $.getJSON(`${apiDatosURL}?accion=sucursales`, function (data) {
        $('#base_sucursalSelector').empty().append('<option value="">Seleccione...</option>');
        $.each(data, (key, sucursal) => {
            $('#base_sucursalSelector').append(`<option value="${sucursal.NRO_SUCURSAL}">${sucursal.DESC_SUCURSAL}</option>`);
        });
    });

    // Evento cambio de tipo de hora (Normal vs Extra)
    $('#base_tipoHoraSelector input').on('change', function () {
        tipoHoraSeleccionada = $(this).val();
    });

    // 2. BOTÓN CARGAR (Editar Base)
    $('#base_btnCargar').on('click', function () {
        const sucursal = $('#base_sucursalSelector').val();
        const tipoSemana = $('#base_tipoSemana').val(); // A o B

        if (!sucursal) return alert("Por favor, seleccione una sucursal.");

        // UI Loading
        $('#base_tablaContainer').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando plantilla...</p></div>');
        $('#base_btnGuardar').prop('disabled', true);

        // A. Traer Vendedores de esa sucursal
        $.getJSON(`${apiDatosURL}?accion=vendedores&sucursal=${sucursal}`)
            .done(function (vendedores) {

                // B. Traer la Plantilla Guardada (si existe)
                $.getJSON(`${apiPlantillasURL}?sucursal=${sucursal}&tipo=${tipoSemana}`)
                    .done(function (datosGuardados) {
                        renderGrillaBase(vendedores, datosGuardados);
                        $('#base_btnGuardar').prop('disabled', false); // Habilitar guardar
                    })
                    .fail(function () {
                        $('#base_tablaContainer').html('<div class="alert alert-danger">Error al cargar datos de plantilla.</div>');
                    });

            })
            .fail(function () {
                $('#base_tablaContainer').html('<div class="alert alert-danger">Error al cargar vendedores.</div>');
            });
    });

    // 3. FUNCIÓN DE RENDERIZADO (CORREGIDA HASTA 23HS)
    function renderGrillaBase(vendedores, guardado) {
        const diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        let htmlHeader = '<table class="table table-bordered table-sm mb-0 user-select-none"><thead><tr><th class="text-start ps-3" style="width:250px;">Vendedor</th>';

        // Header Días
        diasSemana.forEach(dia => {
            // CAMBIO 1: Colspan pasa a 18 (17 horas [7 a 23] + 1 Franco)
            htmlHeader += `<th colspan="18" class="dia-header">${dia}</th>`;
        });
        htmlHeader += '</tr><tr><th></th>';

        // Subheader Horas
        for (let d = 0; d < 7; d++) {
            // CAMBIO 2: El bucle va hasta 23
            for (let h = 7; h <= 23; h++) {
                htmlHeader += `<th style="font-size:0.7em; min-width:22px;">${h}</th>`;
            }
            htmlHeader += '<th class="header-dia-fin" style="width:30px; background-color:#e9ecef;">F</th>';
        }
        htmlHeader += '</tr></thead><tbody>';

        let htmlBody = '';

        vendedores.forEach(v => {
            const vid = v.COD_VENDED;
            const data = guardado[vid] || { horarios: {}, francos: {} };

            htmlBody += `<tr data-vendedor-id="${vid}">
                            <td class="fw-bold text-start ps-3 text-nowrap">
                                <div class="text-truncate" style="max-width: 230px;" title="${v.NOMBRE_VEN}">${v.NOMBRE_VEN}</div>
                                <small class="text-muted fw-normal">${v.TAREA_HABITUAL}</small>
                            </td>`;

            // 7 Días
            for (let dia = 1; dia <= 7; dia++) {
                const esFranco = data.francos[dia] ? true : false;

                // Horas 7-23
                // CAMBIO 3: El bucle va hasta 23
                for (let h = 7; h <= 23; h++) {
                    let clase = '';
                    let texto = '';

                    if (esFranco) {
                        clase = 'dia-franco';
                    } else if (data.horarios[dia] && data.horarios[dia][h]) {
                        const tipoGuardado = data.horarios[dia][h];
                        if (tipoGuardado === 'extra') {
                            clase = 'hora-extra';
                            texto = 'E';
                        } else {
                            clase = 'marcado';
                            texto = 'X';
                        }
                    }

                    htmlBody += `<td class="hora-base ${clase}" data-dia="${dia}" data-hora="${h}">${texto}</td>`;
                }

                // Checkbox Franco
                let checked = esFranco ? 'checked' : '';
                let activeClass = esFranco ? 'franco-activo' : '';
                htmlBody += `<td class="text-center p-0 align-middle celda-dia-fin ${activeClass}" style="background-color:#f8f9fa;">
                                <input type="checkbox" class="form-check-input checkbox-base" data-dia="${dia}" ${checked}>
                             </td>`;
            }
            htmlBody += '</tr>';
        });

        htmlBody += '</tbody></table>';
        $('#base_tablaContainer').html(htmlHeader + htmlBody);
    }

    // =========================================================
    // 4. INTERACCIÓN (PINTAR Y FRANCOS)
    // =========================================================

    $('#base_tablaContainer').on('click', '.checkbox-base', function () {
        const fila = $(this).closest('tr');
        const dia = $(this).data('dia');

        if ($(this).is(':checked')) {
            $(this).parent().addClass('franco-activo');
            fila.find(`.hora-base[data-dia="${dia}"]`).removeClass('marcado hora-extra').text('').addClass('dia-franco');
        } else {
            $(this).parent().removeClass('franco-activo');
            fila.find(`.hora-base[data-dia="${dia}"]`).removeClass('dia-franco');
        }
    });

    // B. Pintar Horas (Drag & Drop)
    $('#base_tablaContainer').on('mousedown', '.hora-base', function (e) {
        e.preventDefault();

        if ($(this).hasClass('dia-franco')) return;

        isMouseDown = true;
        // Detectar si vamos a pintar o borrar basándonos en si ya tiene color
        modoPintado = ($(this).hasClass('marcado') || $(this).hasClass('hora-extra')) ? 'borrar' : 'pintar';

        pintarCelda($(this));
    });

    $('#base_tablaContainer').on('mouseover', '.hora-base', function () {
        if (isMouseDown) {
            if ($(this).hasClass('dia-franco')) return;
            pintarCelda($(this));
        }
    });

    $(document).on('mouseup', function () {
        isMouseDown = false;
    });

    function pintarCelda(celda) {
        // Primero limpiamos cualquier clase de color previa
        celda.removeClass('marcado hora-extra').text('');

        if (modoPintado === 'pintar') {
            if (tipoHoraSeleccionada === 'extra') {
                celda.addClass('hora-extra').text('E');
            } else {
                celda.addClass('marcado').text('X');
            }
        }
    }

    // =========================================================
    // 5. GUARDAR PLANTILLA
    // =========================================================
    $('#base_btnGuardar').on('click', function () {
        const boton = $(this);
        const sucursal = $('#base_sucursalSelector').val();
        const tipo = $('#base_tipoSemana').val();

        // Recolectar Datos
        let datosParaGuardar = [];

        $('tbody tr').each(function () {
            const vid = $(this).data('vendedor-id');

            // 1. Recorrer celdas marcadas (Horas)
            $(this).find('.hora-base').each(function () {
                // Chequear si tiene clase marcado (Normal) o hora-extra (Extra)
                if ($(this).hasClass('marcado') || $(this).hasClass('hora-extra')) {

                    let tipoHora = 'normal';
                    if ($(this).hasClass('hora-extra')) tipoHora = 'extra';

                    datosParaGuardar.push({
                        cod: vid,
                        dia: $(this).data('dia'),
                        hora: $(this).data('hora'),
                        tipo: tipoHora,
                        franco: 0
                    });
                }
            });

            // 2. Recorrer francos
            $(this).find('.checkbox-base:checked').each(function () {
                datosParaGuardar.push({
                    cod: vid,
                    dia: $(this).data('dia'),
                    hora: 0, // <--- ESTO DEBE SER 0, NO NULL.
                    tipo: 'normal',
                    franco: 1
                });
            });
        });

        // Enviar
        boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        $.ajax({
            url: apiPlantillasURL,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                sucursal: sucursal,
                tipo: tipo,
                datos: datosParaGuardar
            }),
            success: function (res) {
                if (res.status === 'ok') {
                    alert('Plantilla guardada correctamente.');
                } else {
                    alert('Error al guardar: ' + (res.message || 'Desconocido'));
                }
            },
            error: function () {
                alert('Error de conexión.');
            },
            complete: function () {
                boton.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Base');
            }
        });
    });

});