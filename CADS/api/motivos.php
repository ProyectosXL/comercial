<?php
require_once __DIR__ . '/../config/api_helpers.php';

// vw_Motivos_Analitica: IdMotivoDevolucion, Motivo, CantidadDevoluciones,
//   ValorTotal, TiempoPromedioMinutos
// Vista agregada: sin filtros de fila disponibles.
try {
    $sql  = "SELECT Motivo, CantidadDevoluciones, ValorTotal, TiempoPromedioMinutos
             FROM vw_Motivos_Analitica
             WHERE Motivo IS NOT NULL
             ORDER BY CantidadDevoluciones DESC";
    $data = dbFetchAll($sql);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[motivos] ' . $e->getMessage());
    jsonError('Error al obtener motivos.');
}
