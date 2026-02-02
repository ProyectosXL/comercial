<?php
// src/Services/NotificationService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar el autoloader de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

class NotificationService {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true); // true habilita excepciones

        try {
            // Configuración del servidor SMTP (tomada del .env)
            $this->mailer->isSMTP();
            $this->mailer->Host       = Env::get('HOST_EMAIL', 'smtp.gmail.com');
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = Env::get('USER_EMAIL2');
            $this->mailer->Password   = Env::get('PASS_EMAIL2');
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = 587;
            $this->mailer->CharSet    = 'UTF-8';

            // Remitente
            $this->mailer->setFrom(Env::get('USER_EMAIL2'), 'Sistema de Auditorías XL');

        } catch (Exception $e) {
            // Manejar error de configuración si es necesario
            error_log("Error al configurar PHPMailer: {$this->mailer->ErrorInfo}");
        }
    }

    public function sendEmail($to, $subject, $body) {
        try {
                        // --- CORRECCIÓN: LIMPIAR DESTINATARIOS PREVIOS ---
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            // Destinatario
            $this->mailer->addAddress($to);

            // Contenido
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            $this->mailer->AltBody = strip_tags($body); // Versión de texto plano

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            die("ERROR DETALLADO DE PHPMailer: " . $this->mailer->ErrorInfo);
            error_log("Error al enviar email a {$to}: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
}