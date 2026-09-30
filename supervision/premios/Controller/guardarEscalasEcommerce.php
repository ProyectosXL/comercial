<?php
// Controller/guardarEscalasEcommerce.php
// POST {conceptos:[{id, escalas:[{umbral, importe}]}]} -> reemplaza los tramos de esos
// conceptos (los que no vengan en el payload quedan intactos).
session_start();
require_once '../Class/PremioEcommerce.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new InvalidArgumentException('Método no permitido');
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $conceptosInput = $input['conceptos'] ?? null;
    if (!is_array($conceptosInput) || !$conceptosInput) {
        throw new InvalidArgumentException('Falta el detalle de conceptos');
    }

    $premios = new PremioEcommerce();
    $idsValidos = $premios->idsConceptos();
    $tramosPorConcepto = [];

    foreach ($conceptosInput as $c) {
        $id = (int) ($c['id'] ?? 0);
        if (!in_array($id, $idsValidos, true)) {
            throw new InvalidArgumentException("Concepto desconocido o inactivo: $id");
        }
        if (isset($tramosPorConcepto[$id])) {
            throw new InvalidArgumentException("El concepto $id viene repetido");
        }

        $escalas = $c['escalas'] ?? null;
        if (!is_array($escalas) || !$escalas) {
            // Un concepto sin tramos quedaría impagable en silencio.
            throw new InvalidArgumentException("El concepto $id debe tener al menos un tramo");
        }

        $tramos = [];
        $umbralesVistos = [];
        foreach ($escalas as $e) {
            $umbral  = $e['umbral']  ?? null;
            $importe = $e['importe'] ?? null;
            if (!is_numeric($umbral) || !is_numeric($importe)) {
                throw new InvalidArgumentException("Tramo inválido en el concepto $id: umbral e importe deben ser numéricos");
            }
            $umbral  = (float) $umbral;
            $importe = (float) $importe;
            if ($umbral <= 0) {
                throw new InvalidArgumentException("Los umbrales del concepto $id deben ser mayores a 0");
            }
            if ($importe < 0) {
                throw new InvalidArgumentException("Los importes del concepto $id no pueden ser negativos");
            }
            // El UNIQUE (ID_CONCEPTO, UMBRAL) de la tabla también lo impide, pero acá el
            // mensaje es entendible.
            $clave = (string) round($umbral, 4);
            if (isset($umbralesVistos[$clave])) {
                throw new InvalidArgumentException("El concepto $id tiene dos tramos con el mismo umbral ($clave)");
            }
            $umbralesVistos[$clave] = true;

            $tramos[] = ['umbral' => $umbral, 'importe' => $importe];
        }
        $tramosPorConcepto[$id] = $tramos;
    }

    $premios->guardarEscalas($tramosPorConcepto, $_SESSION['username'] ?? 'gestionarPremios');

    echo json_encode([
        'status' => 'success',
        'message' => 'Escalas guardadas correctamente.'
    ]);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log("Error en guardarEscalasEcommerce.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
