
// Gestión de Checkboxes y Selección Masiva
let contadorSeleccionados = 0;

$(document).ready(function() {
    inicializarCheckboxes();
});

function inicializarCheckboxes() {
    // Event listeners para checkboxes individuales
    $(document).on('change', '.check', function() {
        contar();
        actualizarEstadoSelectAll();
        actualizarUISeleccion();
    });
    
    // Event listener para select all
    $('#selectAll').on('change', function() {
        checkMasivo(this);
    });
}

function guardarCheck(datoMasivo = 0, e) {
    let check;
    let dato = datoMasivo != 0 ? datoMasivo : e.target;
    if (dato.checked == true) {
        check = 1;
    } else {
        check = 0;
    }
    // Aquí puedes agregar lógica adicional si necesitas guardar el estado
}

function checkMasivo(source) {
    var checkboxes = document.querySelectorAll(".check");
    
    for (var i = 0; i < checkboxes.length; i++) {
        // Solo cambiar checkboxes visibles
        const fila = checkboxes[i].closest('tr');
        if (fila.style.display !== 'none') {
            checkboxes[i].checked = source.checked;
            guardarCheck(checkboxes[i]);
        }
    }
    
    contar();
    actualizarUISeleccion();
}

function contar() {
    contadorSeleccionados = 0;
    const checkboxes = document.querySelectorAll('.check');
    
    for (let i = 0; i < checkboxes.length; i++) {
        const fila = checkboxes[i].closest('tr');
        // Solo contar checkboxes visibles y marcados
        if (checkboxes[i].checked && fila.style.display !== 'none') {
            contadorSeleccionados++;
        }
    }
    
    console.log('Artículos seleccionados:', contadorSeleccionados);
    return contadorSeleccionados;
}

function actualizarEstadoSelectAll() {
    const checkboxesVisibles = $('.check').filter(function() {
        return $(this).closest('tr').is(':visible');
    });
    
    const checkboxesMarcados = checkboxesVisibles.filter(':checked');
    const selectAllCheckbox = $('#selectAll');
    
    if (checkboxesVisibles.length === 0) {
        selectAllCheckbox.prop('indeterminate', false);
        selectAllCheckbox.prop('checked', false);
    } else if (checkboxesMarcados.length === checkboxesVisibles.length) {
        selectAllCheckbox.prop('indeterminate', false);
        selectAllCheckbox.prop('checked', true);
    } else if (checkboxesMarcados.length > 0) {
        selectAllCheckbox.prop('indeterminate', true);
        selectAllCheckbox.prop('checked', false);
    } else {
        selectAllCheckbox.prop('indeterminate', false);
        selectAllCheckbox.prop('checked', false);
    }
}

function actualizarUISeleccion() {
    const deleteBtn = $('#btn_delete');
    
    if (contadorSeleccionados > 0) {
        deleteBtn.html(`<i class="bi bi-trash3"></i> Eliminar (${contadorSeleccionados})`);
        deleteBtn.removeClass('btn-outline-danger').addClass('btn-delete');
    } else {
        deleteBtn.html('<i class="bi bi-trash3"></i> Eliminar');
        deleteBtn.removeClass('btn-delete').addClass('btn-outline-danger');
    }
}

function matrizOrdenes() {
    let checked = document.querySelectorAll(".check");
    let matriz = [];

    for (let i = 0; i < checked.length; i++) {
        const fila = checked[i].parentElement.parentElement;
        // Solo incluir filas visibles y marcadas
        if (fila.style.display !== "none" && checked[i].checked) {
            const id = fila.childNodes[1].textContent.trim();
            matriz.push(id);
        }
    }
    
    // Filtrar elementos vacíos
    matriz = matriz.filter(id => id && id !== '');
    console.log('Matriz de IDs a eliminar:', matriz);
    return matriz;
}

// Función para obtener conteo actual
function obtenerConteoSeleccionados() {
    return contadorSeleccionados;
}

// Función para limpiar selección
function limpiarSeleccion() {
    document.querySelectorAll('.check').forEach(cb => {
        cb.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    contadorSeleccionados = 0;
    actualizarUISeleccion();
}

// Función para seleccionar por criterio
function seleccionarPorCriterio(criterio) {
    document.querySelectorAll('.check').forEach(cb => {
        const fila = cb.closest('tr');
        if (fila.style.display !== 'none') {
            // Implementar lógica de criterio aquí
            cb.checked = true;
        }
    });
    contar();
    actualizarEstadoSelectAll();
    actualizarUISeleccion();
}