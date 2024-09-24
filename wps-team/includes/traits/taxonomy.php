<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
trait Taxonomy
{
    public function add_taxonomies_menu() {
        global $pagenow;
        if ( $pagenow !== 'edit-tags.php' ) {
            return;
        }
        $taxonomy = $_GET['taxonomy'];
        $this->load_taxonomies_template( $taxonomy );
        ?>
        <script>window.location.href.indexOf('/edit-tags.php') > -1 && document.querySelector(`a[href='edit.php?post_type=wps-team-members&page=taxonomies']`).parentElement.classList.add('current');</script>
        <?php 
    }

    public function custom_taxonomies_menu() {
        if ( empty( Utils::get_active_taxonomies() ) ) {
            return;
        }
        $taxonomy_menu_title = _x( 'Taxonomies', 'Menu Label', 'wpspeedo-team' );
        add_submenu_page(
            Utils::get_top_label_menu(),
            $taxonomy_menu_title,
            $taxonomy_menu_title,
            'manage_options',
            'taxonomies',
            [$this, 'custom_taxonomies_menu__callback']
        );
    }

    public function custom_taxonomies_menu__callback() {
        $taxonomy = $this->load_taxonomies_template();
        if ( !$taxonomy ) {
            return;
        }
        ?>
        <script>
            window.location.href = '<?php 
        echo admin_url( sprintf( 'edit-tags.php?taxonomy=%s&post_type=wps-team-members', $taxonomy ) );
        ?>';
        </script>
        <?php 
    }

