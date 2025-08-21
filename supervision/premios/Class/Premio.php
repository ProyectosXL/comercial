<?php

class Premio
{
    private $cid;
    private $cid_central;
    private const DB_PREFIX = '[LAKERBIS].LOCALES_LAKERS.DBO.';

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');
    } 

    private function retornarArray($sqlEnviado){
        $sql = $sqlEnviado;
        $stmt = sqlsrv_query($this->cid_central, $sql);
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;  
    }

    private function obtenerUltimoDiaMes($periodo) {
        // El periodo viene en formato YYYY-MM
        $año = substr($periodo, 0, 4);
        $mes = substr($periodo, 5, 2);
        return date('Y-m-d', strtotime("last day of $año-$mes"));
    }

    public function traerSupervisoras(){
        $sql = "SELECT ID, NOMBRE FROM " . self::DB_PREFIX . "RO_T_SUPERVISORAS_COMERCIAL WHERE ACTIVA = 1 ORDER BY ID";
        return $this->retornarArray($sql);
    }

    public function traerConceptos(){
        $sql = "SELECT COD_CONCEPTO, DESC_CONCEPTO FROM " . self::DB_PREFIX . "RO_T_CONCEPTO_PREMIOS_COMERCIAL ORDER BY ORDEN";
        return $this->retornarArray($sql);
    }

    public function esPeriodoActual($periodo) {
        $periodoActual = date('Y-m');
        return $periodo === $periodoActual;
    }

    public function actualizarPremios($data, $periodo) {
        try {
            $ultimoDiaMes = $this->obtenerUltimoDiaMes($periodo);

            $sqlUpdate = "UPDATE " . self::DB_PREFIX . "RO_T_PREMIOS_OBJETIVOS_VENTAS 
                         SET P_OBJ_VENTA = ?, 
                             P_OBJ_CREC_VTA = ?, 
                             P_TICKET_PROM = ?, 
                             P_TICKET_2PROD = ?, 
                             P_TICKET_3PROD = ?,
                             P_OBJ_VENTA_F = ?,
                             P_OBJ_CREC_VTA_F = ?,
                             FECHA_MOD = GETDATE()
                         WHERE ID_SUP = ? AND PERIODO = ?";

            $hasError = false;
            $errorMessage = '';
            $updatedRows = 0;

            foreach ($data as $row) {
                $params = array(
                    $row['P_OBJ_VENTA'],
                    $row['P_OBJ_CREC_VTA'],
                    $row['P_TICKET_PROM'],
                    $row['P_TICKET_2PROD'],
                    $row['P_TICKET_3PROD'],
                    $row['P_OBJ_VENTA'],   // P_OBJ_VENTA_F
                    $row['P_OBJ_CREC_VTA'], // P_OBJ_CREC_VTA_F
                    $row['ID_SUP'],
                    $ultimoDiaMes
                );

                $stmt = sqlsrv_query($this->cid_central, $sqlUpdate, $params);
                if ($stmt === false) {
                    $hasError = true;
                    $errorMessage = "Error al actualizar registro para supervisora ID: " . $row['ID_SUP'] . 
                                  " - " . print_r(sqlsrv_errors(), true);
                    break;
                } else {
                    $rowsAffected = sqlsrv_rows_affected($stmt);
                    $updatedRows += $rowsAffected;
                }
            }

            if ($hasError) {
                throw new Exception($errorMessage);
            }

            return $updatedRows;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function traerPremiosPorPeriodo($periodo) {
        try {
            $ultimoDiaMes = $this->obtenerUltimoDiaMes($periodo);
            
            $sql = "SELECT p.*, FORMAT(p.FECHA_MOD, 'dd/MM/yyyy HH:mm:ss') as FECHA_FORMATEADA 
                    FROM " . self::DB_PREFIX . "RO_T_PREMIOS_OBJETIVOS_VENTAS p 
                    WHERE p.PERIODO = ? 
                    ORDER BY p.ID_SUP";
            
            $params = array($ultimoDiaMes);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener los premios: " . print_r(sqlsrv_errors(), true));
            }

            $premios = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertimos las fechas a formato string para evitar problemas con JSON
                if (isset($row['FECHA_MOD']) && $row['FECHA_MOD'] instanceof DateTime) {
                    $row['FECHA_MOD'] = $row['FECHA_MOD']->format('Y-m-d H:i:s');
                }
                if (isset($row['PERIODO']) && $row['PERIODO'] instanceof DateTime) {
                    $row['PERIODO'] = $row['PERIODO']->format('Y-m-d');
                }
                $premios[] = $row;
            }

            return $premios;

        } catch (Exception $e) {
            error_log("Error en traerPremiosPorPeriodo: " . $e->getMessage());
            throw new Exception("Error al obtener los premios: " . $e->getMessage());
        }
    }
}