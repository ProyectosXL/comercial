<?php
// controller/iniciar_consulta.php
session_start();

// Suprimir cualquier salida de error no deseada para asegurar una respuesta JSON limpia
// Los errores se registrarán en el archivo de log en su lugar.
ini_set('display_errors', 0); 
error_reporting(0);

// Definir un archivo de log para nuestra depuración en la misma carpeta que este script
$logFile = __DIR__ . '/debug_iniciar_consulta.log';
// Limpiar el log anterior para esta nueva ejecución (opcional, puedes quitar esta línea si quieres un historial largo)
// file_put_contents($logFile, ''); 

// Escribir la cabecera de la nueva entrada en el log
file_put_contents($logFile, "--- " . date('Y-m-d H:i:s') . " --- Petición iniciada ---\n", FILE_APPEND);

// Función de manejo de errores personalizada para capturar todo y enviarlo al log
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile) {
    if (!(error_reporting() & $errno)) {
        // Este error no está incluido en error_reporting
        return false;
    }
    $errorMsg = "Error en PHP: [$errno] $errstr en $errfile en la línea $errline\n";
    file_put_contents($logFile, $errorMsg, FILE_APPEND);
    // No interrumpir la ejecución, solo registrar el error
    return true; 
}, E_ALL);

// Validamos que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response = ['success' => false, 'error' => 'Método no permitido'];
    file_put_contents($logFile, "Error: Método no permitido. Respuesta: " . json_encode($response) . "\n", FILE_APPEND);
    echo json_encode($response);
    exit;
}

// Generamos un ID único para esta tarea y el nombre del archivo de resultados
$taskId = uniqid('query_', true);
$resultsFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $taskId . '.json';
file_put_contents($logFile, "TaskId: $taskId, Archivo de resultado será: $resultsFile\n", FILE_APPEND);

// Respondemos al navegador INMEDIATAMENTE con un JSON limpio

// Desactivar cualquier compresión que pueda interferir con el flush
if (ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 'Off');
}

// 1. Preparar la respuesta JSON
$response = json_encode(['success' => true, 'taskId' => $taskId]);

// 2. Limpiar cualquier buffer de salida existente
while (ob_get_level() > 0) {
    ob_end_clean();
}

// 3. Establecer las cabeceras HTTP
header('Content-Type: application/json');
header('Connection: close');
header('Content-Length: ' . strlen($response));

// 4. Iniciar un nuevo buffer de salida
ob_start();

// 5. Poner la respuesta en el buffer
echo $response;

// 6. Finalizar el buffer y enviar todo al navegador
ob_end_flush();
flush();

// 7. Cerrar la conexión y continuar con el script en segundo plano
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
ignore_user_abort(true);
// --- EMPIEZA EL TRABAJO PESADO EN SEGUNDO PLANO ---

require_once '../Class/Articulo.php';

// Aumentamos los límites para el proceso en segundo plano
ini_set('max_execution_time', 300); // 5 minutos
ini_set('memory_limit', '512M');

try {
    // Función para escapar strings de SQL de forma simple
    function escape_sql_string($str) {
        if (is_null($str)) return '';
        if (!is_string($str)) $str = (string)$str;
        return str_replace("'", "''", $str);
    }

    // Función para construir las condiciones del WHERE
    function buildCondition($values, $fieldName) {
        if (!is_array($values) || empty($values) || in_array('%', $values)) {
            return "1=1"; 
        }
        
        $conditions = [];
        $hasNull = false;
        $regularValues = [];
        
        foreach ($values as $value) {
            if ($value === 'NULL') {
                $hasNull = true;
                if ($fieldName === 'DESTINO') $regularValues[] = 'Sin Destino';
                elseif ($fieldName === 'TEMPORADA') $regularValues[] = 'Sin Temporada';
                elseif ($fieldName === 'RUBRO') $regularValues[] = 'Sin Rubro';
            } else {
                $regularValues[] = escape_sql_string($value);
            }
        }
        
        if (!empty($regularValues)) {
            $conditions[] = "$fieldName IN ('" . implode("','", $regularValues) . "')";
        }
        
        if ($hasNull) {
            $conditions[] = "$fieldName IS NULL";
        }
        
        if (empty($conditions)) return "1=1";

        return "(" . implode(" OR ", $conditions) . ")";
    }

    // Recoger los filtros enviados por POST
    $rubros = isset($_POST['rubro']) ? $_POST['rubro'] : ['%'];
    $categorias = isset($_POST['categoria']) ? $_POST['categoria'] : ['%'];
    $temporadas = isset($_POST['temporada']) ? $_POST['temporada'] : ['%'];
    $destinos = isset($_POST['destino']) ? $_POST['destino'] : ['%'];

    // Construir la cláusula WHERE
    $condiciones = [];
    $condiciones[] = buildCondition($rubros, 'RUBRO');
    $condiciones[] = buildCondition($categorias, 'CATEGORIA');
    $condiciones[] = buildCondition($temporadas, 'TEMPORADA');
    $condiciones[] = buildCondition($destinos, 'DESTINO');
    
    $whereSQL = implode(" AND ", $condiciones);
    file_put_contents($logFile, "Cláusula WHERE generada: $whereSQL\n", FILE_APPEND);
    
    // Instanciar la clase y ejecutar la consulta
    $articulo = new Articulo();
    $todosLosResultados = $articulo->traerProductosExportacion($whereSQL);
    $numResultados = is_array($todosLosResultados) ? count($todosLosResultados) : 0;
    file_put_contents($logFile, "Consulta a la BD ejecutada. Se encontraron $numResultados filas.\n", FILE_APPEND);

    // Formatear la fecha para cada fila
    $dataFormatted = [];
    foreach ($todosLosResultados as $row) {
        $fecha = '';
        if (isset($row['FECHA']) && $row['FECHA'] instanceof DateTime) {
            $fecha = $row['FECHA']->format('d/m/Y');
        }
        // Asignar al nombre de columna que espera el JS
        $row['FECHA_INGRESO_SUC'] = $fecha;
        $dataFormatted[] = $row;
    }
    
    // Guardar los resultados finales en el archivo temporal
    file_put_contents($resultsFile, json_encode([
        'status' => 'complete',
        'data' => $dataFormatted
    ]));
    file_put_contents($logFile, "Archivo de resultados '$resultsFile' escrito correctamente.\n", FILE_APPEND);

} catch (Exception $e) {
    // Si ocurre una excepción en cualquier punto, la registramos en el log
    // y creamos un archivo de resultado con el estado de error.
    $errorMsg = "EXCEPCIÓN ATRAPADA: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
    file_put_contents($logFile, $errorMsg, FILE_APPEND);
    
    file_put_contents($resultsFile, json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]));
}

// Marcar el final del proceso en el log
file_put_contents($logFile, "--- Script en segundo plano finalizado ---\n\n", FILE_APPEND);
?>