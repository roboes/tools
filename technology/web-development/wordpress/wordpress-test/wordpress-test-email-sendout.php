<?php

// WordPress Test - Email Sendout Test
// Last update: 2024-07-07

// Function to send a test email
function send_test_email()
{
    $to = 'email@website.com';
    $subject = 'Test Email from WordPress';
    $message = 'This is a test email sent from WordPress using the wp_mail function.';
    $headers = array('Content-Type: text/html; charset=UTF-8');

    // Send the email
    if (wp_mail($to = $to, $subject = $subject, $message = $message, $headers = $headers)) {
        echo 'Test email sent successfully.';
    } else {
        echo 'Failed to send test email.';
    }
}

send_test_email();
