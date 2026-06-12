<?php
// Send an email via PHP's built-in mail() function.
//
// To use SMTP instead of the system sendmail, either:
//   - Configure sendmail_path / SMTP / smtp_port in php.ini, or
//   - Replace this function body with PHPMailer or Symfony Mailer.
//
// Returns true on success, false on failure.
function sendMail(string $to, string $subject, string $body, string $replyTo = '', string $fromName = ''): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $fromName = $fromName ?: 'Website';
    $fromAddr = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    $encodedSubject  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: {$encodedFromName} <{$fromAddr}>\r\n";

    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers .= "Reply-To: {$replyTo}\r\n";
    }

    $headers .= "X-Mailer: PHP\r\n";

    return (bool) mail($to, $encodedSubject, wordwrap($body, 70, "\r\n"), $headers);
}
