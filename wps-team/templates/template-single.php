<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

/**
 * Member singular page.
 *
 * Override: copy to `wpspeedo-team/` (mu-plugins, child, or parent theme; see {@link https://wpspeedo.com/docs/team-template-override/}).
 * Folder: filter `wpspeedo_team/template/folder`, {@see Utils::load_template()}.
 *
 * @version 1.0.0 Bump when this file changes; compare after updates if you override.
 */
get_header();

while ( have_posts() ) : the_post();
    include Utils::load_template( "partials/template-single-content.php" );
endwhile;

get_footer();