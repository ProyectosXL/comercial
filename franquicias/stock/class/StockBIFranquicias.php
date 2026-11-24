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
        10 => 'LIQUIDACION',
        11 => 'CANT_STOCK',
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
        $path = realpath(__DIR__ . '/../../../../.env');
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

    private function sucursalJoinSql(string $biAlias = 'BI'): string {
        return "LEFT JOIN (
                    SELECT NRO_SUCURSAL, MAX(LTRIM(RTRIM(DESC_SUCURSAL))) AS DESC_SUCURSAL
                    FROM [XL-LAKERBIS].[locales_lakers].[dbo].[SUCURSALES_LAKERS] WITH (NOLOCK)
                    WHERE DESC_SUCURSAL IS NOT NULL AND LTRIM(RTRIM(DESC_SUCURSAL)) != ''
                    GROUP BY NRO_SUCURSAL
                ) SL ON SL.NRO_SUCURSAL = {$biAlias}.NRO_SUCURSAL";
    }

    private function sucursalNombreExpr(string $biAlias = 'BI'): string {
        return "COALESCE(NULLIF(LTRIM(RTRIM(SL.DESC_SUCURSAL COLLATE database_default)), ''), {$biAlias}.DESC_SUCURSAL)";
    }

    private function needsSucursalJoin(array $f, string $col = ''): bool {
        return (!empty($f['sucursal']) && is_array($f['sucursal']))
            || !empty($f['search'])
            || $col === 'DESC_SUCURSAL';
    }

    private function buildWhere(array $f): array {
        $conds   = ["BI.CANT_STOCK > 0", "BI.RUBRO != '_KITS'"];
        $params  = [];
        $sucExpr = $this->sucursalNombreExpr('BI');

        if (!empty($f['sucursal']) && is_array($f['sucursal'])) {
            $ph = [];
            foreach ($f['sucursal'] as $i => $v) {
                $k = ":sucursal_{$i}"; $ph[] = $k; $params[$k] = $v;
            }
            $conds[] = "{$sucExpr} IN (" . implode(',', $ph) . ")";
        }
        if (!empty($f['temporada'])) {
            $conds[]              = "BI.TEMPORADA = :temporada";
            $params[':temporada'] = $f['temporada'];
        }
        if (!empty($f['destino']) && is_array($f['destino'])) {
            $ph = [];
            foreach ($f['destino'] as $i => $v) {
                $k = ":destino_{$i}"; $ph[] = $k; $params[$k] = $v;
            }
            $conds[] = "BI.DESTINO IN (" . implode(',', $ph) . ")";
        }
        if (!empty($f['rubros']) && is_array($f['rubros'])) {
            $ph = [];
            foreach ($f['rubros'] as $i => $v) {
                $k = ":rubro_{$i}"; $ph[] = $k; $params[$k] = $v;
            }
            $conds[] = "BI.RUBRO IN (" . implode(',', $ph) . ")";
        }
        if (!empty($f['categorias']) && is_array($f['categorias'])) {
            $ph = [];
            foreach ($f['categorias'] as $i => $v) {
                $k = ":cat_{$i}"; $ph[] = $k; $params[$k] = $v;
            }
            $conds[] = "BI.CATEGORIA IN (" . implode(',', $ph) . ")";
        }
        if (!empty($f['grupos']) && is_array($f['grupos'])) {
            $ph = [];
            foreach ($f['grupos'] as $i => $v) {
                $k = ":grupo_{$i}"; $ph[] = $k; $params[$k] = $v;
            }
            $conds[] = "BI.GRUPO_EMPRESARIO IN (" . implode(',', $ph) . ")";
        }
        if (!empty($f['modelo'])) {
            $conds[]           = "BI.MODELO LIKE :modelo";
            $params[':modelo'] = '%' . $f['modelo'] . '%';
        }
        if (!empty($f['articulo'])) {
            $conds[]           = "(BI.ARTICULO LIKE :art1 OR BI.DESCRIPCION LIKE :art2)";
            $params[':art1']   = '%' . $f['articulo'] . '%';
            $params[':art2']   = '%' . $f['articulo'] . '%';
        }
        if (isset($f['liquidacion']) && $f['liquidacion'] !== '') {
            if ($f['liquidacion'] === 'SI') {
                $conds[] = "MD.LIQUIDACION COLLATE database_default = 'SI'";
            } elseif ($f['liquidacion'] === 'NO') {
                $conds[] = "(MD.LIQUIDACION IS NULL OR MD.LIQUIDACION COLLATE database_default = 'NO' OR MD.LIQUIDACION COLLATE database_default = '')";
            }
        }
        if (!empty($f['search'])) {
            $s               = '%' . $f['search'] . '%';
            $conds[]         = "(BI.ARTICULO LIKE :s1 OR BI.DESCRIPCION LIKE :s2 OR BI.MODELO LIKE :s3 OR {$sucExpr} LIKE :s4)";
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

        $joinMD       = (isset($f['liquidacion']) && $f['liquidacion'] !== '') || $col === 'LIQUIDACION';
        $joinSLFilter = $this->needsSucursalJoin($f);
        $joinSLInside = $this->needsSucursalJoin($f, $col);

        $sucExprBI    = $this->sucursalNombreExpr('BI');
        $joinMDSql    = $joinMD
            ? "LEFT JOIN [XL-LAKERBIS].[locales_lakers].[dbo].[MAESTRO_DESTINOS] MD ON MD.COD_ARTICU COLLATE database_default = BI.ARTICULO COLLATE database_default"
            : "";
        $joinSLSql    = $joinSLFilter ? $this->sucursalJoinSql('BI') : "";

        [$where, $params] = $this->buildWhere($f);

        $stmtTotal = $this->conn->query(
            "SELECT COUNT(*) FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK) WHERE CANT_STOCK > 0 AND RUBRO != '_KITS'"
        );
        $recordsTotal = (int) $stmtTotal->fetchColumn();

        $stmtFiltered = $this->conn->prepare(
            "SELECT COUNT(*) 
             FROM BI_STOCK_FRANQUICIAS BI WITH (NOLOCK)
             $joinSLSql
             $joinMDSql
             $where"
        );
        $stmtFiltered->execute($params);
        $recordsFiltered = (int) $stmtFiltered->fetchColumn();

        // OFFSET/FETCH no acepta parámetros PDO en SQL Server — se inyectan como literales enteros ya validados
        if ($joinMD) {
            if ($col === 'LIQUIDACION') {
                $orderSql = "MD.LIQUIDACION";
            } elseif ($col === 'DESC_SUCURSAL') {
                $orderSql = $sucExprBI;
            } else {
                $orderSql = "BI.{$col}";
            }
            $slJoin = $this->sucursalJoinSql('BI');
            $sql = "SELECT
                        BI.ARTICULO, BI.DESCRIPCION, {$sucExprBI} AS DESC_SUCURSAL, BI.NRO_SUCURSAL, BI.TEMPORADA, BI.RUBRO,
                        BI.CATEGORIA, BI.DESTINO, BI.GRUPO_EMPRESARIO, BI.MODELO, BI.CANT_STOCK,
                        MD.LIQUIDACION AS LIQUIDACION
                    FROM BI_STOCK_FRANQUICIAS BI WITH (NOLOCK)
                    $slJoin
                    LEFT JOIN [XL-LAKERBIS].[locales_lakers].[dbo].[MAESTRO_DESTINOS] MD ON MD.COD_ARTICU COLLATE database_default = BI.ARTICULO COLLATE database_default
                    $where
                    ORDER BY $orderSql $dir
                    OFFSET {$start} ROWS FETCH NEXT {$length} ROWS ONLY";
        } elseif ($joinSLInside) {
            $slJoin   = $this->sucursalJoinSql('BI');
            $orderSql = $col === 'DESC_SUCURSAL' ? $sucExprBI : "BI.{$col}";
            $sql = "SELECT
                        Paged.*,
                        MD.LIQUIDACION AS LIQUIDACION
                    FROM (
                        SELECT
                            BI.ARTICULO, BI.DESCRIPCION, {$sucExprBI} AS DESC_SUCURSAL, BI.NRO_SUCURSAL, BI.TEMPORADA, BI.RUBRO,
                            BI.CATEGORIA, BI.DESTINO, BI.GRUPO_EMPRESARIO, BI.MODELO, BI.CANT_STOCK
                        FROM BI_STOCK_FRANQUICIAS BI WITH (NOLOCK)
                        $slJoin
                        $where
                        ORDER BY $orderSql $dir
                        OFFSET {$start} ROWS FETCH NEXT {$length} ROWS ONLY
                    ) Paged
                    LEFT JOIN [XL-LAKERBIS].[locales_lakers].[dbo].[MAESTRO_DESTINOS] MD ON MD.COD_ARTICU COLLATE database_default = Paged.ARTICULO COLLATE database_default";
        } else {
            $slJoinPaged = $this->sucursalJoinSql('Paged');
            $sucExprOut  = "COALESCE(NULLIF(LTRIM(RTRIM(SL.DESC_SUCURSAL COLLATE database_default)), ''), Paged.BI_DESC_SUCURSAL)";
            $sql = "SELECT
                        Paged.ARTICULO, Paged.DESCRIPCION, {$sucExprOut} AS DESC_SUCURSAL, Paged.NRO_SUCURSAL, Paged.TEMPORADA, Paged.RUBRO,
                        Paged.CATEGORIA, Paged.DESTINO, Paged.GRUPO_EMPRESARIO, Paged.MODELO, Paged.CANT_STOCK,
                        MD.LIQUIDACION AS LIQUIDACION
                    FROM (
                        SELECT
                            BI.ARTICULO, BI.DESCRIPCION, BI.DESC_SUCURSAL AS BI_DESC_SUCURSAL, BI.NRO_SUCURSAL, BI.TEMPORADA, BI.RUBRO,
                            BI.CATEGORIA, BI.DESTINO, BI.GRUPO_EMPRESARIO, BI.MODELO, BI.CANT_STOCK
                        FROM BI_STOCK_FRANQUICIAS BI WITH (NOLOCK)
                        $where
                        ORDER BY BI.{$col} $dir
                        OFFSET {$start} ROWS FETCH NEXT {$length} ROWS ONLY
                    ) Paged
                    $slJoinPaged
                    LEFT JOIN [XL-LAKERBIS].[locales_lakers].[dbo].[MAESTRO_DESTINOS] MD ON MD.COD_ARTICU COLLATE database_default = Paged.ARTICULO COLLATE database_default";
        }

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

        $joinMD    = isset($f['liquidacion']) && $f['liquidacion'] !== '';
        $slJoin    = $this->sucursalJoinSql('BI');
        $sucExprBI = $this->sucursalNombreExpr('BI');
        $joinSql   = $joinMD
            ? "LEFT JOIN [XL-LAKERBIS].[locales_lakers].[dbo].[MAESTRO_DESTINOS] MD ON MD.COD_ARTICU COLLATE database_default = BI.ARTICULO COLLATE database_default"
            : "";

        $sql = "SELECT
                    ISNULL(SUM(BI.CANT_STOCK), 0) AS stock_total,
                    COUNT(DISTINCT BI.ARTICULO)    AS articulos_unicos,
                    COUNT(DISTINCT {$sucExprBI})   AS franquicias_con_stock
                FROM BI_STOCK_FRANQUICIAS BI WITH (NOLOCK)
                $slJoin
                $joinSql
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

        $base      = "FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK) WHERE CANT_STOCK > 0 AND RUBRO != '_KITS'";
        $slJoin    = $this->sucursalJoinSql('BI');
        $sucExprBI = $this->sucursalNombreExpr('BI');

        $queries = [
            'sucursales' => "SELECT DISTINCT {$sucExprBI} AS v
                             FROM BI_STOCK_FRANQUICIAS BI WITH (NOLOCK)
                             $slJoin
                             WHERE BI.CANT_STOCK > 0 AND BI.RUBRO != '_KITS'
                               AND {$sucExprBI} IS NOT NULL AND {$sucExprBI} != ''
                             ORDER BY v",
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

        $joinMD    = isset($f['liquidacion']) && $f['liquidacion'] !== '';
        $sucExprBI = $this->sucursalNombreExpr('BI');
        $slJoinBI  = $this->sucursalJoinSql('BI');

        if ($joinMD) {
            $sql = "SELECT TOP 100000
                        BI.ARTICULO, BI.DESCRIPCION, BI.MODELO, BI.CATEGORIA, BI.RUBRO, BI.TEMPORADA,
                        {$sucExprBI} AS FRANQUICIA, BI.NRO_SUCURSAL, BI.GRUPO_EMPRESARIO, BI.DESTINO,
                        BI.COD_DEPOSI, BI.COD_CLIENT, MD.LIQUIDACION AS LIQUIDACION, BI.CANT_STOCK
                    FROM BI_STOCK_FRANQUICIAS BI WITH (NOLOCK)
                    $slJoinBI
                    LEFT JOIN [XL-LAKERBIS].[locales_lakers].[dbo].[MAESTRO_DESTINOS] MD ON MD.COD_ARTICU COLLATE database_default = BI.ARTICULO COLLATE database_default
                    $where
                    ORDER BY FRANQUICIA, BI.ARTICULO";
        } elseif ($this->needsSucursalJoin($f)) {
            $sql = "SELECT
                        Paged.*, MD.LIQUIDACION AS LIQUIDACION
                    FROM (
                        SELECT TOP 100000
                            BI.ARTICULO, BI.DESCRIPCION, BI.MODELO, BI.CATEGORIA, BI.RUBRO, BI.TEMPORADA,
                            {$sucExprBI} AS FRANQUICIA, BI.NRO_SUCURSAL, BI.GRUPO_EMPRESARIO, BI.DESTINO,
                            BI.COD_DEPOSI, BI.COD_CLIENT, BI.CANT_STOCK
                        FROM BI_STOCK_FRANQUICIAS BI WITH (NOLOCK)
                        $slJoinBI
                        $where
                    ) Paged
                    LEFT JOIN [XL-LAKERBIS].[locales_lakers].[dbo].[MAESTRO_DESTINOS] MD ON MD.COD_ARTICU COLLATE database_default = Paged.ARTICULO COLLATE database_default
                    ORDER BY Paged.FRANQUICIA, Paged.ARTICULO";
        } else {
            $slJoinPaged = $this->sucursalJoinSql('Paged');
            $sucExprOut  = "COALESCE(NULLIF(LTRIM(RTRIM(SL.DESC_SUCURSAL COLLATE database_default)), ''), Paged.BI_DESC_SUCURSAL)";
            $sql = "SELECT
                        Paged.ARTICULO, Paged.DESCRIPCION, Paged.MODELO, Paged.CATEGORIA, Paged.RUBRO, Paged.TEMPORADA,
                        {$sucExprOut} AS FRANQUICIA, Paged.NRO_SUCURSAL, Paged.GRUPO_EMPRESARIO, Paged.DESTINO,
                        Paged.COD_DEPOSI, Paged.COD_CLIENT, Paged.CANT_STOCK, MD.LIQUIDACION AS LIQUIDACION
                    FROM (
                        SELECT TOP 100000
                            BI.ARTICULO, BI.DESCRIPCION, BI.MODELO, BI.CATEGORIA, BI.RUBRO, BI.TEMPORADA,
                            BI.DESC_SUCURSAL AS BI_DESC_SUCURSAL, BI.NRO_SUCURSAL, BI.GRUPO_EMPRESARIO, BI.DESTINO,
                            BI.COD_DEPOSI, BI.COD_CLIENT, BI.CANT_STOCK
                        FROM BI_STOCK_FRANQUICIAS BI WITH (NOLOCK)
                        $where
                    ) Paged
                    $slJoinPaged
                    LEFT JOIN [XL-LAKERBIS].[locales_lakers].[dbo].[MAESTRO_DESTINOS] MD ON MD.COD_ARTICU COLLATE database_default = Paged.ARTICULO COLLATE database_default
                    ORDER BY FRANQUICIA, Paged.ARTICULO";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCategoriasPorRubros(array $rubros): array {
        $base = "FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK) WHERE CANT_STOCK > 0 AND RUBRO != '_KITS'";
        if (empty($rubros)) {
            $sql = "SELECT DISTINCT CATEGORIA AS v $base AND CATEGORIA IS NOT NULL AND CATEGORIA != '' ORDER BY CATEGORIA";
            $stmt = $this->conn->query($sql);
            return array_column($stmt->fetchAll(), 'v');
        } else {
            $ph = [];
            $params = [];
            foreach ($rubros as $i => $v) {
                $k = ":rubro_{$i}";
                $ph[] = $k;
                $params[$k] = $v;
            }
            $sql = "SELECT DISTINCT CATEGORIA AS v $base AND RUBRO IN (" . implode(',', $ph) . ") AND CATEGORIA IS NOT NULL AND CATEGORIA != '' ORDER BY CATEGORIA";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return array_column($stmt->fetchAll(), 'v');
        }
    }

    public function invalidarCache(): void {
        $cacheFile = $this->cacheDir . 'filtros_bi_stock.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}
