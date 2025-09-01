<?php
// Configurar manejo de errores para JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Función para manejar errores fatales
if (!function_exists('handleFatalError')) {
    function handleFatalError() {
        $error = error_get_last();
        if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error_type' => 'fatal_error'
            ]);
            exit;
        }
    }
    
    register_shutdown_function('handleFatalError');
}

// Configurar el content type solo si no se han enviado headers
if (!headers_sent()) {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../class/FranquiciasSQLServer.php';
require_once __DIR__ . '/../class/AuthFranquicia.php';

if (!class_exists('FranquiciaStockController')) {
    class FranquiciaStockController {
    private $franquicias;
    private $cid; // SQL Server connection for rubros/categorias
    private $sistemas_conn; // Conexión específica a base sistemas
    
    public function __construct() {
        try {
            // Intentar crear FranquiciasSQLServer, pero no fallar si hay error de .env
            try {
                $this->franquicias = new FranquiciasSQLServer();
            } catch (Exception $e) {
                error_log("Advertencia: No se pudo inicializar FranquiciasSQLServer (problema .env): " . $e->getMessage());
                $this->franquicias = null;
            }
            
            // Intentar conexión central, pero no fallar si hay error de .env
            try {
                require_once __DIR__ . '/../../class/Conexion.php';
                $conexion = new Conexion();
                $this->cid = $conexion->conectar('central');
                
                if (!$this->cid) {
                    error_log("Advertencia: No se pudo conectar a SQL Server central - usando datos de prueba");
                    $this->cid = null;
                }
            } catch (Exception $e) {
                error_log("Advertencia: No se pudo inicializar Conexion (problema .env): " . $e->getMessage());
                $this->cid = null;
            }
            
            // Usar DatabaseSQLServer que ya tiene la configuración correcta para sistemas
            error_log("DEBUG: Intentando conectar a sistemas usando DatabaseSQLServer");
            require_once __DIR__ . '/../class/DatabaseSQLServer.php';
            try {
                $dbSistemas = DatabaseSQLServer::getInstance();
                $this->sistemas_conn = $dbSistemas->getConnection('sistemas');
                error_log("DEBUG: Conexión a sistemas exitosa usando DatabaseSQLServer");
            } catch (Exception $e) {
                error_log("DEBUG: Error conectando a sistemas con DatabaseSQLServer: " . $e->getMessage());
                $this->sistemas_conn = null;
            }
            
        } catch (Exception $e) {
            error_log("Error grave en constructor: " . $e->getMessage());
            $this->cid = null;
            $this->sistemas_conn = null;
        }
    }
    
    /**
     * Procesar peticiones AJAX
     */
    public function procesarPeticion() {
        try {
            $accion = $_POST['accion'] ?? $_GET['accion'] ?? null;
            
            if (!$accion) {
                throw new Exception('Acción no especificada');
            }
            
            // Verificar autenticación para todas las acciones excepto login
            if (!AuthFranquicia::verificarSesion()) {
                return [
                    'success' => false,
                    'message' => 'No autorizado - falta franquicia_id en sesión',
                    'error_type' => 'unauthorized',
                    'redirect' => 'login.php'
                ];
            }
            
            // Obtener franquicia_id de la sesión
            $franquicia_id_sesion = AuthFranquicia::obtenerFranquiciaIdSesion();
            
            switch ($accion) {
                case 'obtener_rubros':
                    return $this->obtenerRubros();
                
                case 'obtener_categorias':
                    $rubro = $_POST['rubro'] ?? $_GET['rubro'] ?? null;
                    if (!$rubro) {
                        throw new Exception('Rubro no especificado');
                    }
                    return $this->obtenerCategoriasPorRubro($rubro);
                
                case 'obtener_resumen_stock':
                    // Usar franquicia_id de la sesión en lugar del parámetro
                    return $this->obtenerResumenStock($franquicia_id_sesion);
                
                case 'registrar_movimiento':
                    return $this->registrarMovimiento($franquicia_id_sesion);
                
                case 'obtener_historial':
                    // Usar franquicia_id de la sesión
                    $limite = $_POST['limite'] ?? $_GET['limite'] ?? 100;
                    return $this->obtenerHistorialStock($franquicia_id_sesion, $limite);
                
                case 'editar_stock':
                    return $this->editarStock($franquicia_id_sesion);
                case 'buscar_articulos':
                    return $this->buscarArticulos();
                default:
                    throw new Exception('Acción no válida: ' . $accion);
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'application_error'
            ];
        }
    }
    
    /**
     * Obtener rubros disponibles
     */
    public function obtenerRubros() {
        try {
            error_log("DEBUG: obtenerRubros llamado");
            error_log("DEBUG: sistemas_conn status: " . ($this->sistemas_conn ? 'conectado' : 'no conectado'));
            
            if ($this->sistemas_conn) {
                // Obtener de SQL Server sistemas - usando la query específica
                $query = "SELECT DISTINCT RUBRO 
                         FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                         WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                         ORDER BY RUBRO";
                
                error_log("DEBUG: Ejecutando query rubros");
                $stmt = sqlsrv_query($this->sistemas_conn, $query);
                
                $rubros = [];
                if ($stmt) {
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $rubros[] = ['RUBRO' => $row['RUBRO']];
                    }
                    sqlsrv_free_stmt($stmt);
                    error_log("DEBUG: Encontrados " . count($rubros) . " rubros desde BD");
                } else {
                    error_log("DEBUG: Error en query rubros: " . print_r(sqlsrv_errors(), true));
                }
                
                if (!empty($rubros)) {
                    return [
                        'success' => true,
                        'rubros' => $rubros,
                        'source' => 'sistemas_database',
                        'count' => count($rubros)
                    ];
                }
            } else {
                error_log("DEBUG: No hay conexión a sistemas para rubros, usando datos de prueba");
            }
            
            // Datos de prueba si no hay conexión
            $rubros_prueba = [
                ['RUBRO' => 'CALZADO'],
                ['RUBRO' => 'INDUMENTARIA'],
                ['RUBRO' => 'ACCESORIOS'],
                ['RUBRO' => 'MARROQUINERIA'],
                ['RUBRO' => 'DEPORTES']
            ];
            
            return [
                'success' => true,
                'rubros' => $rubros_prueba,
                'source' => 'test_data',
                'count' => count($rubros_prueba)
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener rubros: " . $e->getMessage());
            throw new Exception("Error al obtener rubros");
        }
    }
    
    /**
     * Obtener categorías por rubro
     */
    public function obtenerCategoriasPorRubro($rubro) {
        try {
            error_log("DEBUG: obtenerCategoriasPorRubro llamado con rubro: $rubro");
            error_log("DEBUG: sistemas_conn status: " . ($this->sistemas_conn ? 'conectado' : 'no conectado'));
            
            if ($this->sistemas_conn) {
                // Obtener de SQL Server sistemas - usando la query específica
                $query = "SELECT DISTINCT CATEGORIA_PADRE 
                         FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                         WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                         AND RUBRO = ?
                         ORDER BY CATEGORIA_PADRE";
                
                error_log("DEBUG: Ejecutando query con rubro: $rubro");
                $stmt = sqlsrv_prepare($this->sistemas_conn, $query, [$rubro]);
                
                $categorias = [];
                if ($stmt && sqlsrv_execute($stmt)) {
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $categorias[] = ['nombre' => $row['CATEGORIA_PADRE']];
                    }
                    sqlsrv_free_stmt($stmt);
                    error_log("DEBUG: Encontradas " . count($categorias) . " categorías desde BD");
                } else {
                    error_log("DEBUG: Error en query: " . print_r(sqlsrv_errors(), true));
                }
                
                if (!empty($categorias)) {
                    return [
                        'success' => true,
                        'categorias' => $categorias,
                        'source' => 'sistemas_database',
                        'rubro' => $rubro,
                        'count' => count($categorias)
                    ];
                }
            } else {
                error_log("DEBUG: No hay conexión a sistemas, usando datos de prueba");
            }
            
            // Datos de prueba si no hay conexión
            $categorias_prueba = [
                ['nombre' => 'Categoria A - ' . $rubro],
                ['nombre' => 'Categoria B - ' . $rubro],
                ['nombre' => 'Categoria C - ' . $rubro]
            ];
            
            return [
                'success' => true,
                'categorias' => $categorias_prueba,
                'source' => 'test_data',
                'rubro' => $rubro,
                'count' => count($categorias_prueba)
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener categorías: " . $e->getMessage());
            throw new Exception("Error al obtener categorías");
        }
    }
    
    /**
     * Obtener resumen de stock para una franquicia
     */
    public function obtenerResumenStock($franquicia_id) {
        try {
            // Si franquicias es null, usar método directo
            if (!$this->franquicias) {
                error_log("DEBUG: franquicias es null, usando método directo para resumen");
                return $this->obtenerResumenStockDirecto($franquicia_id);
            }
            
            $resumen = $this->franquicias->obtenerResumenStock($franquicia_id);
            
            // Transformar datos para que coincidan con lo que espera el frontend
            $resumenTransformado = $this->transformarResumenStock($resumen);
            
            return [
                'success' => true,
                'stock' => $resumenTransformado
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener resumen de stock: " . $e->getMessage());
            // Intentar método directo como fallback
            try {
                return $this->obtenerResumenStockDirecto($franquicia_id);
            } catch (Exception $e2) {
                throw new Exception("Error al obtener resumen de stock: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Obtener resumen de stock usando conexión directa
     */
    private function obtenerResumenStockDirecto($franquicia_id) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos sistemas');
            }
            
            $sql = "SELECT 
                        codigo_articulo,
                        descripcion_articulo,
                        stock_actual,
                        fecha_movimiento as ultima_actualizacion
                    FROM franquicia_historial_stock hs1
                    WHERE hs1.franquicia_id = ?
                    AND hs1.fecha_movimiento = (
                        SELECT MAX(hs2.fecha_movimiento) 
                        FROM franquicia_historial_stock hs2 
                        WHERE hs2.franquicia_id = hs1.franquicia_id 
                        AND hs2.codigo_articulo = hs1.codigo_articulo
                    )
                    ORDER BY codigo_articulo";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$franquicia_id]);
            $resumen = [];
            
            if ($stmt && sqlsrv_execute($stmt)) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $resumen[] = $row;
                }
                sqlsrv_free_stmt($stmt);
            } else {
                $errors = sqlsrv_errors();
                error_log("DEBUG: Error SQL en resumen: " . print_r($errors, true));
            }
            
            // Transformar datos
            $resumenTransformado = $this->transformarResumenStock($resumen);
            
            return [
                'success' => true,
                'stock' => $resumenTransformado
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerResumenStockDirecto: " . $e->getMessage());
            throw new Exception("Error al obtener resumen de stock: " . $e->getMessage());
        }
    }
    
    /**
     * Transformar datos de resumen para el frontend
     */
    private function transformarResumenStock($resumenDb) {
        $resumenTransformado = [];
        
        foreach ($resumenDb as $item) {
            // Extraer rubro y categoría del código_articulo (formato: RUBRO_CATEGORIA)
            $partes = explode('_', $item['codigo_articulo'], 2);
            $rubro = $partes[0] ?? 'Sin Rubro';
            $categoria = $partes[1] ?? $item['descripcion_articulo'] ?? 'Sin Categoría';
            
            $resumenTransformado[] = [
                'rubro' => $rubro,
                'categoria' => $categoria,
                'cantidad' => $item['stock_actual'] ?? 0,
                'codigo_articulo' => $item['codigo_articulo'],
                'descripcion_articulo' => $item['descripcion_articulo'] ?? '',
                'ultima_actualizacion' => $item['ultima_actualizacion'] ?? null
            ];
        }
        
        error_log("DEBUG: Resumen transformado: " . json_encode($resumenTransformado));
        return $resumenTransformado;
    }
    
    /**
     * Registrar movimiento de stock
     */
    public function registrarMovimiento($franquicia_id_sesion) {
        try {
            error_log("DEBUG: Iniciando registrarMovimiento para franquicia_id: " . $franquicia_id_sesion);
            error_log("DEBUG: Tipo de franquicia_id: " . gettype($franquicia_id_sesion));
            
            // Verificar si tenemos la instancia de franquicias
            if (!$this->franquicias) {
                error_log("DEBUG: franquicias es null, intentando usar DatabaseSQLServer directamente");
                return $this->registrarMovimientoDirecto($franquicia_id_sesion);
            }
            
            // Verificar si vienen datos en formato stock_data (del frontend) o individual
            if (isset($_POST['stock_data'])) {
                return $this->procesarStockData($franquicia_id_sesion);
            }
            
            $datos = [
                'franquicia_id' => $franquicia_id_sesion, // Usar el ID de la sesión
                'codigo_articulo' => $_POST['codigo_articulo'] ?? null,
                'descripcion_articulo' => $_POST['descripcion_articulo'] ?? '',
                'movimiento' => intval($_POST['movimiento'] ?? 0),
                'tipo_movimiento' => $_POST['tipo_movimiento'] ?? null,
                'observaciones' => $_POST['observaciones'] ?? '',
                'usuario' => $_SESSION['usuario_franquicia'] ?? 'sistema'
            ];
            
            error_log("DEBUG: Datos recibidos: " . json_encode($datos));
            
            // Validar datos requeridos
            if (!$datos['franquicia_id'] || !$datos['codigo_articulo'] || !$datos['tipo_movimiento']) {
                throw new Exception('Datos requeridos faltantes');
            }
            
            if ($datos['movimiento'] == 0) {
                throw new Exception('El movimiento no puede ser cero');
            }
            
            error_log("DEBUG: Llamando a registrarMovimientoStock");
            $resultado = $this->franquicias->registrarMovimientoStock($datos);
            error_log("DEBUG: Resultado registrarMovimientoStock: " . ($resultado ? 'true' : 'false'));
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Movimiento registrado correctamente'
                ];
            } else {
                throw new Exception('Error al registrar el movimiento');
            }
            
        } catch (Exception $e) {
            error_log("Error al registrar movimiento: " . $e->getMessage());
            throw new Exception("Error al registrar movimiento: " . $e->getMessage());
        }
    }
    
    /**
     * Procesar datos de stock desde el frontend (formato stock_data)
     */
    private function procesarStockData($franquicia_id_sesion) {
        try {
            error_log("DEBUG: Procesando stock_data del frontend");
            
            $stockDataJson = $_POST['stock_data'] ?? '';
            $periodo = $_POST['periodo'] ?? '';
            
            error_log("DEBUG: stock_data recibido: " . $stockDataJson);
            error_log("DEBUG: periodo recibido: " . $periodo);
            
            if (empty($stockDataJson)) {
                throw new Exception('No se recibieron datos de stock');
            }
            
            $stockArray = json_decode($stockDataJson, true);
            if (!$stockArray || !is_array($stockArray)) {
                throw new Exception('Formato de datos de stock inválido: ' . json_last_error_msg());
            }
            
            error_log("DEBUG: Array de stock decodificado: " . json_encode($stockArray));
            
            // Validar que vengan datos válidos
            $datosValidos = array_filter($stockArray, function($item) {
                return isset($item['categoria']) && isset($item['cantidad']) && 
                       isset($item['rubro']) && intval($item['cantidad']) > 0;
            });
            
            if (empty($datosValidos)) {
                throw new Exception('No se encontraron datos válidos para procesar. Verifique que las cantidades sean mayores a 0.');
            }
            
            $movimientosRegistrados = 0;
            $errores = [];
            
            foreach ($datosValidos as $item) {
                try {
                    $codigoArticulo = $item['rubro'] . '_' . $item['categoria']; // Generar código único
                    $descripcion = $item['categoria'] . ' - ' . $item['rubro'];
                    $cantidad = intval($item['cantidad']);
                    
                    // Registrar cada artículo individualmente
                    $resultado = $this->registrarArticuloIndividual(
                        $franquicia_id_sesion,
                        $codigoArticulo,
                        $descripcion,
                        $cantidad,
                        'AJUSTE'
                    );
                    
                    if ($resultado) {
                        $movimientosRegistrados++;
                        error_log("DEBUG: Movimiento registrado exitosamente para: " . $codigoArticulo);
                    } else {
                        $errores[] = "No se pudo registrar {$item['categoria']}";
                    }
                    
                } catch (Exception $e) {
                    $errores[] = "Error en {$item['categoria']}: " . $e->getMessage();
                    error_log("DEBUG: Error procesando item: " . $e->getMessage());
                }
            }
            
            if ($movimientosRegistrados > 0) {
                return [
                    'success' => true,
                    'message' => "Se registraron $movimientosRegistrados movimientos de stock",
                    'movimientos_registrados' => $movimientosRegistrados,
                    'errores' => $errores
                ];
            } else {
                throw new Exception('No se pudo registrar ningún movimiento. Errores: ' . implode('; ', $errores));
            }
            
        } catch (Exception $e) {
            error_log("Error procesando stock_data: " . $e->getMessage());
            throw new Exception("Error procesando datos de stock: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar un artículo individual usando conexión directa
     */
    private function registrarArticuloIndividual($franquicia_id, $codigo_articulo, $descripcion, $movimiento, $tipo_movimiento) {
        try {
            error_log("DEBUG: registrarArticuloIndividual - franquicia_id: '$franquicia_id' (tipo: " . gettype($franquicia_id) . ")");
            error_log("DEBUG: registrarArticuloIndividual - codigo_articulo: '$codigo_articulo'");
            
            // Intentar reconectar si no hay conexión
            if (!$this->sistemas_conn) {
                error_log("DEBUG: Reintentando conexión a sistemas en registrarArticuloIndividual");
                try {
                    require_once __DIR__ . '/../class/DatabaseSQLServer.php';
                    $dbSistemas = DatabaseSQLServer::getInstance();
                    $this->sistemas_conn = $dbSistemas->getConnection('sistemas');
                    error_log("DEBUG: Reconexión exitosa a sistemas");
                } catch (Exception $e) {
                    error_log("DEBUG: Error en reconexión: " . $e->getMessage());
                    throw new Exception('No se pudo establecer conexión a la base de datos sistemas: ' . $e->getMessage());
                }
            }
            
            // Obtener stock anterior
            $stockAnterior = $this->obtenerStockAnteriorDirecto($franquicia_id, $codigo_articulo);
            $stockActual = $stockAnterior + $movimiento;
            
            $sql = "INSERT INTO franquicia_historial_stock 
                    (franquicia_id, codigo_articulo, descripcion_articulo, stock_anterior, 
                     movimiento, stock_actual, tipo_movimiento, observaciones, usuario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $franquicia_id,
                $codigo_articulo,
                $descripcion,
                $stockAnterior,
                $movimiento,
                $stockActual,
                $tipo_movimiento,
                'Registro desde interfaz web',
                $_SESSION['usuario_franquicia'] ?? 'sistema'
            ];
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, $params);
            if ($stmt && sqlsrv_execute($stmt)) {
                sqlsrv_free_stmt($stmt);
                error_log("DEBUG: Insert exitoso para artículo: $codigo_articulo");
                return true;
            } else {
                $errors = sqlsrv_errors();
                error_log("DEBUG: Error SQL para $codigo_articulo: " . print_r($errors, true));
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Error registrando artículo individual $codigo_articulo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar movimiento usando DatabaseSQLServer directamente
     */
    private function registrarMovimientoDirecto($franquicia_id_sesion) {
        try {
            error_log("DEBUG: Usando registrarMovimientoDirecto");
            
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos sistemas');
            }
            
            $datos = [
                'franquicia_id' => $franquicia_id_sesion,
                'codigo_articulo' => $_POST['codigo_articulo'] ?? null,
                'descripcion_articulo' => $_POST['descripcion_articulo'] ?? '',
                'movimiento' => intval($_POST['movimiento'] ?? 0),
                'tipo_movimiento' => $_POST['tipo_movimiento'] ?? 'AJUSTE',
                'observaciones' => $_POST['observaciones'] ?? '',
                'usuario' => $_SESSION['usuario_franquicia'] ?? 'sistema'
            ];
            
            // Validar datos requeridos
            if (!$datos['franquicia_id'] || !$datos['codigo_articulo']) {
                throw new Exception('Datos requeridos faltantes: franquicia_id o codigo_articulo');
            }
            
            if ($datos['movimiento'] == 0) {
                throw new Exception('El movimiento no puede ser cero');
            }
            
            // Obtener stock anterior (0 si es nuevo artículo)
            $stockAnterior = $this->obtenerStockAnteriorDirecto($datos['franquicia_id'], $datos['codigo_articulo']);
            $stockActual = $stockAnterior + $datos['movimiento'];
            
            // Insertar en la tabla
            $sql = "INSERT INTO franquicia_historial_stock 
                    (franquicia_id, codigo_articulo, descripcion_articulo, stock_anterior, 
                     movimiento, stock_actual, tipo_movimiento, observaciones, usuario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $datos['franquicia_id'],
                $datos['codigo_articulo'],
                $datos['descripcion_articulo'],
                $stockAnterior,
                $datos['movimiento'],
                $stockActual,
                $datos['tipo_movimiento'],
                $datos['observaciones'],
                $datos['usuario']
            ];
            
            error_log("DEBUG: Ejecutando SQL directa con params: " . json_encode($params));
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, $params);
            if ($stmt && sqlsrv_execute($stmt)) {
                sqlsrv_free_stmt($stmt);
                error_log("DEBUG: Insert exitoso");
                return [
                    'success' => true,
                    'message' => 'Movimiento registrado correctamente'
                ];
            } else {
                $errors = sqlsrv_errors();
                error_log("DEBUG: Error SQL: " . print_r($errors, true));
                throw new Exception('Error SQL al insertar: ' . print_r($errors, true));
            }
            
        } catch (Exception $e) {
            error_log("Error en registrarMovimientoDirecto: " . $e->getMessage());
            throw new Exception("Error al registrar movimiento: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener stock anterior directamente de la base
     */
    private function obtenerStockAnteriorDirecto($franquicia_id, $codigo_articulo) {
        try {
            $sql = "SELECT TOP 1 stock_actual 
                    FROM franquicia_historial_stock 
                    WHERE franquicia_id = ? AND codigo_articulo = ? 
                    ORDER BY fecha_movimiento DESC";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$franquicia_id, $codigo_articulo]);
            if ($stmt && sqlsrv_execute($stmt)) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($stmt);
                return $row ? intval($row['stock_actual']) : 0;
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error obteniendo stock anterior: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtener historial de stock
     */
    public function obtenerHistorialStock($franquicia_id, $limite = 100) {
        try {
            error_log("DEBUG: Obteniendo historial para franquicia_id: $franquicia_id con límite: $limite");
            
            // Si franquicias es null, usar método directo
            if (!$this->franquicias) {
                error_log("DEBUG: franquicias es null, usando método directo para historial");
                return $this->obtenerHistorialDirecto($franquicia_id, $limite);
            }
            
            $historial = $this->franquicias->obtenerHistorialMovimientos($franquicia_id, null, null, $limite);
            
            // Formatear fechas para el frontend
            foreach ($historial as &$movimiento) {
                if (isset($movimiento['fecha_movimiento']) && is_object($movimiento['fecha_movimiento'])) {
                    $movimiento['fecha_movimiento'] = $movimiento['fecha_movimiento']->format('Y-m-d H:i:s');
                }
            }
            
            error_log("DEBUG: Historial obtenido, " . count($historial) . " registros");
            
            return [
                'success' => true,
                'data' => $historial, // Cambiar 'historial' por 'data' para que coincida con el JS
                'total_registros' => count($historial)
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener historial: " . $e->getMessage());
            throw new Exception("Error al obtener historial de stock");
        }
    }
    
    /**
     * Obtener historial usando conexión directa
     */
    private function obtenerHistorialDirecto($franquicia_id, $limite = 100) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos sistemas');
            }
            
            $sql = "SELECT TOP ? 
                        id,
                        franquicia_id,
                        codigo_articulo,
                        descripcion_articulo,
                        stock_anterior,
                        movimiento,
                        stock_actual,
                        tipo_movimiento,
                        observaciones,
                        usuario,
                        fecha_movimiento
                    FROM franquicia_historial_stock 
                    WHERE franquicia_id = ?
                    ORDER BY fecha_movimiento DESC";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$limite, $franquicia_id]);
            
            $historial = [];
            if ($stmt && sqlsrv_execute($stmt)) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    // Formatear fecha si es necesario
                    if (isset($row['fecha_movimiento']) && is_object($row['fecha_movimiento'])) {
                        $row['fecha_movimiento'] = $row['fecha_movimiento']->format('Y-m-d H:i:s');
                    }
                    $historial[] = $row;
                }
                sqlsrv_free_stmt($stmt);
            } else {
                $errors = sqlsrv_errors();
                error_log("DEBUG: Error SQL en historial: " . print_r($errors, true));
            }
            
            error_log("DEBUG: Historial directo obtenido, " . count($historial) . " registros");
            
            return [
                'success' => true,
                'data' => $historial, // Usar 'data' para que coincida con el JS
                'total_registros' => count($historial),
                'source' => 'sistemas_database'
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerHistorialDirecto: " . $e->getMessage());
            throw new Exception("Error al obtener historial: " . $e->getMessage());
        }
    }
    
    /**
     * Editar entrada de stock
     */
    public function editarStock($franquicia_id) {
        try {
            $id = $_POST['id'] ?? null;
            $stock_actual = $_POST['stock_actual'] ?? null;
            $observaciones = $_POST['observaciones'] ?? '';
            
            if (!$id || $stock_actual === null) {
                throw new Exception('Parámetros requeridos faltantes');
            }
            
            // Actualizar directamente en la base de datos
            $sql = "UPDATE franquicia_historial_stock 
                    SET stock_actual = ?, observaciones = ?, fecha_modificacion = GETDATE() 
                    WHERE id = ? AND franquicia_id = ?";
            
            $params = [$stock_actual, $observaciones, $id, $franquicia_id];
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, $params);
            if ($stmt && sqlsrv_execute($stmt)) {
                sqlsrv_free_stmt($stmt);
                return [
                    'success' => true,
                    'message' => 'Stock actualizado correctamente'
                ];
            } else {
                $errors = sqlsrv_errors();
                throw new Exception('Error SQL al actualizar: ' . print_r($errors, true));
            }
            
        } catch (Exception $e) {
            error_log("Error en editarStock: " . $e->getMessage());
            throw new Exception("Error al editar stock: " . $e->getMessage());
        }
    }
    /**
     * Buscar artículos por código o descripción
     */
    public function buscarArticulos() {
        try {
            $termino = $_POST['termino'] ?? $_GET['termino'] ?? '';
            
            if (strlen($termino) < 2) {
                return [
                    'success' => false,
                    'message' => 'Debe ingresar al menos 2 caracteres para buscar'
                ];
            }
            
            error_log("DEBUG: Buscando artículos con término: $termino");
            
            if ($this->sistemas_conn) {
                // Buscar por código o descripción
                $query = "SELECT TOP 50 COD_ARTICU, DESCRIPCION, RUBRO, CATEGORIA_PADRE 
                        FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                        WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                        AND (COD_ARTICU LIKE ? OR DESCRIPCION LIKE ?)
                        ORDER BY COD_ARTICU";
                
                $terminoBusqueda = "%{$termino}%";
                $stmt = sqlsrv_prepare($this->sistemas_conn, $query, [$terminoBusqueda, $terminoBusqueda]);
                
                $articulos = [];
                if ($stmt && sqlsrv_execute($stmt)) {
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $articulos[] = [
                            'codigo' => $row['COD_ARTICU'],
                            'descripcion' => $row['DESCRIPCION'],
                            'rubro' => $row['RUBRO'],
                            'categoria_padre' => $row['CATEGORIA_PADRE'],
                            // Formato para Select2
                            'id' => $row['COD_ARTICU'],
                            'text' => $row['COD_ARTICU'] . ' - ' . $row['DESCRIPCION']
                        ];
                    }
                    sqlsrv_free_stmt($stmt);
                    error_log("DEBUG: Encontrados " . count($articulos) . " artículos");
                } else {
                    error_log("DEBUG: Error en query artículos: " . print_r(sqlsrv_errors(), true));
                }
                
                if (!empty($articulos)) {
                    return [
                        'success' => true,
                        'articulos' => $articulos,
                        'total' => count($articulos),
                        'termino_busqueda' => $termino
                    ];
                }
            }
            
            // Datos de prueba si no hay conexión
            $articulos_prueba = [
                [
                    'codigo' => 'ART001',
                    'descripcion' => 'Zapatilla Deportiva',
                    'rubro' => 'CALZADO',
                    'categoria_padre' => 'DEPORTIVO',
                    'id' => 'ART001',
                    'text' => 'ART001 - Zapatilla Deportiva'
                ],
                [
                    'codigo' => 'ART002',
                    'descripcion' => 'Remera Básica',
                    'rubro' => 'INDUMENTARIA',
                    'categoria_padre' => 'REMERAS',
                    'id' => 'ART002',
                    'text' => 'ART002 - Remera Básica'
                ]
            ];
            
            // Filtrar datos de prueba por el término
            $articulos_filtrados = array_filter($articulos_prueba, function($item) use ($termino) {
                return stripos($item['codigo'], $termino) !== false || 
                    stripos($item['descripcion'], $termino) !== false;
            });
            
            return [
                'success' => true,
                'articulos' => array_values($articulos_filtrados),
                'total' => count($articulos_filtrados),
                'termino_busqueda' => $termino,
                'source' => 'test_data'
            ];
            
        } catch (Exception $e) {
            error_log("Error al buscar artículos: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al buscar artículos: ' . $e->getMessage()
            ];
        }
    }
}
}

// Procesar petición si es una llamada AJAX directa
if (($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['accion'])) && !headers_sent()) {
    // Incluir configuración de sesiones
    require_once __DIR__ . '/../config_sesiones_simple.php';
    
    try {
        $controller = new FranquiciaStockController();
        $resultado = $controller->procesarPeticion();
        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'error_type' => 'fatal_error'
        ]);
    }
}
?>
