<?php
// src/Models/AuditHistoryModel.php

class AuditHistoryModel {

    /**
     * Registra una nueva acción en el historial de una auditoría.
     */
    public function registrarAccion($audit_id, $usuario, $accion, $detalle = null) {
        $sql = "INSERT INTO FP_AUDITORIA_HISTORIAL (audit_id, usuario, accion, detalle) 
                VALUES (:audit_id, :usuario, :accion, :detalle)";

        try {
            $pdo = Database::getConnectionSistemas();
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindParam(':audit_id', $audit_id, PDO::PARAM_INT);
            $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
            $stmt->bindParam(':accion', $accion, PDO::PARAM_STR);
            $stmt->bindParam(':detalle', $detalle, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            // No detenemos la app si falla el log, pero lo reportamos.
            error_log("Error al registrar historial: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todo el historial de una auditoría específica.
     */
    public function getHistoryByAuditId($audit_id) {
        $sql = "SELECT * FROM FP_AUDITORIA_HISTORIAL WHERE audit_id = :audit_id ORDER BY fecha ASC";

        try {
            $pdo = Database::getConnectionSistemas();
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':audit_id', $audit_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Error al obtener el historial: " . $e->getMessage());
        }
    }
}