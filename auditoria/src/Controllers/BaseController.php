<?php
// src/Controllers/BaseController.php
class BaseController {
    public function __construct() {
        if (!isset($_SESSION['user'])) {
            // Si no hay un usuario en la sesión, detenemos la ejecución.
            die('Acceso Denegado. Por favor, ingrese a través del portal XL Gestión.');
        }
    }
}