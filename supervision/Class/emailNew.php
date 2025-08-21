<?php


require_once $_SERVER["DOCUMENT_ROOT"].'/comercial/supervision/Class/PHPMailer/PHPMailer.php';
require_once $_SERVER["DOCUMENT_ROOT"].'/comercial/supervision/Class/PHPMailer/SMTP.php';
require_once $_SERVER["DOCUMENT_ROOT"].'/comercial/supervision/Class/PHPMailer/Exception.php';

require_once $_SERVER["DOCUMENT_ROOT"].'/comercial/supervision/Class/classEnv.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Email{

    private $Host;
    private $SMTPAuth;
    private $Username;
    private $Password;
    private $SMTPSecure;
    private $Port;
    private $email;
    private $sender;
    private $vars;
    private $envVars;
    private $body;
    private $message;
    private $mail;


    public function __construct() {

        try {
            
            $this->vars = new DotEnv($_SERVER["DOCUMENT_ROOT"].'/comercial/.env');
            
       
            $this->envVars = $this->vars->listVars();
            $this->mail = new PHPMailer(true);
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com'; 
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'xl.notificaciones@xl.com.ar';
            $this->mail->Password = 'oysuyuhsnyoaifin';
            $this->mail->SMTPSecure = 'tls'; 
            $this->mail->Port = 587; 
            $this->mail->setFrom('xl.notificaciones@xl.com.ar', 'XL Extralarge');

        } catch (Exception $e) {
            echo "Error al instanciar la clase Email: {$this->mail->ErrorInfo}";
        }
        
    }


    
    public function enviarEmail($email, $asunto, $arrayData){

        try {
            
            $this->mail->addAddress($email);
            $this->mail->Subject = $asunto;
            $this->mail->Body = $this->emailBody($arrayData);
            $this->mail->isHTML(true);
            $this->mail->send();

            return true;

        } catch (\Throwable $th) {
            echo "Error al enviar el correo: {$th}";
        }
        
    }
    
    private function emailBody ($arrayData) {

        switch ($arrayData['tipo']) {

            case '1':
                $this->body = $this->cargaGasto($arrayData['supervisora'], $arrayData['semana'], $arrayData['totalGastos']);
                break;

            case '2':
                $this->body = $this->autorizar($arrayData['supervisora'], $arrayData['semana'], $arrayData['totalGastos']);
                break;
            case '3':
                $this->body = $this->cargaGastoConValidacion(
                    $arrayData['supervisora'], 
                    $arrayData['semana'], 
                    $arrayData['totalGastos'],
                    $arrayData['gastosAprobados'],
                    $arrayData['gastosExcedidos'],
                    $arrayData['conceptosExcedidos']
                );
                break;
            case '4':
                $this->body = $this->autorizacionAutomatica(
                    $arrayData['supervisora'], 
                    $arrayData['semana'], 
                    $arrayData['totalGastos']
                );
                break;   
        }

        return $this->body;

    }

    private function cargaGasto($supervisora, $semana, $totalGastos) {

        $this->body = '

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
            <html lang="en" xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Document</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

                <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
                <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

                <!-- Bootstrap Icons -->
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

                
            </head>
            <body>
                
                <div style="height:57rem;padding-left: 0px;padding-right: 0px;font-size:16px">'.$supervisora.' - Resumen de Gastos de la semana '.$semana.'
                
                    <div style="height: 25rem; width:100%;display: flex; justify-content: center; align-items: center; ">
                        <div>
                            <div class="card" style="width: 800px; height: 28rem; margin-top:16rem; background-color: #17a2b8; color: #FFFFFF">
                                <br>
                                <div style="text-align:center"><h1 style="margin-top:2rem;font-size:60px">Gastos de Supervision</h1></div>
                                <div style="margin-top:2rem;text-align: center; font-size:22px">Hola,</div>
                                <br>
                                <div style="text-align: center; font-size:22px">Se cargaron gastos de supervision para la semana '.$semana.' de la supervisora '.$supervisora.' por un total de '.$totalGastos.'</div>
                                <br>
                                <div style="text-align: center; font-size:14px">Este correo es informativo, favor no responder a esta direccion de correo, ya que no se <br> encuentra habilitada para recibir mensajes</div>
                                <br>
                                <div style="text-align: center; font-size:15px;">Extra Large, ARGENTINA</div>
                                <br><br>
                            </div>
                        </div>
                    </div>

                </div>

                
            </body>
            </html>

            ';

        return $this->body;
    
    }

    private function autorizar($supervisora, $semana, $totalGastos) {

        $this->body = '

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
            <html lang="en" xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Document</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

                <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
                <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

                <!-- Bootstrap Icons -->
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

                
            </head>
            <body>
                
                <div style="height:57rem;padding-left: 0px;padding-right: 0px;font-size:16px">'.$supervisora.' - Resumen de Gastos de la semana '.$semana.'
                
                    <div style="height: 25rem; width:100%;display: flex; justify-content: center; align-items: center; ">
                        <div>
                            <div class="card" style="width: 800px; height: 28rem; margin-top:16rem; background-color: #17a2b8; color: #FFFFFF">
                                <br>
                                <div style="text-align:center"><h1 style="margin-top:2rem;font-size:60px">Gastos de Supervision</h1></div>
                                <div style="margin-top:2rem;text-align: center; font-size:22px">Hola,</div>
                                <br>
                                <div style="text-align: center; font-size:22px">Se autorizaron gastos de supervisión para la semana  '.$semana.' de la supervisora '.$supervisora.' por un total de $'.$totalGastos.' en efectivo </div>
                                <br>
                                <div style="text-align: center; font-size:14px">Este correo es informativo, favor no responder a esta direccion de correo, ya que no se <br> encuentra habilitada para recibir mensajes</div>
                                <br>
                                <div style="text-align: center; font-size:15px;">Extra Large, ARGENTINA</div>
                                <br><br>
                            </div>
                        </div>
                    </div>

                </div>

                
            </body>
            </html>

            ';

        return $this->body;
    
    }


}
