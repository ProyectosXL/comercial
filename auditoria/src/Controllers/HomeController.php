<?php
// src/Controllers/HomeController.php

require_once 'BaseController.php'; // Aseguramos que hereda la seguridad

class HomeController extends BaseController {

    public function index() {
        $pageTitle = "Inicio - Auditoría App";
        
        // Simplemente cargamos la vista. La vista se encargará de la lógica de roles.
        require_once __DIR__ . '/../Views/home.php';
    }
}