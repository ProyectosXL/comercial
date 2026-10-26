// Global variables
let currentRubro = '';
let currentRubroDesc = '';

// Initialize when DOM is loaded
$(document).ready(function() {
    initializeSearch();
    updateStats();
    initializeTooltips();
    
    // Get current rubro from hidden div
    currentRubro = $('#siglaRubro').text() || 'A';
    currentRubroDesc = $('#rubroActual').text() || 'ACCESORIOS';
});

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    row.classList.add('animate-fade-in');
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

// Initialize tooltips
function initializeTooltips() {
    $('[data-tooltip]').hover(
        function() {
            const tooltip = $(this).attr('data-tooltip');
            $(this).append('<div class="tooltip-text">' + tooltip + '</div>');
        },
        function() {
            $('.tooltip-text').remove();
        }
    );
}

// Modal functions
function abrirModalCategoria() {
    document.getElementById('modalCategoria').classList.remove('hidden');
    document.getElementById('codCategoria').focus();
}

function cerrarModalCategoria() {
    document.getElementById('modalCategoria').classList.add('hidden');
    document.getElementById('codCategoria').value = '';
    document.getElementById('descCategoria').value = '';
    document.getElementById('descCategoriaPadre').value = '';
}

function abrirModalRubro() {
    document.getElementById('modalRubro').classList.remove('hidden');
    cargarSiglasDisponibles();
}

function cerrarModalRubro() {
    document.getElementById('modalRubro').classList.add('hidden');
    document.getElementById('siglaRubroModal').value = '';
    document.getElementById('descRubro').value = '';
}

// Load available siglas for new rubro
function cargarSiglasDisponibles() {
    $.ajax({
        url: 'Controller/CategoriaController.php?accion=obtenerSiglasDisponibles',
        method: 'GET',
        success: function(data) {
            const select = document.getElementById('siglaRubroModal');
            if (select) {
                select.innerHTML = '<option value="">Seleccionar sigla...</option>';
                
                try {
                    const siglas = JSON.parse(data);
                    siglas.forEach(sigla => {
                        const option = document.createElement('option');
                        option.value = sigla;
                        option.textContent = sigla;
                        select.appendChild(option);
                    });
                } catch (e) {
                    console.error('Error parsing siglas:', e);
                }
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar las siglas disponibles',
                background: '#fff',
                color: '#374151'
            });
        }
    });
}

// Add new rubro
function agregarRubro() {
    const sigla = document.getElementById('siglaRubroModal').value;
    const descripcion = document.getElementById('descRubro').value;
    
    if (!sigla || !descripcion.trim()) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Complete todos los campos!',
            background: '#fff',
            color: '#374151'
        });
        return;
    }
    
    showLoading();
    
    $.ajax({
        url: 'Controller/CategoriaController.php?accion=insertarNuevoRubro',
        method: 'POST',
        data: {
            sigla: sigla,
            descripcion: descripcion.trim()
        },
        success: function(data) {
            hideLoading();
            
            if (data == 1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: 'Rubro agregado correctamente!',
                    background: '#fff',
                    color: '#374151',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    cerrarModalRubro();
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'El rubro ya existe o error en la inserción',
                    background: '#fff',
                    color: '#374151'
                });
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            console.error('AJAX Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión con el servidor',
                background: '#fff',
                color: '#374151'
            });
        }
    });
}

