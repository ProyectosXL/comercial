
<?php

$codArticu = $_POST['codArticu'];

require_once '../Class/Articulo.php';

$articulo = new Articulo();
$articuloArray = $articulo->traerMaestro($codArticu);



if($articuloArray){
    $response = array(
        'code' => 200, 
        'msg' => json_encode($articuloArray),
    );
}else{
    $response = array(
        'code' => 404, 
        'msg' => 'articulo no encontrado!',
    );
}

echo json_encode($response);