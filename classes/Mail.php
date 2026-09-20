<?php
/**
 * Mail Class - Dynamic Email Dispatcher
 * Supports SMTP (with PHPMailer) and PHP Mail() with automatic fallback
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

class Mail {
    /**
     * Send email using selected driver (SMTP or PHP Mail) with automatic fallback
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email HTML content
     * @param string|null $fromName Sender display name
     * @param string|null $fromEmail Sender email address
     * @param string|null $replyToEmail Optional Reply-To email address
     * @param string|null $replyToName Optional Reply-To display name
     * @return array ['success' => bool, 'message' => string]
     */
    public static function send($to, $subject, $body, $fromName = null, $fromEmail = null, $replyToEmail = null, $replyToName = null) {
        $settings = new Settings();

        // 1. Resolve SMTP settings (Database settings take priority, fallback to constants / .env)
        $dbHost      = trim((string)$settings->get('smtp_host', ''));
        $dbPort      = trim((string)$settings->get('smtp_port', ''));
        $dbUser      = trim((string)$settings->get('smtp_username', ''));
        $dbPass      = trim((string)$settings->get('smtp_password', ''));
        $dbSecure    = trim((string)$settings->get('smtp_encryption', ''));
        $dbFromName  = trim((string)$settings->get('smtp_from_name', ''));
        $dbFromEmail = trim((string)$settings->get('smtp_from_email', ''));
        $dbDriver    = trim((string)$settings->get('email_driver', ''));

        $smtpHost   = !empty($dbHost) ? $dbHost : (defined('SMTP_HOST') && SMTP_HOST ? SMTP_HOST : 'smtp.gmail.com');
        $smtpUser   = !empty($dbUser) ? $dbUser : (defined('SMTP_USER') ? SMTP_USER : '');
        $smtpPass   = !empty($dbPass) ? $dbPass : (defined('SMTP_PASS') ? SMTP_PASS : '');
        $smtpPort   = !empty($dbPort) ? (int)$dbPort : (defined('SMTP_PORT') && SMTP_PORT ? (int)SMTP_PORT : 587);
        $smtpSecure = !empty($dbSecure) ? $dbSecure : (defined('SMTP_SECURE') && SMTP_SECURE ? SMTP_SECURE : ($smtpPort == 465 ? 'ssl' : 'tls'));

        // 2. Resolve From Name & Email
        if (empty($fromEmail)) {
            $fromEmail = !empty($dbFromEmail) ? $dbFromEmail : (!empty($smtpUser) ? $smtpUser : 'noreply@wapi.com');
        }
        if (empty($fromName)) {
            $fromName = !empty($dbFromName) ? $dbFromName : (defined('MAIL_FROM_NAME') && MAIL_FROM_NAME ? MAIL_FROM_NAME : 'WAPI');
        }

        // 3. Determine primary driver
        if (!empty($dbDriver)) {
            $driver = $dbDriver;
        } elseif (!empty($smtpUser) && !empty($smtpPass)) {
            $driver = 'smtp';
        } else {
            $driver = 'mail';
        }

        // 4. Dispatch based on driver with automatic fallback
        if ($driver === 'smtp') {
            $result = self::sendViaSmtp($to, $subject, $body, $fromName, $fromEmail, $replyToEmail, $replyToName, $smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpSecure);
            if ($result['success']) {
                return $result;
            }

            // SMTP Failed — log and attempt fallback to PHP mail()
            error_log("Mail Notice: Primary SMTP failed (" . $result['message'] . "). Attempting PHP mail() fallback...");
            $fallback = self::sendViaPhpMail($to, $subject, $body, $fromName, $fromEmail, $replyToEmail, $replyToName);
            if ($fallback['success']) {
                return [
                    'success' => true,
                    'message' => 'Sent via PHP Mail fallback (SMTP reported: ' . $result['message'] . ')'
                ];
            }

            return [
                'success' => false,
                'message' => "Delivery failed: {$result['message']} (Fallback: {$fallback['message']})"
            ];
        } else {
            // Driver is PHP mail()
            $result = self::sendViaPhpMail($to, $subject, $body, $fromName, $fromEmail, $replyToEmail, $replyToName);
            if ($result['success']) {
                return $result;
            }

            // PHP mail failed — if SMTP credentials exist, attempt SMTP fallback
            if (!empty($smtpUser) && !empty($smtpPass)) {
                error_log("Mail Notice: PHP mail() failed (" . $result['message'] . "). Attempting SMTP fallback...");
                $fallback = self::sendViaSmtp($to, $subject, $body, $fromName, $fromEmail, $replyToEmail, $replyToName, $smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpSecure);
                if ($fallback['success']) {
                    return [
                        'success' => true,
                        'message' => 'Sent via SMTP fallback (PHP Mail reported: ' . $result['message'] . ')'
                    ];
                }
                return [
                    'success' => false,
                    'message' => "Delivery failed: {$fallback['message']} (PHP Mail: {$result['message']})"
                ];
            }

            return $result;
        }
    }

    /**
     * Send email via PHPMailer SMTP
     */
    public static function sendViaSmtp($to, $subject, $body, $fromName, $fromEmail, $replyToEmail, $replyToName, $host, $port, $user, $pass, $secure) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $user;
            $mail->Password   = $pass;
            $mail->SMTPSecure = ($secure === 'none' || empty($secure)) ? '' : $secure;
            if (empty($secure) || $secure === 'none') {
                $mail->SMTPAutoTLS = false;
            }
            $mail->Port       = $port;
            $mail->Timeout    = 12;

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            if (!empty($replyToEmail)) {
                $mail->addReplyTo($replyToEmail, $replyToName ?: $replyToEmail);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return ['success' => true, 'message' => 'Message sent successfully via SMTP'];
        } catch (Exception $e) {
            $errorInfo = $mail->ErrorInfo;
            if (stripos($errorInfo, 'BadCredentials') !== false || stripos($errorInfo, 'Username and Password not accepted') !== false) {
                $errorInfo = "Google SMTP authentication failed (535 Bad Credentials). If using Gmail, please create a new App Password at myaccount.google.com/apppasswords and update in Email Settings.";
            }
            error_log("Mail SMTP Error: {$errorInfo}");
            return ['success' => false, 'message' => "SMTP Error: {$errorInfo}"];
        }
    }

    /**
     * Send email via native PHP mail()
     */
    public static function sendViaPhpMail($to, $subject, $body, $fromName, $fromEmail, $replyToEmail, $replyToName) {
        $headers  = "From: " . $fromName . " <" . $fromEmail . ">\r\n";
        if (!empty($replyToEmail)) {
            $headers .= "Reply-To: " . ($replyToName ? "{$replyToName} <{$replyToEmail}>" : $replyToEmail) . "\r\n";
        } else {
            $headers .= "Reply-To: " . $fromEmail . "\r\n";
        }
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        // Try with -f parameter first
        $sent = @mail($to, $subject, $body, $headers, "-f " . $fromEmail);
        if (!$sent) {
            $sent = @mail($to, $subject, $body, $headers);
        }

        if ($sent) {
            return ['success' => true, 'message' => 'Message sent via PHP Mail'];
        }

        return ['success' => false, 'message' => 'PHP Mail function failed to dispatch. Server MTA may not be configured.'];
    }
}
