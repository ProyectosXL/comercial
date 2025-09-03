<?php
/**
 * Ejemplo de uso del componente nueva_pagina.php
 * Esta página demuestra cómo incluir el componente en otras vistas
 */

// Configuración de sesiones
require_once __DIR__ . '/config_sesiones_fixed.php';
require_once __DIR__ . '/class/AuthFranquicia.php';

// Verificar sesión activa
if (!AuthFranquicia::verificarSesion()) {
    header('Location: login.php');
    exit();
}

// Verificar acceso especial
if (!AuthFranquicia::verificarAccesoEspecial()) {
    header('Location: index.php?error=sin_acceso_especial');
    exit();
}

// Obtener datos de sesión
$usuario = $_SESSION['usuario_franquicia'] ?? 'N/A';
$franquiciaId = $_SESSION['franquicia_id'] ?? 'N/A';
$rol = 'franquicia';
$franquicia = $usuario; // Para compatibilidad con componentes
$vista = 'nueva_pagina'; // Para el navbar
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Página con Componente - Franquicias</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link href="css/main.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Contenido Principal -->
    <div class="app-layout">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Overlay para móvil -->
        <div class="sidebar-overlay"></div>

        <!-- Área de Contenido -->
        <main class="main-content nueva-pagina-component">
            <?php 
            // Definir contexto para el componente
            define('NUEVA_PAGINA_CONTEXT', true);
            
            // Incluir el componente
            include 'components/nueva_pagina.php'; 
            ?>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts principales -->
    <script src="js/main.js"></script>
    
    <script>
        // Inicialización específica para nueva página con componente
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Nueva página con componente cargada correctamente');
            
            // Aquí se pueden agregar funcionalidades específicas del componente
        });
    </script>
</body>
</html>
