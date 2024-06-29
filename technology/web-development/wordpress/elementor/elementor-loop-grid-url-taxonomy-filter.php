<?php

// Elementor - Create custom URLs that on load scrolls down to the Loop Grid and selects a specific Taxonomy Filter
// Last update: 2024-06-15

if (is_plugin_active('elementor/elementor.php')) {

    add_action($hook_name = 'wp_footer', $callback = 'add_custom_filter_script', $priority = 10, $accepted_args = 1);

    function add_custom_filter_script()
    {
        ?>
        <script type="text/javascript">
        window.addEventListener('load', function() {
            // Settings
            const anchorId = 'products';

            // Function to get URL parameters, handling both fragment and query parameters
            function getUrlParameter(name) {
                const url = window.location.href;

                // Look for both fragment (#) and query parameters (?)
                const queryString = url.split('#')[1] || '';
                const queryParams = queryString.split('?')[1] || '';
                const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
                const results = regex.exec('?' + queryParams);

                return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
            }

            // Function to smoothly scroll to an element
            function scrollToElement(element) {
                window.scrollTo({
                    behavior: 'smooth',
                    top: element.getBoundingClientRect().top + window.scrollY - 100,
                });
            }

            // Get the filter parameter from the URL
            const filterValue = getUrlParameter('filter');

            if (filterValue) {
                // Scroll to the taxonomy filter section
                const filterSection = document.getElementById(anchorId);
                if (filterSection) {
                    scrollToElement(filterSection);

                    // Wait for a short period to ensure the scroll animation completes
                    setTimeout(function() {
                        // Trigger the filter selection
                        const filterSelector = `button[data-filter="${filterValue}"]`;
                        const filterElement = document.querySelector(filterSelector);

                        if (filterElement) {
                            filterElement.click();
                        }
                    }, 100); // Increased timeout to ensure elements are fully rendered
                }
            }
        });
        </script>
        <?php
    }

}
