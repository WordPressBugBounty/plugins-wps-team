<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

get_header();

$shortcode_loader = new Archive_Loader();

$shortcode_loader->add_attribute( 'wrapper', 'class', [
    'wps-container wps-widget--team wps-widget-container-archive',
    'wps-team-theme--square-01',
    'wps-si--b-bg-color wps-si--b-bg-color--hover',
    'wps-team--social-hover-up',
    'wps-team--thumbnail-shad'
]);

$shortcode_loader->add_attribute( 'single_item_col', 'class', 'wps-col' );

$thumbnail_size     = Utils::get_setting( 'thumbnail_size' );
$thumbnail_size_custom   = Utils::get_setting( 'thumbnail_size_custom' );

?>

<div class="wps-archive-title--wrapper">
    <h1 class="wps-archive--title"><?php the_archive_title(); ?></h1>
</div>

<div <?php $shortcode_loader->print_attribute_string( 'wrapper' ); ?>>

    <div class="wps-container--inner">

        <?php if ( have_posts() ) : ?>

            <div class="wps-row">
                <?php while ( have_posts() ) : the_post();
            
                    $shortcode_loader->add_attribute( 'single_item_col_' . get_the_ID(), 'class', 'wps-widget--item wps-widget--item-' . get_the_ID(), true );
            
                    $primary_color = sanitize_text_field( Utils::get_item_data('_color') );
            
                    if ( !empty( $primary_color ) ) {
                        $shortcode_loader->add_attribute( 'single_item_col_' . get_the_ID(), 'style', [
                            "--wps-divider-bg-color:$primary_color;",
                            "--wps-item-primary-color:$primary_color;"
                        ], true );
                    }
            
                    ?>
            
                    <div <?php $shortcode_loader->print_attribute_string( ['single_item_col', 'single_item_col_' . get_the_ID()] ); ?>>
                        <div class="wpspeedo-team--single">
                            <div class="wps-team--single-inner">
                                <?php
                                echo Utils::get_the_thumbnail( get_the_ID(), [ 'card_action' => 'single-page', 'thumbnail_size' => $thumbnail_size, 'thumbnail_size_custom' => $thumbnail_size_custom ] );
                                echo Utils::get_the_title( get_the_ID(), [ 'card_action' => 'single-page', 'tag' => 'h3' ] );
                                echo Utils::get_the_designation( get_the_ID() );
                                echo Utils::get_the_divider();
                                echo Utils::get_the_excerpt( get_the_ID() );
                                Utils::get_the_social_links( get_the_ID() );
                                ?>
                            </div>
                        </div>
                    </div>
                    
                <?php endwhile; ?>
            </div>

            <?php include Utils::load_template( 'partials/template-pagination.php' ); ?>

        <?php endif; ?>

    </div>

</div>

<?php get_footer();