
<?php

$ART = $_GET['codigo'];

if (isset($ART)) {
    echo $ART;
    require_once '../Class/Conexion.php';

    $cid = new Conexion();
    $cid_central = $cid->conectar();

    $sql = "SELECT COD_ARTICU FROM SJ_EXCLUIDOS WHERE ID = '$ART'";

    $stmt = sqlsrv_query($cid_central, $sql);

    if ($row = sqlsrv_fetch_array($stmt)) {
        echo 'ENCONTRADO';
    }
}




?>