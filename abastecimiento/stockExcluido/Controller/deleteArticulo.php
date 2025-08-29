
<?php

$matriz = $_POST['matriz'];

foreach($matriz as $id){
  require_once '../../class/conexion.php';

    $cid = new Conexion();
    $cid_central = $cid->conectar('central');        

    $sql = "DELETE FROM SJ_EXCLUIDOS WHERE ID = '$id'";

    $stmt = sqlsrv_query( $cid_central, $sql );

}

echo $id;

?>