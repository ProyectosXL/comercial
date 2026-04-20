<?php
// src/Controllers/LogisticaController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/TransferenciaModel.php';

class LogisticaController extends BaseController {

    public function index() {
        $pageTitle = 'Gestión Logística - Transferencias';
        $isLogistica = true; // Flag para el header
        $solicitudes = TransferenciaModel::obtenerSolicitudes();
        require_once __DIR__ . '/../Views/logistica/index.php';
    }
}
