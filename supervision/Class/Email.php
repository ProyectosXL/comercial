
<?php

require_once $_SERVER["DOCUMENT_ROOT"].'/comercial/supervision/Class/PHPMailer/PHPMailer.php';
require_once $_SERVER["DOCUMENT_ROOT"].'/comercial/supervision/Class/PHPMailer/SMTP.php';
require_once $_SERVER["DOCUMENT_ROOT"].'/comercial/supervision/Class/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailNew {
    
    private $mail;

    public function __construct() {
        try {
            // Cargar variables de entorno
            require_once __DIR__ . '/classEnv.php';
            $dotenv = new DotEnv(__DIR__ . '/../../../.env');
            $dotenv->load();

            $this->mail = new PHPMailer(true);
            $this->mail->isSMTP();
            $this->mail->Host = getenv('HOST_EMAIL_EGRESOS') ?: 'smtp.gmail.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = getenv('USER_EMAIL_SUPERVISION');
            $this->mail->Password = getenv('PASS_EMAIL_SUPERVISION');
            $this->mail->SMTPSecure = (getenv('PORT_EMAIL_EGRESOS') == '465') ? 'ssl' : 'tls';
            $this->mail->Port = getenv('PORT_EMAIL_EGRESOS') ?: 587;
            $this->mail->setFrom($this->mail->Username, 'XL Extralarge');
            
        } catch (Exception $e) {
            error_log("Error al instanciar EmailNew: " . $e->getMessage());
        }
    }
    
    /**
     * Envía notificación de autorización de gastos en efectivo
     * Solo se envía cuando hay gastos en efectivo
     */
    public function enviarNotificacionEfectivo($supervisora, $semana, $totalEfectivo, $adelanto = 0) {
        try {
            if ($totalEfectivo <= 0) {
                return false; // No enviar si no hay gastos en efectivo
            }

            $reintegro = $totalEfectivo - $adelanto;
            
            $destinatarios = ['rodrigo.alganaraz@xl.com.ar','julieta.bianculli@xl.com.ar','tesoreria@xl.com.ar','diego.szmulewicz@xl.com.ar'];
            // $destinatarios = ['ramiro.orozco@xl.com.ar'];

            $asunto = "Reintegro de Gastos en Efectivo - {$supervisora} - Semana {$semana}";
            $htmlBody = $this->generarHtmlReintegroEfectivo($supervisora, $semana, $totalEfectivo, $adelanto, $reintegro);

            foreach ($destinatarios as $email) {
                $this->mail->clearAddresses();
                $this->mail->addAddress($email);
                $this->mail->Subject = $asunto;
                $this->mail->Body = $htmlBody;
                $this->mail->isHTML(true);
                $this->mail->send();
            }

            return true;

        } catch (Exception $e) {
            error_log("Error al enviar notificación de efectivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía notificación de exceso de presupuesto
     * Se envía cuando hay gastos que exceden el presupuesto
     */
    public function enviarNotificacionExcesoPresupuesto($supervisora, $semana, $conceptosExcedidos) {
        try {
            if (empty($conceptosExcedidos)) {
                return false; // No enviar si no hay excesos
            }

            $destinatarios = ['rodrigo.alganaraz@xl.com.ar','julieta.bianculli@xl.com.ar','tesoreria@xl.com.ar','johanna.bolig@xl.com.ar'];
            // $destinatarios = ['ramiro.orozco@xl.com.ar'];

            $asunto = "Exceso de Presupuesto - {$supervisora} - Semana {$semana}";
            $htmlBody = $this->generarHtmlExcesoPresupuesto($supervisora, $semana, $conceptosExcedidos);

            foreach ($destinatarios as $email) {
                $this->mail->clearAddresses();
                $this->mail->addAddress($email);
                $this->mail->Subject = $asunto;
                $this->mail->Body = $htmlBody;
                $this->mail->isHTML(true);
                $this->mail->send();
            }

            return true;

        } catch (Exception $e) {
            error_log("Error al enviar notificación de exceso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera HTML para notificación de reintegro en efectivo
     */
    private function generarHtmlReintegroEfectivo($supervisora, $semana, $totalEfectivo, $adelanto, $reintegro) {
        $colorFondo = $reintegro > 0 ? '#d1e7dd' : '#fff3cd';
        $colorBorde = $reintegro > 0 ? '#28a745' : '#ffc107';
        $iconoEstado = $reintegro > 0 ? '💰' : '⚖️';
        
        $mensajeReintegro = '';
        if ($reintegro > 0) {
            $mensajeReintegro = "
                <div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>
                    <h4 style='color: #155724; margin: 0 0 10px 0;'>💰 Reintegro a realizar:</h4>
                    <p style='color: #155724; margin: 0; font-size: 18px; font-weight: bold;'>\$" . number_format($reintegro, 2, ',', '.') . "</p>
                </div>";
        } else if ($reintegro < 0) {
            $reintegroPositivo = abs($reintegro);
            $mensajeReintegro = "
                <div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 5px;'>
                    <h4 style='color: #856404; margin: 0 0 10px 0;'>⚖️ El adelanto supera los gastos:</h4>
                    <p style='color: #856404; margin: 0; font-size: 18px; font-weight: bold;'>Diferencia: \$" . number_format($reintegroPositivo, 2, ',', '.') . "</p>
                    <p style='color: #856404; margin: 5px 0 0 0; font-size: 14px;'>No requiere reintegro adicional.</p>
                </div>";
        } else {
            $mensajeReintegro = "
                <div style='background-color: #e2e3e5; border: 1px solid #d6d8db; padding: 15px; margin: 15px 0; border-radius: 5px;'>
                    <h4 style='color: #383d41; margin: 0 0 10px 0;'>⚖️ Gastos balanceados:</h4>
                    <p style='color: #383d41; margin: 0; font-size: 16px;'>Los gastos coinciden exactamente con el adelanto.</p>
                </div>";
        }

        return "
            <!DOCTYPE html>
            <html lang=\"es\">
            <head>
                <meta charset=\"UTF-8\">
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                <title>Reintegro de Gastos en Efectivo</title>
            </head>
            <body style=\"font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px;\">
                <div style=\"max-width: 600px; margin: 0 auto; background-color: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1);\">
                    
                    <!-- Header -->
                    <div style=\"background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;\">
                        <h1 style=\"margin: 0; font-size: 28px;\">{$iconoEstado} Gastos en Efectivo</h1>
                        <p style=\"margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;\">Notificación de Reintegro</p>
                    </div>
                    
                    <!-- Content -->
                    <div style=\"padding: 30px;\">
                        
                        <!-- Información Principal -->
                        <div style=\"background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;\">
                            <h3 style=\"color: #333; margin-top: 0;\">Detalles del Gasto</h3>
                            <table style=\"width: 100%; border-collapse: collapse;\">
                                <tr>
                                    <td style=\"padding: 8px 0; font-weight: bold; color: #666;\">Supervisora:</td>
                                    <td style=\"padding: 8px 0; color: #333;\">{$supervisora}</td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 8px 0; font-weight: bold; color: #666;\">Semana:</td>
                                    <td style=\"padding: 8px 0; color: #333;\">{$semana}</td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 8px 0; font-weight: bold; color: #666;\">Total Gastos en Efectivo:</td>
                                    <td style=\"padding: 8px 0; color: #333; font-weight: bold;\">\$" . number_format($totalEfectivo, 2, ',', '.') . "</td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 8px 0; font-weight: bold; color: #666;\">Adelanto Recibido:</td>
                                    <td style=\"padding: 8px 0; color: #333;\">\$" . number_format($adelanto, 2, ',', '.') . "</td>
                                </tr>
                            </table>
                        </div>
                        
                        {$mensajeReintegro}
                        
                        <!-- Footer -->
                        <div style=\"text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;\">
                            <p style=\"color: #666; font-size: 14px; margin: 0;\">Este correo es generado automáticamente por el sistema de gestión de gastos.</p>
                            <p style=\"color: #666; font-size: 14px; margin: 5px 0 0 0;\">Por favor, no responda a esta dirección de correo.</p>
                            <p style=\"color: #333; font-weight: bold; margin: 15px 0 0 0;\">Extra Large, ARGENTINA</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>";
    }

    /**
     * Genera HTML para notificación de exceso de presupuesto
     */
    private function generarHtmlExcesoPresupuesto($supervisora, $semana, $conceptosExcedidos) {
        $detalleConceptos = '';
        $totalExceso = 0;
        
        foreach ($conceptosExcedidos as $concepto) {
            $totalExceso += $concepto['exceso'];
            
            // CAMBIO: Usar 'totalGastoAcumulado' que contiene el gasto REAL total del mes
            $gastoRealTotal = isset($concepto['totalGastoAcumulado']) ? $concepto['totalGastoAcumulado'] : $concepto['gasto'];
            
            $detalleConceptos .= "
                <tr style=\"border-bottom: 1px solid #dee2e6;\">
                    <td style=\"padding: 12px 8px; color: #333; border-right: 1px solid #dee2e6;\">{$concepto['concepto']}</td>
                    <td style=\"padding: 12px 8px; color: #333; text-align: right; border-right: 1px solid #dee2e6;\">\$" . number_format($concepto['presupuesto'], 2, ',', '.') . "</td>
                    <td style=\"padding: 12px 8px; color: #333; text-align: right; border-right: 1px solid #dee2e6;\">\$" . number_format($gastoRealTotal, 2, ',', '.') . "</td>
                    <td style=\"padding: 12px 8px; color: #dc3545; text-align: right; font-weight: bold;\">\$" . number_format($concepto['exceso'], 2, ',', '.') . "</td>
                </tr>";
        }

        return "
            <!DOCTYPE html>
            <html lang=\"es\">
            <head>
                <meta charset=\"UTF-8\">
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                <title>Exceso de Presupuesto Detectado</title>
            </head>
            <body style=\"font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px;\">
                <div style=\"max-width: 700px; margin: 0 auto; background-color: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1);\">
                    
                    <!-- Header -->
                    <div style=\"background: linear-gradient(135deg, #dc3545, #fd7e14); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;\">
                        <h1 style=\"margin: 0; font-size: 28px;\">⚠️ EXCESO DE PRESUPUESTO</h1>
                        <p style=\"margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;\">Requiere Revisión Gerencial</p>
                    </div>
                    
                    <!-- Content -->
                    <div style=\"padding: 30px;\">
                        
                        <!-- Información Principal -->
                        <div style=\"background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin-bottom: 25px;\">
                            <h3 style=\"color: #856404; margin-top: 0;\">🔍 Información del Evento</h3>
                            <table style=\"width: 100%; border-collapse: collapse;\">
                                <tr>
                                    <td style=\"padding: 8px 0; font-weight: bold; color: #856404;\">Supervisora:</td>
                                    <td style=\"padding: 8px 0; color: #856404; font-weight: bold;\">{$supervisora}</td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 8px 0; font-weight: bold; color: #856404;\">Semana:</td>
                                    <td style=\"padding: 8px 0; color: #856404; font-weight: bold;\">{$semana}</td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 8px 0; font-weight: bold; color: #856404;\">Total Exceso:</td>
                                    <td style=\"padding: 8px 0; color: #dc3545; font-weight: bold; font-size: 18px;\">\$" . number_format($totalExceso, 2, ',', '.') . "</td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Detalle de Conceptos Excedidos -->
                        <div style=\"margin-bottom: 25px;\">
                            <h3 style=\"color: #333; margin-bottom: 15px;\">📊 Detalle de Conceptos Excedidos</h3>
                            <div style=\"overflow-x: auto;\">
                                <table style=\"width: 100%; border-collapse: collapse; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;\">
                                    <thead>
                                        <tr style=\"background-color: #f8f9fa;\">
                                            <th style=\"padding: 15px 8px; color: #495057; font-weight: bold; text-align: left; border-right: 1px solid #dee2e6;\">Concepto</th>
                                            <th style=\"padding: 15px 8px; color: #495057; font-weight: bold; text-align: right; border-right: 1px solid #dee2e6;\">Presupuesto</th>
                                            <th style=\"padding: 15px 8px; color: #495057; font-weight: bold; text-align: right; border-right: 1px solid #dee2e6;\">Gasto Real Total</th>
                                            <th style=\"padding: 15px 8px; color: #495057; font-weight: bold; text-align: right;\">Exceso</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {$detalleConceptos}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Acción Requerida -->
                        <div style=\"background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; margin-bottom: 25px;\">
                            <h4 style=\"color: #721c24; margin: 0 0 10px 0;\">🎯 Acción Requerida</h4>
                            <p style=\"color: #721c24; margin: 0; line-height: 1.5;\">
                                Los gastos han sido <strong>autorizados temporalmente</strong> pero requieren <strong>revisión y aprobación de la gerencia comercial</strong> 
                                debido al exceso del presupuesto asignado.
                            </p>
                            <p style=\"color: #721c24; margin: 10px 0 0 0; font-size: 14px;\">
                                <strong>Próximos pasos:</strong> Evaluar justificación y autorizar o rechazar definitivamente.
                            </p>
                        </div>
                        
                        <!-- Footer -->
                        <div style=\"text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;\">
                            <p style=\"color: #666; font-size: 14px; margin: 0;\">Este correo es generado automáticamente por el sistema de control de presupuestos.</p>
                            <p style=\"color: #666; font-size: 14px; margin: 5px 0 0 0;\">Por favor, tome las acciones correspondientes a la brevedad.</p>
                            <p style=\"color: #333; font-weight: bold; margin: 15px 0 0 0;\">Extra Large, ARGENTINA</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>";
    }
}