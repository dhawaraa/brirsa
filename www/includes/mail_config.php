<?php
/**
 * Email Configuration and Sending Helper
 * Use PHPMailer for reliable email delivery via SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// SMTP Settings
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587); // 587 for TLS, 465 for SSL
define('SMTP_USER', getenv('SMTP_USER') ?: 'bri.it@mcu.ac.th');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'lofz hwxq kfoz isuu');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'bri.it@mcu.ac.th');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'สถาบันวิจัยพุทธศาสตร์ มจร');

/**
 * Send email helper
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email content (HTML supported)
 * @return bool True on success, false on failure
 */
function sendMail($to, $subject, $body)
{
    if (empty(SMTP_PASS) || SMTP_PASS === 'YOUR_GMAIL_APP_PASSWORD') {
        error_log("Email sending failed: SMTP password not configured.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->Timeout = 10; // Set timeout for mail server connection

        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
