/**
 * costoPersonal.js
 * Módulo global para el dashboard Costo de Personal.
 * Gestiona el toggle de categorías (FIJO/VARIABLE/DIFERIDO/CONTINGENTE),
 * dispara el evento `cp:categorias-changed` cuando cambia la selección,
 * y expone utilidades de formato compartidas por todos los tabs.
 */

const CostoPersonalGlobal = (() => {

    const STORAGE_KEY         = 'cp:categorias_activas';
    const STORAGE_KEY_AJUSTE  = 'cp:ajuste_inflacion';
    const STORAGE_KEY_PANEL   = 'cp:avisos_panel_abierto';
    const TODAS_CATS          = ['FIJO', 'VARIABLE', 'DIFERIDO', 'CONTINGENTE'];
    const MIN_ACTIVAS         = 1;

    /* ── Estado ──────────────────────────────────────────────── */
    let _activas      = [...TODAS_CATS];   // categorías actualmente activas
    let _ajusteActivo = false;             // toggle ajuste por inflación

    /* ── Inicialización ──────────────────────────────────────── */
    function _cargarDesdeStorage() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            if (raw) {
                const arr = JSON.parse(raw);
                if (Array.isArray(arr) && arr.length >= MIN_ACTIVAS) {
                    _activas = arr.filter(c => TODAS_CATS.includes(c));
                    if (_activas.length < MIN_ACTIVAS) _activas = [...TODAS_CATS];
                }
            }
        } catch (_) {
            _activas = [...TODAS_CATS];
        }
    }

    function _guardarEnStorage() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(_activas));
        } catch (_) {}
    }

    function _cargarAjusteDesdeStorage() {
        try {
            _ajusteActivo = sessionStorage.getItem(STORAGE_KEY_AJUSTE) === 'true';
        } catch (_) {
            _ajusteActivo = false;
        }
    }

    function _guardarAjusteEnStorage() {
        try {
            sessionStorage.setItem(STORAGE_KEY_AJUSTE, String(_ajusteActivo));
        } catch (_) {}
    }

    /* ── Render visual de las píldoras ──────────────────────── */
    function _renderCheckboxes() {
        $('#cpCategoriasToggle .cp-cat-pill').each(function () {
            const $pill = $(this);
            const val   = $pill.find('input[type=checkbox]').val();
            const activ = _activas.includes(val);

            $pill.find('input[type=checkbox]').prop('checked', activ);
            $pill.toggleClass('activa',   activ);
            $pill.toggleClass('inactiva', !activ);

            // Deshabilitar si es la única activa (no puede deseleccionarse)
            const solaActiva = activ && _activas.length === MIN_ACTIVAS;
            $pill.find('input[type=checkbox]').prop('disabled', solaActiva);
            $pill.css('cursor', solaActiva ? 'not-allowed' : 'pointer');
        });
    }

    /* ── Render del pill de ajuste inflación ────────────────── */
    function _renderAjustePill() {
        const $toggle = $('#cpAjusteInflacionToggle');
        // Solo mostrar si DIFERIDO está activo
        if (!_activas.includes('DIFERIDO')) {
            $toggle.hide();
            return;
        }
        $toggle.show();
        const $check = $('#cpAjusteInflacionCheck');
        $check.prop('checked', _ajusteActivo);
        const $dot   = $('.cp-ajuste-dot-indicator');
        const $label = $('#cpAjusteLabel');
        if (_ajusteActivo) {
            $dot.addClass('activo');
            $label.text('Con ajuste inflación');
        } else {
            $dot.removeClass('activo');
            $label.text('Sin ajuste (nominal)');
        }
    }

    /* ── Binding de eventos ──────────────────────────────────── */
    function _bindEvents() {
        // El <input> está dentro del <label>, así que el browser ya lo togglea
        // al hacer clic en cualquier parte de la píldora — no se necesita handler
        // adicional de click (agregaría un segundo toggle cancelando el primero).
        $(document).on('change', '#cpCategoriasToggle .cat-toggle', function () {
            const val     = $(this).val();
            const checked = $(this).prop('checked');

            if (checked) {
                if (!_activas.includes(val)) _activas.push(val);
            } else {
                if (_activas.length <= MIN_ACTIVAS) {
                    // Revertir: no se puede quedar sin categorías
                    $(this).prop('checked', true);
                    return;
                }
                _activas = _activas.filter(c => c !== val);
            }

            _guardarEnStorage();
            _renderCheckboxes();
            _renderAjustePill();
            _dispatchChange();
        });

        // Toggle ajuste por inflación
        $('#cpAjusteInflacionCheck').on('change', function () {
            _ajusteActivo = $(this).prop('checked');
            _guardarAjusteEnStorage();
            _renderAjustePill();
            $(document).trigger('cp:ajuste-inflacion-changed', [{ activo: _ajusteActivo }]);
        });
    }

    /* ── Despachar evento global ─────────────────────────────── */
    function _dispatchChange() {
        $(document).trigger('cp:categorias-changed', [{ activas: [..._activas] }]);
    }

    /* ── API pública ─────────────────────────────────────────── */

    /**
     * Devuelve las categorías actualmente activas.
     * @returns {string[]}
     */
    function getActivas() {
        return [..._activas];
    }

    /**
     * Calcula el costo total combinando sólo las categorías activas.
     * @param {Object} costos_por_categoria  { FIJO: n, VARIABLE: n, DIFERIDO: n, CONTINGENTE: n }
     * @returns {number}
     */
    function calcularCostoTotal(costos_por_categoria) {
        if (!costos_por_categoria) return 0;
        return _activas.reduce((sum, cat) => sum + (costos_por_categoria[cat] || 0), 0);
    }

    /**
     * Calcula % costo de personal sobre venta neta.
     * @param {number} costo
     * @param {number} ventaNeta
     * @returns {number|null}
     */
    function calcularPorcentaje(costo, ventaNeta) {
        if (!ventaNeta || ventaNeta <= 0) return null;
        return (costo / ventaNeta) * 100;
    }

    /* ── Helpers de formato (compartidos por todos los módulos) ─ */

    function fmtPesos(v) {
        if (v === null || v === undefined) return '—';
        return new Intl.NumberFormat('es-AR', {
            style:                 'currency',
            currency:              'ARS',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(v);
    }

    function fmtPct(v, decimals = 2) {
        if (v === null || v === undefined) return '—';
        return v.toFixed(decimals) + '%';
    }

    function fmtDateHuman(dateStr) {
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-');
        const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        return `${parseInt(d)} ${meses[parseInt(m) - 1]} ${y}`;
    }

    function fmtDate(d) {
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${d.getFullYear()}-${mm}-${dd}`;
    }

    function calcRange(months) {
        const hoy   = new Date();
        const hasta = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
        const desde = new Date(hasta.getFullYear(), hasta.getMonth() - (months - 1), 1);
        return { desde: fmtDate(desde), hasta: fmtDate(hasta) };
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ── Clasificar semáforo con umbrales dinámicos de CP_CONFIG ─ */
    function semClass(pct) {
        if (pct === null || pct === undefined) return 'sin_datos';
        const cfg = typeof CP_CONFIG !== 'undefined' ? CP_CONFIG : {};
        const azul     = cfg.umbralAzul     ?? 15;
        const verde    = cfg.umbralVerde    ?? 18;
        const amarillo = cfg.umbralAmarillo ?? 20;
        const naranja  = cfg.umbralNaranja  ?? 22;
        if (pct <= azul)     return 'azul';
        if (pct <= verde)    return 'verde';
        if (pct <= amarillo) return 'amarillo';
        if (pct <= naranja)  return 'naranja';
        return 'rojo';
    }

    function semLabel(pct) {
        const cl = semClass(pct);
        const labels = { azul: 'Excelente', verde: 'Eficiente', amarillo: 'Aceptable', naranja: 'Riesgo', rojo: 'Crítico', sin_datos: 'Sin datos' };
        return labels[cl] || 'Sin datos';
    }

    function semColor(pct) {
        const cl = semClass(pct);
        const colors = { azul: '#3498db', verde: '#27ae60', amarillo: '#f1c40f', naranja: '#e67e22', rojo: '#e74c3c', sin_datos: '#95a5a6' };
        return colors[cl] || '#95a5a6';
    }

    /* ── Banner: cuotas DIFERIDO sin ajuste ────────────────────── */

    /**
     * Muestra u oculta el banner de cuotas sin ajuste en #cp-banner-cuotas-sin-ajuste.
     * Solo aparece cuando el toggle de ajuste está activo y hay cuotas sin procesar.
     * @param {boolean} ajusteActivo
     * @param {Object}  cuotas  { total, con_ajuste, sin_ajuste }
     */
    function renderBannerCuotasSinAjuste(ajusteActivo, cuotas) {
        const $banner = $('#cp-banner-cuotas-sin-ajuste');
        if (!$banner.length) return;

        if (!ajusteActivo || !cuotas || cuotas.sin_ajuste === 0) {
            $banner.hide().empty();
            return;
        }

        const pct = cuotas.total > 0 ? Math.round((cuotas.sin_ajuste / cuotas.total) * 100) : 0;
        $banner.html(
            `<div class="cp-banner-info">` +
                `<span class="cp-banner-icon">&#8505;</span>` +
                `<div class="cp-banner-content">` +
                    `<strong>Cuotas DIFERIDO sin ajuste</strong>` +
                    `<small>${cuotas.sin_ajuste} de ${cuotas.total} cuotas (${pct}%) no tienen factor de inflación aplicado — se usa el importe nominal para esas filas.</small>` +
                `</div>` +
            `</div>`
        ).show();
    }

    /* ── Panel consolidado de avisos ─────────────────────────────── */

    /**
     * Punto de entrada único para actualizar los 3 banners y el panel contenedor.
     * @param {Object} data  response.data devuelto por cualquier controlador
     */
    function actualizarPanelAvisos(data) {
        if (!data) return;

        // Render individual de cada banner interno
        renderBannerMesesSinDatos(
            data.meses_sin_datos           || [],
            data.meses_totales_periodo     || 0,
            data.meses_con_datos_completos || 0
        );
        const aj = data.ajuste_inflacion;
        renderBannerCuotasSinAjuste(aj ? aj.activo : false, aj ? aj.cuotas : null);
        _renderBannerValidacion(data.validacion_mensual || null);

        _actualizarCabeceraPanel();
    }

    function _renderBannerValidacion(vm) {
        const $banner = $('#cp-banner-validacion-rrhh');
        if (!$banner.length) return;

        if (!vm || !vm.total_pendientes || vm.total_pendientes === 0) {
            $banner.hide().empty();
            return;
        }

        const n = vm.total_pendientes;
        const meses = (vm.meses_pendientes || []).map(m => {
            const [y, mo] = m.split('-');
            const MESES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            return MESES[parseInt(mo) - 1] + ' ' + y;
        }).join(', ');

        $banner.html(
            `<div class="cp-banner-validacion">` +
                `<span class="cp-banner-icon"><i class="bi bi-shield-exclamation"></i></span>` +
                `<div class="cp-banner-content">` +
                    `<strong>Validación RRHH pendiente</strong>` +
                    `<small>${n} ${n === 1 ? 'mes sin validar' : 'meses sin validar'}: ${esc(meses)}.</small>` +
                `</div>` +
            `</div>`
        ).show();
    }

    function _actualizarCabeceraPanel() {
        const $panel  = $('#cp-avisos-panel');
        if (!$panel.length) return;

        const $banners = [
            { $el: $('#cp-banner-meses-sin-datos'),    cls: 'badge-sinDatos',   label: 'Meses s/datos' },
            { $el: $('#cp-banner-cuotas-sin-ajuste'),  cls: 'badge-ajuste',     label: 'Sin ajuste'    },
            { $el: $('#cp-banner-validacion-rrhh'),    cls: 'badge-validacion', label: 'Validación'    },
        ];

        // No usar .is(':visible'): los banners están dentro de #cpAvisosBody (display:none),
        // por lo que la visibilidad heredada siempre es false. Chequeamos solo el estilo propio del elemento.
        const activos = $banners.filter(b => b.$el.length && b.$el[0].style.display !== 'none' && b.$el.html().trim() !== '');

        if (activos.length === 0) {
            $panel.hide();
            return;
        }

        $panel.show();

        const resumen = activos.length === 1
            ? '1 aviso activo'
            : activos.length + ' avisos activos';
        $('#cpAvisosResumen').text(resumen);

        const $badges = $('#cpAvisosBadges').empty();
        activos.forEach(b => {
            $badges.append(`<span class="cp-aviso-mini-badge ${b.cls}">${b.label}</span>`);
        });

        // Restaurar estado expandido desde sessionStorage
        const abierto = sessionStorage.getItem(STORAGE_KEY_PANEL) === 'true';
        _setPanelExpanded(abierto, false);
    }

    function _setPanelExpanded(abierto, animate) {
        const $body    = $('#cpAvisosBody');
        const $chevron = $('#cpAvisosChevronIcon').parent();

        if (abierto) {
            animate ? $body.slideDown(200) : $body.show();
            $chevron.addClass('open');
        } else {
            animate ? $body.slideUp(200) : $body.hide();
            $chevron.removeClass('open');
        }
    }

    /* ── Banner: meses sin datos ────────────────────────────────── */

    const _NOMBRES_MES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    const _VERBO = {
        SIN_COSTOS: { sing: 'no tiene costos cargados',          plur: 'no tienen costos cargados' },
        SIN_VENTAS: { sing: 'no tiene ventas cargadas',           plur: 'no tienen ventas cargadas' },
        AMBOS:      { sing: 'no tiene costos ni ventas cargados', plur: 'no tienen costos ni ventas cargados' },
    };

    function _parseFecha(fecha) {
        const [y, m] = fecha.split('-');
        return { nombre: _NOMBRES_MES[parseInt(m) - 1], anio: parseInt(y) };
    }

    function _formatGrupo(fechas, estado) {
        const parsed = fechas.map(_parseFecha);
        const n      = parsed.length;
        const v      = (_VERBO[estado] || _VERBO.AMBOS);
        const verbo  = n === 1 ? v.sing : v.plur;

        if (n === 1) return `${parsed[0].nombre} ${parsed[0].anio} ${verbo}`;

        const anyos = [...new Set(parsed.map(p => p.anio))];

        if (n === 2) {
            // "Febrero 2025 y Marzo 2025 …"
            return `${parsed[0].nombre} ${parsed[0].anio} y ${parsed[1].nombre} ${parsed[1].anio} ${verbo}`;
        }

        // 3 o más
        if (anyos.length === 1) {
            // "Febrero, Marzo y Abril 2025 …"
            const head = parsed.slice(0, -1).map(p => p.nombre).join(', ');
            return `${head} y ${parsed[n - 1].nombre} ${anyos[0]} ${verbo}`;
        }

        // Años distintos: cada mes lleva su año
        const head = parsed.slice(0, -1).map(p => `${p.nombre} ${p.anio}`).join(', ');
        return `${head} y ${parsed[n - 1].nombre} ${parsed[n - 1].anio} ${verbo}`;
    }

    function _buildBannerMsg(mesesSinDatos) {
        const grupos = {};
        for (const mes of mesesSinDatos) {
            if (!grupos[mes.estado]) grupos[mes.estado] = [];
            grupos[mes.estado].push(mes.mes);
        }
        const partes = Object.entries(grupos).map(([est, fechas]) => _formatGrupo(fechas, est));
        return partes.join('. ') + '.';
    }

    /**
     * Muestra u oculta el banner de "meses sin datos" en #cp-banner-meses-sin-datos.
     * @param {Array}  mesesSinDatos   Array de {fecha, estado} devuelto por el backend.
     * @param {number} periodoCompleto Total de meses en el período seleccionado.
     * @param {number} periodoConDatos Meses con datos completos que se usaron.
     */
    function renderBannerMesesSinDatos(mesesSinDatos, periodoCompleto, periodoConDatos) {
        const $banner = $('#cp-banner-meses-sin-datos');
        if (!$banner.length) return;

        if (!mesesSinDatos || mesesSinDatos.length === 0) {
            $banner.hide().empty();
            return;
        }

        const msg  = _buildBannerMsg(mesesSinDatos);
        const sub  = periodoConDatos < periodoCompleto
            ? `Se calculó con ${periodoConDatos} de ${periodoCompleto} meses disponibles. `
            : '';

        $banner.html(
            `<div class="cp-banner-warning">` +
                `<span class="cp-banner-icon">&#9888;</span>` +
                `<div class="cp-banner-content">` +
                    `<strong>Período incompleto</strong>` +
                    `<small>${sub}${esc(msg)}</small>` +
                `</div>` +
            `</div>`
        ).show();
    }

    /* ── Auto-init cuando el DOM esté listo ────────────────────── */
    function init() {
        _cargarDesdeStorage();
        _cargarAjusteDesdeStorage();
        _renderCheckboxes();
        _renderAjustePill();
        _bindEvents();

        // Panel expand/collapse
        $(document).on('click', '#cpAvisosHeader', function () {
            const abierto = $('#cpAvisosBody').is(':visible');
            const nuevoEstado = !abierto;
            try { sessionStorage.setItem(STORAGE_KEY_PANEL, String(nuevoEstado)); } catch (_) {}
            _setPanelExpanded(nuevoEstado, true);
        });
    }

    $(document).ready(init);

    function getAjusteInflacionActivo() { return _ajusteActivo; }

    return {
        getActivas,
        calcularCostoTotal,
        calcularPorcentaje,
        actualizarPanelAvisos,
        renderBannerMesesSinDatos,
        renderBannerCuotasSinAjuste,
        getAjusteInflacionActivo,
        fmtPesos,
        fmtPct,
        fmtDateHuman,
        fmtDate,
        calcRange,
        esc,
        semClass,
        semLabel,
        semColor,
    };

})();
