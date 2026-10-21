<?php
/**
 * Clase AjusteStockDeposito
 * Ajuste masivo de stock en Tango por depósito (sin WMS / sin ubicación).
 * Conexión a LAKER_SA (central).
 *
 * Flujo: crearLote -> validarLote -> (corregir filas / revalidar) -> ejecutarLote.
 * La validación deja el resultado por fila en EB_AjusteStockDet (Estado / Resultado).
 */
class AjusteStockDeposito {
    private $conexion;

    public static $DEPOSITOS_HABILITADOS = [
        '01' => 'PRINCIPAL',
        '05' => 'DIFERENCIAS',
    ];

    const ESTADO_OK          = 'OK';
    const ESTADO_ADVERTENCIA = 'ADVERTENCIA';
    const ESTADO_ERROR       = 'ERROR';

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
        $conn = $this->conexion->conectar('central');
        if (!$conn) throw new Exception("No se pudo conectar a la base central");
        return $conn;
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
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Validación de forma de las filas crudas [Depósito, Artículo, Cantidad, Partida?].
     * Devuelve [filasValidas, errores]. Cada fila válida: [dep, art, cant, partida|null].
     */
    public static function validarForma($filasCrudas) {
        $filas = [];
        $errores = [];
        $habilitados = implode(', ', array_keys(self::$DEPOSITOS_HABILITADOS));

        foreach ($filasCrudas as $indice => $fila) {
            $numFila = $indice + 1;
            $fila = is_array($fila) ? array_values($fila) : [];

            // Se toleran celdas vacías al final (Excel suele mandarlas)
            while (count($fila) > 3 && trim((string)end($fila)) === '') array_pop($fila);

            if (count($fila) < 3 || count($fila) > 4) {
                $errores[] = "Fila $numFila: se esperaban 3 o 4 columnas (Depósito, Artículo, Cantidad, Partida), se recibieron " . count($fila) . ".";
                continue;
            }

            $depCrudo = trim((string)$fila[0]);
            $art      = strtoupper(trim((string)$fila[1]));
            $cantStr  = str_replace(' ', '', trim((string)$fila[2]));
            // "1.066" / "-12.001": separador de miles con punto
            if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $cantStr)) $cantStr = str_replace('.', '', $cantStr);
            // "5.0": entero que Excel exporta con decimales
            if (preg_match('/^-?\d+\.0+$/', $cantStr)) $cantStr = substr($cantStr, 0, strpos($cantStr, '.'));
            $partida  = isset($fila[3]) ? strtoupper(trim((string)$fila[3])) : '';

            $problemas = [];
            $dep = self::normalizarDeposito($depCrudo);
            if ($dep === null || !isset(self::$DEPOSITOS_HABILITADOS[$dep])) {
                $problemas[] = "el depósito \"$depCrudo\" no está habilitado ($habilitados)";
            }
            if ($art === '') $problemas[] = "el artículo es obligatorio";
            if (strlen($art) > 15) $problemas[] = "el artículo \"$art\" supera los 15 caracteres";
            if (strlen($partida) > 25) $problemas[] = "la partida \"$partida\" supera los 25 caracteres";

            $cant = null;
            if (!preg_match('/^-?\d+$/', $cantStr)) {
                $problemas[] = "la cantidad \"" . trim((string)$fila[2]) . "\" debe ser un número entero (negativo = baja, positivo = alta)";
            } elseif ((int)$cantStr === 0) {
                $problemas[] = "la cantidad no puede ser cero";
            } else {
                $cant = (int)$cantStr;
            }

            if (!empty($problemas)) {
                $errores[] = "Fila $numFila: " . implode('; ', $problemas) . ".";
                continue;
            }

