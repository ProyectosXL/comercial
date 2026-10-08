<?php

require_once __DIR__ . '/FormatoPedido.php';

class Pedido
{
    const HISTORIAL_MAX_ANULACIONES = 500;

    private function conectar($pais)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        $cid = new Conexion();

        $connName = ($pais === 'UY') ? 'uy' : 'central';
        $conexion = $cid->conectar($connName);

        if (!$conexion) {
            throw new RuntimeException("No se pudo conectar a la base ($connName)");
        }
        return $conexion;
    }

    private function normalizarFila($row, $formatoFecha = 'd/m/Y H:i:s')
    {
        foreach ($row as $col => $valor) {
            if ($valor instanceof DateTimeInterface) {
                $row[$col] = $valor->format($formatoFecha);
            } elseif (is_string($valor)) {
                $row[$col] = rtrim($valor);
            }
        }
        return $row;
    }

    public function validarPedido($pedido, $talon_pedido, $pais)
    {
        $talon = intval($talon_pedido);
        $pedidoFormateado = FormatoPedido::nroPedido($pedido);

        $conexion = $this->conectar($pais);
        $sql = "SELECT G.TALON_PED, G.NRO_PEDIDO, G.COD_CLIENT, C.RAZON_SOCI, G.TOTAL_PEDI, G.ESTADO
                FROM GVA21 G
                LEFT JOIN GVA14 C ON C.COD_CLIENT = G.COD_CLIENT COLLATE DATABASE_DEFAULT
                WHERE G.TALON_PED = ? AND G.NRO_PEDIDO = ? AND G.ESTADO = 2";
        $params = array($talon, $pedidoFormateado);
        $stmt = sqlsrv_query($conexion, $sql, $params);

        if ($stmt === false) {
            return false;
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if ($row) {
            return $this->normalizarFila($row);
        }
        return false;
    }

    public function anularPedido($pedido, $talon_pedido, $pais, $usuario = null, $origen = 'INDIVIDUAL', $operacionId = null)
    {
        $talon = intval($talon_pedido);
        $pedidoFormateado = FormatoPedido::nroPedido($pedido);
        $usuario = ($usuario === null || $usuario === '') ? null : mb_substr($usuario, 0, 50);

        $conexion = $this->conectar($pais);

        // Obtener datos del pedido antes de anular
        $sqlInfo = "SELECT G.COD_CLIENT, C.RAZON_SOCI, G.TOTAL_PEDI 
                    FROM GVA21 G 
                    LEFT JOIN GVA14 C ON C.COD_CLIENT = G.COD_CLIENT COLLATE DATABASE_DEFAULT
                    WHERE G.TALON_PED = ? AND G.NRO_PEDIDO = ?";
        $stmtInfo = sqlsrv_query($conexion, $sqlInfo, array($talon, $pedidoFormateado));
        $codClient = null;
        $razonSoci = null;
        $totalPedi = 0.0;

        if ($stmtInfo && ($rowInfo = sqlsrv_fetch_array($stmtInfo, SQLSRV_FETCH_ASSOC))) {
            $codClient = isset($rowInfo['COD_CLIENT']) ? rtrim($rowInfo['COD_CLIENT']) : null;
            $razonSoci = isset($rowInfo['RAZON_SOCI']) ? rtrim($rowInfo['RAZON_SOCI']) : null;
            $totalPedi = isset($rowInfo['TOTAL_PEDI']) ? floatval($rowInfo['TOTAL_PEDI']) : 0.0;
            sqlsrv_free_stmt($stmtInfo);
        }

        // Ejecutar anulación oficial
        $sqlAnular = "EXEC RO_ANULAR_PEDIDOS ?, ?";
        $stmtAnular = sqlsrv_query($conexion, $sqlAnular, array(strval($talon), $pedidoFormateado));
        if ($stmtAnular === false) {
            return false;
        }
        sqlsrv_free_stmt($stmtAnular);

        // Registrar en tabla de log de anulaciones
        try {
            $sqlLog = "INSERT INTO dbo.RO_LOG_ANULACION_PEDIDO 
                       (OPERACION, FECHA, USUARIO, TALON_PED, NRO_PEDIDO, COD_CLIENT, RAZON_SOCI, TOTAL_PEDI, ORIGEN) 
                       VALUES (ISNULL(?, NEWID()), GETDATE(), ?, ?, ?, ?, ?, ?, ?)";
            $paramsLog = array(
                $operacionId,
                $usuario,
                $talon,
                $pedidoFormateado,
                $codClient,
                $razonSoci,
                $totalPedi,
                $origen
            );
            $stmtLog = sqlsrv_query($conexion, $sqlLog, $paramsLog);
            if ($stmtLog !== false) {
                sqlsrv_free_stmt($stmtLog);
            }
        } catch (Throwable $t) {
            error_log("Error insertando en RO_LOG_ANULACION_PEDIDO: " . $t->getMessage());
        }

        return true;
    }

    public function historialAnulaciones($pais, $filtros)
    {
        $conexion = $this->conectar($pais);

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
            $where[] = '(L.COD_CLIENT LIKE ? OR L.RAZON_SOCI LIKE ?)';
            $clienteFiltro = '%' . $filtros['cliente'] . '%';
            $params[] = array($clienteFiltro, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR(100));
            $params[] = array($clienteFiltro, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_VARCHAR(100));
        }

        $sql = "SELECT TOP (" . self::HISTORIAL_MAX_ANULACIONES . ")
                       L.ID, L.OPERACION, L.FECHA, L.USUARIO, L.TALON_PED, L.NRO_PEDIDO,
                       L.COD_CLIENT, ISNULL(L.RAZON_SOCI, C.RAZON_SOCI) AS RAZON_SOCI,
                       L.TOTAL_PEDI, L.ORIGEN
                FROM RO_LOG_ANULACION_PEDIDO L
                LEFT JOIN GVA14 C ON C.COD_CLIENT = L.COD_CLIENT COLLATE DATABASE_DEFAULT
                WHERE " . implode(' AND ', $where) . "
                ORDER BY L.FECHA DESC, L.ID DESC";

        $stmt = sqlsrv_query($conexion, $sql, $params);
        if ($stmt === false) {
            throw new RuntimeException("Error al consultar historial de anulaciones: " . print_r(sqlsrv_errors(), true));
        }

        $rows = array();
        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $this->normalizarFila($r, 'd/m/Y H:i:s');
        }
        sqlsrv_free_stmt($stmt);

        return array(
            'anulaciones' => $rows,
            'truncado' => count($rows) >= self::HISTORIAL_MAX_ANULACIONES
        );
    }
}
