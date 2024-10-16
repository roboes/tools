<?php

// WordPress Admin - Store open status (using AJAX to load dynamic content, ensuring content not to be cached)
// Last update: 2024-10-16


add_shortcode($tag = 'wordpress_admin_store_open_status', $callback = 'store_hours_shortcode');
add_action($hook_name = 'wp_enqueue_scripts', $callback = 'enqueue_custom_scripts', $priority = 10, $accepted_args = 1);

// Handle the AJAX request
add_action($hook_name = 'wp_ajax_get_store_hours', $callback = 'get_store_hours', $priority = 10, $accepted_args = 1);
add_action($hook_name = 'wp_ajax_nopriv_get_store_hours', $callback = 'get_store_hours', $priority = 10, $accepted_args = 1);


function store_hours_shortcode()
{
    static $instance = 0;
    $instance++;
    $nonce = wp_create_nonce('store_hours_nonce');
    $unique_id = 'store-hours-container-' . $instance;

    return '
        <div id="' . $unique_id . '"></div>
        <script type="text/javascript">
            (function($) {
                $(document).ready(function() {
                    $.ajax({
                        url: "' . admin_url('admin-ajax.php') . '",
                        type: "POST",
                        data: {
                            action: "get_store_hours",
                            nonce: "' . $nonce . '",
                            unique_id: "' . $unique_id . '"
                        },
                        success: function(response) {
                            $("#' . $unique_id . '").html(response);
                        },
                        error: function(xhr, status, error) {
                            console.log("AJAX Error: " + status + " " + error);
                        }
                    });
                });
            })(jQuery);
        </script>';
}

// Enqueue jQuery
function enqueue_custom_scripts()
{
    wp_enqueue_script('jquery');
}

function get_store_hours()
{
    check_ajax_referer('store_hours_nonce', 'nonce');

    // Setup
    $opening_hours = [
        'Monday' => '10:00-17:00',
        'Tuesday' => '10:00-17:00',
        'Wednesday' => '10:00-17:00',
        'Thursday' => '10:00-17:00',
        'Friday' => '10:00-17:00',
        'Saturday' => '10:00-14:00',
    ];
    $public_holidays = ['2024-01-01', '2024-01-06', '2024-03-29', '2024-04-01', '2024-05-01', '2024-05-09', '2024-05-20', '2024-05-30', '2024-08-08', '2024-08-15', '2024-10-03', '2024-11-01', '2024-12-25', '2024-12-26', '2025-01-01', '2025-01-06'];
    $special_days = ['2024-06-28', '2024-06-29', '2024-07-01', '2024-07-02', '2024-07-03'];
    $time_zone = get_option('timezone_string');

    // Get current date and time
    $current_datetime = new DateTime('now', new DateTimeZone($time_zone));
    $current_day_of_week = $current_datetime->format('l');
    $current_date = $current_datetime->format('Y-m-d');
    $current_time = $current_datetime->format('H:i');

    // Get current language
    $current_language = (function_exists('pll_current_language') && in_array(pll_current_language('slug'), pll_languages_list(array('fields' => 'slug')))) ? pll_current_language('slug') : 'en';

    // Determine opening hours for today
    if (!isset($opening_hours[$current_day_of_week])) {
        echo generate_message('closed', $current_language);
        wp_die();
    }

    list($start_time, $end_time) = explode('-', $opening_hours[$current_day_of_week]);
    $start_time = DateTime::createFromFormat('H:i', $start_time, new DateTimeZone($time_zone));
    $end_time = DateTime::createFromFormat('H:i', $end_time, new DateTimeZone($time_zone));
    $closing_soon_time = clone $end_time;
    $closing_soon_time->modify('-1 hour');

    // Check if today is a public holiday
    if (in_array($current_date, $public_holidays)) {
        echo generate_message('holiday', $current_language);
        wp_die();
    }

    // Check if today is a special day
    if (in_array($current_date, $special_days)) {
        echo generate_message('special_event', $current_language);
        wp_die();
    }

    // Determine store status based on current time
    if ($current_datetime >= $start_time && $current_datetime <= $end_time) {
        if ($current_datetime >= $closing_soon_time) {
            echo generate_message('closing_soon', $current_language);
        } else {
            echo generate_message('open', $current_language);
        }
    } else {
        echo generate_message('closed', $current_language);
    }

    wp_die();
}

function generate_message($status, $language)
{
    $statuses = [
        'open' => [
            'de' => 'Geschäft ist jetzt geöffnet',
            'en' => 'Store is now open',
            'color' => '#50C878',
        ],
        'closing_soon' => [
            'de' => 'Geschäft schließt bald',
            'en' => 'Store is closing soon',
            'color' => '#EAA300',
        ],
        'closed' => [
            'de' => 'Geschäft ist jetzt geschlossen',
            'en' => 'Store is now closed',
            'color' => '#B20000',
        ],
        'holiday' => [
            'de' => 'Geschäft ist aufgrund eines Feiertags heute geschlossen',
            'en' => 'Store is closed today due to public holiday',
            'color' => '#B20000',
        ],
        'special_event' => [
            'de' => 'Geschäft ist aufgrund einer Veranstaltung heute geschlossen',
            'en' => 'Store is closed today due to an event',
            'color' => '#B20000',
        ],
    ];

    $message = '<span class="store-open-status" style="margin-right: 6px"><i class="fa-solid fa-circle" style="color: ' . $statuses[$status]['color'] . ';"></i></span>' . $statuses[$status][$language];
    return $message;
}
