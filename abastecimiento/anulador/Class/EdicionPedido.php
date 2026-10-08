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

    // Historial de ediciones (RO_LOG_EDICION_PEDIDO) agrupado por operación con datos de cliente.
    // $filtros: desde y hasta (Y-m-d, obligatorios), usuario, talon_ped, nro_pedido, cliente (opcionales)
    public function historial($pais, $filtros)
    {
        $where = array('L.FECHA >= ?', 'L.FECHA < DATEADD(DAY, 1, ?)');
        $params = array(
            array($filtros['desde'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
            array($filtros['hasta'], SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
        );

        if (!empty($filtros['usuario'])) {
            $where[] = 'L.USUARIO LIKE ?';
            $params[] = array('%' . $filtros['usuario'] . '%', SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR(52));
        }
        if (!empty($filtros['talon_ped'])) {
            $where[] = 'L.TALON_PED = ?';
            $params[] = array(intval($filtros['talon_ped']), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT);
        }
        if (!empty($filtros['nro_pedido'])) {
            $where[] = 'L.NRO_PEDIDO = ?';
            $params[] = array(FormatoPedido::nroPedido($filtros['nro_pedido']), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(14));
        }
        if (!empty($filtros['cliente'])) {
            $where[] = '(G.COD_CLIENT LIKE ? OR C.RAZON_SOCI LIKE ?)';
            $clienteFiltro = '%' . $filtros['cliente'] . '%';
            $params[] = array($clienteFiltro, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR(100));
            $params[] = array($clienteFiltro, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR(100));
        }

        // Se limitan las operaciones (no las filas) para no cortar el detalle de una edición
        $sql = "SELECT L.OPERACION, L.FECHA, L.USUARIO, L.TALON_PED, L.NRO_PEDIDO, 
                       G.COD_CLIENT, C.RAZON_SOCI,
                       L.N_RENGLON, L.RENGL_PADR,
                       L.COD_ARTICU, A.DESCRIPCIO, L.COD_DEPOSI, L.CANT_PEDID_ANT, L.CANT_PEDID_NUEVA,
                       L.DELTA_COMP, L.COMP_ANTES, L.COMP_DESPUES, L.TOTAL_PEDI_ANT, L.TOTAL_PEDI_NUEVO
                FROM RO_LOG_EDICION_PEDIDO L
                LEFT JOIN GVA21 G ON G.TALON_PED = L.TALON_PED AND G.NRO_PEDIDO = L.NRO_PEDIDO COLLATE DATABASE_DEFAULT
                LEFT JOIN GVA14 C ON C.COD_CLIENT = G.COD_CLIENT COLLATE DATABASE_DEFAULT
                LEFT JOIN STA11 A ON A.COD_ARTICU = L.COD_ARTICU COLLATE DATABASE_DEFAULT
                WHERE L.OPERACION IN (
                    SELECT TOP (" . self::HISTORIAL_MAX_OPERACIONES . ") L2.OPERACION
                    FROM RO_LOG_EDICION_PEDIDO L2
                    LEFT JOIN GVA21 G2 ON G2.TALON_PED = L2.TALON_PED AND G2.NRO_PEDIDO = L2.NRO_PEDIDO COLLATE DATABASE_DEFAULT
                    LEFT JOIN GVA14 C2 ON C2.COD_CLIENT = G2.COD_CLIENT COLLATE DATABASE_DEFAULT
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY L2.OPERACION
                    ORDER BY MAX(L2.FECHA) DESC
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
                    'cod_client' => isset($f['COD_CLIENT']) ? $f['COD_CLIENT'] : '',
                    'razon_soci' => isset($f['RAZON_SOCI']) ? $f['RAZON_SOCI'] : '',
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

    // Valida un lote de ediciones proveniente de Excel agrupado por pedido
    public function validarLoteEdicion($pais, $filasExcel)
    {
        $pedidosAgrupados = array();
        foreach ($filasExcel as $idx => $f) {
            $talon = isset($f['TALON_PED']) ? trim(strval($f['TALON_PED'])) : '';
            $nro = isset($f['NRO_PEDIDO']) ? trim(strval($f['NRO_PEDIDO'])) : '';
            $art = isset($f['COD_ARTICU']) ? trim(strval($f['COD_ARTICU'])) : '';
            $cantNueva = isset($f['NUEVA_CANTIDAD']) ? trim(strval($f['NUEVA_CANTIDAD'])) : '';

            $key = "{$talon}|{$nro}";
            if (!isset($pedidosAgrupados[$key])) {
                $pedidosAgrupados[$key] = array(
                    'talon_ped' => $talon,
                    'nro_pedido' => $nro,
                    'filas' => array()
                );
            }
            $pedidosAgrupados[$key]['filas'][] = array(
                'idx' => $idx,
                'cod_articu' => $art,
                'nueva_cantidad' => $cantNueva
            );
        }

        $filasResultado = array();
        $pedidosProcesados = array();
        $pedidosValidos = 0;
        $pedidosInvalidos = 0;
        $filasValidas = 0;
        $filasInvalidas = 0;

        foreach ($pedidosAgrupados as $key => $grupo) {
            $talon = $grupo['talon_ped'];
            $nro = $grupo['nro_pedido'];
            $filasGrupo = $grupo['filas'];

            if ($talon === '' || !ctype_digit($talon) || $nro === '') {
                foreach ($filasGrupo as $fg) {
                    $filasResultado[] = array(
                        'talon_ped' => $talon,
                        'nro_pedido' => $nro,
                        'cod_articu' => $fg['cod_articu'],
                        'descripcio' => '---',
                        'cant_actual' => 0,
                        'cant_nueva' => $fg['nueva_cantidad'],
                        'accion' => 'ERROR',
                        'valido' => false,
                        'mensaje' => 'Talón o Nro. Pedido inválido'
                    );
                    $filasInvalidas++;
                }
                $pedidosInvalidos++;
                continue;
            }

            try {
                $consulta = $this->consultar($pais, intval($talon), $nro);
                $pedido = $consulta['pedido'];
                $renglones = $consulta['renglones'];

                if (intval($pedido['EDITABLE']) !== 1) {
                    $motivo = !empty($pedido['MOTIVO_NO_EDITABLE']) ? $pedido['MOTIVO_NO_EDITABLE'] : 'El pedido no es editable';
                    foreach ($filasGrupo as $fg) {
                        $filasResultado[] = array(
                            'talon_ped' => $talon,
                            'nro_pedido' => $nro,
                            'cod_articu' => $fg['cod_articu'],
                            'descripcio' => '---',
                            'cant_actual' => 0,
                            'cant_nueva' => $fg['nueva_cantidad'],
                            'accion' => 'ERROR',
                            'valido' => false,
                            'mensaje' => $motivo
                        );
                        $filasInvalidas++;
                    }
                    $pedidosInvalidos++;
                    continue;
                }

                $cambiosPedido = array();
                $pedidoTieneError = false;
                $filasGrupoResultado = array();

                // Mapear renglones principales por COD_ARTICU (insensible a mayúsculas)
                $mapRenglones = array();
                $principalesCount = 0;
                foreach ($renglones as $r) {
                    if (intval($r['RENGL_PADR']) === 0) {
                        $principalesCount++;
                        $mapRenglones[strtoupper(trim($r['COD_ARTICU']))] = $r;
                    }
                }

                $eliminadosCount = 0;

                foreach ($filasGrupo as $fg) {
                    $art = $fg['cod_articu'];
                    $artUpper = strtoupper($art);
                    $cantStr = $fg['nueva_cantidad'];

                    if ($art === '') {
                        $filasGrupoResultado[] = array(
                            'talon_ped' => $talon,
                            'nro_pedido' => $nro,
                            'cod_articu' => $art,
                            'descripcio' => '---',
                            'cant_actual' => 0,
                            'cant_nueva' => $cantStr,
                            'accion' => 'ERROR',
                            'valido' => false,
                            'mensaje' => 'Código de artículo vacío'
                        );
                        $pedidoTieneError = true;
                        continue;
                    }

                    if (!isset($mapRenglones[$artUpper])) {
                        $filasGrupoResultado[] = array(
                            'talon_ped' => $talon,
                            'nro_pedido' => $nro,
                            'cod_articu' => $art,
                            'descripcio' => '---',
                            'cant_actual' => 0,
                            'cant_nueva' => $cantStr,
                            'accion' => 'ERROR',
                            'valido' => false,
                            'mensaje' => 'El artículo no existe como renglón principal en el pedido'
                        );
                        $pedidoTieneError = true;
                        continue;
                    }

                    $renglon = $mapRenglones[$artUpper];
                    $cantActual = floatval($renglon['CANT_PEDID']);

                    if (intval($renglon['EDITABLE']) !== 1) {
                        $filasGrupoResultado[] = array(
                            'talon_ped' => $talon,
                            'nro_pedido' => $nro,
                            'cod_articu' => $art,
                            'descripcio' => $renglon['DESCRIPCIO'],
                            'cant_actual' => $cantActual,
                            'cant_nueva' => $cantStr,
                            'accion' => 'ERROR',
                            'valido' => false,
                            'mensaje' => !empty($renglon['MOTIVO_NO_EDITABLE']) ? $renglon['MOTIVO_NO_EDITABLE'] : 'Renglón no editable (remito o componente)'
                        );
                        $pedidoTieneError = true;
                        continue;
                    }

                    if (!is_numeric($cantStr) || !ctype_digit(strval($cantStr))) {
                        $filasGrupoResultado[] = array(
                            'talon_ped' => $talon,
                            'nro_pedido' => $nro,
                            'cod_articu' => $art,
                            'descripcio' => $renglon['DESCRIPCIO'],
                            'cant_actual' => $cantActual,
                            'cant_nueva' => $cantStr,
                            'accion' => 'ERROR',
                            'valido' => false,
                            'mensaje' => 'La nueva cantidad debe ser un número entero >= 0'
                        );
                        $pedidoTieneError = true;
                        continue;
                    }

                    $cantNuevaInt = intval($cantStr);
                    if ($cantNuevaInt < 0) {
                        $filasGrupoResultado[] = array(
                            'talon_ped' => $talon,
                            'nro_pedido' => $nro,
                            'cod_articu' => $art,
                            'descripcio' => $renglon['DESCRIPCIO'],
                            'cant_actual' => $cantActual,
                            'cant_nueva' => $cantNuevaInt,
                            'accion' => 'ERROR',
                            'valido' => false,
                            'mensaje' => 'La nueva cantidad no puede ser negativa'
                        );
                        $pedidoTieneError = true;
                        continue;
                    }

                    if ($cantNuevaInt >= $cantActual) {
                        $filasGrupoResultado[] = array(
                            'talon_ped' => $talon,
                            'nro_pedido' => $nro,
                            'cod_articu' => $art,
                            'descripcio' => $renglon['DESCRIPCIO'],
                            'cant_actual' => $cantActual,
                            'cant_nueva' => $cantNuevaInt,
                            'accion' => 'ERROR',
                            'valido' => false,
                            'mensaje' => "La nueva cantidad ($cantNuevaInt) debe ser menor a la actual ($cantActual)"
                        );
                        $pedidoTieneError = true;
                        continue;
                    }

                    $accion = ($cantNuevaInt === 0) ? 'ELIMINAR' : 'MODIFICAR';
                    if ($accion === 'ELIMINAR') {
                        $eliminadosCount++;
                    }

                    $cambiosPedido[] = array(
                        'n_renglon' => intval($renglon['N_RENGLON']),
                        'cod_articu' => $renglon['COD_ARTICU'],
                        'cantidad' => $cantNuevaInt
                    );

                    $filasGrupoResultado[] = array(
                        'talon_ped' => $talon,
                        'nro_pedido' => $nro,
                        'cod_articu' => $renglon['COD_ARTICU'],
                        'descripcio' => $renglon['DESCRIPCIO'],
                        'cant_actual' => $cantActual,
                        'cant_nueva' => $cantNuevaInt,
                        'accion' => $accion,
                        'valido' => true,
                        'mensaje' => ($accion === 'ELIMINAR') ? 'Quitar artículo' : "Reducir a $cantNuevaInt"
                    );
                }

                // Verificar que no se eliminen todos los renglones principales
                if (!$pedidoTieneError && $principalesCount > 0 && $eliminadosCount >= $principalesCount) {
                    $pedidoTieneError = true;
                    foreach ($filasGrupoResultado as &$fgr) {
                        $fgr['valido'] = false;
                        $fgr['mensaje'] = 'No se pueden quitar todos los renglones del pedido (use Anular Pedido)';
                    }
                    unset($fgr);
                }

                if (!$pedidoTieneError && count($cambiosPedido) > 0) {
                    $pedidosValidos++;
                    $pedidosProcesados[] = array(
                        'talon_ped' => intval($talon),
                        'nro_pedido' => $nro,
                        'cod_client' => $pedido['COD_CLIENT'],
                        'cambios' => $cambiosPedido,
                        'valido' => true
                    );
                    foreach ($filasGrupoResultado as $fgr) {
                        $filasResultado[] = $fgr;
                        $filasValidas++;
                    }
                } else {
                    $pedidosInvalidos++;
                    foreach ($filasGrupoResultado as $fgr) {
                        $filasResultado[] = $fgr;
                        $filasInvalidas++;
                    }
                }

            } catch (EdicionPedidoException $e) {
                foreach ($filasGrupo as $fg) {
                    $filasResultado[] = array(
                        'talon_ped' => $talon,
                        'nro_pedido' => $nro,
                        'cod_articu' => $fg['cod_articu'],
                        'descripcio' => '---',
                        'cant_actual' => 0,
                        'cant_nueva' => $fg['nueva_cantidad'],
                        'accion' => 'ERROR',
                        'valido' => false,
                        'mensaje' => $e->getMessage()
                    );
                    $filasInvalidas++;
                }
                $pedidosInvalidos++;
            } catch (Throwable $e) {
                foreach ($filasGrupo as $fg) {
                    $filasResultado[] = array(
                        'talon_ped' => $talon,
                        'nro_pedido' => $nro,
                        'cod_articu' => $fg['cod_articu'],
                        'descripcio' => '---',
                        'cant_actual' => 0,
                        'cant_nueva' => $fg['nueva_cantidad'],
                        'accion' => 'ERROR',
                        'valido' => false,
                        'mensaje' => 'Error al consultar pedido: ' . $e->getMessage()
                    );
                    $filasInvalidas++;
                }
                $pedidosInvalidos++;
            }
        }

        return array(
            'filas_data' => $filasResultado,
            'pedidos_validos' => $pedidosProcesados,
            'cant_pedidos_validos' => $pedidosValidos,
            'cant_pedidos_invalidos' => $pedidosInvalidos,
            'cant_filas_validas' => $filasValidas,
            'cant_filas_invalidas' => $filasInvalidas
        );
    }

    // Aplica el lote de ediciones masivas confirmado
    public function aplicarLoteEdicion($pais, $pedidosValidos, $usuario)
    {
        $exitosos = 0;
        $fallidos = 0;
        $detalles = array();

        foreach ($pedidosValidos as $p) {
            $talon = intval($p['talon_ped']);
            $nro = $p['nro_pedido'];
            $cambios = $p['cambios'];

            try {
                $cambiosJson = json_encode($cambios, JSON_UNESCAPED_UNICODE);
                $res = $this->editar($pais, $talon, $nro, $cambiosJson, $usuario, false);
                $exitosos++;
                $detalles[] = array(
                    'talon_ped' => $talon,
                    'nro_pedido' => $nro,
                    'success' => true,
                    'total_nuevo' => $res['resumen']['TOTAL_PEDI_NUEVO']
                );
            } catch (Throwable $e) {
                $fallidos++;
                $detalles[] = array(
                    'talon_ped' => $talon,
                    'nro_pedido' => $nro,
                    'success' => false,
                    'error' => $e->getMessage()
                );
            }
        }

        return array(
            'total' => count($pedidosValidos),
            'exitosos' => $exitosos,
            'fallidos' => $fallidos,
            'detalles' => $detalles
        );
    }
}
