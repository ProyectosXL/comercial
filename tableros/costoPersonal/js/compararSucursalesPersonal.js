/**
 * compararSucursalesPersonal.js
 * Tab 5 – Comparar Sucursales de Costo de Personal.
 * Depende de: jQuery, CostoPersonalGlobal, SweetAlert2.
 */

const CompararSucursalesPersonalModule = (() => {

    let _data        = null;
    let _dtable      = null;
    let _initialized = false;

    function init() {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('href') === '#comparar') {
                if (!_initialized) {
                    _initialized = true;
                    _bindEvents();
                }
                if (_dtable) _dtable.columns.adjust().draw(false);
            }
        });

        $(document).on('cp:categorias-changed', function () {
            if (_data) _recalcularConCategorias();
        });

        $(document).on('cp:ajuste-inflacion-changed', function () {
            if (_data) {
                const desde = _data.fecha_desde;
                const hasta = _data.fecha_hasta;
                const suc1  = _data.suc1 && _data.suc1.id;
                const suc2  = _data.suc2 && _data.suc2.id;
                if (suc1 && suc2) _fetchComparacion(suc1, suc2, desde, hasta);
            }
        });
    }

    function _bindEvents() {
        $('#cpCmpBtnComparar').on('click', _comparar);
        $('#cpCmpBtnLimpiar').on('click', _limpiar);
    }

    function _comparar() {
        const suc1  = $('#cpCmpSuc1').val();
        const suc2  = $('#cpCmpSuc2').val();
        const desde = $('#cpCmpDesde').val();
        const hasta = $('#cpCmpHasta').val();

        if (!suc1 || !suc2) {
            Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Seleccioná ambas sucursales.', confirmButtonText: 'Entendido' });
            return;
        }
        if (suc1 === suc2) {
            Swal.fire({ icon: 'warning', title: 'Sucursales iguales', text: 'Las dos sucursales deben ser distintas.', confirmButtonText: 'Entendido' });
            return;
        }
        if (desde && hasta && new Date(desde) > new Date(hasta)) {
            Swal.fire({ icon: 'error', title: 'Fechas inválidas', text: 'La fecha "Desde" debe ser anterior a "Hasta".', confirmButtonText: 'Entendido' });
            return;
        }

        _fetchComparacion(suc1, suc2, desde, hasta);
    }

    function _fetchComparacion(suc1, suc2, desde, hasta) {
        Swal.fire({
            title: 'Comparando sucursales…',
            allowOutsideClick: false,
            allowEscapeKey:    false,
            showConfirmButton:  false,
            didOpen: () => Swal.showLoading(),
        });

        $.ajax({
            url:      CP_CONFIG.ajaxBase + 'compararSucursalesPersonalController.php',
            method:   'POST',
            cache:    false,
            data: {
                idSucursal1:        suc1,
                idSucursal2:        suc2,
                fechaDesde:         desde,
                fechaHasta:         hasta,
                conAjusteInflacion: CostoPersonalGlobal.getAjusteInflacionActivo(),
            },
            dataType: 'json',
            success(response) {
                Swal.close();
                if (response.success) {
                    _data = response.data;
                    _render();
                    CostoPersonalGlobal.actualizarPanelAvisos(response.data);
                } else {
                    _showError(response.message || 'No se pudo realizar la comparación.');
                }
            },
            error(xhr) {
                Swal.close();
                let msg = 'Error de conexión.';
                try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (_) {}
                _showError(msg);
            },
        });
    }

    function _render() {
        const data = _data;
        const nomSuc1 = $('#cpCmpSuc1 option:selected').text();
        const nomSuc2 = $('#cpCmpSuc2 option:selected').text();

        // KPI
        _renderKPI(data, nomSuc1, nomSuc2);

        // Tabla
        _renderTabla(data, nomSuc1, nomSuc2);

        $('#cpCmpEmptyState').hide();
        $('#cpCmpKpi').fadeIn(200);
        $('#cpCmpTableSection').fadeIn(200);
        $('#cpCmpBtnPDF').show();
    }

    function _renderKPI(data, nomSuc1, nomSuc2) {
        // Recalcular pct con categorías activas
        const pct1 = _calcPct(data.suc1);
        const pct2 = _calcPct(data.suc2);
        const diff = (pct1 !== null && pct2 !== null) ? pct2 - pct1 : null;

        $('#cpCmpNomSuc1').text(nomSuc1);
        $('#cpCmpNomSuc2').text(nomSuc2);
        $('#cpCmpValSuc1').text(pct1 !== null ? pct1.toFixed(2) + '%' : '—');
        $('#cpCmpValSuc2').text(pct2 !== null ? pct2.toFixed(2) + '%' : '—');

        if (diff !== null) {
            const absDiff = Math.abs(diff).toFixed(2);
            $('#cpCmpDifVal').text(absDiff + ' pp');
            $('#cpCmpDifCard').removeClass('mejor peor igual');
            if (diff > 0.5) {
                $('#cpCmpDifCard').addClass('peor').css({ background: 'rgba(231,76,60,0.1)', color: '#e74c3c' });
                $('#cpCmpDifTexto').text('Suc. 2 mayor costo');
            } else if (diff < -0.5) {
                $('#cpCmpDifCard').addClass('mejor').css({ background: 'rgba(39,174,96,0.1)', color: '#27ae60' });
                $('#cpCmpDifTexto').text('Suc. 2 menor costo');
            } else {
                $('#cpCmpDifCard').addClass('igual').css({ background: 'rgba(52,152,219,0.1)', color: '#3498db' });
                $('#cpCmpDifTexto').text('Similar');
            }
        } else {
            $('#cpCmpDifVal').text('—');
            $('#cpCmpDifTexto').text('Sin datos');
            $('#cpCmpDifCard').css({ background: '#ecf0f1', color: '#95a5a6' });
        }
    }

    function _calcPct(sucData) {
        if (!sucData) return null;
        const costo = CostoPersonalGlobal.calcularCostoTotal(sucData.costos_por_categoria || {});
        const venta = sucData.venta_neta || 0;
        return venta > 0 ? (costo / venta) * 100 : null;
    }

    function _renderTabla(data, nomSuc1, nomSuc2) {
        if (_dtable) {
            _dtable.destroy();
            $('#cpTablaComparacion').empty();
            _dtable = null;
        }

        const activas   = CostoPersonalGlobal.getActivas();
        const conceptos = data.conceptos || [];

        // Recalcular % costo para ambas sucursales con categorías activas
        const pct1Recalc = _calcPct(data.suc1);
        const pct2Recalc = _calcPct(data.suc2);

        let thead = `<thead><tr>
            <th class="fixed-column" style="background:#2c3e50;color:#fff;min-width:280px;">Concepto</th>
            <th class="text-right" style="background:#3498db;color:#fff;">${CostoPersonalGlobal.esc(nomSuc1)}</th>
            <th class="text-right" style="background:#9b59b6;color:#fff;">${CostoPersonalGlobal.esc(nomSuc2)}</th>
            <th class="text-center" style="background:#e67e22;color:#fff;">Variación</th>
        </tr></thead>`;

        let tbody = '<tbody>';
        conceptos.forEach(c => {
            if (c.categoria && !activas.includes(c.categoria) && !c.is_subtotal && !c.is_metric && !c.is_percentage) return;

            let rowCls = '';
            if      (c.is_percentage)   rowCls = 'row-porcentaje-cp';
            else if (c.is_subtotal && !c.is_subtotal_cat) rowCls = 'row-subtotal';
            else if (c.is_subtotal_cat) rowCls = `row-subtotal-cat-${(c.categoria || '').toLowerCase()}`;

            // Para la fila %, sobreescribir con valores recalculados
            let val1 = c.valor_suc1;
            let val2 = c.valor_suc2;
            if (c.is_percentage) {
                val1 = pct1Recalc;
                val2 = pct2Recalc;
            } else if (c.is_subtotal && !c.is_subtotal_cat) {
                val1 = CostoPersonalGlobal.calcularCostoTotal(data.suc1?.costos_por_categoria || {});
                val2 = CostoPersonalGlobal.calcularCostoTotal(data.suc2?.costos_por_categoria || {});
            }

            const fmt1 = val1 !== null ? (c.is_percentage ? val1.toFixed(2) + '%' : CostoPersonalGlobal.fmtPesos(val1)) : '—';
            const fmt2 = val2 !== null ? (c.is_percentage ? val2.toFixed(2) + '%' : CostoPersonalGlobal.fmtPesos(val2)) : '—';

            // Variación
            let varHtml = '—';
            if (val1 !== null && val2 !== null) {
                if (c.is_percentage) {
                    const diff = val2 - val1;
                    const sign = diff > 0 ? '+' : '';
                    const col  = diff > 0 ? '#e74c3c' : '#27ae60';
                    varHtml = `<span style="color:${col};font-weight:600;">${sign}${diff.toFixed(2)} pp</span>`;
                } else if (!c.is_metric && val1 !== 0) {
                    const varPct = ((val2 - val1) / val1) * 100;
                    const sign   = varPct > 0 ? '+' : '';
                    const col    = varPct > 0 ? '#e74c3c' : '#27ae60';
                    varHtml = `<span style="color:${col};font-weight:600;">${sign}${varPct.toFixed(2)}%</span>`;
                }
            }

            tbody += `<tr class="${rowCls}">
                <td class="fixed-column">${CostoPersonalGlobal.esc(c.nombre ?? '')}</td>
                <td class="text-right" style="border-left:3px solid #3498db;">${fmt1}</td>
                <td class="text-right" style="border-left:3px solid #9b59b6;">${fmt2}</td>
                <td class="text-center" style="border-left:3px solid #e67e22;">${varHtml}</td>
            </tr>`;
        });
        tbody += '</tbody>';

        $('#cpTablaComparacion').html(thead + tbody);

        _dtable = $('#cpTablaComparacion').DataTable({
            responsive:     false,
            scrollX:        true,
            scrollCollapse: true,
            autoWidth:      false,
            paging:         false,
            searching:      false,
            info:           false,
            ordering:       false,
            fixedColumns:   { leftColumns: 1 },
            language: { emptyTable: 'Sin datos' },
        });
        _dtable.columns.adjust().draw(false);
    }

    function _limpiar() {
        $('#cpCmpSuc1, #cpCmpSuc2').val('');
        $('#cpCmpKpi').hide();
        $('#cpCmpTableSection').hide();
        $('#cpCmpBtnPDF').hide();
        $('#cpCmpEmptyState').fadeIn();
        _data = null;
        if (_dtable) { _dtable.destroy(); $('#cpTablaComparacion').empty(); _dtable = null; }
    }

    function _recalcularConCategorias() {
        if (!_data) return;
        const nomSuc1 = $('#cpCmpSuc1 option:selected').text();
        const nomSuc2 = $('#cpCmpSuc2 option:selected').text();
        _renderKPI(_data, nomSuc1, nomSuc2);
        _renderTabla(_data, nomSuc1, nomSuc2);
    }

    function _showError(msg) {
        Swal.fire({ icon: 'error', title: 'Error en la comparación', text: msg, confirmButtonText: 'Entendido', confirmButtonColor: '#e74c3c' });
    }

    $(document).ready(init);

    return {};

})();
