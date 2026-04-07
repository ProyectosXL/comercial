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
            // Usamos la configuración de Gmail que ya funciona en Supervision
            $this->mail->Host = 'smtp.gmail.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'notificaciones@xl.com.ar';
            $this->mail->Password = 'yvsuiewmcztagevs'; 
            $this->mail->SMTPSecure = 'tls';
            $this->mail->Port = 587;
            $this->mail->CharSet = 'UTF-8';
            $this->mail->setFrom($this->mail->Username, 'Notificaciones Horarios XL');
        } catch (Exception $e) {
            error_log("Error al configurar PHPMailer: " . $this->mail->ErrorInfo);
        }
    }

    /**
     * Obtiene datos (Nombre y Mail) de una sucursal
     */
    private function getDatosSucursal($nroSucursal) {
        $nro = intval($nroSucursal);
        $datos = [
            'nombre' => "Sucursal $nro",
            'mail' => null
        ];

        // 1. Buscamos el MAIL en DIRECCIONARIO (XL-LAKERBIS)
        $connLoc = getConexionLocales();
        if ($connLoc) {
            $sql = "SELECT DESC_SUCURSAL, MAIL FROM DIRECCIONARIO WHERE NRO_SUCURSAL = ?";
            $stmt = sqlsrv_query($connLoc, $sql, [$nro]);
            if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $datos['mail'] = trim($row['MAIL']);
                if (!empty(trim($row['DESC_SUCURSAL']))) {
                    $datos['nombre'] = trim($row['DESC_SUCURSAL']);
                }
            }
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($connLoc);
        }

        // 2. Si el nombre sigue siendo el genérico, buscamos el nombre real en RRHH (LAKERS_CORP_SA)
        // Usamos el código formateado tipo LOC0XX
        if (strpos($datos['nombre'], 'Sucursal') !== false) {
            $connCorp = getConexionCorp();
            if ($connCorp) {
                $codigoBuscado = 'LOC' . str_pad($nro, 3, '0', STR_PAD_LEFT); // ej: LOC055
                $sqlCorp = "SELECT DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE COD_DEPARTAMENTO = ? OR ID_DEPARTAMENTO = ?";
                $stmtCorp = sqlsrv_query($connCorp, $sqlCorp, [$codigoBuscado, $nro]);
                if ($stmtCorp && $rowCorp = sqlsrv_fetch_array($stmtCorp, SQLSRV_FETCH_ASSOC)) {
                    $datos['nombre'] = trim($rowCorp['DESC_DEPARTAMENTO']);
                }
                if ($stmtCorp) sqlsrv_free_stmt($stmtCorp);
                sqlsrv_close($connCorp);
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
            $conn = getConexionCorp(); // Usamos la conexión a RRHH
            if (!$conn) return $nombre;

            // Buscamos en LAKERS_CORP_SA (la base ya viene definida en la conexión)
            $sql = "SELECT TOP 1 (TRIM(NOMBRE) + ' ' + TRIM(APELLIDO)) as NOMBRE_COMPLETO 
                    FROM RO_LEGAJOS_PERSONAL 
                    WHERE BLOQUE = ? OR NRO_LEGAJO = ?";
            
            $stmt = sqlsrv_query($conn, $sql, [trim($codVendedor), trim($codVendedor)]);
            
            if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $nombre = trim($row['NOMBRE_COMPLETO']);
            }
            
            if ($stmt) sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);
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
                        <li><strong>Persona que lo generó:</strong> $nombreVendedor</li>
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
