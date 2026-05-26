<?php

require_once __DIR__ . '/conexion.php';
// Referenciamos PHPMailer desde la ubicación centralizada que ya usan en otros proyectos
require_once $_SERVER["DOCUMENT_ROOT"] . '/comercial/supervision/Class/PHPMailer/PHPMailer.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/comercial/supervision/Class/PHPMailer/SMTP.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/comercial/supervision/Class/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailNotification {
    
    private $mail;

    public function __construct() {
        try {
            $this->mail = new PHPMailer(true);
            $this->mail->isSMTP();
            $this->mail->Host = $_ENV['HOST_EMAIL_EGRESOS'] ?? 'smtp.gmail.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $_ENV['USER_EMAIL_HORARIOS'];
            $this->mail->Password = $_ENV['PASS_EMAIL_HORARIOS']; 
            $this->mail->SMTPSecure = ($_ENV['PORT_EMAIL_EGRESOS'] == '465') ? 'ssl' : 'tls';
            $this->mail->Port = $_ENV['PORT_EMAIL_EGRESOS'] ?? 587;
            $this->mail->CharSet = 'UTF-8';
            $this->mail->setFrom($this->mail->Username, 'Notificaciones Horarios XL');
        } catch (Exception $e) {
            error_log("Error al configurar PHPMailer: " . $this->mail->ErrorInfo);
        }
    }

    /**
     * Obtiene datos (Nombre y Mail) de una sucursal
     */
    private function getDatosSucursal($idDepto) {
        $id = intval($idDepto);
        $datos = [
            'nombre' => "Sucursal $id",
            'mail' => null
        ];

        // 1. Buscamos el nombre real y el código Tango en RRHH (LAKERS_CORP_SA)
        $nroTango = null;
        $connCorp = getConexionCorp();
        if ($connCorp) {
            $sqlCorp = "SELECT DESC_DEPARTAMENTO, COD_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO = ?";
            $stmtCorp = sqlsrv_query($connCorp, $sqlCorp, [$id]);
            if ($stmtCorp && $rowCorp = sqlsrv_fetch_array($stmtCorp, SQLSRV_FETCH_ASSOC)) {
                $datos['nombre'] = trim($rowCorp['DESC_DEPARTAMENTO']);
                $codigo = trim($rowCorp['COD_DEPARTAMENTO']);
                // Extraer el número del código LOCxxx
                $nroTango = intval(str_ireplace('LOC', '', $codigo));
                
                // Corrección para Unicenter (si aplica en este entorno)
                if (stripos($datos['nombre'], 'UNICENTER') !== false) {
                    $nroTango = 2;
                }
            }
            if ($stmtCorp) sqlsrv_free_stmt($stmtCorp);
            sqlsrv_close($connCorp);
        }

        // 2. Buscamos el MAIL en DIRECCIONARIO (XL-LAKERBIS) usando el nro de Tango real
        if ($nroTango) {
            $connLoc = getConexionLocales();
            if ($connLoc) {
                $sql = "SELECT MAIL FROM DIRECCIONARIO WHERE NRO_SUCURSAL = ?";
                $stmt = sqlsrv_query($connLoc, $sql, [$nroTango]);
                if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $datos['mail'] = trim($row['MAIL']);
                }
                sqlsrv_free_stmt($stmt);
                sqlsrv_close($connLoc);
            }
        }
        
        return $datos;
    }

    /**
     * Obtiene el nombre del vendedor desde RRHH
     */
    private function getNombreVendedor($codVendedor) {
        $nombre = $codVendedor; // Fallback al código por defecto
        
        try {
            // 1. Intentamos obtener el nombre desde GVA23 (Tango Central - Siempre tiene a todos)
            $connCentral = getConexionCentral();
            if ($connCentral) {
                $sqlGva = "SELECT TOP 1 NOMBRE_VEN FROM GVA23 WHERE COD_VENDED = ?";
                $stmtGva = sqlsrv_query($connCentral, $sqlGva, [trim($codVendedor)]);
                if ($stmtGva && $row = sqlsrv_fetch_array($stmtGva, SQLSRV_FETCH_ASSOC)) {
                    $nom = trim($row['NOMBRE_VEN']);
                    if (!mb_detect_encoding($nom, 'UTF-8', true)) {
                        $nom = utf8_encode($nom);
                    }
                    // Limpieza opcional de números al final del nombre en Tango
                    $nom = preg_replace('/[\s-]*[0-9]+$/', '', $nom);
                    $nombre = $nom;
                }
                if ($stmtGva) sqlsrv_free_stmt($stmtGva);
                sqlsrv_close($connCentral);
            }

            // 2. Complemento/Fallback: Intentar obtener el nombre de RRHH (Corp)
            if ($nombre === $codVendedor) {
                $connCorp = getConexionCorp();
                if ($connCorp) {
                    $sqlCorp = "SELECT TOP 1 APELLIDO, NOMBRE 
                                FROM RO_LEGAJOS_PERSONAL 
                                WHERE BLOQUE = ? OR 'L' + CAST(NRO_LEGAJO AS VARCHAR) = ? OR NRO_LEGAJO = ?";
                    
                    $stmtCorp = sqlsrv_query($connCorp, $sqlCorp, [trim($codVendedor), trim($codVendedor), trim($codVendedor)]);
                    if ($stmtCorp && $row = sqlsrv_fetch_array($stmtCorp, SQLSRV_FETCH_ASSOC)) {
                        $nomFormateado = trim($row['APELLIDO']) . ' ' . trim($row['NOMBRE']);
                        if (!mb_detect_encoding($nomFormateado, 'UTF-8', true)) {
                            $nomFormateado = utf8_encode($nomFormateado);
                        }
                        if (!empty(trim($nomFormateado))) {
                            $nombre = $nomFormateado;
                        }
                    }
                    if ($stmtCorp) sqlsrv_free_stmt($stmtCorp);
                    sqlsrv_close($connCorp);
                }
            }
        } catch (Exception $e) {
            error_log("No se pudo obtener nombre del vendedor $codVendedor: " . $e->getMessage());
        }
        
        return $nombre;
    }

    /**
     * Notifica a la sucursal que sus horarios han sido actualizados
     */
    public function notificarActualizacionHorarios($nroSucursal, $semanaInfo) {
        try {
            $datosSuc = $this->getDatosSucursal($nroSucursal);
            $emailDestino = $datosSuc['mail'] ?? null;
            $nombreSuc = $datosSuc['nombre'] ?? "Sucursal $nroSucursal";
            
            if (!$emailDestino || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
                error_log("No se encontró email válido para la sucursal $nroSucursal");
                return false;
            }

            $asunto = "Actualización de Horarios - $nombreSuc - Semana $semanaInfo";
            $cuerpo = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; padding: 20px;'>
                    <h2 style='color: #333;'>Aviso de Actualización</h2>
                    <p>Hola,</p>
                    <p>Te informamos que los horarios correspondientes a la <strong>Semana $semanaInfo</strong> han sido actualizados por la Administración.</p>
                    <p style='background-color: #f9f9f9; padding: 15px; border-left: 4px solid #007bff;'>
                        Por favor, ingresá a la sección <strong>MI LOCAL</strong> en la App para visualizar los cambios realizados y coordinar con el equipo.
                    </p>
                    <p style='color: #666; font-size: 12px; margin-top: 30px;'>
                        Este es un mensaje automático, por favor no lo respondas.
                    </p>
                </div>";

            $this->mail->clearAddresses();
            $this->mail->addAddress($emailDestino);
            $this->mail->isHTML(true);
            $this->mail->Subject = $asunto;
            $this->mail->Body = $cuerpo;
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Error al enviar notificación de horarios: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica a Bárbara Balado que se ha generado feedback
     */
    public function notificarFeedbackGenerado($nroSucursal, $codVendedor, $semana) {
        try {
            $datosSuc = $this->getDatosSucursal($nroSucursal);
            $nombreSucursal = $datosSuc['nombre'] ?? "Sucursal $nroSucursal";
            $nombreVendedor = $this->getNombreVendedor($codVendedor);

            $asunto = "Nuevo Feedback generado - $nombreSucursal";
            $cuerpo = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; padding: 20px;'>
                    <h2 style='color: #e67e22;'>Nuevo Feedback Detectado</h2>
                    <p>Hola Bárbara,</p>
                    <p>Se ha generado un nuevo comentario/feedback en el sistema de horarios:</p>
                    <ul>
                        <li><strong>Local:</strong> $nombreSucursal</li>
                        <li><strong>Feedback sobre:</strong> $nombreVendedor</li>
                        <li><strong>Semana:</strong> $semana</li>
                    </ul>
                    <p>Por favor, revisá la plataforma de Gestión de Horarios para ver el detalle del mensaje.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='color: #666; font-size: 12px;'>
                        Mensaje automático del Sistema de Horarios XL.
                    </p>
                </div>";

            $this->mail->clearAddresses();
            $this->mail->addAddress('barbara.balado@xl.com.ar');
            
            $this->mail->isHTML(true);
            $this->mail->Subject = $asunto;
            $this->mail->Body = $cuerpo;
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Error al enviar notificación de feedback: " . $e->getMessage());
            return false;
        }
    }
}
