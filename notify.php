<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (!class_exists(PHPMailer::class)) {
    foreach (['Exception', 'PHPMailer', 'SMTP'] as $f) {
        $path = __DIR__ . "/phpmailer/src/{$f}.php";
        if (!is_file($path)) {
            throw new RuntimeException("PHPMailer file not found: {$path}");
        }
        require_once $path;
    }
}

function notifyCollectEmails(PDO $pdo, array $sources): array
{
    $emails = [];
    foreach ($sources as $src) {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $src['table']) || !preg_match('/^[A-Za-z0-9_]+$/', $src['email_col'])) {
            continue;
        }
        $t = $src['table'];
        $c = $src['email_col'];
        try {
            $rows = $pdo->query("SELECT DISTINCT `{$c}` FROM `{$t}` WHERE `{$c}` IS NOT NULL AND `{$c}` <> ''")
                        ->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $ex) {
            error_log("notify: cannot read {$t}.{$c}: " . $ex->getMessage());
            continue;
        }
        foreach ($rows as $email) {
            $email = strtolower(trim($email));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[$email] = true;
            }
        }
    }
    return array_keys($emails);
}

function notifyNewItem(PDO $pdo, array $item): array
{
    $cfg    = require __DIR__ . '/notify-config.php';
    $smtp   = $cfg['smtp'];
    $result = ['sent' => 0, 'failed' => 0, 'errors' => []];

    $emails = notifyCollectEmails($pdo, $cfg['recipients']);
    if (!$emails) {
        return $result;
    }

    @set_time_limit(180);

    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    $type    = ($item['type'] ?? '') === 'Lost' ? 'Lost' : 'Found';
    $subject = "New {$type} item posted: " . ($item['name'] ?? 'Item');
    $link    = rtrim($cfg['site_url'], '/');

    $html = "
        <div style='font-family:Arial,sans-serif;max-width:560px;margin:auto'>
          <h2 style='color:#08265c'>New {$h($type)} item posted</h2>
          <table cellpadding='6' style='border-collapse:collapse;width:100%'>
            <tr><td><b>Item</b></td><td>{$h($item['name'] ?? '')}</td></tr>
            <tr><td><b>Location</b></td><td>{$h($item['location'] ?? '')}</td></tr>
            <tr><td><b>Date</b></td><td>{$h($item['date'] ?? '')}</td></tr>
            <tr><td valign='top'><b>Details</b></td><td>" . nl2br($h($item['description'] ?? '')) . "</td></tr>
          </table>
          <p><a href='{$h($link)}' style='background:#1049b8;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none'>
             Open MU Lost &amp; Found</a></p>
          <p style='color:#94a3b8;font-size:12px'>You received this because you have an MU Lost &amp; Found account.</p>
        </div>";
    $text = "New {$type} item: " . ($item['name'] ?? '') . "\nLocation: " . ($item['location'] ?? '') .
            "\nDate: " . ($item['date'] ?? '') . "\n\n" . ($item['description'] ?? '') . "\n\n{$link}";

    foreach (array_chunk($emails, max(1, (int) $cfg['batch_size'])) as $batch) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['username'];
            $mail->Password   = $smtp['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $smtp['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($smtp['from_email']);
            foreach ($batch as $addr) {
                $mail->addBCC($addr);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $text;
            $mail->send();

            $result['sent'] += count($batch);
        } catch (Exception $ex) {
            $result['failed'] += count($batch);
            $result['errors'][] = $mail->ErrorInfo;
            error_log('notify: ' . $mail->ErrorInfo);
        }
    }

    return $result;
}