<?php
    require_once "Class/categoria.php";

    $categoria = new Categoria();
    $rubros = $categoria->traerRubros();

    $datosRubro = (isset($_GET['rubros'])) ? $_GET['rubros'] : "A-ACCESORIOS" ;

    $siglaRubro = explode("-", $datosRubro)[0];
    $descRubro = explode("-", $datosRubro)[1];

    $data = $categoria->traerVistaRubroCategoriaCodificacion($siglaRubro);
    $ultimo = count($data) - 1;
    
    $ultimoCodigo = 0;
    
    if($ultimo >= 0){
        $ultimoCodigo = $data[$ultimo]['CATEGORIA'];
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">
    
    <!-- Modern CSS Framework -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#6b7280',
                        accent: '#f59e0b',
                        success: '#10b981',
                        danger: '#ef4444',
                        dark: '#1f2937'
                    }
                }
            }
        }
    </script>
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <!-- Bootstrap for tooltips -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/gestionCategoriaProductos.css">

</head>

<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen">
    <!-- Main Container -->
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8 animate-slide-in">
            <h1 class="text-4xl font-bold text-gray-800 mb-2 flex items-center justify-center gap-3">
                <i class="fas fa-cogs text-blue-600"></i>
                Gestión de Categorías
            </h1>
        </div>

        <!-- Main Card -->
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-2xl p-8 shadow-lg border animate-fade-in">
                
                <!-- Filter Section -->
                <div class="mb-8">
                    <div class="bg-gray-50 rounded-xl p-6 border">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-filter text-blue-600"></i>
                            Filtro por Rubro
                        </h3>
                        
                        <form action="" method="GET" id="rubroForm">
                            <div class="flex flex-wrap gap-4 items-end">
                                <div class="flex-1 min-w-64">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Seleccionar Rubro
                                    </label>
                                    <select name="rubros" id="rubros" class="w-full px-4 py-3 rounded-lg bg-white border border-gray-300 text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        <?php 
                                            foreach ($rubros as $key => $value) {
                                        ?>
                                            <option value="<?= $value['SIGLA'] ?>-<?= $value['DESC_RUBRO'] ?>" <?php if( $descRubro == $value['DESC_RUBRO']){ echo "selected" ; }?> >
                                                <?= $value['DESC_RUBRO'] ?>
                                            </option>
                                        <?php 
                                            }
                                        ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 hover-lift flex items-center gap-2">
                                    <i class="fas fa-search"></i>
                                    Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Add New Rubro/Category Section -->
                <div class="mb-8">
                    <div class="bg-gray-50 rounded-xl p-6 border">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-plus-circle text-green-600"></i>
                            Agregar Nuevo Rubro/Categoría
                        </h3>
                        
                        <div class="flex flex-wrap gap-4">
                            <button onclick="abrirModalRubro()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 hover-lift flex items-center gap-2">
                                <i class="fas fa-plus"></i>
                                Agregar Nuevo Rubro
                            </button>
                            
                            <button onclick="abrirModalCategoria()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 hover-lift flex items-center gap-2">
                                <i class="fas fa-plus"></i>
                                Agregar Nueva Categoría
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Categories Table -->
                <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-table text-purple-600"></i>
                            Categorías Existentes - Rubro: <span id="rubroActual" class="text-blue-600"><?= $descRubro ?></span>
                        </h3>
                    </div>
                    
                    <div class="p-6">
                        <!-- Table Container -->
                        <div class="overflow-x-auto custom-scrollbar">
                            <table id="tableData" class="w-full text-left display" style="width:100%">
                                <thead class="sticky top-0 bg-gray-100 backdrop-blur-sm">
                                    <tr class="border-b border-gray-200">
                                        <th class="px-6 py-4 text-gray-700 font-medium">
                                            <i class="fas fa-tag mr-2"></i>Rubro
                                        </th>
                                        <th class="px-6 py-4 text-gray-700 font-medium">
                                            <i class="fas fa-list mr-2"></i>Desc. Rubro
                                        </th>
                                        <th class="px-6 py-4 text-gray-700 font-medium">
                                            <i class="fas fa-code mr-2"></i>Categoría
                                        </th>
                                        <th class="px-6 py-4 text-gray-700 font-medium">
                                            <i class="fas fa-info-circle mr-2"></i>Desc. Categoría
                                        </th>
                                        <th class="px-6 py-4 text-gray-700 font-medium">
                                            <i class="fas fa-folder mr-2"></i>Desc. Categoría Padre
                                        </th>
                                        <th class="px-6 py-4 text-gray-700 font-medium text-center">
                                            <i class="fas fa-cog mr-2"></i>Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody" class="divide-y divide-gray-200">
                                    <?php 
                                        foreach ($data as $key => $value) {
                                    ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 text-gray-800"><?= $value['RUBRO'] ?></td>
                                            <td class="px-6 py-4 text-gray-600"><?= $value['DESC_RUBRO'] ?></td>
                                            <td class="px-6 py-4 text-gray-800 font-mono"><?= $value['CATEGORIA'] ?></td>
                                            <td class="px-6 py-4 text-gray-600 editable-cell"><?= $value['DESC_CATEGORIA'] ?></td>
                                            <td class="px-6 py-4 text-gray-600 editable-cell"><?= $value['DESC_CATEGORIA_PADRE'] ?? '' ?></td>
                                            <td class="px-6 py-4 text-center">
                                                <button onclick="editar(this)" 
                                                        class="px-3 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-all duration-200 hover-lift"
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Editar categoría">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php    
                                        }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Stats Footer -->
                <div class="mt-8 grid md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl p-6 border text-center shadow-sm stats-card">
                        <div class="text-3xl font-bold text-blue-600 mb-2" id="totalCategorias"><?= count($data) ?></div>
                        <div class="text-gray-600">Total Categorías</div>
                    </div>
                    <div class="bg-white rounded-xl p-6 border text-center shadow-sm stats-card">
                        <div class="text-3xl font-bold text-green-600 mb-2" id="ultimoCodigo"><?= str_pad($ultimoCodigo, 2, '0', STR_PAD_LEFT) ?></div>
                        <div class="text-gray-600">Último Código</div>
                    </div>
                    <div class="bg-white rounded-xl p-6 border text-center shadow-sm stats-card">
                        <div class="text-3xl font-bold text-purple-600 mb-2" id="rubroActivo"><?= $siglaRubro ?></div>
                        <div class="text-gray-600">Rubro Activo</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Categoría -->
    <div id="modalCategoria" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-8 w-full max-w-md mx-4 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-blue-600"></i>
                    Agregar Nueva Categoría
                </h3>
                <button onclick="cerrarModalCategoria()" class="text-gray-400 hover:text-gray-600 hover:scale-110 transition-all">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Código Categoría <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="codCategoria" min="1" max="100" 
                        class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="01-100" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="descCategoria" 
                        class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Descripción de la categoría" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción Categoría Padre
                    </label>
                    <input type="text" id="descCategoriaPadre" 
                        class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Descripción de la categoría padre (opcional)">
                </div>
                
                <div class="text-xs text-gray-500 mt-2">
                    <span class="text-red-500">*</span> Campos obligatorios
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button onclick="cerrarModalCategoria()" class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </button>
                <button onclick="agregar('<?= $siglaRubro ?>')" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 hover-lift">
                    <i class="fas fa-plus mr-2"></i>
                    Agregar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Rubro -->
    <div id="modalRubro" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-8 w-full max-w-md mx-4 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-green-600"></i>
                    Agregar Nuevo Rubro
                </h3>
                <button onclick="cerrarModalRubro()" class="text-gray-400 hover:text-gray-600 hover:scale-110 transition-all">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Sigla del Rubro <span class="text-red-500">*</span>
                    </label>
                    <select id="siglaRubroModal" class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                        <option value="">Seleccionar sigla...</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción del Rubro <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="descRubro" 
                        class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Descripción del rubro" required>
                </div>
                
                <div class="text-xs text-gray-500 mt-2">
                    <span class="text-red-500">*</span> Campos obligatorios
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button onclick="cerrarModalRubro()" class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </button>
                <button onclick="agregarRubro()" class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 hover-lift">
                    <i class="fas fa-plus mr-2"></i>
                    Agregar
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center hidden">
        <div class="bg-white backdrop-blur-md rounded-xl p-8 text-center shadow-2xl">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
            <div class="text-lg font-medium text-gray-800">Procesando...</div>
            <div class="text-sm text-gray-600 mt-2">Por favor espere</div>
        </div>
    </div>

    <!-- Success Toast Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Hidden session data for JS -->
    <div id="username" style="display: none;"><?= $_SESSION['username'] ?? '' ?></div>
    <div id="siglaRubro" style="display: none;"><?= $siglaRubro ?></div>

    <!-- Custom JavaScript -->
    <script src="js/gestionCategoriaProductos.js"></script>

    <!-- Additional JavaScript for enhanced UX -->
    <script>
        // Enhanced DataTable initialization
        $(document).ready(function() {
            $('#tableData').DataTable({
                "bInfo": true,
                "aaSorting": [[2, "asc"]], // Sort by category code
                'columnDefs': [
                    {
                        "targets": [0, 2], // Rubro, Categoria
                        "className": "text-center",
                    },
                    {
                        "targets": [1, 3, 4], // Descriptions
                        "className": "text-left",
                    },
                    {
                        "targets": [5], // Actions
                        "className": "text-center",
                        "orderable": false
                    }
                ],
                "oLanguage": {
                    "sSearch": "Búsqueda rápida:",
                    "sSearchPlaceholder": "Buscar en cualquier campo...",
                    "sLengthMenu": "Mostrar _MENU_ registros por página",
                    "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "sInfoFiltered": "(filtrado de _MAX_ registros totales)",
                    "sZeroRecords": "No se encontraron registros coincidentes",
                    "oPaginate": {
                        "sFirst": "Primero",
                        "sLast": "Último",
                        "sNext": "Siguiente",
                        "sPrevious": "Anterior"
                    }
                },
                "pageLength": 25,
                "responsive": true,
                "autoWidth": false,
                "dom": '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                       '<"row"<"col-sm-12"tr>>' +
                       '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Enhanced form validation
        function validateCategoryForm() {
            const codCategoria = document.getElementById('codCategoria').value;
            const descCategoria = document.getElementById('descCategoria').value;
            
            if (!codCategoria || !descCategoria.trim()) {
                return false;
            }
            
            if (codCategoria < 1 || codCategoria > 100) {
                return false;
            }
            
            return true;
        }

        function validateRubroForm() {
            const sigla = document.getElementById('siglaRubroModal').value;
            const descripcion = document.getElementById('descRubro').value;
            
            return sigla && descripcion.trim();
        }

        // Enhanced error handling
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.getElementById('toastContainer');
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
    </script>

</body>
</html>