
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#desde", { dateFormat: "Y-m-d" });
    flatpickr("#hasta", { dateFormat: "Y-m-d" });

    const sucursalesGrid = document.getElementById('sucursales-grid');
    const sortImporteBtn = document.getElementById('sortImporte');
    const sortTicketsBtn = document.getElementById('sortTickets');
    const sortTicketPromedioBtn = document.getElementById('sortTicketPromedio');
    const quickSearchInput = document.getElementById('quickSearch');
    const clearSearchButton = document.getElementById('clearSearch');

    let sucursalesData = [];

    function initializeSucursalesData() {
        sucursalesData = [];
        const cards = sucursalesGrid.querySelectorAll('.col');
        cards.forEach(card => {
            const importe = parseFloat(card.querySelector('.venta-actual').textContent.replace('$', '').replace(/\./g, '').replace(',', '.'));
            const tickets = parseInt(card.querySelector('.tickets').textContent.match(/\d+/)[0].replace('.', ''));
            
            // Modificación aquí para extraer correctamente el ticket promedio
            const ticketPromedioText = card.querySelector('.ticket-promedio').textContent;
            const ticketPromedioMatch = ticketPromedioText.match(/\$\s*([\d.,]+)/);
            const ticketPromedio = ticketPromedioMatch ? parseFloat(ticketPromedioMatch[1].replace(/\./g, '').replace(',', '.')) : 0;

            sucursalesData.push({ 
                element: card, 
                importe: importe, 
                tickets: tickets,
                ticketPromedio: ticketPromedio,
                sucursal: card.querySelector('.card-title').textContent.toLowerCase()
            });
        });
        console.log('Datos inicializados:', sucursalesData);
    }

    function sortCards(sortBy) {
        console.log('Ordenando por:', sortBy);
        console.log('Antes del ordenamiento:', sucursalesData.map(d => ({ sucursal: d.sucursal, [sortBy]: d[sortBy] })));
        sucursalesData.sort((a, b) => b[sortBy] - a[sortBy]);
        console.log('Después del ordenamiento:', sucursalesData.map(d => ({ sucursal: d.sucursal, [sortBy]: d[sortBy] })));
        updateDisplay();
    }

    function updateDisplay() {
        sucursalesData.forEach((data, index) => {
            sucursalesGrid.appendChild(data.element);
            const rankingElement = data.element.querySelector('.ranking-number');
            if (rankingElement) {
                rankingElement.textContent = index + 1;
            }
        });
    }

    function filterCards(searchTerm) {
        sucursalesData.forEach(data => {
            if (data.sucursal.includes(searchTerm.toLowerCase())) {
                data.element.style.display = '';
            } else {
                data.element.style.display = 'none';
            }
        });
    }

    function addClickListener(element, callback) {
        if (!element) return;
        ['click', 'touchstart'].forEach(eventType => {
            element.addEventListener(eventType, function(e) {
                e.preventDefault();
                console.log(`Evento ${eventType} activado en ${element.id}`);
                callback();
            });
        });
    }

    if (sucursalesGrid) {
        addClickListener(sortImporteBtn, function() {
            initializeSucursalesData();
            sortCards('importe');
        });

        addClickListener(sortTicketsBtn, function() {
            initializeSucursalesData();
            sortCards('tickets');
        });

        addClickListener(sortTicketPromedioBtn, function() {
            initializeSucursalesData();
            sortCards('ticketPromedio');
        });

        if (quickSearchInput) {
            quickSearchInput.addEventListener('input', function() {
                filterCards(this.value);
                clearSearchButton.style.display = this.value ? 'inline' : 'none';
            });
        }

        if (clearSearchButton) {
            clearSearchButton.addEventListener('click', function() {
                quickSearchInput.value = '';
                filterCards('');
                this.style.display = 'none';
            });
            clearSearchButton.style.display = 'none';
        }

        initializeSucursalesData();
    }
});