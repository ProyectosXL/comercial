<?php
#[AllowDynamicProperties]
class StockPrecio
{
    /** Base de la sucursal del usuario logueado (solo locales con conexión directa): de acá sale el stock directo. */
    private $cid = false;

    /** Base central (o uy): de acá salen el maestro de artículos y las listas de precios. */
    private $cid_central = false;

    /** Conexión sqlsrv a POWER_BI_CONTROL_FRANQUICIAS en HOST_APPS (para franquicias). */
    private $cid_bi = false;

    /** Conexión PDO a POWER_BI_CONTROL_FRANQUICIAS en HOST_APPS (para franquicias). */
    private ?PDO $pdo_bi = null;

    /** ¿Es franquicia? */
    private bool $esFranquicia = false;

    /** Conexión local bajo demanda */
    private $connObj = null;

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/sistemas/class/conexion.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->connObj = new Conexion();

        $db = (isset($_SESSION['usuarioUy']) && $_SESSION['usuarioUy'] == 1) ? 'uy' : 'central';
        $this->cid_central = $this->connObj->conectar($db);

        $tipo = strtoupper(trim((string)($_SESSION['tipo'] ?? '')));
        $codClient = (string)($_SESSION['codClient'] ?? '');
        $numsuc = (int)($_SESSION['numsuc'] ?? 0);

        $this->esFranquicia = str_starts_with($tipo, 'FRANQUICIA') || str_starts_with($codClient, 'FR') || $numsuc >= 500;

        // Intentar conectar con base de sucursal si están seteados los datos de conexión DNS
        if (!empty($_SESSION['conexion_dns']) && !empty($_SESSION['base_nombre'])) {
            try {
                $this->cid = $this->connObj->conectar();
            } catch (Throwable $e) {
                $this->cid = false;
            }
        }

