<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

global $wp_query;

$paged = get_query_var('paged') ? (int) get_query_var('paged') : 1;
$total = $wp_query->max_num_pages;
$current = max( 1, $paged );

if ( $total < 2 ) return;

Utils::get_pagination([
    'current' => $current,
    'total' => $total,
    'format' => '?paged=%#%'
]);