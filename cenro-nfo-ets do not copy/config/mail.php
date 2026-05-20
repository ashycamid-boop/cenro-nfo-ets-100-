<?php

use PHPMailer\PHPMailer\PHPMailer;

function getMailConfig(): array
{
    $envUsername = trim((string) getenv('MAIL_USERNAME'));
    $envPassword = trim((string) getenv('MAIL_PASSWORD'));
    $envFromEmail = trim((string) getenv('MAIL_FROM_EMAIL'));
    $envFromName = trim((string) getenv('MAIL_FROM_NAME'));

    return [
        // Gmail SMTP settings.
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => PHPMailer::ENCRYPTION_STARTTLS,
        'timeout' => 10,

        // Replace with the Gmail account that will send the OTP emails.
        'username' => $envUsername !== '' ? $envUsername : 'cenro.system@gmail.com',

        // Use a 16-digit Google App Password here, not your normal Gmail password.
        'password' => $envPassword !== '' ? $envPassword : 'wtyj npso rjff zfds',

        // Usually the same as the Gmail username above.
        'from_email' => $envFromEmail !== '' ? $envFromEmail : ($envUsername !== '' ? $envUsername : 'cenro.system@gmail.com'),

        // Sender name shown in the email inbox.
        'from_name' => $envFromName !== '' ? $envFromName : 'CENRO System Security',
    ];
}
