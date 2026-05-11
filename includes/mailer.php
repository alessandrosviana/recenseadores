<?php
/**
 * Simple mailer helper.
 * Tries to use PHP's mail() function.
 * Also logs the email to a file for local testing since XAMPP often doesn't have SMTP configured.
 */
function sendEmail($to, $subject, $body)
{
    $headers = "From: no-reply@caudf.gov.br\r\n";
    $headers .= "Reply-To: atendimento@caudf.gov.br\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Try sending real email
    $mailSent = @mail($to, $subject, $body, $headers);

    // Always log to file for development inspection
    $logContent = "--------------------------------------------------\n";
    $logContent .= "DATE: " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "TO: $to\n";
    $logContent .= "SUBJECT: $subject\n";
    $logContent .= "BODY:\n$body\n";
    $logContent .= "STATUS: " . ($mailSent ? "Sent via mail()" : "mail() failed (Check SMTP config)") . "\n";
    $logContent .= "--------------------------------------------------\n\n";

    // Save to emails.log in the project root relative to where this script is called
    // Assuming this is called from /pages/, root is ../
    // Or we find a writable path.
    $logPath = __DIR__ . '/../emails_log.txt';
    file_put_contents($logPath, $logContent, FILE_APPEND);

    return true; // Return true to simulate success for the user UI
}
?>