            $filas[] = [$dep, $art, $cant, $partida === '' ? null : $partida];
        }

        return [$filas, $errores];
    }

    // =========================================================================
    // LOTE
    // =========================================================================

    public function crearLote($loteId, $usuario, $host, $leyenda, $filas) {
        $conn = $this->getConnCentral();

        if (!sqlsrv_begin_transaction($conn)) {
            throw new Exception("No se pudo iniciar la transacción");
        }

        $sqlLote = "INSERT INTO EB_AjusteStockLote (IdLote, Usuario, Host, Leyenda, Estado, Filas)
                    VALUES (?, ?, ?, ?, 'CARGADO', ?)";
        if (!sqlsrv_query($conn, $sqlLote, [$loteId, $usuario, $host, $leyenda, count($filas)])) {
            $err = sqlsrv_errors();
            sqlsrv_rollback($conn);
            throw new Exception("Error al insertar cabecera del lote: " . print_r($err, true));
        }

        try {
            $this->insertarDetalle($conn, $loteId, $filas, 1);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }

        sqlsrv_commit($conn);
        return true;
    }

    // Filas por sentencia en los INSERT/UPDATE masivos de las tablas del lote. Bloques chicos:
    // cada sentencia toma pocos bloqueos y no escala a bloqueo de tabla.
    const TAM_BLOQUE = 100;

    // Artículos por bloque de ejecución en Tango (una transacción corta por bloque).
    const ARTICULOS_POR_BLOQUE = 50;

    private function insertarDetalle($conn, $loteId, $filas, $filaDesde) {
        $numero = $filaDesde;
        foreach (array_chunk($filas, self::TAM_BLOQUE) as $bloque) {
            $valores = [];
            $params = [];
            foreach ($bloque as $f) {
                $valores[] = '(?, ?, ?, ?, ?, ?)';
                array_push($params, $loteId, $numero++, $f[0], $f[1], $f[2], $f[3]);
            }
            $sql = "INSERT INTO EB_AjusteStockDet (IdLote, Fila, CodDeposito, CodArticulo, Cantidad, Partida)
                    VALUES " . implode(', ', $valores);
            if (!sqlsrv_query($conn, $sql, $params)) {
                $err = sqlsrv_errors();
                throw new Exception("Error al insertar detalle del lote (filas hasta $numero): " . print_r($err, true));
            }
        }
        return $numero;
    }

    public function obtenerCabecera($loteId) {
        $conn = $this->getConnCentral();
        $sql = "SELECT IdLote, Usuario, Leyenda, Estado, Filas, FechaAlta, FechaValidacion,
                       FechaEjecucion, NroComprobantes, Mensaje
                FROM EB_AjusteStockLote WITH (NOLOCK) WHERE IdLote = ?";
        $stmt = sqlsrv_query($conn, $sql, [$loteId]);
        if (!$stmt) return null;
        $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        if (!$r) return null;

        return [
            'id'              => strtoupper((string)$r['IdLote']),
            'usuario'         => trim($r['Usuario'] ?? ''),
            'leyenda'         => trim($r['Leyenda'] ?? ''),
            'estado'          => trim($r['Estado'] ?? ''),
            'filas'           => (int)$r['Filas'],
            'fecha_alta'      => self::fecha($r['FechaAlta']),
            'fecha_validacion'=> self::fecha($r['FechaValidacion']),
            'fecha_ejecucion' => self::fecha($r['FechaEjecucion']),
            'comprobantes'    => trim($r['NroComprobantes'] ?? ''),
            'mensaje'         => trim($r['Mensaje'] ?? ''),
        ];
    }

    public function obtenerDetalle($loteId) {
        $conn = $this->getConnCentral();
        $sql = "SELECT Fila, CodDeposito, CodArticulo, Cantidad, Partida, Estado,
                       StockActual, StockResultante, Disponible, Resultado, Sugerencia,
                       Ejecutada, NroComprobante
                FROM EB_AjusteStockDet WITH (NOLOCK)
                WHERE IdLote = ?
                ORDER BY Fila";
        $stmt = sqlsrv_query($conn, $sql, [$loteId]);
        $filas = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $filas[] = [
                    'fila'             => (int)$r['Fila'],
                    'deposito'         => trim($r['CodDeposito'] ?? ''),
                    'articulo'         => trim($r['CodArticulo'] ?? ''),
                    'cantidad'         => (int)$r['Cantidad'],
                    'partida'          => trim($r['Partida'] ?? ''),
                    'estado'           => $r['Estado'] !== null ? trim($r['Estado']) : null,
                    'stock_actual'     => $r['StockActual'] !== null ? (float)$r['StockActual'] : null,
                    'stock_resultante' => $r['StockResultante'] !== null ? (float)$r['StockResultante'] : null,
                    'disponible'       => $r['Disponible'] !== null ? (float)$r['Disponible'] : null,
                    'resultado'        => trim($r['Resultado'] ?? ''),
                    'sugerencia'       => $r['Sugerencia'] ? json_decode($r['Sugerencia'], true) : null,
                    'ejecutada'        => (bool)$r['Ejecutada'],
                    'comprobante'      => trim($r['NroComprobante'] ?? ''),
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        return $filas;
    }

    /**
     * Verifica que el lote exista, pertenezca al usuario (salvo admin) y no esté ejecutado.
     */
    public function verificarEditable($loteId, $usuario, $esAdmin) {
        $cab = $this->obtenerCabecera($loteId);
        if (!$cab) throw new Exception("El lote no existe.");
        if (!$esAdmin && strcasecmp($cab['usuario'], $usuario) !== 0) {
            throw new Exception("El lote pertenece a otro usuario ({$cab['usuario']}).");
        }
        if ($cab['estado'] === 'EJECUTADO') {
            throw new Exception("El lote ya fue ejecutado y no se puede modificar.");
        }
        return $cab;
    }

    private function marcarSinValidar($conn, $loteId) {
        sqlsrv_query($conn, "UPDATE EB_AjusteStockLote
                             SET Estado = 'CARGADO',
                                 Filas = (SELECT COUNT(*) FROM EB_AjusteStockDet WHERE IdLote = ?)
                             WHERE IdLote = ?", [$loteId, $loteId]);
    }

    public function actualizarFila($loteId, $fila, $dep, $art, $cant, $partida) {
        $conn = $this->getConnCentral();
        $sql = "UPDATE EB_AjusteStockDet
                SET CodDeposito = ?, CodArticulo = ?, Cantidad = ?, Partida = ?,
                    Estado = NULL, Resultado = NULL, Sugerencia = NULL,
                    StockActual = NULL, StockResultante = NULL, Disponible = NULL
                WHERE IdLote = ? AND Fila = ? AND Ejecutada = 0";
        $stmt = sqlsrv_query($conn, $sql, [$dep, $art, $cant, $partida, $loteId, (int)$fila]);
        if (!$stmt) {
            throw new Exception("Error al actualizar la fila: " . print_r(sqlsrv_errors(), true));
        }
        if (sqlsrv_rows_affected($stmt) === 0) {
            throw new Exception("La fila no existe o ya fue ejecutada en Tango.");
        }
        $this->marcarSinValidar($conn, $loteId);
        return true;
    }

    public function eliminarFilas($loteId, $filas) {
        $conn = $this->getConnCentral();
        foreach ($filas as $fila) {
            sqlsrv_query($conn, "DELETE FROM EB_AjusteStockDet WHERE IdLote = ? AND Fila = ? AND Ejecutada = 0", [$loteId, (int)$fila]);
        }
        $this->marcarSinValidar($conn, $loteId);
        return true;
    }

    /** Pone la cantidad de una baja en el máximo disponible calculado en la última validación. */
    public function ajustarAStock($loteId, $fila) {
        $conn = $this->getConnCentral();
        $stmt = sqlsrv_query($conn, "SELECT Cantidad, Disponible FROM EB_AjusteStockDet WHERE IdLote = ? AND Fila = ? AND Ejecutada = 0", [$loteId, (int)$fila]);
        $r = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
        if (!$r) throw new Exception("La fila no existe o ya fue ejecutada en Tango.");
        if ((int)$r['Cantidad'] >= 0) throw new Exception("Solo se puede ajustar a stock una baja.");
        if ($r['Disponible'] === null) throw new Exception("Revalidá el lote antes de ajustar a stock.");

        $disponible = (int)floor((float)$r['Disponible']);
        if ($disponible <= 0) {
            throw new Exception("No hay stock disponible para esta fila. Quitala y pasala al listado de faltantes.");
        }

        sqlsrv_query($conn, "UPDATE EB_AjusteStockDet
                             SET Cantidad = ?, Estado = NULL, Resultado = NULL, Sugerencia = NULL
                             WHERE IdLote = ? AND Fila = ?", [-$disponible, $loteId, (int)$fila]);
        $this->marcarSinValidar($conn, $loteId);

        return ['cantidad_original' => (int)$r['Cantidad'], 'cantidad_nueva' => -$disponible];
    }

    /** Reemplaza una baja sin partida por una fila por partida según el desglose FIFO sugerido. */
    public function aceptarFifo($loteId, $fila) {
        $conn = $this->getConnCentral();
        $stmt = sqlsrv_query($conn, "SELECT CodDeposito, CodArticulo, Sugerencia FROM EB_AjusteStockDet WHERE IdLote = ? AND Fila = ? AND Ejecutada = 0", [$loteId, (int)$fila]);
        $r = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
        if (!$r) throw new Exception("La fila no existe o ya fue ejecutada en Tango.");

        $sugerencia = $r['Sugerencia'] ? json_decode($r['Sugerencia'], true) : null;
        if (empty($sugerencia)) throw new Exception("La fila no tiene un desglose de partidas sugerido. Revalidá el lote.");

        $nuevas = [];
        foreach ($sugerencia as $s) {
            $nuevas[] = [trim($r['CodDeposito']), trim($r['CodArticulo']), -(int)$s['cantidad'], $s['partida']];
        }

        if (!sqlsrv_begin_transaction($conn)) throw new Exception("No se pudo iniciar la transacción");
        try {
            sqlsrv_query($conn, "DELETE FROM EB_AjusteStockDet WHERE IdLote = ? AND Fila = ? AND Ejecutada = 0", [$loteId, (int)$fila]);
            $this->insertarDetalle($conn, $loteId, $nuevas, $this->siguienteFila($conn, $loteId));
            $this->marcarSinValidar($conn, $loteId);
            sqlsrv_commit($conn);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }
        return count($nuevas);
    }

    /** Borra las filas en ERROR del lote y carga las filas corregidas. */
    public function reemplazarErrores($loteId, $filas) {
        $conn = $this->getConnCentral();
        if (!sqlsrv_begin_transaction($conn)) throw new Exception("No se pudo iniciar la transacción");
        try {
            $stmt = sqlsrv_query($conn, "DELETE FROM EB_AjusteStockDet WHERE IdLote = ? AND Estado = 'ERROR' AND Ejecutada = 0", [$loteId]);
            $borradas = $stmt ? sqlsrv_rows_affected($stmt) : 0;
            $this->insertarDetalle($conn, $loteId, $filas, $this->siguienteFila($conn, $loteId));
            $this->marcarSinValidar($conn, $loteId);
            sqlsrv_commit($conn);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            throw $e;
        }
        return ['borradas' => (int)$borradas, 'insertadas' => count($filas)];
    }

    private function siguienteFila($conn, $loteId) {
        $stmt = sqlsrv_query($conn, "SELECT ISNULL(MAX(Fila), 0) + 1 AS Sig FROM EB_AjusteStockDet WHERE IdLote = ?", [$loteId]);
        $r = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
        return $r ? (int)$r['Sig'] : 1;
    }

    public function descartarLote($loteId) {
        $conn = $this->getConnCentral();
        $stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS N FROM EB_AjusteStockDet WHERE IdLote = ? AND Ejecutada = 1", [$loteId]);
        $r = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
        if ($r && (int)$r['N'] > 0) {
            throw new Exception("El lote ya tiene {$r['N']} filas aplicadas en Tango: no se puede descartar. Quitá las filas pendientes que no quieras ajustar.");
        }
        sqlsrv_query($conn, "DELETE FROM EB_AjusteStockLote WHERE IdLote = ? AND Estado <> 'EJECUTADO'", [$loteId]);
        return true;
    }

    // =========================================================================
    // VALIDACIÓN DE STOCK Y PARTIDAS
    // =========================================================================

    /**
     * Valida todas las filas del lote contra Tango y guarda Estado/Resultado por fila.
     * Reglas:
     *  - artículo existente, que lleve stock y no esté inhabilitado;
     *  - sin duplicados artículo+depósito+partida, ni alta y baja del mismo artículo+depósito;
     *  - bajas: la suma de bajas del artículo en el depósito no supera el stock (STA19);
     *           advertencia si el stock resultante queda por debajo de comprometido + reservado;
     *  - partidas (STA10), mismas reglas que EB_AjusteStock_Procesar:
     *      alta sin partida -> última partida del depósito / del artículo (advertencia; error si no hay);
     *      baja sin partida -> FIFO sobre el saldo libre (advertencia con el desglose; error si no alcanza);
     *      partida informada -> debe existir y, en bajas, tener saldo; error si el artículo no usa partidas.
     */
    public function validarLote($loteId) {
        $conn = $this->getConnCentral();

        // 1) Detalle + datos del artículo + stock del depósito (una sola consulta)
        $sql = "SELECT d.Fila, d.CodDeposito, d.CodArticulo, d.Cantidad, d.Partida,
                       a.COD_ARTICU                    AS ArtTango,
                       CAST(ISNULL(a.USA_PARTID, 0) AS INT) AS UsaPartida,
                       CAST(ISNULL(a.STOCK, 1) AS INT)      AS LlevaStock,
                       LTRIM(RTRIM(ISNULL(a.PERFIL, '')))   AS Perfil,
                       ISNULL(s.CANT_STOCK, 0)              AS StockActual,
                       -- Comprometido por pedidos, de STA19 (la vista STOCK_CENTRAL era muy lenta por fila)
                       ISNULL(s.CANT_COMP, 0)               AS Comprometido
                FROM EB_AjusteStockDet d WITH (NOLOCK)
                LEFT JOIN STA11 a WITH (NOLOCK) ON a.COD_ARTICU = d.CodArticulo
                LEFT JOIN STA19 s WITH (NOLOCK) ON s.COD_ARTICU = d.CodArticulo AND s.COD_DEPOSI = d.CodDeposito
                WHERE d.IdLote = ? AND d.Ejecutada = 0   -- lo ya aplicado en Tango no se revalida
                ORDER BY d.Fila";
        $stmt = sqlsrv_query($conn, $sql, [$loteId]);
        if (!$stmt) throw new Exception("Error al leer el lote para validar: " . print_r(sqlsrv_errors(), true));

        $filas = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $filas[] = [
                'fila'         => (int)$r['Fila'],
                'dep'          => trim($r['CodDeposito']),
                'art'          => trim($r['CodArticulo']),
                'cant'         => (int)$r['Cantidad'],
                'partida'      => trim($r['Partida'] ?? ''),
                'existe'       => $r['ArtTango'] !== null,
                'usa_partida'  => (int)$r['UsaPartida'] === 1,
                'lleva_stock'  => (int)$r['LlevaStock'] === 1,
                'perfil'       => strtoupper($r['Perfil']),
                'stock'        => (float)$r['StockActual'],
                'comprometido' => (float)$r['Comprometido'],
                'errores'      => [],
                'avisos'       => [],
                'sugerencia'   => null,
                'disponible'   => null,
            ];
        }
        sqlsrv_free_stmt($stmt);

        $ejecutadas = $this->contarEjecutadas($conn, $loteId);
        if (empty($filas)) {
            if ($ejecutadas > 0) {
                // Todo lo pendiente ya se aplicó: la próxima ejecución cierra el lote
                return ['ok' => 0, 'advertencias' => 0, 'errores' => 0, 'estado' => 'EN_PROCESO'];
            }
            throw new Exception("El lote no tiene filas.");
        }

        // 2) Saldos por partida de los artículos con partida del lote
        $saldosPartida = $this->saldosPartida($conn, $loteId);   // [art|dep] => [partida => saldo] (orden FIFO)
        $partidasArt   = $this->partidasExistentes($conn, $loteId); // [art] => [partida => true]
        $destinoAlta   = $this->partidasDestinoAlta($conn, $loteId); // [art|dep] => partida

        // 3) Agrupaciones del lote
        $porClave = [];      // art|dep|partida => [idx...]
        $porArtDep = [];     // art|dep => ['altas'=>n, 'bajas'=>n, 'idx'=>[...]]
        foreach ($filas as $i => $f) {
            $porClave[$f['art'] . '|' . $f['dep'] . '|' . $f['partida']][] = $i;
            $k = $f['art'] . '|' . $f['dep'];
            if (!isset($porArtDep[$k])) $porArtDep[$k] = ['altas' => 0, 'bajas' => 0, 'idx' => []];
            if ($f['cant'] > 0) $porArtDep[$k]['altas'] += $f['cant'];
            else $porArtDep[$k]['bajas'] += -$f['cant'];
            $porArtDep[$k]['idx'][] = $i;
        }

        // 4) Reglas por fila
        foreach ($filas as $i => &$f) {
            if (!$f['existe']) {
                $f['errores'][] = "El artículo no existe en Tango";
                continue;
            }
            if (!$f['lleva_stock']) $f['errores'][] = "El artículo no lleva stock";
            if ($f['perfil'] === 'N') $f['errores'][] = "El artículo está inhabilitado";

            if (count($porClave[$f['art'] . '|' . $f['dep'] . '|' . $f['partida']]) > 1) {
                $f['errores'][] = "Fila duplicada (mismo artículo, depósito" . ($f['partida'] !== '' ? " y partida" : "") . "): unificalas en una sola";
            }

            $grupo = $porArtDep[$f['art'] . '|' . $f['dep']];
            if ($grupo['altas'] > 0 && $grupo['bajas'] > 0) {
                $f['errores'][] = "Hay altas y bajas del mismo artículo en el depósito {$f['dep']}: dejá una sola fila con el neto";
            }

            // Partidas
            if ($f['partida'] !== '' && !$f['usa_partida']) {
                $f['errores'][] = "El artículo no lleva partidas: dejá la partida vacía";
            }
            if ($f['usa_partida']) {
                $saldos = $saldosPartida[$f['art'] . '|' . $f['dep']] ?? [];
                if ($f['cant'] > 0) {
                    if ($f['partida'] === '') {
                        $destino = $destinoAlta[$f['art'] . '|' . $f['dep']] ?? null;
                        if ($destino === null || !isset($partidasArt[$f['art']][$destino])) {
                            $f['errores'][] = "El artículo usa partidas pero no se encontró ninguna partida existente: informá la partida";
                        } else {
                            $f['avisos'][] = "Sin partida: el alta se imputa a la partida $destino";
                        }
                    } elseif (!isset($partidasArt[$f['art']][$f['partida']])) {
                        $f['errores'][] = "La partida {$f['partida']} no existe para el artículo";
                    }
                } elseif ($f['partida'] !== '') {
                    $saldo = $saldos[$f['partida']] ?? 0;
                    $f['disponible'] = max(0, $saldo);
                    if (!isset($partidasArt[$f['art']][$f['partida']])) {
                        $f['errores'][] = "La partida {$f['partida']} no existe para el artículo";
                    } elseif ($saldo < -$f['cant']) {
                        $f['errores'][] = "Saldo insuficiente en la partida {$f['partida']}: saldo " . self::num($saldo) . ", baja " . (-$f['cant']);
                    }
                } else {
                    // Baja sin partida: FIFO sobre el saldo que no usan las filas con partida explícita
                    $usado = [];
                    foreach ($grupo['idx'] as $j) {
                        $o = $filas[$j];
                        if ($o['cant'] < 0 && $o['partida'] !== '') {
                            $usado[$o['partida']] = ($usado[$o['partida']] ?? 0) + (-$o['cant']);
                        }
                    }
                    $pendiente = -$f['cant'];
                    $desglose = [];
                    $totalLibre = 0;
                    foreach ($saldos as $partida => $saldo) {
                        $libre = $saldo - ($usado[$partida] ?? 0);
                        if ($libre <= 0) continue;
                        $totalLibre += $libre;
                        if ($pendiente > 0) {
                            $toma = (int)min($pendiente, floor($libre));
                            if ($toma > 0) {
                                $desglose[] = ['partida' => (string)$partida, 'cantidad' => $toma];
                                $pendiente -= $toma;
                            }
                        }
                    }
                    $f['disponible'] = $totalLibre;
                    if ($pendiente > 0) {
                        $f['errores'][] = "Saldo insuficiente en partidas: disponible " . self::num($totalLibre) . ", baja " . (-$f['cant']);
                    } else {
                        $f['sugerencia'] = $desglose;
                        $f['avisos'][] = "Sin partida: al ejecutar se descuenta FIFO de " . count($desglose) . " partida(s)";
                    }
                }
            }

            // Stock del depósito (todas las bajas del artículo en el depósito juntas)
            $neto = $grupo['altas'] - $grupo['bajas'];
            $f['resultante'] = $f['stock'] + $neto;
            if ($f['cant'] < 0) {
                $otrasBajas = $grupo['bajas'] - (-$f['cant']);
                $dispDeposito = max(0, $f['stock'] - $otrasBajas);
                $f['disponible'] = $f['disponible'] === null ? $dispDeposito : min($f['disponible'], $dispDeposito);

                if ($grupo['bajas'] > $f['stock']) {
                    $f['errores'][] = "Stock insuficiente en depósito {$f['dep']}: stock " . self::num($f['stock'])
                        . ", baja " . $grupo['bajas'] . ($otrasBajas > 0 ? " (sumando todas las filas del artículo)" : "");
                } elseif ($f['comprometido'] > 0 && $f['resultante'] < $f['comprometido']) {
                    $f['avisos'][] = "El stock resultante (" . self::num($f['resultante']) . ") queda por debajo de lo comprometido en pedidos (" . self::num($f['comprometido']) . ")";
                }
            }
        }
        unset($f);

        // 5) Guardar resultados (UPDATE en bloques contra una tabla de valores)
        $resumen = ['ok' => 0, 'advertencias' => 0, 'errores' => 0];
        $resultados = [];
        foreach ($filas as $f) {
            if (!empty($f['errores'])) {
                $estado = self::ESTADO_ERROR;
                $texto = implode('. ', array_merge($f['errores'], $f['avisos']));
            } elseif (!empty($f['avisos'])) {
                $estado = self::ESTADO_ADVERTENCIA;
                $texto = implode('. ', $f['avisos']);
            } else {
                $estado = self::ESTADO_OK;
                $texto = null;
            }
            $resumen[$estado === self::ESTADO_OK ? 'ok' : ($estado === self::ESTADO_ERROR ? 'errores' : 'advertencias')]++;

            $resultados[] = [
                $f['fila'],
                $estado,
                $texto !== null ? mb_substr($texto, 0, 500) : null,
                $f['existe'] ? (string)$f['stock'] : null,
                $f['existe'] && isset($f['resultante']) ? (string)$f['resultante'] : null,
                $f['disponible'] !== null ? (string)$f['disponible'] : null,
                $f['sugerencia'] ? json_encode($f['sugerencia']) : null,
            ];
        }

        if (!sqlsrv_begin_transaction($conn)) throw new Exception("No se pudo iniciar la transacción");
        foreach (array_chunk($resultados, self::TAM_BLOQUE) as $bloque) {
            $valores = [];
            $params = [];
            foreach ($bloque as $r) {
                $valores[] = '(?, ?, ?, ?, ?, ?, ?)';
                array_push($params, ...$r);
            }
            $params[] = $loteId;
            $sqlUpd = "UPDATE d
                       SET d.Estado          = v.Estado,
                           d.Resultado       = v.Resultado,
                           d.StockActual     = CAST(v.StockActual AS DECIMAL(18, 2)),
                           d.StockResultante = CAST(v.StockResultante AS DECIMAL(18, 2)),
                           d.Disponible      = CAST(v.Disponible AS DECIMAL(18, 2)),
                           d.Sugerencia      = v.Sugerencia
                       FROM EB_AjusteStockDet d
                       JOIN (VALUES " . implode(', ', $valores) . ")
                            AS v (Fila, Estado, Resultado, StockActual, StockResultante, Disponible, Sugerencia)
                         ON v.Fila = d.Fila
                       WHERE d.IdLote = ?";
            if (!sqlsrv_query($conn, $sqlUpd, $params)) {
                $err = sqlsrv_errors();
                sqlsrv_rollback($conn);
                throw new Exception("Error al guardar la validación: " . print_r($err, true));
            }
        }

        // Con bloques ya aplicados en Tango el lote queda EN_PROCESO (se continúa la ejecución)
        $estadoLote = $resumen['errores'] > 0 ? 'CON_ERRORES' : ($ejecutadas > 0 ? 'EN_PROCESO' : 'VALIDADO');
        sqlsrv_query($conn, "UPDATE EB_AjusteStockLote
                             SET Estado = ?, FechaValidacion = GETDATE(), Filas = ?, Mensaje = NULL
                             WHERE IdLote = ? AND Estado <> 'EJECUTADO'",
                     [$estadoLote, count($filas) + $ejecutadas, $loteId]);
        sqlsrv_commit($conn);

        $resumen['estado'] = $estadoLote;
        return $resumen;
    }

    /**
     * Saldo por partida (STA10) de los artículos del lote en cada depósito, en orden FIFO
     * (fecha de la partida, id) — el mismo orden que usa EB_AjusteStock_Procesar.
     */
    private function saldosPartida($conn, $loteId) {
        $sql = "SELECT p.COD_ARTICU, p.COD_DEPOSI, UPPER(LTRIM(RTRIM(p.N_PARTIDA))) AS N_PARTIDA,
                       SUM(p.CANTIDAD) AS SALDO, MIN(p.FECHA) AS FECHA, MIN(p.ID_STA10) AS ID
                FROM STA10 p WITH (NOLOCK)
                JOIN (SELECT DISTINCT CodArticulo, CodDeposito
                      FROM EB_AjusteStockDet WITH (NOLOCK) WHERE IdLote = ?) d
                  ON d.CodArticulo = p.COD_ARTICU AND d.CodDeposito = p.COD_DEPOSI
                GROUP BY p.COD_ARTICU, p.COD_DEPOSI, UPPER(LTRIM(RTRIM(p.N_PARTIDA)))
                HAVING SUM(p.CANTIDAD) > 0
                ORDER BY p.COD_ARTICU, p.COD_DEPOSI, MIN(p.FECHA), MIN(p.ID_STA10)";
        $stmt = sqlsrv_query($conn, $sql, [$loteId]);
        if (!$stmt) throw new Exception("Error al leer saldos por partida (STA10): " . print_r(sqlsrv_errors(), true));

        $saldos = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $saldos[trim($r['COD_ARTICU']) . '|' . trim($r['COD_DEPOSI'])][$r['N_PARTIDA']] = (float)$r['SALDO'];
        }
        sqlsrv_free_stmt($stmt);
        return $saldos;
    }

    /** Partidas existentes (STA10, cualquier depósito) de los artículos del lote. */
    private function partidasExistentes($conn, $loteId) {
        $sql = "SELECT DISTINCT p.COD_ARTICU, UPPER(LTRIM(RTRIM(p.N_PARTIDA))) AS N_PARTIDA
                FROM STA10 p WITH (NOLOCK)
                JOIN (SELECT DISTINCT CodArticulo FROM EB_AjusteStockDet WITH (NOLOCK) WHERE IdLote = ?) d
                  ON d.CodArticulo = p.COD_ARTICU";
        $stmt = sqlsrv_query($conn, $sql, [$loteId]);
        if (!$stmt) throw new Exception("Error al leer partidas (STA10): " . print_r(sqlsrv_errors(), true));

        $partidas = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $partidas[trim($r['COD_ARTICU'])][$r['N_PARTIDA']] = true;
        }
        sqlsrv_free_stmt($stmt);
        return $partidas;
    }

    /**
     * Partida a la que se imputa un alta sin partida, igual que el SP original:
     * última partida del artículo en el depósito (mayor ID_STA10) o, si no hay,
     * la última partida del artículo (sof_partidas). Devuelve [art|dep] => partida.
     */
    private function partidasDestinoAlta($conn, $loteId) {
        $sql = "SELECT d.CodArticulo, d.CodDeposito,
                       UPPER(LTRIM(RTRIM(COALESCE(
                           (SELECT TOP (1) p.N_PARTIDA FROM STA10 p WITH (NOLOCK)
                            WHERE p.COD_ARTICU = d.CodArticulo AND p.COD_DEPOSI = d.CodDeposito
                            ORDER BY p.ID_STA10 DESC),
                           (SELECT MAX(sp.ULTIMA_PARTIDA) COLLATE DATABASE_DEFAULT FROM sof_partidas sp WITH (NOLOCK)
                            WHERE sp.COD_ARTICU = d.CodArticulo COLLATE DATABASE_DEFAULT)
                       )))) AS Partida
                FROM (SELECT DISTINCT x.CodArticulo, x.CodDeposito
                      FROM EB_AjusteStockDet x WITH (NOLOCK)
                      JOIN STA11 a WITH (NOLOCK) ON a.COD_ARTICU = x.CodArticulo AND a.USA_PARTID = 1
                      WHERE x.IdLote = ? AND x.Cantidad > 0 AND ISNULL(x.Partida, '') = '') d";
        $stmt = sqlsrv_query($conn, $sql, [$loteId]);
        if (!$stmt) throw new Exception("Error al buscar partidas para altas: " . print_r(sqlsrv_errors(), true));

        $destino = [];
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($r['Partida'] !== null && $r['Partida'] !== '') {
                $destino[trim($r['CodArticulo']) . '|' . trim($r['CodDeposito'])] = $r['Partida'];
            }
        }
        sqlsrv_free_stmt($stmt);
        return $destino;
    }

    // =========================================================================
    // EJECUCIÓN
    // =========================================================================

    private function contarEjecutadas($conn, $loteId) {
        $stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS N FROM EB_AjusteStockDet WITH (NOLOCK) WHERE IdLote = ? AND Ejecutada = 1", [$loteId]);
        $r = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
        return $r ? (int)$r['N'] : 0;
    }

    /**
     * Ejecuta UN bloque de hasta ARTICULOS_POR_BLOQUE artículos pendientes (transacción corta).
     * La página llama hasta que 'terminado' sea true. Si Tango está ocupado (bloqueo o deadlock)
     * devuelve 'tango_ocupado' => true sin haber aplicado nada, para reintentar más tarde.
     */
    public function ejecutarBloque($loteId, $usuario, $host) {
        $conn = $this->getConnCentral();

        $stmt = sqlsrv_query($conn, "{CALL EB_AjusteStock_Procesar(?, ?, ?, ?)}",
                             [$loteId, $usuario, $host, self::ARTICULOS_POR_BLOQUE]);
        if (!$stmt) {
            $errores = sqlsrv_errors() ?: [];
            foreach ($errores as $e) {
                if (strpos($e['message'] ?? '', 'TANGO_OCUPADO') !== false) {
                    return ['ok' => false, 'tango_ocupado' => true, 'terminado' => false,
                            'filas_con_error' => [], 'filas_procesadas' => 0, 'filas_pendientes' => null,
                            'comprobantes' => null,
                            'mensaje' => 'Tango está ocupado con otro proceso. El bloque no se aplicó; se reintenta en unos segundos.'];
                }
            }
            throw new Exception("Error al ejecutar EB_AjusteStock_Procesar: " . print_r($errores, true));
        }

        // Resultset 1: filas con error
        $filasConError = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $filasConError[] = $row;
        }

        // Resultset 2: resumen
        $resumen = [];
        if (sqlsrv_next_result($stmt)) {
            if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resumen = $row;
            }
        }
        sqlsrv_free_stmt($stmt);

        return [
            'ok'               => !empty($resumen['Ok']),
            'tango_ocupado'    => false,
            'terminado'        => !empty($resumen['Terminado']),
            'filas_con_error'  => $filasConError,
            'filas_procesadas' => (int)($resumen['FilasProcesadas'] ?? 0),
            'filas_pendientes' => (int)($resumen['FilasPendientes'] ?? 0),
            'comprobantes'     => $resumen['NroComprobantes'] ?? null,
            'mensaje'          => $resumen['Mensaje'] ?? '',
        ];
    }

    // =========================================================================
    // HISTORIAL
    // =========================================================================

    public function historialLotes($usuario, $esAdmin, $soloPendientes = false) {
        $conn = $this->getConnCentral();
        $where = [];
        $params = [];
        if (!$esAdmin) {
            $where[] = "l.Usuario = ?";
            $params[] = $usuario;
        }
        if ($soloPendientes) $where[] = "l.Estado <> 'EJECUTADO'";

        $sql = "SELECT TOP (100) l.IdLote, l.Usuario, l.Leyenda, l.Estado, l.FechaAlta, l.FechaEjecucion, l.NroComprobantes,
                       COUNT(d.Fila) AS Filas,
                       SUM(CASE WHEN d.Cantidad < 0 THEN -d.Cantidad ELSE 0 END) AS UnidadesBaja,
                       SUM(CASE WHEN d.Cantidad > 0 THEN d.Cantidad ELSE 0 END)  AS UnidadesAlta,
                       SUM(CASE WHEN d.Estado = 'ERROR' AND d.Ejecutada = 0 THEN 1 ELSE 0 END) AS FilasError,
                       SUM(CASE WHEN d.Ejecutada = 1 THEN 1 ELSE 0 END)            AS FilasEjecutadas
                FROM EB_AjusteStockLote l WITH (NOLOCK)
                LEFT JOIN EB_AjusteStockDet d WITH (NOLOCK) ON d.IdLote = l.IdLote"
             . (empty($where) ? "" : " WHERE " . implode(' AND ', $where)) . "
                GROUP BY l.IdLote, l.Usuario, l.Leyenda, l.Estado, l.FechaAlta, l.FechaEjecucion, l.NroComprobantes
                ORDER BY l.FechaAlta DESC";

        $stmt = sqlsrv_query($conn, $sql, $params);
        $lotes = [];
        if ($stmt) {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $lotes[] = [
                    'id'              => strtoupper((string)$r['IdLote']),
                    'usuario'         => trim($r['Usuario'] ?? ''),
                    'leyenda'         => trim($r['Leyenda'] ?? ''),
                    'estado'          => trim($r['Estado'] ?? ''),
                    'fecha_alta'      => self::fecha($r['FechaAlta']),
                    'fecha_ejecucion' => self::fecha($r['FechaEjecucion']),
                    'comprobantes'    => trim($r['NroComprobantes'] ?? ''),
                    'filas'           => (int)$r['Filas'],
                    'unidades_baja'   => (int)$r['UnidadesBaja'],
                    'unidades_alta'   => (int)$r['UnidadesAlta'],
                    'filas_error'     => (int)$r['FilasError'],
                    'filas_ejecutadas'=> (int)$r['FilasEjecutadas'],
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        return $lotes;
    }

    private static function fecha($valor) {
        if ($valor instanceof DateTime) return $valor->format('Y-m-d H:i');
        return $valor ? (string)$valor : null;
    }

    private static function num($valor) {
        return (float)$valor == (int)$valor ? (string)(int)$valor : number_format((float)$valor, 2, ',', '.');
    }
}
