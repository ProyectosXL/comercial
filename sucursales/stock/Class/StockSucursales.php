<?php
/**
 * Clase StockSucursales
 * Consulta de stock de sucursales en LOCALES_LAKERS (AR) y SUCURSALES_URUGUAY (UY)
 * Vista: SOF_STOCK_LAKERS
 * SOLO LECTURA (SELECT).
 */
class StockSucursales {
    private $cid;

    public function __construct() {
        if (file_exists(__DIR__ . '/../../../Class/Conexion.php')) {
            require_once __DIR__ . '/../../../Class/Conexion.php';
        } elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        } else {
            require_once 'W:/comercial/Class/Conexion.php';
        }
        $this->cid = new Conexion();
    }

    private function getConn($pais = 'AR') {
        $serverKey = (strtoupper($pais) === 'UY') ? 'suc_uy' : 'locales';
        return $this->cid->conectar($serverKey);
    }

    public function getSucursalesLista($pais = 'AR') {
        $conn = $this->getConn($pais);
        if (!$conn) return [];

        $sql = "SELECT DISTINCT DESC_SUCURSAL 
                FROM SOF_STOCK_LAKERS WITH (NOLOCK) 
                WHERE DESC_SUCURSAL IS NOT NULL AND DESC_SUCURSAL <> ''
                ORDER BY DESC_SUCURSAL ASC";
        $stmt = sqlsrv_query($conn, $sql);
        $sucs = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $s = trim($row['DESC_SUCURSAL']);
                if ($s !== '') $sucs[] = $s;
            }
            sqlsrv_free_stmt($stmt);
        }
        return $sucs;
    }

    public function getTemporadas($pais = 'AR') {
        $conn = $this->getConn($pais);
        if (!$conn) return [];

        $sql = "SELECT DISTINCT TEMPORADA 
                FROM SOF_STOCK_LAKERS WITH (NOLOCK) 
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

        $sql = "SELECT DISTINCT RUBRO 
                FROM SOF_STOCK_LAKERS WITH (NOLOCK) 
                WHERE RUBRO IS NOT NULL AND RUBRO <> ''
                ORDER BY RUBRO ASC";
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

    public function getStockSucursales($sucursal = '', $temporada = '', $rubro = '', $busqueda = '', $pais = 'AR') {
        $conn = $this->getConn($pais);
        if (!$conn) return [];

        $where = [];
        $params = [];

        if (!empty($sucursal)) {
            $where[] = "DESC_SUCURSAL = ?";
            $params[] = trim($sucursal);
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
            $where[] = "(ARTICULO LIKE ? OR DESCRIPCION LIKE ? OR COD_CLIENT LIKE ? OR DESTINO LIKE ? OR CATEGORIA LIKE ?)";
            $b = '%' . trim($busqueda) . '%';
            $params[] = $b;
            $params[] = $b;
            $params[] = $b;
            $params[] = $b;
            $params[] = $b;
        }

        $sql = "SELECT ARTICULO, DESCRIPCION, NRO_SUCURSAL, COD_CLIENT, DESC_SUCURSAL, 
                       COD_DEPOSI, CANT_STOCK, RUBRO, CATEGORIA, TEMPORADA, DESTINO, PRECIO
                FROM SOF_STOCK_LAKERS WITH (NOLOCK)";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY DESC_SUCURSAL ASC, ARTICULO ASC";

        $stmt = sqlsrv_query($conn, $sql, $params);
        $registros = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $registros[] = [
                    'articulo'      => trim($row['ARTICULO'] ?? ''),
                    'descripcion'   => trim($row['DESCRIPCION'] ?? ''),
                    'nro_sucursal'  => (int)($row['NRO_SUCURSAL'] ?? 0),
                    'cod_client'    => trim($row['COD_CLIENT'] ?? ''),
                    'desc_sucursal' => trim($row['DESC_SUCURSAL'] ?? ''),
                    'cod_deposi'    => trim($row['COD_DEPOSI'] ?? ''),
                    'cant_stock'    => (float)($row['CANT_STOCK'] ?? 0),
                    'rubro'         => trim($row['RUBRO'] ?? ''),
                    'categoria'     => trim($row['CATEGORIA'] ?? ''),
                    'temporada'     => trim($row['TEMPORADA'] ?? ''),
                    'destino'       => trim($row['DESTINO'] ?? ''),
                    'precio'        => (float)($row['PRECIO'] ?? 0),
                ];
            }
            sqlsrv_free_stmt($stmt);
        }

        return $registros;
    }
}
