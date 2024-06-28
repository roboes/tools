<?php

// WordPress Admin - SMTP Credentials
// Last update: 2024-06-28

add_action($hook_name = 'phpmailer_init', $callback = 'phpmailer_settings', $priority = 10, $accepted_args = 1);

function phpmailer_settings($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host = 'smtp.eu.mailgun.org';
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = 587;
    $phpmailer->Username = 'postmaster@website.com';
    $phpmailer->Password = 'password';
    $phpmailer->SMTPSecure = 'tls';
    $phpmailer->From = 'info@website.com';
    // $phpmailer->FromName = 'Your Name';
}
