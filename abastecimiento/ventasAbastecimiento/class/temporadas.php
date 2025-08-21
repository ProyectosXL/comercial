<?php

class Temporada
{

    function __construct()
    {

        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');

    } 

    public function traerTemporadas(){
       

        $sql = "
        SELECT DISTINCT(TEMPORADA) TEMPORADA FROM MAESTRO_DESTINOS WHERE TEMPORADA IS NOT NULL AND TEMPORADA NOT IN ('ELIMINAR/DESHABILITA', 'SIN') ORDER BY 1 ASC
        ";
        $stmt = sqlsrv_query(  $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        return $rows;
    }

}    