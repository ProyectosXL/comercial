
<?php

class Pedido
{

    private function retornarArray($sqlEnviado)
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/sistemas/class/conexion.php';

        $cid = new Conexion();
        $cid_central = $cid->conectar('central');
        
        if($cid_central === false){
            die("Error al conectar con la base de datos: ".print_r(sqlsrv_errors(), true));
        }
        
        $sql = $sqlEnviado;

        $stmt = sqlsrv_query($cid_central, $sql);
        
        if($stmt === false){
            die("Error en sqlsrv_query: ".print_r(sqlsrv_errors(), true));
        }

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }


        return $rows;
    }

    public function traerPedidos($vendedor)
    {

        $sql = "SELECT A.COD_VENDED, A.COD_CLIENT, A.RAZON_SOCI, A.LOCALIDAD, SUM(A.CANT_PEDIDO) UNID_PEDIDO, SUM(A.CANT_PENDIENTE) UNID_PENDIENTE, COUNT(A.NRO_PEDIDO) CANT_PEDIDOS, SUM(A.IMP_PENDIENTE) IMPORTE, IMPRESO FROM RO_PEDIDOS_PENDIENTE_MAYORISTAS A
                LEFT JOIN SJ_PICKING_ENCABEZADO B ON A.NRO_PEDIDO = B.NRO_PEDIDO AND A.TALON_PED = B.TALON_PED
                WHERE VENDEDOR LIKE '$vendedor' --AND IMPRESO = 'NO'
                GROUP BY COD_VENDED, A.COD_CLIENT, LOCALIDAD, A.RAZON_SOCI, IMPRESO
                ORDER BY A.RAZON_SOCI";

        $rows = $this->retornarArray($sql);
        
        $myJSON = json_encode($rows);

        return $myJSON;

    }

    public function traerPedidosCliente($cliente)
    {

        $sql = "SELECT FECHA, ESTADO, HORA_INGRESO, COD_CLIENT, RAZON_SOCI, LOCALIDAD, TALON_PED, NRO_PEDIDO, CANT_PEDIDO, CANT_PENDIENTE, IMP_PENDIENTE, COD_VENDED, VENDEDOR, TIPO_COMP, EMBALAJE, DESPACHO, ARREGLO, PRIORIDAD,
                CAST(FECHA_DESPACHO AS DATE) FECHA_DESPACHO FROM
                (
                    SELECT A.FECHA, A.ESTADO, A.HORA_INGRESO, A.COD_CLIENT, A.RAZON_SOCI, LOCALIDAD, A.TALON_PED, A.NRO_PEDIDO, A.CANT_PEDIDO, A.CANT_PENDIENTE, IMP_PENDIENTE, COD_VENDED, VENDEDOR, A.TIPO_COMP, A.EMBALAJE, A.DESPACHO, A.ARREGLO, A.PRIORIDAD, FECHA_DESPACHO 
                    FROM RO_PEDIDOS_MAYORISTA_ASIGNADOS A
                    LEFT JOIN SJ_PICKING_ENCABEZADO B ON A.NRO_PEDIDO = B.NRO_PEDIDO AND A.TALON_PED = B.TALON_PED
                    WHERE B.ESTADO = 'ABIERTO'
            
                UNION ALL
            
                    SELECT A.FECHA, A.ESTADO, A.HORA_INGRESO, A.COD_CLIENT, A.RAZON_SOCI, A.LOCALIDAD, A.TALON_PED, A.NRO_PEDIDO, A.CANT_PEDIDO, A.CANT_PENDIENTE, A.IMP_PENDIENTE, A.COD_VENDED, A.VENDEDOR, '' TIPO_COMP, '' EMBALAJE, '' DESPACHO, NULL ARREGLO, '' PRIORIDAD,'' FECHA_DESPACHO FROM RO_PEDIDOS_PENDIENTE_MAYORISTAS A
                    LEFT JOIN SJ_PICKING_ENCABEZADO B ON A.NRO_PEDIDO = B.NRO_PEDIDO AND A.TALON_PED = B.TALON_PED
                    WHERE A.NRO_PEDIDO NOT IN (SELECT NRO_PEDIDO FROM RO_PEDIDOS_MAYORISTA_ASIGNADOS WHERE COD_CLIENT = '$cliente') AND B.ESTADO = 'ABIERTO'
                ) A
                WHERE COD_CLIENT = '$cliente' ORDER BY FECHA";

        $rows = $this->retornarArray($sql);
        
        $myJSON = json_encode($rows);

        return $myJSON;

    }

    public function traerPedidosAsignados($cliente, $desde, $hasta)
    {

        $sql = "SELECT FECHA_DESPACHO, COD_CLIENT, RAZON_SOCI, COUNT(NRO_PEDIDO) CANT_PEDIDOS, SUM(CANT_PENDIENTE) UNID_PENDIENTE, SUM(IMP_PENDIENTE) IMP_PENDIENTE FROM RO_PEDIDOS_MAYORISTA_ASIGNADOS
                WHERE RAZON_SOCI LIKE '$cliente' AND FECHA_DESPACHO BETWEEN '$desde' AND '$hasta'
                GROUP BY FECHA_DESPACHO, COD_CLIENT, RAZON_SOCI";

        $rows = $this->retornarArray($sql);
        
        $myJSON = json_encode($rows);

        return $myJSON;

    }

    public function traerResumenPorPedido($desde, $hasta)
    {
        
        $sql = "SELECT CAST(A.FECHA AS DATE) FECHA, A.ESTADO, A.HORA_INGRESO, A.COD_CLIENT, A.RAZON_SOCI, LOCALIDAD, A.TALON_PED, A.NRO_PEDIDO, A.CANT_PEDIDO, A.CANT_PENDIENTE, IMP_PENDIENTE, COD_VENDED, VENDEDOR, A.TIPO_COMP, A.EMBALAJE, A.DESPACHO, FECHA_DESPACHO
                FROM RO_PEDIDOS_MAYORISTA_ASIGNADOS A
                LEFT JOIN SJ_PICKING_ENCABEZADO B ON A.NRO_PEDIDO = B.NRO_PEDIDO AND A.TALON_PED = B.TALON_PED
                WHERE B.ESTADO = 'ABIERTO' AND A.FECHA_DESPACHO BETWEEN '$desde' AND '$hasta'
                ORDER BY FECHA, RAZON_SOCI, NRO_PEDIDO";

        $rows = $this->retornarArray($sql);
        
        $myJSON = json_encode($rows);

        return $myJSON;

    }

}