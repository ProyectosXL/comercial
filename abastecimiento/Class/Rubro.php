<?php

class Rubro
{

    private $cid;
    private $cid_central;

    
    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
        $this->cid = new Conexion();
        
        // Verificar si no hay una sesión activa antes de iniciarla
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $db = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
     
        $this->cid_central = $this->cid->conectar($db);

    } 


    public function traerRubros(){

        $sql = "
        SELECT REPLACE(DESCRIP, '_', '') DESCRIP FROM STA11FLD WHERE DESCRIP NOT LIKE '[_][ZD]%' AND DESCRIP NOT LIKE 'Todos' 
        AND DESCRIP NOT LIKE '%OUTLET' AND DESCRIP NOT IN ('ALHAJEROS','PACKAGING','_KITS')
        ";
        $stmt = sqlsrv_query( $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        return $rows;
    }
    public function traerRubrosDestinos(){

        $sql="SELECT DISTINCT(RUBRO) RUBRO FROM SOF_RUBROS_TANGO
        WHERE RUBRO NOT LIKE '[_]%'";
        
        $stmt = sqlsrv_query( $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        return $rows;
    }

}  