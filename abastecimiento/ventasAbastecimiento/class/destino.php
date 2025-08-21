<?php

class Destino
{

    function __construct()
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');

    } 


    public function traerDestinos(){

        $sql = "
        SELECT DISTINCT(DESTINO) DESTINO FROM MAESTRO_DESTINOS 
        ";
        $stmt = sqlsrv_query($this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        return $rows;
    }

}    