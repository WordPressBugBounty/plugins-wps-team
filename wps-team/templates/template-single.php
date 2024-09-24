<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

get_header();

while ( have_posts() ) : the_post();

$shortcode_loader = new Single_Loader();

$detail_thumbnail_size          = Utils::get_setting( 'detail_thumbnail_size' );
$detail_thumbnail_size_custom   = Utils::get_setting( 'detail_thumbnail_size_custom' );
$detail_thumbnail_type          = Utils::get_setting( 'detail_thumbnail_type' );

$color = Utils::get_item_data( '_color' );

if ( !empty($color) ) {
    $shortcode_loader->add_attribute( 'wps-widget-single-page--wrapper', 'style', '--wps-divider-bg-color:' . sanitize_text_field($color) );
}

?>

<div <?php $shortcode_loader->print_attribute_string('wps-widget-single-page--wrapper'); ?>>

    <?php if ( Utils::get_setting('archive_page') ) : ?>
        <?php include Utils::load_template( "partials/template-return-link.php" ); ?>
    <?php endif; ?>

    <div class="wps-row">

        <div class="wps-col wps-col--left-info">

            <?php
            
            echo Utils::get_the_thumbnail( get_the_ID(), [ 'card_action' => 'none', 'thumbnail_size' => $detail_thumbnail_size, 'thumbnail_size_custom' => $detail_thumbnail_size_custom, 'allow_ribbon' => true, 'thumbnail_type' => $detail_thumbnail_type ] );

            echo Utils::get_the_title( get_the_ID(), [ 'card_action' => 'none', 'tag' => 'h1', 'class' => 'wps-show--tablet-small' ] );
            echo Utils::get_the_designation( get_the_ID(), [ 'class' => 'wps-show--tablet-small' ] );
            echo Utils::get_the_divider([ 'class' => 'wps-show--tablet-small' ]);
            
            echo Utils::get_the_extra_info( get_the_ID(), [
                'fields' => [ '_mobile', '_telephone', '_email', '_website' ],
                'info_style' => 'start-aligned',
            ]);
            
            echo Utils::get_the_extra_info( get_the_ID(), [
                'fields' => array_merge( [ '_experience', '_company' ], Utils::get_active_taxonomies( true ) ),
                'label_type' => 'text',
                'info_style' => 'start-aligned-alt',
                'info_top_border' => true
            ]);

            ?>

        </div>

        <div class="wps-col wps-col--right-info">
            <div class="wps-team--single-inner">

                <?php
                
                echo Utils::get_the_title( get_the_ID(), [ 'card_action' => 'none', 'tag' => 'h1', 'class' => 'wps-hide--tablet-small' ]);
                echo Utils::get_the_designation( get_the_ID(), [ 'class' => 'wps-hide--tablet-small' ]);
                echo Utils::get_the_divider([ 'class' => 'wps-hide--tablet-small' ]);
                echo Utils::get_the_description( get_the_ID(), [ 'context' => 'details' ]);
                Utils::get_the_education( get_the_ID(), [ 'context' => 'details', 'show_title' => true ]);
                Utils::get_the_social_links( get_the_ID(), [ 'show_title' => true ]);
                Utils::get_the_skills( get_the_ID(), [ 'show_title' => true ]);
                echo Utils::get_the_action_links( get_the_ID(), [ 'context' => 'single' ]);

                ?>

            </div>
        </div>

    </div>

</div>

<?php endwhile;

get_footer();