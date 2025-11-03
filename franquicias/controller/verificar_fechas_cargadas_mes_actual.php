<?php
/**
 * Controlador para verificar fechas ya cargadas en mes actual
 * Detecta overlap de fechas para evitar duplicados
 */

// Suprimir errores para mantener JSON limpio
error_reporting(0);
ini_set('display_errors', '0');

require_once '../config_sesiones_fixed.php';
require_once '../class/AuthFranquicia.php';
require_once '../class/DatabaseConfig.php';

// Establecer header JSON
header('Content-Type: application/json');

// Verificar sesión
if (!AuthFranquicia::verificarSesion()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar acceso especial
if (!AuthFranquicia::verificarAccesoEspecial()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$nroSucursal = $_SESSION['franquicia_id'] ?? '';

if (empty($nroSucursal)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
    exit;
}

// Obtener parámetros (puede ser GET para verificación o POST con rango)
$accion = $_GET['accion'] ?? null;

// Si es POST, leer el JSON del body
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if ($data) {
        $accion = $data['accion'] ?? 'verificar_existentes';
    }
} else {
    $accion = $accion ?? 'verificar_existentes';
}

try {
    $conexionSistemas = DatabaseConfig::conectarSistemas();
    
    if ($accion === 'verificar_rango') {
        // Verificar si hay overlap en un rango específico
        $fechaDesde = $data['fecha_desde'] ?? null;
        $fechaHasta = $data['fecha_hasta'] ?? null;
        
        if (!$fechaDesde || !$fechaHasta) {
            throw new Exception('Fechas no proporcionadas');
        }
        
        // Convertir fechas DD/MM/YYYY a formato SQL (YYYY-MM-DD)
        $fechaDesdeObj = DateTime::createFromFormat('d/m/Y', $fechaDesde);
        $fechaHastaObj = DateTime::createFromFormat('d/m/Y', $fechaHasta);
        
        if (!$fechaDesdeObj || !$fechaHastaObj) {
            throw new Exception('Formato de fecha inválido. Use DD/MM/YYYY');
        }
        
        $fechaDesdeSql = $fechaDesdeObj->format('Y-m-d');
        $fechaHastaSql = $fechaHastaObj->format('Y-m-d');
        
        // Buscar fechas ya cargadas en el rango
        $sql = "SELECT DISTINCT fecha 
                FROM ventas_franquicias_sin_tango
                WHERE nro_sucursal = ? 
                  AND fecha BETWEEN ? AND ?
                  AND fecha IS NOT NULL
                ORDER BY fecha";
        
        $stmt = $conexionSistemas->prepare($sql);
        $stmt->execute([$nroSucursal, $fechaDesdeSql, $fechaHastaSql]);
        $fechasExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Convertir fechas SQL a formato DD/MM/YYYY para mostrar
        $fechasFormateadas = array_map(function($fecha) {
            $fechaObj = new DateTime($fecha);
            return $fechaObj->format('d/m/Y');
        }, $fechasExistentes);
        
        if (count($fechasExistentes) > 0) {
            echo json_encode([
                'success' => true,
                'tiene_overlap' => true,
                'fechas_existentes' => $fechasFormateadas,
                'mensaje' => 'Se encontraron ' . count($fechasExistentes) . ' fecha(s) ya cargada(s) en el rango seleccionado'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'tiene_overlap' => false,
                'fechas_existentes' => [],
                'mensaje' => 'El rango está disponible para carga'
            ]);
        }
        
    } else {
        // Verificar todas las fechas existentes para el mes actual
        $mesActual = date('Y-m-01'); // Primer día del mes
        $mesSiguiente = date('Y-m-01', strtotime('+1 month')); // Primer día del mes siguiente
        
        $sql = "SELECT fecha, codigo_articulo, rubro, categoria_padre, cantidad, fecha_carga
                FROM ventas_franquicias_sin_tango 
                WHERE nro_sucursal = ? 
                  AND fecha >= ?
                  AND fecha < ?
                  AND fecha IS NOT NULL
                ORDER BY fecha DESC, codigo_articulo";
        
        $stmt = $conexionSistemas->prepare($sql);
        $stmt->execute([$nroSucursal, $mesActual, $mesSiguiente]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($registros) > 0) {
            // Obtener descripciones de artículos
            $conexionLaker = DatabaseConfig::conectarLakerSA();
            $codigosArticulos = array_unique(array_column($registros, 'codigo_articulo'));
            
            if (!empty($codigosArticulos)) {
                // **SOLUCIÓN: Procesar en lotes para evitar límite de 2100 parámetros**
                $batchSize = 2000;
                $totalCodigos = count($codigosArticulos);
                $descripciones = [];
                
                if ($totalCodigos > $batchSize) {
                    error_log("⚠️ Obteniendo descripciones en lotes: $totalCodigos códigos");
                }
                
                for ($i = 0; $i < $totalCodigos; $i += $batchSize) {
                    $batch = array_slice($codigosArticulos, $i, $batchSize);
                    
                    $placeholders = str_repeat('?,', count($batch) - 1) . '?';
                    $sqlDescripciones = "SELECT LTRIM(RTRIM(COD_ARTICU)) COLLATE Modern_Spanish_CI_AI as COD_ARTICU, DESCRIPCIO 
                                         FROM STA11 
                                         WHERE LTRIM(RTRIM(COD_ARTICU)) COLLATE Modern_Spanish_CI_AI IN ($placeholders)";
                    $stmtDescripciones = $conexionLaker->prepare($sqlDescripciones);
                    $stmtDescripciones->execute($batch);
                    $descripcionesLote = $stmtDescripciones->fetchAll(PDO::FETCH_KEY_PAIR);
                    
                    // Combinar resultados
                    $descripciones = array_merge($descripciones, $descripcionesLote);
                }
                
                // Agregar descripciones a los registros
                foreach ($registros as &$registro) {
                    $registro['descripcion'] = $descripciones[$registro['codigo_articulo']] ?? 'Descripción no encontrada';
                    
                    // Formatear fecha a DD/MM/YYYY
                    $fechaObj = new DateTime($registro['fecha']);
                    $registro['fecha_formatted'] = $fechaObj->format('d/m/Y');
                }
            }
            
            echo json_encode([
                'success' => true,
                'tiene_datos' => true,
                'registros' => $registros,
                'total_registros' => count($registros),
                'mensaje' => 'Se encontraron ' . count($registros) . ' registro(s) cargado(s) para el mes actual'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'tiene_datos' => false,
                'registros' => [],
                'total_registros' => 0,
                'mensaje' => 'No hay datos cargados para el mes actual'
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("Error verificando fechas mes actual: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error verificando fechas: ' . $e->getMessage()
    ]);
}
?>
