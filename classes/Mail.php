<?php
/**
 * Mail Class - Dynamic Email Dispatcher
 * Uses PHPMailer and site settings for SMTP delivery
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

class Mail {
    /**
     * Send email using selected driver (SMTP or PHP Mail)
     */
    public static function send($to, $subject, $body, $fromName = null, $fromEmail = null) {
        $settings = new Settings();
        // Priority: 1. ENV Constant, 2. Settings DB
        $driver = defined('MAIL_DRIVER') && MAIL_DRIVER ? MAIL_DRIVER : $settings->get('email_driver', 'mail');
        
        $fromEmail = $fromEmail ?? (defined('MAIL_FROM_ADDRESS') && MAIL_FROM_ADDRESS ? MAIL_FROM_ADDRESS : $settings->get('smtp_from_email', 'noreply@wapi.com'));
        $fromName = $fromName ?? (defined('MAIL_FROM_NAME') && MAIL_FROM_NAME ? MAIL_FROM_NAME : $settings->get('smtp_from_name', 'WAPI'));

        if ($driver === 'mail') {
            // "Pahale wala" (Previous) PHP mail() method
            $headers = "From: " . $fromName . " <" . $fromEmail . ">\r\n";
            $headers .= "Reply-To: " . $fromEmail . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            if (@mail($to, $subject, $body, $headers, "-f " . $fromEmail)) {
                return ['success' => true, 'message' => 'Message sent via PHP Mail'];
            } else {
                return ['success' => false, 'message' => 'PHP Mail failed. Check server configuration.'];
            }
        }

        // SMTP Method (Gmail, Hostinger, etc.)
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = defined('MAIL_HOST') && MAIL_HOST ? MAIL_HOST : $settings->get('smtp_host', 'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = defined('MAIL_USERNAME') && MAIL_USERNAME ? MAIL_USERNAME : $settings->get('smtp_username', '');
            $mail->Password   = defined('MAIL_PASSWORD') && MAIL_PASSWORD ? MAIL_PASSWORD : $settings->get('smtp_password', '');
            $mail->SMTPSecure = defined('MAIL_ENCRYPTION') && MAIL_ENCRYPTION ? MAIL_ENCRYPTION : $settings->get('smtp_encryption', 'tls'); 
            $mail->Port       = defined('MAIL_PORT') && MAIL_PORT ? MAIL_PORT : $settings->get('smtp_port', 587);

            // Recipients
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return ['success' => true, 'message' => 'Message sent via SMTP'];
        } catch (Exception $e) {
            error_log("Mail Error: {$mail->ErrorInfo}");
            return ['success' => false, 'message' => "SMTP Error: {$mail->ErrorInfo}"];
        }
    }
}
