<?php
// src/Controllers/BaseController.php
class BaseController {
    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header('Location: /comercial/auditoria/public/auth');
            exit();
        }
    }
}