<?php
use PHPMailer\PHPMailer\PHPMailer;

// Loads PHPMailer (and the new-item notification helper)
require_once __DIR__ . '/../../notify.php';

/**
 * Returns a PHPMailer object already set up with the Gmail SMTP settings
 * from notify-config.php.
 */
if (!function_exists('createMailer')) {
    function createMailer(): PHPMailer
    {
        $cfg  = require __DIR__ . '/../../notify-config.php';
        $smtp = $cfg['smtp'];

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $smtp['port'];
        $mail->Timeout    = 10;   // do not keep the user waiting if Gmail is slow
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($smtp['from_email'], $smtp['from_name']);

        return $mail;
    }
}

/**
 * Sends one email. Returns true on success, false on failure.
 */
if (!function_exists('sendMail')) {
    function sendMail(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        string $toName = ''
    ): bool {
        try {
            $mail = createMailer();
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);
            $mail->send();

            return true;
        } catch (Throwable $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Sign-in alert, used by api/auth/google-callback.php.
 * Never throws: a failed email must not block the login.
 */
if (!function_exists('muSendLoginNotification')) {
    function muSendLoginNotification(string $email, string $name, string $provider = 'Google'): bool
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

            $when    = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('F j, Y g:i A');
            $ip      = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $browser = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'), 0, 160);
            $name    = $name !== '' ? $name : 'there';

            $html = "
                <div style='font-family:Arial,sans-serif;max-width:560px;margin:auto'>
                  <h2 style='color:#08265c'>New sign-in to MU Lost &amp; Found</h2>
                  <p>Hi {$h($name)},</p>
                  <p>Your account was just used to sign in.</p>
                  <table cellpadding='6' style='border-collapse:collapse;width:100%'>
                    <tr><td><b>Method</b></td><td>{$h($provider)}</td></tr>
                    <tr><td><b>Time</b></td><td>{$h($when)} (Philippine Time)</td></tr>
                    <tr><td><b>IP address</b></td><td>{$h($ip)}</td></tr>
                    <tr><td valign='top'><b>Device</b></td><td>{$h($browser)}</td></tr>
                  </table>
                  <p style='color:#475569'>If this was you, no action is needed.
                     If you do not recognize this sign-in, please secure your Google account.</p>
                  <p style='color:#94a3b8;font-size:12px'>MU Lost &amp; Found</p>
                </div>";

            $text = "New sign-in to MU Lost & Found\n\nMethod: {$provider}\nTime: {$when}\nIP: {$ip}\n\n"
                  . "If this was you, no action is needed. If not, please secure your Google account.";

            return sendMail($email, 'New sign-in to your MU Lost & Found account', $html, $text, $name);
        } catch (Throwable $e) {
            error_log('Login notification error: ' . $e->getMessage());
            return false;
        }
    }
}