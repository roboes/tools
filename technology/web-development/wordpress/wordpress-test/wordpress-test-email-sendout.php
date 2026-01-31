<?php

// WordPress Test - Email Sendout Test
// Last update: 2026-01-15


// Function to send a test email
function send_test_email()
{
    // Send the email
    if (wp_mail(to:'email@website.com', subject:'Test Email from WordPress', message:'This is a test email sent from WordPress using the wp_mail function.', headers:['Content-Type: text/html; charset=UTF-8'])) {
        echo 'Test email sent successfully.';
    } else {
        echo 'Failed to send test email.';
    }
}

send_test_email();
