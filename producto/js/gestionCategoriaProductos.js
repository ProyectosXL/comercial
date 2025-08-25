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