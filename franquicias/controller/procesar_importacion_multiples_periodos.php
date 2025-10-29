<?php
/**
 * Controlador para procesar importación de múltiples períodos
 * Maneja la inserción de ventas para diferentes períodos en una sola operación
 */

// Suprimir TODOS los errores/warnings para evitar contaminar el JSON
error_reporting(0);
ini_set('display_errors', '0');

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_clean();
}

require_once '../config_sesiones_fixed.php';
require_once '../class/DatabaseConfig.php';

// Establecer header JSON inmediatamente
header('Content-Type: application/json');

error_log("✅ PASO 1: Headers y includes cargados correctamente");

// Verificar sesión
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    error_log("❌ Sesión no autenticada");
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

error_log("✅ PASO 2: Sesión verificada");

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Método no POST: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

error_log("✅ PASO 3: Método POST verificado");

error_log("✅ PASO 3: Método POST verificado");

error_log("=== INICIO PROCESAMIENTO MÚLTIPLES PERÍODOS ===");

// Debug: Log de datos recibidos
$rawInputPreview = substr(file_get_contents('php://input'), 0, 200) . '...';
error_log("📥 DATOS RECIBIDOS - Raw input preview: " . $rawInputPreview);

error_log("✅ PASO 4: Input preview obtenido");

// Leer datos JSON del request
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    error_log("❌ Raw input vacío");
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en el request']);
    exit;
}

error_log("✅ PASO 5: Raw input leído - longitud: " . strlen($rawInput));

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("❌ Error JSON: " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'Error decodificando JSON: ' . json_last_error_msg()]);
    exit;
}

error_log("✅ PASO 6: JSON decodificado correctamente");

error_log("📥 JSON decodificado: " . print_r($input, true));

// Extraer datos del input JSON
$datos = $input['datos'] ?? [];
if (empty($datos)) {
    error_log("❌ No se encontraron datos en el input JSON");
    echo json_encode(['success' => false, 'message' => 'No se encontraron datos para procesar']);
    exit;
}

error_log("✅ PASO 7: Datos extraídos - count: " . count($datos));

/**
 * Función para agrupar artículos duplicados por artículo + período
 * Suma las cantidades de registros duplicados
 */
function agruparArticulosDuplicados($datos) {
    error_log("🔄 Iniciando agrupación de duplicados en servidor");
    error_log("📊 Datos originales antes de agrupar: " . count($datos));
    
    $agrupados = [];
    $duplicadosEncontrados = 0;
    
    foreach ($datos as $index => $item) {
        // Crear clave única combinando artículo + período
        $clave = $item['articulo'] . '_' . $item['periodo'];
        
        if (isset($agrupados[$clave])) {
            // Es un duplicado - sumar cantidad
            $agrupados[$clave]['cantidad'] += intval($item['cantidad']);
            $duplicadosEncontrados++;
            error_log("🔀 Duplicado encontrado: {$item['articulo']} ({$item['periodo']}) - Suma total: {$agrupados[$clave]['cantidad']}");
        } else {
            // Primera ocurrencia - crear nueva entrada
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
    error_log("  - Duplicados encontrados y agrupados: $duplicadosEncontrados");
    
    return $datosAgrupados;
}

// **NUEVA FUNCIONALIDAD: Agrupar artículos duplicados antes del procesamiento**
$datosOriginales = $datos;
$datos = agruparArticulosDuplicados($datosOriginales);

error_log("✅ PASO 7A: Agrupación completada - count original: " . count($datosOriginales) . " -> agrupado: " . count($datos));

// **VALIDACIÓN ESPECÍFICA PARA DATOS CORREGIDOS**
// Detectar si hay datos que vinieron de correcciones JavaScript
$hayDatosCorregidos = false;
foreach ($datos as $item) {
    // Verificar si hay caracteres que sugieren conversión de JavaScript
    if (isset($item['periodo']) && is_string($item['periodo'])) {
        // Detectar posibles residuos de normalización JavaScript
        if (strpos($item['periodo'], '44') === 0 && is_numeric($item['periodo'])) {
            error_log("🔍 FECHA EXCEL DETECTADA sin convertir: " . $item['periodo']);
            $hayDatosCorregidos = true;
        }
        
        // Verificar encoding del período
        if (!mb_check_encoding($item['periodo'], 'UTF-8')) {
            error_log("❌ ENCODING INVÁLIDO en período: " . bin2hex($item['periodo']));
            $hayDatosCorregidos = true;
        }
    }
    
    if (isset($item['articulo']) && is_string($item['articulo'])) {
        // Verificar encoding del artículo
        if (!mb_check_encoding($item['articulo'], 'UTF-8')) {
            error_log("❌ ENCODING INVÁLIDO en artículo: " . bin2hex($item['articulo']));
            $hayDatosCorregidos = true;
        }
        
        // Detectar caracteres de control que pueden venir de correcciones JS
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $item['articulo'])) {
            error_log("❌ CARACTERES DE CONTROL en artículo: " . bin2hex($item['articulo']));
            $hayDatosCorregidos = true;
        }
    }
}

if ($hayDatosCorregidos) {
    error_log("⚠️ SE DETECTARON DATOS CON CORRECCIONES - Usando modo de procesamiento especial");
    
    // Sanitizar datos que vienen de correcciones JavaScript (versión simplificada)
    foreach ($datos as $index => &$item) {
        // Limpiar período - solo trim y básico
        if (isset($item['periodo'])) {
            $item['periodo'] = trim($item['periodo']);
        }
        
        // Limpiar artículo - solo trim y uppercase
        if (isset($item['articulo'])) {
            $item['articulo'] = trim(strtoupper($item['articulo']));
        }
        
        // Asegurar que cantidad sea integer
        if (isset($item['cantidad'])) {
            $item['cantidad'] = intval($item['cantidad']);
        }
        
        error_log("🧹 Item $index sanitizado: " . json_encode($item));
    }
    unset($item); // Romper referencia
}

