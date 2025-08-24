<?php
// Incluir configuración de sesiones ANTES que cualquier otra cosa
require_once 'config_sesiones_fixed.php';

// Procesar formulario de login
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once 'class/AuthFranquicia.php';
        $auth = new AuthFranquicia();
        
        $resultado = $auth->iniciarSesion($_POST['usuario'], $_POST['password']);
        
        if ($resultado) {
            $mensaje = '✅ Login exitoso! Franquicia ID: ' . $resultado['franquicia_id'];
            $tipo_mensaje = 'success';
            // Redirigir después de login exitoso
            header('Refresh: 2; url=index.php');
        } else {
            $mensaje = '❌ Credenciales inválidas';
            $tipo_mensaje = 'error';
        }
    } catch (Exception $e) {
        $mensaje = '❌ Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Cargar usuarios de franquicia para el select
$usuarios = [];
try {
    require_once 'class/AuthFranquicia.php';
    $auth = new AuthFranquicia();
    $usuarios = $auth->obtenerUsuariosFranquicia();
} catch (Exception $e) {
    // Error cargando usuarios, se manejará en el HTML
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Franquicias</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #333;
            margin: 0;
            font-size: 28px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        select, input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            color: #dc3545;
            margin-top: 10px;
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }
        .success {
            color: #155724;
            margin-top: 10px;
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }
        .password-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .quick-login {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .quick-login h3 {
            margin-top: 0;
            color: #666;
            font-size: 14px;
        }
        .quick-btn {
            display: inline-block;
            margin: 5px;
            padding: 8px 12px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 3px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
        }
        .quick-btn:hover {
            background-color: #545b62;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🏪 Sistema de Franquicias</h1>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="usuario">Usuario de Franquicia:</label>
                <select name="usuario" id="usuario" required onchange="updatePassword()">
                    <option value="">Seleccionar franquicia...</option>
                    <?php
                    // Mostrar usuarios cargados al inicio
                    if (!empty($usuarios)) {
                        foreach ($usuarios as $user) {
                            echo "<option value=\"{$user['NOMBRE']}\" data-pass=\"{$user['PASS']}\">{$user['NOMBRE']}</option>";
                        }
                    } else {
                        echo "<option value=\"\">Error cargando usuarios</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" name="password" id="password" required>
                <div class="password-info">La contraseña se autocompletará al seleccionar el usuario</div>
            </div>
            
            <button type="submit">Iniciar Sesión</button>
        </form>
        
        <div class="quick-login">
            <h3>Acceso Rápido (Solo para pruebas):</h3>
            <a href="#" class="quick-btn" onclick="quickLogin('xlcentro', 'bahia,123')">XL Centro</a>
            <a href="#" class="quick-btn" onclick="quickLogin('xlbahia', 'bahia,123')">XL Bahía</a>
            <a href="#" class="quick-btn" onclick="quickLogin('xlcatamarca', 'cata,123')">XL Catamarca</a>
            <a href="#" class="quick-btn" onclick="quickLogin('xlcordoba', 'centro,123')">XL Córdoba</a>
            <a href="#" class="quick-btn" onclick="quickLogin('xlmendoza', 'mendoza,123')">XL Mendoza</a>
        </div>
        
        <?php
        // Mostrar mensaje si existe
        if (!empty($mensaje)) {
            echo "<div class=\"$tipo_mensaje\">$mensaje</div>";
        }
        ?>
    </div>
    
    <script>
        function updatePassword() {
            const select = document.getElementById('usuario');
            const passwordInput = document.getElementById('password');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption && selectedOption.dataset.pass) {
                passwordInput.value = selectedOption.dataset.pass;
            }
        }
        
        function quickLogin(usuario, password) {
            document.getElementById('usuario').value = usuario;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
