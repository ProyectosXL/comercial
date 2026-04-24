<?php
ini_set('memory_limit', '3072M');
class Pedido
{
    
    private $cid;
    private $cid_central;

    
    function __construct()
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
        $this->cid = new Conexion();
        
        // Iniciar sesión solo si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $db = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
        $this->cid_central = $this->cid->conectar($db);

    } 


    private function retornarArray($sqlEnviado){

        $sql = $sqlEnviado;
     
        $stmt = sqlsrv_query( $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        return $rows;  

    }

    public function traerClientes(){

        $sql = " 
            SELECT A.COD_CLIENT, RAZON_SOCI FROM GVA14 A
            INNER JOIN
            (
            SELECT DISTINCT(COD_CLIENT) COD_CLIENT FROM GVA21 WHERE FECHA_PEDI >= GETDATE()-365
            ) B
            ON A.COD_CLIENT = B.COD_CLIENT

        ";

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] != 'uy'){
            $sql .= "WHERE A.COD_CLIENT LIKE '[GFM0E]%'";
        }

        $rows = $this->retornarArray($sql);

        return $rows;

    }  

    public function traePedido($desde, $hasta, $canal, $cliente, $estado, $tipo){

        $sql = " 
            SELECT * FROM RO_V_EFICIENCIA_PEDIDOS 
            WHERE FECHA_PEDIDO BETWEEN  '$desde' AND '$hasta'
            AND COD_CLIENT LIKE '%$cliente%'
            AND ESTADO LIKE '%$estado%'
            AND TIPO LIKE '%$tipo%'
        ";

        if (!empty($canal)) {
            $canalList = implode("','", array_map('addslashes', $canal));
            $sql .= " AND CANAL IN ('$canalList')";
        }

        
        $rows = $this->retornarArray($sql);

        return $rows;

    }  

}    
