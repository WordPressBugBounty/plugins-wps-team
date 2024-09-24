<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Plugin_Hooks {
    public function __construct() {
        add_filter( 'template_include', [$this, 'maybe_load_dynamic_template'] );
        add_action( 'init', [$this, 'maybe_flush_rewrite_rules'], PHP_INT_MAX );
        add_action( 'wpspeedo_team/before_single_team', [$this, 'before_single_team'] );
        add_action( 'wpspeedo_team/before_wrapper_inner', [$this, 'before_wrapper_inner'] );
        add_action( 'wpspeedo_team/before_wrapper_inner', [$this, 'add_shortcode_edit_link'] );
        add_action( 'wpspeedo_team/after_wrapper_inner', [$this, 'after_wrapper_inner'] );
        add_action( 'wpspeedo_team/after_wrapper_inner', [$this, 'after_wrapper_inner_last'], 999999 );
        add_action( 'wpspeedo_team/after_posts', [$this, 'after_posts'] );
    }

    function single_page_few_info() {
        return [
            '_mobile',
            '_telephone',
            '_email',
            '_website'
        ];
    }

    function maybe_load_dynamic_template( $template ) {
        if ( is_singular( Utils::post_type_name() ) ) {
            add_filter( 'wpspeedo_team/few_info', [$this, 'single_page_few_info'] );
            return Utils::load_template( 'template-single.php' );
        }
        if ( is_post_type_archive( Utils::post_type_name() ) ) {
            return Utils::load_template( 'template-archive.php' );
        }
        $enabled_taxonomies = Utils::archive_enabled_taxonomies();
        if ( !empty( $enabled_taxonomies ) && is_tax( $enabled_taxonomies ) ) {
            return Utils::load_template( 'template-archive.php' );
        }
        return $template;
    }

    function maybe_flush_rewrite_rules() {
        if ( get_option( Utils::rewrite_flush_key(), false ) === false ) {
            flush_rewrite_rules();
        }
    }

    public function before_wrapper_inner( $shortcode_loader ) {
        $display_type = $shortcode_loader->get_setting( 'display_type' );
        $card_action = $shortcode_loader->get_setting( 'card_action' );
        if ( $display_type == 'filter' && $card_action != 'expand' ) {
        }
        if ( $display_type === 'carousel' && $card_action !== 'expand' ) {
            ?>
            <div class="wps-carousel--inner">
        <?php 
        }
    }

    public function add_shortcode_edit_link( $shortcode_loader ) {
        if ( $shortcode_loader->mode !== 'preview' && $shortcode_loader->id && (current_user_can( 'editor' ) || current_user_can( 'administrator' )) ) {
            ?>
                <div class="wps-widget--edit-link">
                    <a class="wps-widget--edit-link-btn" target="_blank" href="<?php 
            echo admin_url( "/admin.php?page=wps-team#/shortcode/{$shortcode_loader->id}" );
            ?>">
                        <i class="fas fa-pencil-alt"></i>
                        <span class="wps-widget--edit-link-popup">Only <strong>Admin</strong> & <strong>Editor</strong> can see this link</span>
                    </a>
                </div>
            <?php 
        }
    }

    public function before_single_team( $shortcode_loader ) {
        $terms_classes = Utils::get_post_term_slugs( get_the_ID(), Utils::get_active_taxonomies() );
        if ( !empty( $terms_classes ) ) {
            $shortcode_loader->add_attribute( 'single_item_col_' . get_the_ID(), 'class', $terms_classes );
        }
    }

    public function after_wrapper_inner( $shortcode_loader ) {
        $card_action = $shortcode_loader->get_setting( 'card_action' );
        $display_type = $shortcode_loader->get_setting( 'display_type' );
        $detail_thumbnail_size = $shortcode_loader->get_setting( 'detail_thumbnail_size' );
        $detail_thumbnail_size_custom = $shortcode_loader->get_setting( 'detail_thumbnail_size_custom' );
        $detail_thumbnail_type = $shortcode_loader->get_setting( 'detail_thumbnail_type' );
        if ( $display_type === 'carousel' && $card_action !== 'expand' ) {
            if ( $shortcode_loader->get_setting( 'navs' ) ) {
                ?>
                <div class="wps-team--carousel-navs">
                    <button class="swiper-button-prev" tabindex="0" aria-label="Previous slide"><i aria-hidden="true" class="fas fa-chevron-left"></i></button>
                    <button class="swiper-button-next" tabindex="0" aria-label="Next slide"><i aria-hidden="true" class="fas fa-chevron-right"></i></button>
                </div>
            <?php 
            }
            if ( $shortcode_loader->get_setting( 'dots' ) ) {
                ?>
                <div class="swiper-pagination"></div>
            <?php 
            }
            print "</div>";
        }
    }

    public function after_wrapper_inner_last( $shortcode_loader ) {
        $display_type = $shortcode_loader->get_setting( 'display_type' );
        $card_action = $shortcode_loader->get_setting( 'card_action' );
        if ( $display_type == 'filter' && $card_action != 'expand' ) {
        }
    }

    public function after_posts() {
        ?>
        <div class="wps-team--not-found-wrapper">
            <div class="wps-team--not-found"><?php 
        esc_html_e( plugin()->translations->get( 'no_results_found_text', _x( 'No Results Found', 'Public', 'wpspeedo-team' ) ) );
        ?></div>
        </div>
        <?php 
    }

}