// Extraer modo de operación
$modo = $input['modo'] ?? 'nueva';

// **DEBUG CRÍTICO: Verificar modo recibido**
error_log("MODO RECIBIDO: " . $modo);
error_log("INPUT COMPLETO: " . json_encode($input));
if ($modo === 'reemplazar') {
    error_log("CONFIRMADO: Es modo reemplazar");
} else {
    error_log("ATENCION: NO es modo reemplazar, es: " . $modo);
}

error_log("📋 RESUMEN INICIAL:");
error_log("  Input recibido: " . strlen($rawInput) . " caracteres");
error_log("  Datos count: " . count($datos));
error_log("  Modo: " . $modo);
error_log("  Hay datos corregidos: " . ($hayDatosCorregidos ? 'SÍ' : 'NO'));

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$nroSucursal = $_SESSION['franquicia_id'] ?? '';

if (empty($nroSucursal)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
    exit;
}

try {
    error_log("🔍 DEBUG: Iniciando try principal");
    
    // Variables de control
    $eliminarTodoAlFinal = false;
    
    // Conectar a ambas bases de datos
    error_log("🔍 DEBUG: Antes de conectar bases de datos");
    $conexionLaker = DatabaseConfig::conectarLakerSA();
    error_log("🔍 DEBUG: Conexión LAKER exitosa");
    
    $conexionSistemas = DatabaseConfig::conectarSistemas();
    error_log("🔍 DEBUG: Conexión Sistemas exitosa");
    
    // Iniciar transacción
    error_log("🔍 DEBUG: Antes de iniciar transacción");
    $conexionSistemas->beginTransaction();
    error_log("🔍 DEBUG: Transacción iniciada");

    // Validar estructura de datos para múltiples períodos
    error_log("🔍 DEBUG: Iniciando validación de estructura");
    $errores = [];
    $periodosValidos = [];
    $articulosValidar = [];
    
    error_log("🔍 DEBUG: Antes del foreach de validación - datos count: " . count($datos));
    
    foreach ($datos as $index => $item) {
        error_log("🔍 DEBUG: Procesando item $index en foreach");
        
        // Debug logging
        error_log("Procesando fila $index: " . json_encode($item));
        
        // Validar que tenga todas las propiedades necesarias
        if (!isset($item['articulo']) || !isset($item['cantidad']) || !isset($item['periodo'])) {
            $errores[] = "Fila " . ($index + 1) . ": Datos incompletos";
            continue;
        }
        
        error_log("🔍 DEBUG: Item $index - validación de formato de período");
        
        // Validar formato de período (DD/MM/YYYY - primer día del mes)
        if (!preg_match('/^01\/(0[1-9]|1[0-2])\/[0-9]{4}$/', $item['periodo'])) {
            $errores[] = "Fila " . ($index + 1) . ": Formato de período inválido (debe ser 01/MM/YYYY): " . $item['periodo'];
            continue;
        }
        
        error_log("🔍 DEBUG: Item $index - conversión de período");
        
        // Convertir período DD/MM/YYYY a formato YYYYMM para base de datos
        $partesPeriodo = explode('/', $item['periodo']); // [01, MM, YYYY]
        $periodoDb = $partesPeriodo[2] . $partesPeriodo[1]; // YYYYMM (año + mes)
        error_log("Período convertido: {$item['periodo']} -> $periodoDb");
        $periodosValidos[] = $periodoDb;
        
        // Recopilar artículos únicos para validar
        $articulosValidar[] = $item['articulo'];
    }
    
    error_log("🔍 DEBUG: Foreach completado - errores: " . count($errores));

    if (!empty($errores)) {
        error_log("🔍 DEBUG: Hay errores de validación, lanzando excepción");
        throw new Exception("Errores de validación: " . implode(', ', $errores));
    }

    error_log("🔍 DEBUG: Iniciando validación de artículos");
    
    // Validar artículos únicos en LAKER_SA
    $articulosUnicos = array_values(array_unique($articulosValidar));
    
    error_log("🔍 VALIDACIÓN DE ARTÍCULOS - Iniciando...");
    error_log("  Artículos únicos a validar: " . json_encode($articulosUnicos));
    error_log("  Total artículos únicos: " . count($articulosUnicos));
    
    if (empty($articulosUnicos)) {
        error_log("🔍 DEBUG: No hay artículos para validar, lanzando excepción");
        throw new Exception("No hay artículos para validar");
    }
    
    error_log("🔍 DEBUG: Preparando consulta de validación");
    
    $placeholders = str_repeat('?,', count($articulosUnicos) - 1) . '?';
    
    $sqlValidar = "SELECT COD_ARTICU FROM STA11 WHERE COD_ARTICU IN ($placeholders) AND COD_ARTICU LIKE '[XO]%'";
    error_log("  SQL de validación: $sqlValidar");
    error_log("  Placeholders count: " . substr_count($sqlValidar, '?') . " vs parámetros: " . count($articulosUnicos));
    
    error_log("🔍 DEBUG: Antes de try de validación");
    
    try {
        error_log("🔍 DEBUG: Preparando statement de validación");
        $stmtValidar = $conexionLaker->prepare($sqlValidar);
        if (!$stmtValidar) {
            $errorInfo = $conexionLaker->errorInfo();
            throw new Exception("Error preparando validación de artículos: " . implode(" ", $errorInfo));
        }
        
        error_log("🔍 DEBUG: Ejecutando statement de validación");
        error_log("🔍 DEBUG: Parámetros a pasar: " . json_encode($articulosUnicos));
        error_log("🔍 DEBUG: Tipo de array: " . gettype($articulosUnicos) . " - Count: " . count($articulosUnicos));
        $ejecutadoValidar = $stmtValidar->execute($articulosUnicos);
        if (!$ejecutadoValidar) {
            $errorInfo = $stmtValidar->errorInfo();
            throw new Exception("Error ejecutando validación de artículos: " . $errorInfo[2] . " (SQLSTATE: " . $errorInfo[0] . ")");
        }
        
        error_log("🔍 DEBUG: Obteniendo resultados de validación");
        $articulosValidos = $stmtValidar->fetchAll(PDO::FETCH_COLUMN);
        error_log("✅ Validación exitosa - Artículos válidos encontrados: " . count($articulosValidos));
        error_log("  Artículos válidos: " . json_encode($articulosValidos));
        
    } catch (Exception $e) {
        error_log("❌ Error en validación de artículos: " . $e->getMessage());
        throw new Exception("Error validando artículos: " . $e->getMessage());
    }

    error_log("🔍 DEBUG: Validación estricta de artículos");
    
    // **VALIDACIÓN ESTRICTA COMO EL CONTROLADOR INDIVIDUAL**
    // Verificar que todos los artículos sean válidos (no permitir inválidos)
    $articulosInvalidos = [];
    foreach ($articulosUnicos as $articulo) {
        if (!in_array($articulo, $articulosValidos)) {
            $articulosInvalidos[] = $articulo;
        }
    }
    
    if (!empty($articulosInvalidos)) {
        error_log("❌ ARTÍCULOS INVÁLIDOS DETECTADOS: " . json_encode($articulosInvalidos));
        throw new Exception("Artículos inválidos encontrados: " . implode(', ', $articulosInvalidos) . ". Todos los artículos deben ser corregidos antes de continuar.");
    }
    
    error_log("✅ TODOS LOS ARTÍCULOS SON VÁLIDOS - Continuando procesamiento...");
    error_log("🔍 DEBUG: Iniciando creación de tabla");

    // Crear tabla si no existe con todas las columnas necesarias
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
        
        -- Verificar y agregar columnas faltantes
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'usuario_carga')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD usuario_carga varchar(50) NULL
        END
        
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'rubro')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD rubro varchar(100) NULL
        END
        
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'categoria_padre')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD categoria_padre varchar(100) NULL
        END
        
        -- Agregar columna fecha si no existe (para carga individual mes actual)
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'fecha')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD fecha DATE NULL
        END
        
        -- Asegurar que fecha_carga tenga valor por defecto
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'fecha_carga' AND COLUMN_DEFAULT IS NOT NULL)
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD CONSTRAINT DF_ventas_franquicias_sin_tango_fecha_carga DEFAULT GETDATE() FOR fecha_carga
        END
    ";
    
    error_log("🏗️ Ejecutando creación/verificación de tabla...");
    try {
        $conexionSistemas->exec($sqlCrearTabla);
        error_log("✅ Tabla verificada/creada exitosamente");
    } catch (Exception $e) {
        error_log("❌ Error en creación de tabla: " . $e->getMessage());
        throw new Exception("Error preparando estructura de tabla: " . $e->getMessage());
    }

    // Lógica según el modo de operación
    $periodosAfectados = array_values(array_unique($periodosValidos)); // array_values para reindexar
    
    error_log("🔍 DEBUG PERÍODOS:");
    error_log("  periodosValidos: " . json_encode($periodosValidos));
    error_log("  periodosAfectados: " . json_encode($periodosAfectados));
    error_log("  count periodosAfectados: " . count($periodosAfectados));
    
    if (empty($periodosAfectados)) {
        throw new Exception("No hay períodos válidos para procesar");
    }
    
    switch ($modo) {
        case 'reemplazar':
        case 'reemplazar_todo':
            error_log("=== MODO REEMPLAZAR ACTIVADO ===");
            error_log("ENTRANDO EN CASE REEMPLAZAR - Modo: " . $modo);
            
            // DEBUGGING CRÍTICO: Verificar valores exactos
            error_log("🔍 VALORES PARA ELIMINACIÓN:");
            error_log("  nroSucursal: '$nroSucursal' (tipo: " . gettype($nroSucursal) . ", longitud: " . strlen($nroSucursal) . ")");
            error_log("  periodosAfectados: " . json_encode($periodosAfectados));
            
            // Crear lista de períodos únicos que vamos a reemplazar
            $periodosParaEliminar = array_unique($periodosAfectados);
            error_log("  periodosParaEliminar: " . json_encode($periodosParaEliminar));
            
            // VERIFICACIÓN PREVIA: ¿Existen realmente registros?
            error_log("🔍 VERIFICACIÓN PREVIA - ¿Existen registros para eliminar?");
            foreach ($periodosParaEliminar as $periodo) {
                $sqlVerificarExistencia = "SELECT COUNT(*) as total, 
                                                MIN(id) as min_id, 
                                                MAX(id) as max_id,
                                                MIN(fecha_carga) as fecha_min,
                                                MAX(fecha_carga) as fecha_max
                                        FROM ventas_franquicias_sin_tango 
                                        WHERE nro_sucursal = ? AND periodo = ?";
                
                $stmtVerificar = $conexionSistemas->prepare($sqlVerificarExistencia);
                $stmtVerificar->execute([$nroSucursal, $periodo]);
                $infoExistente = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
                
                error_log("📊 PERÍODO $periodo - Registros existentes:");
                error_log("    Total: " . ($infoExistente['total'] ?? 0));
                error_log("    IDs: " . ($infoExistente['min_id'] ?? 'N/A') . " - " . ($infoExistente['max_id'] ?? 'N/A'));
                error_log("    Fechas: " . ($infoExistente['fecha_min'] ?? 'N/A') . " - " . ($infoExistente['fecha_max'] ?? 'N/A'));
                
                if ($infoExistente['total'] == 0) {
                    error_log("⚠️ ADVERTENCIA: No hay registros para eliminar en período $periodo");
                }
            }
            
            // ELIMINACIÓN CON MÁXIMO DEBUGGING
            error_log("🗑️ INICIANDO ELIMINACIÓN DETALLADA PARA MODO REEMPLAZAR");
            error_log("=== EJECUTANDO ELIMINACIONES PARA CADA PERÍODO ===");
            $totalRegistrosEliminados = 0;
            
            foreach ($periodosParaEliminar as $periodo) {
                error_log("🗑️ === PROCESANDO ELIMINACIÓN PARA PERÍODO: $periodo ===");
                
                // PASO 1: Contar registros antes de eliminar
                $sqlContarRegistros = "SELECT COUNT(*) FROM ventas_franquicias_sin_tango 
                                    WHERE nro_sucursal = ? AND periodo = ?";
                $stmtContar = $conexionSistemas->prepare($sqlContarRegistros);
                
                if (!$stmtContar) {
                    $errorInfo = $conexionSistemas->errorInfo();
                    throw new Exception("Error preparando COUNT: " . implode(" ", $errorInfo));
                }
                
                $ejecutadoContar = $stmtContar->execute([$nroSucursal, $periodo]);
                if (!$ejecutadoContar) {
                    $errorInfo = $stmtContar->errorInfo();
                    throw new Exception("Error ejecutando COUNT: " . $errorInfo[2]);
                }
                
                $countPeriodo = $stmtContar->fetchColumn();
                error_log("📊 ANTES DE ELIMINAR - Período $periodo: $countPeriodo registros");
                
                if ($countPeriodo == 0) {
                    error_log("⚠️ SALTANDO eliminación - No hay registros en período $periodo");
                    continue;
                }
                
                // PASO 2: Mostrar algunos registros que se van a eliminar (para debugging)
                $sqlMostrarEjemplos = "SELECT TOP 3 id, codigo_articulo, cantidad, fecha_carga 
                                    FROM ventas_franquicias_sin_tango 
                                    WHERE nro_sucursal = ? AND periodo = ?";
                $stmtEjemplos = $conexionSistemas->prepare($sqlMostrarEjemplos);
                $stmtEjemplos->execute([$nroSucursal, $periodo]);
                $ejemplos = $stmtEjemplos->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("📋 EJEMPLOS DE REGISTROS A ELIMINAR:");
                foreach ($ejemplos as $ej) {
                    error_log("    ID: {$ej['id']}, Artículo: {$ej['codigo_articulo']}, Cantidad: {$ej['cantidad']}, Fecha: {$ej['fecha_carga']}");
                }
                
                // PASO 3: ELIMINAR CON DEBUGGING EXTREMO
                $sqlEliminarPeriodo = "DELETE FROM ventas_franquicias_sin_tango 
                                    WHERE nro_sucursal = ? AND periodo = ?";
                
                error_log("🔥 EJECUTANDO DELETE:");
                error_log("    SQL: $sqlEliminarPeriodo");
                error_log("    Parámetros: ['{$nroSucursal}', '{$periodo}']");
                
                $stmtEliminarPeriodo = $conexionSistemas->prepare($sqlEliminarPeriodo);
                
                if (!$stmtEliminarPeriodo) {
                    $errorInfo = $conexionSistemas->errorInfo();
                    throw new Exception("Error preparando DELETE: " . implode(" ", $errorInfo));
                }
                
                // EJECUTAR LA ELIMINACIÓN
                $ejecutadoEliminacion = $stmtEliminarPeriodo->execute([$nroSucursal, $periodo]);
                
                if (!$ejecutadoEliminacion) {
                    $errorInfo = $stmtEliminarPeriodo->errorInfo();
                    error_log("❌ ERROR EN DELETE: " . print_r($errorInfo, true));
                    throw new Exception("Error eliminando período $periodo: " . $errorInfo[2] . " (SQLSTATE: " . $errorInfo[0] . ")");
                }
                
                // VERIFICAR RESULTADO DE LA ELIMINACIÓN
                $registrosEliminados = $stmtEliminarPeriodo->rowCount();
                $totalRegistrosEliminados += $registrosEliminados;
                
                error_log("🗑️ RESULTADO DELETE - Período $periodo:");
                error_log("    Registros que DEBÍAN eliminarse: $countPeriodo");
                error_log("    Registros EFECTIVAMENTE eliminados: $registrosEliminados");
                error_log("    ¿Coinciden? " . ($countPeriodo == $registrosEliminados ? 'SÍ' : 'NO'));
                
                // VERIFICACIÓN FINAL INMEDIATA - ¿Realmente se eliminaron?
                $sqlVerificarFinal = "SELECT COUNT(*) FROM ventas_franquicias_sin_tango 
                                    WHERE nro_sucursal = ? AND periodo = ?";
                $stmtVerificarFinal = $conexionSistemas->prepare($sqlVerificarFinal);
                $stmtVerificarFinal->execute([$nroSucursal, $periodo]);
                $quedanRegistros = $stmtVerificarFinal->fetchColumn();
                
                error_log("🔍 VERIFICACIÓN FINAL INMEDIATA - Período $periodo:");
                error_log("    Registros que QUEDAN después del DELETE: $quedanRegistros");
                
                if ($quedanRegistros > 0) {
                    error_log("❌ PROBLEMA CRÍTICO: Aún quedan $quedanRegistros registros después de DELETE");
                    error_log("❌ ESTO INDICA QUE LA ELIMINACIÓN NO FUNCIONÓ CORRECTAMENTE");
                    
                    // INTENTAR ELIMINACIÓN FORZADA CON COMMIT INMEDIATO
                    error_log("🔄 INTENTANDO ELIMINACIÓN FORZADA CON COMMIT...");
                    
                    // Hacer commit de la transacción actual para asegurar que el DELETE se aplique
                    $conexionSistemas->commit();
                    
                    // Iniciar nueva transacción
                    $conexionSistemas->beginTransaction();
                    
                    // Intentar DELETE forzado
                    $sqlEliminarForzado = "DELETE FROM ventas_franquicias_sin_tango 
                                        WHERE nro_sucursal = ? AND periodo = ?";
                    $stmtForzado = $conexionSistemas->prepare($sqlEliminarForzado);
                    $ejecutadoForzado = $stmtForzado->execute([$nroSucursal, $periodo]);
                    
                    if ($ejecutadoForzado) {
                        $eliminadosForzados = $stmtForzado->rowCount();
                        error_log("� ELIMINACIÓN FORZADA: $eliminadosForzados registros eliminados");
                        
                        // Verificar nuevamente
                        $stmtVerificarForzado = $conexionSistemas->prepare($sqlVerificarFinal);
                        $stmtVerificarForzado->execute([$nroSucursal, $periodo]);
                        $quedanDespuesForzado = $stmtVerificarForzado->fetchColumn();
                        
                        if ($quedanDespuesForzado > 0) {
                            error_log("❌ FALLO CRÍTICO: Incluso después de eliminación forzada quedan $quedanDespuesForzado registros");
                            throw new Exception("No se pudo eliminar completamente el período $periodo. Quedan $quedanDespuesForzado registros.");
                        } else {
                            error_log("✅ ELIMINACIÓN FORZADA EXITOSA: Período $periodo completamente limpio");
                        }
                    }
                } else {
                    error_log("✅ CONFIRMADO: Período $periodo completamente eliminado");
                }
            }
            
            error_log("📊 RESUMEN ELIMINACIÓN TOTAL:");
            error_log("  Total registros eliminados: $totalRegistrosEliminados");
            error_log("  Períodos procesados: " . count($periodosParaEliminar));
            error_log("=== ELIMINACIÓN COMPLETADA EXITOSAMENTE ===");
            
            $eliminarTodoAlFinal = false; // Ya eliminamos
            break;
            
        case 'agregar':
        case 'solo_faltantes':
            // Obtener períodos existentes para esta sucursal
            error_log("📋 MODO AGREGAR: Verificando períodos existentes");
            $placeholdersPerio = str_repeat('?,', count($periodosAfectados) - 1) . '?';
            $sqlPeriodosExist = "SELECT DISTINCT periodo FROM ventas_franquicias_sin_tango 
                                WHERE nro_sucursal = ? AND periodo IN ($placeholdersPerio)";
            $stmtPeriodosExist = $conexionSistemas->prepare($sqlPeriodosExist);
            $stmtPeriodosExist->execute(array_merge([$nroSucursal], $periodosAfectados));
            $periodosExistentes = $stmtPeriodosExist->fetchAll(PDO::FETCH_COLUMN);
            
            error_log("Períodos existentes encontrados: " . json_encode($periodosExistentes));
            
            // Filtrar datos para solo incluir períodos faltantes
            $datosOriginales = $datos;
            $datos = array_filter($datos, function($item) use ($periodosExistentes) {
                $partesPeriodo = explode('/', $item['periodo']); // [01, MM, YYYY]
                $periodoDb = $partesPeriodo[2] . $partesPeriodo[1]; // YYYYMM (año + mes)
                return !in_array($periodoDb, $periodosExistentes);
            });
            
            error_log("Datos filtrados: " . count($datosOriginales) . " -> " . count($datos));
            
            if (empty($datos)) {
                $conexionSistemas->commit(); // Confirmar transacción aunque no hay datos que insertar
                echo json_encode([
                    'success' => true,
                    'message' => 'No hay períodos faltantes para insertar. Todos los períodos ya están cargados.',
                    'procesados' => 0,
                    'periodos_existentes' => $periodosExistentes,
                    'modo_operacion' => 'Solo períodos faltantes',
                    'resumen_periodos' => []
                ]);
                exit;
            }
            $eliminarTodoAlFinal = false;
            break;
            
        case 'nueva':
        default:
            // Eliminar solo los períodos específicos que se van a importar
            error_log("🗑️ INICIANDO ELIMINACIÓN DE PERÍODOS EXISTENTES");
            error_log("Períodos afectados para eliminar: " . json_encode($periodosAfectados));
            if (empty($periodosAfectados)) {
                throw new Exception("No hay períodos válidos para eliminar en modo nueva");
            }
            
            // Validar que todos los períodos sean strings válidos
            foreach ($periodosAfectados as $periodo) {
                if (!is_string($periodo) || !preg_match('/^[0-9]{6}$/', $periodo)) {
                    throw new Exception("Período inválido para SQL: " . json_encode($periodo));
                }
            }
            
            $placeholdersPerio = str_repeat('?,', count($periodosAfectados) - 1) . '?';
            $sqlEliminarPeriodos = "DELETE FROM ventas_franquicias_sin_tango 
                                   WHERE nro_sucursal = ? AND periodo IN ($placeholdersPerio)";
            error_log("SQL de eliminación: $sqlEliminarPeriodos");
            
            $parametrosEliminar = array_merge([$nroSucursal], $periodosAfectados);
            error_log("Parámetros eliminación: " . json_encode($parametrosEliminar));
            error_log("Cantidad parámetros: " . count($parametrosEliminar) . " (debe ser " . (1 + count($periodosAfectados)) . ")");
            
            try {
                $stmtEliminarPeriodos = $conexionSistemas->prepare($sqlEliminarPeriodos);
                if (!$stmtEliminarPeriodos) {
                    throw new Exception("Error preparando statement de eliminación: " . implode(" ", $conexionSistemas->errorInfo()));
                }
                
                $ejecutadoEliminar = $stmtEliminarPeriodos->execute($parametrosEliminar);
                if (!$ejecutadoEliminar) {
                    $errorInfo = $stmtEliminarPeriodos->errorInfo();
                    throw new Exception("Error ejecutando eliminación: " . $errorInfo[2] . " (SQLSTATE: " . $errorInfo[0] . ")");
                }
                
                error_log("✅ Eliminación de períodos exitosa");
            } catch (Exception $e) {
                error_log("❌ Error en eliminación: " . $e->getMessage());
                throw new Exception("Error en eliminación de períodos: " . $e->getMessage());
            }
            break;
    }

    // VERIFICACIÓN DE ESTADO DE TRANSACCIÓN
    error_log("🔍 ESTADO TRANSACCIÓN después de eliminación:");
    error_log("  En transacción: " . ($conexionSistemas->inTransaction() ? 'SÍ' : 'NO'));

    // Si hay transacción activa, verificar que los cambios sean visibles
    if ($conexionSistemas->inTransaction()) {
        error_log("⚠️ ADVERTENCIA: Transacción activa - los cambios no son definitivos hasta COMMIT");
        
        // Verificar cuántos registros quedan en total para esta sucursal
        $sqlTotalSucursal = "SELECT COUNT(*) FROM ventas_franquicias_sin_tango WHERE nro_sucursal = ?";
        $stmtTotalSucursal = $conexionSistemas->prepare($sqlTotalSucursal);
        $stmtTotalSucursal->execute([$nroSucursal]);
        $totalRegistrosSucursal = $stmtTotalSucursal->fetchColumn();
        
        error_log("📊 TOTAL REGISTROS para sucursal $nroSucursal: $totalRegistrosSucursal");
    }

    // **TIMESTAMP DE REFERENCIA PARA MODO REEMPLAZAR**
    $timestampReemplazar = null;
    if (isset($eliminarTodoAlFinal) && $eliminarTodoAlFinal === true) {
        $timestampReemplazar = date('Y-m-d H:i:s');
        error_log("🕒 MODO REEMPLAZAR: Timestamp de referencia establecido: $timestampReemplazar");
    }

    // Preparar query de inserción (MISMA ESTRUCTURA QUE EL ARCHIVO INDIVIDUAL)
    // NOTA: fecha = NULL para carga múltiples períodos (trabaja con columna periodo)
    $sqlInsertar = "INSERT INTO ventas_franquicias_sin_tango 
                    (nro_sucursal, periodo, codigo_articulo, cantidad, fecha_carga, rubro, categoria_padre, usuario_carga, fecha) 
                    VALUES (?, ?, ?, ?, GETDATE(), ?, ?, ?, NULL)";
    
    // DEBUGGING: Contar placeholders manualmente
    $placeholdersEnInsert = substr_count($sqlInsertar, '?');
    error_log("🔍 SQL INSERT preparación:");
    error_log("  SQL: $sqlInsertar");
    error_log("  Placeholders encontrados: $placeholdersEnInsert");
    error_log("  Placeholders esperados: 8");
    
    try {
        $stmtInsertar = $conexionSistemas->prepare($sqlInsertar);
        if (!$stmtInsertar) {
            $errorInfo = $conexionSistemas->errorInfo();
            error_log("❌ Error preparando INSERT: " . print_r($errorInfo, true));
            throw new Exception("Error preparando statement de inserción: " . implode(" ", $errorInfo));
        }
        error_log("✅ Statement INSERT preparado exitosamente");
    } catch (Exception $e) {
        error_log("❌ Excepción preparando INSERT: " . $e->getMessage());
        throw new Exception("Error en preparación SQL: " . $e->getMessage());
    }

    // Procesar datos
    $procesados = 0;
    $erroresProcesamiento = [];
    $resumenPeriodos = [];

    error_log("📋 INICIO PROCESAMIENTO - Total items: " . count($datos));
    error_log("📋 MODO OPERACIÓN: $modo");
    error_log("📋 ELIMINAR AL FINAL: " . ($eliminarTodoAlFinal ? 'SÍ' : 'NO'));
    error_log("📋 PRIMER ITEM: " . print_r($datos[0] ?? 'NO HAY ITEMS', true));

    foreach ($datos as $index => $item) {
        if ($modo === 'reemplazar' || $modo === 'reemplazar_todo') {
            error_log("🔄 MODO REEMPLAZAR - PROCESANDO ITEM $index: " . print_r($item, true));
        }
        
        try {
            $cantidad = intval($item['cantidad']);
            if ($cantidad <= 0) {
                continue; // Saltar cantidades no válidas
            }

            // Convertir período DD/MM/YYYY a YYYYMM para base de datos
            $partesPeriodo = explode('/', $item['periodo']); // [01, MM, YYYY]
            $periodoDb = $partesPeriodo[2] . $partesPeriodo[1]; // YYYYMM (año + mes)
            
            // Validar que el período convertido sea numérico y tenga formato correcto
            if (!preg_match('/^[0-9]{6}$/', $periodoDb)) {
                error_log("❌ PERÍODO INVÁLIDO: '{$item['periodo']}' -> '$periodoDb'");
                throw new Exception("Período convertido inválido: {$item['periodo']} -> $periodoDb");
            }
            
            // **SANITIZACIÓN ESPECÍFICA PARA SQL SERVER**
            // SQL Server puede tener problemas con ciertos caracteres Unicode
            $codigoArticulo = trim($item['articulo']);
            
            // Sanitización segura (verificar disponibilidad de funciones)
            if (function_exists('mb_convert_encoding')) {
                // Convertir a UTF-8 limpio y remover caracteres problemáticos
                $codigoArticulo = mb_convert_encoding($codigoArticulo, 'UTF-8', 'UTF-8');
            }
            
            // Remover caracteres de control y caracteres problemáticos para SQL Server
            $codigoArticulo = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $codigoArticulo);
            
            // Asegurar que solo contenga caracteres ASCII seguros para SQL Server
            $codigoArticulo = preg_replace('/[^\x20-\x7E]/', '', $codigoArticulo);
            
            // Re-aplicar trim después de la limpieza
            $codigoArticulo = trim($codigoArticulo);
            
            // Verificar que el artículo no quedó vacío después de la sanitización
            if (empty($codigoArticulo)) {
                throw new Exception("Código de artículo vacío después de sanitización para item $index");
            }
            
            error_log("🧹 Artículo sanitizado para item $index: '{$item['articulo']}' -> '$codigoArticulo'");
            
            // VALIDACIÓN BÁSICA (sin funciones que puedan no estar disponibles)
            // Validar longitudes y asegurar valores no null
            if (strlen($codigoArticulo) > 50) {
                throw new Exception("Código de artículo muy largo: " . $codigoArticulo);
            }
            
            if (strlen($nroSucursal) > 10) {
                throw new Exception("Número de sucursal muy largo: " . $nroSucursal);
            }
            
            // Asegurar que usuario no sea null y tenga una longitud válida
            $usuario = $usuario ?? 'Sistema';  // Valor por defecto si es null
            if (strlen($usuario) > 50) {
                $usuario = substr($usuario, 0, 50); // Truncar si es necesario
            }
            
            // Obtener rubro y categoría
            $rubro = 'Sin rubro';
            $categoria = 'Sin categoría';
            
            try {
                $sqlRubroCategoria = "SELECT RUBRO, CATEGORIA_PADRE 
                                     FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                                     WHERE COD_ARTICU = ?";
                $stmtRubroCategoria = $conexionSistemas->prepare($sqlRubroCategoria);
                $stmtRubroCategoria->execute([$codigoArticulo]);
                $datosAdicionales = $stmtRubroCategoria->fetch(PDO::FETCH_ASSOC);
                
                if ($datosAdicionales) {
                    $rubro = trim($datosAdicionales['RUBRO']) ?: 'Sin rubro';
                    $categoria = trim($datosAdicionales['CATEGORIA_PADRE']) ?: 'Sin categoría';
                    
                    // Limitar longitud de campos y asegurar que no sean null
                    if (strlen($rubro) > 100) {
                        $rubro = substr($rubro, 0, 97) . '...';
                    }
                    if (strlen($categoria) > 100) {
                        $categoria = substr($categoria, 0, 97) . '...';
                    }
                }
            } catch (Exception $e) {
                error_log("Error obteniendo rubro/categoría para $codigoArticulo: " . $e->getMessage());
                // Mantener valores por defecto
            }
            
            // Validación final: asegurar que ningún campo crítico sea null
            $rubro = $rubro ?? 'Sin rubro';
            $categoria = $categoria ?? 'Sin categoría';
            $codigoArticulo = $codigoArticulo ?? '';
            $periodoDb = $periodoDb ?? '';
            $nroSucursal = $nroSucursal ?? '';
            $usuario = $usuario ?? 'Sistema';
            
            // Insertar registro
            $parametros = [
                $nroSucursal,      // placeholder 1: nro_sucursal
                $periodoDb,        // placeholder 2: periodo
                $codigoArticulo,   // placeholder 3: codigo_articulo
                $cantidad,         // placeholder 4: cantidad
                                   // fecha_carga usa GETDATE() - NO es placeholder
                $rubro,            // placeholder 5: rubro
                $categoria,        // placeholder 6: categoria_padre
                $usuario,          // placeholder 7: usuario_carga
                                   // fecha = NULL - NO es placeholder (valor literal NULL)
            ];
            
            // Validar que ningún parámetro sea null antes de ejecutar
            foreach ($parametros as $idx => $param) {
                if ($param === null) {
                    error_log("❌ PARÁMETRO NULL en posición $idx para item $index");
                    throw new Exception("Parámetro null detectado en posición $idx para item $index");
                }
            }
            
            error_log("🔥 ANTES DE EJECUTAR INSERT - Item $index:");
            error_log("  SQL: $sqlInsertar");
            error_log("  Parámetros (" . count($parametros) . "): " . print_r($parametros, true));
            error_log("  Placeholders esperados: 8");
            
            // Verificar estado de la conexión antes de ejecutar
            try {
                $conexionSistemas->query("SELECT 1");
                error_log("✅ Conexión verificada para item $index");
            } catch (Exception $e) {
                error_log("❌ CONEXIÓN PERDIDA antes del item $index: " . $e->getMessage());
                throw new Exception("Conexión a base de datos perdida: " . $e->getMessage());
            }
            
            // Contar manualmente los placeholders en el SQL
            $placeholdersEnSQL = substr_count($sqlInsertar, '?');
            error_log("  Placeholders encontrados en SQL: $placeholdersEnSQL");
            
            if (count($parametros) !== $placeholdersEnSQL) {
                throw new Exception("MISMATCH: SQL tiene $placeholdersEnSQL placeholders pero se proporcionan " . count($parametros) . " parámetros");
            }
            
            $ejecutado = $stmtInsertar->execute($parametros);
            
            if ($modo === 'reemplazar' || $modo === 'reemplazar_todo') {
                error_log("🔥 MODO REEMPLAZAR - RESULTADO INSERT item $index: " . ($ejecutado ? 'EXITOSO' : 'FALLIDO'));
            }
            
            if (!$ejecutado) {
                $errorInfo = $stmtInsertar->errorInfo();
                error_log("❌ ERROR EN EXECUTE - Item $index: " . print_r($errorInfo, true));
                
                // Si el error es relacionado con el statement, intentar recrearlo
                if ($errorInfo[0] === '07002' || strpos($errorInfo[2], 'COUNT field') !== false) {
                    error_log("🔄 Intentando recrear statement por error COUNT field...");
                    try {
                        $stmtInsertar = $conexionSistemas->prepare($sqlInsertar);
                        $ejecutado = $stmtInsertar->execute($parametros);
                        if ($ejecutado) {
                            error_log("✅ Statement recreado y ejecutado exitosamente");
                        } else {
                            $errorInfo2 = $stmtInsertar->errorInfo();
                            throw new Exception("Error persistente después de recrear statement: " . $errorInfo2[2] . " (SQLSTATE: " . $errorInfo2[0] . ")");
                        }
                    } catch (Exception $e) {
                        throw new Exception("Error recreando statement: " . $e->getMessage());
                    }
                } else {
                    throw new Exception("Error ejecutando inserción: " . $errorInfo[2] . " (SQLSTATE: " . $errorInfo[0] . ")");
                }
            }
            
            error_log("✅ INSERT EXITOSO - Item $index");
            
            if ($modo === 'reemplazar' || $modo === 'reemplazar_todo') {
                error_log("🔥 MODO REEMPLAZAR - Item $index insertado exitosamente");
            }
            
            $procesados++;
            
            // Contar por período para resumen
            if (!isset($resumenPeriodos[$item['periodo']])) {
                $resumenPeriodos[$item['periodo']] = 0;
            }
            $resumenPeriodos[$item['periodo']]++;
            
        } catch (Exception $e) {
            $erroresProcesamiento[] = "Fila " . ($index + 1) . ": " . $e->getMessage();
            error_log("Error procesando fila $index: " . $e->getMessage());
        }
    }

    // **NOTA: La eliminación en modo reemplazar ahora se hace ANTES de insertar**
    // Esta sección ya no es necesaria porque eliminamos primero
    /*
    if (isset($eliminarTodoAlFinal) && $eliminarTodoAlFinal === true) {
        // Esta lógica se movió al inicio, antes de las inserciones
        error_log("ℹ️ Eliminación ya realizada antes de las inserciones");
    }
        error_log("🗑️ MODO REEMPLAZAR: Eliminando SOLO datos antiguos, manteniendo nuevos datos insertados");
        try {
            // ESTRATEGIA MÁS PRECISA: Eliminar registros por períodos específicos que acabamos de procesar
            // Esto garantiza que solo eliminamos los datos de los períodos que estamos reemplazando
            
            // Crear lista de períodos únicos de los datos que acabamos de insertar
            $periodosParaEliminar = array_unique($periodosAfectados);
            error_log("� Períodos que serán reemplazados: " . implode(', ', $periodosParaEliminar));
            
            foreach ($periodosParaEliminar as $periodo) {
                // Eliminar TODOS los registros de este período específico para esta sucursal
                // (modo reemplazar completo)
                $sqlEliminarPeriodo = "DELETE FROM ventas_franquicias_sin_tango 
                                      WHERE nro_sucursal = ? 
                                      AND periodo = ?";
                
                $stmtEliminarPeriodo = $conexionSistemas->prepare($sqlEliminarPeriodo);
                $ejecutadoEliminacion = $stmtEliminarPeriodo->execute([$nroSucursal, $periodo]);
                
                if (!$ejecutadoEliminacion) {
                    $errorInfo = $stmtEliminarPeriodo->errorInfo();
                    throw new Exception("Error eliminando período $periodo: " . $errorInfo[2]);
                }
                
                $registrosEliminados = $stmtEliminarPeriodo->rowCount();
                error_log("🗑️ Período $periodo: $registrosEliminados registros eliminados (TODOS)");
            }
            
            error_log("✅ REEMPLAZAR COMPLETADO: Todos los períodos antiguos eliminados correctamente");
            
        } catch (Exception $e) {
            error_log("❌ Error en eliminación modo reemplazar: " . $e->getMessage());
            throw new Exception("Error reemplazando datos existentes: " . $e->getMessage());
        }
    }
    */

    // Confirmar transacción
    if ($modo === 'reemplazar' || $modo === 'reemplazar_todo') {
        error_log("🔥 MODO REEMPLAZAR - ANTES DEL COMMIT:");
        error_log("  Total procesados: $procesados");
        error_log("  Eliminar al final: " . ($eliminarTodoAlFinal ? 'SÍ' : 'NO'));
        error_log("  Resumen períodos: " . json_encode($resumenPeriodos));
    }
    
    $conexionSistemas->commit();
    
    if ($modo === 'reemplazar' || $modo === 'reemplazar_todo') {
        error_log("✅ MODO REEMPLAZAR - COMMIT COMPLETADO");
    }

    echo json_encode([
        'success' => true,
        'message' => "Importación múltiples períodos completada exitosamente",
        'procesados' => $procesados,
        'modo_operacion' => $modo,
        'periodos_afectados' => count($periodosAfectados),
        'resumen_periodos' => $resumenPeriodos,
        'errores_procesamiento' => $erroresProcesamiento
    ]);

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conexionSistemas)) {
        $conexionSistemas->rollback();
    }
    
    error_log("Error en importación múltiples períodos: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error procesando importación: ' . $e->getMessage()
    ]);
}
?>
