$(document).ready(function() {
    // Initialize Select2 for edit modal
    $('#codArticulo').select2({
        dropdownParent: $('#altaModal'),
        placeholder: 'Seleccione un artículo...',
        allowClear: true
    });

    // Modern export functionality
    $('#btn_export').click(function() {
        modernExportToExcel();
    });

    // Reset import modal when closed
    $('#altaModalImport').on('hidden.bs.modal', function() {
        $('#fileupload').val('');
        $('#tblItems tbody').empty();
        updateImportCounter();
        $('#saveButtonText').text('Importar Artículos');
    });

    // Update counter when rows are added to import table
    function updateImportCounter() {
        const rowCount = $('#tblItems tbody tr').length;
        $('#importCounter').text(rowCount + ' artículo' + (rowCount !== 1 ? 's' : ''));
    }

    // Observer for table changes in import modal
    const targetNode = document.querySelector('#tblItems tbody');
    if (targetNode) {
        const observer = new MutationObserver(function(mutationsList, observer) {
            updateImportCounter();
        });
        observer.observe(targetNode, { childList: true });
    }

    // Initialize tooltips if needed
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Modern export function
function modernExportToExcel() {
    // Show loading state
    const originalText = $('#btn_export').html();
    $('#btn_export').html('<i class="bi bi-arrow-clockwise"></i> Exportando...');
    $('#btn_export').prop('disabled', true);

    setTimeout(() => {
        // Use the existing table2excel functionality
        $('input[type=number]').each(function(){
            this.setAttribute('value',$(this).val());
        });
            
        $("table").table2excel({
            exclude: ".noExl",
            name: "Articulos Excluidos",
            filename: "ArticulosExcluidos_" + new Date().toISOString().split('T')[0],
            fileext: ".xls",
        });

        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Exportación completada',
            text: 'El archivo se ha descargado correctamente',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });

        // Restore button
        $('#btn_export').html(originalText);
        $('#btn_export').prop('disabled', false);
    }, 500);
}

// Enhanced search function (keeps existing myFunction but adds modern features)
function myFunction() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("textBox");
    filter = input.value.toUpperCase();
    table = document.getElementById("table");
    tr = table.getElementsByTagName("tr");

    let visibleRows = 0;

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
            visibleRows++;
        } else {
            tr[i].style.display = "none";
        }
    }

    // Update select all checkbox state based on visible rows
    updateSelectAllState();
}

