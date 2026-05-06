<?php
class StockBIFranquicias {
    private $conn;
    private $cacheDir;

    private const CACHE_FILTROS_TTL = 1800; // 30 minutos

    private const COL_MAP = [
        0  => 'ARTICULO',
        1  => 'DESCRIPCION',
        2  => 'NRO_SUCURSAL',
        3  => 'DESC_SUCURSAL',
        4  => 'TEMPORADA',
        5  => 'RUBRO',
        6  => 'CATEGORIA',
        7  => 'DESTINO',
        8  => 'GRUPO_EMPRESARIO',
        9  => 'MODELO',
        10 => 'CANT_STOCK',
    ];

    public function __construct() {
        $this->cacheDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        $this->conectar();
    }

    private function conectar(): void {
        $env    = $this->loadEnv();
        $server = $env['HOST_APPS'] ?? 'XL-APPS';
        $user   = $env['USER']      ?? 'sa';
        $pass   = $env['PASS']      ?? '';

        $this->conn = new PDO(
            "sqlsrv:server={$server};Database=POWER_BI_CONTROL_FRANQUICIAS",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    private function loadEnv(): array {
        $path = realpath(__DIR__ . '/../../../.env');
        if (!$path || !file_exists($path)) {
            throw new Exception("Archivo .env no encontrado desde " . __DIR__);
        }

        $vars = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            [$k, $v]       = explode('=', $line, 2);
            $vars[trim($k)] = trim($v);
        }
        return $vars;
    }

    private function buildWhere(array $f): array {
        $conds  = ["CANT_STOCK > 0", "RUBRO != '_KITS'"];
        $params = [];

        if (!empty($f['sucursal'])) {
            $conds[]             = "DESC_SUCURSAL = :sucursal";
            $params[':sucursal'] = $f['sucursal'];
        }
        if (!empty($f['temporada'])) {
            $conds[]              = "TEMPORADA = :temporada";
            $params[':temporada'] = $f['temporada'];
        }
        if (!empty($f['destino'])) {
            $conds[]            = "DESTINO = :destino";
            $params[':destino'] = $f['destino'];
        }
        if (!empty($f['rubros']) && is_array($f['rubros'])) {
            $ph = [];
            foreach ($f['rubros'] as $i => $v) {
                $k = ":rubro_{$i}"; $ph[] = $k; $params[$k] = $v;
            }
            $conds[] = "RUBRO IN (" . implode(',', $ph) . ")";
        }
        if (!empty($f['categorias']) && is_array($f['categorias'])) {
            $ph = [];
            foreach ($f['categorias'] as $i => $v) {
                $k = ":cat_{$i}"; $ph[] = $k; $params[$k] = $v;
            }
            $conds[] = "CATEGORIA IN (" . implode(',', $ph) . ")";
        }
        if (!empty($f['grupos']) && is_array($f['grupos'])) {
            $ph = [];
            foreach ($f['grupos'] as $i => $v) {
                $k = ":grupo_{$i}"; $ph[] = $k; $params[$k] = $v;
            }
            $conds[] = "GRUPO_EMPRESARIO IN (" . implode(',', $ph) . ")";
        }
        if (!empty($f['modelo'])) {
            $conds[]           = "MODELO LIKE :modelo";
            $params[':modelo'] = '%' . $f['modelo'] . '%';
        }
        if (!empty($f['articulo'])) {
            $conds[]           = "(ARTICULO LIKE :art1 OR DESCRIPCION LIKE :art2)";
            $params[':art1']   = '%' . $f['articulo'] . '%';
            $params[':art2']   = '%' . $f['articulo'] . '%';
        }
        if (!empty($f['search'])) {
            $s               = '%' . $f['search'] . '%';
            $conds[]         = "(ARTICULO LIKE :s1 OR DESCRIPCION LIKE :s2 OR MODELO LIKE :s3 OR DESC_SUCURSAL LIKE :s4)";
            $params[':s1']   = $s;
            $params[':s2']   = $s;
            $params[':s3']   = $s;
            $params[':s4']   = $s;
        }

        return ['WHERE ' . implode(' AND ', $conds), $params];
    }

    public function getData(array $f, int $start, int $length, int $colIdx, string $dir): array {
        $col    = self::COL_MAP[$colIdx] ?? 'DESC_SUCURSAL';
        $dir    = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $length = max(1, min($length, 500));

        [$where, $params] = $this->buildWhere($f);

        $stmtTotal = $this->conn->query(
            "SELECT COUNT(*) FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK) WHERE CANT_STOCK > 0 AND RUBRO != '_KITS'"
        );
        $recordsTotal = (int) $stmtTotal->fetchColumn();

        $stmtFiltered = $this->conn->prepare(
            "SELECT COUNT(*) FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK) $where"
        );
        $stmtFiltered->execute($params);
        $recordsFiltered = (int) $stmtFiltered->fetchColumn();

        // OFFSET/FETCH no acepta parámetros PDO en SQL Server — se inyectan como literales enteros ya validados
        $sql = "SELECT
                    ARTICULO, DESCRIPCION, DESC_SUCURSAL, NRO_SUCURSAL, TEMPORADA, RUBRO,
                    CATEGORIA, DESTINO, GRUPO_EMPRESARIO, MODELO, CANT_STOCK
                FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK)
                $where
                ORDER BY $col $dir
                OFFSET {$start} ROWS FETCH NEXT {$length} ROWS ONLY";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return [
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $stmt->fetchAll(),
        ];
    }

    public function getKPIs(array $f): array {
        [$where, $params] = $this->buildWhere($f);

        $sql = "SELECT
                    ISNULL(SUM(CANT_STOCK), 0)   AS stock_total,
                    COUNT(DISTINCT ARTICULO)      AS articulos_unicos,
                    COUNT(DISTINCT DESC_SUCURSAL) AS franquicias_con_stock
                FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK)
                $where";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function getFiltros(): array {
        $cacheFile = $this->cacheDir . 'filtros_bi_stock.json';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_FILTROS_TTL) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) return $cached;
        }

        $base = "FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK) WHERE CANT_STOCK > 0 AND RUBRO != '_KITS'";

        $queries = [
            'sucursales' => "SELECT DISTINCT DESC_SUCURSAL    AS v $base AND DESC_SUCURSAL    IS NOT NULL AND DESC_SUCURSAL    != '' ORDER BY DESC_SUCURSAL",
            'temporadas' => "SELECT DISTINCT TEMPORADA        AS v $base AND TEMPORADA        IS NOT NULL AND TEMPORADA        != '' ORDER BY TEMPORADA DESC",
            'rubros'     => "SELECT DISTINCT RUBRO            AS v $base AND RUBRO            IS NOT NULL AND RUBRO            != '' ORDER BY RUBRO",
            'categorias' => "SELECT DISTINCT CATEGORIA        AS v $base AND CATEGORIA        IS NOT NULL AND CATEGORIA        != '' ORDER BY CATEGORIA",
            'destinos'   => "SELECT DISTINCT DESTINO          AS v $base AND DESTINO          IS NOT NULL AND DESTINO          != '' ORDER BY DESTINO",
            'grupos'     => "SELECT DISTINCT GRUPO_EMPRESARIO AS v $base AND GRUPO_EMPRESARIO IS NOT NULL AND GRUPO_EMPRESARIO != '' ORDER BY GRUPO_EMPRESARIO",
        ];

        $result = [];
        foreach ($queries as $key => $sql) {
            $stmt          = $this->conn->query($sql);
            $result[$key]  = array_column($stmt->fetchAll(), 'v');
        }

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        file_put_contents($cacheFile, json_encode($result));

        return $result;
    }

    public function getExportData(array $f): array {
        [$where, $params] = $this->buildWhere($f);

        $sql = "SELECT TOP 100000
                    ARTICULO, DESCRIPCION, MODELO, CATEGORIA, RUBRO, TEMPORADA,
                    DESC_SUCURSAL AS FRANQUICIA, NRO_SUCURSAL, GRUPO_EMPRESARIO, DESTINO,
                    COD_DEPOSI, COD_CLIENT, CANT_STOCK
                FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK)
                $where
                ORDER BY DESC_SUCURSAL, ARTICULO";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function invalidarCache(): void {
        $cacheFile = $this->cacheDir . 'filtros_bi_stock.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}
