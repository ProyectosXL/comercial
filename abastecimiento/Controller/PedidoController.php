<?php

$accion = $_GET['accion'];

switch ($accion) {
    case 'cambiarEntorno':
        cambiarEntorno();
        break;
    
        default:
        # code...
        break;
    
}

function cambiarEntorno () {

    session_start();

    if($_POST['entorno'] == 0){
 
        $_SESSION['entorno'] = 'central';

    }else{
  
        $_SESSION['entorno'] = 'uy';

    }
    echo true;
}