/**
 * adminPersonal.js
 * Módulo del modal Administrador (gear button).
 * Gestiona parámetros y CRUD de categorías.
 */

$(document).ready(function () {

    const AJAX        = CP_CONFIG.ajaxBase + 'adminPersonalController.php';
    const AJAX_AJUSTE = CP_CONFIG.ajaxBase + 'ajusteInflacionStatusController.php';

    /* ── Helpers UI ──────────────────────────────────────────────────── */

    function showMsg($el, type, text) {
        $el.removeClass('alert-success alert-danger alert-warning')
           .addClass('alert alert-' + type)
           .text(text)
           .show();
    }

    function hideMsg($el) { $el.hide().text(''); }

    /* ═══════════════════════════════════════════════════════════════════
       PESTAÑA: PARÁMETROS
    ═══════════════════════════════════════════════════════════════════ */

    // Ocultar botón "Guardar parámetros" cuando no está en tab Parámetros
    $('#cpAdminTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        $('#cpBtnGuardarParam').toggle(target === '#cpAdminTabParam');
        if (target === '#cpAdminTabCat')        _loadCategorias();
        if (target === '#cpAdminTabAjuste')     _loadEstadoAjuste();
        // ValidacionRRHHModule handles its own tab event in validacionRRHH.js
    });

    // Guardar parámetros
    $('#cpBtnGuardarParam').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Guardando…');
        hideMsg($('#cpAdminParamMsg'));

        $.ajax({
            url:      AJAX,
            method:   'POST',
            data: {
                accion:          'guardarParametros',
                UMBRAL_AZUL:     $('#cpParamUmbralAzul').val(),
                UMBRAL_VERDE:    $('#cpParamUmbralVerde').val(),
                UMBRAL_AMARILLO: $('#cpParamUmbralAmarillo').val(),
                UMBRAL_NARANJA:  $('#cpParamUmbralNaranja').val(),
                OBJETIVO_PCT:    $('#cpParamObjetivo').val(),
            },
            dataType: 'json',
            success(response) {
                if (response.success) {
                    showMsg($('#cpAdminParamMsg'), 'success', 'Parámetros guardados correctamente.');
                    // Actualizar CP_CONFIG en memoria para que el semáforo refleje los nuevos valores
                    if (response.data) {
                        CP_CONFIG.umbralAzul     = response.data.UMBRAL_AZUL;
                        CP_CONFIG.umbralVerde    = response.data.UMBRAL_VERDE;
                        CP_CONFIG.umbralAmarillo = response.data.UMBRAL_AMARILLO;
                        CP_CONFIG.umbralNaranja  = response.data.UMBRAL_NARANJA;
                        CP_CONFIG.objetivoPct    = response.data.OBJETIVO_PCT;
                        // Redibujar el tab activo si tiene datos
                        $(document).trigger('cp:categorias-changed', [{ activas: CostoPersonalGlobal.getActivas() }]);
                    }
                } else {
                    showMsg($('#cpAdminParamMsg'), 'danger', response.message || 'Error al guardar.');
                }
            },
            error(xhr) {
                let msg = 'Error de conexión.';
                try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (_) {}
                showMsg($('#cpAdminParamMsg'), 'danger', msg);
            },
            complete() {
                $btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar parámetros');
            },
        });
    });

    // Limpiar mensajes al abrir el modal
    $('#cpAdminModal').on('show.bs.modal', function () {
        hideMsg($('#cpAdminParamMsg'));
        hideMsg($('#cpCatFormMsg'));
        $('#cpCatForm').hide();
        // Activar tab Parámetros por defecto
        $('#cpAdminTabParam-tab').tab('show');
        $('#cpBtnGuardarParam').show();
    });

    /* ═══════════════════════════════════════════════════════════════════
       PESTAÑA: CATEGORÍAS
    ═══════════════════════════════════════════════════════════════════ */

    let _catDT = null;

    function _loadCategorias() {
        $.ajax({
            url:      AJAX,
            method:   'POST',
            data:     { accion: 'listarCategorias' },
            dataType: 'json',
            success(response) {
                if (response.success) _renderCatTable(response.data);
            },
        });
    }

    const CAT_COLORS = { FIJO: '#3498db', VARIABLE: '#27ae60', DIFERIDO: '#f39c12', CONTINGENTE: '#95a5a6' };

    function _renderCatTable(rows) {
        if ($.fn.dataTable.isDataTable('#cpTablaCategorias')) {
            $('#cpTablaCategorias').DataTable().destroy();
        }
        _catDT = null;

        let html = '';
        rows.forEach(function (c) {
            const inclBadge = c.incluir
                ? '<span class="badge badge-success">Sí</span>'
                : '<span class="badge badge-secondary">No</span>';
            const catColor = CAT_COLORS[c.categoria] || '#7f8c8d';
            const catBadge = `<span style="background:${catColor};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">${_esc(c.categoria)}</span>`;
            html += `<tr
                data-id="${c.id}"
                data-cod="${_esc(c.cod_cuenta)}"
                data-desc="${_esc(c.desc_cuenta)}"
                data-cat="${_esc(c.categoria)}"
                data-concepto="${_esc(c.concepto_agrupado)}"
                data-prorrateo="${c.prorratear_meses}"
                data-incluir="${c.incluir ? 1 : 0}"
                data-obs="${_esc(c.observaciones)}">
                <td><code>${_esc(c.cod_cuenta)}</code></td>
                <td>${_esc(c.desc_cuenta)}</td>
                <td>${catBadge}</td>
                <td>${_esc(c.concepto_agrupado)}</td>
                <td class="text-center">${c.prorratear_meses > 0 ? c.prorratear_meses + 'm' : '—'}</td>
                <td class="text-center">${inclBadge}</td>
                <td class="text-center" style="white-space:nowrap;">
                    <button class="btn btn-xs btn-outline-primary cp-cat-edit mr-1" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                    <button class="btn btn-xs btn-outline-danger cp-cat-del" title="Eliminar"><i class="bi bi-trash-fill"></i></button>
                </td>
            </tr>`;
        });

        if (html) {
            $('#cpCatTbody').html(html);
            _catDT = $('#cpTablaCategorias').DataTable({
                paging:    false,
                searching: true,
                info:      false,
                ordering:  true,
                order:     [[2, 'asc'], [0, 'asc']],
                autoWidth: false,
                language:  { search: 'Buscar cuenta:', emptyTable: 'Sin registros', zeroRecords: 'Sin resultados' },
            });
        } else {
            $('#cpCatTbody').html('<tr><td colspan="7" class="text-center text-muted">Sin registros en la tabla.</td></tr>');
        }
    }

    function _esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Nueva cuenta
    $('#cpBtnNuevaCat').on('click', function () {
        _openCatForm(null);
    });

    // Editar
    $(document).on('click', '.cp-cat-edit', function () {
        const $tr = $(this).closest('tr');
        _openCatForm({
            id:               parseInt($tr.data('id')),
            cod_cuenta:       $tr.data('cod'),
            desc_cuenta:      $tr.data('desc'),
            categoria:        $tr.data('cat'),
            concepto_agrupado:$tr.data('concepto'),
            prorratear_meses: parseInt($tr.data('prorrateo')),
            incluir:          parseInt($tr.data('incluir')),
            observaciones:    $tr.data('obs'),
        });
    });

    // Eliminar
    $(document).on('click', '.cp-cat-del', function () {
        const $tr = $(this).closest('tr');
        const id  = parseInt($tr.data('id'));
        const cod = $tr.data('cod');

        Swal.fire({
            title:             `¿Eliminar "${cod}"?`,
            text:              'Esta acción no se puede deshacer.',
            icon:              'warning',
            showCancelButton:  true,
            confirmButtonText: 'Eliminar',
            cancelButtonText:  'Cancelar',
            confirmButtonColor: '#e74c3c',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            $.ajax({
                url:      AJAX,
                method:   'POST',
                data:     { accion: 'eliminarCategoria', id },
                dataType: 'json',
                success(response) {
                    if (response.success) {
                        _loadCategorias();
                        Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                    }
                },
                error() {
                    Swal.fire({ icon: 'error', title: 'Error de conexión' });
                },
            });
        });
    });

    function _openCatForm(row) {
        const isNew = !row || !row.id;
        $('#cpCatId').val(isNew ? 0 : row.id);
        $('#cpCatCodCuenta').val(isNew ? '' : row.cod_cuenta);
        $('#cpCatDescCuenta').val(isNew ? '' : row.desc_cuenta);
        $('#cpCatCategoria').val(isNew ? '' : row.categoria);
        $('#cpCatConceptoAgrupado').val(isNew ? '' : row.concepto_agrupado);
        $('#cpCatProrratearMeses').val(isNew ? 0 : row.prorratear_meses);
        $('#cpCatIncluir').val(isNew ? '1' : String(row.incluir));
        $('#cpCatObservaciones').val(isNew ? '' : row.observaciones);
        $('#cpCatFormTitle').text(isNew ? 'Nueva cuenta' : 'Editar cuenta — ' + (row.cod_cuenta || ''));
        hideMsg($('#cpCatFormMsg'));
        $('#cpCatForm').slideDown(150);
        $('#cpCatCodCuenta').focus();
    }

    // Cancelar formulario
    $('#cpBtnCancelarCat').on('click', function () {
        $('#cpCatForm').slideUp(150);
        hideMsg($('#cpCatFormMsg'));
    });

    /* ═══════════════════════════════════════════════════════════════════
       PESTAÑA: ESTADO DE AJUSTE POR INFLACIÓN
    ═══════════════════════════════════════════════════════════════════ */

    function _loadEstadoAjuste() {
        $('#cpAjusteStatusContent').hide();
        $('#cpAjusteStatusError').hide();
        $('#cpAjusteStatusLoading').show();

        $.ajax({
            url:      AJAX_AJUSTE,
            method:   'POST',
            data:     { accion: 'obtenerEstado' },
            dataType: 'json',
            success(response) {
                $('#cpAjusteStatusLoading').hide();
                if (response.success && response.data && response.data.disponible) {
                    const d = response.data;
                    $('#cpAjusteTotal').text(d.total.toLocaleString('es-AR'));
                    $('#cpAjusteConAjuste').text(d.con_ajuste.toLocaleString('es-AR'));
                    $('#cpAjusteSinAjuste').text(d.sin_ajuste.toLocaleString('es-AR'));
                    $('#cpAjusteUltimaFecha').text(d.ultima_fecha_ajuste || 'Sin registro');
                    $('#cpAjusteStatusContent').show();
                } else {
                    $('#cpAjusteStatusError').show();
                }
            },
            error() {
                $('#cpAjusteStatusLoading').hide();
                $('#cpAjusteStatusError').show();
            },
        });
    }

    // Guardar
    $('#cpBtnGuardarCat').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true);
        hideMsg($('#cpCatFormMsg'));

        $.ajax({
            url:    AJAX,
            method: 'POST',
            data: {
                accion:             'guardarCategoria',
                id:                 $('#cpCatId').val(),
                cod_cuenta:         $('#cpCatCodCuenta').val(),
                desc_cuenta:        $('#cpCatDescCuenta').val(),
                categoria:          $('#cpCatCategoria').val(),
                concepto_agrupado:  $('#cpCatConceptoAgrupado').val(),
                prorratear_meses:   $('#cpCatProrratearMeses').val(),
                incluir:            $('#cpCatIncluir').val(),
                observaciones:      $('#cpCatObservaciones').val(),
            },
            dataType: 'json',
            success(response) {
                if (response.success) {
                    showMsg($('#cpCatFormMsg'), 'success', 'Categoría guardada.');
                    _loadCategorias();
                    setTimeout(function () { $('#cpCatForm').slideUp(150); }, 900);
                } else {
                    showMsg($('#cpCatFormMsg'), 'danger', response.message || 'Error al guardar.');
                }
            },
            error(xhr) {
                let msg = 'Error de conexión.';
                try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (_) {}
                showMsg($('#cpCatFormMsg'), 'danger', msg);
            },
            complete() { $btn.prop('disabled', false); },
        });
    });

});
