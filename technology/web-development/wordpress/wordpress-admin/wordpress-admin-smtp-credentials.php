<?php

// WordPress Admin - SMTP Credentials
// Last update: 2026-01-14

if (defined('SMTP_HOST') && defined('SMTP_USER') && defined('SMTP_PASS')) {
    add_action(hook_name: 'phpmailer_init', callback: 'phpmailer_credentials', priority: 10, accepted_args: 1);
}

function phpmailer_credentials(PHPMailer\PHPMailer\PHPMailer $phpmailer): void
{
    $phpmailer->isSMTP();
    $phpmailer->Host       = SMTP_HOST;
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
    $phpmailer->Username   = SMTP_USER;
    $phpmailer->Password   = SMTP_PASS;
    $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
}
