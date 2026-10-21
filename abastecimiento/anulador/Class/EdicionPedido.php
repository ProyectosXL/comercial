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

    // Artículos y kits que se pueden agregar al pedido (precio de su lista y disponible en su depósito)
    public function buscarArticulos($pais, $talon_ped, $nro_pedido, $texto)
    {
        $params = array(
            array(intval($talon_ped), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT),
            array(FormatoPedido::nroPedido($nro_pedido), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(14)),
            array(mb_substr($texto, 0, 50), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR(50)),
        );
        $sets = $this->ejecutar($pais, '{CALL dbo.RO_Pedido_Buscar_Articulos(?, ?, ?)}', $params);

        // Simples y kits pueden venir en uno o más result sets: se unifican
        $articulos = array();
        foreach ($sets as $rows) {
            foreach ($rows as $row) {
                $articulos[] = $row;
            }
        }
        return $articulos;
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

    // Fila del resultado de validación del lote
    private function filaLote($talon, $nro, $art, $descripcio, $cantActual, $cantNueva, $accion, $valido, $mensaje)
    {
        return array(
            'talon_ped' => $talon,
            'nro_pedido' => $nro,
            'cod_articu' => $art,
            'descripcio' => $descripcio,
            'cant_actual' => $cantActual,
            'cant_nueva' => $cantNueva,
            'accion' => $accion,
            'valido' => $valido,
            'mensaje' => $mensaje
        );
    }

    // Valida un lote de ediciones proveniente de Excel agrupado por pedido.
    // NUEVA_CANTIDAD es la cantidad total final: 0 quita, menor reduce, mayor aumenta y,
    // si el artículo no está en el pedido, se agrega. Cada pedido sin errores se simula con @TEST = 1.
    public function validarLoteEdicion($pais, $filasExcel)
    {
        $pedidosAgrupados = array();
        foreach ($filasExcel as $idx => $f) {
            $talon = isset($f['TALON_PED']) ? trim(strval($f['TALON_PED'])) : '';
            $nro = isset($f['NRO_PEDIDO']) ? trim(strval($f['NRO_PEDIDO'])) : '';
            // El código se normaliza (mayúsculas, sin espacios) antes de compararlo con el pedido y de enviarlo al SP
            $art = isset($f['COD_ARTICU']) ? strtoupper(trim(strval($f['COD_ARTICU']))) : '';
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
        $pedidosResumen = array();
        $pedidosValidos = 0;
        $pedidosInvalidos = 0;
        $filasValidas = 0;
        $filasInvalidas = 0;

        foreach ($pedidosAgrupados as $key => $grupo) {
            $talon = $grupo['talon_ped'];
            $nro = $grupo['nro_pedido'];
            $filasGrupo = $grupo['filas'];
            $filasGrupoResultado = array();
            $resumen = array(
                'talon_ped' => $talon,
                'nro_pedido' => $nro,
                'valido' => false,
                'mensaje' => '',
                'total_ant' => null,
                'total_nuevo' => null,
                'controla_credito' => 0,
                'consumo_credito' => null,
                'credito_antes' => null,
                'credito_despues' => null
            );

            if ($talon === '' || !ctype_digit($talon) || $nro === '') {
                foreach ($filasGrupo as $fg) {
                    $filasGrupoResultado[] = $this->filaLote($talon, $nro, $fg['cod_articu'], '---', 0, $fg['nueva_cantidad'], 'ERROR', false, 'Talón o Nro. Pedido inválido');
                }
                $resumen['mensaje'] = 'Talón o Nro. Pedido inválido';
            } else {
                try {
                    $consulta = $this->consultar($pais, intval($talon), $nro);
                    $pedido = $consulta['pedido'];
                    $renglones = $consulta['renglones'];
                    $resumen['total_ant'] = $pedido['TOTAL_PEDI'];

                    if (intval($pedido['EDITABLE']) !== 1) {
                        $motivo = !empty($pedido['MOTIVO_NO_EDITABLE']) ? $pedido['MOTIVO_NO_EDITABLE'] : 'El pedido no es editable';
                        foreach ($filasGrupo as $fg) {
                            $filasGrupoResultado[] = $this->filaLote($talon, $nro, $fg['cod_articu'], '---', 0, $fg['nueva_cantidad'], 'ERROR', false, $motivo);
                        }
                        $resumen['mensaje'] = $motivo;
                    } else {
                        $this->validarFilasPedido($pais, $talon, $nro, $pedido, $renglones, $filasGrupo, $filasGrupoResultado, $resumen, $pedidosProcesados);
                    }
                } catch (EdicionPedidoException $e) {
                    $filasGrupoResultado = array();
                    foreach ($filasGrupo as $fg) {
                        $filasGrupoResultado[] = $this->filaLote($talon, $nro, $fg['cod_articu'], '---', 0, $fg['nueva_cantidad'], 'ERROR', false, $e->getMessage());
                    }
                    $resumen['mensaje'] = $e->getMessage();
                } catch (Throwable $e) {
                    $filasGrupoResultado = array();
                    foreach ($filasGrupo as $fg) {
                        $filasGrupoResultado[] = $this->filaLote($talon, $nro, $fg['cod_articu'], '---', 0, $fg['nueva_cantidad'], 'ERROR', false, 'Error al consultar pedido: ' . $e->getMessage());
                    }
                    $resumen['mensaje'] = 'Error al consultar pedido';
                }
            }

            if ($resumen['valido']) {
                $pedidosValidos++;
                $filasValidas += count($filasGrupoResultado);
            } else {
                $pedidosInvalidos++;
                $filasInvalidas += count($filasGrupoResultado);
            }
            foreach ($filasGrupoResultado as $fgr) {
                $filasResultado[] = $fgr;
            }
            $pedidosResumen[] = $resumen;
        }

        return array(
            'filas_data' => $filasResultado,
            'pedidos_validos' => $pedidosProcesados,
            'pedidos_resumen' => $pedidosResumen,
            'cant_pedidos_validos' => $pedidosValidos,
            'cant_pedidos_invalidos' => $pedidosInvalidos,
            'cant_filas_validas' => $filasValidas,
            'cant_filas_invalidas' => $filasInvalidas
        );
    }

    // Arma los cambios de un pedido editable y, si no hay errores de formato, los simula con el SP
    private function validarFilasPedido($pais, $talon, $nro, $pedido, $renglones, $filasGrupo, &$filasGrupoResultado, &$resumen, &$pedidosProcesados)
    {
        // Renglones principales y componentes de kit por COD_ARTICU (insensible a mayúsculas)
        $mapPrincipales = array();
        $mapComponentes = array();
        foreach ($renglones as $r) {
            $cod = strtoupper(trim($r['COD_ARTICU']));
            if (intval($r['RENGL_PADR']) === 0) {
                $mapPrincipales[$cod] = $r;
            } else {
                $mapComponentes[$cod] = $r;
            }
        }

        $cambiosPedido = array();
        $pedidoTieneError = false;
        $eliminados = 0;
        $agregados = 0;

        foreach ($filasGrupo as $fg) {
            $art = $fg['cod_articu'];
            $cantStr = $fg['nueva_cantidad'];
            $renglon = isset($mapPrincipales[$art]) ? $mapPrincipales[$art] : null;
            $descripcio = $renglon !== null ? $renglon['DESCRIPCIO'] : '---';
            $cantActual = $renglon !== null ? floatval($renglon['CANT_PEDID']) : 0;

            if ($art === '') {
                $filasGrupoResultado[] = $this->filaLote($talon, $nro, $art, '---', 0, $cantStr, 'ERROR', false, 'Código de artículo vacío');
                $pedidoTieneError = true;
                continue;
            }

            if (!ctype_digit(strval($cantStr))) {
                $filasGrupoResultado[] = $this->filaLote($talon, $nro, $art, $descripcio, $cantActual, $cantStr, 'ERROR', false, 'La nueva cantidad debe ser un número entero >= 0');
                $pedidoTieneError = true;
                continue;
            }
            $cantNuevaInt = intval($cantStr);

            if ($renglon !== null) {
                if (intval($renglon['EDITABLE']) !== 1) {
                    $motivo = !empty($renglon['MOTIVO_NO_EDITABLE']) ? $renglon['MOTIVO_NO_EDITABLE'] : 'Renglón no editable (remito o componente)';
                    $filasGrupoResultado[] = $this->filaLote($talon, $nro, $renglon['COD_ARTICU'], $descripcio, $cantActual, $cantNuevaInt, 'ERROR', false, $motivo);
                    $pedidoTieneError = true;
                    continue;
                }
                if ($cantNuevaInt == $cantActual) {
                    $filasGrupoResultado[] = $this->filaLote($talon, $nro, $renglon['COD_ARTICU'], $descripcio, $cantActual, $cantNuevaInt, 'ERROR', false, 'Sin cambios');
                    $pedidoTieneError = true;
                    continue;
                }

                if ($cantNuevaInt === 0) {
                    $accion = 'ELIMINAR';
                    $mensaje = 'Quitar artículo';
                    $eliminados++;
                } elseif ($cantNuevaInt < $cantActual) {
                    $accion = 'MODIFICAR';
                    $mensaje = "Reducir a $cantNuevaInt";
                } else {
                    $accion = 'AUMENTAR';
                    $mensaje = "Aumentar a $cantNuevaInt";
                }

                $cambiosPedido[] = array(
                    'n_renglon' => intval($renglon['N_RENGLON']),
                    'cod_articu' => $renglon['COD_ARTICU'],
                    'cantidad' => $cantNuevaInt
                );
                $filasGrupoResultado[] = $this->filaLote($talon, $nro, $renglon['COD_ARTICU'], $descripcio, $cantActual, $cantNuevaInt, $accion, true, $mensaje);
                continue;
            }

            if (isset($mapComponentes[$art])) {
                $comp = $mapComponentes[$art];
                $filasGrupoResultado[] = $this->filaLote($talon, $nro, $comp['COD_ARTICU'], $comp['DESCRIPCIO'], floatval($comp['CANT_PEDID']), $cantNuevaInt, 'ERROR', false, 'El artículo está en el pedido como componente de un kit');
                $pedidoTieneError = true;
                continue;
            }

            if ($cantNuevaInt === 0) {
                $filasGrupoResultado[] = $this->filaLote($talon, $nro, $art, '---', 0, $cantNuevaInt, 'ERROR', false, 'El artículo no está en el pedido');
                $pedidoTieneError = true;
                continue;
            }

            $agregados++;
            $cambiosPedido[] = array(
                'n_renglon' => null,
                'cod_articu' => $art,
                'cantidad' => $cantNuevaInt
            );
            $filasGrupoResultado[] = $this->filaLote($talon, $nro, $art, '---', 0, $cantNuevaInt, 'AGREGAR', true, "Agregar $cantNuevaInt");
        }

        if ($pedidoTieneError || count($cambiosPedido) === 0) {
            $resumen['mensaje'] = 'El pedido tiene filas con error';
            $this->invalidarFilas($filasGrupoResultado, null);
            return;
        }

        // Tiene que quedar al menos un renglón principal: existentes − eliminados + nuevos ≥ 1
        if (count($mapPrincipales) - $eliminados + $agregados < 1) {
            $mensaje = 'No se pueden quitar todos los renglones del pedido (use Anular Pedido)';
            $resumen['mensaje'] = $mensaje;
            $this->invalidarFilas($filasGrupoResultado, $mensaje);
            return;
        }

        // Simulación del pedido completo: stock, crédito, precios y remitos los valida el SP
        try {
            $cambiosJson = json_encode($cambiosPedido, JSON_UNESCAPED_UNICODE);
            $sim = $this->editar($pais, intval($talon), $nro, $cambiosJson, null, true);
        } catch (EdicionPedidoException $e) {
            $resumen['mensaje'] = $e->getMessage();
            $this->invalidarFilas($filasGrupoResultado, $e->getMessage());
            return;
        } catch (Throwable $e) {
            $mensaje = 'Error al simular la edición: ' . $e->getMessage();
            $resumen['mensaje'] = $mensaje;
            $this->invalidarFilas($filasGrupoResultado, $mensaje);
            return;
        }

        // Descripción de los artículos agregados según la simulación
        $descNuevos = array();
        foreach ($sim['renglones'] as $sr) {
            if (intval(isset($sr['RENGL_PADR']) ? $sr['RENGL_PADR'] : 0) === 0 && !empty($sr['DESCRIPCIO'])) {
                $descNuevos[strtoupper(trim($sr['COD_ARTICU']))] = $sr['DESCRIPCIO'];
            }
        }
        foreach ($filasGrupoResultado as &$fgr) {
            $cod = strtoupper($fgr['cod_articu']);
            if ($fgr['accion'] === 'AGREGAR' && isset($descNuevos[$cod])) {
                $fgr['descripcio'] = $descNuevos[$cod];
            }
        }
        unset($fgr);

        $simResumen = $sim['resumen'];
        $controla = isset($simResumen['CONTROLA_CREDITO']) ? intval($simResumen['CONTROLA_CREDITO']) : 0;
        $resumen['valido'] = true;
        $resumen['total_ant'] = $simResumen['TOTAL_PEDI_ANT'];
        $resumen['total_nuevo'] = $simResumen['TOTAL_PEDI_NUEVO'];
        $resumen['controla_credito'] = $controla;
        if ($controla === 1) {
            $resumen['consumo_credito'] = $simResumen['CONSUMO_CREDITO'];
            $resumen['credito_antes'] = $simResumen['CREDITO_DISPONIBLE_ANTES'];
            $resumen['credito_despues'] = $simResumen['CREDITO_DISPONIBLE_DESPUES'];
        }

        $pedidosProcesados[] = array(
            'talon_ped' => intval($talon),
            'nro_pedido' => $nro,
            'cod_client' => $pedido['COD_CLIENT'],
            'cambios' => $cambiosPedido,
            'valido' => true
        );
    }

    // Marca todas las filas del pedido como inválidas. Con $mensaje reemplaza el motivo de las filas
    // que eran válidas; sin $mensaje (otra fila del pedido tiene error) les agrega la aclaración
    private function invalidarFilas(&$filas, $mensaje)
    {
        foreach ($filas as &$f) {
            if ($f['valido']) {
                $f['mensaje'] = ($mensaje !== null) ? $mensaje : $f['mensaje'] . ' (no se aplica: el pedido tiene filas con error)';
            }
            $f['valido'] = false;
        }
        unset($f);
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
