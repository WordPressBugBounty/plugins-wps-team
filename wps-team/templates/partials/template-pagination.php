<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

global $wp_query;

$paged = get_query_var('paged') ? (int) get_query_var('paged') : 1;

$total = $wp_query->max_num_pages;

if ( $total < 2 ) return; ?>

<div class="wps-pagination--wrap">
    
    <?php

    $extra_links = 2;

    $current = max( 1, $paged );

    $pages = paginate_links([
        'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
        'format'    => '?paged=%#%',
        'current'   => $current,
        'total'     => $total,
        'type'      => 'array',
        'show_all'  => true,
        'prev_next' => false
    ]);

    if ( is_array( $pages ) ) {
        
        echo '<nav class="wps-team--navigation"><ul class="wps-team--pagination">';

        $prev_limit = $current - $extra_links + min( $total - $current - $extra_links, 0 );
		$next_limit = $current + $extra_links + max( $extra_links + 1 - $current, 0 );

        foreach ( $pages as $index => $page ) {

            $n = $index + 1;

            if ( $n < $prev_limit || $n > $next_limit ) continue;

            $page = str_replace( ['page-numbers', 'current'], ['wps--page-numbers', 'wps--current'], $page);

            echo "<li>$page</li>";

        }

        echo '</ul></nav>';

    }

    ?>

</div>