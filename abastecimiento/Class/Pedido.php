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

    public function traerGrupos(){

        $sql = " 
            SELECT DISTINCT GRUPO_EMPR 
            FROM GVA14 
            WHERE GRUPO_EMPR IS NOT NULL AND RTRIM(LTRIM(GRUPO_EMPR)) <> ''
        ";

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] != 'uy'){
            $sql .= " AND COD_CLIENT LIKE '[GFML0E]%'";
        }

        $sql .= " ORDER BY GRUPO_EMPR";

        $rows = $this->retornarArray($sql);

        return $rows;

    }

    public function traerClientes(){

        $sql = " 
            SELECT A.COD_CLIENT, A.RAZON_SOCI, ISNULL(A.GRUPO_EMPR, '') AS GRUPO_EMPR FROM GVA14 A
            INNER JOIN
            (
            SELECT DISTINCT(COD_CLIENT) COD_CLIENT FROM GVA21 WHERE FECHA_PEDI >= GETDATE()-365
            ) B
            ON A.COD_CLIENT = B.COD_CLIENT
        ";

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] != 'uy'){
            $sql .= "WHERE A.COD_CLIENT LIKE '[GFML0E]%'";
        }

        $sql .= " ORDER BY A.COD_CLIENT";

        $rows = $this->retornarArray($sql);

        return $rows;

    }  

    public function traePedido($desde, $hasta, $canal = [], $cliente = [], $estado = '%', $tipo = '%', $grupo = []){

        $sql = " 
            SELECT p.*, ISNULL(cl.GRUPO_EMPR, '') AS GRUPO_EMPR 
            FROM RO_V_EFICIENCIA_PEDIDOS p
            LEFT JOIN GVA14 cl ON p.COD_CLIENT = cl.COD_CLIENT
            WHERE p.FECHA_PEDIDO BETWEEN  '$desde' AND '$hasta'
            AND p.ESTADO LIKE '%$estado%'
            AND p.TIPO LIKE '%$tipo%'
        ";

        if (!empty($cliente)) {
            $clientesFiltrados = array_filter((array)$cliente, function($c) {
                return !empty($c) && $c !== '%';
            });
            if (!empty($clientesFiltrados)) {
                $cliList = implode("','", array_map('addslashes', $clientesFiltrados));
                $sql .= " AND p.COD_CLIENT IN ('$cliList')";
            }
        }

        if (!empty($grupo)) {
            $gruposFiltrados = array_filter((array)$grupo, function($g) {
                return !empty($g) && $g !== '%';
            });
            if (!empty($gruposFiltrados)) {
                $grupoList = implode("','", array_map('addslashes', $gruposFiltrados));
                $sql .= " AND cl.GRUPO_EMPR IN ('$grupoList')";
            }
        }

        if (!empty($canal)) {
            $canalList = implode("','", array_map('addslashes', (array)$canal));
            $sql .= " AND p.CANAL IN ('$canalList')";
        }

        $rows = $this->retornarArray($sql);

        return $rows;

    }  

}    
