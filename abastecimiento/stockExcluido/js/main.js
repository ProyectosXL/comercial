// Archivo principal simplificado - Solo funciones esenciales

//Búsqueda rápida en tabla
function myFunction() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("textBox");
    filter = input.value.toUpperCase();
    table = document.getElementById("table");
    tr = table.getElementsByTagName("tr");

    let filasVisibles = 0;

    for (i = 0; i < tr.length; i++) {
        var visible = false;
        td = tr[i].getElementsByTagName("td");

        for (var j = 0; j < td.length; j++) {
            if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                visible = true;
            }
        }

        if (visible === true) {
            tr[i].style.display = "";
            filasVisibles++;
        } else {
            tr[i].style.display = "none";
        }
    }

    // Actualizar estado del checkbox "Seleccionar todo" después del filtrado
    if (typeof actualizarEstadoSelectAll === 'function') {
        actualizarEstadoSelectAll();
    }
}

//Verifica si el artículo existe en la STA11
function traerDescripcion() {
    $("#inputDescrip").val("");
    var codigoAbuscar = $("#codArticulo").val();

    $.ajax({
        url: "Controller/buscarCodigo.php",
        method: "POST",
        data: {
            codArticu: codigoAbuscar,
        },
        success: function (data) {
            let datos = JSON.parse(data);
            if (datos.code != 200) {
                $("#inputDescrip").val("NO EXISTE EL ARTICULO SELECCIONADO");
            } else {
                let codigo = JSON.parse(datos.msg);
                $("#inputDescrip").val(codigo[0].DESCRIPCIO);
            }
        },
    });
}

// Función para insertar/actualizar artículo
function insertarArticulo() {
    let articulo = document.getElementById("codArticulo").value;
    let cantidad = document.getElementById("inputCant").value;
    let observaciones = document.getElementById("inputObs").value;

    if (!articulo) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor seleccione un artículo',
            confirmButtonColor: '#3498db'
        });
        return;
    }

    if (!cantidad || cantidad <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cantidad inválida',
            text: 'Por favor ingrese una cantidad válida mayor a 0',
            confirmButtonColor: '#3498db'
        });
        return;
    }

    // Mostrar loading
    Swal.fire({
        title: 'Guardando cambios...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: "Controller/articuloController.php?action=update",
        method: "POST",
        data: {
            articulo: articulo,
            cantidad: cantidad,
            observaciones: observaciones
        },
        success: function (data) {
            procesarEvento();
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error al guardar',
                text: 'Ocurrió un error al intentar guardar los cambios.',
                confirmButtonColor: '#e74c3c'
            });
        }
    });
}

// Procesar evento exitoso
function procesarEvento() {
    Swal.fire({
        icon: 'success',
        title: '¡Guardado exitosamente!',
        text: 'Los cambios han sido guardados correctamente',
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    }).then(() => {
        redireccionar();
    });
}

// Redireccionar a index
function redireccionar() {
    location.href = 'index.php';
}

// Función para eliminar (ahora usa las funciones de checkbox-manager)
function postear(matriz) {
    let env = 1;
    let url = (env == 1) ? 'deleteArticulo.php' : 'PedidoTest.php';
    
    $.ajax({
        url: 'Controller/' + url,
        method: 'POST',
        data: {
            matriz: matriz,
        },
        success: function (data) {
            Swal.fire({
                icon: 'success',
                title: '¡Eliminado exitosamente!',
                text: 'Los artículos han sido eliminados correctamente',
                confirmButtonColor: '#27ae60',
                confirmButtonText: '<i class="bi bi-check-lg"></i> Continuar'
            }).then(function () {
                window.location = "index.php";
            });
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error al eliminar',
                text: 'Ocurrió un error al intentar eliminar los artículos.',
                confirmButtonColor: '#e74c3c'
            });
        }
    });
}

// Configuración inicial
$(document).ready(function() {
    // Configurar select2 para el modal de edición
    if (typeof $.fn.select2 !== 'undefined') {
        $('#codArticulo').select2({
            dropdownParent: $('#altaModal'),
            placeholder: 'Seleccione un artículo...',
            allowClear: true
        });
    }
    
    // Event listeners para botones principales
    $('#btn_export').on('click', function() {
        if (typeof modernExportToExcel === 'function') {
            modernExportToExcel();
        }
    });
});