// Function to update select all checkbox state
function updateSelectAllState() {
    const visibleCheckboxes = document.querySelectorAll('#table tr:not([style*="display: none"]) .check');
    const checkedVisible = document.querySelectorAll('#table tr:not([style*="display: none"]) .check:checked');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    if (visibleCheckboxes.length === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (checkedVisible.length === visibleCheckboxes.length) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else if (checkedVisible.length > 0) {
        selectAllCheckbox.indeterminate = true;
    } else {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    }
}

// Enhanced delete function with modern UI
function deleteArticulo() {
    const matriz = matrizOrdenes();
    const selectedCount = matriz.filter(id => id).length;
    
    if (selectedCount === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin selección',
            text: 'No hay ningún artículo seleccionado para eliminar',
            confirmButtonColor: '#3498db'
        });
        return;
    }

    Swal.fire({
        title: '¿Está seguro?',
        html: `Se eliminarán <strong>${selectedCount}</strong> artículo(s) seleccionado(s).<br><small class="text-muted">Esta acción no se puede deshacer.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash3"></i> Sí, eliminar',
        cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Eliminando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            postear(matriz);
        }
    });
}

// Matrix function - moved from main.js
function matrizOrdenes() {
    let checked = document.querySelectorAll("#check");
    let matriz = [];

    for (let i = 0; i < checked.length; i++) {
        if (checked[i].parentElement.parentElement.style.display == "" && checked[i].checked == true) { //si el tr está visible
            matriz[i] = checked[i].parentElement.parentElement.childNodes[1].textContent;
        }
    }
    return matriz;
}

// Enhanced postear function with modern UI
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
                text: 'Ocurrió un error al intentar eliminar los artículos. Por favor, intente nuevamente.',
                confirmButtonColor: '#e74c3c'
            });
        }
    });
}

// Enhanced contar function with visual feedback
var cont = 0;

function contar(checkbox) {
    cont = 0;
    var checkboxes = document.querySelectorAll('#table input[type="checkbox"]:not(#selectAll)');
    
    for (var x = 0; x < checkboxes.length; x++) {
        if (checkboxes[x].checked) {
            cont = cont + 1;
        }
    }

    // Update UI based on selection
    updateSelectionUI();
    updateSelectAllState();
}

function updateSelectionUI() {
    const deleteBtn = document.getElementById('btn_delete');
    const editBtn = document.getElementById('btn_edit');
    
    if (cont > 0) {
        deleteBtn.innerHTML = `<i class="bi bi-trash3"></i> Eliminar (${cont})`;
        deleteBtn.classList.remove('btn-outline-danger');
        deleteBtn.classList.add('btn-delete');
    } else {
        deleteBtn.innerHTML = '<i class="bi bi-trash3"></i> Eliminar';
        deleteBtn.classList.add('btn-outline-danger');
        deleteBtn.classList.remove('btn-delete');
    }
}

// Enhanced insertarArticulo with modern UI
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
            text: 'Por favor ingrese una cantidad válida',
            confirmButtonColor: '#3498db'
        });
        return;
    }

    // Show loading
    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        allowEscapeKey: false,
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
                text: 'Ocurrió un error al intentar guardar los cambios. Por favor, intente nuevamente.',
                confirmButtonColor: '#e74c3c'
            });
        }
    });
}

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

function redireccionar() {
    location.href = 'index.php';
}

// Function to download Excel template
function descargarPlantilla() {
    // Show loading
    Swal.fire({
        title: 'Generando plantilla...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        showConfirmButton: false,
        timer: 1500,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    setTimeout(() => {
        // Create workbook
        const wb = XLSX.utils.book_new();
        
        // Template data with headers and examples
        const templateData = [
            ['ARTICULO', 'CANTIDAD', 'OBSERVACIONES'], // Encabezados
            ['XC5SLZ03C0301', 69, 'CARTERA CUERO VER 25-26'],
            ['XC5SLZ02C1701', 60, 'CARTERA CUERO VER 25-26'],
            ['XC5SLZ02C1709', 60, 'CARTERA CUERO VER 25-26'],
            ['XC5SLZ01C0124', 50, 'CARTERA CUERO VER 25-26'],
            ['XC5SLZ01C0209', 40, 'CARTERA CUERO VER 25-26'],
            ['XC5SLZ01C1701', 60, 'CARTERA CUERO VER 25-26']
        ];
        
        // Create worksheet
        const ws = XLSX.utils.aoa_to_sheet(templateData);
        
        // Set column widths
        ws['!cols'] = [
            { width: 18 },  // Código Artículo
            { width: 12 },  // Cantidad
            { width: 35 }   // Observaciones
        ];
        
        // Style the header row
        const headerCells = ['A1', 'B1', 'C1'];
        headerCells.forEach(cell => {
            if (ws[cell]) {
                ws[cell].s = {
                    font: { bold: true, color: { rgb: "FFFFFF" } },
                    fill: { fgColor: { rgb: "2C3E50" } },
                    alignment: { horizontal: "center" }
                };
            }
        });
        
        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, 'Artículos Excluidos');
        
        // Generate Excel file
        XLSX.writeFile(wb, 'Plantilla_Articulos_Excluidos.xlsx');
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Plantilla descargada',
            html: `
                <div class="text-start">
                    <p><strong>Archivo:</strong> Plantilla_Articulos_Excluidos.xlsx</p>
                    <hr>
                    <small class="text-muted">
                        <strong>Instrucciones:</strong><br>
                        • La primera fila contiene los <strong>encabezados</strong> (no eliminar)<br>
                        • <strong>Columna A:</strong> Código del artículo (ej: XC5SLZ03C0301)<br>
                        • <strong>Columna B:</strong> Cantidad a excluir (número entero)<br>
                        • <strong>Columna C:</strong> Observaciones o motivo<br><br>
                        <em class="text-primary">Puede modificar los ejemplos por sus datos reales.<br>
                        Los códigos de ejemplo son reales y se validarán contra el maestro.</em>
                    </small>
                </div>
            `,
            confirmButtonText: '<i class="bi bi-check-lg"></i> Entendido',
            confirmButtonColor: '#9b59b6',
            width: '550px'
        });
    }, 1500);
}