<?php
// src/Controllers/HomeController.php
require_once __DIR__ . '/BaseController.php';

class HomeController extends BaseController {
    public function index() {
        $pageTitle = 'Solicitudes de Transferencias';
        require_once __DIR__ . '/../Views/home.php';
    }
}
