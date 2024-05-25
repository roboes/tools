<?php
// WordPress Admin Dynamic Copyright Date

add_shortcode( $tag='current_year', $callback='get_year');

function get_year() {
    $year = date_i18n('Y');
    return $year;
}
