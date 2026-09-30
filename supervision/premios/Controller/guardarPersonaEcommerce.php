<?php
// Controller/guardarPersonaEcommerce.php
// POST {accion:'crear', nombre, email, id_puesto}
//      {accion:'editar', id, nombre, email}
//      {accion:'baja'|'reactivar', id}
session_start();
require_once '../Class/PremioEcommerce.php';

header('Content-Type: application/json; charset=utf-8');

/** Nombre obligatorio, sin espacios de más; el largo es el de la columna NOMBRE. */
function nombreValido($v)
{
    $nombre = trim(preg_replace('/\s+/', ' ', (string) $v));
    if ($nombre === '') throw new InvalidArgumentException('Falta el nombre');
    if (mb_strlen($nombre) > 100) throw new InvalidArgumentException('El nombre no puede superar los 100 caracteres');
    return $nombre;
}

/** Mail opcional (vacío = sin mail), pero si viene tiene que ser válido. */
function emailValido($v)
{
    $email = trim((string) $v);
    if ($email === '') return null;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        throw new InvalidArgumentException("El mail \"$email\" no es válido");
    }
    return $email;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new InvalidArgumentException('Método no permitido');
    }

    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $usuario = $_SESSION['username'] ?? 'gestionarPremios';
    $id      = (int) ($input['id'] ?? 0);
    $premios = new PremioEcommerce();

    switch ($input['accion'] ?? '') {
        case 'crear':
            $idPuesto = (int) ($input['id_puesto'] ?? 0);
            if ($idPuesto <= 0) throw new InvalidArgumentException('Elegí el puesto');
            $id = $premios->crearPersona(nombreValido($input['nombre'] ?? ''), emailValido($input['email'] ?? ''), $idPuesto, $usuario);
            $mensaje = 'Persona dada de alta correctamente.';
            break;
        case 'editar':
            if ($id <= 0) throw new InvalidArgumentException('Falta la persona');
            $premios->editarPersona($id, nombreValido($input['nombre'] ?? ''), emailValido($input['email'] ?? ''), $usuario);
            $mensaje = 'Persona actualizada correctamente.';
            break;
        case 'baja':
        case 'reactivar':
            if ($id <= 0) throw new InvalidArgumentException('Falta la persona');
            $premios->cambiarActivoPersona($id, $input['accion'] === 'reactivar', $usuario);
            $mensaje = $input['accion'] === 'baja' ? 'Persona dada de baja.' : 'Persona reactivada.';
            break;
        default:
            throw new InvalidArgumentException('Acción desconocida');
    }

    echo json_encode([
        'status' => 'success',
        'message' => $mensaje,
        'data' => ['id' => $id]
    ]);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log("Error en guardarPersonaEcommerce.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
