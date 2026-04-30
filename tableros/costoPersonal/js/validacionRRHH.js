/**
 * validacionRRHH.js
 * Gestiona la pestaña "Validación RRHH" del modal administrador.
 */

const ValidacionRRHHModule = (() => {

    const AJAX = CP_CONFIG.ajaxBase + 'validacionPersonalController.php';

    const NOMBRES_MES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                         'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    function _mesLabel(mesStr) {
        const [y, m] = mesStr.split('-');
        return NOMBRES_MES[parseInt(m) - 1] + ' ' + y;
    }

    /* ── Init ─────────────────────────────────────────────────────────── */
    function init() {
        $('#cpAdminTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('href') === '#cpAdminTabValidacion') {
                _loadValidaciones();
            }
        });

        $('#cpBtnCancelarValidar').on('click', _cancelarForm);
        $('#cpBtnConfirmarValidar').on('click', _confirmarValidar);
    }

    /* ── Cargar tabla ─────────────────────────────────────────────────── */
    function _loadValidaciones() {
        _showLoading();

        $.ajax({
            url:      AJAX,
            method:   'POST',
            data:     { accion: 'listarUltimos12' },
            dataType: 'json',
            success(response) {
                if (response.success) {
                    _renderTabla(response.data);
                    _showContent();
                } else {
                    _showError(response.message || 'Error al cargar validaciones.');
                }
            },
            error() {
                _showError('Error de conexión al cargar las validaciones.');
            },
        });
    }

    /* ── Render tabla ─────────────────────────────────────────────────── */
    function _renderTabla(meses) {
        const $tbody = $('#cpValidacionTbody').empty();

        meses.forEach(m => {
            const label       = _mesLabel(m.mes);
            const esValidable = m.es_validable !== false; // default true si el campo no viene

            let estadoBadge;
            if (!esValidable) {
                estadoBadge = '<span class="badge badge-secondary">Sin datos no-DIFERIDO</span>';
            } else if (m.validado) {
                estadoBadge = '<span class="badge badge-success"><i class="bi bi-shield-check"></i> Validado</span>';
            } else {
                estadoBadge = '<span class="badge badge-warning text-dark"><i class="bi bi-shield-exclamation"></i> Pendiente</span>';
            }

            const fechaVal = m.validado && m.fecha_validacion
                ? CostoPersonalGlobal.fmtDateHuman(m.fecha_validacion.substring(0, 10))
                : '—';
            const usuario = m.validado ? (m.usuario_validacion || '—') : '—';
            const obs     = m.validado ? (m.observaciones || '—') : '—';

            let accionBtn;
            if (!esValidable) {
                accionBtn = '<span class="cp-no-validable">Sin datos para validar</span>';
            } else if (m.validado) {
                accionBtn = `<button class="btn btn-sm cp-inval-btn cp-btn-invalidar" data-fecha="${m.mes}"><i class="bi bi-shield-x"></i> Invalidar</button>`;
            } else {
                accionBtn = `<button class="btn btn-sm cp-val-btn cp-btn-validar" data-fecha="${m.mes}"><i class="bi bi-shield-check"></i> Validar</button>`;
            }

            $tbody.append(`<tr>
                <td style="font-weight:600;">${CostoPersonalGlobal.esc(label)}</td>
                <td class="text-center">${estadoBadge}</td>
                <td>${CostoPersonalGlobal.esc(fechaVal)}</td>
                <td>${CostoPersonalGlobal.esc(usuario)}</td>
                <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${CostoPersonalGlobal.esc(obs)}">${CostoPersonalGlobal.esc(obs)}</td>
                <td class="text-center">${accionBtn}</td>
            </tr>`);
        });

        $tbody.find('.cp-btn-validar').on('click', function () {
            _abrirFormValidar($(this).data('fecha'));
        });

        $tbody.find('.cp-btn-invalidar').on('click', function () {
            _confirmarInvalidar($(this).data('fecha'));
        });
    }

    /* ── Formulario validar ───────────────────────────────────────────── */
    function _abrirFormValidar(fecha) {
        $('#cpValidarFecha').val(fecha);
        $('#cpValidarMesLabel').text(_mesLabel(fecha));
        $('#cpValidarObs').val('');
        $('#cpValidacionMsg').hide().text('');
        $('#cpValidarForm').slideDown(150);
        $('#cpValidarObs').focus();
    }

    function _cancelarForm() {
        $('#cpValidarForm').slideUp(150);
        $('#cpValidarFecha').val('');
        $('#cpValidarObs').val('');
    }

    function _confirmarValidar() {
        const fecha = $('#cpValidarFecha').val();
        const obs   = $('#cpValidarObs').val().trim();
        if (!fecha) return;

        const $btn = $('#cpBtnConfirmarValidar');
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Guardando…');

        $.ajax({
            url:      AJAX,
            method:   'POST',
            data:     { accion: 'validar', fechaPeriodo: fecha, observaciones: obs },
            dataType: 'json',
            success(response) {
                if (response.success) {
                    _cancelarForm();
                    _loadValidaciones();
                    Swal.fire({
                        icon:             'success',
                        title:            'Mes validado',
                        text:             `${_mesLabel(fecha)} marcado como validado correctamente.`,
                        timer:            2500,
                        showConfirmButton: false,
                        toast:            true,
                        position:         'top-end',
                    });
                } else {
                    Swal.fire({
                        icon:             'error',
                        title:            'Error al validar',
                        text:             response.message || 'No se pudo registrar la validación.',
                        confirmButtonText: 'Entendido',
                    });
                }
            },
            error() {
                Swal.fire({ icon: 'error', title: 'Error de conexión', confirmButtonText: 'Entendido' });
            },
            complete() {
                $btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Confirmar');
            },
        });
    }

    /* ── Invalidar ────────────────────────────────────────────────────── */
    function _confirmarInvalidar(fecha) {
        const label = _mesLabel(fecha);

        Swal.fire({
            title:             `¿Invalidar ${label}?`,
            text:              'Se eliminará la validación. El mes quedará como pendiente.',
            icon:              'warning',
            showCancelButton:  true,
            confirmButtonText: 'Sí, invalidar',
            cancelButtonText:  'Cancelar',
            confirmButtonColor: '#e74c3c',
        }).then(res => {
            if (!res.isConfirmed) return;

            $.ajax({
                url:      AJAX,
                method:   'POST',
                data:     { accion: 'invalidar', fechaPeriodo: fecha },
                dataType: 'json',
                success(response) {
                    if (response.success) {
                        _loadValidaciones();
                        Swal.fire({
                            icon:             'success',
                            title:            'Validación eliminada',
                            text:             `${label} volvió a estado pendiente.`,
                            timer:            2500,
                            showConfirmButton: false,
                            toast:            true,
                            position:         'top-end',
                        });
                    } else {
                        Swal.fire({
                            icon:             'error',
                            title:            'Error al invalidar',
                            text:             response.message || 'No se pudo quitar la validación.',
                            confirmButtonText: 'Entendido',
                        });
                    }
                },
                error() {
                    Swal.fire({ icon: 'error', title: 'Error de conexión', confirmButtonText: 'Entendido' });
                },
            });
        });
    }

    /* ── Helpers UI ───────────────────────────────────────────────────── */
    function _showMsg(type, text) {
        const $msg = $('#cpValidacionMsg');
        $msg.removeClass('alert-success alert-danger alert-warning')
            .addClass('alert alert-' + type)
            .text(text)
            .show();
        setTimeout(() => $msg.fadeOut(400), 5000);
    }

    function _showLoading() {
        $('#cpValidacionLoading').show();
        $('#cpValidacionContent').hide();
        $('#cpValidacionError').hide().text('');
        $('#cpValidacionMsg').hide().text('');
        _cancelarForm();
    }

    function _showContent() {
        $('#cpValidacionLoading').hide();
        $('#cpValidacionContent').show();
        $('#cpValidacionError').hide();
    }

    function _showError(msg) {
        $('#cpValidacionLoading').hide();
        $('#cpValidacionContent').hide();
        $('#cpValidacionError').text(msg).show();
    }

    $(document).ready(init);

    return {};

})();
