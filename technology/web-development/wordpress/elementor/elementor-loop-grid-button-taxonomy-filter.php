<?php

// Elementor - Create a CSS ID to be used on buttons, where on click it scrolls down to the Loop Grid and selects a specific Taxonomy Filter
// Last update: 2024-06-13

if (is_plugin_active('elementor/elementor.php')) {

    add_action($hook_name = 'wp_footer', $callback = 'elementor_loop_grid_button_taxonomy_filter', $priority = 10, $accepted_args = 1);

    function elementor_loop_grid_button_taxonomy_filter()
    {
        ?>
		<script type="text/javascript">
		document.addEventListener("DOMContentLoaded", function() {
			// Settings
			const buttonConfigs = {
				'button-filter-specialty-coffees-1-de': {
					anchorId: 'products',
					filterValue: 'specialty-coffees-de'
				},
				'button-filter-specialty-coffees-1-en': {
					anchorId: 'products',
					filterValue: 'specialty-coffees-en'
				},
				'button-filter-specialty-coffees-2-de': {
					anchorId: 'products',
					filterValue: 'specialty-coffees-de'
				},
				'button-filter-specialty-coffees-2-en': {
					anchorId: 'products',
					filterValue: 'specialty-coffees-en'
				},
				'button-filter-specialty-coffees-3-de': {
					anchorId: 'products',
					filterValue: 'specialty-coffees-de'
				},
				'button-filter-specialty-coffees-3-en': {
					anchorId: 'products',
					filterValue: 'specialty-coffees-en'
				},
				'button-filter-specialty-trainings-de': {
					anchorId: 'products',
					filterValue: 'trainings-de'
				},
				'button-filter-specialty-trainings-en': {
					anchorId: 'products',
					filterValue: 'trainings-en'
				},
				'button-filter-specialty-accessories-de': {
					anchorId: 'products',
					filterValue: 'accessories-de'
				},
				'button-filter-specialty-accessories-en': {
					anchorId: 'products',
					filterValue: 'accessories-en'
				},
			};

			// Function to smoothly scroll to an element
			function scrollToElement(element) {
				window.scrollTo({
					behavior: 'smooth',
					top: element.getBoundingClientRect().top + window.scrollY - 100,
				});
			}

			// Function to handle filter selection based on filter value
			function handleFilterSelection(anchorId, filterValue) {
				// Scroll to the taxonomy filter section
				var filterSection = document.getElementById(anchorId);
				if (filterSection) {
					scrollToElement(filterSection);

					// Wait for a short period to ensure the scroll animation completes
					setTimeout(function() {
						// Check if the filter button is already pressed
						var filterSelector = 'button[data-filter="' + filterValue + '"]';
						var filterElement = document.querySelector(filterSelector);
						var ariaPressed = filterElement.getAttribute('aria-pressed');

						if (filterElement && ariaPressed !== 'true') {
							// Trigger the filter selection
							filterElement.click();
						}
					}, 0);
				}
			}

			// Click event handlers for different buttons
			Object.keys(buttonConfigs).forEach(function(buttonClassId) {
				var buttonConfig = buttonConfigs[buttonClassId];
				var filterImageButton = document.getElementById(buttonClassId);
				if (filterImageButton) {
					filterImageButton.addEventListener('click', function(event) {
						event.preventDefault(); // Prevent default anchor behavior if applicable

						// Call function to handle filter selection with specific config
						handleFilterSelection(buttonConfig.anchorId, buttonConfig.filterValue);
					});
				}
			});
		});
		</script>
		<?php
    }

}
