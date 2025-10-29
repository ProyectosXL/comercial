<?php require_once __DIR__ . '/layouts/header.php'; ?>

<div class="container text-center">
    <h2 class="mt-4">¡Bienvenido, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>!</h2>
    <p class="lead text-muted">Sistema de Gestión de Auditorías de Diferencia de Precios.</p>
    <hr class="my-4">
</div>

<div class="row justify-content-center">
    
    <?php // --- TARJETA PARA ROL SUCURSAL --- ?>
    <?php if ($_SESSION['user']['role'] === 'sucursal'): ?>
    <div class="col-md-6 col-lg-5">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-tasks fa-3x mb-3 text-primary"></i>
                <h5 class="card-title">Operaciones Pendientes</h5>
                <p class="card-text">Vea y justifique las operaciones con diferencia de precio asignadas a su sucursal.</p>
                <a href="/comercial/auditoria/public/sucursal/pendientes" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> Ir a Pendientes
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>


    <?php // --- TARJETA PARA ROLES DE GESTIÓN --- ?>
    <?php if (in_array($_SESSION['user']['role'], ['asistente', 'gerencia'])): ?>
    <div class="col-md-6 col-lg-5">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-tachometer-alt fa-3x mb-3 text-success"></i>
                <h5 class="card-title">Dashboard de Auditorías</h5>
                <p class="card-text">Revise, gestione y apruebe todas las solicitudes de auditoría enviadas por las sucursales.</p>
                <a href="/comercial/auditoria/public/dashboard" class="btn btn-success">
                    <i class="fas fa-arrow-right"></i> Ir al Dashboard
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>