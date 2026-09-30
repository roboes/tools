<?php

// WordPress Admin - Store open status (using REST API)
// Last update: 2026-09-13


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

function get_easter_sunday(int $year, DateTimeZone $timezone): DateTimeImmutable
{
    // Anonymous Gregorian algorithm (Meeus/Jones/Butcher) - doesn't require the ext-calendar extension
    $a = $year % 19;
    $b = intdiv($year, 100);
    $c = $year % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $month = intdiv($h + $l - 7 * $m + 114, 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;

    return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day), $timezone);
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
        '2025-12-24' => ['10:00', '14:00'],
    ];
    // Fixed-date public holidays for Augsburg, Bavaria (month-day, so no year needed)
    $public_holidays_fixed = [
        '01-01', // New Year's Day
        '01-06', // Epiphany
        '05-01', // Labour Day
        '08-08', // Augsburger Hohes Friedensfest (Augsburg only)
        '08-15', // Assumption Day
        '10-03', // German Unity Day
        '11-01', // All Saints' Day
        '12-25', // Christmas Day
        '12-26', // Boxing Day
    ];
    $closed_days = ['2026-12-24', '2026-12-31', '2027-12-24', '2027-12-31'];
    $special_days = [];

    $current_datetime = new DateTimeImmutable(datetime: 'now', timezone: wp_timezone());
    $current_day_of_week = $current_datetime->format('l');
    $current_date = $current_datetime->format('Y-m-d');
    $current_date_md = $current_datetime->format('m-d');

    // Easter-based public holidays move every year, so they're calculated instead of hardcoded
    $easter_sunday = get_easter_sunday((int) $current_datetime->format('Y'), wp_timezone());
    $public_holidays_movable = [
        $easter_sunday->modify('-2 days')->format('Y-m-d'),  // Good Friday
        $easter_sunday->modify('+1 day')->format('Y-m-d'),   // Easter Monday
        $easter_sunday->modify('+39 days')->format('Y-m-d'), // Ascension Day
        $easter_sunday->modify('+50 days')->format('Y-m-d'), // Whit Monday
        $easter_sunday->modify('+60 days')->format('Y-m-d'), // Corpus Christi
    ];

    $is_public_holiday = in_array($current_date_md, $public_holidays_fixed, true)
        || in_array($current_date, $public_holidays_movable, true);

    $browsing_language = filter_var($request->get_param('lang') ?: 'en', FILTER_DEFAULT, FILTER_THROW_ON_FAILURE);

    // Only call out "holiday" on a day the store would otherwise be open;
    // a holiday falling on an already-closed day (e.g. Sunday) just shows the normal closed status.
    if ($is_public_holiday && isset($opening_hours[$current_day_of_week])) {
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
