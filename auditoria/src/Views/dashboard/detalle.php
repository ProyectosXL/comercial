<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Detalle de Auditoría #<?php echo $auditoria['id']; ?></h3>
    <a href="/comercial/auditoria/public/dashboard" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver al Dashboard
    </a>
</div>
<hr>

<div class="row">
    <!-- Columna Izquierda: Información de la Auditoría -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <strong>Información de la Solicitud</strong>
            </div>
            <div class="card-body">
                <p><strong>N° Comprobante:</strong> <?php echo htmlspecialchars($auditoria['n_comp']); ?></p>
                <p><strong>Fecha Operación:</strong> <?php echo date('d/m/Y', strtotime($auditoria['fecha_operacion'])); ?></p>
                <p><strong>Sucursal:</strong> <?php echo htmlspecialchars($auditoria['nro_sucursal'] . ' - ' . $auditoria['DESC_SUCURSAL']); ?></p>
                <p><strong>Importe:</strong> $<?php echo number_format($auditoria['importe'], 2, ',', '.'); ?></p>
                <hr>
                <p><strong>Motivo Reportado:</strong> <?php echo htmlspecialchars($auditoria['motivo']); ?></p>
                <p><strong>Observaciones:</strong></p>
                <blockquote class="blockquote">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($auditoria['observaciones'])); ?></p>
                </blockquote>
                 <hr>
                <p><strong>Fecha de Solicitud:</strong> <?php echo date('d/m/Y H:i:s', strtotime($auditoria['fecha_creacion'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Estado y Acciones -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <strong>Estado y Acciones</strong>
            </div>
            <div class="card-body">
                <?php
                    $estado = $auditoria['estado'];
                    $badgeClass = 'badge-secondary';
                    switch ($estado) {
                        case 'Pendiente': $badgeClass = 'badge-info'; break;
                        case 'Derivado a Gerencia': $badgeClass = 'badge-warning'; break;
                        case 'Aprobado': $badgeClass = 'badge-success'; break;
                        case 'Rechazado': $badgeClass = 'badge-danger'; break;
                    }
                ?>
                <p><strong>Estado Actual:</strong> <span class="badge <?php echo $badgeClass; ?> h4"><?php echo htmlspecialchars($estado); ?></span></p>
                <hr>
                <h5>Cambiar Estado</h5>

                <?php 
                $userRole = $_SESSION['user']['role']; 
                $auditStatus = $auditoria['estado'];
                ?>

                <?php // --- Lógica para el Asistente Comercial --- ?>
                <?php if ($userRole === 'asistente'): ?>
                    <?php if ($auditStatus === 'Pendiente'): ?>
                        <form action="/comercial/auditoria/public/dashboard/actualizarEstado" method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $auditoria['id']; ?>">
                            <input type="hidden" name="estado" value="Aprobado">
                            <button type="submit" class="btn btn-success mb-2 w-100"><i class="fas fa-check"></i> Aprobar Directo</button>
                        </form>
                        <form action="/comercial/auditoria/public/dashboard/actualizarEstado" method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $auditoria['id']; ?>">
                            <input type="hidden" name="estado" value="Derivado a Gerencia">
                            <button type="submit" class="btn btn-warning w-100"><i class="fas fa-share-square"></i> Derivar a Gerencia</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <?php // --- Lógica para la Gerencia Comercial --- ?>
                <?php if ($userRole === 'gerencia'): ?>
                    <?php if ($auditStatus === 'Derivado a Gerencia'): ?>
                        <form action="/comercial/auditoria/public/dashboard/actualizarEstado" method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $auditoria['id']; ?>">
                            <input type="hidden" name="estado" value="Aprobado">
                            <button type="submit" class="btn btn-success mb-2 w-100"><i class="fas fa-check"></i> Aprobar</button>
                        </form>
                        <form action="/comercial/auditoria/public/dashboard/actualizarEstado" method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $auditoria['id']; ?>">
                            <input type="hidden" name="estado" value="Rechazado">
                            <button type="submit" class="btn btn-danger w-100"><i class="fas fa-times"></i> Rechazar</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <?php // --- Mensaje para estados donde no hay acciones o ya están finalizados --- ?>
                <?php if (
                    in_array($auditStatus, ['Aprobado', 'Rechazado']) ||
                    ($userRole === 'asistente' && $auditStatus !== 'Pendiente') ||
                    ($userRole === 'gerencia' && $auditStatus !== 'Derivado a Gerencia')
                ): ?>
                     <div class="alert alert-light" role="alert">
                        <?php if (in_array($auditStatus, ['Aprobado', 'Rechazado'])): ?>
                            Esta auditoría ya ha sido finalizada.
                        <?php else: ?>
                            No hay acciones disponibles para usted en este estado.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <strong><i class="fas fa-history"></i> Historial de Trazabilidad</strong>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php if (empty($historial)): ?>
                        <li class="list-group-item">No hay historial para esta solicitud.</li>
                    <?php else: ?>
                        <?php foreach ($historial as $evento): ?>
                            <li class="list-group-item">
                                <strong><?php echo date('d/m/Y H:i', strtotime($evento['fecha'])); ?></strong> - 
                                <span class="text-primary"><?php echo htmlspecialchars($evento['usuario']); ?></span>: 
                                <?php echo htmlspecialchars($evento['accion']); ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>