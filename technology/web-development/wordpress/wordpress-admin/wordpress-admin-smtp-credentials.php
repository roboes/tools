<?php

// WordPress Admin - SMTP Credentials
// Last update: 2024-09-11


// Add these lines to wp-config.php file
/** SMTP Credentials **/
// define('SMTP_HOST', 'smtp.yourservice.com');
// define('SMTP_PORT', 587);
// define('SMTP_USER', 'your_username');
// define('SMTP_PASS', 'your_password');
// define('SMTP_FROM', 'your_email@example.com');
// // define('SMTP_NAME', 'Your Name');


add_action($hook_name = 'phpmailer_init', $callback = 'phpmailer_credentials', $priority = 10, $accepted_args = 1);

function phpmailer_credentials($phpmailer)
{
    $phpmailer->isSMTP();
    $phpmailer->Host = SMTP_HOST;
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = SMTP_PORT;
    $phpmailer->Username = SMTP_USER;
    $phpmailer->Password = SMTP_PASS;
    $phpmailer->SMTPSecure = 'tls';
    // $phpmailer->From = SMTP_FROM;
    // $phpmailer->FromName = SMTP_NAME;
}
