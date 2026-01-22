<?php
// src/Models/StockModel.php

use PhpOffice\PhpSpreadsheet\IOFactory;

class StockModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getRubros() {
        $sql = "SELECT B.IDFOLDER ID_RUBRO, DESCRIP RUBRO 
                FROM STA11ITC A WITH (NOLOCK)          
                INNER JOIN STA11FLD B ON A.IDFOLDER = B.IDFOLDER          
                WHERE DESCRIP NOT LIKE '[_][^K]%' AND NOT DESCRIP LIKE 'PROMO VILLA%' 
                GROUP BY B.IDFOLDER, DESCRIP ORDER BY DESCRIP";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSucursales() {
        $sql = "SELECT NRO_SUCURSAL ID_SUCURSAL, COD_CLIENT, DESC_SUCURSAL
                FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS
                WHERE HABILITADO = 1 ORDER BY NRO_SUCURSAL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
public function getStockBase($filterSucursal = null, $filterRubro = null, $orderBy = null, $orderDir = 'ASC') {
    $sql = "SELECT 
                sb.Codigo_Sucursal,
                sb.Id_Rubro,
                sb.Stock_Base,
                ISNULL(sb.Codigo_Cliente, suc.COD_CLIENT) AS Codigo_Cliente,
                ISNULL(sb.Nombre_Sucursal, suc.DESC_SUCURSAL) AS Nombre_Sucursal,
                ISNULL(sb.Nombre_Rubro, rub.DESCRIP) AS Nombre_Rubro
            FROM EB_StockBase sb
            LEFT JOIN [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS suc 
                ON sb.Codigo_Sucursal = CAST(suc.NRO_SUCURSAL AS INT)
            LEFT JOIN STA11FLD rub 
                ON sb.Id_Rubro = CAST(rub.IDFOLDER AS INT)
            WHERE 1=1";

    $params = [];
    if ($filterSucursal) {
        $sql .= " AND sb.Codigo_Sucursal = :codigo_sucursal";
        $params[':codigo_sucursal'] = $filterSucursal;
    }
    if ($filterRubro) {
        $sql .= " AND sb.Id_Rubro = :id_rubro";
        $params[':id_rubro'] = $filterRubro;
    }
    
    $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
    $validColumns = ['Codigo_Sucursal', 'Id_Rubro', 'Stock_Base', 'Codigo_Cliente', 'Nombre_Sucursal', 'Nombre_Rubro'];
    $orderBy = ($orderBy && in_array($orderBy, $validColumns)) ? $orderBy : 'Codigo_Sucursal';
    
    $sql .= " ORDER BY $orderBy $orderDir";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function addStock($codigoSucursal, $idRubro, $stockBase) {
        try {
            $sqlSuc = "SELECT COD_CLIENT, DESC_SUCURSAL FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS WHERE NRO_SUCURSAL = :sucursal";
            $stmtSuc = $this->pdo->prepare($sqlSuc);
            $stmtSuc->execute([':sucursal' => $codigoSucursal]);
            $sucursal = $stmtSuc->fetch(PDO::FETCH_ASSOC);
            if (!$sucursal) return false;

            $sqlRubro = "SELECT DESCRIP FROM STA11FLD WHERE IDFOLDER = :id_rubro";
            $stmtRubro = $this->pdo->prepare($sqlRubro);
            $stmtRubro->execute([':id_rubro' => $idRubro]);
            $rubro = $stmtRubro->fetch(PDO::FETCH_ASSOC);
            if (!$rubro) return false;

            $sql = "INSERT INTO EB_StockBase (Codigo_Sucursal, Codigo_Cliente, Nombre_Sucursal, Id_Rubro, Nombre_Rubro, Stock_Base) 
                    VALUES (:codigo_sucursal, :codigo_cliente, :nombre_sucursal, :id_rubro, :nombre_rubro, :stock_base)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':codigo_sucursal' => $codigoSucursal,
                ':codigo_cliente' => $sucursal['COD_CLIENT'],
                ':nombre_sucursal' => $sucursal['DESC_SUCURSAL'],
                ':id_rubro' => $idRubro,
                ':nombre_rubro' => $rubro['DESCRIP'],
                ':stock_base' => $stockBase,
            ]);
        } catch (PDOException $e) {
            error_log("Error addStock: " . $e->getMessage());
            return false;
        }
    }

