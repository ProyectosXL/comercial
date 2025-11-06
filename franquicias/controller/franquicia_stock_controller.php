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
    private $laker_conn; // Conexión específica a base LAKER_SA
    
    public function __construct() {
        try {
            // Intentar crear FranquiciasSQLServer, pero no fallar si hay error de .env
            try {
                $this->franquicias = new FranquiciasSQLServer();
            } catch (Exception $e) {
                error_log("Advertencia: No se pudo inicializar FranquiciasSQLServer (problema .env): " . $e->getMessage());
                $this->franquicias = null;
            }
            
            // Intentar conexión central usando la clase Conexion local de franquicias
            try {
                require_once __DIR__ . '/../class/Conexion.php';
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
            
            // Conexión adicional a LAKER_SA para consultas de usuarios
            try {
                $this->laker_conn = $dbSistemas->getConnection('laker_sa');
                error_log("DEBUG: Conexión a LAKER_SA exitosa");
            } catch (Exception $e) {
                error_log("DEBUG: Error conectando a LAKER_SA: " . $e->getMessage());
                $this->laker_conn = null;
            }
            
        } catch (Exception $e) {
            error_log("Error grave en constructor: " . $e->getMessage());
            $this->cid = null;
            $this->sistemas_conn = null;
        }
    }
    
    /**
     * Obtener NRO_SUCURS del usuario desde LAKER_SA
     */
    private function obtenerNroSucursUsuario($nombreUsuario) {
        try {
            if (!$this->laker_conn) {
                error_log("ERROR: No hay conexión a LAKER_SA");
                return $nombreUsuario; // Fallback al nombre de usuario
            }
            
            $sql = "SELECT NRO_SUCURS FROM SOF_USUARIOS WHERE TIPO = 'FRANQUICIA' AND NOMBRE = ?";
            error_log("DEBUG: Consultando SOF_USUARIOS para usuario: '$nombreUsuario'");
            $stmt = sqlsrv_prepare($this->laker_conn, $sql, [$nombreUsuario]);
            
            if ($stmt && sqlsrv_execute($stmt)) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($stmt);
                
                if ($row && isset($row['NRO_SUCURS'])) {
                    error_log("DEBUG: Usuario '$nombreUsuario' -> NRO_SUCURS: " . $row['NRO_SUCURS']);
                    return $row['NRO_SUCURS'];
                } else {
                    error_log("WARNING: No se encontró NRO_SUCURS para usuario '$nombreUsuario' en SOF_USUARIOS");
                    return $nombreUsuario; // Fallback al nombre de usuario
                }
            } else {
                $errors = sqlsrv_errors();
                error_log("ERROR SQL obteniendo NRO_SUCURS: " . print_r($errors, true));
                return $nombreUsuario; // Fallback al nombre de usuario
            }
            
        } catch (Exception $e) {
            error_log("ERROR obteniendo NRO_SUCURS: " . $e->getMessage());
            return $nombreUsuario; // Fallback al nombre de usuario
        }
    }
    
    /**
     * Procesar peticiones AJAX
     */
    public function procesarPeticion() {
        try {
            error_log("DEBUG PROC: Iniciando procesarPeticion");
            
            $accion = $_POST['accion'] ?? $_GET['accion'] ?? null;
            error_log("DEBUG PROC: Acción solicitada: " . ($accion ?? 'no especificada'));
            
            if (!$accion) {
                error_log("DEBUG PROC: Error - Acción no especificada");
                return [
                    'success' => false,
                    'message' => 'Error: Acción no especificada',
                    'error_type' => 'missing_action'
                ];
            }
            
            // Verificar sesión
            error_log("DEBUG PROC: Verificando sesión...");
            if (!AuthFranquicia::verificarSesion()) {
                error_log("DEBUG PROC: Error - Sesión no válida");
                return [
                    'success' => false,
                    'message' => 'No autorizado - falta franquicia_id en sesión',
                    'error_type' => 'unauthorized',
                    'redirect' => 'login.php',
                    'session_status' => [
                        'active' => session_status() === PHP_SESSION_ACTIVE,
                        'id' => session_id(),
                        'autenticado' => $_SESSION['autenticado'] ?? false,
                        'franquicia_id' => $_SESSION['franquicia_id'] ?? null,
                        'rol' => $_SESSION['user_role'] ?? null
                    ]
                ];
            }
            
            // Obtener y validar franquicia_id
            $franquicia_id_sesion = AuthFranquicia::obtenerFranquiciaIdSesion();
            error_log("DEBUG PROC: Franquicia ID de sesión: " . ($franquicia_id_sesion ?? 'no disponible'));
            
            if (!$franquicia_id_sesion && $accion !== 'obtener_rubros') {
                error_log("DEBUG PROC: Error - Franquicia ID no disponible");
                return [
                    'success' => false,
                    'message' => 'Error: ID de franquicia no disponible',
                    'error_type' => 'missing_franquicia_id'
                ];
            }
            
            // Procesar acción
            error_log("DEBUG PROC: Procesando acción: $accion");
            $resultado = null;
            
            switch ($accion) {
                case 'obtener_rubros':
                    error_log("DEBUG PROC: Llamando a obtenerRubros()");
                    $resultado = $this->obtenerRubros();
                    error_log("DEBUG PROC: Resultado obtenerRubros: " . json_encode($resultado));
                    break;
                
                case 'obtener_categorias':
                    $rubro = $_POST['rubro'] ?? $_GET['rubro'] ?? null;
                    if (!$rubro) {
                        throw new Exception('Rubro no especificado');
                    }
                    $resultado = $this->obtenerCategoriasPorRubro($rubro);
                    break;
                
                case 'obtener_resumen_stock':
                    $periodo = $_POST['periodo'] ?? null;
                    $resultado = $this->obtenerResumenStock($franquicia_id_sesion, $periodo);
                    break;
                
                case 'registrar_movimiento':
                    $resultado = $this->registrarMovimiento($franquicia_id_sesion);
                    break;
                
                case 'obtener_historial':
                    $limite = $_POST['limite'] ?? $_GET['limite'] ?? 100;
                    $resultado = $this->obtenerHistorialStock($franquicia_id_sesion, $limite);
                    break;
                
                case 'editar_stock':
                    $resultado = $this->editarStock($franquicia_id_sesion);
                    break;
                    
                case 'buscar_articulos':
                    $resultado = $this->buscarArticulos();
                    break;
                    
                default:
                    error_log("DEBUG PROC: Error - Acción no válida: $accion");
                    return [
                        'success' => false,
                        'message' => "Error: Acción no válida: $accion",
                        'error_type' => 'invalid_action'
                    ];
            }
            
            if ($resultado === null) {
                error_log("DEBUG PROC: Error - Resultado nulo para acción: $accion");
                throw new Exception("Error procesando acción: $accion");
            }
            
            error_log("DEBUG PROC: Retornando resultado exitoso");
            return $resultado;
            
        } catch (Exception $e) {
            error_log("ERROR PROC: " . $e->getMessage());
            error_log("ERROR PROC: Stack trace - " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage(),
                'error_type' => 'application_error',
                'trace' => es_modo_desarrollo() ? $e->getTraceAsString() : null
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
                         FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                         WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                         ORDER BY RUBRO";
                
                error_log("DEBUG: Ejecutando query: $query");
                $stmt = sqlsrv_query($this->sistemas_conn, $query);
                
                if ($stmt === false) {
                    $errors = sqlsrv_errors();
                    error_log("DEBUG: Error SQL en query rubros: " . print_r($errors, true));
                    error_log("DEBUG: Error específico: " . json_encode($errors));
                    return [
                        'success' => false,
                        'message' => 'Error obteniendo rubros: ' . ($errors ? $errors[0]['message'] : 'Error desconocido'),
                        'error' => $errors
                    ];
                }
                
                $rubros = [];
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    error_log("DEBUG: Procesando fila: " . json_encode($row));
                    $rubros[] = ['RUBRO' => $row['RUBRO']];
                }
                sqlsrv_free_stmt($stmt);
                
                error_log("DEBUG: Total rubros encontrados: " . count($rubros));
                error_log("DEBUG: Rubros: " . json_encode($rubros));
                
                if (!empty($rubros)) {
                    $response = [
                        'success' => true,
                        'rubros' => $rubros,
                        'source' => 'sistemas_database',
                        'count' => count($rubros)
                    ];
                    error_log("DEBUG: Enviando respuesta: " . json_encode($response));
                    return $response;
                } else {
                    error_log("DEBUG: No se encontraron rubros en la base de datos");
                }
            } else {
                error_log("DEBUG: No hay conexión a sistemas para rubros");
                error_log("DEBUG: Estado de conexión: " . ($this->sistemas_conn ? 'Conectado' : 'No conectado'));
                error_log("DEBUG: Variables de conexión: " . json_encode([
                    'sistemas_conn' => !empty($this->sistemas_conn),
                    'cid' => !empty($this->cid),
                    'laker_conn' => !empty($this->laker_conn)
                ]));
            }
            
            // Si llegamos aquí, no hay rubros disponibles
            error_log("DEBUG: No se encontraron rubros - retornando array vacío");
            return [
                'success' => false,
                'message' => 'No se pudieron cargar los rubros. Por favor, verifique la conexión a la base de datos.',
                'rubros' => [],
                'source' => 'no_data',
                'count' => 0
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
                         FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
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
    public function obtenerResumenStock($franquicia_id, $periodo = null) {
        try {
            // Si franquicias es null, usar método directo
            if (!$this->franquicias) {
                error_log("DEBUG: franquicias es null, usando método directo para resumen");
                return $this->obtenerResumenStockDirecto($franquicia_id, $periodo);
            }
            
            // Obtener NRO_SUCURS dinámicamente del usuario
            $nro_sucurs = $this->obtenerNroSucursUsuario($franquicia_id);
            if (!$nro_sucurs) {
                throw new Exception("No se pudo obtener NRO_SUCURS para el usuario: " . $franquicia_id);
            }
            
            error_log("DEBUG: Llamando a FranquiciasSQLServer->obtenerResumenStock con NRO_SUCURS: " . $nro_sucurs . " y periodo: " . $periodo);
            $resumen = $this->franquicias->obtenerResumenStock($nro_sucurs, $periodo);
            
            // Transformar datos para que coincidan con lo que espera el frontend
            $resumenTransformado = $this->transformarResumenStock($resumen);
            
            // Determinar si tiene stock del período actual
            $periodoActual = date('Ym'); // Formato YYYYMM
            $tieneStockActual = $this->verificarStockPeriodoActual($franquicia_id, $periodoActual);
            
            return [
                'success' => true,
                'stock' => $resumenTransformado,
                'tiene_stock' => $tieneStockActual
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener resumen de stock: " . $e->getMessage());
            // Intentar método directo como fallback
            try {
                return $this->obtenerResumenStockDirecto($franquicia_id, $periodo);
            } catch (Exception $e2) {
                throw new Exception("Error al obtener resumen de stock: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Verificar si hay stock cargado para el período actual
     */
    private function verificarStockPeriodoActual($franquicia_id, $periodo) {
        try {
            if (!$this->sistemas_conn) {
                return false;
            }
            
            $nroSucurs = $this->obtenerNroSucursUsuario($franquicia_id);
            
            // Convertir periodo YYYYMM a mes y año
            $año = substr($periodo, 0, 4);
            $mes = substr($periodo, 4, 2);
            
            $sql = "SELECT COUNT(*) as total
                    FROM franquicia_historial_stock
                    WHERE franquicia_id = ?
                    AND YEAR(fecha_movimiento) = ?
                    AND MONTH(fecha_movimiento) = ?";
            
            $stmt = sqlsrv_query($this->sistemas_conn, $sql, array($nroSucurs, $año, $mes));
            
            if ($stmt) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($stmt);
                return ($row && $row['total'] > 0);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error verificando stock del período actual: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener resumen de stock usando conexión directa
     */
    private function obtenerResumenStockDirecto($franquicia_id, $periodo = null) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos sistemas');
            }

            // Obtener NRO_SUCURS del usuario actual
            $nroSucurs = $this->obtenerNroSucursUsuario($franquicia_id);
            error_log("DEBUG RESUMEN: Usuario '$franquicia_id' -> NRO_SUCURS: '$nroSucurs'");

            // Si se especifica período, filtrar por él. Si no, tomar todo
            if ($periodo) {
                // Convertir periodo MM-YYYY a filtro de fecha  
                list($mes, $año) = explode('-', $periodo);
                error_log("DEBUG RESUMEN: Filtrando por período $mes-$año para franquicia $nroSucurs");
                
                $sql = "SELECT 
                            rubro,
                            categoria,
                            stock_actual,
                            fecha_movimiento as ultima_actualizacion
                        FROM franquicia_historial_stock hs1
                        WHERE hs1.franquicia_id = ?
                        AND MONTH(hs1.fecha_movimiento) = ? AND YEAR(hs1.fecha_movimiento) = ?
                        AND hs1.fecha_movimiento = (
                            SELECT MAX(hs2.fecha_movimiento) 
                            FROM franquicia_historial_stock hs2 
                            WHERE hs2.franquicia_id = hs1.franquicia_id 
                            AND hs2.rubro = hs1.rubro 
                            AND hs2.categoria = hs1.categoria
                            AND MONTH(hs2.fecha_movimiento) = ?
                            AND YEAR(hs2.fecha_movimiento) = ?
                        )
                        ORDER BY rubro, categoria";
                        
                $params = [$nroSucurs, $mes, $año, $mes, $año];
            } else {
                // Sin filtro de período - tomar toda la historia
                error_log("DEBUG RESUMEN: Sin filtro de período para franquicia $nroSucurs");
                
                $sql = "SELECT 
                            rubro,
                            categoria,
                            stock_actual,
                            fecha_movimiento as ultima_actualizacion
                        FROM franquicia_historial_stock hs1
                        WHERE hs1.franquicia_id = ?
                        AND hs1.fecha_movimiento = (
                            SELECT MAX(hs2.fecha_movimiento) 
                            FROM franquicia_historial_stock hs2 
                            WHERE hs2.franquicia_id = hs1.franquicia_id 
                            AND hs2.rubro = hs1.rubro 
                            AND hs2.categoria = hs1.categoria
                        )
                        ORDER BY rubro, categoria";
                        
                $params = [$nroSucurs];
            }
            
            error_log("DEBUG RESUMEN: Ejecutando SQL con params: " . json_encode($params));
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, $params);
            $resumen = [];
            
            if ($stmt && sqlsrv_execute($stmt)) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $resumen[] = $row;
                }
                sqlsrv_free_stmt($stmt);
                error_log("DEBUG RESUMEN: Encontrados " . count($resumen) . " registros");
            } else {
                $errors = sqlsrv_errors();
                error_log("DEBUG: Error SQL en resumen: " . print_r($errors, true));
            }
            
            // Transformar datos
            $resumenTransformado = $this->transformarResumenStock($resumen);
            
            // Determinar si tiene stock del período actual
            $periodoActual = date('Ym'); // Formato YYYYMM
            $tieneStockActual = $this->verificarStockPeriodoActual($franquicia_id, $periodoActual);
            
            return [
                'success' => true,
                'stock' => $resumenTransformado,
                'tiene_stock' => $tieneStockActual
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
            // Usar directamente rubro y categoría de la base de datos
            $rubro = $item['rubro'] ?? 'Sin Rubro';
            $categoria = $item['categoria'] ?? 'Sin Categoría';
            
            // Convertir fecha DateTime a string si es necesario
            $ultimaActualizacion = null;
            if (isset($item['ultima_actualizacion'])) {
                if (is_object($item['ultima_actualizacion']) && method_exists($item['ultima_actualizacion'], 'format')) {
                    $ultimaActualizacion = $item['ultima_actualizacion']->format('Y-m-d H:i:s');
                } else {
                    $ultimaActualizacion = (string)$item['ultima_actualizacion'];
                }
            }
            
            $resumenTransformado[] = [
                'rubro' => $rubro,
                'categoria' => $categoria,
                'cantidad' => $item['stock_actual'] ?? 0,
                'codigo_articulo' => $rubro . '_' . $categoria, // Crear dinámicamente
                'descripcion_articulo' => $categoria . ' - ' . $rubro, // Crear dinámicamente
                'ultima_actualizacion' => $ultimaActualizacion
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
                'usuario' => $franquicia_id_sesion // Usar el mismo ID de franquicia como usuario
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
            
            // Validar que vengan datos válidos (incluir cantidades 0)
            $datosValidos = array_filter($stockArray, function($item) {
                return isset($item['categoria']) && isset($item['cantidad']) && 
                       isset($item['rubro']) && is_numeric($item['cantidad']);
            });
            
            if (empty($datosValidos)) {
                throw new Exception('No se encontraron datos válidos para procesar. Verifique que los datos estén completos.');
            }
            
            $movimientosRegistrados = 0;
            $errores = [];
            
            foreach ($datosValidos as $item) {
                try {
                    // Limpiar y validar los datos del item
                    $rubro = trim($item['rubro']);
                    $categoria = trim($item['categoria']);
                    $cantidad = intval($item['cantidad']); // Nueva cantidad a establecer
                    
                    if (empty($rubro) || empty($categoria)) {
                        $errores[] = "Datos inválidos para {$categoria} (rubro: $rubro)";
                        continue;
                    }
                    
                    // Limpiar caracteres especiales que puedan causar problemas
                    $categoria = preg_replace('/[^\w\s\-\.]/', '', $categoria);
                    $rubro = preg_replace('/[^\w\s\-\.]/', '', $rubro);
                    
                    $codigoArticulo = $rubro . '_' . $categoria;
                    $descripcion = $categoria . ' - ' . $rubro;
                    
                    // Obtener stock actual
                    $stockActual = $this->obtenerStockAnteriorDirecto($franquicia_id_sesion, $codigoArticulo);
                    
                    // Calcular el movimiento necesario para llegar a la cantidad deseada
                    $movimiento = $cantidad - $stockActual;
                    
                    error_log("DEBUG: Procesando item - Rubro: '$rubro', Categoría: '$categoria', " . 
                             "Stock actual: $stockActual, Nueva cantidad: $cantidad, Movimiento: $movimiento");
                    
                    // Solo registrar si hay un cambio
                    if ($movimiento !== 0) {
                        $resultado = $this->registrarArticuloIndividual(
                            $franquicia_id_sesion,
                            $codigoArticulo,
                            $descripcion,
                            $movimiento, // Enviamos la diferencia, no la cantidad final
                            'AJUSTE'
                        );
                        
                        if ($resultado) {
                            $movimientosRegistrados++;
                            error_log("DEBUG: Movimiento registrado exitosamente para: " . $codigoArticulo);
                        } else {
                            $errores[] = "No se pudo registrar '$categoria' en rubro '$rubro'";
                            error_log("DEBUG: Error registrando: $categoria");
                        }
                    } else {
                        error_log("DEBUG: No se requiere movimiento para: $codigoArticulo (stock actual = cantidad deseada)");
                    }
                    
                } catch (Exception $e) {
                    $errores[] = "Error procesando '$categoria' en rubro '$rubro': " . $e->getMessage();
                    error_log("DEBUG: Excepción procesando item '$categoria': " . $e->getMessage());
                }
            }
            
            if ($movimientosRegistrados > 0) {
                return [
                    'success' => true,
                    'message' => "Se registraron $movimientosRegistrados registros de stock (incluye cantidades 0)",
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
            error_log("DEBUG: registrarArticuloIndividual - descripcion: '$descripcion'");
            error_log("DEBUG: registrarArticuloIndividual - movimiento: '$movimiento'");
            
            // Obtener NRO_SUCURS del usuario logueado desde LAKER_SA
            $nombreUsuario = $franquicia_id;
            $nroSucurs = $this->obtenerNroSucursUsuario($nombreUsuario);
            error_log("DEBUG: Usuario '$nombreUsuario' convertido a NRO_SUCURS: '$nroSucurs'");
            
            // Validaciones de datos
            if (empty($descripcion)) {
                $descripcion = $codigo_articulo;
            }
            
            $descripcion = trim($descripcion);
            $descripcion = preg_replace('/[^\w\s\-\.]/', '', $descripcion);
            
            if (strlen($codigo_articulo) > 50) {
                $codigo_articulo = substr($codigo_articulo, 0, 50);
            }
            
            if (strlen($descripcion) > 255) {
                $descripcion = substr($descripcion, 0, 255);
            }
            
            error_log("DEBUG: Descripción limpia: '$descripcion'");
            
            // Validar que el movimiento sea un número
            if (!is_numeric($movimiento)) {
                error_log("ERROR: Movimiento no es un número válido: $movimiento");
                return false;
            }
            
            // Convertir a entero para asegurar consistencia
            $movimiento = intval($movimiento);
            
            // Obtener stock anterior
            $stockAnterior = $this->obtenerStockAnteriorDirecto($franquicia_id, $codigo_articulo);
            error_log("DEBUG: Stock anterior: $stockAnterior");
            
            // Calcular nuevo stock
            $stockActual = $stockAnterior + $movimiento;
            error_log("DEBUG: Stock actual calculado: $stockActual = $stockAnterior + $movimiento");
            
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
            
            // Obtener stock anterior (usando el nombre de usuario para la consulta pero insertando el NRO_SUCURS)
            $stockAnterior = $this->obtenerStockAnteriorDirecto($nombreUsuario, $codigo_articulo);
            $stockActual = $stockAnterior + $movimiento;
            
            // Usar nroSucurs para el INSERT
            error_log("DEBUG: Usando NRO_SUCURS para INSERT: '$nroSucurs'");
            
            // Extraer rubro y categoría del código de artículo
            $partes = explode('_', $codigo_articulo, 2);
            $rubro = $partes[0] ?? '';
            $categoria = $partes[1] ?? '';
            
            // Si no hay categoría separada, usar la descripción
            if (empty($categoria)) {
                $categoria = $descripcion;
            }
            
            error_log("DEBUG: Rubro extraído: '$rubro', Categoría extraída: '$categoria'");
            
            // **NUEVA LÓGICA: Verificar si ya existe un registro para este rubro/categoría en el período actual**
            $periodoActual = date('m-Y');
            
            $sqlVerificar = "SELECT id, stock_actual 
                           FROM franquicia_historial_stock 
                           WHERE franquicia_id = ? AND rubro = ? AND categoria = ? 
                           AND FORMAT(fecha_movimiento, 'MM-yyyy') = ?";
            
            $paramsVerificar = [$nroSucurs, $rubro, $categoria, $periodoActual];
            $stmtVerificar = sqlsrv_prepare($this->sistemas_conn, $sqlVerificar, $paramsVerificar);
            
            if (!$stmtVerificar || !sqlsrv_execute($stmtVerificar)) {
                throw new Exception('Error al verificar registro existente');
            }
            
            $registroExistente = sqlsrv_fetch_array($stmtVerificar, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmtVerificar);
            
            if ($registroExistente) {
                // **EXISTE UN REGISTRO: Hacer UPDATE**
                error_log("DEBUG: Registro existente encontrado para $rubro/$categoria, haciendo UPDATE. ID: " . $registroExistente['id']);
                
                // Calcular la nueva cantidad (no es incremental, es absoluta)
                $nuevaCantidad = $stockAnterior + $movimiento; // $movimiento ya contiene la diferencia calculada
                
                $sqlUpdate = "UPDATE franquicia_historial_stock 
                             SET stock_actual = ?, 
                                 observaciones = 'Actualizado desde interfaz web - ' + CONVERT(varchar, GETDATE(), 20)
                             WHERE id = ?";
                
                $paramsUpdate = [$nuevaCantidad, $registroExistente['id']];
                $stmtUpdate = sqlsrv_prepare($this->sistemas_conn, $sqlUpdate, $paramsUpdate);
                
                if ($stmtUpdate && sqlsrv_execute($stmtUpdate)) {
                    sqlsrv_free_stmt($stmtUpdate);
                    error_log("DEBUG: UPDATE exitoso para $rubro/$categoria. Nueva cantidad: $nuevaCantidad");
                    return true;
                } else {
                    $errors = sqlsrv_errors();
                    $errorMsg = "Error SQL UPDATE para $codigo_articulo";
                    if ($errors) {
                        foreach ($errors as $error) {
                            $errorMsg .= " - SQLSTATE: {$error['SQLSTATE']}, Error: {$error['message']}";
                        }
                    }
                    error_log("DEBUG: $errorMsg");
                    return false;
                }
                
            } else {
                // **NO EXISTE: Hacer INSERT como antes**
                error_log("DEBUG: No existe registro para $rubro/$categoria en período $periodoActual, haciendo INSERT");
                
                // ORDEN CORRECTO según estructura real de la tabla:
                // franquicia_id, stock_anterior, movimiento, stock_actual, observaciones, usuario, fecha_movimiento, rubro, categoria
                $sqlInsert = "INSERT INTO franquicia_historial_stock 
                            (franquicia_id, stock_anterior, movimiento, stock_actual, 
                             observaciones, usuario, fecha_movimiento, rubro, categoria) 
                            VALUES (?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)";
                
                $paramsInsert = [
                    $nroSucurs,     // franquicia_id
                    $stockAnterior, // stock_anterior
                    $movimiento,    // movimiento
                    $stockActual,   // stock_actual
                    'Registro desde interfaz web', // observaciones
                    $nombreUsuario, // usuario
                    // fecha_movimiento se establece automáticamente con GETDATE()
                    $rubro,         // rubro (va AL FINAL)
                    $categoria      // categoria (va AL FINAL)
                ];
                
                $stmtInsert = sqlsrv_prepare($this->sistemas_conn, $sqlInsert, $paramsInsert);
                if ($stmtInsert && sqlsrv_execute($stmtInsert)) {
                    sqlsrv_free_stmt($stmtInsert);
                    error_log("DEBUG: INSERT exitoso para artículo: $codigo_articulo");
                    return true;
                } else {
                    $errors = sqlsrv_errors();
                    $errorMsg = "Error SQL INSERT para $codigo_articulo";
                    if ($errors) {
                        foreach ($errors as $error) {
                            $errorMsg .= " - SQLSTATE: {$error['SQLSTATE']}, Error: {$error['message']}";
                        }
                    }
                    error_log("DEBUG: $errorMsg");
                    error_log("DEBUG: SQL: $sqlInsert");
                    error_log("DEBUG: Params: " . json_encode($paramsInsert));
                    return false;
                }
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

            // Obtener NRO_SUCURS del usuario logueado desde LAKER_SA
            $nombreUsuario = $franquicia_id_sesion; // El nombre de usuario actual
            $nroSucurs = $this->obtenerNroSucursUsuario($nombreUsuario);
            
            $datos = [
                'franquicia_id' => $nroSucurs, // Usar NRO_SUCURS en lugar del nombre de usuario
                'codigo_articulo' => $_POST['codigo_articulo'] ?? null,
                'descripcion_articulo' => $_POST['descripcion_articulo'] ?? '',
                'movimiento' => intval($_POST['movimiento'] ?? 0),
                'tipo_movimiento' => $_POST['tipo_movimiento'] ?? 'AJUSTE',
                'observaciones' => $_POST['observaciones'] ?? '',
                'usuario' => $nombreUsuario // Mantener el nombre de usuario en campo usuario
            ];
            
            // Validar datos requeridos
            if (!$datos['franquicia_id'] || !$datos['codigo_articulo']) {
                throw new Exception('Datos requeridos faltantes: franquicia_id o codigo_articulo');
            }

            if ($datos['movimiento'] == 0) {
                throw new Exception('El movimiento no puede ser cero');
            }

            // Extraer rubro y categoría del código de artículo
            $partes = explode('_', $datos['codigo_articulo'], 2);
            $rubro = $partes[0] ?? '';
            $categoria = $partes[1] ?? $datos['descripcion_articulo'] ?? '';

            // Obtener stock anterior (0 si es nuevo artículo)
            $stockAnterior = $this->obtenerStockAnteriorDirecto($datos['franquicia_id'], $datos['codigo_articulo']);
            $stockActual = $stockAnterior + $datos['movimiento'];

            // Insertar en la tabla (sin tipo_movimiento que fue eliminado)
            $sql = "INSERT INTO franquicia_historial_stock 
                    (franquicia_id, rubro, categoria, stock_anterior, 
                     movimiento, stock_actual, observaciones, usuario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $datos['franquicia_id'],
                $rubro,
                $categoria,
                $stockAnterior,
                $datos['movimiento'],
                $stockActual,
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
            // Si franquicia_id parece ser un nombre de usuario, convertir a NRO_SUCURS
            $nroSucursConsulta = $franquicia_id;
            if (!is_numeric($franquicia_id)) {
                $nroSucursConsulta = $this->obtenerNroSucursUsuario($franquicia_id);
                error_log("DEBUG: Convirtiendo '$franquicia_id' a NRO_SUCURS '$nroSucursConsulta' para consulta de stock anterior");
            }
            
            // Extraer rubro y categoría del código de artículo
            $partes = explode('_', $codigo_articulo, 2);
            $rubro = $partes[0] ?? '';
            $categoria = $partes[1] ?? '';
            
            // Usar franquicia_id directamente como texto
            $sql = "SELECT TOP 1 stock_actual 
                    FROM franquicia_historial_stock 
                    WHERE franquicia_id = ? AND rubro = ? AND categoria = ? 
                    ORDER BY fecha_movimiento DESC";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$nroSucursConsulta, $rubro, $categoria]);
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
            
            // Usar franquicia_id directamente como texto
            $sql = "SELECT TOP ? 
                        id,
                        franquicia_id,
                        rubro,
                        categoria,
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
                    
                    // Agregar campos dinámicos para compatibilidad con frontend
                    $row['codigo_articulo'] = $row['rubro'] . '_' . $row['categoria'];
                    $row['descripcion_articulo'] = $row['categoria'] . ' - ' . $row['rubro'];
                    
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
            
            // Usar franquicia_id directamente como texto
            
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
                        FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
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
    
    /**
     * Convertir ID de franquicia de texto a numérico
     * Busca en la tabla SOF_USUARIOS para obtener el ID numérico correspondiente
     */
    private function obtenerIdNumericoFranquicia($franquicia_codigo) {
        try {
            // Primero intentar si ya es numérico
            if (is_numeric($franquicia_codigo)) {
                return intval($franquicia_codigo);
            }
            
            // Si no es numérico, buscar en la base de franquicias (LAKER_SA)
            require_once __DIR__ . '/../class/DatabaseSQLServer.php';
            $db = DatabaseSQLServer::getInstance();
            $conn_laker = $db->getConnection('laker_sa');
            
            if (!$conn_laker) {
                error_log("DEBUG: No se pudo conectar a LAKER_SA para obtener ID de franquicia");
                // Como fallback, usar un mapeo simple basado en hash
                return abs(crc32($franquicia_codigo)) % 10000;
            }
            
            // Buscar el ID en SOF_USUARIOS
            $sql = "SELECT TOP 1 CAST(CODIGO AS INT) as id_numerico 
                    FROM SOF_USUARIOS 
                    WHERE NOMBRE = ? AND TIPO = 'FRANQUICIA'";
            
            $stmt = sqlsrv_prepare($conn_laker, $sql, [$franquicia_codigo]);
            if ($stmt && sqlsrv_execute($stmt)) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($stmt);
                
                if ($row && isset($row['id_numerico'])) {
                    error_log("DEBUG: ID numérico encontrado para '$franquicia_codigo': {$row['id_numerico']}");
                    return intval($row['id_numerico']);
                }
            }
            
            // Si no se encuentra, usar un ID basado en hash como fallback
            $fallback_id = abs(crc32($franquicia_codigo)) % 10000;
            error_log("DEBUG: No se encontró ID para '$franquicia_codigo', usando fallback: $fallback_id");
            return $fallback_id;
            
        } catch (Exception $e) {
            error_log("Error obteniendo ID numérico de franquicia: " . $e->getMessage());
            // Fallback: usar hash del código
            return abs(crc32($franquicia_codigo)) % 10000;
        }
    }
}
}

// Procesar petición si es una llamada AJAX directa
if (($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['accion'])) && !headers_sent()) {
    try {
        error_log("DEBUG CONTROLLER: Iniciando procesamiento de petición AJAX");
        error_log("DEBUG CONTROLLER: Método: " . $_SERVER['REQUEST_METHOD']);
        error_log("DEBUG CONTROLLER: Acción: " . ($_POST['accion'] ?? $_GET['accion'] ?? 'no especificada'));
        error_log("DEBUG CONTROLLER: Parámetros POST: " . json_encode($_POST));
        error_log("DEBUG CONTROLLER: Parámetros GET: " . json_encode($_GET));
        
        // Verificar sesión antes de procesar
        if (!isset($_SESSION)) {
            error_log("DEBUG CONTROLLER: Iniciando sesión...");
            session_start();
        }
        
        error_log("DEBUG CONTROLLER: Estado de sesión: " . json_encode([
            'autenticado' => $_SESSION['autenticado'] ?? false,
            'usuario' => $_SESSION['usuario_franquicia'] ?? null,
            'franquicia_id' => $_SESSION['franquicia_id'] ?? null,
            'rol' => $_SESSION['user_role'] ?? null
        ]));
        
        // Crear controlador y procesar petición
        $controller = new FranquiciaStockController();
        error_log("DEBUG CONTROLLER: Controlador creado");
        
        $resultado = $controller->procesarPeticion();
        error_log("DEBUG CONTROLLER: Petición procesada, resultado: " . json_encode($resultado));
        
        // Verificar que no se han enviado headers
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            error_log("DEBUG CONTROLLER: Headers establecidos");
        } else {
            error_log("DEBUG CONTROLLER: Headers ya enviados");
        }
        
        // Codificar respuesta
        $jsonResponse = json_encode($resultado);
        if ($jsonResponse === false) {
            error_log("DEBUG CONTROLLER: Error codificando JSON: " . json_last_error_msg());
            throw new Exception("Error codificando respuesta JSON: " . json_last_error_msg());
        }
        
        echo $jsonResponse;
        error_log("DEBUG CONTROLLER: Respuesta enviada exitosamente");
        
    } catch (Exception $e) {
        error_log("ERROR CONTROLLER: " . $e->getMessage());
        
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'error_type' => 'fatal_error',
            'stack_trace' => $e->getTraceAsString()
        ]);
    }
}
?>
