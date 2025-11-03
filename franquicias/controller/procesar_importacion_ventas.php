<?php
// Incluir configuración de sesiones y autenticación
require_once '../config_sesiones_fixed.php';
require_once '../class/AuthFranquicia.php';
require_once '../class/DatabaseConfig.php';

// Verificar autenticación
if (!AuthFranquicia::verificarSesion()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea usuario de franquicia con acceso especial
if (($_SESSION['user_role'] ?? '') !== 'franquicia' || !AuthFranquicia::verificarAccesoEspecial()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Obtener datos de la solicitud
$input = json_decode(file_get_contents('php://input'), true);
$datosOriginales = $input['datos'] ?? [];

if (empty($datosOriginales)) {
    echo json_encode(['success' => false, 'message' => 'No hay datos para procesar']);
    exit;
}

/**
 * Función para agrupar artículos duplicados (solo por artículo para carga individual)
 * Suma las cantidades de registros duplicados
 */
function agruparArticulosDuplicadosIndividual($datos) {
    error_log("🔄 Iniciando agrupación individual de duplicados en servidor");
    error_log("📊 Datos originales antes de agrupar: " . count($datos));
    
    $agrupados = [];
    $duplicadosEncontrados = 0;
    
    foreach ($datos as $item) {
        // Para carga individual, solo agrupamos por artículo (no hay columna período)
        $clave = $item['articulo'];
        
        if (isset($agrupados[$clave])) {
            // Es un duplicado - sumar cantidad
            $agrupados[$clave]['cantidad'] += intval($item['cantidad']);
            $duplicadosEncontrados++;
            error_log("🔀 Duplicado individual encontrado: {$item['articulo']} - Suma total: {$agrupados[$clave]['cantidad']}");
        } else {
            // Primera ocurrencia - crear nueva entrada
            $agrupados[$clave] = [
                'articulo' => $item['articulo'],
                'cantidad' => intval($item['cantidad'])
            ];
        }
    }
    
    $datosAgrupados = array_values($agrupados);
    
    error_log("📊 Resultado de agrupación individual:");
    error_log("  - Registros originales: " . count($datos));
    error_log("  - Registros después de agrupación: " . count($datosAgrupados));
    error_log("  - Duplicados encontrados y agrupados: $duplicadosEncontrados");
    
    return $datosAgrupados;
}

// **NUEVA FUNCIONALIDAD: Agrupar artículos duplicados antes del procesamiento**
$datos = agruparArticulosDuplicadosIndividual($datosOriginales);

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$nroSucursal = $_SESSION['franquicia_id'] ?? ''; // Este es el NRO_SUCURS que necesitamos

if (empty($nroSucursal)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
    exit;
}

// Calcular el período anterior (YYYYMM)
$periodoActual = date('Ym', strtotime('-1 month')); // Formato: mes anterior al actual

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
    // Conectar a ambas bases de datos
    $conexionLaker = DatabaseConfig::conectarLakerSA(); // Para validar artículos
    $conexionSistemas = DatabaseConfig::conectarSistemas(); // Para guardar ventas
    
    // Nota: PDO::ATTR_TIMEOUT no es compatible con pdo_sqlsrv

    // Iniciar transacción en sistemas
    $conexionSistemas->beginTransaction();

    // **VALIDACIÓN: Verificar que no haya filas vacías o inválidas**
    $erroresValidacion = [];
    foreach ($datos as $index => $item) {
        $fila = $index + 1;
        
        // Verificar que exista el campo articulo y no esté vacío
        if (!isset($item['articulo']) || trim($item['articulo']) === '') {
            $erroresValidacion[] = "Fila $fila: El código de artículo está vacío";
        }
        
        // Verificar que exista el campo cantidad
        if (!isset($item['cantidad'])) {
            $erroresValidacion[] = "Fila $fila: La cantidad está vacía";
        } elseif (!is_numeric($item['cantidad']) || intval($item['cantidad']) < 0) {
            $erroresValidacion[] = "Fila $fila: La cantidad debe ser un número positivo (valor: " . ($item['cantidad'] ?? 'null') . ")";
        }
    }
    
    if (!empty($erroresValidacion)) {
        throw new Exception("Errores en el archivo Excel:\n" . implode("\n", $erroresValidacion));
    }

    // **NUEVA FUNCIONALIDAD: Normalizar códigos de artículos (trim, uppercase y eliminar TODOS los espacios)**
    foreach ($datos as &$item) {
        $original = $item['articulo'];
        // 1. Trim de espacios al inicio/final
        // 2. Convertir a mayúsculas
        // 3. Eliminar TODOS los espacios (incluso internos) para coincidir con base
        $item['articulo'] = strtoupper(preg_replace('/\s+/', '', trim($item['articulo'])));
        error_log("📝 Artículo normalizado: '$original' → '" . $item['articulo'] . "' (length: " . strlen($item['articulo']) . ")");
    }
    unset($item); // Romper la referencia
    
    $articulosValidar = array_column($datos, 'articulo');
    
    error_log("🔍 VALIDACION BACKEND: Artículos a validar: " . json_encode($articulosValidar));
    
    // **SOLUCIÓN: Procesar en lotes para evitar límite de 2100 parámetros de SQL Server**
    $batchSize = 2000; // Límite seguro (2100 - margen de seguridad)
    $totalCodigos = count($articulosValidar);
    
    if ($totalCodigos > $batchSize) {
        error_log("⚠️ VALIDACION POR LOTES: Procesando $totalCodigos artículos en lotes de $batchSize");
    }
    
    $articulosValidos = [];
    
    // Dividir en lotes y procesar
    for ($i = 0; $i < $totalCodigos; $i += $batchSize) {
        $batch = array_slice($articulosValidar, $i, $batchSize);
        $batchNum = floor($i / $batchSize) + 1;
        $totalBatches = ceil($totalCodigos / $batchSize);
        
        error_log("📦 Validando lote $batchNum de $totalBatches (" . count($batch) . " artículos)");
        
        $placeholders = str_repeat('?,', count($batch) - 1) . '?';
        
        // Normalizar también en la consulta SQL para garantizar coincidencia (REPLACE elimina espacios internos)
        $sqlValidar = "SELECT REPLACE(LTRIM(RTRIM(COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI as COD_ARTICU 
                       FROM STA11 
                       WHERE REPLACE(LTRIM(RTRIM(COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI IN ($placeholders) 
                         AND COD_ARTICU LIKE '[XO]%'";
        $stmtValidar = $conexionLaker->prepare($sqlValidar);
        $stmtValidar->execute($batch);
        $articulosValidosLote = $stmtValidar->fetchAll(PDO::FETCH_COLUMN);
        
        // Agregar resultados de este lote
        $articulosValidos = array_merge($articulosValidos, $articulosValidosLote);
        
        error_log("✅ Lote $batchNum completado: " . count($articulosValidosLote) . " artículos válidos");
    }

    error_log("🔍 Total artículos válidos encontrados: " . count($articulosValidos));
    error_log("🔍 Artículos válidos: " . json_encode($articulosValidos));

    // Verificar que todos los artículos sean válidos
    $articulosInvalidos = [];
    foreach ($datos as $item) {
        if (!in_array($item['articulo'], $articulosValidos)) {
            $articulosInvalidos[] = $item['articulo'];
            error_log("❌ Artículo NO encontrado en STA11: " . $item['articulo']);
        }
    }
    
    if (!empty($articulosInvalidos)) {
        $mensajeError = "Artículos inválidos: " . implode(', ', $articulosInvalidos);
        error_log("❌ ERROR VALIDACION: " . $mensajeError);
        throw new Exception($mensajeError);
    }

    // Crear la tabla si no existe en sistemas y asegurar que tenga todas las columnas
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
        
        -- Agregar columnas que podrían faltar si la tabla ya existía
        IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ventas_franquicias_sin_tango' AND COLUMN_NAME = 'id')
        BEGIN
            -- Solo agregar la columna sin PRIMARY KEY para evitar conflictos
            -- Si la tabla no tiene PK, se agregará después
            ALTER TABLE ventas_franquicias_sin_tango ADD id int IDENTITY(1,1)
        END
        
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
    ";
    $conexionSistemas->exec($sqlCrearTabla);

    // Eliminar registros existentes de esta sucursal para el período actual (reemplazar datos)
    $sqlEliminar = "DELETE FROM ventas_franquicias_sin_tango WHERE nro_sucursal = ? AND periodo = ?";
    $stmtEliminar = $conexionSistemas->prepare($sqlEliminar);
    $stmtEliminar->execute([$nroSucursal, $periodoActual]);

    // **OPTIMIZACIÓN: Obtener TODOS los rubros/categorías en UNA sola consulta**
    error_log("📊 Obteniendo rubros y categorías para " . count($articulosValidos) . " artículos...");
    $rubrosCategoriasCache = [];
    
    if (!empty($articulosValidos)) {
        $batchSizeRC = 2000;
        for ($i = 0; $i < count($articulosValidos); $i += $batchSizeRC) {
            $batchRC = array_slice($articulosValidos, $i, $batchSizeRC);
            $placeholdersRC = str_repeat('?,', count($batchRC) - 1) . '?';
            
            $sqlRubroCategoria = "SELECT 
                                    REPLACE(LTRIM(RTRIM(COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI as COD_ARTICU,
                                    RUBRO, 
                                    CATEGORIA_PADRE 
                                  FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                                  WHERE REPLACE(LTRIM(RTRIM(COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI IN ($placeholdersRC)";
            
            $stmtRubroCategoria = $conexionSistemas->prepare($sqlRubroCategoria);
            $stmtRubroCategoria->execute($batchRC);
            $resultadosRC = $stmtRubroCategoria->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($resultadosRC as $row) {
                $rubrosCategoriasCache[trim($row['COD_ARTICU'])] = [
                    'rubro' => trim($row['RUBRO']) ?: 'Sin rubro',
                    'categoria' => trim($row['CATEGORIA_PADRE']) ?: 'Sin categoría'
                ];
            }
        }
    }
    error_log("✅ Cache de rubros/categorías creado: " . count($rubrosCategoriasCache) . " artículos");

    // Preparar query para insertar nuevos datos - usar solo las columnas que sabemos que existen
    // NOTA: fecha = NULL para carga individual (mes anterior)
    $sqlInsertar = "INSERT INTO ventas_franquicias_sin_tango 
                    (nro_sucursal, periodo, codigo_articulo, cantidad, fecha_carga, rubro, categoria_padre, usuario_carga, fecha) 
                    VALUES (?, ?, ?, ?, GETDATE(), ?, ?, ?, NULL)";
    $stmtInsertar = $conexionSistemas->prepare($sqlInsertar);

    $procesados = 0;
    foreach ($datos as $item) {
        $cantidad = intval($item['cantidad']);
        if ($cantidad > 0) { // Solo insertar cantidades positivas
            $codigoArticulo = $item['articulo'];
            
            // Obtener rubro y categoría del CACHE
            $rubro = 'Sin rubro';
            $categoria = 'Sin categoría';
            
            if (isset($rubrosCategoriasCache[$codigoArticulo])) {
                $rubro = $rubrosCategoriasCache[$codigoArticulo]['rubro'];
                $categoria = $rubrosCategoriasCache[$codigoArticulo]['categoria'];
            }
            
            // Insertar el registro
            $stmtInsertar->execute([
                $nroSucursal,
                $periodoActual,
                $codigoArticulo,
                $cantidad,
                $rubro,
                $categoria,
                $usuario
            ]);
            $procesados++;
        }
    }

    // Confirmar transacción
    $conexionSistemas->commit();

    error_log("DEBUG IMPORTACION: Procesados $procesados registros para sucursal $nroSucursal en período $periodoActual");

    echo json_encode([
        'success' => true,
        'message' => 'Importación completada exitosamente',
        'procesados' => $procesados,
        'nro_sucursal' => $nroSucursal,
        'periodo' => $periodoActual,
        'tabla' => 'ventas_franquicias_sin_tango'
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conexionSistemas) && $conexionSistemas->inTransaction()) {
        $conexionSistemas->rollback();
    }
    
    $errorMessage = $e->getMessage();
    error_log("❌ Error procesando importación: " . $errorMessage);
    
    // Mejorar formato del mensaje de error para el usuario
    $userMessage = $errorMessage;
    
    // Si el mensaje tiene múltiples líneas, formatearlo mejor
    if (strpos($errorMessage, "\n") !== false) {
        $lineas = explode("\n", $errorMessage);
        $userMessage = implode("<br>", $lineas);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $userMessage
    ]);
}
?>