        // Si es franquicia o no hay conexión local directa, inicializar conexión a base BI de franquicias
        if ($this->esFranquicia || !$this->cid) {
            $this->conectarBIFranquicias($this->connObj);
        }
    }

    private function conectarBIFranquicias(Conexion $conn): void
    {
        try {
            $env = $conn->envVars ?? [];
            if (empty($env)) {
                $env = $this->loadEnv();
            }

            $server   = !empty($env['HOST_APPS']) ? $env['HOST_APPS'] : 'XL-APPS';
            $database = !empty($env['DATABASE_FRANQUICIAS']) ? $env['DATABASE_FRANQUICIAS'] : 'POWER_BI_CONTROL_FRANQUICIAS';
            $user     = !empty($env['USER']) ? $env['USER'] : ($conn->user ?? 'sa');
            $pass     = !empty($env['PASS']) ? $env['PASS'] : ($conn->pass ?? '');

            // 1. PDO sqlsrv
            try {
                $this->pdo_bi = new PDO(
                    "sqlsrv:server={$server};Database={$database}",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT            => 4,
                    ]
                );
            } catch (Throwable $pe) {
                $this->pdo_bi = null;
            }

            // 2. sqlsrv_connect
            $params = [
                "Database"     => $database,
                "UID"          => $user,
                "PWD"          => $pass,
                "CharacterSet" => "UTF-8",
                "LoginTimeout" => 4,
            ];
            $this->cid_bi = @sqlsrv_connect($server, $params);
        } catch (Throwable $e) {
            error_log("StockPrecio - conectarBIFranquicias Exception: " . $e->getMessage());
        }
    }

    private function loadEnv(): array
    {
        $candidates = [
            __DIR__ . '/../../../.env',
            __DIR__ . '/../../.env',
            __DIR__ . '/../../../../.env',
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/../.env',
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/.env',
            'w:/.env',
            'W:/.env',
            'd:/htdocs/.env',
            'D:/htdocs/.env',
        ];

        foreach ($candidates as $candidate) {
            $path = @realpath($candidate);
            if ($path && @file_exists($path) && @is_readable($path)) {
                $vars = [];
                $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines) {
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                            continue;
                        }
                        [$k, $v] = explode('=', $line, 2);
                        $vars[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
                    }
                }
                if (!empty($vars)) {
                    return $vars;
                }
            }
        }
        return [];
    }

    public function traerMaestroArticulo($codArticulo, $usuarioUy = 0): array
    {
        try {
            $codLimpio = trim((string)$codArticulo);
            if (empty($codLimpio)) {
                return [];
            }

            $articulos = $this->traerPreciosCentral($codLimpio, true);
            $stock = $this->traerStockLocal($codLimpio, true);

            return $this->combinarStock($articulos, $stock);
        } catch (Throwable $e) {
            error_log("Error in traerMaestroArticulo: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function traerVariantes($codArticulo, $usuarioUy = 0): array
    {
        try {
            $codLimpio = trim((string)$codArticulo);
            if (empty($codLimpio)) {
                return [];
            }

            // Patrón base del artículo para traer variantes de color/talle
            $len = strlen($codLimpio);
            $prefijoLen = $len >= 13 ? 11 : ($len >= 10 ? 9 : $len);
            $patronBase = substr($codLimpio, 0, $prefijoLen) . '%';

            $articulos = $this->traerPreciosCentral($patronBase, false);
            $stock = $this->traerStockLocal($patronBase, false);

            return $this->combinarStock($articulos, $stock);
        } catch (Throwable $e) {
            error_log("Error in traerVariantes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Maestro de artículos y precios desde central: lista 20 es el precio actual y
     * lista 95 el precio base. PRECIO_ANTERIOR queda en NULL cuando no hay liquidación.
     */
    private function traerPreciosCentral($codArticulo, $exacto): array
    {
        $comparador = $exacto ? '=' : 'LIKE';

        $sql = "SELECT RTRIM(D.COD_ARTICU) AS COD_ARTICU, 
                    COALESCE(NULLIF(RTRIM(H.DESCRIPCION), ''), RTRIM(D.DESCRIPCIO), '') AS DESCRIPCIO, 
                    ISNULL(RTRIM(E.DESC_VALOR), '') AS COLOR, 
                    ISNULL(C.PRECIO, 0) AS PRECIO,
                    CAST((ISNULL(C.PRECIO, 0)/(1+(ISNULL(F.PORCENTAJE, 21)/100))) AS DECIMAL(10,2)) AS PRECIO_S_IVA,
                    CASE WHEN G.PRECIO > C.PRECIO THEN G.PRECIO ELSE NULL END AS PRECIO_ANTERIOR,
                    CASE WHEN UPPER(LTRIM(RTRIM(ISNULL(H.LIQUIDACION, '')))) IN ('SI', '1', 'YES', 'TRUE')
                        THEN 'SI' ELSE 'NO' END AS LIQUIDACION,
                    ISNULL(RTRIM(H.DESTINO), '') AS DESTINO
                FROM STA11 D WITH (NOLOCK)
                LEFT JOIN GVA17 C WITH (NOLOCK) ON D.COD_ARTICU = C.COD_ARTICU AND C.NRO_DE_LIS = 20
                LEFT JOIN GVA17 G WITH (NOLOCK) ON D.COD_ARTICU = G.COD_ARTICU AND G.NRO_DE_LIS = 95
                LEFT JOIN STA33 E WITH (NOLOCK) ON D.ESCALA_1 = E.COD_ESCALA AND D.VALOR1 = E.COD_VALOR AND E.COD_ESCALA IN ('**', 'ZZ')
                LEFT JOIN GVA41 F WITH (NOLOCK) ON D.ID_GVA41_COD_IVA = F.ID_GVA41
                LEFT JOIN MAESTRO_DESTINOS H WITH (NOLOCK) ON D.COD_ARTICU = H.COD_ARTICU
                WHERE D.COD_ARTICU {$comparador} ?
                ORDER BY D.COD_ARTICU";

        return $this->ejecutar($this->cid_central, $sql, [$codArticulo], 'central');
    }

    /**
     * Resuelve los datos de conexión y número de sucursal desde SUCURSALES_LAKERS.
     */
    private function obtenerDatosSucursal(): array
    {
        $datos = [
            'nroSucursal' => (int)($_SESSION['numsuc'] ?? 0),
            'conexionDns' => trim((string)($_SESSION['conexion_dns'] ?? '')),
            'baseNombre'  => trim((string)($_SESSION['base_nombre'] ?? '')),
            'usuarioDns'  => trim((string)($_SESSION['usuario_dns'] ?? '')),
            'claveDns'    => trim((string)($_SESSION['clave_dns'] ?? '')),
        ];

        $codClient = trim((string)($_SESSION['codClient'] ?? ''));
        $descLocal = trim((string)($_SESSION['descLocal'] ?? ''));

        if ($this->cid_central && (!empty($codClient) || !empty($descLocal))) {
            $tablaSucursales = '[XL-LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS';
            
            if (!empty($codClient)) {
                $sql = "SELECT NRO_SUCURSAL, CONEXION_DNS, BASE_NOMBRE, USUARIO_DNS, CLAVE_DNS
                        FROM $tablaSucursales WITH (NOLOCK) 
                        WHERE COD_CLIENT = ? AND NRO_SUC_MADRE IS NULL";
                try {
                    $rows = $this->ejecutar($this->cid_central, $sql, [$codClient], 'central');
                    if (!empty($rows[0])) {
                        $r = $rows[0];
                        if (empty($datos['nroSucursal']) || $datos['nroSucursal'] === 0) {
                            $datos['nroSucursal'] = (int)$r['NRO_SUCURSAL'];
                        }
                        if (empty($datos['conexionDns'])) $datos['conexionDns'] = trim((string)$r['CONEXION_DNS']);
                        if (empty($datos['baseNombre']))  $datos['baseNombre']  = trim((string)$r['BASE_NOMBRE']);
                        if (empty($datos['usuarioDns']))  $datos['usuarioDns']  = trim((string)$r['USUARIO_DNS']);
                        if (empty($datos['claveDns']))    $datos['claveDns']    = trim((string)$r['CLAVE_DNS']);
                    }
                } catch (Throwable $e) {}
            }

            if (empty($datos['nroSucursal']) && !empty($descLocal)) {
                $sql = "SELECT NRO_SUCURSAL, CONEXION_DNS, BASE_NOMBRE, USUARIO_DNS, CLAVE_DNS
                        FROM $tablaSucursales WITH (NOLOCK) 
                        WHERE DESC_SUCURSAL LIKE ? OR DESCRIPCION LIKE ?";
                try {
                    $rows = $this->ejecutar($this->cid_central, $sql, ["%{$descLocal}%", "%{$descLocal}%"], 'central');
                    if (!empty($rows[0])) {
                        $r = $rows[0];
                        $datos['nroSucursal'] = (int)$r['NRO_SUCURSAL'];
                        if (empty($datos['conexionDns'])) $datos['conexionDns'] = trim((string)$r['CONEXION_DNS']);
                        if (empty($datos['baseNombre']))  $datos['baseNombre']  = trim((string)$r['BASE_NOMBRE']);
                        if (empty($datos['usuarioDns']))  $datos['usuarioDns']  = trim((string)$r['USUARIO_DNS']);
                        if (empty($datos['claveDns']))    $datos['claveDns']    = trim((string)$r['CLAVE_DNS']);
                    }
                } catch (Throwable $e) {}
            }
        }

        return $datos;
    }

    /**
     * Stock del local logueado.
     * 1. Conexión directa a servidor del local (STA19) si está disponible
     * 2. Si falla o no tiene conexión directa:
     *    - BI_STOCK_FRANQUICIAS (HOST_APPS o Central) para franquicias
     *    - BI_STOCK_LOCALES (Central) para locales propios
     * Devuelve un mapa COD_ARTICU => CANT_STOCK.
     */
    private function traerStockLocal($codArticulo, $exacto): array
    {
        $comparador = $exacto ? '=' : 'LIKE';
        $stock = [];
        $codArticuloLimpio = trim((string)$codArticulo);
        $descLocal = trim((string)($_SESSION['descLocal'] ?? ''));
        $codClient = trim((string)($_SESSION['codClient'] ?? ''));

        $datosSuc = $this->obtenerDatosSucursal();
        $numsuc = $datosSuc['nroSucursal'];

        // -------------------------------------------------------------
        // ESTRATEGIA 1: Conexión directa al servidor local (STA19)
        // Funciona tanto para locales propios como franquicias con enlace directo activo.
        // -------------------------------------------------------------
        $connLocal = $this->cid;
        $cerrarLocal = false;
        $baseNombre = $datosSuc['baseNombre'];

        if (!$connLocal && !empty($datosSuc['conexionDns']) && !empty($datosSuc['baseNombre'])) {
            try {
                $connectionInfo = [
                    "Database"          => $datosSuc['baseNombre'],
                    "UID"               => !empty($datosSuc['usuarioDns']) ? $datosSuc['usuarioDns'] : 'sa',
                    "PWD"               => $datosSuc['claveDns'] ?? '',
                    "CharacterSet"      => "UTF-8",
                    "LoginTimeout"      => 4,
                    "ConnectionPooling" => 0
                ];
                $connLocal = @sqlsrv_connect($datosSuc['conexionDns'], $connectionInfo);
                if (!$connLocal && stripos($datosSuc['conexionDns'], 'tcp:') !== 0 && preg_match('/^([^,]+),(\d+)$/', $datosSuc['conexionDns'])) {
                    $connLocal = @sqlsrv_connect('tcp:' . $datosSuc['conexionDns'], $connectionInfo);
                }
                if ($connLocal) {
                    $cerrarLocal = true;
                }
            } catch (Throwable $e) {
                $connLocal = null;
            }
        }

        if ($connLocal) {
            try {
                $sqlStock = "SELECT RTRIM(A.COD_ARTICU) AS COD_ARTICU, CAST(SUM(A.CANT_STOCK) AS INT) AS CANT_STOCK
                             FROM " . (!empty($baseNombre) ? "[{$baseNombre}].DBO." : "") . "STA19 A WITH (NOLOCK)
                             WHERE A.CANT_STOCK > 0
                               AND A.COD_ARTICU {$comparador} ?
                             GROUP BY A.COD_ARTICU";

                $filas = $this->ejecutar($connLocal, $sqlStock, [$codArticuloLimpio], 'local_sta19');
                foreach ($filas as $fila) {
                    $stock[trim($fila['COD_ARTICU'])] = (int)$fila['CANT_STOCK'];
                }
                if ($cerrarLocal) {
                    @sqlsrv_close($connLocal);
                }
                if (!empty($stock)) {
                    return $stock;
                }
            } catch (Throwable $e) {
                if ($cerrarLocal) {
                    @sqlsrv_close($connLocal);
                }
            }
        }

        // -------------------------------------------------------------
        // ESTRATEGIA 2: Base BI de Franquicias (HOST_APPS / Central)
        // -------------------------------------------------------------
        if ($this->esFranquicia || !empty($codClient)) {
            // A) Base POWER_BI_CONTROL_FRANQUICIAS en HOST_APPS (vía PDO)
            if ($this->pdo_bi !== null) {
                try {
                    $sql = "SELECT RTRIM(ARTICULO) AS COD_ARTICU, SUM(CAST(CANT_STOCK AS INT)) AS CANT_STOCK
                            FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK)
                            WHERE (NRO_SUCURSAL = :numsuc OR CAST(NRO_SUCURSAL AS VARCHAR) = :numsucStr OR DESC_SUCURSAL LIKE :descLike OR COD_CLIENT = :codClient)
                              AND RTRIM(ARTICULO) {$comparador} :codArticulo
                              AND CANT_STOCK > 0
                            GROUP BY ARTICULO";

                    $stmt = $this->pdo_bi->prepare($sql);
                    $stmt->execute([
                        ':numsuc'      => $numsuc,
                        ':numsucStr'   => (string)$numsuc,
                        ':descLike'    => '%' . $descLocal . '%',
                        ':codClient'   => $codClient,
                        ':codArticulo' => $codArticuloLimpio,
                    ]);

                    while ($row = $stmt->fetch()) {
                        $stock[trim($row['COD_ARTICU'])] = (int)$row['CANT_STOCK'];
                    }
                    if (!empty($stock)) {
                        return $stock;
                    }
                } catch (Throwable $e) {
                    error_log("StockPrecio - BI_STOCK_FRANQUICIAS (HOST_APPS PDO) fallo: " . $e->getMessage());
                }
            }

            // B) Base POWER_BI_CONTROL_FRANQUICIAS en HOST_APPS (vía sqlsrv)
            if ($this->cid_bi) {
                try {
                    $sql = "SELECT RTRIM(ARTICULO) AS COD_ARTICU, SUM(CAST(CANT_STOCK AS INT)) AS CANT_STOCK
                            FROM BI_STOCK_FRANQUICIAS WITH (NOLOCK)
                            WHERE (NRO_SUCURSAL = ? OR CAST(NRO_SUCURSAL AS VARCHAR) = ? OR DESC_SUCURSAL LIKE ? OR COD_CLIENT = ?)
                              AND RTRIM(ARTICULO) {$comparador} ?
                              AND CANT_STOCK > 0
                            GROUP BY ARTICULO";

                    $filas = $this->ejecutar($this->cid_bi, $sql, [$numsuc, (string)$numsuc, '%' . $descLocal . '%', $codClient, $codArticuloLimpio], 'bi_franquicias');
                    foreach ($filas as $fila) {
                        $stock[trim($fila['COD_ARTICU'])] = (int)$fila['CANT_STOCK'];
                    }
                    if (!empty($stock)) {
                        return $stock;
                    }
                } catch (Throwable $e) {
                    error_log("StockPrecio - BI_STOCK_FRANQUICIAS (HOST_APPS sqlsrv) fallo: " . $e->getMessage());
                }
            }

            // C) Base FRANQUICIAS_LAKERS en HOST_CENTRAL (vía cid_central)
            if ($this->cid_central) {
                try {
                    $sql = "SELECT RTRIM(ARTICULO) AS COD_ARTICU, SUM(CAST(CANT_STOCK AS INT)) AS CANT_STOCK
                            FROM FRANQUICIAS_LAKERS.DBO.BI_STOCK_FRANQUICIAS WITH (NOLOCK)
                            WHERE (NRO_SUCURSAL = ? OR CAST(NRO_SUCURSAL AS VARCHAR) = ? OR DESC_SUCURSAL LIKE ? OR COD_CLIENT = ?)
                              AND RTRIM(ARTICULO) {$comparador} ?
                              AND CANT_STOCK > 0
                            GROUP BY ARTICULO";

                    $filas = $this->ejecutar($this->cid_central, $sql, [$numsuc, (string)$numsuc, '%' . $descLocal . '%', $codClient, $codArticuloLimpio], 'central_franquicias');
                    foreach ($filas as $fila) {
                        $stock[trim($fila['COD_ARTICU'])] = (int)$fila['CANT_STOCK'];
                    }
                    if (!empty($stock)) {
                        return $stock;
                    }
                } catch (Throwable $e) {}
            }
        }

        // -------------------------------------------------------------
        // ESTRATEGIA 3: Fallback para locales propios desde Central (BI_STOCK_LOCALES)
        // -------------------------------------------------------------
        if ($this->cid_central) {
            try {
                $sqlBI = "SELECT RTRIM(COD_ARTICULO) AS COD_ARTICU, CAST(SUM(CANT_STOCK) AS INT) AS CANT_STOCK
                          FROM LOCALES_LAKERS.DBO.BI_STOCK_LOCALES WITH (NOLOCK)
                          WHERE (NRO_SUCURSAL = ? OR COD_CLIENT = ?)
                            AND COD_ARTICULO {$comparador} ?
                            AND CANT_STOCK > 0
                          GROUP BY COD_ARTICULO";
                $filas = $this->ejecutar($this->cid_central, $sqlBI, [$numsuc, $codClient, $codArticuloLimpio], 'central_locales');
                foreach ($filas as $fila) {
                    $stock[trim($fila['COD_ARTICU'])] = (int)$fila['CANT_STOCK'];
                }
            } catch (Throwable $e) {
                error_log("StockPrecio - fallback BI_STOCK_LOCALES fallo: " . $e->getMessage());
            }
        }

        return $stock;
    }

    /**
     * Agrega el stock del local a cada artículo traído de central.
     * Un artículo sin registro de stock en el local queda en 0.
     */
    private function combinarStock(array $articulos, array $stock): array
    {
        foreach ($articulos as &$articulo) {
            $cod = trim($articulo['COD_ARTICU']);
            $articulo['CANT_STOCK'] = $stock[$cod] ?? 0;
        }
        unset($articulo);

        return $articulos;
    }

    private function ejecutar($cid, $sql, $params, $origen): array
    {
        if (!$cid) {
            throw new Exception("No hay conexión disponible a la base $origen.");
        }

        $stmt = sqlsrv_prepare($cid, $sql, $params);
        if ($stmt === false) {
            throw new Exception("Error preparing statement ($origen): " . print_r(sqlsrv_errors(), true));
        }

        if (sqlsrv_execute($stmt) === false) {
            throw new Exception("Error executing statement ($origen): " . print_r(sqlsrv_errors(), true));
        }

        $v = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $v[] = $row;
        }

        return $v;
    }
}
