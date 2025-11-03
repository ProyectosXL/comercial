<?php
/**
 * Controlador para procesar importación de múltiples períodos
 * Versión optimizada con manejo robusto de errores
 */

// Configuración inicial
ini_set('display_errors', '0');
ini_set('error_reporting', E_ALL);
ini_set('log_errors', '1');
ini_set('max_execution_time', '300');
ini_set('memory_limit', '512M');
set_time_limit(300);

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        error_log("FATAL ERROR: " . print_r($error, true));
        if (!headers_sent()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error fatal del servidor: ' . $error['message'],
                'file' => basename($error['file']),
                'line' => $error['line']
            ]);
        }
    }
});

// Iniciar buffer de output
ob_start();

// Includes
require_once '../config_sesiones_fixed.php';
require_once '../class/DatabaseConfig.php';

// Descartar cualquier output previo
$trash = ob_get_clean();
if ($trash) {
    error_log("Output descartado: " . strlen($trash) . " bytes");
}

// Headers limpios
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');

// Función helper para responder JSON limpio
function jsonResponse($data) {
    echo json_encode($data);
    exit;
}

// Verificaciones básicas
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    jsonResponse(['success' => false, 'message' => 'No autorizado']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido']);
}

error_log("╔═══════════════════════════════════════════════════════════╗");
error_log("║   INICIO PROCESAMIENTO MÚLTIPLES PERÍODOS                ║");
error_log("║   Timestamp: " . date('Y-m-d H:i:s') . "                        ║");
error_log("╚═══════════════════════════════════════════════════════════╝");

// Leer datos JSON del request
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    jsonResponse(['success' => false, 'message' => 'No se recibieron datos en el request']);
}

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    jsonResponse(['success' => false, 'message' => 'Error decodificando JSON: ' . json_last_error_msg()]);
}

// Extraer datos del input JSON
$datos = $input['datos'] ?? [];
if (empty($datos)) {
    jsonResponse(['success' => false, 'message' => 'No se encontraron datos para procesar']);
}

// Extraer modo de operación
$modo = $input['modo'] ?? 'nueva';

error_log("MODO RECIBIDO: " . $modo);
error_log("Total de registros recibidos: " . count($datos));

/**
 * Función para agrupar artículos duplicados por artículo + período
 */
function agruparArticulosDuplicados($datos) {
    error_log("🔄 Iniciando agrupación de duplicados en servidor");
    error_log("📊 Datos originales antes de agrupar: " . count($datos));
    
    $agrupados = [];
    $duplicadosEncontrados = 0;
    
    foreach ($datos as $item) {
        $clave = $item['articulo'] . '_' . $item['periodo'];
        
        if (isset($agrupados[$clave])) {
            $agrupados[$clave]['cantidad'] += intval($item['cantidad']);
            $duplicadosEncontrados++;
        } else {
            $agrupados[$clave] = [
                'articulo' => $item['articulo'],
                'cantidad' => intval($item['cantidad']),
                'periodo' => $item['periodo']
            ];
        }
    }
    
    $datosAgrupados = array_values($agrupados);
    
    error_log("📊 Resultado de agrupación:");
    error_log("  - Registros originales: " . count($datos));
    error_log("  - Registros después de agrupación: " . count($datosAgrupados));
    error_log("  - Duplicados encontrados: $duplicadosEncontrados");
    
    return $datosAgrupados;
}

// Agrupar artículos duplicados
$datosOriginales = $datos;
$datos = agruparArticulosDuplicados($datosOriginales);

error_log("✅ Agrupación completada - count original: " . count($datosOriginales) . " -> agrupado: " . count($datos));

