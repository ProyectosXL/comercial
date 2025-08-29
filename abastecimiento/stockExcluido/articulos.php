<?php

class Codigos
{

    public function traerArticulo($codigo){
        try {

            require_once '../class/conexion.php';
            $cid = new Conexion();
            $cid_central = $cid->conectar('central');
             
         } catch (PDOException $e){
                 echo $e->getMessage();
         }
        $sql ="
        SELECT COD_ARTICU, DESCRIPCIO FROM STA11 WHERE COD_ARTICU LIKE '[XO]%' AND COD_ARTICU LIKE '$codigo'
        ";
        $stmt = sqlsrv_query( $cid_central, $sql);

       if($row=sqlsrv_fetch_array($stmt))
       {
         echo $row['DESCRIPCIO'];
        }else{
            echo 'error';
        }
        sqlsrv_close($cid_central);
    }
     
    }

  
//'

if(isset($_GET['codigo']))
{
$r=new Codigos();

$r->traerArticulo($_GET['codigo']);
}