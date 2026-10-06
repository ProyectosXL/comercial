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

    // Ejecuta un SP parametrizado y devuelve todos sus result sets
    private function ejecutarSP($pais, $sql, $params)
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
                    $rows[] = $this->normalizarFila($row);
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

    private function normalizarFila($row)
    {
        foreach ($row as $col => $valor) {
            if ($valor instanceof DateTimeInterface) {
                $row[$col] = $valor->format('d/m/Y');
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
        $sets = $this->ejecutarSP($pais, '{CALL dbo.RO_Pedido_Consultar(?, ?)}', $params);

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
        $sets = $this->ejecutarSP($pais, '{CALL dbo.RO_Pedido_Editar(?, ?, ?, ?, ?)}', $params);

        if (empty($sets[0])) {
            throw new RuntimeException('RO_Pedido_Editar no devolvió el resumen');
        }

        return array(
            'resumen' => $sets[0][0],
            'renglones' => isset($sets[1]) ? $sets[1] : array(),
            'stock' => isset($sets[2]) ? $sets[2] : array(),
        );
    }
}
