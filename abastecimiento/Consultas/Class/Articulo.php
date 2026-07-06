<?php

class Articulo
{
    private function retornarArrayConParametros($sqlEnviado, $params, $pais)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        $cid = new Conexion();
        
        $connName = ($pais === 'UY') ? 'uy' : 'central';
        $conexion = $cid->conectar($connName);

        if (!$conexion) {
            return false;
        }

        $stmt = sqlsrv_prepare($conexion, $sqlEnviado, $params);
        if ($stmt === false) {
            return false;
        }

        $result = sqlsrv_execute($stmt);
        if ($result === false) {
            return false;
        }

        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }

    public function buscarArticuloPorCodigo($codArticulo, $pais)
    {
        if (empty($codArticulo)) {
            return [];
        }

        $sql = "SELECT 
                    A.COD_ARTICU, 
                    ISNULL(A.DESCRIPCION, '') AS DESCRIPCION, 
                    ISNULL(A.DESTINO, '') AS DESTINO, 
                    ISNULL(A.TEMPORADA, '') AS TEMPORADA, 
                    ISNULL(B.RUBRO, '') AS RUBRO, 
                    ISNULL(A.LIQUIDACION, '0') AS LIQUIDACION, 
                    ISNULL(C.PRECIO, 0) AS PRECIO, 
                    ISNULL(CAST((C.PRECIO/(1+(E.PORCENTAJE/100))) AS DECIMAL(10,2)), 0) AS PRECIO_S_IVA
                FROM MAESTRO_DESTINOS A
                LEFT JOIN SOF_RUBROS_TANGO B ON A.COD_ARTICU = B.COD_ARTICU
                LEFT JOIN (SELECT COD_ARTICU, PRECIO FROM GVA17 WHERE NRO_DE_LIS = 20) C ON A.COD_ARTICU = C.COD_ARTICU
                LEFT JOIN STA11 D ON A.COD_ARTICU = D.COD_ARTICU
                LEFT JOIN GVA41 E ON D.ID_GVA41_COD_IVA = E.ID_GVA41
                WHERE A.COD_ARTICU = ?";

        $result = $this->retornarArrayConParametros($sql, array($codArticulo), $pais);
        
        if (!empty($result)) {
            foreach ($result as &$row) {
                // Sanitize numeric fields
                if (isset($row['PRECIO']) && $row['PRECIO'] === null) {
                    $row['PRECIO'] = 0;
                }
                if (isset($row['PRECIO_S_IVA']) && $row['PRECIO_S_IVA'] === null) {
                    $row['PRECIO_S_IVA'] = 0;
                }
                
                // Sanitize text fields
                $textFields = ['DESCRIPCION', 'DESTINO', 'TEMPORADA', 'RUBRO'];
                foreach ($textFields as $field) {
                    if (isset($row[$field]) && $row[$field] === null) {
                        $row[$field] = '';
                    }
                }
                
                // Normalize liquidacion
                if (isset($row['LIQUIDACION'])) {
                    if ($row['LIQUIDACION'] === '1' || $row['LIQUIDACION'] === 1 || 
                        strtoupper($row['LIQUIDACION']) === 'SI' || strtoupper($row['LIQUIDACION']) === 'YES' || 
                        strtoupper($row['LIQUIDACION']) === 'TRUE') {
                        $row['LIQUIDACION'] = 'SI';
                    } else {
                        $row['LIQUIDACION'] = 'NO';
                    }
                } else {
                    $row['LIQUIDACION'] = 'NO';
                }
            }
        }
        
        return $result;
    }
}
