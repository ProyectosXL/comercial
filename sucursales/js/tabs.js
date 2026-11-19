
/**
 * Script para manejar las pestañas
 */
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Obtener el ID del tab a mostrar
            const tabId = button.getAttribute('data-tab');
            
            // Quitar la clase active de todos los botones y contenidos
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            tabContents.forEach(content => content.classList.remove('active'));

            // Agregar la clase active al botón y contenido seleccionado
            button.classList.add('active');
            button.setAttribute('aria-selected', 'true');
            document.getElementById(tabId).classList.add('active');
        });
    });

    tabButtons.forEach(btn => btn.setAttribute('aria-selected', btn.classList.contains('active') ? 'true' : 'false'));
});