// Updated function - Add new category with DESC_CATEGORIA_PADRE
const agregar = (siglaRubro) => {
    let codCategoria = document.getElementById("codCategoria").value;
    let descCategoria = document.getElementById("descCategoria").value;
    let descCategoriaPadre = document.getElementById("descCategoriaPadre").value;

    if(codCategoria == "" || descCategoria == ""){
        Swal.fire({
            icon: "error",
            title: "Error",
            text: `Complete los campos obligatorios!`,
            background: '#fff',
            color: '#374151'
        })
        return;
    }

    if(codCategoria < 0 || codCategoria > 100){ 
        Swal.fire({
            icon: "error",
            title: "Error",
            text: `El codigo de la categoria no puede ser negativo o mayor a 100!`,
            background: '#fff',
            color: '#374151'
        })
        return;
    }

    codCategoria = String(codCategoria).padStart(2, '0')

    showLoading();

    $.ajax({
        url: 'Controller/CategoriaController.php?accion=insertarNuevo',
        method: 'POST',
        data:{
            codCategoria: codCategoria,
            descCategoria: descCategoria,
            descCategoriaPadre: descCategoriaPadre,
            siglaRubro: siglaRubro
        },
        success : function(data) {
            hideLoading();
            if(data == 1){
                Swal.fire({
                    icon: "success",
                    title: "Carga Exitosa",
                    text: `Categoria Insertada!`,
                    background: '#fff',
                    color: '#374151',
                    timer: 2000,
                    showConfirmButton: false
                }).then((result) => {
                    cerrarModalCategoria();
                    location.reload();
                })
            }else{
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: `La categoria ya existe para ese rubro!`,
                    background: '#fff',
                    color: '#374151'
                })
            }
        },
        error: function() {
            hideLoading();
            Swal.fire({
                icon: "error",
                title: "Error",
                text: `Error de conexión`,
                background: '#fff',
                color: '#374151'
            })
        }
    })
}

// NUEVA FUNCIÓN: Cambiar estado de categoría

function cambiarEstado(button, siglaRubro, codCategoria, nuevoEstado) {
    const estadoTexto = nuevoEstado == 1 ? 'activar' : 'desactivar';
    
    Swal.fire({
        title: '¿Confirmar cambio?',
        text: `¿Está seguro que desea ${estadoTexto} esta categoría?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado == 1 ? '#10b981' : '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Sí, ${estadoTexto}`,
        cancelButtonText: 'Cancelar',
        background: '#fff',
        color: '#374151'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading en el botón
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            // Debug: mostrar valores que se envían
            console.log('Enviando:', {
                siglaRubro: siglaRubro,
                codCategoria: codCategoria,
                nuevoEstado: nuevoEstado
            });
            
            $.ajax({
                url: 'Controller/CategoriaController.php?accion=actualizarEstado',
                method: 'POST',
                data: {
                    siglaRubro: siglaRubro,
                    codCategoria: codCategoria,
                    nuevoEstado: nuevoEstado
                },
                success: function(data) {
                    console.log('Respuesta del servidor:', data);
                    
                    if (data == 1) {
                        // Actualizar toda la fila para asegurar consistencia
                        const row = button.closest('tr');
                        const estadoCell = row.querySelector('td:nth-child(6)');
                        const accionesCell = row.querySelector('td:nth-child(7)');
                        const esActivo = nuevoEstado == 1;
                        
                        // Actualizar badge de estado
                        estadoCell.innerHTML = `
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${esActivo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                <i class="fas ${esActivo ? 'fa-check-circle' : 'fa-times-circle'} mr-1"></i>
                                ${esActivo ? 'Activo' : 'Inactivo'}
                            </span>
                        `;
                        
                        // Recrear completamente los botones de acción
                        const rubro = row.children[0].textContent;
                        const categoria = row.children[2].textContent;
                        
                        accionesCell.innerHTML = `
                            <div class="flex justify-center gap-2">
                                <button onclick="editar(this)" 
                                        class="px-3 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-all duration-200 hover-lift"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Editar categoría">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <button onclick="cambiarEstado(this, '${rubro}', '${categoria}', ${esActivo ? 0 : 1})" 
                                        class="px-3 py-2 ${esActivo ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'} text-white rounded-lg transition-all duration-200 hover-lift"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="${esActivo ? 'Desactivar' : 'Activar'} categoría">
                                    <i class="fas ${esActivo ? 'fa-toggle-off' : 'fa-toggle-on'}"></i>
                                </button>
                            </div>
                        `;
                        
                        // Reinicializar tooltips para los nuevos botones
                        const tooltips = accionesCell.querySelectorAll('[data-bs-toggle="tooltip"]');
                        tooltips.forEach(el => {
                            new bootstrap.Tooltip(el);
                        });
                        
                        // Actualizar estadísticas
                        updateStats();
                        
                        // Mostrar éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Estado actualizado',
                            text: `Categoría ${estadoTexto}da correctamente`,
                            background: '#fff',
                            color: '#374151',
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                        
                        // Efecto visual en la fila
                        row.classList.add('estado-success-fade');
                        setTimeout(() => {
                            row.classList.remove('estado-success-fade');
                        }, 2000);
                        
                    } else {
                        // Restaurar botón en caso de error
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo actualizar el estado de la categoría. Respuesta: ' + data,
                            background: '#fff',
                            color: '#374151'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Restaurar botón en caso de error
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                    
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor: ' + error,
                        background: '#fff',
                        color: '#374151'
                    });
                }
            });
        }
    });
}

