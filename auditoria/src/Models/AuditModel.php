<?php
// src/Models/AuditModel.php
// VERSIÓN FINAL Y DEFINITIVA - ADAPTADA A LA CONEXIÓN COMPATIBLE

class AuditModel {

    public function guardarJustificacion($datos) {
        $sql = "INSERT INTO FP_AUDITORIA_DIF_PRECIO (n_comp, nro_sucursal, fecha_operacion, importe, motivo, observaciones) VALUES (:n_comp, :nro_sucursal, :fecha_operacion, :importe, :motivo, :observaciones); SELECT SCOPE_IDENTITY() as LastID;";
        try {
            $pdo = Database::getConnectionSistemas();
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':n_comp', $datos['n_comp']);
            $stmt->bindParam(':nro_sucursal', $datos['nro_sucursal']);
            $stmt->bindParam(':fecha_operacion', $datos['fecha_operacion']);
            $stmt->bindParam(':importe', $datos['importe']);
            $stmt->bindParam(':motivo', $datos['motivo']);
            $stmt->bindParam(':observaciones', $datos['observaciones']);
            $stmt->execute();
            $stmt->nextRowset(); 
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['LastID'];
        } catch (PDOException $e) { 
            die("Error al guardar la justificación: " . $e->getMessage()); 
        }
    }

    public function getJustificadosNComp() {
        $sql = "SELECT n_comp FROM FP_AUDITORIA_DIF_PRECIO";
        try {
            $pdo = Database::getConnectionSistemas();
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($results, 'n_comp');
        } catch (PDOException $e) {
            die("Error al obtener los comprobantes justificados: " . $e->getMessage());
        }
    }

    private function applyFilters(&$sql, &$params, $filters) {
        if (!empty($filters['sucursal'])) { 
            $sql .= " AND a.nro_sucursal = ? "; 
            $params[] = $filters['sucursal']; 
        }
        if (!empty($filters['estado'])) { 
            $sql .= " AND a.estado = ? "; 
            $params[] = $filters['estado']; 
        }
        if (!empty($filters['fecha_desde'])) { 
            $sql .= " AND a.fecha_creacion >= ? "; 
            $params[] = $filters['fecha_desde']; 
        }
        if (!empty($filters['fecha_hasta'])) { 
            $sql .= " AND a.fecha_creacion <= ? "; 
            $params[] = $filters['fecha_hasta']; 
        }
    }

    public function getAllAudits($filters = []) {
        $params = [];
        $sql = "SELECT a.id, a.fecha_creacion, a.n_comp, a.motivo, a.estado, a.nro_sucursal FROM FP_AUDITORIA_DIF_PRECIO a WHERE 1=1";
        $this->applyFilters($sql, $params, $filters);
        $sql .= " ORDER BY a.fecha_creacion DESC;";
        try {
            $pdo = Database::getConnectionSistemas();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { 
            die("Error al obtener las auditorías: " . $e->getMessage()); 
        }
    }
    
    public function getDashboardStats($filters = []) {
        $params = [];
        $sql = "SELECT COUNT(*) AS total, SUM(CASE WHEN a.estado = 'Pendiente' THEN 1 ELSE 0 END) AS abiertos, SUM(CASE WHEN a.estado = 'Autorizado N1' THEN 1 ELSE 0 END) AS en_curso, SUM(CASE WHEN a.estado IN ('Autorizado N2', 'Rechazado') THEN 1 ELSE 0 END) AS finalizados, AVG(CAST(DATEDIFF(day, a.fecha_creacion, a.fecha_actualizacion) AS FLOAT)) as tiempo_promedio_resolucion FROM FP_AUDITORIA_DIF_PRECIO a WHERE 1=1";
        $this->applyFilters($sql, $params, $filters);
        try {
            $pdo = Database::getConnectionSistemas();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { 
            die("Error al calcular las estadísticas: " . $e->getMessage()); 
        }
    }

    public function getAuditById($id) {
        $sql = "SELECT * FROM FP_AUDITORIA_DIF_PRECIO WHERE id = :id";
        try {
            $pdo = Database::getConnectionSistemas();
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { 
            die("Error al buscar la auditoría: " . $e->getMessage()); 
        }
    }

    public function updateAuditStatus($id, $nuevoEstado) {
        $sql = "UPDATE FP_AUDITORIA_DIF_PRECIO SET estado = :nuevo_estado, fecha_actualizacion = GETDATE() WHERE id = :id";
        try {
            $pdo = Database::getConnectionSistemas();
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nuevo_estado', $nuevoEstado, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) { 
            die("Error al actualizar el estado: " . $e->getMessage()); 
        }
    }
}