<?php
session_start();
require_once __DIR__ . '/../Class/Gasto.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['username'])) {
        throw new Exception("No autorizado: la sesión expiró. Volvé a iniciar sesión.");
    }

    $usuario = $_SESSION['username'];

    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error decodificando JSON: " . json_last_error_msg());
    }

    $accion = $data['accion'] ?? null;
    $gasto = new Gasto();

    switch ($accion) {

        /**
         * Estado del período antes de reabrirlo. Alimenta el aviso de la
         * pantalla: cuántos conceptos ya están asentados en Tango (CARGADO /
         * CARGADO_TARJ) y cuántos tienen CAE. Reabrir un período así hace
         * divergir el registro de supervisión del contable, así que se avisa,
         * pero no se bloquea: quien reabre es quien tiene el contexto.
         */
        case 'estado':
            $semana = $data['semana'] ?? null;
            $anio = $data['anio'] ?? null;
            $supervisora = $data['supervisora'] ?? null;

            if (empty($semana) || empty($anio) || empty($supervisora)) {
                throw new Exception("Faltan semana, año o supervisora.");
            }

            $estado = $gasto->estadoPeriodoGastos($semana, $anio, $supervisora);
            $reaperturaActiva = $gasto->obtenerReaperturaActiva($semana, $anio, $supervisora);

            if ($reaperturaActiva && $reaperturaActiva['FECHA_HABILITACION'] instanceof DateTime) {
                $reaperturaActiva['FECHA_HABILITACION'] =
                    $reaperturaActiva['FECHA_HABILITACION']->format('d-m-Y H:i');
            }

            echo json_encode([
                'success' => true,
                'estado' => $estado,
                'reaperturaActiva' => $reaperturaActiva,
                'cerradoPorFecha' => Gasto::esPeriodoCerradoPorFecha($semana, $anio)
            ]);
            break;

        case 'habilitar':
            $semana = $data['semana'] ?? null;
            $anio = $data['anio'] ?? null;
            $supervisora = $data['supervisora'] ?? null;
            $motivo = trim($data['motivo'] ?? '');

            if (empty($semana) || empty($anio) || empty($supervisora)) {
                throw new Exception("Faltan semana, año o supervisora.");
            }
            if ($motivo === '') {
                throw new Exception("El motivo de la reapertura es obligatorio.");
            }
            if (mb_strlen($motivo) > 500) {
                throw new Exception("El motivo no puede superar los 500 caracteres.");
            }

            $gasto->habilitarReapertura($semana, $anio, $supervisora, $motivo, $usuario);

            error_log("Reapertura habilitada por $usuario: semana $semana/$anio, $supervisora. Motivo: $motivo");

            echo json_encode([
                'success' => true,
                'message' => "Período reabierto para $supervisora (semana $semana/$anio)."
            ]);
            break;

        case 'anular':
            $id = $data['id'] ?? null;
            if (empty($id)) {
                throw new Exception("Falta el identificador de la reapertura.");
            }

            $gasto->anularReapertura($id, $usuario);

            error_log("Reapertura $id anulada por $usuario");

            echo json_encode([
                'success' => true,
                'message' => 'Reapertura anulada.'
            ]);
            break;

        case 'listar':
            // Por defecto solo las abiertas: es el indicador de qué períodos
            // están habilitados ahora mismo.
            $estadoFiltro = $data['estado'] ?? 'ABIERTA';
            if ($estadoFiltro === 'TODAS') {
                $estadoFiltro = null;
            }

            echo json_encode([
                'success' => true,
                'reaperturas' => $gasto->listarReaperturas($estadoFiltro)
            ]);
            break;

        default:
            throw new Exception("Acción no reconocida.");
    }
} catch (Exception $e) {
    error_log("Error en reaperturasGastos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
