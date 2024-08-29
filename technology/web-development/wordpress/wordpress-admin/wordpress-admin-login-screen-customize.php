<?php
// WordPress Admin - Customize Login Screen
// Last update: 2024-08-18


// Customize Login Screen
add_action($hook_name = 'login_enqueue_scripts', $callback = 'login_screen_customize', $priority = 10, $accepted_args = 1);

function login_screen_customize()
{
    ?>
	<style type="text/css">
		:root {
			/* Colors */
			--color-cararra: #ECEAE3;
			--color-dove-gray-light: #6565651A;
			--color-mine-shaft: #262626;
			--color-mongoose: #BCA38A;
			--color-pampas: #F2F0EB;
			--color-sandal: #AB8C6C;
			--color-white: #FFFFFF;
		}

		/* Logo */
		.login h1 a {
			background-image: url("<?php echo get_option($option = 'siteurl', $default_value = false); ?>/wp-content/uploads/logo.png") !important;
			height: 80px !important;
			width: auto !important;
			background-size: contain !important;
			display: block !important;
			text-indent: -9999px;
		}

		/* Background color */
		body.login {
			background-color: var(--color-pampas) !important;
		}

		/* Form styles */
		.login #loginform,
		.login #wfls-prompt-overlay {
			background-color: var(--color-cararra);
			border: 1px solid var(--color-dove-gray-light);
			border-radius: 10px;
		}
		.login .privacy-policy-link {
			color: var(--color-sandal) !important;
		}
		.login .privacy-policy-link:hover {
			color: var(--color-mongoose) !important;
		}
		.login .input[type="text"],
		.login .input[type="password"]			{
			background-color: var(--color-dove-gray-light) !important;
			border: 1px solid var(--color-dove-gray-light) !important;
		}

		/* Buttons */
		.login .button,
		.login .two-factor-email-resend .button {
			border: 2px solid var(--color-mine-shaft) !important;
			padding: 10px 20px !important;
			border-radius: 0 !important;
			transition: all 0.3s !important;
			color: var(--color-white) !important;
			background-color: var(--color-mine-shaft) !important;
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
