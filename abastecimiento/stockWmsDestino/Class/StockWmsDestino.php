<?php
/**
 * Clase StockWmsDestino
 * Consulta de stock físico por depósito, ubicación y destino en el sistema WMS (XL-SALES\SQLEXPRESS - UbicacionesStockMvc)
 * Tabla/Vista: RO_STOCK_WMS_DESTINO
 * SOLO OPERACIONES DE LECTURA (SELECT).
 */
class StockWmsDestino {
    private $cid;
    private $cacheFile;

    public function __construct() {
        if (file_exists('W:/comercial/Class/Conexion.php')) {
            require_once 'W:/comercial/Class/Conexion.php';
        } elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        } else {
            require_once __DIR__ . '/../../../Class/Conexion.php';
        }
        $this->cid = new Conexion();
        $this->cacheFile = __DIR__ . '/cache_filtros_wms.json';
    }

    private function getConn() {
        return $this->cid->conectar('wms');
    }

    /**
     * Carga y cachea los combos de filtros (Tipo Ubicación, Destino, Rubro, Temporada, Depósitos)
     * Utiliza un archivo JSON local con TTL de 2 horas para evitar queries repetitivas pesadas de 30s.
     */
    public function getFiltros($forceRefresh = false) {
        $ttl = 7200; // 2 horas
        if (!$forceRefresh && file_exists($this->cacheFile) && (time() - filemtime($this->cacheFile) < $ttl)) {
            $cached = json_decode(file_get_contents($this->cacheFile), true);
            if (!empty($cached) && is_array($cached)) {
                return $cached;
            }
        }

        $conn = $this->getConn();
        if (!$conn) {
            // Si falla conexión pero existe cache previo, devolver cache
            if (file_exists($this->cacheFile)) {
                return json_decode(file_get_contents($this->cacheFile), true) ?: [];
            }
            return [
                'depositos'      => [],
                'tiposUbicacion' => [],
                'destinos'       => [],
                'rubros'         => [],
                'temporadas'     => []
            ];
        }

        // 1. Obtener Depósitos
        $sqlDep = "SELECT DISTINCT DEPOSITO FROM RO_STOCK_WMS_DESTINO WITH (NOLOCK) WHERE DEPOSITO IS NOT NULL AND DEPOSITO <> '' ORDER BY DEPOSITO ASC";
        $stmtDep = sqlsrv_query($conn, $sqlDep);
        $depositos = [];
        if ($stmtDep) {
            while ($r = sqlsrv_fetch_array($stmtDep, SQLSRV_FETCH_ASSOC)) {
                $dep = trim($r['DEPOSITO']);
                if ($dep !== '') {
                    $depositos[] = $dep;
                }
            }
            sqlsrv_free_stmt($stmtDep);
        }

        // 2. Obtener Tipo Ubicacion, Destino, Rubro, Temporada
        $sqlCombos = "SELECT DISTINCT TIPO_UBICACION, DESTINO, RUBRO, TEMPORADA FROM RO_STOCK_WMS_DESTINO WITH (NOLOCK)";
        $stmtCombos = sqlsrv_query($conn, $sqlCombos);
        $tipos = [];
        $destinos = [];
        $rubros = [];
        $temporadas = [];

        if ($stmtCombos) {
            while ($row = sqlsrv_fetch_array($stmtCombos, SQLSRV_FETCH_ASSOC)) {
                $t = trim($row['TIPO_UBICACION'] ?? '');
                $d = trim($row['DESTINO'] ?? '');
                $r = trim($row['RUBRO'] ?? '');
                $temp = trim($row['TEMPORADA'] ?? '');

                if ($t !== '') $tipos[$t] = true;
                if ($d !== '') $destinos[$d] = true;
                if ($r !== '') $rubros[$r] = true;
                if ($temp !== '') $temporadas[$temp] = true;
            }
            sqlsrv_free_stmt($stmtCombos);
        }

        $tiposList = array_keys($tipos);
        sort($tiposList);

        $destinosList = array_keys($destinos);
        sort($destinosList);

        $rubrosList = array_keys($rubros);
        sort($rubrosList);

        $temporadasList = array_keys($temporadas);
        sort($temporadasList);

        $result = [
            'depositos'      => $depositos,
            'tiposUbicacion' => $tiposList,
            'destinos'       => $destinosList,
            'rubros'         => $rubrosList,
            'temporadas'     => $temporadasList,
            'updated_at'     => date('Y-m-d H:i:s')
        ];

        @file_put_contents($this->cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE));
        return $result;
    }

    /**
     * Consulta el stock WMS por destino según los filtros aplicados.
     */
    public function getStock($filtros = [], $accesoRestringido = false) {
        $conn = $this->getConn();
        if (!$conn) return [];

        $where = [];
        $params = [];

        // Si el usuario tiene acceso restringido (no es Logística ni Admin), solo ve PICKING
        if ($accesoRestringido) {
            $where[] = "TIPO_UBICACION = 'PICKING'";
        } elseif (!empty($filtros['tipo_ubicacion'])) {
            $where[] = "TIPO_UBICACION = ?";
            $params[] = trim($filtros['tipo_ubicacion']);
        }

        if (!empty($filtros['deposito'])) {
            $where[] = "DEPOSITO = ?";
            $params[] = trim($filtros['deposito']);
        }

        if (!empty($filtros['ubicacion'])) {
            $where[] = "UBICACION LIKE ?";
            $params[] = '%' . trim($filtros['ubicacion']) . '%';
        }

        if (!empty($filtros['cod_articulo'])) {
            $where[] = "COD_ARTICULO LIKE ?";
            $params[] = '%' . trim($filtros['cod_articulo']) . '%';
        }

        if (!empty($filtros['destino'])) {
            $where[] = "DESTINO = ?";
            $params[] = trim($filtros['destino']);
        }

        if (!empty($filtros['rubro'])) {
            $where[] = "RUBRO = ?";
            $params[] = trim($filtros['rubro']);
        }

        if (!empty($filtros['temporada'])) {
            $where[] = "TEMPORADA = ?";
            $params[] = trim($filtros['temporada']);
        }

        if (!empty($filtros['busqueda'])) {
            $where[] = "(COD_ARTICULO LIKE ? OR DESCRIPCION LIKE ? OR UBICACION LIKE ? OR DESTINO LIKE ? OR RUBRO LIKE ?)";
            $b = '%' . trim($filtros['busqueda']) . '%';
            $params[] = $b;
            $params[] = $b;
            $params[] = $b;
            $params[] = $b;
            $params[] = $b;
        }

        $sql = "SELECT DEPOSITO, UBICACION, COD_ARTICULO, DESCRIPCION, STOCK_UBIC, TIPO_UBICACION, DESTINO, RUBRO, TEMPORADA
                FROM RO_STOCK_WMS_DESTINO WITH (NOLOCK)";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        } else {
            // Filtro por defecto para evitar sobrecargar si se abre sin filtros
            $sql .= " WHERE DEPOSITO = '01'";
        }

        $sql .= " ORDER BY DEPOSITO ASC, UBICACION ASC, COD_ARTICULO ASC";

        $stmt = sqlsrv_query($conn, $sql, $params);
        $registros = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $registros[] = [
                    'deposito'       => trim($row['DEPOSITO'] ?? ''),
                    'ubicacion'      => trim($row['UBICACION'] ?? ''),
                    'cod_articulo'   => trim($row['COD_ARTICULO'] ?? ''),
                    'descripcion'    => trim($row['DESCRIPCION'] ?? ''),
                    'stock_ubic'     => (int)($row['STOCK_UBIC'] ?? 0),
                    'tipo_ubicacion' => trim($row['TIPO_UBICACION'] ?? ''),
                    'destino'        => trim($row['DESTINO'] ?? ''),
                    'rubro'          => trim($row['RUBRO'] ?? ''),
                    'temporada'      => trim($row['TEMPORADA'] ?? ''),
                ];
            }
            sqlsrv_free_stmt($stmt);
        }

        return $registros;
    }
}
