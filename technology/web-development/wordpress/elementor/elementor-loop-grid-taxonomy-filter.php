<?php

// Elementor - Scroll to Loop Grid and activate Taxonomy Filter via button clicks or URL parameters
// Last update: 2026-01-14

if (is_plugin_active('elementor/elementor.php')) {

    // Create a CSS ID to be used on buttons, where on click it scrolls down to the Loop Grid and selects a specific Taxonomy Filter
    add_action(hook_name: 'wp_footer', callback: 'elementor_loop_grid_button_taxonomy_filter', priority: 10, accepted_args: 1);

    function elementor_loop_grid_button_taxonomy_filter(): void
    {
        ?>
        <script type="text/javascript">
        // Settings
        document.addEventListener("DOMContentLoaded", function() {
            const filterMap = {
                'specialty-coffees': 'specialty-coffees',
                'trainings': 'trainings',
                'accessories': 'accessories'
            };

            function scrollToElement(element) {
                window.scrollTo({
                    behavior: 'smooth',
                    top: element.getBoundingClientRect().top + window.scrollY - 100,
                });
            }

            function handleFilterSelection(anchorId, filterValue) {
                const filterSection = document.getElementById(anchorId);
                if (!filterSection) return;

                scrollToElement(filterSection);

                setTimeout(function() {
                    const filterElement = document.querySelector(`button[data-filter="${filterValue}"]`);
                    if (filterElement?.getAttribute('aria-pressed') !== 'true') {
                        filterElement.click();
                    }
                }, 100);
            }

            // Use event delegation on document body
            document.body.addEventListener('click', function(event) {
                const button = event.target.closest('[id^="button-filter-"]');
                if (!button) return;

                const id = button.id;
                const lang = id.endsWith('-de') ? 'de' : id.endsWith('-en') ? 'en' : null;
                if (!lang) return;

                // Extract filter type from ID (e.g., "specialty-coffees" from "button-filter-specialty-coffees-1-de")
                const match = id.match(/^button-filter-([\w-]+?)(?:-\d+)?-(?:de|en)$/);
                if (!match) return;

                const filterType = match[1];
                const filterBase = filterMap[filterType];
                if (!filterBase) return;

                event.preventDefault();
                handleFilterSelection('products', `${filterBase}-${lang}`);
            });
        });
        </script>
        <?php
    }

    // Create custom URLs that on load scrolls down to the Loop Grid and selects a specific Taxonomy Filter
    add_action(hook_name: 'wp_footer', callback: function (): void {
        ?>
        <script type="text/javascript">
        window.addEventListener('load', () => { // Changed from DOMContentLoaded to load
            const ANCHOR_ID = 'products';
            const SCROLL_OFFSET = 100;

            // Simplified parsing: Look for everything after "?filter="
            const urlParts = window.location.hash.split('?filter=');
            const filterValue = urlParts.length > 1 ? urlParts[1] : null;

            if (!filterValue) return;

            const section = document.getElementById(ANCHOR_ID);
            if (!section) return;

            // Wrapped in a slight timeout to ensure Elementor is ready
            setTimeout(() => {
                window.scrollTo({
                    behavior: 'smooth',
                    top: section.getBoundingClientRect().top + window.scrollY - SCROLL_OFFSET
                });

                // Click filter after scroll
                setTimeout(() => {
                    document.querySelector(`button[data-filter="${CSS.escape(filterValue)}"]`)?.click();
                }, 300); 
            }, 200);
        });
        </script>
        <?php
    }, priority: 10, accepted_args: 1);

}
