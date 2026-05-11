<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Team card — Square 01.
 *
 * Override: copy to `wpspeedo-team/` (mu-plugins, child, or parent theme; see {@link https://wpspeedo.com/docs/team-template-override/}).
 * Folder: filter `wpspeedo_team/template/folder`, {@see Utils::load_template()}.
 *
 * @version 1.0.0 Bump when this file changes; compare after updates if you override.
 *
 * {@see Shortcode_Loader::init_template()} — scope vars (not set here):
 *
 * @var string      $card_action
 * @var string      $thumbnail_size
 * @var string      $thumbnail_size_custom
 * @var string      $thumbnail_type
 * @var int         $description_length
 * @var string|bool $show_read_more_link
 */
$this->add_attribute( 'wrapper', 'class', ['wps-team--social-hover-up', 'wps-team--thumbnail-shad'] );
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
                ["--wps-divider-bg-color:{$primary_color};", "--wps-item-primary-color:{$primary_color};"],
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
        Utils::get_the_thumbnail( get_the_ID(), [
            'card_action'           => $card_action,
            'thumbnail_size'        => $thumbnail_size,
            'thumbnail_size_custom' => $thumbnail_size_custom,
            'thumbnail_type'        => $thumbnail_type,
        ] );
        Utils::get_the_ribbon( get_the_ID() );
        Utils::get_the_title( get_the_ID(), [
            'card_action' => $card_action,
        ] );
        Utils::get_the_designation( get_the_ID() );
        Utils::get_the_divider();
        Utils::get_the_excerpt( get_the_ID(), [
            'description_length'  => $description_length,
            'show_read_more_link' => $show_read_more_link,
            'card_action'         => $card_action,
        ] );
        Utils::get_the_social_links( get_the_ID() );
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
?>
    
</div><?php 