// Función para actualizar el modal de confirmación de stock
function actualizarModalConfirmacionStock() {
    const categorias = document.querySelectorAll('[data-categoria-id]');
    const categoriasSinCargar = [];
    let totalUnidades = 0;
    let totalCategorias = 0;

    // Recorrer todas las categorías
    categorias.forEach(categoria => {
        const input = categoria.querySelector('input[type="number"]');
        if (input) {
            totalCategorias++;
            const valor = parseInt(input.value || 0);
            const nombreCategoria = categoria.querySelector('.nombre-categoria')?.textContent || 
                                  categoria.getAttribute('data-categoria-nombre') || 
                                  'Categoría ' + totalCategorias;
            
            if (valor === 0) {
                categoriasSinCargar.push(nombreCategoria.trim());
            }
            totalUnidades += valor;
        }
    });

    // Actualizar total de categorías
    document.getElementById('totalCategorias').textContent = totalCategorias;
    
    // Actualizar total de unidades
    document.getElementById('totalUnidades').textContent = totalUnidades;

    // Actualizar lista de categorías sin cargar
    const contenedorCategoriasSinCargar = document.getElementById('categoriasSinCargar');
    const listaCategorias = contenedorCategoriasSinCargar.querySelector('ul');
    listaCategorias.innerHTML = '';
    
    if (categoriasSinCargar.length > 0) {
        categoriasSinCargar.forEach(categoria => {
            const li = document.createElement('li');
            li.textContent = categoria;
            listaCategorias.appendChild(li);
        });
        contenedorCategoriasSinCargar.style.display = 'block';
    } else {
        contenedorCategoriasSinCargar.style.display = 'none';
    }
}

// Agregar el evento al botón que abre el modal
document.addEventListener('DOMContentLoaded', function() {
    const btnAbrirModal = document.querySelector('[data-bs-target="#modalConfirmarStock"]');
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', function() {
            actualizarModalConfirmacionStock();
        });
    }

    // También actualizar cuando se muestra el modal (por si se abre de otra manera)
    const modalConfirmarStock = document.getElementById('modalConfirmarStock');
    if (modalConfirmarStock) {
        modalConfirmarStock.addEventListener('show.bs.modal', function() {
            actualizarModalConfirmacionStock();
        });
    }
});
