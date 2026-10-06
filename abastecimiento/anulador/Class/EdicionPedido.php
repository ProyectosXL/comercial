<?php

require_once __DIR__ . '/FormatoPedido.php';

// Error de negocio informado por los SPs (THROW 51000-51099): el mensaje se muestra tal cual al usuario
class EdicionPedidoException extends Exception
{
}

class EdicionPedido
{
    const ERROR_NEGOCIO_MIN = 51000;
    const ERROR_NEGOCIO_MAX = 51099;

    private function conectar($pais)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        $cid = new Conexion();

        // Conectar al servidor según el país (AR -> central, UY -> uy)
        $connName = ($pais === 'UY') ? 'uy' : 'central';
        $conexion = $cid->conectar($connName);

        if (!$conexion) {
            throw new RuntimeException("No se pudo conectar a la base ($connName)");
        }
        return $conexion;
    }

    // Ejecuta un SP o consulta parametrizada y devuelve todos sus result sets
    private function ejecutar($pais, $sql, $params, $formatoFecha = 'd/m/Y')
    {
        // Los PRINT / mensajes informativos del SP no deben cortar la ejecución
        sqlsrv_configure('WarningsReturnAsErrors', 0);

        $conexion = $this->conectar($pais);
        $stmt = sqlsrv_query($conexion, $sql, $params);
        if ($stmt === false) {
            $this->lanzarError();
        }

        $resultSets = array();
        do {
            // Se omiten los resultados sin columnas (conteos de filas)
            if (sqlsrv_num_fields($stmt) > 0) {
                $rows = array();
                while (($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) !== null) {
                    if ($row === false) {
                        $this->lanzarError();
                    }
                    $rows[] = $this->normalizarFila($row, $formatoFecha);
                }
                $resultSets[] = $rows;
            }

            $siguiente = sqlsrv_next_result($stmt);
            if ($siguiente === false) {
                $this->lanzarError();
            }
        } while ($siguiente === true);

        sqlsrv_free_stmt($stmt);
        return $resultSets;
    }

    private function normalizarFila($row, $formatoFecha)
    {
        foreach ($row as $col => $valor) {
            if ($valor instanceof DateTimeInterface) {
                $row[$col] = $valor->format($formatoFecha);
            } elseif (is_string($valor)) {
                // Los campos CHAR de Tango vienen con espacios a la derecha
                $row[$col] = rtrim($valor);
            }
        }
        return $row;
    }

    private function lanzarError()
    {
        $errores = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        if (is_array($errores)) {
            foreach ($errores as $error) {
                $codigo = intval($error['code']);
                if ($codigo >= self::ERROR_NEGOCIO_MIN && $codigo <= self::ERROR_NEGOCIO_MAX) {
                    // Quitar el prefijo del driver: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]
                    $mensaje = trim(preg_replace('/^(\[[^\]]*\])+\s*/', '', $error['message']));
                    throw new EdicionPedidoException($mensaje, $codigo);
                }
            }
        }
        throw new RuntimeException('Error SQL: ' . print_r($errores, true));
    }

    public function consultar($pais, $talon_ped, $nro_pedido)
    {
        $params = array(
            array(intval($talon_ped), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
            array(FormatoPedido::nroPedido($nro_pedido), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(14)),
        );
        $sets = $this->ejecutar($pais, '{CALL dbo.RO_Pedido_Consultar(?, ?)}', $params);

        if (empty($sets[0])) {
            throw new EdicionPedidoException('El pedido no existe.', 51001);
        }

        return array(
            'pedido' => $sets[0][0],
            'renglones' => isset($sets[1]) ? $sets[1] : array(),
        );
    }

    // $cambiosJson: JSON array ya validado [{"n_renglon":5,"cod_articu":"...","cantidad":0}, ...]
    public function editar($pais, $talon_ped, $nro_pedido, $cambiosJson, $usuario, $test)
    {
        $usuario = ($usuario === null || $usuario === '') ? null : mb_substr($usuario, 0, 50);

        $params = array(
            array(intval($talon_ped), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
            array(FormatoPedido::nroPedido($nro_pedido), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(14)),
            array($cambiosJson, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_NVARCHAR('max')),
            array($usuario, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR(50)),
            array($test ? 1 : 0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT),
        );
        $sets = $this->ejecutar($pais, '{CALL dbo.RO_Pedido_Editar(?, ?, ?, ?, ?)}', $params);

        if (empty($sets[0])) {
            throw new RuntimeException('RO_Pedido_Editar no devolvió el resumen');
        }

        return array(
            'resumen' => $sets[0][0],
            'renglones' => isset($sets[1]) ? $sets[1] : array(),
            'stock' => isset($sets[2]) ? $sets[2] : array(),
        );
    }

    const HISTORIAL_MAX_OPERACIONES = 300;

    // Historial de ediciones (RO_LOG_EDICION_PEDIDO) agrupado por operación.
    // $filtros: desde y hasta (Y-m-d, obligatorios), usuario, talon_ped, nro_pedido (opcionales)
    public function historial($pais, $filtros)
    {
        $where = array('L.FECHA >= ?', 'L.FECHA < DATEADD(DAY, 1, ?)');
        $params = array(
            array($filtros['desde'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
            array($filtros['hasta'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
        );

        if ($filtros['usuario'] !== '') {
            $where[] = 'L.USUARIO LIKE ?';
            $params[] = array('%' . $filtros['usuario'] . '%', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR(52));
        }
        if ($filtros['talon_ped'] !== '') {
            $where[] = 'L.TALON_PED = ?';
            $params[] = array(intval($filtros['talon_ped']), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT);
        }
        if ($filtros['nro_pedido'] !== '') {
            $where[] = 'L.NRO_PEDIDO = ?';
            $params[] = array(FormatoPedido::nroPedido($filtros['nro_pedido']), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(14));
        }

        // Se limitan las operaciones (no las filas) para no cortar el detalle de una edición
        $sql = "SELECT L.OPERACION, L.FECHA, L.USUARIO, L.TALON_PED, L.NRO_PEDIDO, L.N_RENGLON, L.RENGL_PADR,
                       L.COD_ARTICU, A.DESCRIPCIO, L.COD_DEPOSI, L.CANT_PEDID_ANT, L.CANT_PEDID_NUEVA,
                       L.DELTA_COMP, L.COMP_ANTES, L.COMP_DESPUES, L.TOTAL_PEDI_ANT, L.TOTAL_PEDI_NUEVO
                FROM RO_LOG_EDICION_PEDIDO L
                LEFT JOIN STA11 A ON A.COD_ARTICU = L.COD_ARTICU COLLATE DATABASE_DEFAULT
                WHERE L.OPERACION IN (
                    SELECT TOP (" . self::HISTORIAL_MAX_OPERACIONES . ") L.OPERACION
                    FROM RO_LOG_EDICION_PEDIDO L
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY L.OPERACION
                    ORDER BY MAX(L.FECHA) DESC
                )
                ORDER BY L.FECHA DESC, L.OPERACION, L.N_RENGLON";

        $sets = $this->ejecutar($pais, $sql, $params, 'd/m/Y H:i:s');
        $filas = isset($sets[0]) ? $sets[0] : array();

        $operaciones = array();
        foreach ($filas as $f) {
            $id = $f['OPERACION'];
            if (!isset($operaciones[$id])) {
                $operaciones[$id] = array(
                    'operacion' => $id,
                    'fecha' => $f['FECHA'],
                    'usuario' => $f['USUARIO'],
                    'talon_ped' => $f['TALON_PED'],
                    'nro_pedido' => ltrim($f['NRO_PEDIDO']),
                    'total_ant' => $f['TOTAL_PEDI_ANT'],
                    'total_nuevo' => $f['TOTAL_PEDI_NUEVO'],
                    'eliminados' => 0,
                    'modificados' => 0,
                    'detalle' => array(),
                );
            }

            // El conteo es sobre renglones principales; los componentes de kit van solo en el detalle
            if (intval($f['RENGL_PADR']) === 0) {
                if (floatval($f['CANT_PEDID_NUEVA']) == 0) {
                    $operaciones[$id]['eliminados']++;
                } else {
                    $operaciones[$id]['modificados']++;
                }
            }

            $operaciones[$id]['detalle'][] = array(
                'N_RENGLON' => $f['N_RENGLON'],
                'RENGL_PADR' => $f['RENGL_PADR'],
                'COD_ARTICU' => $f['COD_ARTICU'],
                'DESCRIPCIO' => $f['DESCRIPCIO'],
                'COD_DEPOSI' => $f['COD_DEPOSI'],
                'CANT_PEDID_ANT' => $f['CANT_PEDID_ANT'],
                'CANT_NUEVA' => $f['CANT_PEDID_NUEVA'],
                'DELTA_COMP' => $f['DELTA_COMP'],
                'COMP_ANTES' => $f['COMP_ANTES'],
                'COMP_DESPUES' => $f['COMP_DESPUES'],
            );
        }

        return array(
            'operaciones' => array_values($operaciones),
            'truncado' => count($operaciones) >= self::HISTORIAL_MAX_OPERACIONES,
        );
    }
}
