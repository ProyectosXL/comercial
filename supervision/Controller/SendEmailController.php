<?php
session_start();

require_once $_SERVER["DOCUMENT_ROOT"].'/comercial/supervision/class/emailNew.php';


$accion = $_GET['accion'];

switch ($accion ) {

    case 'cargaGasto':
        cargaGasto();
        break;

    case 'autorizar':
        autorizar();
        break;

    
}


function cargaGasto () {

    

    $supervisora = $_POST['supervisora'];
    $semana = $_POST['semana'];
    $totalGastos = $_POST['totalGastos'];


    $asunto =  "Gastos Cargados semana $semana  de $supervisora";

    $arrayData = [

        'tipo' => 1,
        'supervisora' => $supervisora,
        'semana' => $semana,
        'totalGastos' => $totalGastos,

    ];

    $email = new Email();
    
    try {
        $arrayEmail = array();
        $usuarios =  ['johanna.bolig@xl.com.ar'];

        foreach ($usuarios as $key => $usuario) {
           

            $arrayEmail[] = $email->enviarEmail($usuario, $asunto, $arrayData);
            

        }
        
        echo true;

    } catch (Exception $e) {
        echo "Error al enviar el correo: {$mail->ErrorInfo}";
    }

}

function autorizar () {

    

    $supervisora = $_POST['supervisora'];
    $semana = $_POST['semana'];
    $totalGastos = $_POST['totalGastos'];
    
    $totalGastos = number_format($totalGastos, 2, ',', '.');

    $emailSupervisora = $_POST['email'];


    $asunto =  "Gastos Autorizados semana $semana  de $supervisora";

    $arrayData = [

        'tipo' => 2,
        'supervisora' => $supervisora,
        'semana' => $semana,
        'totalGastos' => $totalGastos

    ];

    $email = new Email();
    
    try {
        $arrayEmail = array();
        $usuarios =  ['rodrigo.alganaraz@xl.com.ar', 'julieta.bianculli@xl.com.ar', 'tesoreria@xl.com.ar', $emailSupervisora];

        foreach ($usuarios as $key => $usuario) {
           
            $arrayEmail[] = $email->enviarEmail($usuario, $asunto, $arrayData);

        }
        
        echo true;

    } catch (Exception $e) {
        echo "Error al enviar el correo: {$mail->ErrorInfo}";
    }

}

?>