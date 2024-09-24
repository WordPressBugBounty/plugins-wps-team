<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
abstract class Assets_Manager extends Style_Manager {
    use AJAX_Handler;
    public $ajax_key = 'wpspeedo_team';

    public $ajax_scope = '_assets_handler';

    private $main_post_id;

    private $google_fonts_index = 0;

    public $fonts_to_enqueue = [];

    private $registered_fonts = [];

    private $processed = [];

    public $assets = [];

    private $generate_hooked = false;

    public function __construct() {
        $this->set_ajax_scope_hooks();
        $this->init();
    }

    protected final function init() {
        add_action( 'WP_Speedo/Enqueue_Assets/' . $this->get_assets_key(), [$this, 'enqueue_assets'] );
        add_action(
            'WP_Speedo/Build_Assets/' . $this->get_assets_key(),
            [$this, 'maybe_build_assets_data'],
            10,
            2
        );
        add_action( 'WP_Speedo/Enqueue_Assets_Force/' . $this->get_assets_key(), [$this, 'force_enqueue_assets'], 10 );
        add_action( 'wp_head', [$this, 'print_fonts_links'], 7 );
        add_action( 'wp_head', [$this, 'print_google_fonts_preconnect_tag'], 8 );
        add_action( 'wp_enqueue_scripts', [$this, 'public_scripts'], 99999 );
        add_action( 'wp_footer', [$this, 'maybe_save_assets_data'] );
        add_filter( 'widget_update_callback', [$this, 'widget_updated__purge'] );
        add_action( 'post_updated', [$this, 'post_updated__purge'] );
        add_action( 'update_option_sidebars_widgets', [$this, 'assets_purge_all'] );
        add_action( 'wps_shortcode_created', [$this, 'assets_purge_all'] );
        add_action( 'wps_shortcode_updated', [$this, 'assets_purge_all'] );
        add_action( 'wps_shortcode_deleted', [$this, 'assets_purge_all'] );
        add_action( 'wps_preference_update', [$this, 'assets_purge_all'] );
    }

    public function ajax_purge_cache() {
        $this->assets_purge_all();
        $message = _x( 'All cache purged successfully', 'Settings: Tools', 'wpspeedo-team' );
        if ( wp_doing_ajax() ) {
            wp_send_json_success( $message, 200 );
        }
        return [
            'status'  => 200,
            'message' => $message,
        ];
    }

    protected final function get_save_key() {
        return 'wpspeedo--' . $this->get_assets_key() . '--assets';
    }

    protected final function get_model() {
        return [
            'styles'  => [],
            'scripts' => [],
            'fonts'   => [],
        ];
    }

    protected final function get_save_option_key( $id ) {
        return $this->get_save_key() . '--' . $id;
    }

    protected final function get_backward_ids() {
        return [
            'notfound',
            'front',
            'page',
            'home',
            'category',
            'tag',
            'tax',
            'archive',
            'search'
        ];
    }

    public final function get_data( $main_post_id = null ) {
        if ( empty( $main_post_id ) ) {
            $main_post_id = $this->get_current_page_id();
        }
        if ( in_array( $main_post_id, $this->get_backward_ids() ) ) {
            return get_option( $this->get_save_option_key( $main_post_id ) );
        } else {
            $data = get_post_meta( $main_post_id, $this->get_save_key(), true );
            if ( !empty( $data ) ) {
                return $data;
            }
        }
        return '';
    }

    public final function get_current_page_id() {
        $id = get_queried_object_id();
        if ( $id !== 0 ) {
            return $id;
        }
        global $wp_query;
        if ( $wp_query->is_page ) {
            return ( is_front_page() ? 'front' : 'page' );
        } elseif ( $wp_query->is_home ) {
            return 'home';
        } elseif ( $wp_query->is_category ) {
            return 'category';
        } elseif ( $wp_query->is_tag ) {
            return 'tag';
        } elseif ( $wp_query->is_tax ) {
            return 'tax';
        } elseif ( $wp_query->is_archive ) {
            return 'archive';
        } elseif ( $wp_query->is_search ) {
            return 'search';
        } elseif ( $wp_query->is_404 ) {
            return 'notfound';
        }
        return $id;
    }

