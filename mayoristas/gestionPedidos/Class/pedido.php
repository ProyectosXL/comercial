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
        
        // Establecer un tiempo de espera de 2 minutos como máximo
        sqlsrv_configure("QueryTimeout", 120);

        $stmt = sqlsrv_query($cid_central, $sqlEnviado);
        
        if($stmt === false){
            http_response_code(500);
            die(json_encode(['error' => 'Error en la consulta SQL', 'details' => sqlsrv_errors(), 'query' => $sqlEnviado]));
        }

        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Limpieza de caracteres para asegurar un JSON válido y sin errores
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
        // Corregido: Usamos LEFT JOIN para asegurar que siempre traiga datos, aunque B sea NULL.
        // Y usamos COALESCE para asegurar que IMPRESO siempre sea una cadena vacía si no existe.
        $sql = "SELECT 
                    A.COD_VENDED, A.COD_CLIENT, A.RAZON_SOCI, A.LOCALIDAD, 
                    SUM(A.CANT_PEDIDO) AS UNID_PEDIDO, 
                    SUM(A.CANT_PENDIENTE) AS UNID_PENDIENTE, 
                    COUNT(A.NRO_PEDIDO) AS CANT_PEDIDOS, 
                    SUM(A.IMP_PENDIENTE) AS IMPORTE,
                    COALESCE(B.IMPRESO, 'NO') AS IMPRESO -- Aseguramos que la columna IMPRESO exista
                FROM RO_PEDIDOS_PENDIENTE_MAYORISTAS A
                LEFT JOIN SJ_PICKING_ENCABEZADO B 
                    ON LTRIM(RTRIM(A.NRO_PEDIDO)) = LTRIM(RTRIM(B.NRO_PEDIDO)) 
                    AND (A.TALON_PED = B.TALON_PED OR TRY_CAST(A.TALON_PED AS INT) = TRY_CAST(B.TALON_PED AS INT))
                WHERE A.VENDEDOR LIKE '$vendedor' AND B.ESTADO = 'ABIERTO'
                GROUP BY A.COD_VENDED, A.COD_CLIENT, A.LOCALIDAD, A.RAZON_SOCI, B.IMPRESO
                ORDER BY A.RAZON_SOCI";

        $rows = $this->retornarArray($sql);
        return json_encode($rows);
    }

public function traerPedidosCliente($cliente)
    {
        // 1. Limpiamos el cliente aquí también, por si acaso.
        $cliente_limpio = trim($cliente);

        // 2. Usamos la sintaxis simple en el JOIN, asumiendo que el cliente es lo más importante
        // y que el NRO_PEDIDO en ASIGNADOS está limpio, o si es sucio, que coincide exactamente.
        $sql = "
        WITH ClientePedidosPendientes AS (
            -- Filtramos por el cliente, usando LTRIM/RTRIM aquí es MUY RÁPIDO
            SELECT *
            FROM RO_PEDIDOS_PENDIENTE_MAYORISTAS
            WHERE LTRIM(RTRIM(COD_CLIENT)) = LTRIM(RTRIM('$cliente_limpio'))
        )
        SELECT
            A.FECHA, A.ESTADO, A.HORA_INGRESO, A.COD_CLIENT, A.RAZON_SOCI, A.LOCALIDAD, A.TALON_PED, A.NRO_PEDIDO,
            A.CANT_PEDIDO, A.CANT_PENDIENTE, A.IMP_PENDIENTE, A.COD_VENDED, A.VENDEDOR,
            
            -- COALESCE: Trae el dato de ASIGNADOS si existe, si no, usa el vacío
            COALESCE(ASIG.TIPO_COMP, '') AS TIPO_COMP,
            COALESCE(ASIG.EMBALAJE, '') AS EMBALAJE,
            COALESCE(ASIG.DESPACHO, '') AS DESPACHO,
            COALESCE(ASIG.ARREGLO, NULL) AS ARREGLO, -- Arreglo debe ser NULL para el else del PHP
            COALESCE(ASIG.PRIORIDAD, '') AS PRIORIDAD,
            CAST(COALESCE(ASIG.FECHA_DESPACHO, '1900-01-01') AS DATE) AS FECHA_DESPACHO
        FROM
            ClientePedidosPendientes A
        
        -- Unir con encabezado (B) para verificar el estado ABIERTO
        INNER JOIN SJ_PICKING_ENCABEZADO B
            ON A.NRO_PEDIDO = B.NRO_PEDIDO 
            AND A.TALON_PED = B.TALON_PED
        
        -- LEFT JOIN: Busca los datos guardados (ASIG)
        LEFT JOIN RO_PEDIDOS_MAYORISTA_ASIGNADOS ASIG
            -- Usamos LTRIM(RTRIM) solo en el JOIN para forzar la coincidencia de pedidos limpios/sucios
            ON LTRIM(RTRIM(A.NRO_PEDIDO)) = LTRIM(RTRIM(ASIG.NRO_PEDIDO))
            AND (A.TALON_PED = ASIG.TALON_PED)
            -- Nota: Quitamos el TRY_CAST para evitar que la consulta se vuelva lenta de nuevo
            
        WHERE
            B.ESTADO = 'ABIERTO'
        ORDER BY
            A.FECHA, A.HORA_INGRESO;
        ";

        $rows = $this->retornarArray($sql);
        return json_encode($rows);
    }

    public function traerPedidosAsignados($cliente, $desde, $hasta)
    {
        $sql = "SELECT FECHA_DESPACHO, COD_CLIENT, RAZON_SOCI, COUNT(NRO_PEDIDO) CANT_PEDIDOS, SUM(CANT_PENDIENTE) UNID_PENDIENTE, SUM(IMP_PENDIENTE) IMP_PENDIENTE FROM RO_PEDIDOS_MAYORISTA_ASIGNADOS
                WHERE LTRIM(RTRIM(COD_CLIENT)) = LTRIM(RTRIM('$cliente')) 
                AND FECHA_DESPACHO BETWEEN '$desde' AND '$hasta'
                GROUP BY FECHA_DESPACHO, COD_CLIENT, RAZON_SOCI";

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