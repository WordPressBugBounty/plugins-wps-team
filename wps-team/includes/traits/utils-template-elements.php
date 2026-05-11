<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
trait Utils_Template_Elements
{
    public static function get_post_link_attrs( int $post_id, $shortcode_id = null, $action = 'single-page' ) {
        $attrs = [
            'href'   => '',
            'class'  => '',
            'target' => '',
            'rel'    => '',
        ];
        if ( $action === 'single-page' && Utils::has_singular_page() ) {
            $attrs['href'] = get_the_permalink( $post_id );
            $attrs['class'] = 'wpspeedo-team--url';
        }
        $attrs = apply_filters(
            'wpspeedo_team/post_link_attrs',
            $attrs,
            $action,
            $post_id,
            $shortcode_id
        );
        return $attrs;
    }

    public static function get_the_title( int $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'card_action' => 'single-page',
            'tag'         => 'h3',
            'class'       => '',
        ], $args );
        $action = Utils::normalize_card_action( (string) $args['card_action'], $post_id );
        $tag_name = (string) $args['tag'];
        $title_classes = ['wps-team--member-title', 'wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $title_classes[] = $args['class'];
        }
        if ( $action !== 'none' ) {
            $title_classes[] = 'team-member--link';
        }
        $title_open = sprintf( '<%s class="%s">', esc_attr( $tag_name ), esc_attr( Utils::join_classes( $title_classes ) ) );
        $title_text = get_the_title( $post_id );
        if ( $action === 'none' ) {
            $content = esc_html( $title_text );
        } else {
            $link_attrs = Utils::get_link_attrs_for_post( (int) $post_id, $action );
            $content = Utils::render_link( $link_attrs, esc_html( $title_text ) );
        }
        printf(
            '%s%s</%s>',
            $title_open,
            $content,
            esc_attr( $tag_name )
        );
        // phpcs:ignore WordPress.Security.EscapeOutput
    }

    public static function get_render_info( string $element, string $context = 'general' ) {
        if ( $context == 'general' ) {
            return Utils::shortcode_loader()->get_setting( "show_{$element}" );
        }
        if ( $context == 'details' ) {
            return Utils::shortcode_loader()->get_setting( "show_details_{$element}" );
        }
        if ( $context == 'single' ) {
            return Utils::get_setting( "single_{$element}" );
        }
    }

    public static function is_allowed_render( string $element, string $context = 'general', bool $force_show = false ) {
        if ( $force_show ) {
            return true;
        }
        $render_info = self::get_render_info( $element, $context );
        if ( $render_info == 'false' ) {
            return false;
        }
        return true;
    }

    public static function is_allowed_render_alt( string $element, string $context = 'general', bool $force_hide = false ) {
        if ( $force_hide ) {
            return false;
        }
        $render_info = self::get_render_info( $element, $context );
        if ( $render_info == 'true' ) {
            return true;
        }
        return false;
    }

    public static function get_the_thumbnail( int $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context'               => 'general',
            'card_action'           => 'single-page',
            'thumbnail_type'        => 'image',
            'thumbnail_size'        => 'large',
            'thumbnail_size_custom' => [],
            'force_show'            => false,
            'tag'                   => 'div',
            'class'                 => '',
            'allow_ribbon'          => false,
        ], $args );
        if ( !self::is_allowed_render( 'thumbnail', $args['context'], (bool) $args['force_show'] ) ) {
            return '';
        }
        $action = Utils::normalize_card_action( (string) $args['card_action'], $post_id );
        $wrapper_tag = (string) $args['tag'];
        $wrapper_classes = ['team-member--thumbnail-wrapper', 'wps-team--member-element'];
        $thumbnail_container = ['team-member--thumbnail'];
        $thumb_img_extra_class = '';
        $thumbnail_size = $args['thumbnail_size'];
        $gallery_html = '';
        $args['thumbnail_type'] = 'image';
        $html = sprintf( '<%s class="%s">', esc_attr( $wrapper_tag ), esc_attr( Utils::join_classes( $wrapper_classes ) ) );
        $html .= sprintf( '<div class="%s">', esc_attr( Utils::join_classes( $thumbnail_container ) ) );
        if ( wps_team_fs()->can_use_premium_code__premium_only() && $args['thumbnail_type'] === 'carousel' ) {
            $html .= '<div class="swiper-wrapper">';
        }
        if ( $action === 'none' ) {
            $html .= get_the_post_thumbnail( $post_id, $thumbnail_size, [
                'class' => $thumb_img_extra_class,
            ] );
            // phpcs:ignore WordPress.Security.EscapeOutput
            $html .= $gallery_html;
            // phpcs:ignore WordPress.Security.EscapeOutput
        } else {
            $link_attrs = Utils::get_link_attrs_for_post( (int) $post_id, $action );
            $aria = sprintf( 
                /* translators: %s: Post title. */
                esc_attr_x( 'Read More about %s.', 'Public', 'wps-team' ),
                get_the_title( $post_id )
             );
            $inner = get_the_post_thumbnail( $post_id, $thumbnail_size );
            // phpcs:ignore WordPress.Security.EscapeOutput
            $inner .= $gallery_html;
            // phpcs:ignore WordPress.Security.EscapeOutput
            $html .= Utils::render_link( $link_attrs, $inner, [
                'aria-label' => $aria,
            ] );
        }
        if ( wps_team_fs()->can_use_premium_code__premium_only() && $args['thumbnail_type'] === 'carousel' ) {
            $html .= '</div><div class="wps-team--carousel-navs"><button class="swiper-button-prev" tabindex="0" aria-label="Previous slide"><i aria-hidden="true" class="fas fa-chevron-left"></i></button><button class="swiper-button-next" tabindex="0" aria-label="Next slide"><i aria-hidden="true" class="fas fa-chevron-right"></i></button></div><div class="swiper-pagination"></div>';
        }
        echo $html;
        // phpcs:ignore WordPress.Security.EscapeOutput
        if ( !empty( $args['allow_ribbon'] ) ) {
            Utils::get_the_ribbon( get_the_ID() );
        }
        printf( '</div></%s>', esc_attr( $wrapper_tag ) );
        // phpcs:ignore WordPress.Security.EscapeOutput
    }

    public static function get_the_ribbon( int $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
            'class'   => '',
        ], $args );
        $ribbon_render = self::get_render_info( 'ribbon', $args['context'] );
        $show_ribbon = ( $ribbon_render == '' ? false : wp_validate_boolean( $ribbon_render ) );
        if ( !$show_ribbon ) {
            return '';
        }
        $ribbon_classes = ['wps-team--member-ribbon wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $ribbon_classes[] = $args['class'];
        }
        $ribbon = Utils::get_item_data( '_ribbon', $post_id );
        if ( empty( $ribbon ) ) {
            return '';
        }
        printf( '<div class="%s">%s</div>', esc_attr( Utils::join_classes( $ribbon_classes ) ), esc_html( $ribbon ) );
    }

    public static function get_the_designation( int $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
            'tag'     => 'h4',
            'class'   => '',
        ], $args );
        if ( !self::is_allowed_render( 'designation', $args['context'] ) ) {
            return '';
        }
        $desig_classes = ['wps-team--member-desig wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $desig_classes[] = $args['class'];
        }
        $designation = Utils::get_item_data( '_designation', $post_id );
        if ( empty( $designation ) ) {
            return '';
        }
        printf(
            '<%1$s class="%2$s">%3$s</%1$s>',
            esc_attr( $args['tag'] ),
            esc_attr( Utils::join_classes( $desig_classes ) ),
            esc_html( $designation )
        );
    }

    public static function get_the_divider( $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
            'class'   => '',
        ], $args );
        if ( !self::is_allowed_render( 'divider', $args['context'] ) ) {
            return '';
        }
        $divider_classes = ['wps-team--divider-wrapper wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $divider_classes[] = $args['class'];
        }
        printf( '<div class="%s"><div class="wps-team--divider"></div></div>', esc_attr( Utils::join_classes( $divider_classes ) ) );
    }

    public static function get_description_length( $length = null ) {
        if ( $length == null ) {
            $length = Utils::shortcode_loader()->get_setting( 'description_length' );
        }
        if ( !$length || $length < 1 ) {
            return PHP_INT_MAX - 500;
        }
        return $length;
    }

    public static function get_the_excerpt( int $post_id, array $args = [] ) {
        $args = shortcode_atts( [
            'context'             => 'general',
            'tag'                 => 'div',
            'description_length'  => 110,
            'show_read_more_link' => false,
            'card_action'         => 'single-page',
        ], $args );
        if ( !self::is_allowed_render( 'description', $args['context'] ) ) {
            return '';
        }
        $tag_name = (string) $args['tag'];
        $max_length = (int) $args['description_length'];
        $read_more_text = (string) plugin()->translations->get( 'read_more_link_text', _x( 'Read More', 'Public', 'wps-team' ) );
        $read_more_plain = trim( wp_specialchars_decode( wp_strip_all_tags( $read_more_text ), ENT_QUOTES ) );
        $read_more_len = mb_strlen( $read_more_plain );
        $action = Utils::normalize_card_action( (string) $args['card_action'], $post_id );
        $read_more_link_html = '';
        $show_read_more_link = ( $args['show_read_more_link'] === '' || $args['show_read_more_link'] === null ? false : wp_validate_boolean( $args['show_read_more_link'] ) );
        if ( $max_length > 0 && $read_more_len > 0 && $show_read_more_link ) {
            if ( $action !== 'none' ) {
                $link_attrs = Utils::get_link_attrs_for_post( (int) $post_id, $action, 'wps-team--read-more-link' );
                $read_more_link_html = Utils::render_link( $link_attrs, $read_more_text );
                $max_length = max( 0, $max_length - $read_more_len );
            }
        }
        $trimmed = Utils::wp_trim_html_chars( get_the_excerpt( $post_id ), $max_length );
        $markup = wpautop( $trimmed . $read_more_link_html );
        printf( '<%1$s class="wps-team--member-details wps-team--member-details-excerpt wps-team--member-element">%2$s</%1$s>', esc_attr( $tag_name ), wp_kses_post( $markup ) );
    }

    public static function get_the_description( int $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
        ], $args );
        if ( !self::is_allowed_render( 'description', $args['context'] ) ) {
            return '';
        }
        ?>

		<div class="wps-team--member-details wps-team--member-element">
			<?php 
        self::get_the_content( $post_id );
        ?>
		</div>

		<?php 
    }

    public static function get_the_education_title( $args = [] ) {
        $args = shortcode_atts( [
            'title_tag' => 'h4',
        ], $args );
        $title_text = plugin()->translations->get( 'education_title', _x( 'Education:', 'Public', 'wps-team' ) );
        printf( '<%1$s class="wps-team--block-title team-member--education-title">%2$s</%1$s>', sanitize_key( $args['title_tag'] ), esc_html( $title_text ) );
    }

    public static function get_the_education( int $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context'    => 'general',
            'title_tag'  => 'h4',
            'show_title' => false,
        ], $args );
        if ( !self::is_allowed_render_alt( 'education', $args['context'] ) ) {
            return '';
        }
        $education = Utils::get_item_data( '_education' );
        if ( empty( $education ) ) {
            return;
        }
        ?>

		<div class="wps-team--member-education wps-team--member-element">
			<?php 
        if ( $args['show_title'] ) {
            self::get_the_education_title( $args );
        }
        ?>
			<div class="wps-team--member-details wps--education">
				<?php 
        echo wp_kses_post( $education );
        ?>
			</div>
		</div>
		
		<?php 
    }

    public static function wps_responsive_oembed( string $html ) {
        return '<div class="wps-team--res-oembed">' . $html . '</div>';
    }

    public static function get_the_content( int $post_id ) {
        add_filter( 'embed_oembed_html', get_called_class() . '::wps_responsive_oembed' );
        $content = get_the_content( null, false, $post_id );
        $content = apply_filters( 'the_content', $content );
        $content = wpautop( $content );
        $content = str_replace( ']]>', ']]&gt;', $content );
        remove_filter( 'embed_oembed_html', get_called_class() . '::wps_responsive_oembed' );
        echo $content;
        // phpcs:ignore WordPress.Security.EscapeOutput --safe-html
    }

    public static function parse_social_links( array $social_links ) {
        $links = '';
        foreach ( $social_links as $slink ) {
            $links .= sprintf(
                '<li class="wps-si--%s">
					<a href="%s" aria-label="%s"%s>%s</a>
				</li>',
                esc_attr( Utils::get_brand_name( $slink['social_icon']['icon'] ) ),
                esc_url_raw( $slink['social_link'] ),
                'Social Link',
                Utils::get_ext_url_params(),
                // phpcs:ignore WordPress.Security.EscapeOutput
                Icon_Manager::render_font_icon( $slink['social_icon'] )
            );
        }
        return $links;
    }

    public static function get_the_social_links_title( $args = [] ) {
        $args = shortcode_atts( [
            'title_tag' => 'h4',
        ], $args );
        $title_text = plugin()->translations->get( 'social_links_title', _x( 'Connect with me:', 'Public', 'wps-team' ) );
        printf( '<%1$s class="wps-team--block-title team-member--slinks-title">%2$s</%1$s>', sanitize_key( $args['title_tag'] ), esc_html( $title_text ) );
    }

    public static function get_the_social_links( int $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context'    => 'general',
            'show_title' => false,
            'title_tag'  => 'h4',
            'tag'        => 'div',
        ], $args );
        if ( !self::is_allowed_render( 'social', $args['context'] ) ) {
            return '';
        }
        $social_links = array_filter( (array) Utils::get_item_data( '_social_links', $post_id ) );
        if ( empty( $social_links ) ) {
            return;
        }
        $tag = $args['tag'];
        $render_string_key = ( $args['context'] === 'details' ? 'social_details' : 'social' );
        printf( '<%s class="wps-team--member-s-links wps-team--member-element">', esc_attr( $tag ) );
        if ( $args['show_title'] ) {
            self::get_the_social_links_title( $args );
        }
        ?>

			<ul <?php 
        Utils::shortcode_loader()->print_attribute_string( $render_string_key );
        ?>>
				<?php 
        echo self::parse_social_links( $social_links );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>
			</ul>

		<?php 
        printf( '</%s>', esc_attr( $tag ) );
    }

    public static function get_the_read_more_button_txt() {
        return plugin()->translations->get( 'read_more_btn_text', _x( 'Read More', 'Public', 'wps-team' ) );
    }

    public static function get_the_link_1_title() {
        return plugin()->translations->get( 'link_1_btn_text', _x( 'My Resume', 'Public', 'wps-team' ) );
    }

    public static function get_the_link_2_title() {
        return plugin()->translations->get( 'link_2_btn_text', _x( 'Hire Me', 'Public', 'wps-team' ) );
    }

    public static function get_the_action_links( int $post_id, $args = [] ) {
        if ( isset( $args['show_read_more'] ) && !isset( $args['show_read_more_btn'] ) ) {
            $args['show_read_more_btn'] = $args['show_read_more'];
            unset($args['show_read_more']);
        }
        $args = shortcode_atts( [
            'link_1'             => false,
            'link_2'             => false,
            'show_read_more_btn' => false,
            'card_action'        => 'single-page',
            'context'            => 'general',
        ], $args );
        switch ( $args['context'] ) {
            case 'details':
                $show_link_1 = Utils::shortcode_loader()->get_setting( 'show_details_link_1' );
                $show_link_2 = Utils::shortcode_loader()->get_setting( 'show_details_link_2' );
                $show_read_more_btn = false;
                break;
            case 'single':
                $show_link_1 = Utils::get_setting( 'single_link_1' );
                $show_link_2 = Utils::get_setting( 'single_link_2' );
                $show_read_more_btn = false;
                break;
            default:
                $show_link_1 = Utils::shortcode_loader()->get_setting( 'show_link_1' );
                $show_link_2 = Utils::shortcode_loader()->get_setting( 'show_link_2' );
                $show_read_more_btn = Utils::shortcode_loader()->get_setting( 'show_read_more_btn' );
                if ( $show_read_more_btn === null || $show_read_more_btn === '' ) {
                    $show_read_more_btn = Utils::shortcode_loader()->get_setting( 'show_read_more' );
                }
                break;
        }
        $show_link_1 = ( $show_link_1 === '' ? (bool) $args['link_1'] : wp_validate_boolean( $show_link_1 ) );
        $show_link_2 = ( $show_link_2 === '' ? (bool) $args['link_2'] : wp_validate_boolean( $show_link_2 ) );
        $show_read_more_btn = ( $show_read_more_btn === '' ? (bool) $args['show_read_more_btn'] : wp_validate_boolean( $show_read_more_btn ) );
        if ( !$show_link_1 && !$show_link_2 && !$show_read_more_btn ) {
            return '';
        }
        $link_1_value = Utils::get_item_data( '_link_1' );
        $link_2_value = Utils::get_item_data( '_link_2' );
        if ( empty( $link_1_value ) && empty( $link_2_value ) && !$show_read_more_btn ) {
            return '';
        }
        $html = '<div class="wps-team--action-links wps-team--member-element">';
        if ( $show_link_1 && !empty( $link_1_value ) ) {
            $ext_params = ( Utils::is_external_url( $link_1_value ) ? Utils::get_ext_url_params() : '' );
            $html .= sprintf(
                '<a href="%s" class="wps-team--btn wps-team--link-1"%s>%s</a>',
                esc_url( $link_1_value ),
                ( $ext_params ? ' ' . esc_attr( $ext_params ) : '' ),
                esc_html( self::get_the_link_1_title() )
            );
        }
        if ( $show_link_2 && !empty( $link_2_value ) ) {
            $ext_params = ( Utils::is_external_url( $link_2_value ) ? Utils::get_ext_url_params() : '' );
            $html .= sprintf(
                '<a href="%s" class="wps-team--btn wps-team--link-2"%s>%s</a>',
                esc_url( $link_2_value ),
                ( $ext_params ? ' ' . esc_attr( $ext_params ) : '' ),
                esc_html( self::get_the_link_2_title() )
            );
        }
        $normalized_action = Utils::normalize_card_action( (string) $args['card_action'], $post_id );
        if ( $show_read_more_btn && $normalized_action !== 'none' ) {
            $link_attrs = Utils::get_link_attrs_for_post( (int) $post_id, $normalized_action, 'wps-team--btn wps-team--read-more-btn' );
            $html .= Utils::render_link( $link_attrs, esc_html( self::get_the_read_more_button_txt() ) );
        }
        $html .= '</div>';
        echo $html;
        // phpcs:ignore WordPress.Security.EscapeOutput
    }

    /**
     * Bar/percentage display: empty or invalid values default to 100 so layout stays valid.
     */
    private static function normalize_skill_bar_percent( $raw ) : int {
        if ( null === $raw || '' === $raw || false === $raw ) {
            return 100;
        }
        if ( is_numeric( $raw ) ) {
            return min( 100, max( 0, (int) round( (float) $raw ) ) );
        }
        return 100;
    }

    public static function parse_skills( array $_skills ) {
        $skills = '';
        foreach ( $_skills as $skill ) {
            $name = sanitize_text_field( ( isset( $skill['skill_name'] ) ? (string) $skill['skill_name'] : '' ) );
            $val = self::normalize_skill_bar_percent( $skill['skill_val'] ?? null );
            $skills .= sprintf(
                '<li>
				<span class="skill-name">%1$s</span>
				<span class="skill-value">%2$d%3$s</span>
				<span class="skill-bar" data-width="%2$d" style="width: %2$d%3$s"></span>
			</li>',
                $name,
                $val,
                '%'
            );
        }
        return $skills;
    }

    /**
     * Skills list markup when values/bars are disabled (tag-style chips).
     */
    public static function parse_skills_tags( array $_skills ) {
        $html = '';
        foreach ( $_skills as $skill ) {
            if ( empty( $skill['skill_name'] ) ) {
                continue;
            }
            $name = sanitize_text_field( (string) $skill['skill_name'] );
            if ( $name === '' ) {
                continue;
            }
            $html .= sprintf( '<li class="wps--skill-tag"><span class="wps--skill-tag__label">%s</span></li>', esc_html( $name ) );
        }
        return $html;
    }

    /**
     * Whether member skills should show percentages/bars (true) or tag chips (false).
     * Missing meta matches admin default: with values.
     */
    private static function member_skills_with_values( int $post_id ) : bool {
        if ( !metadata_exists( 'post', $post_id, '_skills_with_value' ) ) {
            return true;
        }
        return wp_validate_boolean( get_post_meta( $post_id, '_skills_with_value', true ) );
    }

    public static function get_the_skills_title( $args = [] ) {
        $args = shortcode_atts( [
            'title_tag' => 'h4',
        ], $args );
        $title_text = plugin()->translations->get( 'skills_title', _x( 'Skills:', 'Public', 'wps-team' ) );
        printf( '<%1$s class="wps-team--block-title team-member--skills-title">%2$s</%1$s>', sanitize_key( $args['title_tag'] ), esc_html( $title_text ) );
    }

    public static function get_the_skills( int $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context'    => 'general',
            'title_tag'  => 'h4',
            'show_title' => false,
        ], $args );
        if ( !self::is_allowed_render( 'skills', $args['context'] ) ) {
            return '';
        }
        $skills = array_filter( (array) Utils::get_item_data( '_skills', $post_id ) );
        if ( empty( $skills ) ) {
            return;
        }
        $skills_with_values = self::member_skills_with_values( $post_id );
        if ( !$skills_with_values ) {
            $skills_inner = self::parse_skills_tags( $skills );
            if ( $skills_inner === '' ) {
                return;
            }
        } else {
            $skills_inner = self::parse_skills( $skills );
        }
        $wrap_classes = 'wps-team--member-skills wps-team--member-element';
        $list_classes = 'wps--skills';
        if ( !$skills_with_values ) {
            $wrap_classes .= ' wps-team--member-skills--tags';
            $list_classes .= ' wps--skills--tags';
        }
        ?>

		<div class="<?php 
        echo esc_attr( $wrap_classes );
        ?>">
			<?php 
        if ( $args['show_title'] ) {
            self::get_the_skills_title( $args );
        }
        ?>
			<ul class="<?php 
        echo esc_attr( $list_classes );
        ?>">
				<?php 
        echo wp_kses_post( $skills_inner );
        ?>
			</ul>
		</div>

		<?php 
    }

    public static function get_all_shortcodes() {
        global $wpdb;
        $shortcodes = wp_cache_get( 'wps_team_all_shortcodes', 'wps_team' );
        if ( false === $shortcodes ) {
            $shortcodes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wps_team ORDER BY created_at DESC", ARRAY_A );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            wp_cache_set( 'wps_team_all_shortcodes', $shortcodes, 'wps_team' );
        }
        return $shortcodes;
    }

    /**
     * Member info row label from taxonomy settings: single name for one term, plural for multiple.
     *
     * @param string $tax_root Root slug e.g. group, extra-one.
     * @param int    $term_count Assigned term count for this row.
     */
    public static function get_taxonomy_display_label_from_settings( string $tax_root, int $term_count ) : string {
        $key = Utils::to_field_key( $tax_root );
        $single = trim( (string) Utils::get_setting( $key . '_single_name' ) );
        $plural = trim( (string) Utils::get_setting( $key . '_plural_name' ) );
        $use_plural = $term_count !== 1;
        $text = ( $use_plural ? $plural : $single );
        if ( $text === '' ) {
            $text = ( $use_plural ? $single : $plural );
        }
        if ( $text === '' ) {
            return '';
        }
        if ( substr( $text, -1 ) !== ':' ) {
            $text .= ':';
        }
        return $text;
    }

    /**
     * Number of assigned terms for a taxonomy member field (for singular vs plural labels).
     *
     * @param string $field_key Taxonomy field key or other.
     * @param mixed  $val       Value from Utils::get_item_data().
     * @return int|null         Integer count for taxonomy fields; null if not a taxonomy field.
     */
    public static function get_taxonomy_label_term_count( string $field_key, $val ) : ?int {
        foreach ( Utils::get_taxonomy_roots( true ) as $tax_root ) {
            if ( $field_key !== Utils::get_taxonomy_name( $tax_root, true ) ) {
                continue;
            }
            if ( is_wp_error( $val ) ) {
                return 0;
            }
            if ( is_array( $val ) ) {
                return count( $val );
            }
            return 1;
        }
        return null;
    }

    /**
     * @param string   $field_key   Field or taxonomy member key.
     * @param string   $label_type  "icon" or text.
     * @param int|null $term_count  For taxonomy text labels: number of assigned terms (1 = singular). Omitted = plural.
     */
    public static function get_the_field_label( string $field_key, string $label_type = '', $term_count = null ) {
        $field_label = '';
        if ( $label_type === 'icon' ) {
            switch ( $field_key ) {
                case '_mobile':
                    $field_label = '<i class="fas fa-mobile-alt"></i>';
                    break;
                case '_telephone':
                    $field_label = '<i class="fas fa-phone"></i>';
                    break;
                case '_fax':
                    $field_label = '<i class="fas fa-fax"></i>';
                    break;
                case '_email':
                    $field_label = '<i class="fas fa-envelope"></i>';
                    break;
                case '_website':
                    $field_label = '<i class="fas fa-globe"></i>';
                    break;
                case '_experience':
                    $field_label = '<i class="fas fa-briefcase"></i>';
                    break;
                case '_company':
                    $field_label = '<i class="fas fa-building"></i>';
                    break;
                case '_address':
                    $field_label = '<i class="fas fa-map-marker-alt"></i>';
                    break;
                case Utils::get_taxonomy_name( 'group', true ):
                    $field_label = '<i class="fas fa-tags"></i>';
                    break;
            }
            if ( !empty( $field_label ) ) {
                $field_label = '<span class="wps--info-label info-label--icon">' . $field_label . '</span>';
            }
        } else {
            foreach ( Utils::get_taxonomy_roots( true ) as $tax_root ) {
                if ( $field_key !== Utils::get_taxonomy_name( $tax_root, true ) ) {
                    continue;
                }
                $count = ( $term_count !== null ? (int) $term_count : 2 );
                $raw = self::get_taxonomy_display_label_from_settings( $tax_root, $count );
                if ( $raw !== '' ) {
                    $field_label = esc_html( $raw );
                }
                break;
            }
            if ( $field_label === '' ) {
                switch ( $field_key ) {
                    case '_mobile':
                        $field_label = plugin()->translations->get( 'mobile_meta_label', _x( 'Mobile:', 'Public', 'wps-team' ) );
                        break;
                    case '_telephone':
                        $field_label = plugin()->translations->get( 'phone_meta_label', _x( 'Telephone:', 'Public', 'wps-team' ) );
                        break;
                    case '_fax':
                        $field_label = plugin()->translations->get( 'fax_meta_label', _x( 'Fax:', 'Public', 'wps-team' ) );
                        break;
                    case '_email':
                        $field_label = plugin()->translations->get( 'email_meta_label', _x( 'Email:', 'Public', 'wps-team' ) );
                        break;
                    case '_website':
                        $field_label = plugin()->translations->get( 'website_meta_label', _x( 'Website:', 'Public', 'wps-team' ) );
                        break;
                    case '_experience':
                        $field_label = plugin()->translations->get( 'experience_meta_label', _x( 'Experience:', 'Public', 'wps-team' ) );
                        break;
                    case '_company':
                        $field_label = plugin()->translations->get( 'company_meta_label', _x( 'Company:', 'Public', 'wps-team' ) );
                        break;
                    case '_address':
                        $field_label = plugin()->translations->get( 'address_meta_label', _x( 'Address:', 'Public', 'wps-team' ) );
                        break;
                }
            }
            if ( !empty( $field_label ) ) {
                $field_label = '<strong class="wps--info-label info-label--text">' . $field_label . '</strong>';
            }
        }
        return $field_label;
    }

    public static function get_extra_info_fields( $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
            'fields'  => [],
        ], $args );
        $fields = (array) $args['fields'];
        $sorted_fields = Utils::get_sorted_elements();
        $display_fields = [];
        $supported_sorted_fields = array_intersect( $sorted_fields, array_merge( [
            '_telephone',
            '_fax',
            '_email',
            '_website',
            '_experience',
            '_company',
            '_mobile',
            '_address'
        ], Utils::get_active_taxonomies( true ) ) );
        $supported_sorted_fields = array_values( $supported_sorted_fields );
        foreach ( $supported_sorted_fields as $s_field ) {
            $s_field_alt = ltrim( $s_field, '_' );
            $s_field_status = self::get_render_info( $s_field_alt, $args['context'] );
            if ( $s_field_status == 'true' || $s_field_status != 'false' && in_array( $s_field, $fields ) ) {
                $display_fields[] = $s_field;
            }
        }
        return array_intersect( $display_fields, $supported_sorted_fields );
    }

    public static function get_the_taxonomy_values( array $tax_values, string $separator = ', ' ) {
        return implode( '', array_map( function ( $i, $name ) use($tax_values, $separator) {
            $output = '<span class="wps--field-item">' . esc_html( $name ) . '</span>';
            if ( $i < count( $tax_values ) - 1 ) {
                $output .= '<span class="wps-field-sep">' . esc_html( $separator ) . '</span>';
            }
            return $output;
        }, array_keys( $tax_values ), $tax_values ) );
    }

    /**
     * Label icon for a taxonomy row: taxonomy setting icon, then built-in default.
     *
     * @param \WP_Term[] $terms Assigned terms (unused; kept for signature stability).
     */
    public static function get_taxonomy_row_label_icon_html( array $terms, string $field_key ) {
        $taxonomy_icon_classes = self::get_taxonomy_default_icon_class_from_settings( $field_key );
        if ( $taxonomy_icon_classes !== '' ) {
            return sprintf( '<span class="wps--info-label info-label--icon"><i class="%s" aria-hidden="true"></i></span>', esc_attr( $taxonomy_icon_classes ) );
        }
        return wp_kses_post( self::get_the_field_label( $field_key, 'icon' ) );
    }

    /**
     * Icon class string from taxonomy settings (edit-tags bar), keyed by member field slug.
     *
     * @param string $field_key e.g. wps_team_language.
     */
    public static function get_taxonomy_default_icon_class_from_settings( $field_key ) {
        foreach ( Utils::get_taxonomy_roots( true ) as $root ) {
            if ( $field_key !== Utils::get_taxonomy_name( $root, true ) ) {
                continue;
            }
            $page_key = Utils::to_field_key( $root );
            $raw = Utils::get_setting( $page_key . '_taxonomy_icon' );
            if ( !is_string( $raw ) || $raw === '' ) {
                return '';
            }
            $data = json_decode( $raw, true );
            if ( !is_array( $data ) || empty( $data['icon'] ) || empty( $data['library'] ) ) {
                return '';
            }
            return Icon_Manager::get_term_icon_class_string( $data );
        }
        return '';
    }

    private static function render_contact_field(
        string $field,
        string $val,
        string $label_html,
        string $format,
        string $protocol,
        string $translation_key,
        string $default_text,
        string $link_attrs = ''
    ) {
        $class = esc_attr( 'wps--info-field' . $field );
        // Sanitize value by type
        if ( $protocol === 'mailto:' ) {
            $link_val = antispambot( sanitize_email( $val ) );
        } elseif ( in_array( $protocol, ['tel:', 'fax:'], true ) ) {
            $link_val = Utils::sanitize_phone_number( $val );
        } else {
            $link_val = esc_url( $val );
        }
        if ( $format === 'linked_raw' ) {
            return sprintf(
                '<li class="%s">%s<a class="wps--info-text" href="%s%s" %s>%s</a></li>',
                $class,
                $label_html,
                $protocol,
                $link_val,
                $link_attrs,
                esc_html( $val )
            );
        }
        if ( $format === 'linked_text' ) {
            $text = esc_html( plugin()->translations->get( $translation_key, $default_text ) );
            return sprintf(
                '<li class="%s">%s<a class="wps--info-text" href="%s%s" %s>%s</a></li>',
                $class,
                $label_html,
                $protocol,
                $link_val,
                $link_attrs,
                $text
            );
        }
        // No link
        return sprintf(
            '<li class="%s">%s<span class="wps--info-text">%s</span></li>',
            $class,
            $label_html,
            esc_html( $val )
        );
    }

    public static function get_the_extra_info( int $post_id, $args = [] ) {
        // Merge default arguments
        $args = shortcode_atts( [
            'context'            => 'general',
            'fields'             => [],
            'info_style'         => '',
            'info_style_default' => 'center-aligned',
            'label_type'         => '',
            'label_type_default' => 'icon',
            'items_border'       => false,
            'info_top_border'    => false,
        ], $args );
        $fields = self::get_extra_info_fields( $args );
        if ( empty( $fields ) ) {
            return;
        }
        // Collect wrapper classes
        $info_classes = ['team-member--info-wrapper'];
        $info_style = ( $args['info_style'] ?: $args['info_style_default'] );
        $label_type = ( $args['label_type'] ?: $args['label_type_default'] );
        $complex_styles = ['start-aligned-alt', 'center-aligned-alt', 'center-aligned-combined'];
        // $info_style = 'start-aligned';
        // $info_style = 'start-aligned-alt';
        // $info_style = 'center-aligned';
        // $info_style = 'center-aligned-alt';
        // $info_style = 'center-aligned-combined';
        // $info_style = 'justify-aligned';
        if ( in_array( $info_style, $complex_styles, true ) ) {
            $info_classes[] = 'wps-team--info-tabled';
        }
        if ( $args['items_border'] ) {
            $info_classes[] = 'wps-team--info-bordered';
        }
        $fields_html = '';
        foreach ( $fields as $field ) {
            $val = Utils::get_item_data( $field, $post_id );
            if ( empty( $val ) ) {
                continue;
            }
            $taxonomy_term_count = self::get_taxonomy_label_term_count( $field, $val );
            $field_label_html = wp_kses_post( Utils::get_the_field_label( $field, $label_type, $taxonomy_term_count ) );
            // contains HTML
            switch ( $field ) {
                case '_mobile':
                    $format = Utils::get_setting( 'mobile_display_format' );
                    $fields_html .= self::render_contact_field(
                        $field,
                        $val,
                        $field_label_html,
                        $format,
                        'tel:',
                        'mobile_link_text',
                        _x( 'Call on Mobile', 'Public', 'wps-team' )
                    );
                    break;
                case '_telephone':
                    $format = Utils::get_setting( 'telephone_display_format' );
                    $fields_html .= self::render_contact_field(
                        $field,
                        $val,
                        $field_label_html,
                        $format,
                        'tel:',
                        'phone_link_text',
                        _x( 'Call on Telephone', 'Public', 'wps-team' )
                    );
                    break;
                case '_fax':
                    $format = Utils::get_setting( 'fax_display_format' );
                    $fields_html .= self::render_contact_field(
                        $field,
                        $val,
                        $field_label_html,
                        $format,
                        'fax:',
                        'fax_link_text',
                        _x( 'Send Fax', 'Public', 'wps-team' )
                    );
                    break;
                case '_email':
                    $format = Utils::get_setting( 'email_display_format' );
                    $fields_html .= self::render_contact_field(
                        $field,
                        $val,
                        $field_label_html,
                        $format,
                        'mailto:',
                        'email_link_text',
                        _x( 'Send Email', 'Public', 'wps-team' )
                    );
                    break;
                case '_website':
                    $format = Utils::get_setting( 'website_display_format' );
                    $fields_html .= self::render_contact_field(
                        $field,
                        $val,
                        $field_label_html,
                        $format,
                        '',
                        // protocol not needed for website
                        'website_link_text',
                        _x( 'Visit Website', 'Public', 'wps-team' ),
                        ( Utils::is_external_url( $val ) ? Utils::get_ext_url_params() : '' )
                    );
                    break;
                case '_experience':
                case '_company':
                case '_address':
                    $fields_html .= sprintf(
                        '<li class="%s">%s<span class="wps--info-text">%s</span></li>',
                        esc_attr( 'wps--info-field' . $field ),
                        $field_label_html,
                        esc_html( $val )
                    );
                    break;
                default:
                    // Handle taxonomy fields dynamically
                    foreach ( Utils::get_taxonomy_roots() as $taxonomy ) {
                        if ( $field === Utils::get_taxonomy_name( $taxonomy, true ) ) {
                            if ( 'icon' === $label_type ) {
                                $field_label_html = wp_kses_post( self::get_taxonomy_row_label_icon_html( $val, $field ) );
                            }
                            $fields_html .= sprintf(
                                '<li class="%s">%s<span class="wps--info-text">%s</span></li>',
                                esc_attr( 'wps--info-field_' . $taxonomy ),
                                $field_label_html,
                                wp_kses_post( Utils::get_the_taxonomy_values( wp_list_pluck( $val, 'name' ) ) )
                            );
                        }
                    }
                    break;
            }
        }
        if ( empty( $fields_html ) ) {
            return '';
        }
        $info_classes[] = 'info--' . $info_style;
        if ( $args['info_top_border'] ) {
            $info_classes[] = 'wps-team--info-top-border';
        }
        printf( '<div class="%s"><ul class="wps--member-info">%s</ul></div>', esc_attr( Utils::join_classes( $info_classes ) ), $fields_html );
    }

}