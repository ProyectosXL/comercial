<?php
/**
 * Controlador para procesar importación de ventas mes actual
 * Maneja la inserción de ventas para fechas específicas del mes actual
 */

// Suprimir TODOS los errores/warnings para evitar contaminar el JSON
error_reporting(0);
ini_set('display_errors', '0');

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_clean();
}

require_once '../config_sesiones_fixed.php';
require_once '../class/AuthFranquicia.php';
require_once '../class/DatabaseConfig.php';

// Establecer header JSON inmediatamente
header('Content-Type: application/json');

error_log("✅ PASO 1: Headers y includes cargados correctamente - MES ACTUAL");

// Verificar sesión
if (!AuthFranquicia::verificarSesion()) {
    error_log("❌ Sesión no autenticada");
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

error_log("✅ PASO 2: Sesión verificada - MES ACTUAL");

// Verificar acceso especial
if (!AuthFranquicia::verificarAccesoEspecial()) {
    error_log("❌ Sin acceso especial");
    echo json_encode(['success' => false, 'message' => 'Acceso denegado - sin acceso especial']);
    exit;
}

error_log("✅ PASO 3: Acceso especial verificado - MES ACTUAL");

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Método no POST: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

error_log("=== INICIO PROCESAMIENTO MES ACTUAL ===");

// Leer datos JSON del request
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    error_log("❌ Raw input vacío");
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en el request']);
    exit;
}

error_log("✅ PASO 4: Raw input leído - longitud: " . strlen($rawInput));

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("❌ Error JSON: " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'Error decodificando JSON: ' . json_last_error_msg()]);
    exit;
}

error_log("✅ PASO 5: JSON decodificado correctamente - MES ACTUAL");
error_log("📥 JSON decodificado: " . print_r($input, true));

// Extraer datos del input JSON
$datosOriginales = $input['datos'] ?? [];
if (empty($datosOriginales)) {
    error_log("❌ No se encontraron datos en el input JSON");
    echo json_encode(['success' => false, 'message' => 'No se encontraron datos para procesar']);
    exit;
}

error_log("✅ PASO 6: Datos extraídos - count: " . count($datosOriginales));

/**
 * Función para agrupar artículos duplicados por artículo + fecha
 * Suma las cantidades de registros duplicados
 */
function agruparArticulosDuplicadosMesActual($datos) {
    error_log("🔄 Iniciando agrupación de duplicados mes actual");
    error_log("📊 Datos originales antes de agrupar: " . count($datos));
    
    $agrupados = [];
    $duplicadosEncontrados = 0;
    
    foreach ($datos as $item) {
        // Crear clave única combinando artículo + fecha
        $clave = $item['articulo'] . '_' . $item['fecha'];
        
        if (isset($agrupados[$clave])) {
            // Es un duplicado - sumar cantidad
            $agrupados[$clave]['cantidad'] += intval($item['cantidad']);
            $duplicadosEncontrados++;
            error_log("🔀 Duplicado encontrado: {$item['articulo']} ({$item['fecha']}) - Suma total: {$agrupados[$clave]['cantidad']}");
        } else {
            // Primera ocurrencia - crear nueva entrada
            $agrupados[$clave] = [
                'articulo' => $item['articulo'],
                'cantidad' => intval($item['cantidad']),
                'fecha' => $item['fecha']
            ];
        }
    }
    
    $datosAgrupados = array_values($agrupados);
    
    error_log("📊 Resultado de agrupación mes actual:");
    error_log("  - Registros originales: " . count($datos));
    error_log("  - Registros después de agrupación: " . count($datosAgrupados));
    error_log("  - Duplicados encontrados y agrupados: $duplicadosEncontrados");
    
    return $datosAgrupados;
}

// Agrupar artículos duplicados antes del procesamiento
$datos = agruparArticulosDuplicadosMesActual($datosOriginales);

error_log("✅ PASO 7: Agrupación completada - count original: " . count($datosOriginales) . " -> agrupado: " . count($datos));

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$nroSucursal = $_SESSION['franquicia_id'] ?? '';

if (empty($nroSucursal)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
    exit;
}

