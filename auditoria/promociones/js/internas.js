/**
 * internas.js — Módulo de Promociones Internas (placeholder)
 * TODO: implementar cuando el SP esté disponible.
 */

const Internas = (() => {
    'use strict';

    function init() {
        // Escuchar evento de consulta pero no hacer nada por ahora
        document.addEventListener('promos:consultar', () => {
            // placeholder — sin implementación
        });
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', Internas.init);