    public final function enqueue() {
        $this->main_post_id = $this->get_current_page_id();
        $assets = $this->get_data( $this->main_post_id );
        if ( !empty( $assets ) ) {
            do_action( 'WP_Speedo/Enqueue_Assets/' . $this->get_assets_key(), $assets );
        }
    }

    public final function build( array $settings ) {
        if ( !empty( $settings ) ) {
            do_action( 'WP_Speedo/Build_Assets/' . $this->get_assets_key(), $this->get_current_page_id(), $settings );
        }
        return $this;
    }

    public function add_item_in_asset_list( $type, $item, $item_data = [] ) {
        if ( empty( $this->assets ) ) {
            $this->assets = $this->get_model();
        }
        if ( !array_key_exists( $item, $this->assets[$type] ) ) {
            $this->assets[$type][$item] = $item_data;
        } else {
            if ( $item == 'inline' ) {
                $this->assets[$type][$item] = $item_data;
            } else {
                foreach ( $item_data as $dep ) {
                    if ( !in_array( $dep, $this->assets[$type][$item] ) ) {
                        $this->assets[$type][$item][] = $dep;
                    }
                }
            }
        }
    }

    public final function maybe_build_assets_data( $main_post_id, array $settings ) {
        $this->generate_hooked = true;
        $this->main_post_id = $main_post_id;
        if ( empty( $settings ) ) {
            return;
        }
        $process_id = $main_post_id . '_' . $settings['id'];
        if ( in_array( $process_id, $this->processed ) ) {
            return;
        }
        $this->processed[] = $process_id;
        $this->build_assets_data( $settings );
    }

    public final function maybe_save_assets_data() {
        // Do not go below where shortcode is not used
        if ( !$this->generate_hooked ) {
            return;
        }
        if ( empty( $this->main_post_id ) ) {
            return;
        }
        // If already has data return
        if ( !empty( $this->get_data( $this->main_post_id ) ) ) {
            return;
        }
        if ( in_array( $this->main_post_id, $this->get_backward_ids() ) ) {
            update_option( $this->get_save_option_key( $this->main_post_id ), $this->assets );
        } else {
            update_post_meta( $this->main_post_id, $this->get_save_key(), $this->assets );
        }
    }

    public final function purge_assets_data_from_post_meta( $post_id = null ) {
        if ( !empty( $post_id ) ) {
            delete_post_meta( $post_id, $this->get_save_key() );
            return;
        }
        global $wpdb;
        $ids = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = %s", $this->get_save_key() ) );
        if ( empty( $ids ) ) {
            return;
        }
        $ids = implode( ',', array_map( 'absint', wp_list_pluck( $ids, 'meta_id' ) ) );
        $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_id IN({$ids})" );
    }

