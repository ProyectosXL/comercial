
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
    <title>Consulta de Categorías</title>
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
                <i class="fas fa-search text-blue-600"></i>
                Consulta de Categorías
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
                            Filtro por Rubro - <?= $descRubro ?>
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

                <!-- Categories Table -->
                <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-table text-purple-600"></i>
                            Categorías de: <span class="text-blue-600"><?= $descRubro ?></span>
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
                                            <td class="px-6 py-4 text-gray-600"><?= $value['DESC_CATEGORIA'] ?></td>
                                            <td class="px-6 py-4 text-gray-600"><?= $value['DESC_CATEGORIA_PADRE'] ?? '' ?></td>
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
                    <div class="bg-white rounded-xl p-6 border text-center shadow-sm">
                        <div class="text-3xl font-bold text-blue-600 mb-2" id="totalCategorias"><?= count($data) ?></div>
                        <div class="text-gray-600">Total Categorías</div>
                    </div>
                    <div class="bg-white rounded-xl p-6 border text-center shadow-sm">
                        <div class="text-3xl font-bold text-green-600 mb-2" id="ultimoCodigo"><?= str_pad($ultimoCodigo, 2, '0', STR_PAD_LEFT) ?></div>
                        <div class="text-gray-600">Último Código</div>
                    </div>
                    <div class="bg-white rounded-xl p-6 border text-center shadow-sm">
                        <div class="text-3xl font-bold text-purple-600 mb-2" id="rubroActivo"><?= $siglaRubro ?></div>
                        <div class="text-gray-600">Rubro Activo</div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex justify-center gap-4">
                    <button onclick="exportarDatos()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 hover-lift flex items-center gap-2">
                        <i class="fas fa-download"></i>
                        Exportar Datos
                    </button>
                    <button onclick="imprimirTabla()" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-200 hover-lift flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden session data for JS -->
    <div id="username" style="display: none;"><?= $_SESSION['username'] ?? '' ?></div>
    <div id="siglaRubro" style="display: none;"><?= $siglaRubro ?></div>

    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function() {
            // Initialize DataTable
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
                    }
                ],
                "oLanguage": {
                    "sSearch": "Búsqueda rápida:",
                    "sSearchPlaceholder": "sobre cualquier campo",
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
                "pageLength": 50,
                "responsive": true,
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                       '<"row"<"col-sm-12"tr>>' +
                       '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });

            // Add custom styling to DataTables
            addDataTableStyles();
        });

        // Add custom styles to DataTables
        function addDataTableStyles() {
            const style = document.createElement('style');
            style.textContent = `
                .dataTables_wrapper {
                    color: #374151 !important;
                }
                
                .dataTables_wrapper .dataTables_length select,
                .dataTables_wrapper .dataTables_filter input {
                    background: #f9fafb !important;
                    border: 1px solid #d1d5db !important;
                    color: #374151 !important;
                    border-radius: 0.5rem !important;
                    padding: 0.5rem !important;
                }
                
                .dataTables_wrapper .dataTables_length,
                .dataTables_wrapper .dataTables_filter,
                .dataTables_wrapper .dataTables_info,
                .dataTables_wrapper .dataTables_paginate {
                    color: #6b7280 !important;
                }
                
                .dataTables_wrapper .dataTables_paginate .paginate_button {
                    background: #f9fafb !important;
                    border: 1px solid #d1d5db !important;
                    color: #374151 !important;
                    border-radius: 0.375rem !important;
                    margin: 0 2px !important;
                }
                
                .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                    background: #3b82f6 !important;
                    border-color: #3b82f6 !important;
                    color: white !important;
                }
                
                .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                    background: #3b82f6 !important;
                    border-color: #3b82f6 !important;
                    color: white !important;
                }
            `;
            document.head.appendChild(style);
        }

        // Export data function - Updated to include DESC_CATEGORIA_PADRE
        function exportarDatos() {
            const table = $('#tableData').DataTable();
            const data = table.rows().data();
            
            let csvContent = "Rubro,Descripción Rubro,Categoría,Descripción Categoría,Descripción Categoría Padre\n";
            
            for (let i = 0; i < data.length; i++) {
                const row = data[i];
                csvContent += `"${row[0]}","${row[1]}","${row[2]}","${row[3]}","${row[4] || ''}"\n`;
            }
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `categorias_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Print table function
        function imprimirTabla() {
            const printWindow = window.open('', '_blank');
            const tableHTML = document.getElementById('tableData').outerHTML;
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Consulta de Categorías</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; font-weight: bold; }
                        .header { text-align: center; margin-bottom: 20px; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>Consulta de Categorías</h2>
                        <p>Fecha: ${new Date().toLocaleDateString()}</p>
                    </div>
                    ${tableHTML}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        }
    </script>

</body>
</html>