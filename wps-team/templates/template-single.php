<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

get_header();

while ( have_posts() ) : the_post();
    include Utils::load_template( "partials/template-single-content.php" );
endwhile;

get_footer();