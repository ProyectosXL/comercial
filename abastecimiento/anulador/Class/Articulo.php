<?php

class Articulo
{
    private function retornarArray($sqlEnviado, $pais)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        $cid = new Conexion();
        
        $connName = ($pais === 'UY') ? 'uy' : 'central';
        $conexion = $cid->conectar($connName);

        if (!$conexion) {
            return false;
        }

        $stmt = sqlsrv_query($conexion, $sqlEnviado);
        if ($stmt === false) {
            return false;
        }

        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }

    private function ejecutarComando($sqlEnviado, $pais)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        $cid = new Conexion();
        
        $connName = ($pais === 'UY') ? 'uy' : 'central';
        $conexion = $cid->conectar($connName);

        if (!$conexion) {
            return false;
        }

        $stmt = sqlsrv_query($conexion, $sqlEnviado);
        if ($stmt === false) {
            return false;
        }
        return true;
    }

    public function validarArticulo($cod_articu, $pais)
    {
        $cod_articu = strval($cod_articu);
        $cod_articu = str_replace("'", "''", $cod_articu);

        $sql = "SELECT DESCRIPCIO, PERFIL FROM STA11 WHERE COD_ARTICU = '$cod_articu'";
        $rows = $this->retornarArray($sql, $pais);

        if ($rows && count($rows) > 0) {
            return $rows[0];
        }
        return null;
    }

    public function obtenerSugerenciasInhabilitacion($pais, $rubros = null)
    {
        $sql = "SELECT COD_ARTICU, DESCRIPCIO, FECHA_ALTA, RUBRO FROM RO_V_INHABILITAR_ARTICULOS WHERE 1=1";
        if ($rubros !== null && is_array($rubros) && count($rubros) > 0) {
            $escapedRubros = [];
            foreach ($rubros as $rubro) {
                $escaped = strval($rubro);
                $escaped = str_replace("'", "''", $escaped);
                $escapedRubros[] = "'$escaped'";
            }
            $sql .= " AND RUBRO IN (" . implode(",", $escapedRubros) . ")";
        }
        $sql .= " OPTION (HASH JOIN, MERGE JOIN)";
        return $this->retornarArray($sql, $pais);
    }

    public function obtenerRubros($pais)
    {
        $sql = "SELECT DISTINCT RUBRO FROM LAKER_SA.dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA WHERE RUBRO IS NOT NULL AND RUBRO <> '' ORDER BY RUBRO";
        return $this->retornarArray($sql, $pais);
    }

    public function inhabilitarArticulo($cod_articu, $pais)
    {
        $cod_articu = strval($cod_articu);
        $cod_articu = str_replace("'", "''", $cod_articu);

        $sql = "UPDATE STA11 SET PERFIL = 'N' WHERE COD_ARTICU = '$cod_articu'";
        return $this->ejecutarComando($sql, $pais);
    }
}
