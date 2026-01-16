
<?php

// Wrapper para mantener compatibilidad con código existente
// Redirige a la clase Conexion centralizada
require_once $_SERVER['DOCUMENT_ROOT'].'/sistemas/class/conexion.php';

class Conexion{
    public function conectar(){
        $conn = new \Conexion();
        $cid = $conn->conectar('central');
        
        if($cid === false){
            die("Error al conectar con la base de datos: ".print_r(sqlsrv_errors(), true));
        }
        
        return $cid;
    }
}

/* 
    if(search())
    { */
//update
    /* }else{ */
        //insert
   /*  } */


   /*  function search($num) */
    //query
/*    {

   }
} */