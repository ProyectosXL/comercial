<?php
// src/Models/VentaModel.php
// VERSIÓN FINAL Y DEFINITIVA - ADAPTADA A LA CONEXIÓN COMPATIBLE

class VentaModel {

    private function addDateFilter(&$sql, &$params, $fecha_desde, $fecha_hasta) {
        if (!empty($fecha_desde)) {
            $sql .= " AND FECHA >= ? ";
            $params[] = $fecha_desde;
        }
        if (!empty($fecha_hasta)) {
            $sql .= " AND FECHA <= ? ";
            $params[] = $fecha_hasta;
        }
    }

    public function getOperacionesDifPrecio($fecha_desde = null, $fecha_hasta = null) {
        $sql = "SELECT FECHA, N_COMP, NRO_SUCURS, IMPORTE FROM POWER_BI_CONTROL.dbo.BI_SALES_SUCURSALES WHERE COD_ARTICU = '*****DIF PRECIO' AND CANAL = 'LOCALES PROPIOS'";
        $params = [];
        $this->addDateFilter($sql, $params, $fecha_desde, $fecha_hasta);
        $sql .= " ORDER BY FECHA DESC;";
        try {
            $pdo = Database::getConnectionPowerBI();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { 
            die("Error en getOperacionesDifPrecio: " . $e->getMessage()); 
        }
    }

    public function getSucursales() {
        $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM LAKER_SA.dbo.SUCURSALES_LAKERSW WHERE CANAL = 'PROPIOS' ORDER BY DESC_SUCURSAL ASC";
        try {
            $pdo = Database::getConnectionLaker();
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $sucursalesArray = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $sucursal) {
                $sucursalesArray[$sucursal['NRO_SUCURSAL']] = $sucursal['DESC_SUCURSAL'];
            }
            return $sucursalesArray;
        } catch (PDOException $e) { 
            die("Error en getSucursales: " . $e->getMessage()); 
        }
    }

    public function getStatsSucursal($nroSucursal, $justificadosNComp, $fecha_desde = null, $fecha_hasta = null) {
        $params = [];
        $sql = "SELECT COUNT(*) as total_pendientes, SUM(IMPORTE) as monto_total, MIN(FECHA) as fecha_mas_antigua FROM POWER_BI_CONTROL.dbo.BI_SALES_SUCURSALES WHERE COD_ARTICU = '*****DIF PRECIO' AND CANAL = 'LOCALES PROPIOS'";
        
        if ($nroSucursal !== null) { 
            $sql .= " AND NRO_SUCURS = ? "; 
            $params[] = $nroSucursal; 
        }
        $this->addDateFilter($sql, $params, $fecha_desde, $fecha_hasta);
        
        if (!empty($justificadosNComp)) {
            $placeholders = implode(',', array_fill(0, count($justificadosNComp), '?'));
            $sql .= " AND N_COMP NOT IN ($placeholders) ";
            $params = array_merge($params, $justificadosNComp);
        }

        try {
            $pdo = Database::getConnectionPowerBI();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['total_pendientes' => 0, 'monto_total' => 0, 'fecha_mas_antigua' => null];
        }
    }
    
    public function getPendientesPorFecha($nroSucursal, $justificadosNComp, $fecha_desde = null, $fecha_hasta = null) {
        $params = [];
        $sql = "SELECT FORMAT(FECHA, 'yyyy-MM') as mes, COUNT(*) as cantidad FROM POWER_BI_CONTROL.dbo.BI_SALES_SUCURSALES WHERE COD_ARTICU = '*****DIF PRECIO' AND CANAL = 'LOCALES PROPIOS'";
        
        if ($nroSucursal !== null) { 
            $sql .= " AND NRO_SUCURS = ? "; 
            $params[] = $nroSucursal; 
        }
        $this->addDateFilter($sql, $params, $fecha_desde, $fecha_hasta);

        if (!empty($justificadosNComp)) {
            $placeholders = implode(',', array_fill(0, count($justificadosNComp), '?'));
            $sql .= " AND N_COMP NOT IN ($placeholders) ";
            $params = array_merge($params, $justificadosNComp);
        }
        
        $sql .= " GROUP BY FORMAT(FECHA, 'yyyy-MM') ORDER BY mes ASC;";

        try {
            $pdo = Database::getConnectionPowerBI();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getRankingPorSucursal($justificadosNComp, $fecha_desde = null, $fecha_hasta = null) {
        $params = [];
        $sql = "SELECT TOP 10 NRO_SUCURS, COUNT(N_COMP) as cantidad FROM POWER_BI_CONTROL.dbo.BI_SALES_SUCURSALES WHERE COD_ARTICU = '*****DIF PRECIO' AND CANAL = 'LOCALES PROPIOS'";
        $this->addDateFilter($sql, $params, $fecha_desde, $fecha_hasta);
        if (!empty($justificadosNComp)) {
            $placeholders = implode(',', array_fill(0, count($justificadosNComp), '?'));
            $sql .= " AND N_COMP NOT IN ($placeholders) ";
            $params = array_merge($params, $justificadosNComp);
        }
        $sql .= " GROUP BY NRO_SUCURS ORDER BY cantidad DESC";
        try {
            $pdo = Database::getConnectionPowerBI();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { 
            return []; 
        }
    }

        public function getDatosContactoSucursal($nro_sucursal) {
        // Consultamos explícitamente el MAIL y la Descripción usando el NRO_SUCURSAL (ID)
        $sql = "SELECT DESC_SUCURSAL, MAIL 
                FROM LAKER_SA.dbo.SUCURSALES_LAKERSW 
                WHERE NRO_SUCURSAL = :nro_sucursal";
                
        try {
            $pdo = Database::getConnectionLaker();
            $stmt = $pdo->prepare($sql);
            // Usamos bindParam para mayor seguridad con el ID
            $stmt->bindParam(':nro_sucursal', $nro_sucursal, PDO::PARAM_INT);
            $stmt->execute();
            
            // Retorna un array asociativo con MAIL y DESC_SUCURSAL, o false si falla
            return $stmt->fetch(PDO::FETCH_ASSOC); 
        } catch (PDOException $e) { 
            // En caso de error, retornamos null para que no rompa el flujo, pero podrías loguear el error
            return null; 
        }
    }

    public function getOperacionPorNComp($n_comp) {
        $sql = "SELECT FECHA, N_COMP, NRO_SUCURS, IMPORTE FROM POWER_BI_CONTROL.dbo.BI_SALES_SUCURSALES WHERE N_COMP = :n_comp AND CANAL = 'LOCALES PROPIOS';";
        try {
            $pdo = Database::getConnectionPowerBI();
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':n_comp', $n_comp, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { 
            die("Error en getOperacionPorNComp: " . $e->getMessage()); 
        }
    }
}