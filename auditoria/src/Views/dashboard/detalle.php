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
                                <!-- INICIO DE INFORMACIÓN DE SUPERVISORA -->
                <hr>
                <p><strong>¿Aprobado por supervisora?:</strong> <?php echo htmlspecialchars($auditoria['aprobado_supervisora']); ?></p>
                <?php if (!empty($auditoria['path_adjunto'])): ?>
                    <p><strong>Comprobante Adjunto:</strong> 
                        <a href="/comercial/auditoria/public/<?php echo htmlspecialchars($auditoria['path_adjunto']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download mr-1"></i> Descargar Adjunto
                        </a>
                    </p>
                <?php endif; ?>
                <!-- FIN DE INFORMACIÓN DE SUPERVISORA -->
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
                <?php if ($userRole === 'asistente' && $auditStatus === 'Pendiente'): ?>
                    <form action="/comercial/auditoria/public/dashboard/actualizarEstado" method="POST" class="d-inline">
                        <input type="hidden" name="id" value="<?php echo $auditoria['id']; ?>">
                        <input type="hidden" name="estado" value="Aprobado">
                        <button type="submit" class="btn btn-success mb-2 w-100"><i class="fas fa-check"></i> Aprobar Directo</button>
                    </form>
                    
                    <!-- Botón para Derivar que abre la ventana modal -->
                    <button type="button" class="btn btn-warning mb-2 w-100" data-toggle="modal" data-target="#derivarModal">
                        <i class="fas fa-share-square"></i> Derivar a Gerencia...
                    </button>

                    <!-- Botón para Rechazar que abre su propia modal -->
                    <button type="button" class="btn btn-danger w-100" data-toggle="modal" data-target="#rechazarModal">
                        <i class="fas fa-times"></i> Rechazar...
                    </button>
                <?php endif; ?>

                <?php // --- Lógica para la Gerencia Comercial --- ?>
                <?php if ($userRole === 'gerencia' && $auditStatus === 'Derivado a Gerencia'): ?>
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
                                
                                <!-- Mostrar el comentario si existe -->
                                <?php if (!empty($evento['detalle'])): ?>
                                    <blockquote class="blockquote blockquote-sm mt-2 mb-0 border-left pl-3 bg-light">
                                        <p class="mb-0 small font-italic p-2"><?php echo htmlspecialchars($evento['detalle']); ?></p>
                                    </blockquote>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Ventana Modal para Derivar -->
<div class="modal fade" id="derivarModal" tabindex="-1" role="dialog" aria-labelledby="derivarModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="derivarModalLabel">Derivar Auditoría a Gerencia</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="/comercial/auditoria/public/dashboard/actualizarEstado" method="POST">
          <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $auditoria['id']; ?>">
                <input type="hidden" name="estado" value="Derivado a Gerencia">
                <div class="form-group">
                    <label for="comentario_derivacion"><strong>Comentario para Gerencia (Opcional):</strong></label>
                    <!-- AJUSTE: Renombrado a 'comentario' para ser genérico -->
                    <textarea class="form-control" id="comentario_derivacion" name="comentario" rows="4" placeholder="Ej: El descuento es muy alto, requiere supervisión..."></textarea>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning"><i class="fas fa-share-square"></i> Confirmar Derivación</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Ventana Modal para Rechazar -->
<div class="modal fade" id="rechazarModal" tabindex="-1" role="dialog" aria-labelledby="rechazarModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="rechazarModalLabel">Rechazar Auditoría</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="/comercial/auditoria/public/dashboard/actualizarEstado" method="POST">
          <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $auditoria['id']; ?>">
                <input type="hidden" name="estado" value="Rechazado">
                <div class="form-group">
                    <label for="comentario_rechazo"><strong>Motivo del Rechazo (Obligatorio):</strong></label>
                    <!-- Usa 'comentario' para ser consistente con el controlador -->
                    <textarea class="form-control" id="comentario_rechazo" name="comentario" rows="4" placeholder="Ej: Falta documentación de respaldo..." required></textarea>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Confirmar Rechazo</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>