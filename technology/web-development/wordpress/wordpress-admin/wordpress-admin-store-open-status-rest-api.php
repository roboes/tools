<?php

// WordPress Admin - Store open status (using REST API)
// Last update: 2025-10-31


add_shortcode($tag = 'wordpress_admin_store_open_status', $callback = 'store_hours_shortcode');
add_action($hook_name = 'rest_api_init', $callback = 'register_store_hours_endpoint');

function register_store_hours_endpoint()
{
    register_rest_route('store/v1', '/hours', [
        'methods' => 'GET',
        'callback' => 'get_store_hours_rest',
        'permission_callback' => '__return_true',
        'args' => [
            'lang' => [
                'required' => false,
                'default' => 'en',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ]);
}

function store_hours_shortcode()
{
    static $instance = 0;
    $instance++;
    $unique_id = 'store-hours-container-' . $instance;

    // Get current language in the page context
    $current_language = (function_exists('pll_current_language') && in_array(pll_current_language('slug'), pll_languages_list(['fields' => 'slug']))) ? pll_current_language('slug') : 'en';

    $rest_url = esc_url(add_query_arg('lang', $current_language, rest_url('store/v1/hours')));

    return sprintf(
        '<div id="%1$s"></div>
        <script type="text/javascript">
            jQuery(function($) {
                $.get("%2$s")
                .done(function(response) {
                    $("#%1$s").html(response.message);
                })
                .fail(function(xhr, status, error) {
                    console.error("REST API Error:", status, error);
                });
            });
        </script>',
        esc_attr($unique_id),
        $rest_url
    );
}

function get_store_hours_rest($request)
{
    // Setup
    $opening_hours = [
        'Monday' => ['10:00', '17:00'],
        'Tuesday' => ['10:00', '17:00'],
        'Wednesday' => ['10:00', '17:00'],
        'Thursday' => ['10:00', '17:00'],
        'Friday' => ['10:00', '17:00'],
        'Saturday' => ['10:00', '14:00'],
    ];
    $special_opening_hours = [
        '2024-12-24' => ['10:00', '14:00'],
        '2025-12-24' => ['10:00', '14:00'],
    ];
    $public_holidays = ['2024-01-01', '2024-01-06', '2024-03-29', '2024-04-01', '2024-05-01', '2024-05-09', '2024-05-20', '2024-05-30', '2024-08-08', '2024-08-15', '2024-10-03', '2024-11-01', '2024-12-25', '2024-12-26', '2024-12-31', '2025-01-01', '2025-01-06', '2025-01-01', '2025-01-06', '2025-04-18', '2025-04-21', '2025-05-01', '2025-05-29', '2025-06-09', '2025-06-19', '2025-08-08', '2025-08-15', '2025-10-03', '2025-11-01', '2025-12-25', '2025-12-26', '2026-01-01', '2026-01-06'];
    $closed_days = ['2025-12-31'];
    $special_days = ['2024-06-28', '2024-06-29', '2024-07-01', '2024-07-02', '2024-07-03'];

    $timezone = new DateTimeZone(get_option('timezone_string') ?: 'UTC');
    $current_datetime = new DateTime('now', $timezone);
    $current_day_of_week = $current_datetime->format('l');
    $current_date = $current_datetime->format('Y-m-d');

    // Get language from request parameter
    $current_language = $request->get_param('lang') ?: 'en';

    if (in_array($current_date, $public_holidays, true)) {
        return ['message' => generate_message('holiday', $current_language)];
    }

    if (in_array($current_date, $closed_days, true)) {
        return ['message' => generate_message('closed_date', $current_language)];
    }

    if (in_array($current_date, $special_days, true)) {
        return ['message' => generate_message('special_event', $current_language)];
    }

    if (isset($special_opening_hours[$current_date])) {
        [$start_time, $end_time] = $special_opening_hours[$current_date];
    } elseif (isset($opening_hours[$current_day_of_week])) {
        [$start_time, $end_time] = $opening_hours[$current_day_of_week];
    } else {
        return ['message' => generate_message('closed', $current_language)];
    }

    $start_datetime = DateTime::createFromFormat('H:i', $start_time, $timezone);
    $end_datetime = DateTime::createFromFormat('H:i', $end_time, $timezone);
    $closing_soon_datetime = (clone $end_datetime)->modify('-1 hour');

    if ($current_datetime >= $start_datetime && $current_datetime < $end_datetime) {
        $status = $current_datetime >= $closing_soon_datetime ? 'closing_soon' : 'open';
        return ['message' => generate_message($status, $current_language)];
    }

    return ['message' => generate_message('closed', $current_language)];
}

function generate_message($status, $language)
{
    static $statuses = [
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
        'closed_date' => [
            'de' => 'Geschäft ist heute geschlossen',
            'en' => 'Store is closed today',
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

    return sprintf(
        '<span class="store-open-status" style="margin-right: 6px"><i class="fa-solid fa-circle" style="color: %s;"></i></span>%s',
        esc_attr($statuses[$status]['color']),
        esc_html($statuses[$status][$language])
    );
}
