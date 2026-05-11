<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

/**
 * Pagination.
 *
 * Override: copy to `wpspeedo-team/` (mu-plugins, child, or parent theme; see {@link https://wpspeedo.com/docs/team-template-override/}).
 * Folder: filter `wpspeedo_team/template/folder`, {@see Utils::load_template()}.
 *
 * @version 1.0.0 Bump when this file changes; compare after updates if you override.
 */
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