// FUNCIÓN AUXILIAR: Actualizar estadísticas después del cambio de estado
function updateStats() {
    const rows = document.querySelectorAll('#tableBody tr');
    const totalCategorias = rows.length;
    
    // Contar categorías activas
    let categoriasActivas = 0;
    rows.forEach(row => {
        const estadoSpan = row.querySelector('td:nth-child(6) span');
        if (estadoSpan && estadoSpan.textContent.trim() === 'Activo') {
            categoriasActivas++;
        }
    });
    
    // Actualizar display
    const totalElement = document.getElementById('totalCategorias');
    const activasElement = document.getElementById('categoriasActivas');
    
    if (totalElement) totalElement.textContent = totalCategorias;
    if (activasElement) activasElement.textContent = categoriasActivas;
}

// Updated function - Edit category with both DESC_CATEGORIA and DESC_CATEGORIA_PADRE
const editar = (div) => {
    let allTd = div.parentElement.parentElement.parentElement.querySelectorAll("td");
    let descCategoriaElement = allTd[3];
    let descCategoriaPadreElement = allTd[4];
    let actionElement = allTd[6]; // Actualizado: ahora las acciones están en la columna 6
    
    let originalDescCategoria = descCategoriaElement.textContent.trim();
    let originalDescCategoriaPadre = descCategoriaPadreElement.textContent.trim();
    
    // Create input for DESC_CATEGORIA
    descCategoriaElement.innerHTML = `
        <input type="text" id="descCategoriaEdit" value="${originalDescCategoria}" 
               class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded text-gray-800 focus:ring-2 focus:ring-blue-500" 
               placeholder="Descripción de la categoría">`;
    
    // Create input for DESC_CATEGORIA_PADRE
    descCategoriaPadreElement.innerHTML = `
        <input type="text" id="descCategoriaPadreEdit" value="${originalDescCategoriaPadre}" 
               class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded text-gray-800 focus:ring-2 focus:ring-blue-500" 
               placeholder="Descripción categoría padre">`;
    
    // Change buttons to save/cancel
    actionElement.innerHTML = `
        <div class="flex justify-center gap-2">
            <button onclick="guardarEdicion(this)" class="px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-all duration-200"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Guardar cambios">
                <i class="fas fa-save"></i>
            </button>
            <button onclick="cancelarEdicion(this, '${originalDescCategoria.replace(/'/g, "\\'")}', '${originalDescCategoriaPadre.replace(/'/g, "\\'")}'" class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all duration-200"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Cancelar">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    
    // Focus and select the first input
    document.getElementById('descCategoriaEdit').focus();
    document.getElementById('descCategoriaEdit').select();
    
    // Add Enter key navigation
    document.getElementById('descCategoriaEdit').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('descCategoriaPadreEdit').focus();
        }
    });
    
    document.getElementById('descCategoriaPadreEdit').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            guardarEdicion(actionElement.querySelector('button'));
        }
    });
}

// Updated function - Save edition with both fields
function guardarEdicion(button) {
    let allTd = button.parentElement.parentElement.parentElement.querySelectorAll("td");
    let rubro = allTd[0].textContent;
    let categoria = allTd[2].textContent;
    
    let descCategoriaInput = document.getElementById('descCategoriaEdit');
    let descCategoriaPadreInput = document.getElementById('descCategoriaPadreEdit');
    
    if (!descCategoriaInput || !descCategoriaPadreInput) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron encontrar los campos de edición',
            background: '#fff',
            color: '#374151'
        });
        return;
    }
    
    let nuevoValor = descCategoriaInput.value.trim();
    let nuevoValorPadre = descCategoriaPadreInput.value.trim();

    if (!nuevoValor) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'La descripción de la categoría no puede estar vacía',
            background: '#fff',
            color: '#374151'
        });
        descCategoriaInput.focus();
        return;
    }

    // Show loading on the row
    const row = button.parentElement.parentElement.parentElement;
    row.classList.add('loading');

    $.ajax({
        url: 'Controller/CategoriaController.php?accion=actualizarDescCategoria',
        method: 'POST',
        data:{
            nuevoValor: nuevoValor,
            nuevoValorPadre: nuevoValorPadre,
            rubro: rubro,
            Categoria: categoria
        },
        success : function(data) {
            row.classList.remove('loading');
            
            if (data == 1) {
                // Update the cells with new values
                allTd[3].innerHTML = nuevoValor;
                allTd[4].innerHTML = nuevoValorPadre;
                
                // Get current estado to restore correct buttons
                const estadoSpan = allTd[5].querySelector('span');
                const esActivo = estadoSpan.textContent.trim() === 'Activo';
                
                // Reset buttons to edit mode
                allTd[6].innerHTML = `
                    <div class="flex justify-center gap-2">
                        <button onclick="editar(this)" 
                                class="px-3 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-all duration-200 hover-lift"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Editar categoría">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <button onclick="cambiarEstado(this, '${rubro}', '${categoria}', ${esActivo ? '0' : '1'})" 
                                class="px-3 py-2 ${esActivo ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'} text-white rounded-lg transition-all duration-200 hover-lift"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="${esActivo ? 'Desactivar' : 'Activar'} categoría">
                            <i class="fas ${esActivo ? 'fa-toggle-off' : 'fa-toggle-on'}"></i>
                        </button>
                    </div>`;
                
                // Show success toast
                Swal.fire({
                    icon: 'success',
                    title: 'Actualizado',
                    text: 'Categoría actualizada correctamente',
                    background: '#fff',
                    color: '#374151',
                    timer: 1500,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo actualizar la categoría',
                    background: '#fff',
                    color: '#374151'
                });
                
                // Restore original state if update failed
                location.reload();
            }
        },
        error: function(xhr, status, error) {
            row.classList.remove('loading');
            console.error('AJAX Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al actualizar la categoría: ' + error,
                background: '#fff',
                color: '#374151'
            });
        }
    });
}

// Updated function - Cancel edition with both fields
function cancelarEdicion(button, originalDescCategoria, originalDescCategoriaPadre) {
    let allTd = button.parentElement.parentElement.parentElement.querySelectorAll("td");
    let rubro = allTd[0].textContent;
    let categoria = allTd[2].textContent;
    
    // Restore original values
    allTd[3].innerHTML = originalDescCategoria;
    allTd[4].innerHTML = originalDescCategoriaPadre;
    
    // Get current estado to restore correct buttons
    const estadoSpan = allTd[5].querySelector('span');
    const esActivo = estadoSpan.textContent.trim() === 'Activo';
    
    // Reset buttons to edit mode
    allTd[6].innerHTML = `
        <div class="flex justify-center gap-2">
            <button onclick="editar(this)" 
                    class="px-3 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-all duration-200 hover-lift"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Editar categoría">
                <i class="fas fa-edit"></i>
            </button>
            
            <button onclick="cambiarEstado(this, '${rubro}', '${categoria}', ${esActivo ? '0' : '1'})" 
                    class="px-3 py-2 ${esActivo ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'} text-white rounded-lg transition-all duration-200 hover-lift"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="${esActivo ? 'Desactivar' : 'Activar'} categoría">
                <i class="fas ${esActivo ? 'fa-toggle-off' : 'fa-toggle-on'}"></i>
            </button>
        </div>`;
}

// Original function kept for backward compatibility - Update category description
const actualizarValor = (input) => {
    let allTd = input.parentElement.parentElement.querySelectorAll("td")
    let nuevoValor = input.value.trim();
    let rubro = allTd[0].textContent;
    let categoria = allTd[2].textContent;

    if (!nuevoValor) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'La descripción no puede estar vacía',
            background: '#fff',
            color: '#374151'
        });
        input.focus();
        return;
    }

    // Show loading on the row
    const row = input.parentElement.parentElement;
    row.classList.add('loading');

    $.ajax({
        url: 'Controller/CategoriaController.php?accion=actualizarDescCategoria',
        method: 'POST',
        data:{
            nuevoValor: nuevoValor,
            nuevoValorPadre: '', // Empty padre value for backward compatibility
            rubro: rubro,
            Categoria: categoria
        },
        success : function(data) {
            row.classList.remove('loading');
            input.parentElement.innerHTML = nuevoValor;
            
            // Show success toast
            Swal.fire({
                icon: 'success',
                title: 'Actualizado',
                text: 'Categoría actualizada correctamente',
                background: '#fff',
                color: '#374151',
                timer: 1500,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        },
        error: function() {
            row.classList.remove('loading');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al actualizar la categoría',
                background: '#fff',
                color: '#374151'
            });
        }
    })
}

// Helper functions
function updateStats() {
    const rows = document.querySelectorAll('#tableBody tr');
    const totalCategorias = rows.length;
    
    // Count active categories
    let categoriasActivas = 0;
    rows.forEach(row => {
        const estadoSpan = row.querySelector('td:nth-child(6) span');
        if (estadoSpan && estadoSpan.textContent.trim() === 'Activo') {
            categoriasActivas++;
        }
    });
    
    // Find highest category code
    let ultimoCodigo = 0;
    rows.forEach(row => {
        const codigo = parseInt(row.children[2].textContent);
        if (codigo > ultimoCodigo) {
            ultimoCodigo = codigo;
        }
    });
    
    const totalElement = document.getElementById('totalCategorias');
    const activasElement = document.getElementById('categoriasActivas');
    const ultimoElement = document.getElementById('ultimoCodigo');
    
    if (totalElement) totalElement.textContent = totalCategorias;
    if (activasElement) activasElement.textContent = categoriasActivas;
    if (ultimoElement) ultimoElement.textContent = String(ultimoCodigo).padStart(2, '0');
}

function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('hidden');
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('hidden');
    }
}

// Event listeners for modals
document.addEventListener('DOMContentLoaded', function() {
    // Close modals when clicking outside
    const modalCategoria = document.getElementById('modalCategoria');
    const modalRubro = document.getElementById('modalRubro');
    
    if (modalCategoria) {
        modalCategoria.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalCategoria();
            }
        });
    }
    
    if (modalRubro) {
        modalRubro.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalRubro();
            }
        });
    }
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalCategoria();
            cerrarModalRubro();
            cerrarModalImportacion();
        }
    });
    
    // Enter key in modal forms
    const codCategoriaInput = document.getElementById('codCategoria');
    const descCategoriaInput = document.getElementById('descCategoria');
    const descCategoriaPadreInput = document.getElementById('descCategoriaPadre');
    const descRubroInput = document.getElementById('descRubro');
    
    if (codCategoriaInput) {
        codCategoriaInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                descCategoriaInput.focus();
            }
        });
    }
    
    if (descCategoriaInput) {
        descCategoriaInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                descCategoriaPadreInput.focus();
            }
        });
    }
    
    if (descCategoriaPadreInput) {
        descCategoriaPadreInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const siglaRubro = document.getElementById('siglaRubro').textContent;
                agregar(siglaRubro);
            }
        });
    }
    
    if (descRubroInput) {
        descRubroInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                agregarRubro();
            }
        });
    }

});

// Función para filtrar por estado
function filtrarPorEstado(tipo) {
    const table = $('#tableData').DataTable();
    
    // Remover clase active de todos los filtros
    document.querySelectorAll('.estado-filter').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Agregar clase active al filtro clickeado
    document.getElementById('filter-' + tipo).classList.add('active');
    
    // Aplicar filtro según el tipo
    if (tipo === 'todos') {
        table.search('').columns().search('').draw();
    } else if (tipo === 'activos') {
        table.column(5).search('Activo').draw(); // Columna 5 es el Estado
    } else if (tipo === 'inactivos') {
        table.column(5).search('Inactivo').draw(); // Columna 5 es el Estado
    }
    
    // Actualizar estadísticas después del filtrado
    updateStatsAfterFilter();
}

// Función para actualizar estadísticas después del filtrado
function updateStatsAfterFilter() {
    const table = $('#tableData').DataTable();
    const visibleRows = table.rows({ filter: 'applied' }).nodes();
    
    let activasCount = 0;
    let totalCount = visibleRows.length;
    
    $(visibleRows).each(function() {
        const estadoCell = $(this).find('td:eq(5)'); // Columna 5 es el Estado
        if (estadoCell.text().includes('Activo')) {
            activasCount++;
        }
    });
    
    // Opcional: Mostrar información de filtrado en consola
    console.log(`Filtro aplicado - Mostrando: ${totalCount} total, ${activasCount} activas`);
}

// ===== Reasignación masiva de categorías por Excel =====
// Lee el Excel en el navegador (SheetJS), valida en el servidor y, al confirmar,
// actualiza SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA_AUX.

let filasImportacion = [];      // filas leídas del Excel
let previewImportacion = [];    // resultado de la validación del servidor

// Encabezados aceptados (normalizados: mayúsculas, sin tildes, separadores como "_")
const COLUMNAS_IMPORTACION = {
    COD_ARTICU: ['COD_ARTICU', 'CODIGO', 'COD_ARTICULO', 'CODIGO_ARTICULO', 'ARTICULO'],
    CATEGORIA: ['CATEGORIA', 'CATEGORIA_NUEVA'],
    CATEGORIA_PADRE: ['CATEGORIA_PADRE', 'PADRE', 'CATEGORIA_PADRE_NUEVA']
};

function abrirModalImportacion() {
    limpiarImportacion();
    document.getElementById('archivoImportacion').value = '';
    document.getElementById('modalImportacion').classList.remove('hidden');
}

function cerrarModalImportacion() {
    const modal = document.getElementById('modalImportacion');
    if (modal) modal.classList.add('hidden');
}

function limpiarImportacion() {
    filasImportacion = [];
    previewImportacion = [];
    document.getElementById('resumenImportacion').classList.add('hidden');
    document.getElementById('previewImportacion').classList.add('hidden');
    document.getElementById('previewImportacion').innerHTML = '';
    document.getElementById('btnErroresImportacion').classList.add('hidden');
    document.getElementById('btnAplicarImportacion').disabled = true;
}

function normalizarEncabezado(texto) {
    return String(texto ?? '').trim().toUpperCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[\s.\-]+/g, '_');
}

function escaparHtml(texto) {
    const mapa = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String(texto ?? '').replace(/[&<>"']/g, c => mapa[c]);
}

function descargarPlantillaImportacion() {
    const ws = XLSX.utils.aoa_to_sheet([
        ['COD_ARTICU', 'CATEGORIA', 'CATEGORIA_PADRE'],
        ['XA0SLV07L0101', 'LENTES', 'LENTES']
    ]);
    ws['!cols'] = [{ wch: 18 }, { wch: 25 }, { wch: 25 }];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Categorias');
    XLSX.writeFile(wb, 'PlantillaReasignacionCategorias.xlsx');
}

function leerExcelImportacion(file) {
    limpiarImportacion();
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        try {
            const wb = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });
            const ws = wb.Sheets[wb.SheetNames[0]];
            const filas = XLSX.utils.sheet_to_json(ws, { header: 1, raw: false, defval: '' });

            if (filas.length < 2) {
                Swal.fire('Archivo vacío', 'El Excel no tiene filas debajo de los encabezados.', 'warning');
                return;
            }

            // Ubicar cada columna por su encabezado
            const encabezados = filas[0].map(normalizarEncabezado);
            const indices = {};
            for (const [col, alias] of Object.entries(COLUMNAS_IMPORTACION)) {
                indices[col] = encabezados.findIndex(h => alias.includes(h));
            }
            const faltantes = ['COD_ARTICU', 'CATEGORIA'].filter(c => indices[c] === -1);
            if (faltantes.length) {
                Swal.fire('Faltan columnas', `No se encontró: ${faltantes.join(', ')}. Usá la plantilla como referencia.`, 'error');
                return;
            }

            filasImportacion = filas.slice(1)
                .map((cols, i) => ({
                    fila: i + 2,
                    COD_ARTICU: String(cols[indices.COD_ARTICU] ?? '').trim().toUpperCase(),
                    CATEGORIA: String(cols[indices.CATEGORIA] ?? '').trim().toUpperCase(),
                    CATEGORIA_PADRE: indices.CATEGORIA_PADRE === -1 ? '' : String(cols[indices.CATEGORIA_PADRE] ?? '').trim().toUpperCase()
                }))
                .filter(f => f.COD_ARTICU || f.CATEGORIA || f.CATEGORIA_PADRE);

            if (!filasImportacion.length) {
                Swal.fire('Archivo vacío', 'No se encontraron filas con datos.', 'warning');
                return;
            }

            validarImportacion();
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'No se pudo leer el archivo. Verificá que sea un Excel válido.', 'error');
        }
    };
    reader.readAsArrayBuffer(file);
}

async function llamarImportacion(accion, filas) {
    const resp = await fetch(`Controller/CategoriaController.php?accion=${accion}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ filas })
    });
    const texto = await resp.text();
    try {
        return JSON.parse(texto);
    } catch (e) {
        console.error('Respuesta no válida:', texto);
        throw new Error('El servidor devolvió una respuesta no válida.');
    }
}

