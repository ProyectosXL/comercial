<?php
// **TEMPORAL: Habilitar errores para debugging**
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);
ini_set('html_errors', '0');
ini_set('log_errors', '1');

// Iniciar buffer de output INMEDIATAMENTE
ob_start();

// Aumentar límites
ini_set('max_execution_time', '300');
ini_set('memory_limit', '512M');
set_time_limit(300);

// Includes
require_once '../config_sesiones_fixed.php';
require_once '../class/DatabaseConfig.php';

// Descartar CUALQUIER output hasta ahora
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

error_log("✅ Iniciando procesamiento múltiples períodos");

error_log("=== INICIO PROCESAMIENTO MÚLTIPLES PERÍODOS ===");

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
    jsonResponse(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
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
    
    // **SOLUCIÓN: Procesar en lotes para evitar límite de 2100 parámetros de SQL Server**
    $batchSize = 2000; // Límite seguro (2100 - margen de seguridad)
    $totalCodigos = count($articulosUnicos);
    
    if ($totalCodigos > $batchSize) {
        error_log("⚠️ VALIDACION POR LOTES: Procesando $totalCodigos artículos únicos en lotes de $batchSize");
    }
    
    $articulosValidos = [];
    
    error_log("🔍 DEBUG: Antes de try de validación");
    
    try {
        // Dividir en lotes y procesar
        for ($i = 0; $i < $totalCodigos; $i += $batchSize) {
            $batch = array_slice($articulosUnicos, $i, $batchSize);
            $batchNum = floor($i / $batchSize) + 1;
            $totalBatches = ceil($totalCodigos / $batchSize);
            
            error_log("📦 Validando lote $batchNum de $totalBatches (" . count($batch) . " artículos)");
            
            // Crear placeholders para este lote
            $placeholders = str_repeat('?,', count($batch) - 1) . '?';
            
            $sqlValidar = "SELECT COD_ARTICU FROM STA11 WHERE COD_ARTICU IN ($placeholders) AND COD_ARTICU LIKE '[XO]%'";
            
            error_log("🔍 DEBUG: Preparando statement de validación para lote $batchNum");
            $stmtValidar = $conexionLaker->prepare($sqlValidar);
            if (!$stmtValidar) {
                $errorInfo = $conexionLaker->errorInfo();
                throw new Exception("Error preparando validación de artículos (lote $batchNum): " . implode(" ", $errorInfo));
            }
            
            error_log("🔍 DEBUG: Ejecutando statement de validación para lote $batchNum");
            $ejecutadoValidar = $stmtValidar->execute($batch);
            if (!$ejecutadoValidar) {
                $errorInfo = $stmtValidar->errorInfo();
                throw new Exception("Error ejecutando validación de artículos (lote $batchNum): " . $errorInfo[2] . " (SQLSTATE: " . $errorInfo[0] . ")");
            }
            
            error_log("🔍 DEBUG: Obteniendo resultados de validación para lote $batchNum");
            $articulosValidosLote = $stmtValidar->fetchAll(PDO::FETCH_COLUMN);
            
            // Agregar resultados de este lote al array general
            $articulosValidos = array_merge($articulosValidos, $articulosValidosLote);
            
            error_log("✅ Lote $batchNum completado: " . count($articulosValidosLote) . " artículos válidos encontrados");
        }
        
        error_log("✅ Validación exitosa - Total artículos válidos encontrados: " . count($articulosValidos));
        error_log("  Primeros artículos válidos: " . json_encode(array_slice($articulosValidos, 0, 5)));
        
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
            
            // **SOLUCIÓN: Procesar en lotes si hay muchos períodos (poco probable pero seguro)**
            $batchSize = 2000;
            $totalPeriodos = count($periodosAfectados);
            $periodosExistentes = [];
            
            if ($totalPeriodos > $batchSize) {
                error_log("⚠️ Verificando períodos en lotes: $totalPeriodos períodos");
                
                for ($i = 0; $i < $totalPeriodos; $i += $batchSize) {
                    $batch = array_slice($periodosAfectados, $i, $batchSize);
                    $placeholdersPerio = str_repeat('?,', count($batch) - 1) . '?';
                    
                    $sqlPeriodosExist = "SELECT DISTINCT periodo FROM ventas_franquicias_sin_tango 
                                        WHERE nro_sucursal = ? AND periodo IN ($placeholdersPerio)";
                    $stmtPeriodosExist = $conexionSistemas->prepare($sqlPeriodosExist);
                    $stmtPeriodosExist->execute(array_merge([$nroSucursal], $batch));
                    $periodosExistentesLote = $stmtPeriodosExist->fetchAll(PDO::FETCH_COLUMN);
                    
                    $periodosExistentes = array_merge($periodosExistentes, $periodosExistentesLote);
                }
            } else {
                $placeholdersPerio = str_repeat('?,', count($periodosAfectados) - 1) . '?';
                $sqlPeriodosExist = "SELECT DISTINCT periodo FROM ventas_franquicias_sin_tango 
                                    WHERE nro_sucursal = ? AND periodo IN ($placeholdersPerio)";
                $stmtPeriodosExist = $conexionSistemas->prepare($sqlPeriodosExist);
                $stmtPeriodosExist->execute(array_merge([$nroSucursal], $periodosAfectados));
                $periodosExistentes = $stmtPeriodosExist->fetchAll(PDO::FETCH_COLUMN);
            }
            
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
            
            // **SOLUCIÓN: Procesar en lotes si hay muchos períodos**
            $batchSize = 2000;
            $totalPeriodos = count($periodosAfectados);
            
            if ($totalPeriodos > $batchSize) {
                error_log("⚠️ Eliminando períodos en lotes: $totalPeriodos períodos");
                
                for ($i = 0; $i < $totalPeriodos; $i += $batchSize) {
                    $batch = array_slice($periodosAfectados, $i, $batchSize);
                    $batchNum = floor($i / $batchSize) + 1;
                    $totalBatches = ceil($totalPeriodos / $batchSize);
                    
                    error_log("📦 Eliminando lote $batchNum de $totalBatches (" . count($batch) . " períodos)");
                    
                    $placeholdersPerio = str_repeat('?,', count($batch) - 1) . '?';
                    $sqlEliminarPeriodos = "DELETE FROM ventas_franquicias_sin_tango 
                                           WHERE nro_sucursal = ? AND periodo IN ($placeholdersPerio)";
                    
                    $parametrosEliminar = array_merge([$nroSucursal], $batch);
                    
                    $stmtEliminarPeriodos = $conexionSistemas->prepare($sqlEliminarPeriodos);
                    if (!$stmtEliminarPeriodos) {
                        throw new Exception("Error preparando statement de eliminación (lote $batchNum): " . implode(" ", $conexionSistemas->errorInfo()));
                    }
                    
                    $ejecutadoEliminar = $stmtEliminarPeriodos->execute($parametrosEliminar);
                    if (!$ejecutadoEliminar) {
                        $errorInfo = $stmtEliminarPeriodos->errorInfo();
                        throw new Exception("Error ejecutando eliminación (lote $batchNum): " . $errorInfo[2] . " (SQLSTATE: " . $errorInfo[0] . ")");
                    }
                    
                    error_log("✅ Lote $batchNum eliminado");
                }
                
                error_log("✅ Eliminación de todos los lotes completada");
            } else {
                $placeholdersPerio = str_repeat('?,', count($periodosAfectados) - 1) . '?';
                $sqlEliminarPeriodos = "DELETE FROM ventas_franquicias_sin_tango 
                                       WHERE nro_sucursal = ? AND periodo IN ($placeholdersPerio)";
                error_log("SQL de eliminación: $sqlEliminarPeriodos");
                
                $parametrosEliminar = array_merge([$nroSucursal], $periodosAfectados);
                error_log("Parámetros eliminación: " . json_encode($parametrosEliminar));
                error_log("Cantidad parámetros: " . count($parametrosEliminar) . " (debe ser " . (1 + count($periodosAfectados)) . ")");
                
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

    // **OPTIMIZACIÓN CRÍTICA: Pre-cargar TODOS los rubros/categorías en una sola consulta**
    error_log("� PRE-CARGANDO rubros y categorías para optimización...");
    $articulosUnicos = array_values(array_unique(array_column($datos, 'articulo')));
    $rubrosCategoriasCache = [];
    
    // Cargar rubros/categorías en lotes
    $batchSize = 2000;
    $totalArticulos = count($articulosUnicos);
    
    for ($i = 0; $i < $totalArticulos; $i += $batchSize) {
        $batch = array_slice($articulosUnicos, $i, $batchSize);
        $placeholdersRC = str_repeat('?,', count($batch) - 1) . '?';
        
        $sqlRubroCategoria = "SELECT COD_ARTICU, RUBRO, CATEGORIA_PADRE 
                             FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                             WHERE COD_ARTICU IN ($placeholdersRC)";
        $stmtRubroCategoria = $conexionSistemas->prepare($sqlRubroCategoria);
        $stmtRubroCategoria->execute($batch);
        $resultadosRC = $stmtRubroCategoria->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($resultadosRC as $row) {
            $rubrosCategoriasCache[trim($row['COD_ARTICU'])] = [
                'rubro' => trim($row['RUBRO']) ?: 'Sin rubro',
                'categoria' => trim($row['CATEGORIA_PADRE']) ?: 'Sin categoría'
            ];
        }
    }
    
    error_log("✅ Pre-carga completada: " . count($rubrosCategoriasCache) . " artículos con rubro/categoría");

    // Asegurar que usuario tenga un valor válido
    $usuario = $usuario ?? 'Sistema';
    if (strlen($usuario) > 50) {
        $usuario = substr($usuario, 0, 50);
    }

    // **OPTIMIZACIÓN CRÍTICA: Inserción por lotes (batch insert)**
    error_log("🚀 INICIANDO INSERCIÓN POR LOTES...");
    $totalItems = count($datos);
    $procesados = 0;
    $erroresProcesamiento = [];
    $resumenPeriodos = [];
    
    // Tamaño de lote optimizado para SQL Server
    // SQL Server tiene límite de 2100 parámetros
    // Con 7 parámetros por fila: 2100 / 7 = 300 filas máximo
    $batchSize = 250; // Dejamos margen de seguridad
    $totalBatches = ceil($totalItems / $batchSize);
    
    error_log("📊 Configuración de lotes:");
    error_log("  Total items: $totalItems");
    error_log("  Tamaño de lote: $batchSize");
    error_log("  Total lotes: $totalBatches");
    
    // Procesar en lotes
    for ($batchNum = 0; $batchNum < $totalBatches; $batchNum++) {
        $startIdx = $batchNum * $batchSize;
        $endIdx = min($startIdx + $batchSize, $totalItems);
        $batchDatos = array_slice($datos, $startIdx, $batchSize);
        
        error_log("📦 Procesando lote " . ($batchNum + 1) . "/$totalBatches (items $startIdx-" . ($endIdx - 1) . ")");
        
        // Construir INSERT masivo con múltiples VALUES
        $valuesArray = [];
        $parametros = [];
        
        foreach ($batchDatos as $index => $item) {
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
                throw new Exception("Período convertido inválido: {$item['periodo']} -> $periodoDb");
            }
            
            // Sanitización del código de artículo
            $codigoArticulo = trim($item['articulo']);
            
            if (function_exists('mb_convert_encoding')) {
                $codigoArticulo = mb_convert_encoding($codigoArticulo, 'UTF-8', 'UTF-8');
            }
            
            $codigoArticulo = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $codigoArticulo);
            $codigoArticulo = preg_replace('/[^\x20-\x7E]/', '', $codigoArticulo);
            $codigoArticulo = trim($codigoArticulo);
            
            if (empty($codigoArticulo)) {
                throw new Exception("Código de artículo vacío después de sanitización para item $index");
            }
            
            if (strlen($codigoArticulo) > 50) {
                throw new Exception("Código de artículo muy largo: " . $codigoArticulo);
            }
            
            // **OPTIMIZACIÓN: Usar cache pre-cargado en lugar de consultas individuales**
            if (isset($rubrosCategoriasCache[$codigoArticulo])) {
                $rubro = $rubrosCategoriasCache[$codigoArticulo]['rubro'];
                $categoria = $rubrosCategoriasCache[$codigoArticulo]['categoria'];
            } else {
                $rubro = 'Sin rubro';
                $categoria = 'Sin categoría';
            }
            
            // Limitar longitud de campos
            if (strlen($rubro) > 100) {
                $rubro = substr($rubro, 0, 97) . '...';
            }
            if (strlen($categoria) > 100) {
                $categoria = substr($categoria, 0, 97) . '...';
            }
            
            // Agregar VALUES para este registro
            $valuesArray[] = "(?, ?, ?, ?, GETDATE(), ?, ?, ?, NULL)";
            
            // Agregar parámetros en el orden correcto
            $parametros[] = $nroSucursal;
            $parametros[] = $periodoDb;
            $parametros[] = $codigoArticulo;
            $parametros[] = $cantidad;
            $parametros[] = $rubro;
            $parametros[] = $categoria;
            $parametros[] = $usuario;
            
            // Contar por período para resumen
            if (!isset($resumenPeriodos[$item['periodo']])) {
                $resumenPeriodos[$item['periodo']] = 0;
            }
            $resumenPeriodos[$item['periodo']]++;
            
        } catch (Exception $e) {
            $erroresProcesamiento[] = "Fila " . ($startIdx + $index + 1) . ": " . $e->getMessage();
            error_log("❌ Error procesando fila " . ($startIdx + $index) . ": " . $e->getMessage());
        }
    }
    
    // Ejecutar INSERT masivo si hay datos en este lote
    if (!empty($valuesArray)) {
        try {
            $sqlBatch = "INSERT INTO ventas_franquicias_sin_tango 
                        (nro_sucursal, periodo, codigo_articulo, cantidad, fecha_carga, rubro, categoria_padre, usuario_carga, fecha) 
                        VALUES " . implode(", ", $valuesArray);
            
            $stmtBatch = $conexionSistemas->prepare($sqlBatch);
            $ejecutado = $stmtBatch->execute($parametros);
            
            if (!$ejecutado) {
                $errorInfo = $stmtBatch->errorInfo();
                throw new Exception("Error ejecutando lote: " . $errorInfo[2] . " (SQLSTATE: " . $errorInfo[0] . ")");
            }
            
            $procesadosLote = count($valuesArray);
            $procesados += $procesadosLote;
            
            $porcentaje = round(($batchNum + 1) / $totalBatches * 100);
            error_log("✅ Lote " . ($batchNum + 1) . "/$totalBatches completado: $procesadosLote registros insertados ($porcentaje% total)");
            
        } catch (Exception $e) {
            error_log("❌ Error ejecutando lote " . ($batchNum + 1) . ": " . $e->getMessage());
            throw $e; // Re-lanzar para hacer rollback
        }
    }
}

error_log("✅ INSERCIÓN POR LOTES COMPLETADA - Total procesados: $procesados de $totalItems");
            
            // Log de progreso cada 5% (menos verboso)
            if ($index % $logEach === 0 || $index === $totalItems - 1) {
                $porcentaje = round(($index + 1) / $totalItems * 100);
                error_log("� Progreso: $porcentaje% ($procesados/$totalItems registros procesados)");
            }
            
        } catch (Exception $e) {
            $erroresProcesamiento[] = "Fila " . ($index + 1) . ": " . $e->getMessage();
            error_log("❌ Error procesando fila $index: " . $e->getMessage());
        }
    }
    
    error_log("✅ PROCESAMIENTO COMPLETADO - Total procesados: $procesados de " . count($datos));

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

    jsonResponse([
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
    
    jsonResponse([
        'success' => false,
        'message' => 'Error procesando importación: ' . $e->getMessage()
    ]);
}
?>