    public function updateStock($codigoSucursal, $idRubro, $stockBase) {
         try {
            $sqlSuc = "SELECT COD_CLIENT, DESC_SUCURSAL FROM [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS WHERE NRO_SUCURSAL = :sucursal";
            $stmtSuc = $this->pdo->prepare($sqlSuc);
            $stmtSuc->execute([':sucursal' => $codigoSucursal]);
            $sucursal = $stmtSuc->fetch(PDO::FETCH_ASSOC);
            if (!$sucursal) return false;

            $sqlRubro = "SELECT DESCRIP FROM STA11FLD WHERE IDFOLDER = :id_rubro";
            $stmtRubro = $this->pdo->prepare($sqlRubro);
            $stmtRubro->execute([':id_rubro' => $idRubro]);
            $rubro = $stmtRubro->fetch(PDO::FETCH_ASSOC);
            if (!$rubro) return false;

            $sql = "UPDATE EB_StockBase SET Stock_Base = :stock_base, Codigo_Cliente = :codigo_cliente, Nombre_Sucursal = :nombre_sucursal, Nombre_Rubro = :nombre_rubro
                    WHERE Codigo_Sucursal = :codigo_sucursal AND Id_Rubro = :id_rubro";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':stock_base' => $stockBase,
                ':codigo_cliente' => $sucursal['COD_CLIENT'],
                ':nombre_sucursal' => $sucursal['DESC_SUCURSAL'],
                ':nombre_rubro' => $rubro['DESCRIP'],
                ':codigo_sucursal' => $codigoSucursal,
                ':id_rubro' => $idRubro,
            ]);
        } catch (PDOException $e) {
            error_log("Error updateStock: " . $e->getMessage());
            return false;
        }
    }

    public function deleteStock($codigoSucursal, $idRubro) {
        try {
            $sql = "DELETE FROM EB_StockBase WHERE Codigo_Sucursal = :codigo_sucursal AND Id_Rubro = :id_rubro";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':codigo_sucursal' => $codigoSucursal, ':id_rubro' => $idRubro]);
        } catch (PDOException $e) {
            error_log("Error deleteStock: " . $e->getMessage());
            return false;
        }
    }

    public function clearAllStock() {
        try {
            $sql = "TRUNCATE TABLE EB_StockBase";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error clearAllStock: " . $e->getMessage());
            return false;
        }
    }

    public function processExcelImport($file) {
        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $headers = array_map('trim', array_map('strtoupper', array_shift($rows)));
            $requiredHeaders = ['ID_SUCURSAL', 'ID_RUBRO', 'STOCK_BASE'];
            
            foreach ($requiredHeaders as $requiredHeader) {
                if (!in_array($requiredHeader, $headers)) {
                    return ['success' => false, 'message' => "Falta encabezado: $requiredHeader"];
                }
            }

            $colMap = array_flip($headers);
            $imported = 0; $errors = 0; $errorMessages = [];
            
            $this->pdo->beginTransaction();
            foreach ($rows as $i => $row) {
                if (empty(array_filter($row))) continue;
                $codigoSucursal = trim($row[$colMap['ID_SUCURSAL']]);
                $idRubro = trim($row[$colMap['ID_RUBRO']]);
                $stockBase = trim($row[$colMap['STOCK_BASE']]);
                if (empty($codigoSucursal) || empty($idRubro) || !is_numeric($stockBase)) {
                    $errors++; continue;
                }

                $checkSql = "SELECT COUNT(*) FROM EB_StockBase WHERE Codigo_Sucursal = ? AND Id_Rubro = ?";
                $checkStmt = $this->pdo->prepare($checkSql);
                $checkStmt->execute([$codigoSucursal, $idRubro]);
                
                if ($checkStmt->fetchColumn() > 0) {
                    if ($this->updateStock($codigoSucursal, $idRubro, $stockBase)) $imported++; else $errors++;
                } else {
                    if ($this->addStock($codigoSucursal, $idRubro, $stockBase)) $imported++; else $errors++;
                }
            }
            $this->pdo->commit();
            
            $message = "Importación completada: $imported registros procesados, $errors errores.";
            return ['success' => true, 'message' => $message];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error al procesar el archivo: ' . $e->getMessage()];
        }
    }
}