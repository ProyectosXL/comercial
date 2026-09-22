<?php
/**
 * Clase StockCentral
 * Consulta de stock central en Tango (LAKER_SA para AR / TASKY_SA para UY)
 * SOLO OPERACIONES DE LECTURA (SELECT).
 */
class StockCentral {
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

    public function getDepositos($pais = 'AR') {
        $conn = $this->getConn($pais);
        if (!$conn) return [];
        
        $sql = "SELECT DISTINCT s.COD_DEPOSI AS COD_SUCURS, ISNULL(t.NOMBRE_SUC, 'Depósito ' + s.COD_DEPOSI) AS NOMBRE_SUC
                FROM STOCK_CENTRAL s WITH (NOLOCK)
                LEFT JOIN STA22 t WITH (NOLOCK) ON s.COD_DEPOSI = t.COD_SUCURS COLLATE Modern_Spanish_CI_AS
                WHERE s.COD_DEPOSI IS NOT NULL AND s.COD_DEPOSI <> ''
                ORDER BY COD_SUCURS ASC";
        $stmt = sqlsrv_query($conn, $sql);
        $depos = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $depos[] = [
                    'codigo' => trim($row['COD_SUCURS']),
                    'nombre' => trim($row['NOMBRE_SUC'])
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        return $depos;
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

    public function getStock($deposito = '', $temporada = '', $rubro = '', $busqueda = '', $pais = 'AR') {
        $conn = $this->getConn($pais);
        if (!$conn) return [];

        $where = [];
        $params = [];

        // Por defecto si no viene depósito, filtrar por 05 o 01
        if (!empty($deposito)) {
            $where[] = "COD_DEPOSI = ?";
            $params[] = trim($deposito);
        }

        if (!empty($temporada)) {
            $where[] = "TEMPORADA = ?";
            $params[] = trim($temporada);
        }

        if (!empty($rubro)) {
            $where[] = "RUBRO = ?";
            $params[] = trim($rubro);
        }

        if (!empty($busqueda)) {
            $where[] = "(COD_ARTICU LIKE ? OR DESCRIPCION LIKE ? OR DESTINO LIKE ? OR CATEGORIA LIKE ? OR COLOR LIKE ?)";
            $b = '%' . trim($busqueda) . '%';
            $params[] = $b;
            $params[] = $b;
            $params[] = $b;
            $params[] = $b;
            $params[] = $b;
        }

        $sql = "SELECT COD_ARTICU, DESCRIPCION, COD_DEPOSI, STOCK_TOTAL, CANT_COMP, CANT_RESERVA, 
                       STOCK_EXCLUIDO, STOCK_DISPONIBLE, DESTINO, RUBRO, CATEGORIA, TEMPORADA, COLOR
                FROM STOCK_CENTRAL WITH (NOLOCK)";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        } else {
            // Default filter para evitar timeout en primera carga
            $sql .= " WHERE COD_DEPOSI = '05'";
        }

        $sql .= " ORDER BY COD_ARTICU ASC";

        $stmt = sqlsrv_query($conn, $sql, $params);
        $registros = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $registros[] = [
                    'articulo'    => trim($row['COD_ARTICU'] ?? ''),
                    'descripcion' => trim($row['DESCRIPCION'] ?? ''),
                    'deposito'    => trim($row['COD_DEPOSI'] ?? ''),
                    'rubro'       => trim($row['RUBRO'] ?? ''),
                    'disponible'  => (float)($row['STOCK_DISPONIBLE'] ?? 0),
                    'reserva'     => (int)($row['CANT_RESERVA'] ?? 0),
                    'comp'        => (float)($row['CANT_COMP'] ?? 0),
                    'total'       => (float)($row['STOCK_TOTAL'] ?? 0),
                    'destino'     => trim($row['DESTINO'] ?? ''),
                    'categoria'   => trim($row['CATEGORIA'] ?? ''),
                    'color'       => trim($row['COLOR'] ?? ''),
                    'temporada'   => trim($row['TEMPORADA'] ?? ''),
                    'excluido'    => (int)($row['STOCK_EXCLUIDO'] ?? 0),
                ];
            }
            sqlsrv_free_stmt($stmt);
        }

        return $registros;
    }
}
