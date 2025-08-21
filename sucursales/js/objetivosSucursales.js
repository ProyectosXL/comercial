
document.addEventListener('DOMContentLoaded', function() {
    // Configuración de Flatpickr para los campos de fecha
    flatpickr("#desde, #hasta", {
        dateFormat: "Y-m-d"
    });
    
    // Funciones de ordenamiento
    function sortCards(sortFunction) {
        const container = document.getElementById('sucursales-grid');
        const cards = Array.from(container.children);
        cards.sort(sortFunction);
        cards.forEach((card, index) => {
            container.appendChild(card);
            card.querySelector('.ranking-number').textContent = index + 1;
        });
    }

    // Función para ordenar por ventas (de mayor a menor)
    function sortByVentas() {
        sortCards((a, b) => {
            const ventaA = parseFloat(a.querySelector('.venta-actual').textContent.replace(/[^0-9.-]+/g,""));
            const ventaB = parseFloat(b.querySelector('.venta-actual').textContent.replace(/[^0-9.-]+/g,""));
            return ventaB - ventaA;
        });
    }

    // Ordenar por ventas al cargar la página
    sortByVentas();

    // Event listeners para los botones de ordenamiento
    document.getElementById('sortVentas').addEventListener('click', sortByVentas);

    document.getElementById('sortCumplimientoFecha').addEventListener('click', () => {
        sortCards((a, b) => {
            const cumplA = parseFloat(a.querySelector('.variacion-fecha').textContent);
            const cumplB = parseFloat(b.querySelector('.variacion-fecha').textContent);
            return cumplB - cumplA;
        });
    });

    document.getElementById('sortCumplimientoMes').addEventListener('click', () => {
        sortCards((a, b) => {
            const cumplA = parseFloat(a.querySelector('.variacion-mes').textContent);
            const cumplB = parseFloat(b.querySelector('.variacion-mes').textContent);
            return cumplB - cumplA;
        });
    });

});