async function validarImportacion() {
    showLoading();
    try {
        const res = await llamarImportacion('validarImportacion', filasImportacion);
        if (!res.ok) {
            Swal.fire('No se pudo validar', res.mensaje || 'Error desconocido', 'error');
            return;
        }
        previewImportacion = res.filas;
        renderPreviewImportacion(res.resumen);
    } catch (err) {
        Swal.fire('Error', err.message, 'error');
    } finally {
        hideLoading();
    }
}

function renderPreviewImportacion(resumen) {
    const badges = {
        OK: '<span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>',
        SIN_CAMBIOS: '<span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-700">Sin cambios</span>',
        ERROR: '<span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Error</span>'
    };

    const resumenEl = document.getElementById('resumenImportacion');
    resumenEl.innerHTML = `
        <span class="px-3 py-1 rounded-lg bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>${resumen.ok} para actualizar</span>
        <span class="px-3 py-1 rounded-lg bg-gray-200 text-gray-700"><i class="fas fa-minus-circle mr-1"></i>${resumen.sin_cambios} sin cambios</span>
        <span class="px-3 py-1 rounded-lg bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i>${resumen.error} con error</span>`;
    resumenEl.classList.remove('hidden');

    // Primero los errores, después los OK y al final los sin cambios
    const orden = { ERROR: 0, OK: 1, SIN_CAMBIOS: 2 };
    const filas = [...previewImportacion].sort((a, b) => orden[a.estado] - orden[b.estado] || a.fila - b.fila);

    const cuerpo = filas.map(r => `
        <tr class="${r.estado === 'ERROR' ? 'bg-red-50' : ''}">
            <td class="px-3 py-2 text-gray-500">${r.fila}</td>
            <td class="px-3 py-2 font-mono">${escaparHtml(r.COD_ARTICU)}</td>
            <td class="px-3 py-2">${escaparHtml(r.DESCRIPCIO)}</td>
            <td class="px-3 py-2">${escaparHtml(r.RUBRO)}</td>
            <td class="px-3 py-2">${escaparHtml(r.CATEGORIA_ACTUAL)}<div class="text-xs text-gray-500">${escaparHtml(r.CATEGORIA_PADRE_ACTUAL)}</div></td>
            <td class="px-3 py-2 font-medium">${escaparHtml(r.CATEGORIA_NUEVA || r.CATEGORIA_EXCEL)}<div class="text-xs text-gray-500">${escaparHtml(r.CATEGORIA_PADRE_NUEVA || r.CATEGORIA_PADRE_EXCEL)}</div></td>
            <td class="px-3 py-2">${badges[r.estado]}</td>
            <td class="px-3 py-2 text-xs">${escaparHtml(r.mensaje)}</td>
        </tr>`).join('');

    const preview = document.getElementById('previewImportacion');
    preview.innerHTML = `
        <table class="w-full text-left text-sm">
            <thead class="sticky top-0 bg-gray-100">
                <tr>
                    <th class="px-3 py-2">Fila</th>
                    <th class="px-3 py-2">Código</th>
                    <th class="px-3 py-2">Descripción</th>
                    <th class="px-3 py-2">Rubro</th>
                    <th class="px-3 py-2">Categoría / Padre actual</th>
                    <th class="px-3 py-2">Categoría / Padre nueva</th>
                    <th class="px-3 py-2">Estado</th>
                    <th class="px-3 py-2">Detalle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">${cuerpo}</tbody>
        </table>`;
    preview.classList.remove('hidden');

    document.getElementById('btnErroresImportacion').classList.toggle('hidden', resumen.error === 0);
    document.getElementById('btnAplicarImportacion').disabled = resumen.ok === 0;
}

