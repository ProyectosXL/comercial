<?php
/**
 * Clase StockSupply
 * Consulta de stock supply pivot ejecutando EB_StockCentralPivot en Tango (LAKER_SA / TASKY_SA)
 * SOLO OPERACIONES DE LECTURA.
 */
class StockSupply {
    private $cid;

    public function __construct() {
        if (file_exists(__DIR__ . '/../../Class/Conexion.php')) {
            require_once __DIR__ . '/../../Class/Conexion.php';
        } elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        } else {
            require_once 'W:/comercial/Class/Conexion.php';
        }
        $this->cid = new Conexion();
    }

    private function getConn($pais = 'AR') {
        $serverKey = (strtoupper($pais) === 'UY') ? 'uy' : 'central';
        return $this->cid->conectar($serverKey);
    }

    public function getTemporadas($pais = 'AR') {
        $conn = $this->getConn($pais);
        if (!$conn) return [];

        $sql = "SELECT DISTINCT ISNULL(TEMPORADA, 'SIN') AS TEMPORADA 
                FROM SOF_MAESTRO_ARTICULOS_RUBRO_CATEGORIA WITH (NOLOCK)
                WHERE TEMPORADA IS NOT NULL AND TEMPORADA <> ''
                ORDER BY TEMPORADA DESC";
        $stmt = sqlsrv_query($conn, $sql);
        $temps = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $t = trim($row['TEMPORADA']);
                if ($t !== '') $temps[] = $t;
            }
            sqlsrv_free_stmt($stmt);
        }
        return $temps;
    }

    public function getCategorias($pais = 'AR') {
        $conn = $this->getConn($pais);
        if (!$conn) return [];

        $sql = "SELECT DISTINCT ISNULL(CATEGORIA, 'SIN') AS CATEGORIA 
                FROM SOF_MAESTRO_ARTICULOS_RUBRO_CATEGORIA WITH (NOLOCK)
                WHERE CATEGORIA IS NOT NULL AND CATEGORIA <> ''
                ORDER BY CATEGORIA ASC";
        $stmt = sqlsrv_query($conn, $sql);
        $cats = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $c = trim($row['CATEGORIA']);
                if ($c !== '') $cats[] = $c;
            }
            sqlsrv_free_stmt($stmt);
        }
        return $cats;
    }

    public function getRubros($pais = 'AR') {
        $conn = $this->getConn($pais);
        if (!$conn) return [];

        $sql = "SELECT DISTINCT B.DESCRIP AS RUBRO
                FROM STA11ITC A WITH (NOLOCK)
                INNER JOIN STA11FLD B WITH (NOLOCK) ON A.IDFOLDER = B.IDFOLDER
                INNER JOIN STA11 C WITH (NOLOCK) ON A.CODE = C.COD_ARTICU
                WHERE A.IDFOLDER NOT IN ('3','31')
                  AND NOT B.DESCRIP LIKE 'PROMO VILLA%'
                  AND B.DESCRIP NOT LIKE '[_]%'
                  AND C.USA_ESC != 'B'
                  AND A.CODE LIKE '[XO]%'
                  AND C.PERFIL != 'N'
                ORDER BY B.DESCRIP ASC";
        $stmt = sqlsrv_query($conn, $sql);
        $rubros = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $r = trim($row['RUBRO']);
                if ($r !== '') $rubros[] = $r;
            }
            sqlsrv_free_stmt($stmt);
        }
        return $rubros;
    }

    public function getPivotData($rubro = '', $categoria = '', $temporada = '', $pais = 'AR') {
        $conn = $this->getConn($pais);
        if (!$conn) return ['columns' => [], 'data' => []];

        $sql = "EXEC EB_StockCentralPivot";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            return ['columns' => [], 'data' => []];
        }

        $meta = sqlsrv_field_metadata($stmt);
        $columns = [];
        if ($meta) {
            foreach ($meta as $field) {
                $columns[] = $field['Name'];
            }
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Filtrar en memoria como hace Python
            if (!empty($rubro) && isset($row['RUBRO']) && trim($row['RUBRO']) !== trim($rubro)) {
                continue;
            }
            if (!empty($categoria) && isset($row['CATEGORIA']) && trim($row['CATEGORIA']) !== trim($categoria)) {
                continue;
            }
            if (!empty($temporada) && isset($row['TEMPORADA']) && trim($row['TEMPORADA']) !== trim($temporada)) {
                continue;
            }

            // Sanitizar valores null / strings
            $cleaned = [];
            foreach ($row as $k => $v) {
                $cleaned[$k] = is_string($v) ? trim($v) : $v;
            }
            $rows[] = $cleaned;
        }
        sqlsrv_free_stmt($stmt);

        return [
            'columns' => $columns,
            'data'    => $rows
        ];
    }
}
