<?php
/**
 * adminPersonalController.php
 * Controlador AJAX — Administrador de parámetros y categorías.
 * Acciones: obtenerParametros, guardarParametros, listarCategorias,
 *           guardarCategoria, eliminarCategoria.
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';

try {
    require_once CP_BASE_PATH . '/Class/CostoPersonalService.php';

    $accion  = $_POST['accion'] ?? '';
    $service = new CostoPersonalService();

    switch ($accion) {

        // ── Leer parámetros actuales ──────────────────────────────────────
        case 'obtenerParametros':
            echo json_encode(['success' => true, 'data' => $service->obtenerParametros()], JSON_UNESCAPED_UNICODE);
            break;

        // ── Guardar uno o varios parámetros ──────────────────────────────
        case 'guardarParametros':
            $nombres   = ['UMBRAL_AZUL', 'UMBRAL_VERDE', 'UMBRAL_AMARILLO', 'UMBRAL_NARANJA', 'OBJETIVO_PCT'];
            $errores   = [];
            foreach ($nombres as $n) {
                if (isset($_POST[$n]) && $_POST[$n] !== '') {
                    $val = filter_var($_POST[$n], FILTER_VALIDATE_FLOAT);
                    if ($val === false || $val < 0) { $errores[] = "$n inválido."; continue; }
                    if (!$service->guardarParametro($n, $val)) $errores[] = "No se pudo guardar $n.";
                }
            }
            if ($errores) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
            } else {
                echo json_encode(['success' => true, 'data' => $service->obtenerParametros()], JSON_UNESCAPED_UNICODE);
            }
            break;

        // ── Listar categorías ─────────────────────────────────────────────
        case 'listarCategorias':
            echo json_encode(['success' => true, 'data' => $service->listarCategorias()], JSON_UNESCAPED_UNICODE);
            break;

        // ── Guardar categoría (insert id=0, update id>0) ──────────────────
        case 'guardarCategoria':
            $id   = (int) ($_POST['id'] ?? 0);
            $data = [
                'cod_cuenta'        => $_POST['cod_cuenta']        ?? '',
                'desc_cuenta'       => $_POST['desc_cuenta']       ?? '',
                'categoria'         => $_POST['categoria']         ?? '',
                'concepto_agrupado' => $_POST['concepto_agrupado'] ?? '',
                'prorratear_meses'  => $_POST['prorratear_meses']  ?? '0',
                'incluir'           => $_POST['incluir']           ?? '0',
                'observaciones'     => $_POST['observaciones']     ?? '',
            ];
            $result = $service->guardarCategoria($id, $data);
            if (!$result['success']) { http_response_code(400); }
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;

        // ── Eliminar categoría ────────────────────────────────────────────
        case 'eliminarCategoria':
            $id     = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID inválido.');
            $result = $service->eliminarCategoria($id);
            if (!$result['success']) { http_response_code(400); }
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception("Acción desconocida: $accion");
    }

} catch (Exception $e) {
    error_log('adminPersonalController — ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
