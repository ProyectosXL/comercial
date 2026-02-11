$(document).ready(function () {
    const apiDatosURL = 'api/obtener_datos.php';
    const apiPlantillasURL = 'api/plantillas_handler.php';
    let isMouseDown = false;
    let modoPintado = 'pintar';
    let tipoHoraSeleccionada = 'normal';
    let vendedoresCargados = []; // Variable global para persistir estado

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
        $('#base_agregarRefuerzoBtn').prop('disabled', true);

        // A. Traer Vendedores de esa sucursal
        $.getJSON(`${apiDatosURL}?accion=vendedores&sucursal=${sucursal}`)
            .done(function (vendedores) {

                // B. Traer la Plantilla Guardada (si existe)
                $.getJSON(`${apiPlantillasURL}?sucursal=${sucursal}&tipo=${tipoSemana}`)
                    .done(function (datosGuardados) {
                        // Detectar refuerzos: vendedores en datosGuardados que NO están en la lista de vendedores
                        const idsVendedores = vendedores.map(v => String(v.COD_VENDED));
                        const idsEnPlantilla = Object.keys(datosGuardados);
                        const idsRefuerzos = idsEnPlantilla.filter(id => !idsVendedores.includes(String(id)));

                        if (idsRefuerzos.length > 0) {
                            // Cargar información de los refuerzos
                            $.ajax({
                                url: `${apiDatosURL}?accion=vendedoresPorLista`,
                                type: 'POST',
                                data: { listaVendedores: JSON.stringify(idsRefuerzos) },
                                dataType: 'json',
                                success: function (infoRefuerzos) {
                                    // Marcar como refuerzos y agregar a la lista
                                    infoRefuerzos.forEach(ref => {
                                        ref.ES_REFUERZO = true;
                                        vendedores.push(ref);
                                    });
                                    vendedoresCargados = vendedores; // Actualizar global
                                    renderGrillaBase(vendedores, datosGuardados);
                                    $('#base_btnGuardar').prop('disabled', false);
                                    $('#base_agregarRefuerzoBtn').prop('disabled', false);
                                },
                                error: function () {
                                    // Si falla, renderizar sin los refuerzos
                                    renderGrillaBase(vendedores, datosGuardados);
                                    $('#base_btnGuardar').prop('disabled', false);
                                    $('#base_agregarRefuerzoBtn').prop('disabled', false);
                                }
                            });
                        } else {
                            // No hay refuerzos, renderizar normalmente
                            renderGrillaBase(vendedores, datosGuardados);
                            $('#base_btnGuardar').prop('disabled', false);
                            $('#base_agregarRefuerzoBtn').prop('disabled', false);
                        }
                    })
                    .fail(function () {
                        $('#base_tablaContainer').html('<div class="alert alert-danger">Error al cargar datos de plantilla.</div>');
                    });

            })
            .fail(function () {
                $('#base_tablaContainer').html('<div class="alert alert-danger">Error al cargar vendedores.</div>');
            });
    });

    // 3. FUNCIÓN DE RENDERIZADO (Con Total Hs y hasta 23hs)
    function renderGrillaBase(vendedores, guardado) {
        vendedoresCargados = vendedores; // Actualizamos la global
        const diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        // Header: Agregamos columna Total Hs
        let htmlHeader = '<table class="table table-bordered table-sm mb-0 user-select-none"><thead><tr><th class="text-start ps-3" style="width:200px;">Vendedor</th><th class="text-center" style="width:60px;">Total Hs</th>';

        // Header Días
        diasSemana.forEach(dia => {
            // CAMBIO 1: Colspan pasa a 18 (17 horas [7 a 23] + 1 Franco)
            htmlHeader += `<th colspan="18" class="dia-header">${dia}</th>`;
        });
        htmlHeader += '</tr><tr><th></th><th></th>'; // Vendedor + Total Hs

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
            const esRefuerzo = v.ES_REFUERZO || false; // Marca si es refuerzo

            htmlBody += `<tr data-vendedor-id="${vid}" data-es-refuerzo="${esRefuerzo}">
                            <td class="fw-bold text-start ps-3 text-nowrap">
                                <div class="text-truncate" style="max-width: 180px;" title="${v.NOMBRE_VEN}">${v.NOMBRE_VEN}</div>
                                <small class="text-muted fw-normal">${v.TAREA_HABITUAL}</small>
                                ${esRefuerzo ? '<span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">REFUERZO</span>' : ''}
                            </td>
                            <td class="align-middle text-center fw-bold text-primary total-hs-base">0</td>`;

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
                        const tipoGuardado = data.horarios[dia][h]; // <--- DEFINICION RESTAURADA

                        if (tipoGuardado.includes('extra')) {
                            clase = 'hora-extra';
                            texto = 'E';
                        } else {
                            clase = 'marcado';
                            texto = 'X';
                        }

                        // Chequear media hora
                        if (tipoGuardado.includes('_media')) {
                            clase += ' media-hora';
                            texto += ' <small>½</small>';
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
        calcularTotalesBase();
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

    // CLICK DERECHO (Media Hora)
    $('#base_tablaContainer').on('contextmenu', '.hora-base', function (e) {
        e.preventDefault();

        if ($(this).hasClass('dia-franco')) return;

        // Si está vacía, pintar primero
        if (!$(this).hasClass('marcado') && !$(this).hasClass('hora-extra')) {
            modoPintado = 'marcar'; // Forzamos pintar
            pintarCelda($(this));
        }

        if ($(this).hasClass('media-hora')) {
            $(this).removeClass('media-hora');
            // Restaurar texto base
            let txt = $(this).hasClass('hora-extra') ? 'E' : 'X';
            $(this).html(txt);
        } else {
            $(this).addClass('media-hora');
            let txt = $(this).hasClass('hora-extra') ? 'E' : 'X';
            $(this).html(`${txt} <small>½</small>`);
        }
        calcularTotalesBase();
    });

    $('#base_tablaContainer').on('mousedown', '.hora-base', function (e) {
        e.preventDefault();
        if ($(this).hasClass('dia-franco')) return;
        if (e.which === 3) return;

        isMouseDown = true;
        // Detectar si vamos a pintar o borrar
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
        if (modoPintado === 'borrar') {
            celda.removeClass('marcado hora-extra media-hora').text('');
        } else {
            celda.removeClass('marcado hora-extra');
            // Mantener media hora si ya la tenia
            const esMedia = celda.hasClass('media-hora');

            if (tipoHoraSeleccionada === 'extra') {
                celda.addClass('hora-extra').text('E');
            } else {
                celda.addClass('marcado').text('X');
            }

            if (esMedia) celda.append(' <small>½</small>');
        }
        calcularTotalesBase();
    }

    function calcularTotalesBase() {
        $('#base_tablaContainer tbody tr').each(function () {
            let total = 0;
            $(this).find('.hora-base').each(function () {
                if ($(this).hasClass('marcado') || $(this).hasClass('hora-extra')) {
                    const val = $(this).hasClass('media-hora') ? 0.5 : 1;
                    total += val;
                }
            });
            $(this).find('.total-hs-base').text(total % 1 === 0 ? total : total.toFixed(1));
        });
    }

    // =========================================================
    // 5. REFUERZOS EN BASE
    // =========================================================
    $('#base_agregarRefuerzoBtn').on('click', function () {
        // Configuramos el modal para trabajar en modo BASE
        $('#btnConfirmarAgregarRefuerzo').addClass('d-none');
        $('#btnConfirmarAgregarRefuerzoBase').removeClass('d-none');

        const modal = new bootstrap.Modal(document.getElementById('modalRefuerzos'));
        const listaContainer = $('#listaRefuerzosContainer');
        listaContainer.html('<div class="text-center"><div class="spinner-border text-primary"></div></div>');
        modal.show();

        // Reusamos lógica de obtener refuerzos
        $.getJSON(`${apiDatosURL}?accion=refuerzos`, function (refuerzos) {
            listaContainer.empty();
            if (refuerzos.length > 0) {
                let listaHTML = '<ul class="list-group">';
                refuerzos.forEach(refuerzo => {
                    // Chequear si ya está en la grilla visual actual
                    if ($(`tr[data-vendedor-id="${refuerzo.COD_VENDED}"]`).length === 0) {
                        listaHTML += `<li class="list-group-item">
                            <input class="form-check-input me-2" type="checkbox" value="${refuerzo.COD_VENDED}" 
                                id="base-ref-${refuerzo.COD_VENDED}" 
                                data-nombre="${refuerzo.NOMBRE_VEN}" 
                                data-tarea="${refuerzo.TAREA_HABITUAL}">
                            <label class="form-check-label" for="base-ref-${refuerzo.COD_VENDED}">${refuerzo.NOMBRE_VEN}</label>
                         </li>`;
                    }
                });
                listaHTML += '</ul>';

                if (listaHTML === '<ul class="list-group"></ul>') {
                    listaContainer.html('<p class="text-muted text-center">Todos los refuerzos disponibles ya están en la grilla.</p>');
                } else {
                    listaContainer.html(listaHTML);
                }
            } else {
                listaContainer.html('<p>No hay refuerzos disponibles.</p>');
            }
        });
    });

    $('#btnConfirmarAgregarRefuerzoBase').on('click', function () {
        const seleccionados = $('#listaRefuerzosContainer input:checked');
        if (seleccionados.length > 0) {
            seleccionados.each(function () {
                vendedoresCargados.push({
                    COD_VENDED: $(this).val(),
                    NOMBRE_VEN: $(this).data('nombre'),
                    TAREA_HABITUAL: $(this).data('tarea'),
                    ES_REFUERZO: true // Marcar como refuerzo
                });
            });
            // No tenemos "datos guardados" para estos nuevos, así que pasamos un objeto con sus IDs vacíos
            // Pero renderGrillaBase espera "guardado" como mapa {id: data}.
            // Debemos preservar lo que ya está pintado en la grilla actual antes de re-renderizar?
            // AJAX call to save current state? No, it's better to just append rows or re-render everything preserving state.
            // Dado que `renderGrillaBase` destruye el HTML, perderíamos lo pintado no guardado.

            // SOLUCION MEJORADA: Solo anexar la fila HTML nueva.
            // Pero `renderGrillaBase` es compleja. 
            // Vamos a guardar el estado actual de la grilla en memoria antes de re-renderizar.

            let estadoActual = {};
            // Recorremos la grilla actual para salvar lo que el usuario pintó
            $('#base_tablaContainer tbody tr').each(function () {
                const vid = $(this).data('vendedor-id');
                estadoActual[vid] = { horarios: {}, francos: {} };

                $(this).find('.hora-base').each(function () {
                    if ($(this).hasClass('marcado') || $(this).hasClass('hora-extra')) {
                        const d = $(this).data('dia');
                        const h = $(this).data('hora');
                        let tipo = $(this).hasClass('hora-extra') ? 'extra' : 'normal';
                        if ($(this).hasClass('media-hora')) tipo += '_media';

                        if (!estadoActual[vid].horarios[d]) estadoActual[vid].horarios[d] = {};
                        estadoActual[vid].horarios[d][h] = tipo;
                    }
                });

                $(this).find('.checkbox-base:checked').each(function () {
                    const d = $(this).data('dia');
                    estadoActual[vid].francos[d] = true;
                });
            });

            renderGrillaBase(vendedoresCargados, estadoActual);
        }

        bootstrap.Modal.getInstance(document.getElementById('modalRefuerzos')).hide();
        // Resetear botones
        $('#btnConfirmarAgregarRefuerzo').removeClass('d-none');
        $('#btnConfirmarAgregarRefuerzoBase').addClass('d-none');
    });

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

                    // Si es media hora, agregamos la info
                    if ($(this).hasClass('media-hora')) {
                        datosParaGuardar[datosParaGuardar.length - 1].tipo += '_media';
                    }
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