    public final function purge_assets_data_from_options() {
        global $wpdb;
        $ids = $wpdb->get_results( $wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name LIKE %s", '%' . $this->get_save_key() . '%' ) );
        if ( empty( $ids ) ) {
            return;
        }
        $ids = implode( ',', array_map( 'absint', wp_list_pluck( $ids, 'option_id' ) ) );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_id IN({$ids})" );
    }

    public final function widget_updated__purge( $instance ) {
        $this->assets_purge_all();
        return $instance;
    }

    public final function post_updated__purge( $post_id ) {
        $this->purge_assets_data_from_post_meta( $post_id );
    }

    public final function assets_purge_all() {
        $this->purge_assets_data_from_post_meta();
        $this->purge_assets_data_from_options();
    }

    public final function force_enqueue() {
        do_action( 'WP_Speedo/Enqueue_Assets_Force/' . $this->get_assets_key() );
    }

    public final function add_dependency_scripts( $handle, $scripts ) {
        add_action( 'wp_footer', function () use($handle, $scripts) {
            global $wp_scripts;
            if ( empty( $scripts ) || empty( $handle ) ) {
                return;
            }
            if ( !isset( $wp_scripts->registered[$handle] ) ) {
                return;
            }
            $wp_scripts->registered[$handle]->deps = array_unique( array_merge( $wp_scripts->registered[$handle]->deps, $scripts ) );
        } );
    }

    public final function add_dependency_styles( $handle, $styles ) {
        global $wp_styles;
        if ( empty( $styles ) || empty( $handle ) ) {
            return;
        }
        if ( !isset( $wp_styles->registered[$handle] ) ) {
            return;
        }
        $wp_styles->registered[$handle]->deps = array_unique( array_merge( $wp_styles->registered[$handle]->deps, $styles ) );
    }

    public final function enqueue_assets( $assets = [] ) {
        if ( empty( $assets ) ) {
            return;
        }
        if ( !empty( $assets['fonts'] ) ) {
            $this->enqueue_font_assets( $assets['fonts'] );
        }
        if ( !empty( $assets['styles'] ) ) {
            $this->enqueue_style_assets( $assets['styles'] );
        }
        if ( !empty( $assets['scripts'] ) ) {
            $this->enqueue_script_assets( $assets['scripts'] );
        }
    }

    public final function enqueue_style_assets( $styles = [] ) {
        foreach ( $styles as $asset => $data ) {
            if ( $asset == 'inline' ) {
                if ( !empty( $data ) ) {
                    wp_style_is( $this->asset_handler(), 'enqueued' ) && wp_style_add_data( $this->asset_handler(), 'after', '' );
                    wp_add_inline_style( $this->asset_handler(), $data );
                }
            } else {
                $this->add_dependency_styles( $asset, $data );
            }
        }
        wp_enqueue_style( $this->asset_handler() );
        if ( $this->is_preview() ) {
            wp_enqueue_style( $this->asset_handler() . '-preview' );
        }
    }

    public final function enqueue_script_assets( $scripts = [] ) {
        foreach ( $scripts as $asset => $data ) {
            if ( $asset == 'inline' ) {
                if ( !empty( $data ) ) {
                    wp_add_inline_script( $this->asset_handler(), $data );
                }
            } else {
                $this->add_dependency_scripts( $asset, $data );
            }
        }
        wp_enqueue_script( $this->asset_handler() );
        if ( $this->is_preview() && !$this->is_frame_loading() ) {
            wp_enqueue_script( $this->asset_handler() . '-preview' );
        }
    }

    public final function enqueue_font_assets( $fonts = [] ) {
        foreach ( $fonts as $asset => $data ) {
            foreach ( $data as $font ) {
                $this->enqueue_font( $font );
            }
        }
    }

    public final function enqueue_font( $font ) {
        if ( in_array( $font, $this->registered_fonts ) ) {
            return;
        }
        $this->fonts_to_enqueue[] = $font;
        $this->registered_fonts[] = $font;
    }

    public function print_fonts_links() {
    }

    public function get_list_of_google_fonts_by_type() {
        $google_fonts = [
            'google' => [],
            'early'  => [],
        ];
        foreach ( $this->fonts_to_enqueue as $key => $font ) {
            $font_type = Fonts::get_font_type( $font );
            switch ( $font_type ) {
                case Fonts::GOOGLE:
                    $google_fonts['google'][] = $font;
                    break;
                case Fonts::EARLYACCESS:
                    $google_fonts['early'][] = $font;
                    break;
                default:
                    do_action( "wps_team/fonts/print_font_links/{$font_type}", $font );
            }
        }
        $this->fonts_to_enqueue = [];
        return $google_fonts;
    }

    private function enqueue_google_fonts( $google_fonts = [] ) {
        // Print used fonts
        if ( !empty( $google_fonts['google'] ) ) {
            $this->google_fonts_index++;
            $fonts_url = $this->get_stable_google_fonts_url( $google_fonts['google'] );
            wp_enqueue_style( 'google-fonts-' . $this->google_fonts_index, $fonts_url );
            // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        }
        if ( !empty( $google_fonts['early'] ) ) {
            $early_access_font_urls = $this->get_early_access_google_font_urls( $google_fonts['early'] );
            foreach ( $early_access_font_urls as $ea_font_url ) {
                $this->google_fonts_index++;
                wp_enqueue_style( 'google-earlyaccess-' . $this->google_fonts_index, $ea_font_url );
                // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
            }
        }
    }

    private function print_font_by_js( $id, $url ) {
        ?>
        <script>
            var link_id = '<?php 
        echo $id;
        ?>', link_url = '<?php 
        echo $url;
        ?>';
            if ( ! document.getElementById( link_id ) ) {
                var link = document.createElement('link');
                link.id = link_id;
                link.rel = 'stylesheet';
                link.href = link_url;
                document.head.appendChild(link);
            }
        </script>
        <?php 
    }

    public function load_fonts_on_preview( $settings ) {
        $this->add_item_in_asset_list( 'fonts', $this->asset_handler(), $this->get_widget_fonts( $settings ) );
        foreach ( $this->assets['fonts'] as $asset => $data ) {
            foreach ( $data as $font ) {
                $this->enqueue_font( $font );
            }
        }
        $google_fonts = $this->get_list_of_google_fonts_by_type();
        $this->enqueue_google_fonts_force( $google_fonts );
    }

    protected function enqueue_google_fonts_force( $google_fonts = [] ) {
        // Print used fonts
        if ( !empty( $google_fonts['google'] ) ) {
            $this->google_fonts_index++;
            $fonts_url = $this->get_stable_google_fonts_url( $google_fonts['google'] );
            $this->print_font_by_js( 'google-fonts-' . $this->google_fonts_index, $fonts_url );
        }
        if ( !empty( $google_fonts['early'] ) ) {
            $early_access_font_urls = $this->get_early_access_google_font_urls( $google_fonts['early'] );
            foreach ( $early_access_font_urls as $ea_font_url ) {
                $this->google_fonts_index++;
                $this->print_font_by_js( 'google-fonts-' . $this->google_fonts_index, $ea_font_url );
            }
        }
    }

    public function get_early_access_google_font_urls( array $fonts ) : array {
        $font_urls = [];
        foreach ( $fonts as $font ) {
            $font_urls[] = sprintf( 'https://fonts.googleapis.com/earlyaccess/%s.css', strtolower( str_replace( ' ', '', $font ) ) );
        }
        return $font_urls;
    }

    public function get_stable_google_fonts_url( array $fonts ) {
        foreach ( $fonts as &$font ) {
            $font = str_replace( ' ', '+', $font ) . ':100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic';
        }
        // Defining a font-display type to google fonts.
        $font_display_url_str = '&display=' . 'swap';
        // Fonts::get_font_display_setting(); @todo
        $fonts_url = sprintf( 'https://fonts.googleapis.com/css?family=%1$s%2$s', implode( rawurlencode( '|' ), $fonts ), $font_display_url_str );
        $subsets = [
            'ru_RU' => 'cyrillic',
            'bg_BG' => 'cyrillic',
            'he_IL' => 'hebrew',
            'el'    => 'greek',
            'vi'    => 'vietnamese',
            'uk'    => 'cyrillic',
            'cs_CZ' => 'latin-ext',
            'ro_RO' => 'latin-ext',
            'pl_PL' => 'latin-ext',
            'hr_HR' => 'latin-ext',
            'hu_HU' => 'latin-ext',
            'sk_SK' => 'latin-ext',
            'tr_TR' => 'latin-ext',
            'lt_LT' => 'latin-ext',
        ];
        $subsets = apply_filters( 'wps_team/google_font_subsets', $subsets );
        $locale = get_locale();
        if ( isset( $subsets[$locale] ) ) {
            $fonts_url .= '&subset=' . $subsets[$locale];
        }
        return $fonts_url;
    }

    public function print_google_fonts_preconnect_tag() {
        // if ( Utils::get_setting('disable_google_fonts_loading') ) return;
        if ( 0 >= $this->google_fonts_index ) {
            return;
        }
        echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    }

    public final function force_enqueue_assets() {
        $this->register_assets();
        if ( $this->is_preview() ) {
            $this->build_assets_data_preview();
        }
        $this->enqueue_assets( $this->assets );
    }

    public abstract function is_preview();

    public abstract function is_frame_loading();

    public abstract function get_assets_key();

    public abstract function public_scripts();

    public abstract function asset_handler();

    public abstract function register_assets();

    public abstract function build_assets_data( array $settings );

    public abstract function build_assets_data_preview();

    public abstract function get_widget_fonts( $settings );

}
