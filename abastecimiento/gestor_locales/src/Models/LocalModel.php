<?php
// src/Models/LocalModel.php

use PhpOffice\PhpSpreadsheet\IOFactory;

class LocalModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getSucursales() {
        $sql = "SELECT NRO_SUCURSAL ID_SUCURSAL, COD_CLIENT, DESC_SUCURSAL FROM [LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS WHERE HABILITADO = 1 ORDER BY NRO_SUCURSAL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLocales($filterSucursal = null, $orderBy = 'NRO_SUCURS', $orderDir = 'ASC') {
        $validColumns = ['NRO_SUCURS', 'COD_CLIENT', 'NOMBRE_SUCURSAL'];
        $orderBy = in_array($orderBy, $validColumns) ? $orderBy : 'NRO_SUCURS';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT sm.NRO_SUCURS, sm.M2_VENTA, sm.M2_DEPOSITO, sm.M2_BAULERA, s.COD_CLIENT, s.DESC_SUCURSAL AS NOMBRE_SUCURSAL FROM FP_T_SUCURSALES_MEDIDAS sm LEFT JOIN [LAKERBIS].[LOCALES_LAKERS].[dbo].SUCURSALES_LAKERS s ON sm.NRO_SUCURS = CAST(s.NRO_SUCURSAL AS INT)";
        
        $params = [];
        if ($filterSucursal) {
            $sql .= " WHERE sm.NRO_SUCURS = :nro_sucurs";
            $params[':nro_sucurs'] = $filterSucursal;
        }
        
        $sql .= " ORDER BY $orderBy $orderDir";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addOrUpdateLocal($idSucursal, $m2Venta, $m2Deposito, $m2Baulera) {
        try {
            $checkSql = "SELECT COUNT(*) FROM FP_T_SUCURSALES_MEDIDAS WHERE NRO_SUCURS = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$idSucursal]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $sql = "UPDATE FP_T_SUCURSALES_MEDIDAS SET M2_VENTA = ?, M2_DEPOSITO = ?, M2_BAULERA = ?, FECHA_REGISTRO = GETDATE() WHERE NRO_SUCURS = ?";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([$m2Venta, $m2Deposito, $m2Baulera, $idSucursal]);
            } else {
                $sql = "INSERT INTO FP_T_SUCURSALES_MEDIDAS (NRO_SUCURS, M2_VENTA, M2_DEPOSITO, M2_BAULERA) VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([$idSucursal, $m2Venta, $m2Deposito, $m2Baulera]);
            }
        } catch (PDOException $e) {
            error_log("Error en addOrUpdateLocal: " . $e->getMessage());
            return false;
        }
    }

    public function deleteLocal($idSucursal) {
        try {
            $sql = "DELETE FROM FP_T_SUCURSALES_MEDIDAS WHERE NRO_SUCURS = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$idSucursal]);
        } catch (PDOException $e) {
            error_log("Error en deleteLocal: " . $e->getMessage());
            return false;
        }
    }

    public function processExcelImport($file) {
        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $headers = array_map('trim', array_map('strtoupper', array_shift($rows)));
            $requiredHeaders = ['NRO_SUCURS', 'M2_VENTA', 'M2_DEPOSITO', 'M2_BAULERA'];
            
            foreach ($requiredHeaders as $requiredHeader) {
                if (!in_array($requiredHeader, $headers)) {
                    return ['success' => false, 'message' => "El archivo Excel no es válido. Falta la columna: $requiredHeader"];
                }
            }

            $colMap = array_flip($headers);
            $processed = 0; $errors = 0;
            
            $this->pdo->beginTransaction();
            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue;
                $nroSucurs = trim($row[$colMap['NRO_SUCURS']]);
                $m2Venta = trim($row[$colMap['M2_VENTA']]);
                $m2Deposito = trim($row[$colMap['M2_DEPOSITO']]);
                $m2Baulera = trim($row[$colMap['M2_BAULERA']]);

                if (!is_numeric($nroSucurs) || !is_numeric($m2Venta) || !is_numeric($m2Deposito) || !is_numeric($m2Baulera)) {
                    $errors++; continue;
                }
                
                if ($this->addOrUpdateLocal($nroSucurs, $m2Venta, $m2Deposito, $m2Baulera)) {
                    $processed++;
                } else {
                    $errors++;
                }
            }
            $this->pdo->commit();
            
            $message = "Importación completada: $processed registros procesados. $errors errores.";
            return ['success' => true, 'message' => $message];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error al procesar el archivo Excel: ' . $e->getMessage()];
        }
    }
}