try {
    error_log("🔍 DEBUG: Iniciando try principal - MES ACTUAL");
    
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

    // Validar estructura de datos para mes actual
    error_log("🔍 DEBUG: Iniciando validación de estructura");
    $errores = [];
    $fechasValidas = [];
    $articulosValidar = [];
    
    foreach ($datos as $index => $item) {
        error_log("🔍 DEBUG: Procesando item $index en foreach");
        
        // Validar que tenga todas las propiedades necesarias
        if (!isset($item['articulo']) || !isset($item['cantidad']) || !isset($item['fecha'])) {
            $errores[] = "Fila " . ($index + 1) . ": Datos incompletos";
            continue;
        }
        
        // Validar formato de fecha (DD/MM/YYYY)
        if (!preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/[0-9]{4}$/', $item['fecha'])) {
            $errores[] = "Fila " . ($index + 1) . ": Formato de fecha inválido (debe ser DD/MM/YYYY): " . $item['fecha'];
            continue;
        }
        
        // Convertir fecha DD/MM/YYYY a formato SQL (YYYY-MM-DD)
        $partesFecha = explode('/', $item['fecha']); // [DD, MM, YYYY]
        $fechaSql = $partesFecha[2] . '-' . $partesFecha[1] . '-' . $partesFecha[0]; // YYYY-MM-DD
        
        // Validar que la fecha sea válida
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fechaSql);
        if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fechaSql) {
            $errores[] = "Fila " . ($index + 1) . ": Fecha inválida: " . $item['fecha'];
            continue;
        }
        
        error_log("Fecha convertida: {$item['fecha']} -> $fechaSql");
        $fechasValidas[] = $fechaSql;
        
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
    
    $placeholders = str_repeat('?,', count($articulosUnicos) - 1) . '?';
    $sqlValidar = "SELECT COD_ARTICU FROM STA11 WHERE COD_ARTICU IN ($placeholders) AND COD_ARTICU LIKE '[XO]%'";
    
    try {
        $stmtValidar = $conexionLaker->prepare($sqlValidar);
        if (!$stmtValidar) {
            $errorInfo = $conexionLaker->errorInfo();
            throw new Exception("Error preparando validación de artículos: " . implode(" ", $errorInfo));
        }
        
        $ejecutadoValidar = $stmtValidar->execute($articulosUnicos);
        if (!$ejecutadoValidar) {
            $errorInfo = $stmtValidar->errorInfo();
            throw new Exception("Error ejecutando validación de artículos: " . $errorInfo[2]);
        }
        
        $articulosValidos = $stmtValidar->fetchAll(PDO::FETCH_COLUMN);
        error_log("✅ Validación exitosa - Artículos válidos encontrados: " . count($articulosValidos));
        
    } catch (Exception $e) {
        error_log("❌ Error en validación de artículos: " . $e->getMessage());
        throw new Exception("Error validando artículos: " . $e->getMessage());
    }

    // Verificar que todos los artículos sean válidos
    $articulosInvalidos = [];
    foreach ($articulosUnicos as $articulo) {
        if (!in_array($articulo, $articulosValidos)) {
            $articulosInvalidos[] = $articulo;
        }
    }
    
    if (!empty($articulosInvalidos)) {
        error_log("❌ ARTÍCULOS INVÁLIDOS DETECTADOS: " . json_encode($articulosInvalidos));
        throw new Exception("Artículos inválidos encontrados: " . implode(', ', $articulosInvalidos));
    }
    
    error_log("✅ TODOS LOS ARTÍCULOS SON VÁLIDOS - Continuando procesamiento...");
    error_log("🔍 DEBUG: Iniciando creación/verificación de tabla");

    // Crear tabla si no existe (ya incluye columna fecha)
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
        
        -- Agregar columna fecha si no existe
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'fecha')
        BEGIN
            ALTER TABLE ventas_franquicias_sin_tango ADD fecha DATE NULL
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

    // Preparar query de inserción
    // NOTA: Para mes actual, usamos el campo fecha con el valor del Excel
    // El campo periodo lo llenamos con el período del mes actual (formato YYYYMM)
    $periodoMesActual = date('Ym'); // Período del mes actual
    
    $sqlInsertar = "INSERT INTO ventas_franquicias_sin_tango 
                    (nro_sucursal, periodo, codigo_articulo, cantidad, fecha_carga, rubro, categoria_padre, usuario_carga, fecha) 
                    VALUES (?, ?, ?, ?, GETDATE(), ?, ?, ?, ?)";
    
    error_log("🔍 SQL INSERT preparación:");
    error_log("  SQL: $sqlInsertar");
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
    $resumenFechas = [];

    error_log("📋 INICIO PROCESAMIENTO - Total items: " . count($datos));

    foreach ($datos as $index => $item) {
        try {
            $cantidad = intval($item['cantidad']);
            if ($cantidad <= 0) {
                continue; // Saltar cantidades no válidas
            }

            // Convertir fecha DD/MM/YYYY a formato SQL (YYYY-MM-DD)
            $partesFecha = explode('/', $item['fecha']); // [DD, MM, YYYY]
            $fechaSql = $partesFecha[2] . '-' . $partesFecha[1] . '-' . $partesFecha[0]; // YYYY-MM-DD
            
            // Sanitizar código de artículo
            $codigoArticulo = trim($item['articulo']);
            $codigoArticulo = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $codigoArticulo);
            $codigoArticulo = preg_replace('/[^\x20-\x7E]/', '', $codigoArticulo);
            $codigoArticulo = trim($codigoArticulo);
            
            if (empty($codigoArticulo)) {
                throw new Exception("Código de artículo vacío después de sanitización para item $index");
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
                    
                    if (strlen($rubro) > 100) {
                        $rubro = substr($rubro, 0, 97) . '...';
                    }
                    if (strlen($categoria) > 100) {
                        $categoria = substr($categoria, 0, 97) . '...';
                    }
                }
            } catch (Exception $e) {
                error_log("Error obteniendo rubro/categoría para $codigoArticulo: " . $e->getMessage());
            }
            
            // Validación final de parámetros
            $rubro = $rubro ?? 'Sin rubro';
            $categoria = $categoria ?? 'Sin categoría';
            $usuario = $usuario ?? 'Sistema';
            
            // Insertar registro
            $parametros = [
                $nroSucursal,       // placeholder 1: nro_sucursal
                $periodoMesActual,  // placeholder 2: periodo (YYYYMM del mes actual)
                $codigoArticulo,    // placeholder 3: codigo_articulo
                $cantidad,          // placeholder 4: cantidad
                                    // fecha_carga usa GETDATE() - NO es placeholder
                $rubro,             // placeholder 5: rubro
                $categoria,         // placeholder 6: categoria_padre
                $usuario,           // placeholder 7: usuario_carga
                $fechaSql           // placeholder 8: fecha (YYYY-MM-DD del Excel)
            ];
            
            // Validar que ningún parámetro sea null
            foreach ($parametros as $idx => $param) {
                if ($param === null) {
                    error_log("❌ PARÁMETRO NULL en posición $idx para item $index");
                    throw new Exception("Parámetro null detectado en posición $idx para item $index");
                }
            }
            
            $ejecutado = $stmtInsertar->execute($parametros);
            
            if (!$ejecutado) {
                $errorInfo = $stmtInsertar->errorInfo();
                error_log("❌ ERROR EN EXECUTE - Item $index - Artículo: $codigoArticulo, Fecha: {$item['fecha']}: " . print_r($errorInfo, true));
                throw new Exception("Error ejecutando inserción para $codigoArticulo en {$item['fecha']}: " . $errorInfo[2]);
            }
            
            $rowCount = $stmtInsertar->rowCount();
            if ($rowCount === 0) {
                error_log("⚠️ INSERT ejecutado pero 0 filas afectadas - Item $index - Artículo: $codigoArticulo, Fecha: {$item['fecha']}");
                throw new Exception("No se insertó ninguna fila para $codigoArticulo en {$item['fecha']} - Posible violación de constraint UNIQUE");
            }
            
            error_log("✅ INSERT EXITOSO - Item $index - Artículo: $codigoArticulo, Fecha: {$item['fecha']} -> $fechaSql, Filas afectadas: $rowCount");
            
            $procesados++;
            
            // Contar por fecha para resumen
            if (!isset($resumenFechas[$item['fecha']])) {
                $resumenFechas[$item['fecha']] = 0;
            }
            $resumenFechas[$item['fecha']]++;
            
        } catch (Exception $e) {
            $erroresProcesamiento[] = "Fila " . ($index + 1) . ": " . $e->getMessage();
            error_log("Error procesando fila $index: " . $e->getMessage());
        }
    }

    // Confirmar transacción
    $conexionSistemas->commit();
    error_log("✅ COMMIT COMPLETADO - MES ACTUAL");

    echo json_encode([
        'success' => true,
        'message' => "Importación mes actual completada exitosamente",
        'procesados' => $procesados,
        'modo_operacion' => 'mes_actual',
        'fechas_afectadas' => count(array_keys($resumenFechas)),
        'resumen_fechas' => $resumenFechas,
        'errores_procesamiento' => $erroresProcesamiento
    ]);

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conexionSistemas) && $conexionSistemas->inTransaction()) {
        $conexionSistemas->rollback();
    }
    
    error_log("Error en importación mes actual: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error procesando importación: ' . $e->getMessage()
    ]);
}
?>
