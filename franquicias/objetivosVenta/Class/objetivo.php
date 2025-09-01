
<?php

class Objetivo
{
    private $cid_central;

    function __construct(){
        require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/Class/Conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');
    } 



}

?>