    public function load_taxonomies_template( $taxonomy = '' ) {
        $active_taxonomies = Utils::get_active_taxonomies();
        $taxonomy_roots = Utils::get_taxonomy_roots( true );
        if ( empty( $taxonomy ) ) {
            if ( isset( $_GET['taxonomy'] ) ) {
                $taxonomy = $_GET['taxonomy'];
            } else {
                if ( !empty( $active_taxonomies ) ) {
                    $taxonomy = $active_taxonomies[0];
                } else {
                    $taxonomy = Utils::get_taxonomy_name( $taxonomy_roots[0] );
                }
            }
            if ( in_array( $taxonomy, $active_taxonomies ) ) {
                return $taxonomy;
            }
        }
        $post_type_name = Utils::post_type_name();
        $page_tax_key = Utils::get_taxonomy_key( $taxonomy );
        $enable_taxonomy = 'enable_' . $page_tax_key . '_taxonomy';
        $enable_archive = 'enable_' . $page_tax_key . '_archive';
        $tax_plural_name = $page_tax_key . '_plural_name';
        $tax_single_name = $page_tax_key . '_single_name';
        $tax_archive_slug = $page_tax_key . '_slug';
        ?>

        <div class="wps-team--taxonomies-container">
            <div class="wps-team--taxonomies-wrapper">
                
                <ul class="wps-team--taxonomies">
                    <?php 
        foreach ( $taxonomy_roots as $tax_root ) {
            $tax_root_key = Utils::to_field_key( $tax_root );
            $_taxonomy = Utils::get_taxonomy_name( $tax_root );
            if ( in_array( $_taxonomy, $active_taxonomies ) ) {
                $tax_page_url = admin_url( sprintf( 'edit-tags.php?taxonomy=%s&post_type=%s', esc_attr( $_taxonomy ), esc_attr( $post_type_name ) ) );
                $terms_count = sprintf( '<span class="wps-team--term-count">%d</span>', (int) wp_count_terms( $_taxonomy ) );
            } else {
                $tax_page_url = admin_url( sprintf( 'edit.php?post_type=%s&page=taxonomies&taxonomy=%s', esc_attr( $post_type_name ), esc_attr( $_taxonomy ) ) );
                $terms_count = '';
            }
            printf(
                '<li><a class="button button-%s" href="%s">%s %s</a></li>',
                ( $_taxonomy === $taxonomy ? 'primary' : 'secondary' ),
                esc_url( $tax_page_url ),
                esc_html( Utils::get_setting( $tax_root_key . '_plural_name' ) ),
                $terms_count
            );
        }
        ?>
                </ul>

                <?php 
        if ( in_array( Utils::get_taxonomy_root( $taxonomy ), Utils::get_taxonomy_roots() ) ) {
            ?>
                    <form id="wps-team--taxonomy-settings-form" class="wps-team--taxonomy-settings">
    
                        <?php 
            $val_enable_taxonomy = Utils::get_setting( $enable_taxonomy );
            $val_enable_archive = Utils::get_setting( $enable_archive );
            ?>
    
                        <div class="wps-team--tax_setting--field wps-team--field_switcher wps-team--enable_taxonomy">
                            <label for="wps-team--enable_taxonomy">
                                <?php 
            echo esc_html_x( 'Enable Taxonomy:', 'Settings: Taxonomy', 'wpspeedo-team' );
            ?>
                                <input type="checkbox" id="wps-team--enable_taxonomy" name="<?php 
            echo esc_attr( $enable_taxonomy );
            ?>" <?php 
            checked( Utils::get_setting( $enable_taxonomy ) );
            ?> />
                                <span class="wps-team--switcher-ui"></span>
                            </label>
                        </div>
    
                        <div class="wps-team--tax_setting--field wps-team--tax_plural_name <?php 
            echo ( !$val_enable_taxonomy ? 'wps-team--field-disabled' : '' );
            ?>">
                            <label for="wps-team--tax_plural_name"><?php 
            echo esc_html_x( 'Plural Name:', 'Settings: Taxonomy', 'wpspeedo-team' );
            ?></label>
                            <input type="text" id="wps-team--tax_plural_name" name="<?php 
            echo esc_attr( $tax_plural_name );
            ?>" value="<?php 
            echo Utils::get_setting( $page_tax_key . '_plural_name' );
            ?>" />
                        </div>
    
                        <div class="wps-team--tax_setting--field wps-team--tax_single_name <?php 
            echo ( !$val_enable_taxonomy ? 'wps-team--field-disabled' : '' );
            ?>">
                            <label for="wps-team--tax_single_name"><?php 
            echo esc_html_x( 'Single Name:', 'Settings: Taxonomy', 'wpspeedo-team' );
            ?></label>
                            <input type="text" id="wps-team--tax_single_name" name="<?php 
            echo esc_attr( $tax_single_name );
            ?>" value="<?php 
            echo Utils::get_setting( $page_tax_key . '_single_name' );
            ?>" />
                        </div>
    
                        <div class="wps-team--tax_setting--field wps-team--field_switcher wps-team--enable_tax_archive <?php 
            echo ( !$val_enable_taxonomy ? 'wps-team--field-disabled' : '' );
            ?>">
                            <label for="wps-team--enable_tax_archive">
                                <?php 
            echo esc_html_x( 'Enable Archive:', 'Settings: Taxonomy', 'wpspeedo-team' );
            ?>
                                <input type="checkbox" id="wps-team--enable_tax_archive" name="<?php 
            echo esc_attr( $enable_archive );
            ?>" <?php 
            checked( Utils::get_setting( 'enable_' . $page_tax_key . '_archive' ) );
            ?> />
                                <span class="wps-team--switcher-ui"></span>
                            </label>
                        </div>
    
                        <div class="wps-team--tax_setting--field wps-team--tax_archive_name <?php 
            echo ( !$val_enable_taxonomy || !$val_enable_archive ? 'wps-team--field-disabled' : '' );
            ?>">
                            <label for="wps-team--tax_archive_name"><?php 
            echo esc_html_x( 'Archive Slug:', 'Settings: Taxonomy', 'wpspeedo-team' );
            ?></label>
                            <input type="text" id="wps-team--tax_archive_name" name="<?php 
            echo esc_attr( $tax_archive_slug );
            ?>" value="<?php 
            echo Utils::get_setting( $page_tax_key . '_slug' );
            ?>" />
                        </div>
    
                        <div class="wps-team--tax_setting--field wps-team--tax-save-btn">
                            <button class="button button-primary"><?php 
            echo esc_html_x( 'Save', 'Settings: Taxonomy', 'wpspeedo-team' );
            ?></button>
                        </div>
    
                    </form>
                <?php 
        } else {
            ?>
                    <!-- Add Pro message with premium link -->
                    <div class="wps-team--taxonomy-pro_message">
                        <div style="max-width: 300px">
                            <h3 style="margin-top: 0"><?php 
            echo esc_html_x( 'Pro Feature', 'Settings: Taxonomy', 'wpspeedo-team' );
            ?></h3>
                            <p><?php 
            echo esc_html_x( 'If you love our work please support us by purchasing our Premium plugin.', 'Settings: Taxonomy', 'wpspeedo-team' );
            ?></p>
                            <a href="https://wpspeedo.com/products/wps-team-pro/" class="button button-primary" target="_blank">
                                <?php 
            echo esc_html_x( 'Upgrade to Pro', 'Settings: Taxonomy', 'wpspeedo-team' );
            ?>
                            </a>
                        </div>
                    </div>
                <?php 
        }
        ?>

            </div>
        </div>

        <style>

            .wps-team--taxonomies-container {
                padding-top:20px;
                padding-right:20px;
                padding-bottom:0;
            }

            .wps-team--taxonomies {
                display:flex;
                flex-wrap:wrap;
                margin:0;
                gap:16px;
                margin-top: 10px;
            }

            .wps-team--taxonomies li{
                margin:0
            }

            .wps-team--taxonomies li a.button {
                display:block;
                padding:4px 18px;
                margin:0!important;
                font-size: 14px;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .wps-team--taxonomies li a.button .wps-team--term-count {
                background: rgb(0 0 0 / 15%);
                padding: 3px;
                min-width: 12px;
                display: inline-block;
                text-align: center;
                line-height: 1;
                border-radius: 8px;
                font-size: 0.85em;
            }

            .wps-team--taxonomies li a.button.button-secondary {
                color: #3d3e43;
                border-color: #bbbec5;
                background: #fff;
            }

            .wps-team--taxonomy-settings {
                margin-top: 30px;
                padding: 20px;
                display: flex;
                align-items: center;
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                gap: 30px;
            }

            .wps-team--taxonomy-pro_message {
                margin-top: 30px;
                padding: 30px;
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
            }

            .wps-team--taxonomy-pro_message h3 {
                font-size: 20px;
                color: #212121;
            }

            .wps-team--taxonomy-pro_message p {
                font-size: 14px;
                color: #37373a;
                line-height: 1.8;
            }

            .wps-team--taxonomy-pro_message .button {
                padding: 4px 16px;
                font-size: 14px;
                font-weight: 500;
            }

            .wps-team--tax_setting--field label {
                cursor: pointer;
                user-select: none;
                font-size: 14px;
            }

            .wps-team--tax_setting--field,
            .wps-team--field_switcher label {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .wps-team--field_switcher input {
                display: none;
            }

            .wps-team--field_switcher .wps-team--switcher-ui {
                --wps-team--switcher--width: 54px;
                --wps-team--switcher--height: 30px;
                --wps-team--switcher--color: #dbe5ed;

                position: relative;
                display: inline-block;
                width: var(--wps-team--switcher--width);
                height: var(--wps-team--switcher--height);
                transition: all .15s ease;
            }

            .wps-team--field_switcher .wps-team--switcher-ui:before {
                content: "";
                width: 100%;
                height: 100%;
                display: inline-block;
                background: var(--wps-team--switcher--color);
                border-radius: 100px;
                transition: inherit;
            }
            .wps-team--field_switcher .wps-team--switcher-ui:after {
                content: "";
                position: absolute;
                background: red;
                width: calc( var(--wps-team--switcher--height) - 8px );
                height: calc( var(--wps-team--switcher--height) - 8px );
                border-radius: 50%;
                background-color: #fff;
                background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/PjxzdmcgaGVpZ2h0PSI1MTJweCIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTEyIDUxMjsiIHZpZXdCb3g9IjAgMCA1MTIgNTEyIiB3aWR0aD0iNTEycHgiIHhtbDpzcGFjZT0icHJlc2VydmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPjxwYXRoIGZpbGw9InJnYigxOTIgMjAzIDIxMykiIGQ9Ik00MzcuNSwzODYuNkwzMDYuOSwyNTZsMTMwLjYtMTMwLjZjMTQuMS0xNC4xLDE0LjEtMzYuOCwwLTUwLjljLTE0LjEtMTQuMS0zNi44LTE0LjEtNTAuOSwwTDI1NiwyMDUuMUwxMjUuNCw3NC41ICBjLTE0LjEtMTQuMS0zNi44LTE0LjEtNTAuOSwwYy0xNC4xLDE0LjEtMTQuMSwzNi44LDAsNTAuOUwyMDUuMSwyNTZMNzQuNSwzODYuNmMtMTQuMSwxNC4xLTE0LjEsMzYuOCwwLDUwLjkgIGMxNC4xLDE0LjEsMzYuOCwxNC4xLDUwLjksMEwyNTYsMzA2LjlsMTMwLjYsMTMwLjZjMTQuMSwxNC4xLDM2LjgsMTQuMSw1MC45LDBDNDUxLjUsNDIzLjQsNDUxLjUsNDAwLjYsNDM3LjUsMzg2LjZ6Ii8+PC9zdmc+);
                background-size: 60%;
                background-repeat: no-repeat;
                background-position: center;
                top: 4px;
                left: 4px;
                transition: inherit;
            }

            .wps-team--field_switcher:has( input:checked ) .wps-team--switcher-ui {
                --wps-team--switcher--color: #2271b1;
            }

            .wps-team--field_switcher:has( input:checked ) .wps-team--switcher-ui:after {
                left: calc( var(--wps-team--switcher--width) - var(--wps-team--switcher--height) + 4px );
                background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pjxzdmcgdmlld0JveD0iMCAwIDUxMiA1MTIiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZmlsbD0iIzIyNzFiMSIgZD0iTTE3My44OTggNDM5LjQwNGwtMTY2LjQtMTY2LjRjLTkuOTk3LTkuOTk3LTkuOTk3LTI2LjIwNiAwLTM2LjIwNGwzNi4yMDMtMzYuMjA0YzkuOTk3LTkuOTk4IDI2LjIwNy05Ljk5OCAzNi4yMDQgMEwxOTIgMzEyLjY5IDQzMi4wOTUgNzIuNTk2YzkuOTk3LTkuOTk3IDI2LjIwNy05Ljk5NyAzNi4yMDQgMGwzNi4yMDMgMzYuMjA0YzkuOTk3IDkuOTk3IDkuOTk3IDI2LjIwNiAwIDM2LjIwNGwtMjk0LjQgMjk0LjQwMWMtOS45OTggOS45OTctMjYuMjA3IDkuOTk3LTM2LjIwNC0uMDAxeiIvPjwvc3ZnPg==);
            }

            .wps-team--tax-save-btn {
                margin-top: 26px;
            }
            
            .wps-team--field-disabled {
                opacity: 0.3;
                user-select: none;
                position: relative;
            }
            .wps-team--field-disabled:after {
                content: "";
                position: absolute;
                width: 100%;
                height: 100%;
                top: 0;
                left: 0;
                z-index: 2;
            }

            @media(max-width:782px){
                .wps-team--taxonomies-container{
                    padding-right:10px;
                    padding-top:12px
                }
                .wps-team--taxonomies{
                    gap:10px
                }
            }
            @media(max-width:600px){
                .wps-team--taxonomies-container{
                    margin-bottom:-46px;
                    padding-top:58px
                }
            }
        </style>

        <script>
            
            jQuery(function($) {

                function update_field_visibility() {

                    if ( $('#wps-team--enable_taxonomy').is(':checked') ) {
                        $('.wps-team--tax_setting--field:not(.wps-team--enable_taxonomy)').removeClass('wps-team--field-disabled');

                        if ( $('#wps-team--enable_tax_archive').is(':checked') ) {
                            $('.wps-team--tax_setting--field.wps-team--tax_archive_name').removeClass('wps-team--field-disabled');
                        } else {
                            $('.wps-team--tax_setting--field.wps-team--tax_archive_name').addClass('wps-team--field-disabled');
                        }

                    } else {
                        $('.wps-team--tax_setting--field:not(.wps-team--enable_taxonomy, .wps-team--tax-save-btn)').addClass('wps-team--field-disabled');
                    }

                }

                update_field_visibility();

                $('#wps-team--enable_taxonomy').on('change', update_field_visibility);
                $('#wps-team--enable_tax_archive').on('change', update_field_visibility);

                $('#wps-team--taxonomy-settings-form').on('submit', function(e) {
                    e.preventDefault();
                    const data = {
                        action: 'wpspeedo_team_ajax_handler',
                        route: 'save_taxonomy_settings',
                        taxonomy: '<?php 
        echo $taxonomy;
        ?>',
                        _wpnonce: '<?php 
        echo wp_create_nonce( '_wpspeedo_team_nonce' );
        ?>',
                        settings: $(this).serializeArray()
                    };
                    $.post(ajaxurl, data, function(response) {
                        if ( response.success && response.data.tax_page_url ) {
                            window.location.href = response.data.tax_page_url
                        }
                    });
                });

            });

        </script>

        <?php 
    }

