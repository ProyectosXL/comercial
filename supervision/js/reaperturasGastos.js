/**
 * Reapertura manual de períodos de gastos de supervisión.
 *
 * Va aparte de controlarGastos.js para no mezclarse con la lógica de control,
 * pero se apoya en los filtros que esa pantalla ya tiene (#semanaRetail y
 * #supervisoraSelect).
 *
 * La reapertura es de un solo uso: se consume al guardarse la carga, dentro de
 * la misma transacción que el insert. Desde acá solo se habilita y se anula.
 */
$(document).ready(function () {

    const URL_REAPERTURAS = 'Controller/reaperturasGastos.php';

    function llamar(payload) {
        // El controller ya no depende de la sesión PHP, así que el usuario viaja
        // en el payload. Si la pantalla se abrió sin sesión queda vacío y el
        // controller lo resuelve por su cuenta.
        const usuario = $('#usuarioActual').val();
        if (usuario) {
            payload.usuario = usuario;
        }

        return $.ajax({
            url: URL_REAPERTURAS,
            method: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            dataType: 'json'
        });
    }

    function escaparHtml(texto) {
        return $('<div>').text(texto == null ? '' : texto).html();
    }

    // ---------------------------------------------------------------
    // Panel de períodos reabiertos
    // ---------------------------------------------------------------

    function cargarReaperturas() {
        llamar({ accion: 'listar', estado: 'ABIERTA' })
            .done(function (response) {
                if (!response.success) {
                    console.error('Error al listar reaperturas:', response.message);
                    return;
                }
                renderReaperturas(response.reaperturas || []);
            })
            .fail(function (xhr, status, error) {
                console.error('Error AJAX al listar reaperturas:', status, error);
            });
    }

    function renderReaperturas(reaperturas) {
        const $panel = $('#reaperturasPanel');
        const $tbody = $('#reaperturasTable tbody');

        $tbody.empty();

        if (reaperturas.length === 0) {
            $panel.addClass('d-none');
            return;
        }

        reaperturas.forEach(function (r) {
            $tbody.append(
                '<tr>' +
                '<td>' + escaparHtml(r.SEMANA + '/' + r.ANIO) + '</td>' +
                '<td>' + escaparHtml(r.SUPERVISORA) + '</td>' +
                '<td>' + escaparHtml(r.MOTIVO) + '</td>' +
                '<td>' + escaparHtml(r.USUARIO_HABILITA) + '</td>' +
                '<td>' + escaparHtml(r.FECHA_HABILITACION) + '</td>' +
                '<td class="text-end">' +
                '<button class="btn btn-sm btn-outline-danger anularReaperturaBtn" ' +
                'data-id="' + escaparHtml(r.ID) + '" ' +
                'data-periodo="' + escaparHtml(r.SEMANA + '/' + r.ANIO) + '" ' +
                'data-supervisora="' + escaparHtml(r.SUPERVISORA) + '">' +
                '<i class="fas fa-times"></i> Anular</button>' +
                '</td>' +
                '</tr>'
            );
        });

        $panel.removeClass('d-none');
    }

    // ---------------------------------------------------------------
    // Habilitar una reapertura
    // ---------------------------------------------------------------

    $('#reabrirPeriodoBtn').on('click', function () {
        const semanaCompleta = $('#semanaRetail').val();
        const supervisora = $('#supervisoraSelect').val();

        if (!semanaCompleta) {
            Swal.fire('Error', 'Seleccioná una semana antes de reabrir.', 'error');
            return;
        }
        if (!supervisora) {
            Swal.fire(
                'Error',
                'La reapertura es para una supervisora puntual: elegí una en el filtro (no "Todas").',
                'error'
            );
            return;
        }

        const [semana, anio] = semanaCompleta.split('|');

        // Primero consultamos qué hay cargado, para poder advertir con datos
        // concretos en vez de con una confirmación genérica.
        llamar({ accion: 'estado', semana: semana, anio: anio, supervisora: supervisora })
            .done(function (response) {
                if (!response.success) {
                    Swal.fire('Error', response.message || 'No se pudo consultar el período.', 'error');
                    return;
                }

                if (response.reaperturaActiva) {
                    Swal.fire(
                        'Ya está reabierto',
                        'La semana ' + semana + '/' + anio + ' ya tiene una reapertura abierta para ' +
                        supervisora + ', habilitada por ' + response.reaperturaActiva.USUARIO_HABILITA +
                        ' el ' + response.reaperturaActiva.FECHA_HABILITACION + '.',
                        'info'
                    );
                    cargarReaperturas();
                    return;
                }

                pedirMotivoYHabilitar(semana, anio, supervisora, response.estado);
            })
            .fail(function (xhr, status, error) {
                Swal.fire('Error', 'No se pudo consultar el período: ' + error, 'error');
            });
    });

    function pedirMotivoYHabilitar(semana, anio, supervisora, estado) {
        let advertencia = '';

        if (estado.conceptos === 0) {
            advertencia =
                '<div class="alert alert-info text-start mb-3">' +
                'El período no tiene gastos cargados. La reapertura solo habilita la carga.' +
                '</div>';
        } else {
            let detalle = '<li>' + estado.conceptos + ' concepto(s) cargado(s), total $' +
                estado.total.toLocaleString('es-AR', { minimumFractionDigits: 2 }) + '</li>';

            if (estado.cargados > 0) {
                detalle += '<li><strong>' + estado.cargados + ' ya asentado(s) en Tango</strong> ' +
                    '(marcados como cargados por administración)</li>';
            }
            if (estado.con_cae > 0) {
                detalle += '<li><strong>' + estado.con_cae + ' con CAE registrado</strong></li>';
            }

            const critico = estado.cargados > 0 || estado.con_cae > 0;

            advertencia =
                '<div class="alert alert-' + (critico ? 'danger' : 'warning') + ' text-start mb-3">' +
                '<strong>Al guardarse la nueva carga, lo actual se anula y se reemplaza.</strong>' +
                '<ul class="mb-' + (critico ? '2' : '0') + ' mt-2">' + detalle + '</ul>' +
                (critico
                    ? '<small>Esos conceptos ya fueron asentados en el sistema contable. ' +
                      'Si se recargan, el registro de supervisión y el contable quedan distintos ' +
                      'hasta que administración los vuelva a cargar.</small>'
                    : '') +
                '</div>';
        }

        Swal.fire({
            title: 'Reabrir semana ' + semana + '/' + anio,
            html:
                '<p class="mb-2">Supervisora: <strong>' + escaparHtml(supervisora) + '</strong></p>' +
                advertencia +
                '<p class="text-start small mb-1">La reapertura es de un solo uso: se cierra sola ' +
                'cuando la supervisora guarda la carga.</p>',
            input: 'textarea',
            inputLabel: 'Motivo (obligatorio)',
            inputPlaceholder: 'Por qué se reabre este período...',
            inputAttributes: { maxlength: 500 },
            showCancelButton: true,
            confirmButtonText: 'Reabrir período',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            inputValidator: function (value) {
                if (!value || value.trim() === '') {
                    return 'Tenés que indicar el motivo de la reapertura.';
                }
                return null;
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            llamar({
                accion: 'habilitar',
                semana: semana,
                anio: anio,
                supervisora: supervisora,
                motivo: result.value.trim()
            })
                .done(function (response) {
                    if (response.success) {
                        Swal.fire('Período reabierto', response.message, 'success');
                        cargarReaperturas();
                    } else {
                        Swal.fire('Error', response.message || 'No se pudo reabrir el período.', 'error');
                    }
                })
                .fail(function (xhr, status, error) {
                    Swal.fire('Error', 'No se pudo reabrir el período: ' + error, 'error');
                });
        });
    }

    // ---------------------------------------------------------------
    // Anular una reapertura sin usar
    // ---------------------------------------------------------------

    $('#reaperturasTable').on('click', '.anularReaperturaBtn', function () {
        const $btn = $(this);
        const id = $btn.data('id');
        const periodo = $btn.data('periodo');
        const supervisora = $btn.data('supervisora');

        Swal.fire({
            title: '¿Anular la reapertura?',
            html: 'Semana <strong>' + escaparHtml(periodo) + '</strong> de <strong>' +
                  escaparHtml(supervisora) + '</strong>.<br><br>' +
                  'El período vuelve a quedar cerrado y la supervisora deja de poder cargarlo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Anular',
            cancelButtonText: 'Volver',
            confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            llamar({ accion: 'anular', id: id })
                .done(function (response) {
                    if (response.success) {
                        Swal.fire('Anulada', response.message, 'success');
                    } else {
                        Swal.fire('Error', response.message || 'No se pudo anular.', 'error');
                    }
                    cargarReaperturas();
                })
                .fail(function (xhr, status, error) {
                    Swal.fire('Error', 'No se pudo anular la reapertura: ' + error, 'error');
                });
        });
    });

    $('#refrescarReaperturasBtn').on('click', cargarReaperturas);

    cargarReaperturas();
});
