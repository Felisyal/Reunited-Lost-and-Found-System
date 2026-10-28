<?php
// includes/brevo_mailer.php
// Walang secrets dito, ligtas i-commit.

function brevo_env(string $key): string
{
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    if (!empty($_ENV[$key])) return $_ENV[$key];
    if (!empty($_SERVER[$key])) return $_SERVER[$key];

    // Fallback: basahin ang .env sa project root
    static $env = null;
    if ($env === null) {
        $env = [];
        $file = dirname(_DIR_) . '/.env';
        if (is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim(trim($v), "\"'");
            }
        }
    }
    return $env[$key] ?? '';
}

/**
 * Magpadala ng email via Brevo API (HTTPS).
 * @return array ['success' => bool, 'message' => string]
 */
function sendBrevoEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): array
{
    $apiKey      = brevo_env('BREVO_API_KEY');
    $senderEmail = brevo_env('BREVO_SENDER_EMAIL');
    $senderName  = brevo_env('BREVO_SENDER_NAME') ?: 'Reunited PUP';

    if ($apiKey === '' || $senderEmail === '') {
        error_log('Brevo: kulang ang BREVO_API_KEY o BREVO_SENDER_EMAIL');
        return ['success' => false, 'message' => 'Email service is not configured.'];
    }

    $payload = [
        'sender'      => ['name' => $senderName, 'email' => $senderEmail],
        'to'          => [['email' => $toEmail, 'name' => $toName ?: $toEmail]],
        'subject'     => $subject,
        'htmlContent' => $htmlBody,
    ];
    if ($textBody !== '') {
        $payload['textContent'] = $textBody;
    }

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);

    $response = curl_exec($ch);
    $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('Brevo cURL error: ' . $curlErr);
        return ['success' => false, 'message' => 'Could not reach email service.'];
    }

    if ($status >= 200 && $status < 300) {
        return ['success' => true, 'message' => 'Email sent.'];
    }

    error_log("Brevo API error ($status): $response");
    return ['success' => false, 'message' => "Email failed (HTTP $status)."];
}