<?php
// Incluir configuración de sesiones ANTES que cualquier otra cosa
require_once 'config_sesiones_fixed.php';

// Cargar la nueva clase de autenticación
require_once 'class/AuthFranquicia.php';

// Procesar formulario de login
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($usuario) && !empty($password)) {
        try {
            $auth = new AuthFranquicia();
            $resultado = $auth->iniciarSesion($usuario, $password);
            
            if ($resultado) {
                $mensaje = '✅ Login exitoso! Bienvenido ' . $resultado['usuario'] . ' (ID: ' . $resultado['franquicia_id'] . ')';
                $tipo_mensaje = 'success';
                header('Refresh: 2; url=index.php');
            } else {
                $mensaje = '❌ Credenciales inválidas. Verifique usuario y contraseña.';
                $tipo_mensaje = 'error';
            }
        } catch (Exception $e) {
            $mensaje = '❌ Error de sistema: ' . $e->getMessage();
            $tipo_mensaje = 'error';
            error_log("Error en login: " . $e->getMessage());
        }
    } else {
        $mensaje = '⚠️ Debe completar usuario y contraseña';
        $tipo_mensaje = 'warning';
    }
}

// Cargar usuarios de franquicia para el select
$usuarios = [];
$error_usuarios = '';

try {
    $auth = new AuthFranquicia();
    $usuarios = $auth->obtenerUsuariosFranquicia();
    
    if (empty($usuarios)) {
        $error_usuarios = 'No se pudieron cargar los usuarios. Contacte al administrador.';
    }
} catch (Exception $e) {
    $error_usuarios = 'Error de conexión: ' . $e->getMessage();
    error_log("Error cargando usuarios: " . $e->getMessage());
    
    // Usuarios de fallback solo para desarrollo
    $usuarios = [
        ['NOMBRE' => 'xlcentro', 'PASS' => 'prueba123'],
        ['NOMBRE' => 'xlbahia', 'PASS' => 'prueba123'],
        ['NOMBRE' => 'xlcordoba', 'PASS' => 'prueba123']
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Franquicias</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #4a4a4a 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-floating > .form-select,
        .form-floating > .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .form-floating > .form-select:focus,
        .form-floating > .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
            background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
        }
        
        .quick-access {
            border-top: 1px solid #f0f0f0;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .quick-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            margin: 0.25rem;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #495057;
            display: inline-block;
        }
        
        .quick-btn:hover {
            background: #e9ecef;
            border-color: #adb5bd;
            transform: translateY(-1px);
            color: #495057;
        }
        
        .alert {
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .connection-status {
            font-size: 0.85rem;
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 8px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        @media (max-width: 576px) {
            .login-card {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .login-header,
            .login-body {
                padding: 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-shop display-4 mb-3"></i>
            <h1>Sistema de Franquicias</h1>
            <p>Gestión de Stock</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : ($tipo_mensaje === 'warning' ? 'warning' : 'danger') ?> d-flex align-items-center" role="alert">
                    <i class="bi bi-<?= $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'warning' ? 'exclamation-triangle' : 'x-circle') ?> me-2"></i>
                    <div><?= htmlspecialchars($mensaje) ?></div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-floating">
                    <select name="usuario" id="usuario" class="form-select" required onchange="updatePassword()">
                        <option value="">Seleccionar franquicia...</option>
                        <?php foreach ($usuarios as $user): ?>
                            <option value="<?= htmlspecialchars($user['NOMBRE']) ?>" 
                                    data-pass="<?= htmlspecialchars($user['PASS']) ?>">
                                <?= htmlspecialchars($user['NOMBRE']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="usuario">
                        <i class="bi bi-person me-2"></i>Usuario de Franquicia
                    </label>
                </div>
                
                <div class="form-floating">
                    <input type="password" name="password" id="password" class="form-control" required>
                    <label for="password">
                        <i class="bi bi-lock me-2"></i>Contraseña
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Iniciar Sesión
                </button>
            </form>
            
            <?php if (!empty($error_usuarios)): ?>
                <div class="connection-status">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                    <strong>Estado de conexión:</strong> <?= htmlspecialchars($error_usuarios) ?>
                </div>
            <?php else: ?>
                <div class="connection-status">
                    <i class="bi bi-check-circle text-success me-2"></i>
                    <strong>Conexión exitosa:</strong> <?= count($usuarios) ?> usuarios de franquicia cargados desde LAKER_SA
                </div>
            <?php endif; ?>
            
            <div class="quick-access">
                <h6 class="text-muted mb-3">
                    <i class="bi bi-lightning me-2"></i>Acceso Rápido
                </h6>
                <?php 
                $usuariosPopulares = array_slice($usuarios, 0, 6); // Mostrar solo los primeros 6
                foreach ($usuariosPopulares as $user): 
                ?>
                    <a href="#" class="quick-btn" onclick="quickLogin('<?= htmlspecialchars($user['NOMBRE']) ?>', '<?= htmlspecialchars($user['PASS']) ?>')">
                        <?= htmlspecialchars($user['NOMBRE']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function updatePassword() {
            const select = document.getElementById('usuario');
            const passwordInput = document.getElementById('password');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption && selectedOption.dataset.pass) {
                passwordInput.value = selectedOption.dataset.pass;
                passwordInput.focus();
            } else {
                passwordInput.value = '';
            }
        }
        
        function quickLogin(usuario, password) {
            document.getElementById('usuario').value = usuario;
            document.getElementById('password').value = password;
            
            // Enviar formulario automáticamente
            document.querySelector('form').submit();
        }
        
        // Auto-focus en el primer campo
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('usuario').focus();
        });
        
        // Enviar con Enter desde cualquier campo
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && event.target.tagName !== 'BUTTON') {
                const form = document.querySelector('form');
                if (form.checkValidity()) {
                    form.submit();
                }
            }
        });
    </script>
</body>
</html>