async function aplicarImportacion() {
    const aActualizar = previewImportacion.filter(r => r.estado === 'OK');
    if (!aActualizar.length) return;

    const confirmacion = await Swal.fire({
        title: '¿Aplicar cambios?',
        html: `Se va a cambiar la categoría de <b>${aActualizar.length}</b> artículo(s).` +
              (previewImportacion.length > aActualizar.length ? '<br>Las filas con error o sin cambios no se tocan.' : ''),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, aplicar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#047857'
    });
    if (!confirmacion.isConfirmed) return;

    // Se reenvían los datos originales del Excel: el servidor vuelve a validar todo
    const filas = aActualizar.map(r => ({
        fila: r.fila,
        COD_ARTICU: r.COD_ARTICU,
        CATEGORIA: r.CATEGORIA_EXCEL,
        CATEGORIA_PADRE: r.CATEGORIA_PADRE_EXCEL
    }));

    showLoading();
    try {
        const res = await llamarImportacion('aplicarImportacion', filas);
        hideLoading();

        if (!res.ok) {
            Swal.fire('No se aplicaron cambios', res.mensaje || 'Error desconocido', 'error');
            return;
        }

        const rechazadas = (res.errores || []).length;
        await Swal.fire({
            title: 'Categorías actualizadas',
            html: `Se actualizaron <b>${res.actualizados}</b> artículo(s).` +
                  (rechazadas ? `<br>${rechazadas} fila(s) no pasaron la validación final y no se modificaron.` : '') +
                  (res.maestro_actualizado
                      ? '<br><br>El maestro de categorías ya está actualizado. Las demás bases se actualizan con la próxima corrida de maestros.'
                      : '<br><br><b>Los cambios quedaron grabados</b>, pero no se pudo regenerar el maestro: se van a ver cuando corra la próxima actualización de maestros.'),
            icon: res.maestro_actualizado ? 'success' : 'warning'
        });
        cerrarModalImportacion();
    } catch (err) {
        hideLoading();
        Swal.fire('Error', err.message, 'error');
    }
}

function descargarErroresImportacion() {
    const errores = previewImportacion.filter(r => r.estado === 'ERROR');
    if (!errores.length) return;

    const ws = XLSX.utils.aoa_to_sheet([
        ['FILA', 'COD_ARTICU', 'CATEGORIA', 'CATEGORIA_PADRE', 'RUBRO', 'CATEGORIA_ACTUAL', 'ERROR'],
        ...errores.map(r => [r.fila, r.COD_ARTICU, r.CATEGORIA_EXCEL, r.CATEGORIA_PADRE_EXCEL, r.RUBRO, r.CATEGORIA_ACTUAL, r.mensaje])
    ]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Errores');
    XLSX.writeFile(wb, 'ErroresReasignacionCategorias.xlsx');
}

document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('archivoImportacion');
    if (input) {
        input.addEventListener('change', e => leerExcelImportacion(e.target.files[0]));
    }

    const modal = document.getElementById('modalImportacion');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) cerrarModalImportacion();
        });
    }
});
