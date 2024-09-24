<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
$this->add_attribute( 'wrapper', 'class', ['wps-team--social-hover-up', 'wps-team--thumb-zoom-out', 'wps-team--thumbnail-shad'] );
?>

<div <?php 
$this->print_attribute_string( 'wrapper' );
?>>
    
    <?php 
do_action( 'wpspeedo_team/before_wrapper_inner', $this );
?>

    <div <?php 
$this->print_attribute_string( 'wrapper_inner' );
?>>

        <?php 
do_action( 'wpspeedo_team/before_posts', $this );
?>
        
        <?php 
if ( $this->get_posts()->have_posts() ) {
    ?>

            <div <?php 
    $this->print_attribute_string( 'single_item_row' );
    ?>>

                <?php 
    while ( $this->get_posts()->have_posts() ) {
        $this->get_posts()->the_post();
        ?>

                    <?php 
        $this->add_attribute(
            'single_item_col_' . get_the_ID(),
            'class',
            'wps-widget--item wps-widget--item-' . get_the_ID(),
            true
        );
        do_action( 'wpspeedo_team/before_single_team', $this );
        $primary_color = sanitize_text_field( Utils::get_item_data( '_color' ) );
        if ( !empty( $primary_color ) ) {
            $this->add_attribute(
                'single_item_col_' . get_the_ID(),
                'style',
                ["--wps-item-primary-color:{$primary_color};"],
                true
            );
        }
        ?>
            
                    <div <?php 
        $this->print_attribute_string( ['single_item_col', 'single_item_col_' . get_the_ID()] );
        ?>>
                        <div class="wpspeedo-team--single">
                            <div class="wps-team--single-inner">
                                
                                <?php 
        echo Utils::get_the_thumbnail( get_the_ID(), [
            'card_action'           => $card_action,
            'thumbnail_size'        => $thumbnail_size,
            'thumbnail_size_custom' => $thumbnail_size_custom,
            'allow_ribbon'          => true,
            'thumbnail_type'        => $thumbnail_type,
        ] );
        echo Utils::get_the_designation( get_the_ID() );
        echo Utils::get_the_title( get_the_ID(), [
            'card_action' => $card_action,
        ] );
        echo Utils::get_the_divider();
        echo Utils::get_the_excerpt( get_the_ID(), [
            'description_length' => $description_length,
            'add_read_more'      => $add_read_more,
            'card_action'        => $card_action,
            'read_more_text'     => $read_more_text,
        ] );
        Utils::get_the_social_links( get_the_ID(), [] );
        ?>
                                
                            </div>
                        </div>
                    </div>

                    <?php 
        do_action( 'wpspeedo_team/after_single_team', $this );
        ?>

                <?php 
    }
    ?>
        
            </div>

        <?php 
}
?>

        <?php 
do_action( 'wpspeedo_team/after_posts', $this );
?>

    </div>

    <?php 
do_action( 'wpspeedo_team/after_wrapper_inner', $this );
if ( wps_team_fs()->can_use_premium_code__premium_only() && $this->should_load_ajax_template() ) {
    include Utils::load_template( "ajax-templates/template-circle-01.php" );
}
?>
    
</div><?php 