    public function get_taxonomy_args( $single_name, $plural_name ) {
        $single_name = ucfirst( $single_name );
        $plural_name = ucfirst( $plural_name );
        $plural_name_lc = lcfirst( $plural_name );
        $labels = array(
            'name'                       => $plural_name,
            'singular_name'              => $single_name,
            'menu_name'                  => $plural_name,
            'all_items'                  => sprintf( _x( 'All %s', 'Team Taxonomy', 'wpspeedo-team' ), $plural_name ),
            'popular_items'              => sprintf( _x( 'Popular %s', 'Team Taxonomy', 'wpspeedo-team' ), $plural_name ),
            'search_items'               => sprintf( _x( 'Search %s', 'Team Taxonomy', 'wpspeedo-team' ), $plural_name ),
            'items_list'                 => sprintf( _x( '%s list', 'Team Taxonomy', 'wpspeedo-team' ), $plural_name ),
            'items_list_navigation'      => sprintf( _x( '%s list navigation', 'Team Taxonomy', 'wpspeedo-team' ), $plural_name ),
            'separate_items_with_commas' => sprintf( _x( 'Separate %s with commas', 'Team Taxonomy', 'wpspeedo-team' ), $plural_name_lc ),
            'no_terms'                   => sprintf( _x( 'No %s', 'Team Taxonomy', 'wpspeedo-team' ), $plural_name_lc ),
            'add_or_remove_items'        => sprintf( _x( 'Add or remove %s', 'Team Taxonomy', 'wpspeedo-team' ), $plural_name_lc ),
            'parent_item'                => sprintf( _x( 'Parent %s', 'Team Taxonomy', 'wpspeedo-team' ), $single_name ),
            'parent_item_colon'          => sprintf( _x( 'Parent %s:', 'Team Taxonomy', 'wpspeedo-team' ), $single_name ),
            'new_item_name'              => sprintf( _x( 'New %s Name', 'Team Taxonomy', 'wpspeedo-team' ), $single_name ),
            'add_new_item'               => sprintf( _x( 'Add New %s', 'Team Taxonomy', 'wpspeedo-team' ), $single_name ),
            'edit_item'                  => sprintf( _x( 'Edit %s', 'Team Taxonomy', 'wpspeedo-team' ), $single_name ),
            'update_item'                => sprintf( _x( 'Update %s', 'Team Taxonomy', 'wpspeedo-team' ), $single_name ),
            'view_item'                  => sprintf( _x( 'View %s', 'Team Taxonomy', 'wpspeedo-team' ), $single_name ),
            'choose_from_most_used'      => _x( 'Choose from the most used', 'Team Taxonomy', 'wpspeedo-team' ),
            'not_found'                  => _x( 'Not Found', 'Team Taxonomy', 'wpspeedo-team' ),
        );
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => false,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => false,
        );
        return $args;
    }

    /*
     * Register Custom Taxonomies
     */
    public function register_taxonomies() {
        $taxonomies = Utils::get_active_taxonomies();
        foreach ( $taxonomies as $taxonomy ) {
            $taxonomy_key = Utils::get_taxonomy_key( $taxonomy );
            $args = $this->get_taxonomy_args( Utils::get_setting( $taxonomy_key . '_single_name' ), Utils::get_setting( $taxonomy_key . '_plural_name' ) );
            if ( Utils::has_archive() && Utils::has_archive( $taxonomy_key ) ) {
                $args['public'] = true;
                $args['rewrite'] = [
                    'slug'         => Utils::get_archive_slug( $taxonomy_key ),
                    'with_front'   => true,
                    'hierarchical' => false,
                ];
            }
            register_taxonomy( $taxonomy, array(Utils::post_type_name()), $args );
        }
    }

}