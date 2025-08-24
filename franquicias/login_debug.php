<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Login - Sistema de Franquicias</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin: 0 auto;
            max-width: 600px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }
        .form-group {
            margin: 15px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input, button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐛 Debug Login - Sistema de Franquicias</h1>
        
        <div class="debug-info">
ESTADO INICIAL:
Sesión iniciada: <?= session_id() ? 'SÍ (' . session_id() . ')' : 'NO' ?>
Usuario en sesión: <?= $_SESSION['usuario_franquicia'] ?? 'NO EXISTE' ?>
Franquicia ID: <?= $_SESSION['franquicia_id'] ?? 'NO EXISTE' ?>
Autenticado: <?= ($_SESSION['autenticado'] ?? false) ? 'SÍ' : 'NO' ?>
        </div>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo '<div class="debug-info">POST RECIBIDO:
Usuario: ' . ($_POST['usuario'] ?? 'NO ENVIADO') . '
Password: ' . ($_POST['password'] ?? 'NO ENVIADO') . '</div>';
            
            try {
                require_once 'class/AuthFranquicia.php';
                echo '<div class="debug-info">✅ Clase AuthFranquicia cargada</div>';
                
                $auth = new AuthFranquicia();
                echo '<div class="debug-info">✅ AuthFranquicia instanciada</div>';
                
                $resultado = $auth->iniciarSesion($_POST['usuario'], $_POST['password']);
                
                if ($resultado) {
                    echo '<div class="success">✅ LOGIN EXITOSO!
Usuario: ' . $resultado['usuario'] . '
Franquicia ID: ' . $resultado['franquicia_id'] . '</div>';
                    
                    echo '<div class="debug-info">ESTADO POST-LOGIN:
Usuario en sesión: ' . ($_SESSION['usuario_franquicia'] ?? 'NO EXISTE') . '
Franquicia ID: ' . ($_SESSION['franquicia_id'] ?? 'NO EXISTE') . '
Autenticado: ' . (($_SESSION['autenticado'] ?? false) ? 'SÍ' : 'NO') . '</div>';
                    
                    // Verificar sesión usando métodos estáticos
                    echo '<div class="debug-info">VERIFICACIÓN CON MÉTODOS ESTÁTICOS:
AuthFranquicia::verificarSesion(): ' . (AuthFranquicia::verificarSesion() ? 'SÍ' : 'NO') . '
AuthFranquicia::obtenerFranquiciaIdSesion(): ' . (AuthFranquicia::obtenerFranquiciaIdSesion() ?? 'NULL') . '</div>';
                    
                    echo '<div class="success">
🔄 Redirección automática en 5 segundos...
<br><a href="index.php">O haz clic aquí para ir al index</a>
<br><a href="test_completo.html">O prueba el test completo</a>
                    </div>';
                    
                    echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 5000);</script>';
                    
                } else {
                    echo '<div class="error">❌ LOGIN FALLÓ
Las credenciales no son válidas</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">❌ ERROR EN LOGIN:
' . $e->getMessage() . '

Stack trace:
' . $e->getTraceAsString() . '</div>';
            }
        }
        ?>
        
        <h3>Formulario de Login</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="usuario">Usuario de Franquicia:</label>
                <select name="usuario" id="usuario" required onchange="updatePassword()">
                    <option value="">Seleccionar franquicia...</option>
                    <option value="xlcentro">xlcentro (XL Centro)</option>
                    <option value="xlbahia">xlbahia (XL Bahía)</option>
                    <option value="xlcatamarca">xlcatamarca (XL Catamarca)</option>
                    <option value="xlcordoba">xlcordoba (XL Córdoba)</option>
                    <option value="xlmendoza">xlmendoza (XL Mendoza)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" name="password" id="password" required>
            </div>
            
            <button type="submit">🔐 Hacer Login</button>
        </form>
        
        <h3>Acciones de Debug</h3>
        <button onclick="window.location.href='index.php'">🏠 Ir a Index</button>
        <button onclick="window.location.href='test_completo.html'">🧪 Test Completo</button>
        <button onclick="verificarSesionAjax()">🔍 Verificar Sesión (AJAX)</button>
        
        <div id="ajax-result"></div>
    </div>
    
    <script>
        const passwords = {
            'xlcentro': 'bahia,123',
            'xlbahia': 'bahia,123',
            'xlcatamarca': 'cata,123',
            'xlcordoba': 'centro,123',
            'xlmendoza': 'mendoza,123'
        };

        function updatePassword() {
            const usuario = document.getElementById('usuario').value;
            const passwordField = document.getElementById('password');
            if (passwords[usuario]) {
                passwordField.value = passwords[usuario];
            }
        }
        
        async function verificarSesionAjax() {
            try {
                const response = await fetch('controller/franquicia_stock_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'accion=obtener_rubros'
                });
                
                const data = await response.json();
                
                document.getElementById('ajax-result').innerHTML = `
                    <div class="debug-info">RESULTADO AJAX:
${JSON.stringify(data, null, 2)}</div>
                `;
                
            } catch (error) {
                document.getElementById('ajax-result').innerHTML = `
                    <div class="error">ERROR AJAX: ${error.message}</div>
                `;
            }
        }
    </script>
</body>
</html>
