<?php
/**
 * Clase TransferenciasDepositos
 * Migración de la lógica de transferencias entre depósitos de Python a PHP.
 * Conexión a LAKER_SA (central) y UbicacionesStockMvc (wms).
 */
class TransferenciasDepositos {
    private $conexion;
    public static $DEPOSITOS_HABILITADOS = [
        '04' => 'OUTLET',
        '06' => 'FALLAS',
        '10' => 'RECODIFICACIONES',
        '20' => 'ARREGLOS PRODUCCION'
    ];

    public function __construct() {
        if (file_exists(__DIR__ . '/../../../Class/Conexion.php')) {
            require_once __DIR__ . '/../../../Class/Conexion.php';
        } elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        } else {
            require_once 'W:/comercial/Class/Conexion.php';
        }
        $this->conexion = new Conexion();
    }

    private function getConnCentral() {
        return $this->conexion->conectar('central');
    }

    private function getConnWms() {
        return $this->conexion->conectar('wms');
    }

    public static function normalizarDeposito($valor) {
        $texto = trim((string)$valor);
        if ($texto === '') return null;
        if (str_ends_with($texto, '.0')) {
            $texto = substr($texto, 0, -2);
        }
        if (!ctype_digit($texto) || strlen($texto) > 2) return null;
        return str_pad($texto, 2, '0', STR_PAD_LEFT);
    }

    public static function generarGuid() {
        if (function_exists('random_bytes')) {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    public function ubicacionesHabilitadas() {
        $conn = $this->getConnWms();
        if (!$conn) return [];

        $codigos = array_keys(self::$DEPOSITOS_HABILITADOS);
        $placeholders = implode(',', array_fill(0, count($codigos), '?'));

        $sql = "SELECT Num_Deposito, Cod_Ubicacion, Nombre_Ubicacion, Tipo_Ubicacion
                FROM Ubicacion
                WHERE Num_Deposito IN ($placeholders)
                ORDER BY Num_Deposito, Cod_Ubicacion";

        $stmt = sqlsrv_query($conn, $sql, $codigos);
        $ubicaciones = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $ubicaciones[] = [
                    'deposito'  => trim($row['Num_Deposito'] ?? ''),
                    'ubicacion' => trim($row['Cod_Ubicacion'] ?? ''),
                    'nombre'    => trim($row['Nombre_Ubicacion'] ?? ''),
                    'tipo'      => trim($row['Tipo_Ubicacion'] ?? '')
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        return $ubicaciones;
    }

    public function buscarArticulo($codigo) {
        $codigo = strtoupper(trim((string)$codigo));
        if ($codigo === '') return null;

        $connCentral = $this->getConnCentral();
        if (!$connCentral) return null;

        $sqlTango = "SELECT TOP (1)
                            LTRIM(RTRIM(A.COD_ARTICU)) AS COD_ARTICU,
                            LTRIM(RTRIM(ISNULL(A.DESCRIPCIO, ''))) AS DESCRIPCIO,
                            CAST(ISNULL(A.usa_partid, 0) AS INT) AS usa_partid
                     FROM SJ_VIEW_STA11 A WITH (NOLOCK)
                     JOIN STA11ITC B WITH (NOLOCK) ON A.COD_ARTICU = B.CODE
                     JOIN STA11FLD C WITH (NOLOCK) ON B.IDFOLDER = C.IDFOLDER
                     WHERE C.DESCRIP NOT LIKE '[_]%'
                       AND (   LTRIM(RTRIM(A.COD_ARTICU)) = ?
                            OR LTRIM(RTRIM(ISNULL(A.COD_BARRA, ''))) = ?
                            OR LTRIM(RTRIM(ISNULL(A.SINONIMO, ''))) = ?)";

        $stmtTango = sqlsrv_query($connCentral, $sqlTango, [$codigo, $codigo, $codigo]);
        if (!$stmtTango) return null;

        $filaTango = sqlsrv_fetch_array($stmtTango, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtTango);

        if (!$filaTango) return null;

        $articulo = $filaTango['COD_ARTICU'];
        $descripcion = $filaTango['DESCRIPCIO'];
        $usaPartidas = (bool)$filaTango['usa_partid'];

        $connWms = $this->getConnWms();
        $ubicaciones = [];
        $existeEnWms = false;

        if ($connWms) {
            $codigosDep = array_keys(self::$DEPOSITOS_HABILITADOS);
            $placeholders = implode(',', array_fill(0, count($codigosDep), '?'));

            $sqlWms = "SELECT u.Num_Deposito, u.Cod_Ubicacion, u.Nombre_Ubicacion, SUM(q.s) AS saldo
                       FROM (
                           SELECT id_Articulo, id_ubicacionDestino AS idu, Cantidad_Asosciada AS s
                           FROM Movimientos WITH (NOLOCK) WHERE id_ubicacionDestino > 0
                           UNION ALL
                           SELECT id_Articulo, id_ubicacionOrigen, -Cantidad_Asosciada
                           FROM Movimientos WITH (NOLOCK) WHERE id_ubicacionOrigen > 0
                       ) q
                       JOIN Ubicacion u WITH (NOLOCK) ON u.id_Ubicacion = q.idu
                       JOIN Articulo  a WITH (NOLOCK) ON a.id_Articulo  = q.id_Articulo
                       WHERE a.Cod_Articulo = ?
                         AND u.Num_Deposito IN ($placeholders)
                       GROUP BY u.Num_Deposito, u.Cod_Ubicacion, u.Nombre_Ubicacion
                       HAVING SUM(q.s) > 0
                       ORDER BY SUM(q.s) DESC";

            $params = array_merge([$articulo], $codigosDep);
            $stmtWms = sqlsrv_query($connWms, $sqlWms, $params);
            if ($stmtWms) {
                while ($r = sqlsrv_fetch_array($stmtWms, SQLSRV_FETCH_ASSOC)) {
                    $ubicaciones[] = [
                        'deposito'  => trim($r['Num_Deposito'] ?? ''),
                        'ubicacion' => trim($r['Cod_Ubicacion'] ?? ''),
                        'nombre'    => trim($r['Nombre_Ubicacion'] ?? ''),
                        'saldo'     => (int)$r['saldo']
                    ];
                }
                sqlsrv_free_stmt($stmtWms);
            }

            $sqlExiste = "SELECT TOP (1) id_Articulo FROM Articulo WITH (NOLOCK) WHERE Cod_Articulo = ?";
            $stmtExiste = sqlsrv_query($connWms, $sqlExiste, [$articulo]);
            if ($stmtExiste) {
                $existeEnWms = (sqlsrv_fetch_array($stmtExiste, SQLSRV_FETCH_ASSOC) !== null);
                sqlsrv_free_stmt($stmtExiste);
            }
        }

        return [
            'articulo'      => $articulo,
            'descripcion'   => $descripcion,
            'usa_partidas'  => $usaPartidas,
            'existe_en_wms' => $existeEnWms,
            'ubicaciones'   => $ubicaciones,
        ];
    }

    public function crearLote($loteId, $usuario, $host, $filas) {
        $conn = $this->getConnCentral();
        if (!$conn) throw new Exception("No se pudo conectar a la base central");

        $sqlLote = "INSERT INTO EB_TransferDepositoLote (IdLote, Usuario, Host, Estado, Filas)
                    VALUES (?, ?, ?, 0, ?)";
        $stmtLote = sqlsrv_query($conn, $sqlLote, [$loteId, $usuario, $host, count($filas)]);
        if (!$stmtLote) {
            $err = sqlsrv_errors();
            throw new Exception("Error al insertar cabecera del lote: " . print_r($err, true));
        }

        $sqlDet = "INSERT INTO EB_TransferDepositoDet
                    (IdLote, Fila,
                     DepOrigen, UbicOrigen, Articulo, Cantidad,
                     DepDestino, UbicDestino, ArtDestino, CantAlta)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $indice = 1;
        foreach ($filas as $f) {
            $params = [
                $loteId,
                $indice++,
                $f[0], // DepOrigen
                $f[1], // UbicOrigen
                $f[2], // Articulo (origen)
                $f[3], // Cantidad (baja)
                $f[4], // DepDestino
                $f[5], // UbicDestino
                $f[6], // ArtDestino
                $f[7]  // CantAlta
            ];
            $stmtDet = sqlsrv_query($conn, $sqlDet, $params);
            if (!$stmtDet) {
                $err = sqlsrv_errors();
                throw new Exception("Error al insertar detalle del lote (fila $indice): " . print_r($err, true));
            }
        }
        return true;
    }

    public function procesarLote($loteId, $usuario, $host, $soloValidar = true) {
        $conn = $this->getConnCentral();
        if (!$conn) throw new Exception("No se pudo conectar a la base central");

        $sql = "{CALL EB_TransferDeposito_Procesar(?, ?, ?, ?)}";
        $params = [
            $loteId,
            $usuario,
            $host,
            $soloValidar ? 1 : 0
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);
        if (!$stmt) {
            $err = sqlsrv_errors();
            throw new Exception("Error al ejecutar EB_TransferDeposito_Procesar: " . print_r($err, true));
        }

        // Resultset 1: Filas con error
        $filasConError = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $filasConError[] = $row;
        }

        // Resultset 2: Resumen
        $resumen = [];
        if (sqlsrv_next_result($stmt)) {
            if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resumen = $row;
            }
        }

        // Resultset 3: Sugerencias
        $sugerencias = [];
        if (sqlsrv_next_result($stmt)) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $sugerencias[] = $row;
            }
        }
        sqlsrv_free_stmt($stmt);

        return [
            'filas_con_error' => $filasConError,
            'resumen'         => $resumen,
            'sugerencias'     => $sugerencias
        ];
    }

    public function borrarLote($loteId) {
        $conn = $this->getConnCentral();
        if (!$conn) return false;
        $sql = "DELETE FROM EB_TransferDepositoLote WHERE IdLote = ?";
        sqlsrv_query($conn, $sql, [$loteId]);
        return true;
    }

    // =========================================================================
    // HISTORIAL
    // =========================================================================

    private function buildWhereHistorial($filtros) {
        $condiciones = ['l.Estado = 2', 'l.EsPrueba = 0'];
        $params = [];

        if (!empty($filtros['desde'])) {
            $condiciones[] = 'l.FechaProceso >= ?';
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'l.FechaProceso < DATEADD(DAY, 1, ?)';
            $params[] = $filtros['hasta'];
        }
        if (!empty($filtros['deposito'])) {
            $condiciones[] = '(d.DepOrigenN = ? OR d.DepDestinoN = ?)';
            $params[] = $filtros['deposito'];
            $params[] = $filtros['deposito'];
        }
        if (!empty($filtros['articulo'])) {
            $artDest = "COALESCE(d.ArtDestinoN, UPPER(LTRIM(RTRIM(d.ArtDestino))))";
            $condiciones[] = "(d.ArticuloN LIKE ? OR $artDest LIKE ?)";
            $patron = '%' . strtoupper(trim($filtros['articulo'])) . '%';
            $params[] = $patron;
            $params[] = $patron;
        }
        if (!empty($filtros['usuario'])) {
            $condiciones[] = 'l.Usuario LIKE ?';
            $params[] = '%' . trim($filtros['usuario']) . '%';
        }

        return ['where' => implode(' AND ', $condiciones), 'params' => $params];
    }

    public function historialTotalesPorDeposito($filtros = []) {
        $conn = $this->getConnCentral();
        if (!$conn) return [];

        $w = $this->buildWhereHistorial($filtros);
        $where = $w['where'];
        $params = array_merge($w['params'], $w['params']);

        $sql = "SELECT x.Deposito,
                       SUM(CASE WHEN x.Signo = -1 THEN x.Cant ELSE 0 END) AS Salidas,
                       SUM(CASE WHEN x.Signo =  1 THEN x.Cant ELSE 0 END) AS Entradas,
                       COUNT(*) AS Movimientos
                FROM (
                    SELECT d.DepOrigenN AS Deposito, -1 AS Signo, d.Cantidad AS Cant
                    FROM EB_TransferDepositoDet d WITH (NOLOCK)
                    JOIN EB_TransferDepositoLote l WITH (NOLOCK) ON l.IdLote = d.IdLote
                    WHERE $where AND d.DepOrigenN IS NOT NULL
                    UNION ALL
                    SELECT d.DepDestinoN, 1, d.CantAlta
                    FROM EB_TransferDepositoDet d WITH (NOLOCK)
                    JOIN EB_TransferDepositoLote l WITH (NOLOCK) ON l.IdLote = d.IdLote
                    WHERE $where AND d.DepDestinoN IS NOT NULL
                ) x GROUP BY x.Deposito ORDER BY x.Deposito";

        $stmt = sqlsrv_query($conn, $sql, $params);
        $totales = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $dep = trim($r['Deposito'] ?? '');
                $totales[] = [
                    'deposito'    => $dep,
                    'nombre'      => self::$DEPOSITOS_HABILITADOS[$dep] ?? '',
                    'salidas'     => (int)($r['Salidas'] ?? 0),
                    'entradas'    => (int)($r['Entradas'] ?? 0),
                    'neto'        => (int)($r['Entradas'] ?? 0) - (int)($r['Salidas'] ?? 0),
                    'movimientos' => (int)($r['Movimientos'] ?? 0),
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        return $totales;
    }

    public function historialMovimientos($filtros = [], $pagina = 1, $tamano = 50) {
        $conn = $this->getConnCentral();
        if (!$conn) return ['filas' => [], 'total' => 0];

        $w = $this->buildWhereHistorial($filtros);
        $where = $w['where'];
        $pagina = max(1, (int)$pagina);
        $tamano = max(1, (int)$tamano);
        $offset = ($pagina - 1) * $tamano;

        // Total
        $sqlTotal = "SELECT COUNT(*) AS Total
                     FROM EB_TransferDepositoDet d WITH (NOLOCK)
                     JOIN EB_TransferDepositoLote l WITH (NOLOCK) ON l.IdLote = d.IdLote
                     WHERE $where";
        $stmtTotal = sqlsrv_query($conn, $sqlTotal, $w['params']);
        $total = 0;
        if ($stmtTotal && ($row = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC))) {
            $total = (int)$row['Total'];
            sqlsrv_free_stmt($stmtTotal);
        }

        $artDest = "COALESCE(d.ArtDestinoN, UPPER(LTRIM(RTRIM(d.ArtDestino))))";
        $sqlMovs = "SELECT l.FechaProceso, l.Usuario, l.NroComprobante, l.IdTarea,
                           d.DepOrigenN, d.UbicOrigen, d.Articulo, d.Cantidad,
                           d.DepDestinoN, d.UbicDestino, d.ArtDestino, d.CantAlta,
                           CASE WHEN d.ArticuloN <> $artDest THEN 1 ELSE 0 END AS EsRecodificacion
                    FROM EB_TransferDepositoDet d WITH (NOLOCK)
                    JOIN EB_TransferDepositoLote l WITH (NOLOCK) ON l.IdLote = d.IdLote
                    WHERE $where
                    ORDER BY l.FechaProceso DESC, d.Fila
                    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

        $params = array_merge($w['params'], [$offset, $tamano]);
        $stmtMovs = sqlsrv_query($conn, $sqlMovs, $params);
        $filas = [];
        if ($stmtMovs) {
            while ($r = sqlsrv_fetch_array($stmtMovs, SQLSRV_FETCH_ASSOC)) {
                $fechaStr = ($r['FechaProceso'] instanceof DateTime) ? $r['FechaProceso']->format('Y-m-d H:i:s') : (string)$r['FechaProceso'];
                $filas[] = [
                    'fecha'             => $fechaStr,
                    'usuario'           => trim($r['Usuario'] ?? ''),
                    'comprobante'       => (string)($r['NroComprobante'] ?? ''),
                    'tarea'             => $r['IdTarea'],
                    'dep_origen'        => trim($r['DepOrigenN'] ?? ''),
                    'ubic_origen'       => trim($r['UbicOrigen'] ?? ''),
                    'art_origen'        => trim($r['Articulo'] ?? ''),
                    'cant_baja'         => (int)($r['Cantidad'] ?? 0),
                    'dep_destino'       => trim($r['DepDestinoN'] ?? ''),
                    'ubic_destino'      => trim($r['UbicDestino'] ?? ''),
                    'art_destino'       => trim($r['ArtDestino'] ?? ''),
                    'cant_alta'         => (int)($r['CantAlta'] ?? 0),
                    'es_recodificacion' => (bool)$r['EsRecodificacion'],
                ];
            }
            sqlsrv_free_stmt($stmtMovs);
        }

        return ['filas' => $filas, 'total' => $total];
    }
}
