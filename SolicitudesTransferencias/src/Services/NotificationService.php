<?php
// src/Services/NotificationService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class NotificationService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = Env::get('HOST_EMAIL_EGRESOS', 'smtp.gmail.com');
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = Env::get('USER_EMAIL_EGRESOS');
            $this->mailer->Password = Env::get('PASS_EMAIL_EGRESOS');
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = Env::get('PORT_EMAIL_EGRESOS', 587);
            $this->mailer->CharSet = 'UTF-8';

            $this->mailer->setFrom(Env::get('USER_EMAIL_EGRESOS'), 'Solicitudes de Transferencias - XL');

        } catch (Exception $e) {
            error_log("Error al configurar PHPMailer: {$this->mailer->ErrorInfo}");
        }
    }

    public function sendEmail($to, $subject, $body)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->addAddress($to);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Error al enviar email a {$to}: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    /**
     * Enviar email de nueva solicitud creada.
     */
    public function notificarNuevaSolicitud($solicitud) {
        $articulos = json_decode($solicitud['ARTICULOS'], true);
        $articulosHtml = '';
        foreach ($articulos as $art) {
            $articulosHtml .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;'>{$art['cod_articu']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;'>{$art['descripcion']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;'>{$art['rubro']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;text-align:center;'>{$art['cantidad']}</td>
            </tr>";
        }

        $body = "
        <div style='font-family:Inter,Arial,sans-serif;max-width:600px;margin:0 auto;'>
            <div style='background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:24px;border-radius:12px 12px 0 0;'>
                <h2 style='color:#fff;margin:0;font-size:20px;'>📦 Nueva Solicitud de Transferencia #{$solicitud['ID']}</h2>
            </div>
            <div style='background:#fff;padding:24px;border:1px solid #e2e8f0;'>
                <table style='width:100%;border-collapse:collapse;margin-bottom:16px;'>
                    <tr><td style='padding:6px 0;color:#64748b;width:40%;'>Tipo:</td><td style='padding:6px 0;font-weight:600;'>{$solicitud['TIPO_TRANSFERENCIA']}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'>Usuario:</td><td style='padding:6px 0;font-weight:600;'>{$solicitud['USUARIO']}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'>Origen:</td><td style='padding:6px 0;font-weight:600;'>{$solicitud['ORIGEN']}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'>Destino:</td><td style='padding:6px 0;font-weight:600;'>{$solicitud['DESTINO']}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'>Estado:</td><td style='padding:6px 0;'><span style='background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;'>PENDIENTE</span></td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'>Observaciones:</td><td style='padding:6px 0;'>{$solicitud['OBSERVACIONES']}</td></tr>
                </table>
                <h3 style='font-size:15px;color:#334155;margin:16px 0 8px;'>Artículos solicitados:</h3>
                <table style='width:100%;border-collapse:collapse;'>
                    <thead>
                        <tr style='background:#f1f5f9;'>
                            <th style='padding:8px 12px;text-align:left;font-size:12px;color:#64748b;'>Código</th>
                            <th style='padding:8px 12px;text-align:left;font-size:12px;color:#64748b;'>Descripción</th>
                            <th style='padding:8px 12px;text-align:left;font-size:12px;color:#64748b;'>Rubro</th>
                            <th style='padding:8px 12px;text-align:center;font-size:12px;color:#64748b;'>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>{$articulosHtml}</tbody>
                </table>
                <div style='margin-top: 24px; text-align: center;'>
                    <a href='https://app.xl.com.ar/comercial/SolicitudesTransferencias/public/solicitud/detalle/{$solicitud['ID']}?mode=logistics' 
                       style='background-color: #6366f1; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 700; display: inline-block;'>
                        🔍 Gestionar Solicitud (Logística)
                    </a>
                </div>
            </div>
            <div style='background:#f8fafc;padding:16px 24px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0;border-top:none;'>
                <p style='color:#94a3b8;font-size:12px;margin:0;text-align:center;'>Sistema de Solicitudes de Transferencias - XL Extra Large</p>
            </div>
        </div>";

        $emails = ['lucas.navarro@xl.com.ar', 'pedidos@xl.com.ar', 'tamara.lavorgna@xl.com.ar'];
        $destinatarios = Env::get('EMAIL_TRANSFERENCIAS_DESTINATARIOS', '');
        if (!empty($destinatarios)) {
            $extra = array_filter(array_map('trim', explode(',', $destinatarios)));
            $emails = array_unique(array_merge($emails, $extra));
        }

        foreach ($emails as $email) {
            $this->sendEmail($email, "Nueva Solicitud de Transferencia #{$solicitud['ID']} - {$solicitud['TIPO_TRANSFERENCIA']}", $body);
        }

        return true;
    }

    /**
     * Enviar email de solicitud finalizada.
     */
    public function notificarSolicitudFinalizada($solicitud) {
        $body = "
        <div style='font-family:Inter,Arial,sans-serif;max-width:600px;margin:0 auto;'>
            <div style='background:linear-gradient(135deg,#059669 0%,#10b981 100%);padding:24px;border-radius:12px 12px 0 0;'>
                <h2 style='color:#fff;margin:0;font-size:20px;'>✅ Solicitud de Transferencia #{$solicitud['ID']} Finalizada</h2>
            </div>
            <div style='background:#fff;padding:24px;border:1px solid #e2e8f0;'>
                <table style='width:100%;border-collapse:collapse;'>
                    <tr><td style='padding:6px 0;color:#64748b;width:40%;'>Tipo:</td><td style='padding:6px 0;font-weight:600;'>{$solicitud['TIPO_TRANSFERENCIA']}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'>Usuario:</td><td style='padding:6px 0;font-weight:600;'>{$solicitud['USUARIO']}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'>Origen:</td><td style='padding:6px 0;font-weight:600;'>{$solicitud['ORIGEN']}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'>Destino:</td><td style='padding:6px 0;font-weight:600;'>{$solicitud['DESTINO']}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'>N° Transferencia:</td><td style='padding:6px 0;font-weight:700;color:#059669;font-size:18px;'>{$solicitud['NRO_TRANSFERENCIA']}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'>Estado:</td><td style='padding:6px 0;'><span style='background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;'>FINALIZADA</span></td></tr>
                </table>
                <div style='margin-top: 24px; text-align: center;'>
                    <a href='https://app.xl.com.ar/comercial/SolicitudesTransferencias/public/solicitud/detalle/{$solicitud['ID']}?mode=logistics' 
                       style='background-color: #059669; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 700; display: inline-block;'>
                        🔍 Ver Detalle en Logística
                    </a>
                </div>
            </div>
            <div style='background:#f8fafc;padding:16px 24px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0;border-top:none;'>
                <p style='color:#94a3b8;font-size:12px;margin:0;text-align:center;'>Sistema de Solicitudes de Transferencias - XL Extra Large</p>
            </div>
        </div>";

        $emails = ['lucas.navarro@xl.com.ar', 'pedidos@xl.com.ar', 'tamara.lavorgna@xl.com.ar'];
        $destinatarios = Env::get('EMAIL_TRANSFERENCIAS_DESTINATARIOS', '');
        if (!empty($destinatarios)) {
            $extra = array_filter(array_map('trim', explode(',', $destinatarios)));
            $emails = array_unique(array_merge($emails, $extra));
        }

        foreach ($emails as $email) {
            $this->sendEmail($email, "Solicitud #{$solicitud['ID']} Finalizada - N° Transferencia: {$solicitud['NRO_TRANSFERENCIA']}", $body);
        }

        return true;
    }
}
