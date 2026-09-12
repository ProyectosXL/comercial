
<?php
session_start();
require_once __DIR__ . '/../Class/Gasto.php';
require_once __DIR__ . '/../Class/GastoNew.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['username'])) {
        throw new Exception("No autorizado: la sesión expiró. Volvé a iniciar sesión.");
    }

    $supervisoraSesion = $_SESSION['descLocal'] ?? null;
    if (empty($supervisoraSesion)) {
        throw new Exception("No autorizado: la sesión no tiene una supervisora asociada.");
    }

    $gastoNew = new GastoNew();
    $gasto = new Gasto();

    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error decodificando JSON: " . json_last_error_msg());
    }

    $semana = $data['semana'] ?? null;
    $anio = intval($data['anio']) ?? null;
    $gastos = $data['gastos'] ?? null;
    $supervisora = $data['supervisora'] ?? null;
    $importeAdelanto = $data['importeAdelanto'] ?? null;
    $validacion = $data['validacion'] ?? null;

    if (empty($semana) || empty($anio) || empty($gastos) || empty($supervisora)) {
        throw new Exception("Datos incompletos: semana, año, gastos o supervisora faltantes.");
    }

    // Nadie puede cargar gastos a nombre de otra supervisora.
    $normalizar = function ($nombre) {
        return strtoupper(trim($nombre));
    };
    if ($normalizar($supervisora) !== $normalizar($supervisoraSesion)) {
        error_log("guardarGastosNew.php: intento de carga cruzada. Sesión=$supervisoraSesion, payload=$supervisora");
        throw new Exception("No autorizado: solo podés cargar gastos a tu propio nombre.");
    }

    // A partir de acá vale la sesión, no el payload.
    $supervisora = $supervisoraSesion;

    /**
     * Un período está cerrado si ya pasó (misma regla que el combo de la
     * pantalla, compartida en Gasto::esPeriodoCerradoPorFecha) o si ya tiene
     * gastos vigentes cargados. En cualquiera de los dos casos hace falta una
     * reapertura habilitada, que se consume dentro de la transacción del insert.
     *
     * Hasta esta versión el controller no validaba nada de esto: cualquier POST
     * insertaba en cualquier semana.
     */
    $gastosExistentes = $gasto->obtenerGastosExistentes($semana, $anio, $supervisora);
    $periodoCerrado = Gasto::esPeriodoCerradoPorFecha($semana, $anio) || !empty($gastosExistentes);

    // Insertar gastos con estado automático basado en validación de presupuesto.
    // El adelanto y el consumo de la reapertura van en la misma transacción.
    $resultado = $gastoNew->insertarGastosConValidacion(
        $semana,
        $anio,
        $gastos,
        $supervisora,
        $validacion,
        $_SESSION['username'],
        $importeAdelanto,
        $periodoCerrado
    );

    if (is_string($resultado) && json_decode($resultado) !== null) {
        $resultado = json_decode($resultado, true);
    }

    if (is_array($resultado) && isset($resultado['success']) && $resultado['success'] === true) {
        echo json_encode([
            'success' => true,
            'message' => $resultado['message'] ?? 'Gastos guardados correctamente',
            'gastosAprobados' => $resultado['gastos_aprobados'] ?? 0,
            'gastosExcedidos' => $resultado['gastos_excedidos'] ?? 0,
            'detalleValidacion' => $resultado['detalle_validacion'] ?? [],
            'reaperturaConsumida' => $resultado['reapertura_consumida'] ?? false
        ]);
    } else {
        throw new Exception(is_array($resultado) && isset($resultado['message'])
            ? $resultado['message']
            : "Error al insertar gastos: " . (is_array($resultado) ? json_encode($resultado) : $resultado));
    }
} catch (Exception $e) {
    error_log("Error en guardarGastosNew.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
