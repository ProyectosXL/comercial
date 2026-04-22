<?php
// src/Controllers/ConfigController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/TransferenciaModel.php';

class ConfigController extends BaseController {

    public function index() {
        $config = TransferenciaModel::getAllConfig();
        require_once __DIR__ . '/../Views/config/index.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $categoria = $data['categoria'] ?? '';
            $valor = $data['valor'] ?? '';

            if (empty($categoria) || empty($valor)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
                return;
            }

            $success = TransferenciaModel::guardarConfig($categoria, $valor);
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
        }
    }

    public function toggle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            if ($id) {
                $success = TransferenciaModel::toggleConfig($id);
                header('Content-Type: application/json');
                echo json_encode(['success' => $success]);
            }
        }
    }

    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            if ($id) {
                $success = TransferenciaModel::eliminarConfig($id);
                header('Content-Type: application/json');
                echo json_encode(['success' => $success]);
            }
        }
    }
}
