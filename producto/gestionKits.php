<?php
    require_once "Class/Kit.php";

    $kit = new Kit();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Kits</title>
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
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <!-- Bootstrap for tooltips -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/gestionKits.css">

</head>

<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen">
    <!-- Main Container -->
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8 animate-slide-in">
            <h1 class="text-4xl font-bold text-gray-800 mb-2 flex items-center justify-center gap-3">
                <i class="fas fa-box-open text-blue-600"></i>
                Gestión de Kits de Productos
            </h1>
            <p class="text-gray-600">Alta y mantenimiento de relaciones Kit ↔ Artículo</p>
        </div>

        <!-- Main Card -->
        <div class="max-w-7xl mx-auto">
            <!-- Tabs Navigation -->
            <div class="bg-white rounded-t-2xl border-b animate-fade-in">
                <div class="flex flex-wrap gap-2 p-4">
                    <button onclick="cambiarTab('listado')" id="btnTabListado" 
                            class="tab-button active px-6 py-3 rounded-lg font-medium transition-all">
                        <i class="fas fa-list mr-2"></i>
                        Listado / Administración
                    </button>
                    <button onclick="cambiarTab('alta')" id="btnTabAlta" 
                            class="tab-button px-6 py-3 rounded-lg font-medium transition-all">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Alta de Kit
                    </button>
                </div>
            </div>

            <!-- Tab Content Container -->
            <div class="bg-white rounded-b-2xl shadow-lg border-t-0 border">
                
                <!-- TAB 1: Listado / Administración -->
                <div id="tabListado" class="tab-content active p-8">
                    <div class="space-y-6">
                        <!-- Título y filtros -->
                        <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b">
                            <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-table text-purple-600"></i>
                                Kits Registrados
                            </h3>

                            <!-- Filtros -->
                            <div class="flex flex-wrap gap-3">
                                <input type="text" id="filtroKit" placeholder="Filtrar por Kit..."
                                       class="px-4 py-2 rounded-lg bg-gray-50 border border-gray-300 text-sm">
                                
                                <select id="filtroEstado" class="px-4 py-2 rounded-lg bg-gray-50 border border-gray-300 text-sm">
                                    <option value="">Todos los estados</option>
                                    <option value="0">Habilitado</option>
                                    <option value="1">Inhabilitado</option>
                                </select>

                                <button onclick="limpiarFiltros()" 
                                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm transition-all">
                                    <i class="fas fa-eraser mr-1"></i>
                                    Limpiar
                                </button>
                            </div>
                        </div>

                        <!-- DataTable -->
                        <div class="overflow-x-auto custom-scrollbar bg-white rounded-xl border">
                            <table id="tableKits" class="w-full display" style="width:100%">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left">ID</th>
                                        <th class="px-4 py-3 text-left">Kit</th>
                                        <th class="px-4 py-3 text-left">Artículo</th>
                                        <th class="px-4 py-3 text-left">Descripción</th>
                                        <th class="px-4 py-3 text-center">Múltiplo</th>
                                        <th class="px-4 py-3 text-center">Estado</th>
                                        <th class="px-4 py-3 text-center">Fecha Inhab.</th>
                                        <th class="px-4 py-3 text-center" style="min-width: 110px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Populated by DataTables -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Stats Footer -->
                        <div class="grid md:grid-cols-4 gap-6 mt-8">
                            <div class="bg-white rounded-xl p-6 border text-center shadow-sm stats-card">
                                <div class="text-3xl font-bold text-blue-600 mb-2" id="statTotal">0</div>
                                <div class="text-gray-600">Total Registros</div>
                            </div>
                            <div class="bg-white rounded-xl p-6 border text-center shadow-sm stats-card">
                                <div class="text-3xl font-bold text-green-600 mb-2" id="statHabilitados">0</div>
                                <div class="text-gray-600">Habilitados</div>
                            </div>
                            <div class="bg-white rounded-xl p-6 border text-center shadow-sm stats-card">
                                <div class="text-3xl font-bold text-red-600 mb-2" id="statInhabilitados">0</div>
                                <div class="text-gray-600">Inhabilitados</div>
                            </div>
                            <div class="bg-white rounded-xl p-6 border text-center shadow-sm stats-card">
                                <div class="text-3xl font-bold text-purple-600 mb-2" id="statKitsUnicos">0</div>
                                <div class="text-gray-600">Kits Únicos</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Alta de Kit -->
                <div id="tabAlta" class="tab-content hidden p-8">
                    <div class="space-y-6">
                        <!-- Título de sección -->
                        <div class="flex items-center gap-2 pb-4 border-b">
                            <i class="fas fa-box-open text-blue-600 text-xl"></i>
                            <h3 class="text-xl font-semibold text-gray-800">Agregar artículos al kit</h3>
                        </div>

                        <!-- Form de selección -->
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Select Kit -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Kit <span class="text-red-500">*</span>
                                </label>
                                <select id="selectKit" class="w-full" style="width: 100%">
                                    <option value="">Buscar kit...</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Buscar por código de kit</p>
                            </div>

                            <!-- Descripción del Kit -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Descripción del Kit
                                </label>
                                <div id="descripcionKit" class="w-full px-4 py-3 rounded-lg bg-blue-50 border border-blue-200 text-gray-700 min-h-[52px] flex items-center">
                                    <span class="text-gray-400 italic">Seleccione un kit para ver su descripción</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla temporal de artículos agregados -->
                        <div class="bg-gray-50 rounded-xl p-6 border">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-shopping-cart text-green-600"></i>
                                    Artículos del kit
                                </h4>
                                <div class="flex items-center gap-3">
                                    <span id="contadorArticulos" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                        0 artículos
                                    </span>
                                    <button onclick="limpiarKit()" id="btnLimpiar"
                                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all text-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                            disabled>
                                        <i class="fas fa-trash"></i>
                                        Limpiar Kit
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-x-auto custom-scrollbar">
                                <table id="tablaTemporal" class="w-full">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Kit</th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Artículo</th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Descripción</th>
                                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Múltiplo</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyTemporal" class="divide-y divide-gray-200">
                                        <tr id="rowEmpty">
                                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                                <i class="fas fa-inbox text-4xl mb-2 opacity-50"></i>
                                                <p>Seleccione un kit para ver sus artículos</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Botón Confirmar -->
                            <div class="flex justify-end mt-6">
                                <button onclick="confirmarKits()" id="btnConfirmar"
                                        class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 hover-lift flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled>
                                    <i class="fas fa-check-circle"></i>
                                    Confirmar y Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Modificar Kit -->
    <div id="modalModificarKit" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden animate-slide-in">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between text-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-edit text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">Modificar Kit</h3>
                        <p class="text-xs text-blue-100">ID de registro: <span id="modalIdBadge" class="font-mono font-bold bg-white/20 px-2 py-0.5 rounded">#</span></p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalModificar()" class="text-white/80 hover:text-white transition-colors p-2 rounded-lg hover:bg-white/10" title="Cerrar">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form id="formModificarKit" onsubmit="event.preventDefault(); guardarModificacionKit();" class="p-6 space-y-4">
                <input type="hidden" id="modalKitId" value="">

                <!-- Kit (COD_KIT) -->
                <div>
                    <label for="modalCodKit" class="block text-sm font-semibold text-gray-700 mb-1">
                        Código de Kit <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-box"></i>
                        </span>
                        <input type="text" id="modalCodKit" maxlength="15" required
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm font-mono uppercase focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
                               placeholder="Ej: HI VER 26-01B">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Identificador del kit (máximo 15 caracteres)</p>
                </div>

                <!-- Artículo (Select2) -->
                <div>
                    <label for="modalSelectArticulo" class="block text-sm font-semibold text-gray-700 mb-1">
                        Artículo <span class="text-red-500">*</span>
                    </label>
                    <select id="modalSelectArticulo" class="w-full" style="width: 100%">
                        <!-- Opciones cargadas dinámicamente -->
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Buscar artículo por código o descripción en STA11</p>
                </div>

                <!-- Descripción del Artículo -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Descripción del Artículo
                    </label>
                    <div id="modalDescripcioDisplay" class="w-full px-3 py-2.5 rounded-lg bg-gray-100 border border-gray-200 text-gray-700 text-sm flex items-center min-h-[42px]">
                        <span class="text-gray-400 italic">Sin descripción</span>
                    </div>
                </div>

                <!-- Grid: Múltiplo y Estado -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Múltiplo -->
                    <div>
                        <label for="modalMultiplo" class="block text-sm font-semibold text-gray-700 mb-1">
                            Múltiplo <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i class="fas fa-layer-group"></i>
                            </span>
                            <input type="number" id="modalMultiplo" min="1" step="1" required
                                   class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm font-semibold text-center focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
                                   placeholder="1">
                        </div>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label for="modalEstado" class="block text-sm font-semibold text-gray-700 mb-1">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <select id="modalEstado"
                                class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                            <option value="0">Habilitado</option>
                            <option value="1">Inhabilitado</option>
                        </select>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t mt-6">
                    <button type="button" onclick="cerrarModalModificar()"
                            class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 font-medium text-sm transition-all">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardarModificacion"
                            class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm flex items-center gap-2 shadow-md hover:shadow-lg transition-all">
                        <i class="fas fa-save"></i>
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
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

    <!-- Hidden session data for JS -->
    <div id="username" style="display: none;"><?= $_SESSION['username'] ?? '' ?></div>

    <!-- Custom JavaScript -->
    <script src="js/gestionKits.js"></script>

</body>
</html>