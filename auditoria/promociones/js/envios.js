/**
 * envios.js — Módulo de Envíos Gratis (placeholder)
 * TODO: implementar cuando el SP esté disponible.
 */

const Envios = (() => {
    'use strict';

    function init() {
        // Escuchar evento de consulta pero no hacer nada por ahora
        document.addEventListener('promos:consultar', () => {
            // placeholder — sin implementación
        });
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', Envios.init);
