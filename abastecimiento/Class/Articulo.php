<?php

require_once __DIR__ . '/../../class/conexion.php';

class Articulo
{
    
    private $cid;
    private $cid_central;

    
    function __construct()
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
        $this->cid = new Conexion();

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

        while( $v = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
            $rows[] = $v;
        }

        return $rows;  

    }

    public function traerArticulos($rubro, $temporada){

        $sql = "SELECT A.COD_ARTICU, DESCRIPCION, DESTINO, TEMPORADA, B.RUBRO FROM MAESTRO_DESTINOS A
                LEFT JOIN SOF_RUBROS_TANGO B ON A.COD_ARTICU = B.COD_ARTICU
                WHERE TEMPORADA LIKE '$temporada' AND RUBRO LIKE '$rubro'

        ";

        $rows = $this->retornarArray($sql);

        return $rows;

    }  

}
    