// WordPress Admin - Customize Login Screen
// Last update: 2024-07-30


// Customize Login Screen
add_action($hook_name = 'login_enqueue_scripts', $callback = 'login_screen_customize', $priority = 10, $accepted_args = 1);

function login_screen_customize()
{
    ?>
	<style type="text/css">
		/* Logo */
		.login h1 a {
			background-image: url("<?php echo get_option($option = 'siteurl', $default_value = false); ?>/wp-content/uploads/logo.png") !important;
			height: 80px !important;
			width: auto !important;
			background-size: contain !important;
			display: block !important;
			text-indent: -9999px;
		}

		/* Form styles */
		.login #loginform,
		.login #wfls-prompt-overlay {
			background-color: #ECEAE3;
			border: 1px solid #6565651A;
			border-radius: 10px;
		}
		.login .privacy-policy-link {
			color: #AB8C6C !important;
		}
		.login .privacy-policy-link:hover {
			color: #BCA38A !important;
		}
		.login .input[type="text"],
		.login .input[type="password"]			{
			background-color: #6565651A !important;
			border: 1px solid #6565651A !important;
		}

		/* Background color */
		body.login {
			background-color: #F2F0EB !important;
		}

		/* Buttons */
		.login .button,
		.login .two-factor-email-resend .button {
			border: 2px solid #262626 !important;
			padding: 10px 20px !important;
			border-radius: 0 !important;
			transition: all 0.3s !important;
		}
		.login .button {
			color: #FFFFFF !important;
			background-color: #262626 !important;
		}
		.login .two-factor-email-resend .button {
			color: #262626 !important;
			background-color: transparent !important;
		}
		.login .two-factor-email-resend .button:hover {
			color: #FFFFFF !important;
			background-color: #262626 !important;
		}
		.login .wp-hide-pw,
		.login .hide-if-no-js {
			display: none !important;
		}

		/* Hide reCAPTCHA v3 */
		/* .grecaptcha-badge {
			visibility: hidden !important;
		} */
	</style>
	<script type="text/javascript">
		document.addEventListener("DOMContentLoaded", function() {
			const wpLink = document.querySelector(".login h1 a");
			if (wpLink) {
				wpLink.href = "";
			}
		});
	</script>
	<?php
}
