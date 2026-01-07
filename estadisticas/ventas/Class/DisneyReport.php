<?php

class DisneyReport
{
    private $cid;
    private $cid_apps;

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        $this->cid = new Conexion();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $db = 'power';
        $this->cid_apps = $this->cid->conectar($db);
        
        if ($this->cid_apps  === false) {
            throw new Exception("Error al conectar a la base de datos POWER_BI_CONTROL: " . print_r(sqlsrv_errors(), true));
        }
    }
    
    public function obtenerVentasDisney($fechaDesde, $fechaHasta)
    {
        // Aumentar tiempo de ejecución para consultas grandes
        set_time_limit(300); // 5 minutos
        
        $sql = "
            SELECT 
                COD_ARTICU,
                DESC_CTA_ARTICULO,
                YEAR(FECHA) AS ANIO,
                MONTH(FECHA) AS MES,
                SUM(CANTIDAD) AS CANTIDAD,
                CAST(SUM(IMPORTE) AS INT) AS IMPORTE
            FROM BI_T_SALES_LAKERS_ARTICULO WITH (NOLOCK)
            WHERE DESC_CTA_ARTICULO LIKE 'DISNEY%' 
                AND COD_ARTICU NOT LIKE 'XPAK%'
                AND FECHA BETWEEN ? AND ?
                AND CANTIDAD > 0
            GROUP BY 
                COD_ARTICU,
                DESC_CTA_ARTICULO,
                YEAR(FECHA),
                MONTH(FECHA)
            HAVING SUM(CANTIDAD) > 0
            ORDER BY 
                DESC_CTA_ARTICULO,
                YEAR(FECHA),
                MONTH(FECHA)
        ";
        
        $params = array($fechaDesde, $fechaHasta);
        $stmt = sqlsrv_query($this->cid_apps, $sql, $params, array('QueryTimeout' => 240));

        if ($stmt === false) {
            throw new Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }

        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    public function cerrarConexion()
    {
        if ($this->cid_apps) {
            sqlsrv_close($this->cid_apps);
        }
    }
}
