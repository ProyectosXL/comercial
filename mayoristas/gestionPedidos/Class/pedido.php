<?php

class Pedido
{
    private function retornarArray($sqlEnviado)
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/sistemas/class/conexion.php';
        $cid = new Conexion();
        $cid_central = $cid->conectar('central');
        
        if($cid_central === false){
            http_response_code(500);
            die(json_encode(["error" => "Error de conexión a la Base de Datos."]));
        }
        
        // Aumentamos el timeout para consultas pesadas
        sqlsrv_configure("QueryTimeout", 300); // 5 minutos

        $stmt = sqlsrv_query($cid_central, $sqlEnviado);
        
        if($stmt === false){
            // Log de error silencioso o respuesta json
            // die(print_r(sqlsrv_errors(), true)); 
            return []; // Retornar array vacio si falla
        }

        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($v as $key => $value) {
                if (is_string($value)) {
                    $v[$key] = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                }
            }
            $rows[] = $v;
        }
        return $rows;
    }

    public function traerPedidos($vendedor)
    {
        // CORRECCIÓN 1: Timeout al traer todo.
        // Si el vendedor es '%' (Todos), limitamos a los primeros 2000 registros para no matar el servidor.
        // Si hay un vendedor específico, traemos todo lo suyo.
        
        $topClause = ($vendedor === '%') ? "TOP 2000" : "";

        $sql = "SELECT $topClause
                        A.COD_VENDED,
                            A.COD_CLIENT,
                            A.RAZON_SOCI,
                            A.LOCALIDAD,
                            SUM(A.CANT_PEDIDO)    AS UNID_PEDIDO,
                            SUM(A.CANT_PENDIENTE) AS UNID_PENDIENTE,
                            COUNT(A.NRO_PEDIDO)   AS CANT_PEDIDOS,
                            SUM(A.IMP_PENDIENTE)  AS IMPORTE,
                            COALESCE(B.IMPRESO, 'NO') AS IMPRESO
                        FROM RO_PEDIDOS_PENDIENTE_MAYORISTAS A
                        INNER JOIN SJ_PICKING_ENCABEZADO B 
                            ON LTRIM(RTRIM(A.NRO_PEDIDO)) = LTRIM(RTRIM(B.NRO_PEDIDO))
                            AND LTRIM(RTRIM(A.TALON_PED)) = LTRIM(RTRIM(B.TALON_PED))
                            AND B.ESTADO = 'ABIERTO'                                     
                        WHERE A.VENDEDOR = '$vendedor'                           
                        GROUP BY 
                            A.COD_VENDED,
                            A.COD_CLIENT,
                            A.RAZON_SOCI,
                            A.LOCALIDAD,
                            B.IMPRESO
                        ORDER BY A.RAZON_SOCI";

        $rows = $this->retornarArray($sql);
        return json_encode($rows);
    }

    public function traerPedidosCliente($cliente)
    {
        $cliente_limpio = trim($cliente);
        
        $sql = "
        WITH ClientePedidosPendientes AS (
            SELECT *
            FROM RO_PEDIDOS_PENDIENTE_MAYORISTAS
            WHERE LTRIM(RTRIM(COD_CLIENT)) = LTRIM(RTRIM('$cliente_limpio'))
        )
        SELECT
            A.FECHA, A.ESTADO, A.HORA_INGRESO, A.COD_CLIENT, A.RAZON_SOCI, A.LOCALIDAD, A.TALON_PED, A.NRO_PEDIDO,
            A.CANT_PEDIDO, A.CANT_PENDIENTE, A.IMP_PENDIENTE, A.COD_VENDED, A.VENDEDOR,
            COALESCE(ASIG.TIPO_COMP, '') AS TIPO_COMP,
            COALESCE(ASIG.EMBALAJE, '') AS EMBALAJE,
            COALESCE(ASIG.DESPACHO, '') AS DESPACHO,
            COALESCE(ASIG.ARREGLO, NULL) AS ARREGLO,
            COALESCE(ASIG.PRIORIDAD, '') AS PRIORIDAD,
            CAST(COALESCE(ASIG.FECHA_DESPACHO, '1900-01-01') AS DATE) AS FECHA_DESPACHO
        FROM ClientePedidosPendientes A
        INNER JOIN SJ_PICKING_ENCABEZADO B
            ON A.NRO_PEDIDO = B.NRO_PEDIDO 
            AND A.TALON_PED = B.TALON_PED
        LEFT JOIN RO_PEDIDOS_MAYORISTA_ASIGNADOS ASIG
            ON LTRIM(RTRIM(A.NRO_PEDIDO)) = LTRIM(RTRIM(ASIG.NRO_PEDIDO))
            AND (A.TALON_PED = ASIG.TALON_PED)
        WHERE B.ESTADO = 'ABIERTO'
        ORDER BY A.FECHA, A.HORA_INGRESO;";

        $rows = $this->retornarArray($sql);
        return json_encode($rows);
    }

    public function traerPedidosAsignados($cliente, $desde, $hasta)
    {
        // CORRECCIÓN 3: Si el cliente es '%', no podemos usar '=' en SQL.
        // Construimos la condición dinámicamente.
        
        $condicionCliente = "";
        
        if ($cliente !== '%' && $cliente !== '') {
            $condicionCliente = "AND LTRIM(RTRIM(COD_CLIENT)) = LTRIM(RTRIM('$cliente'))";
        }

        $sql = "SELECT FECHA_DESPACHO, COD_CLIENT, RAZON_SOCI, COUNT(NRO_PEDIDO) CANT_PEDIDOS, SUM(CANT_PENDIENTE) UNID_PENDIENTE, SUM(IMP_PENDIENTE) IMP_PENDIENTE 
                FROM RO_PEDIDOS_MAYORISTA_ASIGNADOS
                WHERE FECHA_DESPACHO BETWEEN '$desde' AND '$hasta'
                $condicionCliente
                GROUP BY FECHA_DESPACHO, COD_CLIENT, RAZON_SOCI
                ORDER BY FECHA_DESPACHO DESC";

        $rows = $this->retornarArray($sql);
        return json_encode($rows);
    }

    public function traerResumenPorPedido($desde, $hasta)
    {
        $sql = "SELECT CAST(A.FECHA AS DATE) FECHA, A.ESTADO, A.HORA_INGRESO, A.COD_CLIENT, A.RAZON_SOCI, LOCALIDAD, A.TALON_PED, A.NRO_PEDIDO, A.CANT_PEDIDO, A.CANT_PENDIENTE, IMP_PENDIENTE, COD_VENDED, VENDEDOR, A.TIPO_COMP, A.EMBALAJE, A.DESPACHO, FECHA_DESPACHO
                FROM RO_PEDIDOS_MAYORISTA_ASIGNADOS A
                INNER JOIN SJ_PICKING_ENCABEZADO B 
                    ON LTRIM(RTRIM(A.NRO_PEDIDO)) = LTRIM(RTRIM(B.NRO_PEDIDO)) 
                    AND (A.TALON_PED = B.TALON_PED OR TRY_CAST(A.TALON_PED AS INT) = TRY_CAST(B.TALON_PED AS INT))
                WHERE B.ESTADO = 'ABIERTO' AND A.FECHA_DESPACHO BETWEEN '$desde' AND '$hasta'
                ORDER BY FECHA, RAZON_SOCI, NRO_PEDIDO";

        $rows = $this->retornarArray($sql);
        return json_encode($rows);
    }
}
?>