<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Auditoría App</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        html, body { height: 100%; }
        body { display: flex; align-items: center; background-color: #f5f5f5; }
        .form-signin { width: 100%; max-width: 330px; padding: 15px; margin: auto; }
    </style>
</head>
<body class="text-center">
    <form class="form-signin" action="/comercial/auditoria/public/auth/login" method="POST">
        <h1 class="h3 mb-3 font-weight-normal">Iniciar Sesión</h1>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">Usuario o contraseña incorrectos.</div>
        <?php endif; ?>

        <label for="username" class="sr-only">Usuario</label>
        <input type="text" id="username" name="username" class="form-control mb-2" placeholder="Usuario (asistente / gerencia)" required autofocus>
        
        <label for="password" class="sr-only">Contraseña</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Contraseña (1234)" required>
        
        <button class="btn btn-lg btn-primary btn-block mt-3" type="submit">Entrar</button>
        <p class="mt-5 mb-3 text-muted">&copy; <?php echo date('Y'); ?> - XL Extra Large</p>
    </form>
</body>
</html>