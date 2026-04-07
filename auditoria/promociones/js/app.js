/**
 * app.js — Auditoría de Promociones
 * Gestión de filtros, localStorage y orquestación entre tabs.
 */

const PromoApp = (() => {
    'use strict';

    const LS_KEY_DESDE = 'promos_filtro_desde';
    const LS_KEY_HASTA = 'promos_filtro_hasta';

    // -------------------------------------------------------
    // Helpers de fecha
    // -------------------------------------------------------

    /** Retorna el valor "YYYY-MM" del mes actual */
    function mesActual() {
        const hoy = new Date();
        return `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}`;
    }

    /** Retorna el valor "YYYY-MM" del mes anterior */
    function mesAnterior() {
        const hoy = new Date();
        hoy.setMonth(hoy.getMonth() - 1);
        return `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}`;
    }

    /** Convierte "YYYY-MM" → "YYYY-MM-01" (primer día del mes) */
    function primerDia(mesAnio) {
        return `${mesAnio}-01`;
    }

    /** Convierte "YYYY-MM" → último día del mes en formato "YYYY-MM-DD" */
    function ultimoDia(mesAnio) {
        const [anio, mes] = mesAnio.split('-').map(Number);
        const ultimo = new Date(anio, mes, 0); // día 0 del mes siguiente = último del actual
        return `${ultimo.getFullYear()}-${String(ultimo.getMonth() + 1).padStart(2, '0')}-${String(ultimo.getDate()).padStart(2, '0')}`;
    }

    /** Formatea "YYYY-MM" a texto legible: "Enero 2025" */
    function formatearMesAnio(mesAnio) {
        const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        const [anio, mes] = mesAnio.split('-').map(Number);
        return `${meses[mes - 1]} ${anio}`;
    }

    // -------------------------------------------------------
    // Loader
    // -------------------------------------------------------
    function mostrarLoader() {
        document.getElementById('loader-overlay').style.display = 'flex';
    }

    function ocultarLoader() {
        document.getElementById('loader-overlay').style.display = 'none';
    }

    // -------------------------------------------------------
    // Inicialización de filtros
    // -------------------------------------------------------
    function inicializarFiltros() {
        const $desde = document.getElementById('filtro-desde');
        const $hasta = document.getElementById('filtro-hasta');

        // Restaurar desde localStorage o usar defaults
        $desde.value = localStorage.getItem(LS_KEY_DESDE) || mesAnterior();
        $hasta.value = localStorage.getItem(LS_KEY_HASTA) || mesActual();
    }

    // -------------------------------------------------------
    // Persistir filtros y disparar consulta
    // -------------------------------------------------------
    function obtenerFiltros() {
        const desde = document.getElementById('filtro-desde').value;
        const hasta = document.getElementById('filtro-hasta').value;
        return { desde, hasta };
    }

    function consultarActivo() {
        const { desde, hasta } = obtenerFiltros();

        if (!desde || !hasta) {
            alert('Seleccioná ambas fechas antes de consultar.');
            return;
        }
        if (desde > hasta) {
            alert('La fecha desde debe ser menor o igual a la fecha hasta.');
            return;
        }

        // Persistir en localStorage
        localStorage.setItem(LS_KEY_DESDE, desde);
        localStorage.setItem(LS_KEY_HASTA, hasta);

        // Mostrar rótulo del período aplicado
        const rotulo = document.getElementById('filtro-aplicado');
        rotulo.textContent = `Período: ${formatearMesAnio(desde)} — ${formatearMesAnio(hasta)}`;
        rotulo.style.display = '';

        const fechaDesde = primerDia(desde);
        const fechaHasta = ultimoDia(hasta);

        // Disparar evento para que cada módulo reaccione
        document.dispatchEvent(new CustomEvent('promos:consultar', {
            detail: { fechaDesde, fechaHasta }
        }));
    }

    // -------------------------------------------------------
    // Init
    // -------------------------------------------------------
    function init() {
        inicializarFiltros();

        document.getElementById('btn-consultar').addEventListener('click', consultarActivo);

        // Shortcut: Enter en los inputs de fecha dispara consulta
        ['filtro-desde', 'filtro-hasta'].forEach(id => {
            document.getElementById(id).addEventListener('keydown', e => {
                if (e.key === 'Enter') consultarActivo();
            });
        });
    }

    // API pública
    return {
        init,
        mostrarLoader,
        ocultarLoader,
    };

})();

// Arrancar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', PromoApp.init);
