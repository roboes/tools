<?php

// WordPress Admin - Store open status
// Last update: 2026-01-15

add_shortcode(tag: 'wordpress_admin_store_open_status', callback: 'store_hours_shortcode');

function store_hours_shortcode(): string
{
    if (is_admin() && !defined('DOING_AJAX')) {
        return '';
    }

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

    $current_datetime = new DateTime(datetime: 'now', timezone: wp_timezone());
    $current_day_of_week = $current_datetime->format('l');
    $current_date = $current_datetime->format('Y-m-d');

    // Get current language
    $current_language = 'en';
    if (function_exists('pll_current_language')) {
        if (pll_current_language('slug') && in_array(needle: pll_current_language('slug'), haystack: pll_languages_list(['fields' => 'slug']), strict: true)) {
            $current_language = pll_current_language('slug');
        }
    }

    if (in_array($current_date, $public_holidays, true)) {
        return generate_message('holiday', $current_language);
    }

    if (in_array($current_date, $closed_days, true)) {
        return generate_message('closed_date', $current_language);
    }

    if (in_array($current_date, $special_days, true)) {
        return generate_message('special_event', $current_language);
    }

    if (isset($special_opening_hours[$current_date])) {
        [$start_time, $end_time] = $special_opening_hours[$current_date];
    } elseif (isset($opening_hours[$current_day_of_week])) {
        [$start_time, $end_time] = $opening_hours[$current_day_of_week];
    } else {
        return generate_message('closed', $current_language);
    }

    $start_datetime = DateTime::createFromFormat(format: 'H:i', datetime: $start_time, timezone: wp_timezone());
    $end_datetime = DateTime::createFromFormat(format: 'H:i', datetime: $end_time, timezone: wp_timezone());
    $closing_soon_datetime = (clone $end_datetime)->modify('-1 hour');

    // Determine store status based on current time
    if ($current_datetime >= $start_datetime && $current_datetime < $end_datetime) {
        $status = $current_datetime >= $closing_soon_datetime ? 'closing_soon' : 'open';
        return generate_message($status, $current_language);
    }

    return generate_message('closed', $current_language);
}

function generate_message(string $status, string $language): string
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
