<?php

// WordPress Admin - Store open status
// Last update: 2024-11-05


add_shortcode($tag = 'wordpress_admin_store_open_status', $callback = 'store_hours_shortcode');

function store_hours_shortcode()
{
    // Setup
    $opening_hours = [
        'Monday' => '10:00-17:00',
        'Tuesday' => '10:00-17:00',
        'Wednesday' => '10:00-17:00',
        'Thursday' => '10:00-17:00',
        'Friday' => '10:00-17:00',
        'Saturday' => '10:00-14:00',
    ];
    $public_holidays = ['2024-01-01', '2024-01-06', '2024-03-29', '2024-04-01', '2024-05-01', '2024-05-09', '2024-05-20', '2024-05-30', '2024-08-08', '2024-08-15', '2024-10-03', '2024-11-01', '2024-12-24', '2024-12-25', '2024-12-26', '2025-01-01', '2025-01-06'];
    $special_days = ['2024-06-28', '2024-06-29', '2024-07-01', '2024-07-02', '2024-07-03'];
    $timezone = get_option('timezone_string');

    // Get current date and time
    $current_datetime = new DateTime('now', new DateTimeZone($timezone));
    $current_day_of_week = $current_datetime->format('l');
    $current_date = $current_datetime->format('Y-m-d');
    $current_time = $current_datetime->format('H:i');

    // Get current language
    $current_language = (function_exists('pll_current_language') && in_array(pll_current_language('slug'), pll_languages_list(array('fields' => 'slug')))) ? pll_current_language('slug') : 'en';

    // Determine opening hours for today
    if (!isset($opening_hours[$current_day_of_week])) {
        return generate_message('closed', $current_language);
    }

    list($start_time, $end_time) = explode('-', $opening_hours[$current_day_of_week]);
    $start_time = DateTime::createFromFormat('H:i', $start_time, new DateTimeZone($timezone));
    $end_time = DateTime::createFromFormat('H:i', $end_time, new DateTimeZone($timezone));
    $closing_soon_time = clone $end_time;
    $closing_soon_time->modify('-1 hour');

    // Check if today is a public holiday
    if (in_array($current_date, $public_holidays)) {
        return generate_message('holiday', $current_language);
    }

    // Check if today is a special day
    if (in_array($current_date, $special_days)) {
        return generate_message('special_event', $current_language);
    }

    // Determine store status based on current time
    if ($current_datetime >= $start_time && $current_datetime <= $end_time) {
        if ($current_datetime >= $closing_soon_time) {
            return generate_message('closing_soon', $current_language);
        } else {
            return generate_message('open', $current_language);
        }
    } else {
        return generate_message('closed', $current_language);
    }
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