// Validación y sanitización de datos
$hayDatosCorregidos = false;
foreach ($datos as $item) {
    if (isset($item['periodo']) && is_string($item['periodo'])) {
        if (strlen($item['periodo']) > 10 && is_numeric($item['periodo'])) {
            error_log("🔍 FECHA EXCEL DETECTADA sin convertir: " . $item['periodo']);
            $hayDatosCorregidos = true;
        }
    }
    
    if (isset($item['articulo']) && is_string($item['articulo'])) {
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $item['articulo'])) {
            error_log("❌ CARACTERES DE CONTROL en artículo");
            $hayDatosCorregidos = true;
        }
    }
}

if ($hayDatosCorregidos) {
    error_log("⚠️ SE DETECTARON DATOS CON CORRECCIONES - Sanitizando...");
    
    foreach ($datos as $index => &$item) {
        if (isset($item['periodo'])) {
            $item['periodo'] = trim(preg_replace('/[^\x20-\x7E]/', '', $item['periodo']));
        }
        
        if (isset($item['articulo'])) {
            $item['articulo'] = trim(strtoupper(preg_replace('/[^\x20-\x7E]/', '', $item['articulo'])));
        }
        
        if (isset($item['cantidad'])) {
            $item['cantidad'] = intval($item['cantidad']);
        }
    }
    unset($item);
}

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? 'Sistema';
$nroSucursal = $_SESSION['franquicia_id'] ?? '';

