<?php

// WordPress Admin - SMTP Credentials
// Last update: 2026-01-15


// Add these lines to wp-config.php file
/** SMTP Credentials **/
// define('SMTP_HOST', 'smtp.yourservice.com');
// define('SMTP_PORT', 587);
// define('SMTP_USER', 'your_username');
// define('SMTP_PASS', 'your_password');
// define('SMTP_FROM', 'your_email@example.com');
// // define('SMTP_NAME', 'Your Name');


if (defined('SMTP_HOST') && defined('SMTP_USER') && defined('SMTP_PASS')) {
    add_action(hook_name: 'phpmailer_init', callback: 'phpmailer_credentials', priority: 10, accepted_args: 1);
}

function phpmailer_credentials(PHPMailer\PHPMailer\PHPMailer $phpmailer): void
{
    $phpmailer->isSMTP();
    $phpmailer->Host        = SMTP_HOST;
    $phpmailer->SMTPAuth    = true;
    $phpmailer->Port        = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
    $phpmailer->Username    = SMTP_USER;
    $phpmailer->Password    = SMTP_PASS;
    $phpmailer->SMTPSecure  = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    // $phpmailer->From     = SMTP_FROM;
    // $phpmailer->FromName = SMTP_NAME;
}
