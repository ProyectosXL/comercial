<?php
// src/Controllers/AuthController.php

class AuthController {

    // Usuarios simulados para la prueba. En el futuro, esto vendrá de XL Gestión.
    private $users = [
        'asistente' => ['password' => '1234', 'role' => 'asistente', 'name' => 'Asistente Comercial'],
        'gerencia' => ['password' => '1234', 'role' => 'gerencia', 'name' => 'Gerencia Comercial'],
        'sucursal' => ['password' => '1234', 'role' => 'sucursal', 'name' => 'Sucursal Unicenter']
    ];

    /**
     * Muestra el formulario de login.
     */
    public function index() {
        $pageTitle = "Login";
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    /**
     * Procesa el intento de login.
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            if (isset($this->users[$username]) && $this->users[$username]['password'] === $password) {
                // Login correcto: Guardamos la info del usuario en la sesión.
                $_SESSION['user'] = [
                    'username' => $username,
                    'name' => $this->users[$username]['name'],
                    'role' => $this->users[$username]['role']
                ];
                // Redirigimos al dashboard.
                header('Location: /comercial/auditoria/');
                exit();
            } else {
                // Login incorrecto: redirigimos de vuelta al login con un error.
                header('Location: /comercial/auditoria/public/auth?error=1');
                exit();
            }
        }
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout() {
        session_destroy();
        header('Location: /comercial/auditoria/public/auth');
        exit();
    }
}