if (empty($nroSucursal)) {
    jsonResponse(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
}

// Limitar longitud de usuario
if (strlen($usuario) > 50) {
    $usuario = substr($usuario, 0, 50);
}

// **OPTIMIZACIÓN: Aumentar límites de tiempo para evitar timeout**
set_time_limit(300); // 5 minutos máximo
ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M'); // Aumentar memoria disponible

// **OPTIMIZACIÓN: Evitar timeout de proxy/gateway (Apache, Nginx, IIS)**
header('Connection: keep-alive');
header('Keep-Alive: timeout=300, max=1');

// **OPTIMIZACIÓN: Enviar pequeños chunks para mantener conexión viva**
if (ob_get_level() == 0) ob_start();

try {
    error_log("🔍 Iniciando procesamiento principal");
    error_log("📊 Usuario: $usuario");
    error_log("📊 Sucursal: $nroSucursal");
    error_log("📊 Total registros a procesar: " . count($datos));
    
    // Conectar a bases de datos
    error_log("🔌 Conectando a bases de datos...");
    $conexionLaker = DatabaseConfig::conectarLakerSA();
    error_log("  ✅ Conexión LAKER_SA establecida");
    
    $conexionSistemas = DatabaseConfig::conectarSistemas();
    error_log("  ✅ Conexión sistemas establecida");
    
    // Nota: PDO::ATTR_TIMEOUT no es compatible con pdo_sqlsrv
    
    // Verificar base de datos actual
    $sqlCheckDB = "SELECT DB_NAME() as db_actual";
    $stmtCheckDB = $conexionSistemas->query($sqlCheckDB);
    $dbInfo = $stmtCheckDB->fetch(PDO::FETCH_ASSOC);
    error_log("  📍 Base de datos actual: " . $dbInfo['db_actual']);
    
    // Iniciar transacción
    error_log("🔄 Iniciando transacción...");
    $conexionSistemas->beginTransaction();
    error_log("  ✅ Transacción iniciada correctamente");
    error_log("  🔍 Estado transacción: " . ($conexionSistemas->inTransaction() ? 'ACTIVA' : 'INACTIVA'));

    // Validar estructura de datos
    $errores = [];
    $periodosValidos = [];
    $articulosValidar = [];
    
    foreach ($datos as $index => $item) {
        $fila = $index + 1;
        
        // Validar que tenga todas las propiedades necesarias
        if (!isset($item['articulo']) || !isset($item['cantidad']) || !isset($item['periodo'])) {
            $errores[] = "Fila $fila: Datos incompletos - se requiere artículo, cantidad y período";
            continue;
        }
        
        // Validar que los campos no estén vacíos
        if (trim($item['articulo']) === '') {
            $errores[] = "Fila $fila: El código de artículo está vacío";
            continue;
        }
        
        if (trim($item['periodo']) === '') {
            $errores[] = "Fila $fila: El período está vacío";
            continue;
        }
        
        if (!is_numeric($item['cantidad']) || intval($item['cantidad']) < 0) {
            $errores[] = "Fila $fila: La cantidad debe ser un número positivo (valor actual: '" . ($item['cantidad'] ?? 'vacío') . "')";
            continue;
        }
        
        // Validar formato de período (DD/MM/YYYY - primer día del mes)
        if (!preg_match('/^01\/(0[1-9]|1[0-2])\/[0-9]{4}$/', $item['periodo'])) {
            $errores[] = "Fila $fila: Formato de período inválido - debe ser 01/MM/YYYY (valor actual: '" . $item['periodo'] . "')";
            continue;
        }
        
        // Convertir período DD/MM/YYYY a formato YYYYMM
        $partesPeriodo = explode('/', $item['periodo']);
        $periodoDb = $partesPeriodo[2] . $partesPeriodo[1];
        $periodosValidos[] = $periodoDb;
        
        // Recopilar artículos únicos (limpiar y normalizar - eliminar TODOS los espacios)
        $original = $item['articulo'];
        $articuloLimpio = strtoupper(preg_replace('/\s+/', '', trim($item['articulo'])));
        $articulosValidar[] = $articuloLimpio;
        error_log("📝 Artículo normalizado (múltiple): '$original' → '" . $articuloLimpio . "' (length: " . strlen($articuloLimpio) . ")");
    }

    if (!empty($errores)) {
        $mensajeError = "Errores encontrados en el archivo Excel:\n" . implode("\n", array_slice($errores, 0, 10));
        if (count($errores) > 10) {
            $mensajeError .= "\n... y " . (count($errores) - 10) . " errores más";
        }
        throw new Exception($mensajeError);
    }

    // Validar artículos en LAKER_SA
    $articulosUnicos = array_values(array_unique($articulosValidar));
    
    error_log("🔍 VALIDACIÓN DE ARTÍCULOS - Total únicos: " . count($articulosUnicos));
    error_log("  Primeros 5 artículos a validar: " . implode(', ', array_slice($articulosUnicos, 0, 5)));
    
    if (empty($articulosUnicos)) {
        throw new Exception("No hay artículos para validar");
    }
    
    // Procesar validación en lotes
    $batchSize = 2000;
    $totalCodigos = count($articulosUnicos);
    $articulosValidos = [];
    
    for ($i = 0; $i < $totalCodigos; $i += $batchSize) {
        $batch = array_slice($articulosUnicos, $i, $batchSize);
        $batchNum = floor($i / $batchSize) + 1;
        $totalBatches = ceil($totalCodigos / $batchSize);
        
        error_log("📦 Validando lote $batchNum de $totalBatches (" . count($batch) . " artículos)");
        
        $placeholders = str_repeat('?,', count($batch) - 1) . '?';
        // Normalizar también en la consulta SQL (REPLACE elimina espacios internos)
        $sqlValidar = "SELECT REPLACE(LTRIM(RTRIM(COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI as COD_ARTICU 
                       FROM STA11 
                       WHERE REPLACE(LTRIM(RTRIM(COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI IN ($placeholders) 
                         AND COD_ARTICU LIKE '[XO]%'";
        
        $stmtValidar = $conexionLaker->prepare($sqlValidar);
        if (!$stmtValidar) {
            throw new Exception("Error preparando validación: " . implode(" ", $conexionLaker->errorInfo()));
        }
        
        if (!$stmtValidar->execute($batch)) {
            throw new Exception("Error ejecutando validación: " . implode(" ", $stmtValidar->errorInfo()));
        }
        
        $articulosValidosLote = $stmtValidar->fetchAll(PDO::FETCH_COLUMN);
        $articulosValidos = array_merge($articulosValidos, $articulosValidosLote);
        
        error_log("✅ Lote $batchNum completado: " . count($articulosValidosLote) . " artículos válidos");
    }
    
    error_log("🔍 Total artículos válidos encontrados: " . count($articulosValidos));
    error_log("🔍 Artículos válidos: " . json_encode($articulosValidos));

    // Verificar artículos inválidos
    $articulosInvalidos = [];
    foreach ($articulosUnicos as $articulo) {
        if (!in_array($articulo, $articulosValidos)) {
            $articulosInvalidos[] = $articulo;
            error_log("❌ Artículo NO encontrado en STA11: $articulo");
        }
    }
    
    if (!empty($articulosInvalidos)) {
        $mensajeError = "Artículos inválidos: " . implode(', ', $articulosInvalidos);
        error_log("❌ ERROR VALIDACION (múltiple): " . $mensajeError);
        throw new Exception($mensajeError);
    }
    
    error_log("✅ TODOS LOS ARTÍCULOS SON VÁLIDOS");

    // Crear/verificar tabla
    $sqlCrearTabla = "
        IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='ventas_franquicias_sin_tango' AND xtype='U')
        BEGIN
            CREATE TABLE ventas_franquicias_sin_tango (
                id int IDENTITY(1,1) PRIMARY KEY,
                nro_sucursal varchar(10) NOT NULL,
                periodo varchar(6) NOT NULL,
                codigo_articulo varchar(50) NOT NULL,
                rubro varchar(100) NULL,
                categoria_padre varchar(100) NULL,
                cantidad int NOT NULL,
                fecha_carga datetime DEFAULT GETDATE(),
                usuario_carga varchar(50) NULL,
                fecha DATE NULL
            )
        END
        
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'rubro')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD rubro varchar(100) NULL
        END
        
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'categoria_padre')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD categoria_padre varchar(100) NULL
        END
        
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'fecha')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD fecha DATE NULL
        END
    ";
    
    $conexionSistemas->exec($sqlCrearTabla);
    error_log("✅ Tabla verificada/creada");

    // Obtener períodos únicos
    $periodosAfectados = array_values(array_unique($periodosValidos));
    
    error_log("📊 Períodos afectados: " . json_encode($periodosAfectados));
    
    if (empty($periodosAfectados)) {
        throw new Exception("No hay períodos válidos para procesar");
    }
    
    // Lógica según el modo
    switch ($modo) {
        case 'reemplazar':
        case 'reemplazar_todo':
            error_log("=== MODO REEMPLAZAR ACTIVADO ===");
            
            $totalRegistrosEliminados = 0;
            
            foreach ($periodosAfectados as $periodo) {
                error_log("🗑️ Eliminando período: $periodo");
                
                $sqlEliminar = "DELETE FROM ventas_franquicias_sin_tango 
                                WHERE nro_sucursal = ? AND periodo = ?";
                
                $stmtEliminar = $conexionSistemas->prepare($sqlEliminar);
                
                if (!$stmtEliminar->execute([$nroSucursal, $periodo])) {
                    throw new Exception("Error eliminando período $periodo");
                }
                
                $eliminados = $stmtEliminar->rowCount();
                $totalRegistrosEliminados += $eliminados;
                
                error_log("✅ Período $periodo: $eliminados registros eliminados");
            }
            
            error_log("📊 Total eliminados: $totalRegistrosEliminados");
            break;
            
        case 'agregar':
        case 'solo_faltantes':
            error_log("📋 MODO AGREGAR: Verificando períodos existentes");
            
            $placeholders = str_repeat('?,', count($periodosAfectados) - 1) . '?';
            $sqlPeriodosExist = "SELECT DISTINCT periodo FROM ventas_franquicias_sin_tango 
                                WHERE nro_sucursal = ? AND periodo IN ($placeholders)";
            
            $stmtPeriodosExist = $conexionSistemas->prepare($sqlPeriodosExist);
            $stmtPeriodosExist->execute(array_merge([$nroSucursal], $periodosAfectados));
            $periodosExistentes = $stmtPeriodosExist->fetchAll(PDO::FETCH_COLUMN);
            
            error_log("Períodos existentes: " . json_encode($periodosExistentes));
            
            // Filtrar datos
            $datos = array_filter($datos, function($item) use ($periodosExistentes) {
                $partes = explode('/', $item['periodo']);
                $periodoDb = $partes[2] . $partes[1];
                return !in_array($periodoDb, $periodosExistentes);
            });
            
            if (empty($datos)) {
                $conexionSistemas->commit();
                jsonResponse([
                    'success' => true,
                    'message' => 'No hay períodos faltantes para insertar',
                    'procesados' => 0,
                    'periodos_existentes' => $periodosExistentes
                ]);
            }
            break;
            
        case 'nueva':
        default:
            error_log("🗑️ MODO NUEVA: Eliminando períodos existentes");
            
            $placeholders = str_repeat('?,', count($periodosAfectados) - 1) . '?';
            $sqlEliminar = "DELETE FROM ventas_franquicias_sin_tango 
                           WHERE nro_sucursal = ? AND periodo IN ($placeholders)";
            
            $stmtEliminar = $conexionSistemas->prepare($sqlEliminar);
            $stmtEliminar->execute(array_merge([$nroSucursal], $periodosAfectados));
            
            error_log("✅ Eliminación completada");
            break;
    }

    // Pre-cargar rubros y categorías desde LAKER_SA (no desde sistemas)
    error_log("📚 Pre-cargando rubros y categorías desde LAKER_SA...");
    
    $articulosUnicos = array_values(array_unique(array_column($datos, 'articulo')));
    $rubrosCategoriasCache = [];
    
    $batchSize = 2000;
    $totalArticulos = count($articulosUnicos);
    
    for ($i = 0; $i < $totalArticulos; $i += $batchSize) {
        $batch = array_slice($articulosUnicos, $i, $batchSize);
        $placeholders = str_repeat('?,', count($batch) - 1) . '?';
        
        // CORRECCIÓN: La tabla está en LAKER_SA, no en sistemas
        // Usamos JOIN con STA11 para asegurar que solo obtengamos artículos válidos
        $sqlRC = "SELECT 
                    REPLACE(LTRIM(RTRIM(s.COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI as COD_ARTICU,
                    ISNULL(m.RUBRO, 'Sin rubro') as RUBRO,
                    ISNULL(m.CATEGORIA_PADRE, 'Sin categoría') as CATEGORIA_PADRE
                  FROM STA11 s
                  LEFT JOIN dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA m 
                    ON REPLACE(LTRIM(RTRIM(s.COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI = REPLACE(m.COD_ARTICU, ' ', '') COLLATE Modern_Spanish_CI_AI
                  WHERE REPLACE(LTRIM(RTRIM(s.COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI IN ($placeholders)";
        
        // CAMBIO CRÍTICO: Usar conexión LAKER en lugar de sistemas
        $stmtRC = $conexionLaker->prepare($sqlRC);
        
        if (!$stmtRC) {
            error_log("⚠️ Error preparando consulta de rubro/categoría: " . implode(" ", $conexionLaker->errorInfo()));
            // Continuar sin rubros/categorías en caso de error
            continue;
        }
        
        if (!$stmtRC->execute($batch)) {
            error_log("⚠️ Error ejecutando consulta de rubro/categoría: " . implode(" ", $stmtRC->errorInfo()));
            continue;
        }
        
        $resultadosRC = $stmtRC->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($resultadosRC as $row) {
            $rubrosCategoriasCache[trim($row['COD_ARTICU'])] = [
                'rubro' => trim($row['RUBRO']) ?: 'Sin rubro',
                'categoria' => trim($row['CATEGORIA_PADRE']) ?: 'Sin categoría'
            ];
        }
        
        error_log("📦 Lote rubro/categoría " . (floor($i / $batchSize) + 1) . ": " . count($resultadosRC) . " artículos procesados");
    }
    
    error_log("✅ Pre-carga completada: " . count($rubrosCategoriasCache) . " artículos con rubro/categoría");
    
    // Si no se pudieron obtener rubros/categorías, usar valores por defecto
    if (empty($rubrosCategoriasCache)) {
        error_log("⚠️ No se pudieron pre-cargar rubros/categorías, usando valores por defecto");
    }

    // Inserción por lotes
    error_log("🚀 INICIANDO INSERCIÓN POR LOTES...");
    
    $totalItems = count($datos);
    $procesados = 0;
    $erroresProcesamiento = [];
    $resumenPeriodos = [];
    
    $batchSize = 250;
    $totalBatches = ceil($totalItems / $batchSize);
    
    error_log("📊 Total items: $totalItems en $totalBatches lotes");
    
    for ($batchNum = 0; $batchNum < $totalBatches; $batchNum++) {
        $startIdx = $batchNum * $batchSize;
        $batchDatos = array_slice($datos, $startIdx, $batchSize);
        
        error_log("📦 Procesando lote " . ($batchNum + 1) . "/$totalBatches");
        
        $valuesArray = [];
        $parametros = [];
        
        foreach ($batchDatos as $index => $item) {
            try {
                $cantidad = intval($item['cantidad']);
                if ($cantidad <= 0) continue;

                $partes = explode('/', $item['periodo']);
                $periodoDb = $partes[2] . $partes[1];
                
                if (!preg_match('/^[0-9]{6}$/', $periodoDb)) {
                    throw new Exception("Período inválido: {$item['periodo']}");
                }
                
                $codigoArticulo = trim($item['articulo']);
                $codigoArticulo = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $codigoArticulo);
                
                if (empty($codigoArticulo) || strlen($codigoArticulo) > 50) {
                    throw new Exception("Código de artículo inválido");
                }
                
                // Obtener rubro/categoría del cache
                $rubro = 'Sin rubro';
                $categoria = 'Sin categoría';
                
                if (isset($rubrosCategoriasCache[$codigoArticulo])) {
                    $rubro = $rubrosCategoriasCache[$codigoArticulo]['rubro'];
                    $categoria = $rubrosCategoriasCache[$codigoArticulo]['categoria'];
                }
                
                if (strlen($rubro) > 100) $rubro = substr($rubro, 0, 97) . '...';
                if (strlen($categoria) > 100) $categoria = substr($categoria, 0, 97) . '...';
                
                // Orden correcto según estructura de tabla en 'sistemas':
                // nro_sucursal, periodo, codigo_articulo, rubro, categoria_padre, cantidad, fecha_carga, usuario_carga, fecha
                $valuesArray[] = "(?, ?, ?, ?, ?, ?, GETDATE(), ?, NULL)";
                
                $parametros[] = $nroSucursal;
                $parametros[] = $periodoDb;
                $parametros[] = $codigoArticulo;
                $parametros[] = $rubro;
                $parametros[] = $categoria;
                $parametros[] = $cantidad;
                $parametros[] = $usuario;
                
                if (!isset($resumenPeriodos[$item['periodo']])) {
                    $resumenPeriodos[$item['periodo']] = 0;
                }
                $resumenPeriodos[$item['periodo']]++;
                
            } catch (Exception $e) {
                $erroresProcesamiento[] = "Fila " . ($startIdx + $index + 1) . ": " . $e->getMessage();
            }
        }
        
        if (!empty($valuesArray)) {
            try {
                // Orden correcto de columnas según estructura en base de datos 'sistemas'
                $sqlBatch = "INSERT INTO ventas_franquicias_sin_tango 
                            (nro_sucursal, periodo, codigo_articulo, rubro, categoria_padre, cantidad, fecha_carga, usuario_carga, fecha) 
                            VALUES " . implode(", ", $valuesArray);
                
                // **DEBUG DETALLADO**
                error_log("🔍 PREPARANDO LOTE " . ($batchNum + 1) . ":");
                error_log("  - Registros en este lote: " . count($valuesArray));
                error_log("  - Parámetros totales: " . count($parametros) . " (esperados: " . (count($valuesArray) * 7) . ")");
                error_log("  - Primeros 3 parámetros: " . json_encode(array_slice($parametros, 0, 3)));
                error_log("  - SQL (primeros 200 chars): " . substr($sqlBatch, 0, 200) . "...");
                
                $stmtBatch = $conexionSistemas->prepare($sqlBatch);
                error_log("  ✅ SQL preparado correctamente");
                
                error_log("  🚀 Ejecutando INSERT...");
                $executeResult = $stmtBatch->execute($parametros);
                error_log("  📊 Execute result: " . ($executeResult ? 'TRUE' : 'FALSE'));
                
                if (!$executeResult) {
                    $errorInfo = $stmtBatch->errorInfo();
                    error_log("  ❌ Error PDO: " . json_encode($errorInfo));
                    throw new Exception("Error ejecutando lote: " . implode(" ", $errorInfo));
                }
                
                $procesadosLote = count($valuesArray);
                $procesados += $procesadosLote;
                
                error_log("  ✅ Lote " . ($batchNum + 1) . " EJECUTADO: $procesadosLote registros");
                
                // Verificar inmediatamente después del INSERT
                $sqlVerificar = "SELECT COUNT(*) as insertados 
                                FROM ventas_franquicias_sin_tango 
                                WHERE nro_sucursal = ? 
                                AND fecha_carga >= DATEADD(SECOND, -5, GETDATE())";
                $stmtVerif = $conexionSistemas->prepare($sqlVerificar);
                $stmtVerif->execute([$nroSucursal]);
                $verifResult = $stmtVerif->fetch(PDO::FETCH_ASSOC);
                error_log("  🔍 Verificación inmediata: " . $verifResult['insertados'] . " registros encontrados en últimos 5 segundos");
                
            } catch (Exception $e) {
                error_log("❌ EXCEPCIÓN en lote " . ($batchNum + 1) . ": " . $e->getMessage());
                error_log("❌ Stack trace: " . $e->getTraceAsString());
                throw $e;
            }
        } else {
            error_log("⚠️ Lote " . ($batchNum + 1) . " está VACÍO (no hay valuesArray)");
        }
    }
    
    error_log("✅ INSERCIÓN COMPLETADA - Total procesados: $procesados");

    // Confirmar transacción
    $conexionSistemas->commit();
    error_log("✅ COMMIT COMPLETADO");

    // **VERIFICACIÓN POST-COMMIT**
    try {
        error_log("🔍 VERIFICACIÓN POST-COMMIT:");
        
        // 1. Verificar servidor y base de datos donde se insertó
        $sqlServerInfo = "SELECT @@SERVERNAME as servidor, DB_NAME() as base_datos";
        $stmtServerInfo = $conexionSistemas->query($sqlServerInfo);
        $serverInfo = $stmtServerInfo->fetch(PDO::FETCH_ASSOC);
        error_log("  📍 Servidor: " . $serverInfo['servidor']);
        error_log("  📍 Base de datos: " . $serverInfo['base_datos']);
        
        // 2. Contar registros insertados en los últimos 5 minutos
        $sqlCount = "SELECT COUNT(*) as total 
                     FROM ventas_franquicias_sin_tango 
                     WHERE nro_sucursal = ? 
                     AND fecha_carga >= DATEADD(MINUTE, -5, GETDATE())";
        
        $stmtCount = $conexionSistemas->prepare($sqlCount);
        $stmtCount->execute([$nroSucursal]);
        $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $registrosInsertados = $countResult['total'];
        
        error_log("  📊 Registros insertados últimos 5 min: " . $registrosInsertados);
        error_log("  📊 Registros que DEBERÍAN haberse insertado: " . $procesados);
        
        if ($registrosInsertados == 0) {
            error_log("  ⚠️ ADVERTENCIA: No se encontraron registros recién insertados!");
        } else if ($registrosInsertados < $procesados) {
            error_log("  ⚠️ ADVERTENCIA: Se insertaron menos registros de los esperados!");
        } else {
            error_log("  ✅ Verificación exitosa: registros encontrados");
        }
        
        // 3. Mostrar ejemplos de registros insertados
        if ($registrosInsertados > 0) {
            $sqlEjemplos = "SELECT TOP 5 
                           id, nro_sucursal, periodo, codigo_articulo, cantidad, 
                           rubro, categoria_padre, fecha_carga 
                           FROM ventas_franquicias_sin_tango 
                           WHERE nro_sucursal = ? 
                           ORDER BY id DESC";
            
            $stmtEjemplos = $conexionSistemas->prepare($sqlEjemplos);
            $stmtEjemplos->execute([$nroSucursal]);
            $ejemplos = $stmtEjemplos->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("  📋 EJEMPLOS DE REGISTROS INSERTADOS:");
            foreach ($ejemplos as $idx => $ej) {
                error_log("    " . ($idx + 1) . ". ID: {$ej['id']}, Período: {$ej['periodo']}, " .
                         "Artículo: {$ej['codigo_articulo']}, Cantidad: {$ej['cantidad']}, " .
                         "Rubro: {$ej['rubro']}, Cat: {$ej['categoria_padre']}");
            }
        }
        
        // 4. Total de registros en la tabla para esta sucursal
        $sqlTotalSucursal = "SELECT COUNT(*) as total_general 
                            FROM ventas_franquicias_sin_tango 
                            WHERE nro_sucursal = ?";
        
        $stmtTotalSucursal = $conexionSistemas->prepare($sqlTotalSucursal);
        $stmtTotalSucursal->execute([$nroSucursal]);
        $totalGeneral = $stmtTotalSucursal->fetch(PDO::FETCH_ASSOC);
        
        error_log("  📊 Total registros en tabla para sucursal {$nroSucursal}: " . $totalGeneral['total_general']);
        
    } catch (Exception $e) {
        error_log("  ❌ Error en verificación post-commit: " . $e->getMessage());
    }
    // **FIN VERIFICACIÓN**

    jsonResponse([
        'success' => true,
        'message' => "Importación completada exitosamente",
        'procesados' => $procesados,
        'modo_operacion' => $modo,
        'periodos_afectados' => count($periodosAfectados),
        'resumen_periodos' => $resumenPeriodos,
        'errores_procesamiento' => $erroresProcesamiento,
        'debug_info' => [
            'nro_sucursal' => $nroSucursal,
            'usuario' => $usuario,
            'servidor_insertado' => $serverInfo['servidor'] ?? 'desconocido',
            'base_datos_insertada' => $serverInfo['base_datos'] ?? 'desconocido'
        ]
    ]);

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conexionSistemas) && $conexionSistemas->inTransaction()) {
        $conexionSistemas->rollback();
        error_log("🔄 ROLLBACK ejecutado");
    }
    
    $errorMessage = $e->getMessage();
    error_log("❌ Error en importación múltiples períodos: " . $errorMessage);
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Mejorar formato del mensaje de error para el usuario
    $userMessage = $errorMessage;
    
    // Si el mensaje tiene múltiples líneas, formatearlo mejor
    if (strpos($errorMessage, "\n") !== false) {
        $lineas = explode("\n", $errorMessage);
        $userMessage = implode("<br>", $lineas);
    }
    
    jsonResponse([
        'success' => false,
        'message' => $userMessage
    ]);
}
?>