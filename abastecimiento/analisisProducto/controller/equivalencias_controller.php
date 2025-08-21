
<?php

// Limpiar completamente cualquier output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Configurar manejo de errores para evitar HTML en respuestas JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Establecer header JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar que no haya output previo
    if (ob_get_length()) {
        ob_clean();
    }
    
    require_once '../Class/Articulo.php';

    $accion = $_REQUEST['accion'] ?? '';

    switch ($accion) {
        case 'listar_grupos':
            listarGrupos();
            break;
            
        case 'buscar_grupos':
            buscarGrupos();
            break;
            
        case 'buscar_articulos':
            buscarArticulos();
            break;

        case 'obtener_rubros':
            obtenerRubros();
            break;
        
        case 'obtener_categorias':
            obtenerCategorias();
            break;

        case 'obtener_categorias_por_rubro':
            obtenerCategoriasPorRubro();
            break;

        case 'obtener_grupo':
            obtenerGrupo();
            break;
            
        case 'crear':
            crearGrupo();
            break;
            
        case 'editar':
            editarGrupo();
            break;
            
        case 'eliminar_grupo':
            eliminarGrupo();
            break;
            
        case 'test':
            // Acción de test para diagnóstico
            echo json_encode([
                'success' => true,
                'message' => 'Controller funcionando correctamente',
                'timestamp' => date('Y-m-d H:i:s'),
                'session_status' => session_status(),
                'php_version' => phpversion()
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception("Acción no válida: $accion");
    }

} catch (Exception $e) {
    // Limpiar cualquier output previo
    if (ob_get_length()) {
        ob_clean();
    }
    
    error_log("Error en equivalencias_controller.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    // Capturar errores fatales de PHP
    if (ob_get_length()) {
        ob_clean();
    }
    
    error_log("Error fatal en equivalencias_controller.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
} finally {
    // Limpiar buffer al final
    if (ob_get_length()) {
        ob_end_clean();
    }
}

function listarGrupos() {
    try {
        $articulo = new Articulo();
        $grupos = $articulo->listarGruposEquivalencias();
        
        // Formatear fechas para JSON con hora completa
        if (is_array($grupos)) {
            foreach ($grupos as &$grupo) {
                // Fecha de creación
                if (isset($grupo['FECHA_CREACION']) && $grupo['FECHA_CREACION'] instanceof DateTime) {
                    $grupo['FECHA_CREACION'] = $grupo['FECHA_CREACION']->format('Y-m-d H:i:s');
                }
                
                // Fecha de modificación
                if (isset($grupo['FECHA_MODIFICACION']) && $grupo['FECHA_MODIFICACION'] instanceof DateTime) {
                    $grupo['FECHA_MODIFICACION'] = $grupo['FECHA_MODIFICACION']->format('Y-m-d H:i:s');
                }
                
                // Si las fechas vienen como string, asegurar formato correcto
                if (isset($grupo['FECHA_CREACION']) && is_string($grupo['FECHA_CREACION'])) {
                    $fecha = new DateTime($grupo['FECHA_CREACION']);
                    $grupo['FECHA_CREACION'] = $fecha->format('Y-m-d H:i:s');
                }
                
                if (isset($grupo['FECHA_MODIFICACION']) && is_string($grupo['FECHA_MODIFICACION'])) {
                    $fecha = new DateTime($grupo['FECHA_MODIFICACION']);
                    $grupo['FECHA_MODIFICACION'] = $fecha->format('Y-m-d H:i:s');
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $grupos,
            'count' => count($grupos)
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Error al listar grupos: " . $e->getMessage());
    }
}

/**
 * Buscar grupos con filtros
 */
function buscarGrupos() {
    try {
        $filtroArticulo = $_GET['articulo'] ?? '';
        $filtroGrupo = $_GET['grupo'] ?? '';
        $filtroRubro = $_GET['rubro'] ?? '';
        $filtroEstado = $_GET['estado'] ?? '';
        
        $articulo = new Articulo();
        $grupos = $articulo->buscarGruposEquivalencias($filtroArticulo, $filtroGrupo, $filtroRubro, $filtroEstado);
        
        // Formatear fechas para JSON con hora completa
        if (is_array($grupos)) {
            foreach ($grupos as &$grupo) {
                // Fecha de creación
                if (isset($grupo['FECHA_CREACION']) && $grupo['FECHA_CREACION'] instanceof DateTime) {
                    $grupo['FECHA_CREACION'] = $grupo['FECHA_CREACION']->format('Y-m-d H:i:s');
                }
                
                // Fecha de modificación
                if (isset($grupo['FECHA_MODIFICACION']) && $grupo['FECHA_MODIFICACION'] instanceof DateTime) {
                    $grupo['FECHA_MODIFICACION'] = $grupo['FECHA_MODIFICACION']->format('Y-m-d H:i:s');
                }
                
                // Si las fechas vienen como string, asegurar formato correcto
                if (isset($grupo['FECHA_CREACION']) && is_string($grupo['FECHA_CREACION'])) {
                    $fecha = new DateTime($grupo['FECHA_CREACION']);
                    $grupo['FECHA_CREACION'] = $fecha->format('Y-m-d H:i:s');
                }
                
                if (isset($grupo['FECHA_MODIFICACION']) && is_string($grupo['FECHA_MODIFICACION'])) {
                    $fecha = new DateTime($grupo['FECHA_MODIFICACION']);
                    $grupo['FECHA_MODIFICACION'] = $fecha->format('Y-m-d H:i:s');
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $grupos,
            'count' => count($grupos)
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Error al buscar grupos: " . $e->getMessage());
    }
}

/**
 * Buscar artículos para Select2 - CON DEBUG
 */
function buscarArticulos() {
    try {
        $termino = $_GET['termino'] ?? '';
        $page = intval($_GET['page'] ?? 1);
        $pageSize = 20;
        
        error_log("=== buscarArticulos === Término: '$termino', Página: $page");
        
        if (strlen($termino) < 2) {
            error_log("Término muy corto, devolviendo array vacío");
            echo json_encode([
                'success' => true,
                'data' => [],
                'pagination' => ['more' => false],
                'message' => 'Término muy corto'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $articulo = new Articulo();
        
        // Verificar si el método existe
        if (!method_exists($articulo, 'buscarArticulosSelect2')) {
            error_log("Método buscarArticulosSelect2 NO existe, usando fallback");
            
            // Fallback usando método existente
            $resultado = $articulo->traerArticulos('%', '%');
            error_log("Fallback - Total artículos: " . count($resultado));
            
            // Filtrar por término de búsqueda
            $articulosFiltrados = array_filter($resultado, function($art) use ($termino) {
                return stripos($art['COD_ARTICU'], $termino) !== false || 
                       stripos($art['DESCRIPCION'], $termino) !== false;
            });
            
            error_log("Fallback - Artículos filtrados: " . count($articulosFiltrados));
            
            // Formatear para Select2
            $articulosFormateados = array_map(function($art) {
                return [
                    'id' => $art['COD_ARTICU'],
                    'text' => $art['COD_ARTICU'] . ' - ' . $art['DESCRIPCION'],
                    'descripcion' => $art['DESCRIPCION'],
                    'color' => ''
                ];
            }, array_slice($articulosFiltrados, 0, $pageSize));
            
            echo json_encode([
                'success' => true,
                'data' => array_values($articulosFormateados),
                'pagination' => ['more' => false],
                'method' => 'fallback'
            ], JSON_UNESCAPED_UNICODE);
            
        } else {
            error_log("Método buscarArticulosSelect2 existe, usándolo");
            
            $resultado = $articulo->buscarArticulosSelect2($termino, $page, $pageSize);
            
            error_log("Resultado método directo: " . json_encode($resultado));
            
            echo json_encode([
                'success' => true,
                'data' => $resultado['articulos'],
                'pagination' => ['more' => $resultado['hasMore']],
                'method' => 'directo',
                'debug' => isset($resultado['error']) ? $resultado['error'] : null
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        error_log("Error en buscarArticulos: " . $e->getMessage());
        throw new Exception("Error al buscar artículos: " . $e->getMessage());
    }
}

/**
 * Obtener datos de un grupo específico
 */
function obtenerGrupo() {
    try {
        $codEquivalencia = intval($_GET['cod_equivalencia'] ?? 0);
        
        if ($codEquivalencia <= 0) {
            throw new Exception("Código de equivalencia inválido");
        }
        
        $articulo = new Articulo();
        
        // Verificar si el método existe
        if (!method_exists($articulo, 'obtenerGrupoCompleto')) {
            throw new Exception("Método obtenerGrupoCompleto no implementado");
        }
        
        $grupo = $articulo->obtenerGrupoCompleto($codEquivalencia);
        
        // Formatear fechas para JSON
        if (isset($grupo['FECHA_CREACION']) && $grupo['FECHA_CREACION'] instanceof DateTime) {
            $grupo['FECHA_CREACION'] = $grupo['FECHA_CREACION']->format('Y-m-d H:i:s');
        }
        if (isset($grupo['FECHA_MODIFICACION']) && $grupo['FECHA_MODIFICACION'] instanceof DateTime) {
            $grupo['FECHA_MODIFICACION'] = $grupo['FECHA_MODIFICACION']->format('Y-m-d H:i:s');
        }
        
        echo json_encode([
            'success' => true,
            'data' => $grupo
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Error al obtener grupo: " . $e->getMessage());
    }
}

/**
 * Crear nuevo grupo de equivalencias
 */
function crearGrupo() {
    try {
        $descripcionGrupo = trim($_POST['descripcion_grupo'] ?? '');
        $rubro = trim($_POST['rubro'] ?? '');
        $estado = intval($_POST['estado'] ?? 1);
        $articulos = $_POST['articulos'] ?? [];
        
        if (empty($descripcionGrupo)) {
            throw new Exception("La descripción del grupo es obligatoria");
        }
        
        if (empty($rubro)) {
            throw new Exception("El rubro es obligatorio");
        }
        
        if (empty($articulos) || !is_array($articulos)) {
            throw new Exception("Debe agregar al menos un artículo al grupo");
        }
        
        $articuloObj = new Articulo();
        
        if (!method_exists($articuloObj, 'crearGrupoEquivalencias')) {
            throw new Exception("Método crearGrupoEquivalencias no implementado");
        }
        
        $resultado = $articuloObj->crearGrupoEquivalencias($descripcionGrupo, $rubro, $estado, $articulos);
        
        echo json_encode([
            'success' => true,
            'message' => 'Grupo creado correctamente',
            'cod_equivalencia' => $resultado
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Error al crear grupo: " . $e->getMessage());
    }
}

/**
 * Editar grupo de equivalencias
 */
function editarGrupo() {
    try {
        $codEquivalencia = intval($_POST['cod_equivalencia'] ?? 0);
        $descripcionGrupo = trim($_POST['descripcion_grupo'] ?? '');
        $rubro = trim($_POST['rubro'] ?? '');
        $estado = intval($_POST['estado'] ?? 1);
        $articulos = $_POST['articulos'] ?? [];
        
        if ($codEquivalencia <= 0) {
            throw new Exception("Código de equivalencia inválido");
        }
        
        if (empty($descripcionGrupo)) {
            throw new Exception("La descripción del grupo es obligatoria");
        }
        
        if (empty($rubro)) {
            throw new Exception("El rubro es obligatorio");
        }
        
        if (empty($articulos) || !is_array($articulos)) {
            throw new Exception("Debe agregar al menos un artículo al grupo");
        }
        
        $articuloObj = new Articulo();
        
        if (!method_exists($articuloObj, 'editarGrupoEquivalencias')) {
            throw new Exception("Método editarGrupoEquivalencias no implementado");
        }
        
        $articuloObj->editarGrupoEquivalencias($codEquivalencia, $descripcionGrupo, $rubro, $estado, $articulos);
        
        echo json_encode([
            'success' => true,
            'message' => 'Grupo actualizado correctamente'
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Error al editar grupo: " . $e->getMessage());
    }
}

/**
 * Eliminar grupo de equivalencias
 */
function eliminarGrupo() {
    try {
        $codEquivalencia = intval($_POST['cod_equivalencia'] ?? 0);
        
        if ($codEquivalencia <= 0) {
            throw new Exception("Código de equivalencia inválido");
        }
        
        $articuloObj = new Articulo();
        
        // Verificar si el método existe
        if (!method_exists($articuloObj, 'eliminarGrupoEquivalencias')) {
            throw new Exception("Método eliminarGrupoEquivalencias no implementado");
        }
        
        $articuloObj->eliminarGrupoEquivalencias($codEquivalencia);
        
        echo json_encode([
            'success' => true,
            'message' => 'Grupo eliminado correctamente'
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Error al eliminar grupo: " . $e->getMessage());
    }
}

function obtenerRubros() {
    try {
        $articulo = new Articulo();
        $rubros = $articulo->obtenerRubrosEquivalencias();
        
        echo json_encode([
            'success' => true,
            'data' => $rubros
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Error al obtener rubros: " . $e->getMessage());
    }
}

function obtenerCategorias() {
    try {
        $articulo = new Articulo();
        $categorias = $articulo->obtenerCategorias();
        
        echo json_encode([
            'success' => true,
            'data' => $categorias
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Error al obtener categorías: " . $e->getMessage());
    }
}

function obtenerCategoriasPorRubro() {
    try {
        error_log("=== Controller obtenerCategoriasPorRubro INICIADO ===");
        
        $rubros = isset($_GET['rubros']) && is_array($_GET['rubros']) ? $_GET['rubros'] : [];
        
        error_log("Rubros recibidos en controller: " . json_encode($rubros));
        
        $articulo = new Articulo();
        
        // Verificar si el método existe
        if (!method_exists($articulo, 'obtenerCategoriasPorRubro')) {
            error_log("ERROR: Método obtenerCategoriasPorRubro NO existe en clase Articulo");
            throw new Exception("Método obtenerCategoriasPorRubro no implementado");
        }
        
        $categorias = $articulo->obtenerCategoriasPorRubro($rubros);
        
        error_log("Categorías filtradas obtenidas: " . count($categorias));
        
        echo json_encode([
            'success' => true,
            'data' => $categorias,
            'count' => count($categorias)
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log("ERROR en controller obtenerCategoriasPorRubro: " . $e->getMessage());
        throw new Exception("Error al obtener categorías por rubro: " . $e->getMessage());
    }
}

?>