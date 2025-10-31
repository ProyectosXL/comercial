<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Auditoría App'; ?> - XL</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .wrapper { min-height: 100vh; display: flex; flex-direction: column; }
        .main-content { flex: 1; }
    </style>
</head>
<body class="wrapper">

<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand font-weight-bold text-primary" href="/comercial/auditoria/"><i class="fas fa-clipboard-check"></i> Auditoría DIF PRECIO</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item"><a class="nav-link" href="/comercial/auditoria/"><i class="fas fa-home mr-1"></i>Inicio</a></li>
                    <?php if ($_SESSION['user']['role'] === 'sucursal'): ?>
                    <li class="nav-item"><a class="nav-link" href="/comercial/auditoria/public/sucursal/pendientes"><i class="fas fa-tasks mr-1"></i>Operaciones Pendientes</a></li>
                    <?php endif; ?>
                    <?php if (in_array($_SESSION['user']['role'], ['asistente', 'gerencia'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/comercial/auditoria/public/dashboard"><i class="fas fa-tachometer-alt mr-1"></i>Dashboard</a></li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($_SESSION['user']['name']); ?></a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarUserDropdown">
                            <h6 class="dropdown-header">Rol: <?php echo ucwords(str_replace('_', ' ', $_SESSION['user']['role'])); ?></h6>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="/comercial/auditoria/public/logout.php"><i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="main-content container py-4">