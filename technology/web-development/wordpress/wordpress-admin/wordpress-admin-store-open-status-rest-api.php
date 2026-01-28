<?php

// WordPress Admin - Store open status (using REST API)
// Last update: 2026-01-15


add_action(hook_name: 'rest_api_init', callback: 'register_store_hours_endpoint', priority: 10, accepted_args: 1);

if (!is_admin()) {
    add_shortcode(tag: 'wordpress_admin_store_open_status', callback: 'store_hours_shortcode');
}

function register_store_hours_endpoint(): void
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

function store_hours_shortcode(): string
{
    static $instance = 0;
    $instance++;
    $unique_id = 'store-hours-container-' . $instance;

    // Get current language (Polylang/WPML)
    $browsing_language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

    // Add cache-busting timestamp parameter
    $rest_url = esc_url(add_query_arg([
        'lang' => $browsing_language,
        '_' => time(),
    ], rest_url('store/v1/hours')));

    return sprintf(
        '<div id="%1$s"></div>
        <script type="text/javascript">
            jQuery(function($) {
                $.ajax({
                    url: "%2$s",
                    cache: false
                })
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

function get_store_hours_rest(WP_REST_Request $request): WP_REST_Response
{
    // Settings
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
    $public_holidays = ['2026-01-01', '2026-01-06', '2026-04-03', '2026-04-06', '2026-05-01', '2026-05-14', '2026-05-25', '2026-06-04', '2026-08-08', '2026-08-15', '2026-10-03', '2026-12-25', '2026-12-26', '2027-01-01', '2027-01-06', '2027-03-26', '2027-03-29', '2027-05-01', '2027-05-06', '2027-05-17', '2027-05-27', '2027-11-01', '2027-12-25'];
    $closed_days = ['2026-12-24', '2026-12-31', '2027-12-24', '2027-12-31'];
    $special_days = ['2024-06-28', '2024-06-29', '2024-07-01', '2024-07-02', '2024-07-03'];

    $current_datetime = new DateTimeImmutable(datetime: 'now', timezone: wp_timezone());
    $current_day_of_week = $current_datetime->format('l');
    $current_date = $current_datetime->format('Y-m-d');

    $browsing_language = filter_var($request->get_param('lang') ?: 'en', FILTER_DEFAULT, FILTER_THROW_ON_FAILURE);

    if (in_array($current_date, $public_holidays, true)) {
        $message = generate_message('holiday', $browsing_language);
    } elseif (in_array($current_date, $closed_days, true)) {
        $message = generate_message('closed_date', $browsing_language);
    } elseif (in_array($current_date, $special_days, true)) {
        $message = generate_message('special_event', $browsing_language);
    } elseif (isset($special_opening_hours[$current_date])) {
        [$start_time, $end_time] = $special_opening_hours[$current_date];
        $message = get_status_for_hours($current_datetime, $start_time, $end_time, wp_timezone(), $browsing_language);
    } elseif (isset($opening_hours[$current_day_of_week])) {
        [$start_time, $end_time] = $opening_hours[$current_day_of_week];
        $message = get_status_for_hours($current_datetime, $start_time, $end_time, wp_timezone(), $browsing_language);
    } else {
        $message = generate_message('closed', $browsing_language);
    }

    // Return response with no-cache headers
    $response = new WP_REST_Response(['message' => $message]);
    $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    $response->header('Pragma', 'no-cache');
    $response->header('Expires', 'Wed, 11 Jan 1984 05:00:00 GMT');

    return $response;
}

function get_status_for_hours(DateTimeImmutable $current_datetime, string $start_time, string $end_time, DateTimeZone $timezone, string $language): string
{
    $start_datetime = DateTimeImmutable::createFromFormat(format: 'H:i', datetime: $start_time, timezone: $timezone);
    $end_datetime = DateTimeImmutable::createFromFormat(format: 'H:i', datetime: $end_time, timezone: $timezone);
    $closing_soon_datetime = $end_datetime->modify('-1 hour');

    if ($current_datetime >= $start_datetime && $current_datetime < $end_datetime) {
        $status = $current_datetime >= $closing_soon_datetime ? 'closing_soon' : 'open';
        return generate_message(status: $status, language: $language);
    }

    return generate_message(status: 'closed', language: $language);
}

function generate_message(string $status, string $language): string
{
    static $statuses = [
        'open' => [
            'en' => 'Store is now open',
            'de' => 'Geschäft ist jetzt geöffnet',
            'color' => '#50C878',
        ],
        'closing_soon' => [
            'en' => 'Store is closing soon',
            'de' => 'Geschäft schließt bald',
            'color' => '#EAA300',
        ],
        'closed' => [
            'en' => 'Store is now closed',
            'de' => 'Geschäft ist jetzt geschlossen',
            'color' => '#B20000',
        ],
        'closed_date' => [
            'en' => 'Store is closed today',
            'de' => 'Geschäft ist heute geschlossen',
            'color' => '#B20000',
        ],
        'holiday' => [
            'en' => 'Store is closed today due to public holiday',
            'de' => 'Geschäft ist aufgrund eines Feiertags heute geschlossen',
            'color' => '#B20000',
        ],
        'special_event' => [
            'en' => 'Store is closed today due to an event',
            'de' => 'Geschäft ist aufgrund einer Veranstaltung heute geschlossen',
            'color' => '#B20000',
        ],
    ];

    return sprintf(
        '<span class="store-open-status" style="margin-right: 6px"><i class="fa-solid fa-circle" style="color: %s;"></i></span>%s',
        esc_attr($statuses[$status]['color']),
        esc_html($statuses[$status][$language] ?? $statuses[$status]['en'])
    );
}
