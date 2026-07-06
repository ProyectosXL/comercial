<?php

class Pedido
{
    private function retornarArray($sqlEnviado, $pais)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        $cid = new Conexion();
        
        // Conectar al servidor según el país (AR -> central, UY -> uy)
        $connName = ($pais === 'UY') ? 'uy' : 'central';
        $conexion = $cid->conectar($connName);

        if (!$conexion) {
            return false;
        }

        $stmt = sqlsrv_query($conexion, $sqlEnviado);
        if ($stmt === false) {
            return false;
        }

        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $v;
        }
        return $rows;
    }

    private function ejecutarComando($sqlEnviado, $pais)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/Class/Conexion.php';
        $cid = new Conexion();
        
        $connName = ($pais === 'UY') ? 'uy' : 'central';
        $conexion = $cid->conectar($connName);

        if (!$conexion) {
            return false;
        }

        $stmt = sqlsrv_query($conexion, $sqlEnviado);
        if ($stmt === false) {
            return false;
        }
        return true;
    }

    public function validarPedido($pedido, $talon_pedido, $pais)
    {
        $talon_pedido = strval($talon_pedido);
        // Formatear el pedido a 14 caracteres: ' 0000' + últimos 9 caracteres
        $pedidoFormateado = ' 0000' . substr(strval($pedido), -9);

        // Limpiar parámetros básicos para prevención de inyección
        $talon_pedido = str_replace("'", "''", $talon_pedido);
        $pedidoFormateado = str_replace("'", "''", $pedidoFormateado);

        $sql = "SELECT COUNT(*) AS CONTAR FROM GVA21 WHERE TALON_PED = '$talon_pedido' AND NRO_PEDIDO = '$pedidoFormateado' AND ESTADO = 2";
        $rows = $this->retornarArray($sql, $pais);
        
        if ($rows && count($rows) > 0) {
            return intval($rows[0]['CONTAR']);
        }
        return 0;
    }

    public function anularPedido($pedido, $talon_pedido, $pais)
    {
        $talon_pedido = strval($talon_pedido);
        $pedidoFormateado = ' 0000' . substr(strval($pedido), -9);

        $talon_pedido = str_replace("'", "''", $talon_pedido);
        $pedidoFormateado = str_replace("'", "''", $pedidoFormateado);

        $sql = "EXEC RO_ANULAR_PEDIDOS '$talon_pedido', '$pedidoFormateado'";
        return $this->ejecutarComando($sql, $pais);
    }
}
