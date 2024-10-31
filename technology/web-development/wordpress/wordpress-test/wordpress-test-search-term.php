<?php

// WordPress Test - Search for terms
// Last update: 2024-10-13

function search_pages_for_terms($search_terms, $languages = array())
{
    global $wpdb;

    // Ensure $languages is an array and sanitize language slugs
    $allowed_languages = array_map('sanitize_text_field', $languages);

    // Initialize an empty array to store results
    $results = array();

    // Loop through each search term
    foreach ($search_terms as $search_term) {
        // Query without language filtering, ordering by page ID
        $query = $wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_content
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'page'
            AND (p.post_title LIKE %s OR p.post_content LIKE %s)
            ORDER BY p.ID
        ", '%' . $wpdb->esc_like($search_term) . '%', '%' . $wpdb->esc_like($search_term) . '%');

        // Execute the query and check for errors
        $term_results = $wpdb->get_results($query);

        if ($wpdb->last_error) {
            error_log('Database query error: ' . $wpdb->last_error);
            continue; // Skip this search term on error
        }

        if ($term_results === null) {
            error_log('Query returned null for search term: ' . $search_term);
            continue; // Skip this search term if null
        }

        // Add results to $results array
        $results = array_merge($results, $term_results);
    }

    // Check if there are any results before proceeding
    if (! empty($results)) {
        // Sort the results by page ID
        usort($results, function ($a, $b) {
            return $a->ID - $b->ID;
        });

        // Initialize an array to store filtered results
        $filtered_results = array();

        // Loop through each result to apply language filtering
        foreach ($results as $page) {
            // Get page ID
            $page_id = $page->ID;

            // Get language of the page using Polylang function
            $page_language = (function_exists('pll_get_post_language') && in_array(pll_get_post_language($page_id, 'slug'), pll_languages_list(array('fields' => 'slug')))) ? pll_get_post_language($page_id, 'slug') : '';

            // Check if page language is in allowed languages or if no languages are specified
            if (empty($allowed_languages) || in_array($page_language, $allowed_languages)) {
                // Add page to filtered results
                $filtered_results[] = $page;
            }
        }

        // Check if there are any filtered results to display
        if (! empty($filtered_results)) {
            // Display filtered results
            foreach ($filtered_results as $page) {
                // Loop through each search term to find matches
                foreach ($search_terms as $search_term) {
                    // Get the raw post content
                    $page_content = $page->post_content;

                    // Strip HTML tags from post content
                    $page_content = strip_tags($page_content);

                    // Find occurrences of the search term in the stripped content
                    $matches = array();
                    preg_match_all("/$search_term/", $page_content, $matches, PREG_OFFSET_CAPTURE);

                    // Check if there are any matches
                    if (!empty($matches[0])) {
                        // Display page ID, title, and text snippet
                        echo "Page: $page->ID - $page->post_title<br>";
                        foreach ($matches[0] as $match) {
                            $offset = $match[1];
                            // Adjust snippet length as needed
                            $snippet = trim(substr($page_content, max(0, $offset - 30), 60));
                            echo "Text Snippet: ... " . str_replace(array("\r", "\n"), '', htmlspecialchars($snippet)) . " ... <br>";
                        }
                        echo "<br>";
                        // Break out of the loop once a match is found for any search term
                        break;
                    }
                }
            }
        } else {
            echo 'No pages found with the specified search terms after filtering.<br>';
        }
    } else {
        echo 'No pages found with the specified search terms.<br>';
    }

    return 'End of search';
}

// Search for curly quotation marks style
// search_pages_for_terms($search_terms = array('“', '”', '„'), $languages = array());

// Search for straight quotation marks
// search_pages_for_terms($search_terms = array("'", '"'), $languages = array());

// Search for straight quotation marks
// search_pages_for_terms($search_terms = array(" ,", ' ;'), $languages = array());

// For German pages, check whether the English curly quotation marks style (“ and ”) was applied
search_pages_for_terms($search_terms = array('”'), $languages = array('de'));
